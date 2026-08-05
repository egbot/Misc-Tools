<?php
@include_once($SERVER_ROOT . '/config/dbconnection.php');

class MediaMigration {

	private $conn;
	private $conditionArr;
	private $collid;

	private $transferThumbnail = false;
	private $transferWeb = false;
	private $transferLarge = false;
	private $urlMatchTerm;
	private $deleteSource = false;
	private $sourcePathPrefix;
	private $targetPathPrefix;
	private $targetUrlPrefix;

	private $logFH;
	private $verboseMode = 0;

	function __construct() {
	}

	function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
		if($this->logFH){
			fwrite($this->logFH,"\n\n");
			fclose($this->logFH);
		}
	}

	public function migrateMediaViaDatabase($mediaIdStart = 0, $limit = 1000){
		set_time_limit(1200);
		if(!$this->conn){
			$this->setDatabaseConnection();
		}
		if(!$this->conditionArr){
			$this->outputStr('FATAL ERROR: query terms have not been defined');
			exit;
		}
		$this->verifyInputVariables();

		$this->setLogFH();
		$this->outputStr('Starting media file transfer (' . date('Y-m-d H:i:s') . ')');
		$this->outputStr('Querying database media table based on search term: ' . $this->getConditionStr());
		$paramArr = array();
		$typeStr = '';
		$sqlBase = 'FROM media m ';
		if($this->collid){
			$sqlBase .= 'INNER JOIN omoccurrences o ON m.occid = o.occid ';
		}
		$sqlBase .= 'WHERE ';
		$fieldArr = $this->getFieldArr();
		$delimiter = '';
		foreach($this->conditionArr as $fieldName => $conditionArr){
			if(array_key_exists($fieldName, $fieldArr) || $fieldName == 'occid'){
				foreach($conditionArr as $condition => $value){
					if($condition == 'ISNULL'){
						$sqlBase .= $delimiter . 'm.' . $fieldName . ' IS NULL ';
					}
					elseif($condition == 'NOTNULL'){
						$sqlBase .= $delimiter . 'm.' . $fieldName . ' IS NOT NULL ';
					}
					else{
						if($condition != 'LIKE'){
							$condition = '=';
						}
						$sqlBase .= $delimiter . 'm.' . $fieldName . ' ' . $condition . ' ? ';
						$paramArr[] = $value;
						if(!empty($fieldArr[$fieldName])) $typeStr .= $fieldArr[$fieldName];
						elseif($fieldName == 'occid') $typeStr .= 'i';
						else $typeStr .= 's';
					}
					$delimiter = 'AND ';
				}
			}
		}
		if($this->collid){
			$sqlBase .= $delimiter . '(o.collid = ?)';
			$paramArr[] = $this->collid;
			$typeStr .= 'i';
		}
		if($mediaIdStart){
			$sqlBase .= $delimiter . '(m.mediaID > ?) ';
			$paramArr[] = $mediaIdStart;
			$typeStr .= 'i';
		}

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
		$this->outputStr('Target count: ' . $targetCnt);

		$cnt = 0;
		$sql = 'SELECT m.mediaID, m.occid, m.' . implode(', m.', array_keys($fieldArr)) . ' ' . $sqlBase;
		if($limit) $sql .= 'LIMIT ' . $limit;
		if($stmt = $this->conn->prepare($sql)){
			$stmt->bind_param($typeStr, ...$paramArr);
			$stmt->execute();
			$rs = $stmt->get_result();
			while($rowArr = $rs->fetch_assoc()){
				if($dataArr = $this->processMediaRecord($rowArr)){
					if($this->databaseMediaRecord($rowArr['mediaID'], $dataArr)){
						$cnt++;
						if($cnt%1000 === 0){
							$this->outputStr($cnt . ' records processed (' . date('Y-m-d H:i:s') . ')', 1);
						}
						/*
						$recordID = $rowArr['occid'];
						$link = $GLOBALS['CLIENT_ROOT'] . '/collections/individual/index.php?occid=' . $rowArr['occid'];
						if(!$rowArr['occid']){
							$link = $GLOBALS['CLIENT_ROOT'] . '/imagelib/imgdetails.php?mediaid=' . $rowArr['mediaID'];
							$recordID = $rowArr['mediaID'];
						}
						$this->outputStr($cnt.': Processing: <a href="' . $link . '" target="_blank">#' . $recordID . '</a>');
						*/
					}
				}


			}
			$rs->free();
			$stmt->close();
		}
		$this->outputStr('Done! Processed ' . $cnt . ' media files (' . date('Y-m-d H:i:s') . ')');
		/*
		 * ALTER TABLE `media`
		 *   ADD COLUMN `fileSize` INT NULL AFTER `pixelXDimension`,
		 *   ADD COLUMN `fileSizeThumbnail` INT NULL AFTER `fileSize`,
		 *   ADD COLUMN `fileSizeMedium` INT NULL AFTER `fileSizeThumbnail`;
		 */
	}

	private function databaseMediaRecord($mediaID, $inputArr){
		$status = false;
		$fieldArr = $this->getFieldArr();
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
			try{
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
			catch(Exception $e){
				$this->outputStr('FATAL ERROR writing to database: ' . $e->getMessage());
				exit;
			}
		}
		return $status;
	}

	public function migrateMediaViaDataFile($dataFile){
		if(!file_exists($dataFile)){
			$this->outputStr('FATAL ERROR: source data file has not been provided');
			exit;
		}
		$this->verifyInputVariables();
		$this->setLogFH();
		$this->outputStr('Starting media file transfer via input file (' . date('Y-m-d H:i:s') . ')');
		if (($inputFH = fopen($dataFile, 'r')) !== FALSE) {
			$outputFile = 'data/mediaUpdateFile_' . time() . '.sql';
			$outputFH = null;
			if (($outputFH = fopen($outputFile, 'x')) === FALSE) {
				$this->outputStr('FATAL ERROR: Unable to create a writable output file');
				exit;
			}
			$cnt = 0;
			// Read header in order to create a map for the column names
			$headerMap = fgetcsv($inputFH);
			if(!$headerMap){
				$this->outputStr('FATAL ERROR: Unable to create header map');
				exit;
			}
			$fieldArr = $this->getFieldArr();
			while (($inputArr = fgetcsv($inputFH)) !== FALSE) {
				$recordArr = array_combine($headerMap, $inputArr);
				if(empty($recordArr['mediaID'])){
					$this->outputStr('ERROR: skipping record because mediaID has not been provided');
					continue;
				}
				if($dataArr = $this->processMediaRecord($recordArr)){
					$sql = 'UPDATE media SET ';
					$delimiter = '';
					foreach($dataArr as $fieldName => $value){
						if(array_key_exists($fieldName, $fieldArr)){
							$sql .= $delimiter . $fieldName . ' = ';
							if($fieldArr[$fieldName] == 'i'){
								$sql .= $value;
							}
							else{
								$sql .= '"' . $value . '"';
							}
							$delimiter = ', ';
						}
					}
					$sql .= ' WHERE mediaID = ' . $recordArr['mediaID'] . ';' . "\n";
					if (fwrite($outputFH, $sql) === FALSE) {
						$this->outputStr('FATAL ERROR: skipping record because mediaID has not been provided');
						exit;
					}
					$cnt++;
					if($cnt%1000 === 0){
						$this->outputStr($cnt . ' records processed (' . date('Y-m-d H:i:s') . ')', 1);
					}
				}
			}
			fclose($inputFH);
			$this->outputStr('Done transferring ' . $cnt . ' media files (' . date('Y-m-d H:i:s') . ')');
		}
		/*
		 * Example SQL for extracting records to remap
		 * SELECT mediaID, IFNULL(originalUrl, "") as originalUrl, IFNULL(url, "") as url, IFNULL(thumbnailUrl, "") as thumbnailUrl, IFNULL(mediaMD5, "") as mediaMD5,
		 * IFNULL(pixelXDimension, "") as pixelXDimension, IFNULL(pixelYDimension, "") as pixelYDimension, IFNULL(fileSize, "") as fileSize,
		 * IFNULL(fileSizeThumbnail, "") as fileSizeThumbnail, IFNULL(fileSizeMedium, "") as fileSizeMedium
		 * FROM media
		 * WHERE originalUrl LIKE "https://media01.symbiota.org/media/%" and occid IS NOT NULL
		 */
	}

	private function processMediaRecord($recordArr){
		$dataReturnArr = array();
		$urlFieldArr = array('originalUrl', 'url', 'thumbnailUrl');
		foreach($urlFieldArr as $urlField){
			if($recordArr[$urlField]){
				$transferFile = false;
				if($urlField == 'originalUrl' && $this->transferLarge) $transferFile = true;
				if($urlField == 'url' && $this->transferWeb) $transferFile = true;
				if($urlField == 'thumbnailUrl' && $this->transferThumbnail) $transferFile = true;

				$pathFrag = substr($recordArr[$urlField], strlen($this->urlMatchTerm));
				$sourcePath = $this->sourcePathPrefix . $pathFrag;
				$targetPath = $this->targetPathPrefix . $pathFrag;
				$activePath = $sourcePath;
				//Run some test to ensure that source exists and writable, if to be transferred
				if(file_exists($targetPath)){
					//Target file exists, thus no need to transferred (perhaps mapped in db twice?), and will use that to collect attributes
					if($transferFile){
						$dataReturnArr[$urlField] = $this->targetUrlPrefix . $pathFrag;
					}
					$transferFile = false;
					$activePath = $targetPath;
					if($this->deleteSource && file_exists($sourcePath)){
						if(!unlink($sourcePath)){
							$this->outputStr('WARNING: Unable to delete source file: ' . $sourcePath, 1);
						}
					}
				}
				else{
					if(!file_exists($sourcePath)){
						$this->outputStr('WARNING: Source file does not exist: ' . $sourcePath, 1);
						continue;
					}
					if($this->deleteSource){
						if(!is_writable($sourcePath)){
							$this->outputStr('WARNING: Source file is not writable: ' . $sourcePath, 1);
							continue;
						}
					}
				}
				if($transferFile){
					//make sure the full target base path exists
					$targetBasePath = substr($targetPath, 0, strrpos($targetPath, '/'));
					if(!file_exists($targetBasePath)){
						if(!mkdir($targetBasePath, 0755, true)){
							$this->outputStr('ERROR: Unable to create target directory: ' . $targetBasePath, 1);
							$transferFile = false;
						}
					}
					if($transferFile){
						//Start transfer
						if($this->deleteSource){
							if(rename($sourcePath, $targetPath)){
								$dataReturnArr[$urlField] = $this->targetUrlPrefix . $pathFrag;
								$activePath = $targetPath;
							}
							else{
								$this->outputStr('ERROR Failed to transfer file (' . $sourcePath . ' => ' . $targetPath . ')', 1);
							}
						}
						else{
							if(copy($sourcePath, $targetPath)){
								$dataReturnArr[$urlField] = $this->targetUrlPrefix . $pathFrag;
								$activePath = $targetPath;
							}
							else{
								$this->outputStr('Failed to copy file (' . $targetPath . ')', 1);
							}
						}
					}
				}

				if($urlField == 'originalUrl'){
					if(!$recordArr['mediaMD5']){
						@$hash = md5_file($activePath);
						if($hash) $dataReturnArr['mediaMD5'] = $hash;
					}
					if(!$recordArr['pixelXDimension']){
						$dim = getimagesize($activePath);
						if ($dim !== false) {
							$dataReturnArr['pixelXDimension'] = $dim[0];
							$dataReturnArr['pixelYDimension'] = $dim[1];
						}
					}
					if(!$recordArr['fileSize']){
						$size = filesize($activePath);
						if($size) $dataReturnArr['fileSize'] = round($size / 1024);
					}
				}
				elseif($urlField == 'url'){
					$size = filesize($activePath);
					if($size) $dataReturnArr['fileSizeMedium'] = round($size / 1024);
				}
				elseif($urlField == 'thumbnailUrl'){
					$size = filesize($activePath);
					if($size) $dataReturnArr['fileSizeThumbnail'] = round($size / 1024);
				}
			}
		}
		return $dataReturnArr;
	}

	//Misc and shared support functions
	private function verifyInputVariables(){
		if(!$this->urlMatchTerm){
			$this->outputStr('FATAL ERROR: source path has not been provided');
			exit;
		}
		if(!$this->sourcePathPrefix){
			$this->outputStr('FATAL ERROR: source path has not been provided');
			exit;
		}
		if($this->transferLarge || $this->transferWeb || $this->transferThumbnail){
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
		}
	}

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

	public function setDatabaseConnection($userName = null, $pwd = null, $database = null, $host = 'localhost', $port = '3306'){
		if($userName){
			try{
				$this->conn = new mysqli($host, $userName, $pwd, $database, $port);
				if(!$this->conn){
					$this->outputStr('FATAL ERROR: unable to establish database connection using input variables');
					exit;
				}
				if(!$this->conn->set_charset('utf8')){
					$this->outputStr('Error loading character set utf8: ' . $this->conn->error);
				}
			}
			catch(Exception $e){
				$this->outputStr('FATAL ERROR setting up database connection: ' . $e->getMessage());
				exit;
			}
		}
		elseif(class_exists('MySQLiConnectionFactory')){
			$this->conn = MySQLiConnectionFactory::getCon('write');
			if(!$this->conn){
				$this->outputStr('FATAL ERROR: unable to establish database connection via MySQLiConnectionFactory');
				exit;
			}
		}
		else{
			if(!$this->conn){
				$this->outputStr('FATAL ERROR: unable to establish database connection due to missing connection variables');
				exit;
			}
		}
	}

	//Setters and getters
	private function getFieldArr(){
		$fieldArr = array('originalUrl' => 's', 'url' => 's', 'thumbnailUrl' => 's', 'mediaMD5' => 's', 'pixelXDimension' => 'i', 'pixelYDimension' => 'i', 'fileSize' => 'i', 'fileSizeThumbnail' => 'i', 'fileSizeMedium' => 'i');
		return $fieldArr;
	}

	private function setLogFH(){
		$logPath = 'logs/mediaProcessing_' . time() . '.log';
		$this->logFH = fopen($logPath, 'x');
	}

	public function setConditionStr($inputArr){
		if(is_array($inputArr[0])){
			foreach($inputArr as $condArr){
				$this->appendQueryTerm($condArr[0], $condArr[1], $condArr[2]);
			}
		}
		else{
			$this->appendQueryTerm($inputArr[0], $inputArr[1], $inputArr[2]);
		}
	}

	public function appendQueryTerm($fieldName, $condition, $value){
		$this->conditionArr[$fieldName][$condition] = $value;
	}

	private function getConditionStr(){
		$retStr = '';
		foreach($this->conditionArr as $fieldName => $condArr){
			foreach($condArr as $condition => $value){
				$retStr .= $fieldName . ' ' .$condition . ($value ? ' ' . $value : '') . ', ';
			}
		}
		return trim($retStr , ' ,');
	}

	public function setCollid($id){
		if(is_numeric($id)) $this->collid = $id;
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
		$this->sourcePathPrefix = $path;
	}

	public function setTargetPathPrefix($path){
		$this->targetPathPrefix = $path;
	}

	public function setTargetUrlPrefix($url){
		$this->targetUrlPrefix = $url;
	}

	public function setVerboseMode($mode){
		if(is_numeric($mode)) $this->verboseMode = $mode;
	}
}
?>
