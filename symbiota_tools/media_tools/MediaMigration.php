<?php
include_once($SERVER_ROOT . '/config/dbconnection.php');

class MediaMigration {

	private $conn;
	private $collid;
	private $collMetaArr;

	private $transferThumbnail = false;
	private $transferWeb = false;
	private $transferLarge = false;
	private $urlMatchTerm;
	private $deleteSource = false;
	private $sourcePathPrefix;
	private $targetPathPrefix;
	private $urlPrefix;

	private $logFH;
	private $verboseMode = 0;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon('write');
	}

	function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
		if($this->logFH){
			fwrite($this->logFH,"\n\n");
			fclose($this->logFH);
		}
	}

	public function migrateMedia($queryTerm, $mediaIdStart = 0, $limit = 1000){
		set_time_limit(1200);
		$this->setVerboseMode(1);
		$this->outputStr('Starting media file transfer (' . date('Y-m-d H:i:s') . ')');
		if(!$queryTerm){
			$this->outputStr('FATAL ERROR: URL matching term has not been set');
			exit;
		}
		$this->outputStr('Querying databnase media table based on search term: ' . $targetQueryField);
		$sqlBase = 'FROM media m ';
		if(is_numeric($this->collid)){
			$sqlBase .= 'INNER JOIN omoccurrences o ON m.occid = o.occid ';
		}
		$sqlBase .= 'WHERE (m.' . $targetQueryField . ' LIKE ?) ';
		//$sqlBase .= 'AND (m.mediaID = 47708) ';		//Used for debugging
		$paramArr = array($queryTerm . '%');
		$typeStr = 's';
		if(is_numeric($this->collid)){
			if($this->collid){
				$sqlBase .= 'AND (o.collid = ?)';
				$paramArr[] = $this->collid;
				$typeStr .= 'i';
			}
		}
		else{
			//Target field images
			$sqlBase .= 'AND (m.occid IS NULL) ';
		}
		if($mediaIdStart){
			$sqlBase .= 'AND (m.mediaID > ?) ';
			$paramArr[] = $mediaIdStart;
			$typeStr .= 'i';
		}
		$sql = 'SELECT m.mediaID, m.occid, m.originalUrl, m.url, m.thumbnailUrl, m.mediaMD5, m.pixelXDimension, m.pixelYDimension, m.fileSize, m.fileSizeThumbnail, m.fileSizeMedium ' . $sqlBase;
		if($limit) $sql .= 'LIMIT ' . $limit;

		//Get count
		$targetCnt = 0;
		$cntSql = 'SELECT COUNT(m.mediaID) AS cnt ' . $sqlBase;
		if($cntStmt = $this->conn->prepare($cntSql)){
			$cntStmt->bind_param($typeStr, ...$paramArr);
			$cntStmt->execute();
			$cntStmt->bind_result($targetCnt);
			$cntStmt->fetch();
			$cntStmt->close();
		}
		$this->outputStr('Target count: ' . $targetCnt, 1);

		$cnt = 0;
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param($typeStr, ...$paramArr);
			$stmt->execute();
			$rs = $stmt->get_result();
			while($rowArr = $rs->fetch_assoc()){
				if($dataArr = $this->processMediaRecord($rowArr)){
					if($this->databaseMediaRecord($rowArr['mediaID'], $dataArr)){
						$cnt++;
						$recordID = $rowArr['occid'];
						$link = $GLOBALS['CLIENT_ROOT'] . '/collections/individual/index.php?occid=' . $rowArr['occid'];
						if(!$rowArr['occid']){
							$link = $GLOBALS['CLIENT_ROOT'] . '/imagelib/imgdetails.php?mediaid=' . $rowArr['mediaID'];
							$recordID = $rowArr['mediaID'];
						}
						$this->outputStr($cnt.': Processing: <a href="' . $link . '" target="_blank">#' . $recordID . '</a>');
					}
				}


			}
			$rs->free();
			$stmt->close();
		}
		$this->outputStr('Done transferring ' . $cnt . ' media files (' . date('Y-m-d H:i:s') . ')');
		/*
		 * ALTER TABLE `media`
		 *   ADD COLUMN `fileSize` INT NULL AFTER `pixelXDimension`,
		 *   ADD COLUMN `fileSizeThumbnail` INT NULL AFTER `fileSize`,
		 *   ADD COLUMN `fileSizeMedium` INT NULL AFTER `fileSizeThumbnail`;
		 */
	}

	public function migrateMediaViaDataFile($dataFile){
		if(!file_exists($dataFile)){
			$this->outputStr('FATAL ERROR: source data file has not been provided');
			exit;
		}
		if (($fh = fopen($dataFile, 'r')) !== FALSE) {
			while (($rowArr = fgetcsv($fh)) !== FALSE) {
				if(empty($rowArr['mediaID'])){
					$this->outputStr('ERROR: skipping record because mediaID has not been provided');
					continue;
				}
				$dataArr = $this->processMediaRecord($rowArr);
				$sql = 'UPDATE media SET ';
				foreach($dataArr as $fieldName => $value){
					$sql .= $fieldName . ' = "' . $value;
				}

				$this->databaseMediaRecord($updateArr);
				$cnt++;
				$recordID = $rowArr['occid'];
				$link = $GLOBALS['CLIENT_ROOT'] . '/collections/individual/index.php?occid=' . $rowArr['occid'];
				if(!$rowArr['occid']){
					$link = $GLOBALS['CLIENT_ROOT'] . '/imagelib/imgdetails.php?mediaid=' . $rowArr['mediaID'];
					$recordID = $rowArr['mediaID'];
				}
				$this->outputStr($cnt.': Processing: <a href="' . $link . '" target="_blank">#' . $recordID . '</a>');
			}
			fclose($fh);
		}
	}

	private function processMediaRecord($recordArr){
		if(!$this->sourcePathPrefix){
			$this->outputStr('FATAL ERROR: source path has not been provided');
			exit;
		}
		if(!is_writable($this->sourcePathPrefix)){
			$this->outputStr('FATAL ERROR: source path is not writable (source: ' . $this->sourcePathPrefix . ')');
			exit;
		}
		if(!$this->targetPathPrefix){
			$this->outputStr('FATAL ERROR: target path has not been provided');
			exit;
		}
		if(!is_writable($this->targetPathPrefix)){
			$this->outputStr('FATAL ERROR: target path is not writable (target: ' . $this->targetPathPrefix . ')');
			exit;
		}
		$dataReturnArr = array();
		$urlFieldArr = array();
		if($this->transferLarge) $urlFieldArr[] = 'originalUrl';
		if($this->transferWeb) $urlFieldArr[] = 'url';
		if($this->transferThumbnail) $urlFieldArr[] = 'thumbnailUrl';
		foreach($urlFieldArr as $urlField){
			$transferFile = true;
			if($recordArr[$urlField] && strpos($recordArr[$urlField], $this->urlMatchTerm) === 0){
				$pathFrag = substr($recordArr[$urlField], strlen($this->urlMatchTerm));
				$sourcePath = $this->sourcePathPrefix . $pathFrag;
				$targetPath = $this->targetPathPrefix . $pathFrag;
				//Run some test to ensure that files can be transferred
				if(!is_writable($sourcePath)){
					if(file_exists($targetPath)){
						//File has already been transferred (perhaps mapping in db twice?), thus just remap media urls in db
						$transferFile = false;
					}
					else{
						$this->outputStr('Source file is not writable: ' . $sourcePath, 1);
						continue;
					}
				}
				//make sure that target base path exists
				$targetBasePath = substr($targetPath, 0, strrpos($targetPath, '/'));
				if(!file_exists($targetBasePath)){
					mkdir($targetBasePath, 0755, true);
				}
				//Start transfer
				if($transferFile){
					if($this->deleteSource){
						if(!rename($sourcePath, $targetPath)){
							$this->outputStr('Failed to transfer file (' . $sourcePath . ' => ' . $targetPath . ')', 1);
							continue;
						}
					}
					else{
						if(!copy($sourcePath, $targetPath)){
							$this->outputStr('Failed to copy file (' . $targetPath . ')', 1);
							continue;
						}
					}
				}

				$dataReturnArr[$urlField] = $this->urlPrefix . $pathFrag;
				if($urlField == 'originalUrl'){
					if(!$recordArr['mediaMD5']){
						$dataReturnArr['mediaMD5'] = md5_file($targetPath);
					}
					if(!$recordArr['pixelXDimension']){
						$dim = getimagesize($targetPath);
						if ($dim !== false) {
							$dataReturnArr['pixelXDimension'] = $dim[0];
							$dataReturnArr['pixelYDimension'] = $dim[1];
						}
					}
					if(!$recordArr['fileSize']){
						$dataReturnArr['fileSize'] = round(filesize($targetPath) / 1024);
					}
				}
				elseif($urlField == 'url'){
					$dataReturnArr['fileSizeMedium'] = round(filesize($targetPath) / 1024);
				}
				elseif($urlField == 'thumbnailUrl'){
					$dataReturnArr['fileSizeThumbnail'] = round(filesize($targetPath) / 1024);
				}
			}
		}
		return $dataReturnArr;
	}

	//Support functions
	private function databaseMediaRecord($mediaID, $inputArr){
		$status = false;
		$fieldArr = array('originalUrl' => 's', 'url' => 's', 'thumbnailUrl' => 's', 'mediamd5' => 's', 'pixelxdimension' => 'i', 'pixelydimension' => 'i', 'filesize' => 'i', 'filesizethumbnail' => 'i', 'filesizemedium' => 'i');
		$inputFieldArr = array();
		$paramArr = array();
		$typeStr = '';
		foreach($inputArr as $field => $value){
			if(isset($fieldArr[$field])){
				$inputFieldArr[] = $field;
				$paramArr[] = $value;
				$typeStr .= $fieldArr[$field];
			}
		}
		if($inputFieldArr){
			$sql = 'UPDATE media SET ' . implode(' = ?, ', $inputFieldArr) . ' = ? WHERE mediaID = ?';
			$paramArr[] = $mediaID;
			$typeStr .= 'i';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param($typeStr, ...$paramArr);
				$stmt->execute();
				if($stmt->error){
					$this->outputStr('ERROR saving new paths (mediaID = ' . $mediaID . '): ' . $stmt->error, 1);
				}
				elseif(!$stmt->affected_rows){
					$this->outputStr('Nothing changed (mediaID = ' . $mediaID . ')', 1);
				}
				else $status = true;
				$stmt->close();
			}
		}
		return $status;
	}

	private function setLogFH(){
		$logPath = 'logs/mediaMigration_' . date('Y-m-d') . '.log';
		$this->logFH = fopen($logPath, 'a');
	}

	private function outputStr($str, $indexLevel = 0){
		//verboseMode: 0 = silent, 1 = out to screen, 2 = out to commandline
		if($str && $this->verboseMode){
			if($this->logFH){
				fwrite($this->logFH, str_repeat("\t", $indexLevel) . strip_tags($str) . "\n");
			}
			if($this->verboseMode == 1){
				echo '<li style="' . ($indexLevel ? 'margin-left:' . ($indexLevel * 15) . 'px' : '') . '">' . $str . '</li>';
				if (ob_get_level() > 0) {
					ob_flush();
				}
				flush();
			}
			elseif($this->verboseMode == 2){
				echo ($indexLevel ? "\t" : '') . $str . "\n";
				if (ob_get_level() > 0) {
					ob_flush();
				}
				flush();
			}
		}
	}

	//Misc data return functions
	public function getCollectionMeta(){
		$retArr = array();
		$sql = 'SELECT collid, collectionname, CONCAT_WS(":",institutioncode,collectioncode) as instcode FROM omcollections ORDER BY collectionname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid]= $r->collectionname.' ('.$r->instcode.')';
		}
		$rs->free();
		return $retArr;
	}

	//Setters and getters
	public function setCollid($id){
		if(is_numeric($id)){
			$this->collid = $id;
			$sql = 'SELECT collectionname, CONCAT_WS("_",institutioncode,collectioncode) as instcode FROM omcollections WHERE collid = '.$id;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->collMetaArr['name']= $r->collectionname;
				$this->collMetaArr['code']= $r->instcode;
			}
			$rs->free();
		}
	}

	public function setTransferThumbnail($bool){
		if($bool) $this->transferThumbnail = true;
		else $this->transferThumbnail = false;
	}

	public function setTransferWeb($bool){
		if($bool) $this->transferWeb = true;
		else $this->transferWeb = false;
	}

	public function setTransferLarge($bool){
		if($bool) $this->transferLarge = true;
		else $this->transferLarge = false;
	}

	public function setUrlMatchTerm($str){
		$this->urlMatchTerm = $str;
	}

	public function setDeleteSource($bool){
		$this->deleteSource = $bool;
	}

	public function setSourcePathPrefix($path){
		if(substr($path, -1) != '/') $path .= '/';
		$this->sourcePathPrefix = $path;
	}

	public function setTargetPathPrefix($path){
		$this->targetPathPrefix = $path;
	}

	public function setUrlPrefix($url){
		$this->urlPrefix = $url;
	}

	public function setVerboseMode($mode){
		if(is_numeric($mode)) $this->verboseMode = $mode;
		if($this->verboseMode == 1 || $this->verboseMode == 3){
			$this->setLogFH();
		}
	}
}
?>
