<?php
/*
 * Script assists in migrating images from a remote server to the portal mount
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../config/symbini.php');

$collid = (array_key_exists('collid', $_POST) ? filter_var($_POST['collid'], FILTER_SANITIZE_NUMBER_INT) : '');
$mediaIdStart = (array_key_exists('mediaIdStart', $_POST) ? filter_var($_POST['mediaIdStart'], FILTER_SANITIZE_NUMBER_INT) : 0);
$limit = (array_key_exists('limit', $_POST) ? filter_var($_POST['limit'], FILTER_SANITIZE_NUMBER_INT) : 10000);

$transferThumbnail = empty($_POST['transferThumbnail']) ? 0 : 1;
$transferWeb = empty($_POST['transferWeb']) ? 0 : 1;
$transferLarge = empty($_POST['transferLarge']) ? 0 : 1;
$urlMatchTerm = (array_key_exists('urlMatchTerm', $_POST) ? $_POST['urlMatchTerm'] : '');
$deleteSource = empty($_POST['deleteSource']) ? 0 : 1;
$sourcePathPrefix = (array_key_exists('sourcePathPrefix', $_POST) ? $_POST['sourcePathPrefix'] : '');
$targetPathPrefix = (array_key_exists('targetPathPrefix', $_POST) ? $_POST['targetPathPrefix'] : '');
$urlPrefix = (array_key_exists('urlPrefix', $_POST) ? $_POST['urlPrefix'] : '');
$submit = (array_key_exists('submitbutton', $_POST)?$_POST['submitbutton']:'');

//Set defaults to be used for testing
//if(!$sourcePathPrefix) $sourcePathPrefix = '/mnt/biokic/biokic/media/neon';
//if(!$targetPathPrefix) $targetPathPrefix = '/mnt/biokic/media/neon';
if(!$sourcePathPrefix) $sourcePathPrefix = '/temp/NEON/migration/source/media/neon';
if(!$targetPathPrefix) $targetPathPrefix = '/temp/NEON/migration/target/media/neon';
if(!$urlPrefix) $urlPrefix = '/media';

$migrationManager = new MediaMigration();
$migrationManager->setCollid($collid);

$isEditor = false;
if($IS_ADMIN) $isEditor = true;
?>
<!DOCTYPE html>
<html lang="<?= $LANG_TAG ?>">
<head>
	<title>Media Tools</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= $CHARSET; ?>"/>
	<script type="text/javascript">
		function verifyMigrationCode(f){
			if(f.urlMatchTerm.value == ""){
				alert("You need at least one matching term defined");
				return false;
			}
			if(f.collid.value == "unselected"){
				alert("Select a Collection Project or Field Images");
				return false;
			}
			return true;
		}
	</script>
	<style type="text/css">
		fieldset{ padding: 10px; margin-bottom: 15px }
		legend{ font-weight: bold }
		.fieldRowDiv{ clear:both; margin: 2px 0px; }
		.fieldDiv{ float:left; margin: 2px 10px 2px 0px; }
		.fieldLabel{  }
		.fieldDiv button{ margin-top: 10px; }
	</style>
</head>
<body>
	<?php
	if($isEditor){
		?>
		<div role="main" id="innertext">
			<h1 class="page-heading">Media Tools</h1>
			<div id="actionDiv">
				<?php
				if($submit){
					?>
					<fieldset>
						<legend>Action Panel</legend>
						<ul>
							<?php
							if($submit == 'transferImages'){
								$migrationManager->setTransferThumbnail($transferThumbnail);
								$migrationManager->setTransferWeb($transferWeb);
								$migrationManager->setTransferLarge($transferLarge);
								$migrationManager->setUrlMatchTerm($urlMatchTerm);
								$migrationManager->setDeleteSource($deleteSource);
								$migrationManager->setSourcePathPrefix($sourcePathPrefix);
								$migrationManager->setTargetPathPrefix($targetPathPrefix);
								$migrationManager->setUrlPrefix($urlPrefix);
								$mediaIdStart = $migrationManager->migrateNeonMedia($mediaIdStart, $limit);
							}
							?>
						</ul>
					</fieldset>
					<?php
				}
				?>
			</div>
			<fieldset>
				<legend>Image Migration Tools</legend>
				<div>This tool can be used to migrate images located on a remote server to the local server that is currently hosting the portal</div>
				<form action="media_scripts.php" method="post" onsubmit="return verifyMigrationCode(this)">
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<span class="fieldLabel">Collection ID (collid):</span>
							<select name="collid">
								<option value="unselected">Select a Collection</option>
								<option value="unselected">-----------------------------</option>
								<option value="">Field Images</option>
								<option value="0">All Collection Images</option>
								<?php
								$collArr = $migrationManager->getCollectionMeta();
								foreach($collArr as $id => $collName){
									echo '<option value="'.$id.'" '.($collid==$id?'SELECTED':'').'>'.$collName.'</option>';
								}
								?>
							</select>
						</div>
					</div>
					<div class="fieldRowDiv">
						<fieldset>
							<legend>Transfer Target</legend>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<input name="transferThumbnail" type="checkbox" value="1" <?= ($transferThumbnail?'CHECKED':''); ?> />
									<span class="fieldLabel">Transfer Thumbnail</span>
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<input name="transferWeb" type="checkbox" value="1" <?= ($transferWeb ? 'CHECKED' : '') ?> />
									<span class="fieldLabel">Transfer Web View (medium)</span>
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<input name="transferLarge" type="checkbox" value="1" <?= ($transferLarge ? 'CHECKED' : '') ?> />
									<span class="fieldLabel">Transfer Large Image</span>
								</div>
							</div>
							<div class="fieldRowDiv" style="padding-top:10px">
								<div class="fieldDiv">
									<input name="deleteSource" type="checkbox" value="1" <?= ($deleteSource ? 'CHECKED' : '') ?> />
									<span class="fieldLabel">Delete source images</span>
								</div>
							</div>
						</fieldset>
					</div>
					<div class="fieldRowDiv">
						<fieldset>
							<legend>Path Variables</legend>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<span class="fieldLabel">URL Matching Term (e.g. query string):</span>
									<input name="urlMatchTerm" type="text" value="<?= htmlspecialchars($urlMatchTerm) ?>" style="width:300px" required >
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<span class="fieldLabel">Source Path:</span>
									<input name="sourcePathPrefix" type="text" value="<?= htmlspecialchars($sourcePathPrefix) ?>" style="width:400px" required >
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<span class="fieldLabel">Target Path (imgRootPath):</span>
									<input name="targetPathPrefix" type="text" value="<?= ($targetPathPrefix ? htmlspecialchars($targetPathPrefix) : $MEDIA_ROOT_PATH); ?>" style="width:400px" required >
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<span class="fieldLabel">URL Path Prefix (e.g. imgRootUrl):</span>
									<input name="urlPrefix" type="text" value="<?= ($urlPrefix ? htmlspecialchars($urlPrefix) : $MEDIA_ROOT_URL); ?>" style="width:400px" />
								</div>
							</div>
						</fieldset>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<span class="fieldLabel">imgId start:</span>
							<input type="text" name="mediaIdStart" value="<?= $mediaIdStart; ?>" />
						</div>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<span class="fieldLabel">Batch limit:</span>
							<input type="text" name="limit" value="<?= $limit; ?>" />
						</div>
					</div>
					<div class="fieldRowDiv">
						<button name="submitbutton" type="submit" value="transferImages">Transfer Images</button>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							* If target file already exists, it will be overwritten
						</div>
					</div>
				</form>
			</fieldset>
		</div>
		<?php
	}
	else echo '<div>Permissions issue; are you logged in?</div>';
	?>
</body>

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

	//NEON migration
	public function migrateNeonMedia($mediaIdStart = 0, $limit = 1000){
		set_time_limit(1200);
		$this->setVerboseMode(3);
		$this->outputStr('Starting media file transfer (' . date('Y-m-d H:i:s') . ')');
		if(!$this->urlMatchTerm){
			$this->outputStr('FATAL ERROR: URL matching term has not been set');
			exit;
		}
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
		$urlFieldArr = array();
		if($this->transferLarge) $urlFieldArr[] = 'originalUrl';
		if($this->transferWeb) $urlFieldArr[] = 'url';
		if($this->transferThumbnail) $urlFieldArr[] = 'thumbnailUrl';
		foreach($urlFieldArr as $targetQueryField){
			$this->outputStr('Querying media based on search term: ' . $targetQueryField);
			$sqlBase = 'FROM media m ';
			if(is_numeric($this->collid)){
				$sqlBase .= 'INNER JOIN omoccurrences o ON m.occid = o.occid ';
			}
			$sqlBase .= 'WHERE (m.' . $targetQueryField . ' LIKE ?) ';
			$paramArr = array($this->urlMatchTerm);
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
			if($cntStmt = $this->conn->query($cntSql)){
				$cntStmt->bind_param($typeStr, ...$paramArr);
				$cntStmt->execute();
				$cntStmt->bind_result($targetCnt);
				$cntStmt->fetch();
				$cntStmt->close();
			}
			$this->outputStr('Target count: ' . $targetCnt, 1);

			$cnt = 0;
			if($stmt = $this->conn->query($sql)){
				$stmt->bind_param($typeStr, ...$paramArr);
				$stmt->execute();
				$rs = $stmt->get_result();
				while($r = $rs->fetch_assoc()){
					$updateArr = array();
					foreach($urlFieldArr as $urlField){
						$transferFile = true;
						if(strpos($r[$urlField], $this->urlMatchTerm) === 0){
							$pathFrag = substr($r[$urlField], strlen($this->urlMatchTerm));
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

							$updateArr[$urlField] = $this->urlPrefix . $pathFrag;
							if($urlField == 'originalUrl'){
								if(!$r['mediaMD5']){
									$updateArr['mediaMD5'] = md5_file($targetPath);
								}
								if(!$r['pixelXDimension']){
									$dim = getimagesize($targetPath);
									if ($dim !== false) {
										$updateArr['pixelXDimension'] = $dim[0];
										$updateArr['pixelYDimension'] = $dim[1];
									}
								}
								if(!$r['fileSize']){
									$updateArr['fileSize'] = round(filesize($targetPath) / 1024);
								}
							}
							elseif($urlField == 'url'){
								$updateArr['fileSizeMedium'] = round(filesize($targetPath) / 1024);
							}
							elseif($urlField == 'thumbnailUrl'){
								$updateArr['fileSizeThumbnail'] = round(filesize($targetPath) / 1024);
							}
						}
					}
					if($updateArr){
						if($this->databaseMediaRecord($r['mediaID'], $updateArr)){
							$cnt++;
							$recordID = $r->occid;
							$link = $GLOBALS['SERVER_ROOT'] . '/collections/individual/index.php?occid=' . $r->occid;
							if(!$r->occid){
								$link = '/imagelib/imgdetails.php?mediaid=' . $r->mediaID;
								$recordID = $r->mediaID;
							}
							$this->outputStr($cnt.': Processing: <a href="' . $link . '" target="_blank">#' . $recordID . '</a>');
						}
					}
				}
				$rs->free();
				$stmt->close();
			}
		}
		$this->outputStr('Done transferring ' . $cnt . ' media files (' . date('Y-m-d H:i:s') . ')');
		/*
		 * ALTER TABLE `media`
		 *   ADD COLUMN `fileSize` INT NULL AFTER `pixelXDimension`,
		 *   ADD COLUMN `fileSizeThumbnail` INT NULL AFTER `fileSize`,
		 *   ADD COLUMN `fileSizeMedium` INT NULL AFTER `fileSizeThumbnail`;
		 */
	}

	//Support functions
	private function databaseMediaRecord($mediaID, $inputArr){
		$status = false;
		$fieldArr = array('originalurl' => 's', 'url' => 's', 'thumbnailurl' => 's', 'mediamd5' => 's', 'pixelxdimension' => 'i', 'pixelydimension' => 'i', 'filesize' => 'i', 'filesizethumbnail' => 'i', 'filesizemedium' => 'i');
		$inputFieldArr = array();
		$paramArr = array();
		$typeStr = '';
		foreach($inputArr as $field => $value){
			$field = strtolower($field);
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
		//verboseMode: 0 = silent, 1 = log, 2 = out to screen, 3 = both
		if($str && $this->verboseMode){
			if($this->verboseMode == 3 || $this->verboseMode == 1){
				if($this->logFH){
					fwrite($this->logFH, str_repeat("\t", $indexLevel) . strip_tags($str) . "\n");
				}
			}
			if($this->verboseMode == 3 || $this->verboseMode == 2){
				echo '<li style="' . ($indexLevel ? 'margin-left:' . ($indexLevel * 15) . 'px' : '') . '">' . $str . '</li>';
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
