<?php
/*
 * Script assists in migrating images from a remote server to the portal mount
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../config/symbini.php');

$collid = (array_key_exists('collid', $_POST) ? filter_var($_POST['collid'], FILTER_SANITIZE_NUMBER_INT) : '');
$imgIdStart = (array_key_exists('imgidstart', $_POST) ? filter_var($_POST['imgidstart'], FILTER_SANITIZE_NUMBER_INT) : 0);
$limit = (array_key_exists('limit', $_POST) ? filter_var($_POST['limit'], FILTER_SANITIZE_NUMBER_INT) : 10000);

$transferThumbnail = empty($_POST['transferThumbnail']) ? 0 : 1;
$transferWeb = empty($_POST['transferWeb']) ? 0 : 1;
$transferLarge = empty($_POST['transferLarge']) ? 0 : 1;
$matchTermThumbnail = (array_key_exists('matchTermThumbnail', $_POST) ? $_POST['matchTermThumbnail'] : '');
$matchTermWeb = (array_key_exists('matchTermWeb', $_POST) ? $_POST['matchTermWeb'] : '');
$matchTermLarge = (array_key_exists('matchTermLarge', $_POST) ? $_POST['matchTermLarge'] : '');
$deleteSource = empty($_POST['deleteSource']) ? 0 : 1;
$imgRootUrl = (array_key_exists('imgRootUrl', $_POST) ? $_POST['imgRootUrl'] : '');
$imgRootPath = (array_key_exists('imgRootPath', $_POST) ? $_POST['imgRootPath'] : '');
$imgSubPath = (array_key_exists('imgSubPath', $_POST) ? $_POST['imgSubPath'] : '');
$copyover = empty($_POST['copyover']) ? 0 : 1;
$submit = (array_key_exists('submitbutton', $_POST)?$_POST['submitbutton']:'');

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
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?= $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?= $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		function verifyMigrationCode(f){
			if(f.matchTermThumbnail.value == "" && f.matchTermWeb.value == "" && f.matchTermLarge.value == ""){
				alert("You need at least one matching term defined");
				return false;
			}
			if(f.collid.value == ""){
				alert("Select a collection project");
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
				$imgidEnd = 0;
				if($submit == 'transferImages'){
					?>
					<fieldset>
						<legend>Action Panel</legend>
						<ol>
							<?php
							$migrationManager->setTransferThumbnail($transferThumbnail);
							$migrationManager->setTransferWeb($transferWeb);
							$migrationManager->setTransferLarge($transferLarge);
							$migrationManager->setMatchTermThumbnail($matchTermThumbnail);
							$migrationManager->setMatchTermWeb($matchTermWeb);
							$migrationManager->setMatchTermLarge($matchTermLarge);
							$migrationManager->setDeleteSource($deleteSource);
							$migrationManager->setImgRootUrl($imgRootUrl);
							$migrationManager->setImgRootPath($imgRootPath);
							$migrationManager->setImgSubPath($imgSubPath);
							$migrationManager->setCopyOverExistingImages($copyover);
							if($collid){
								$imgIdStart = $migrationManager->migrateCollectionDerivatives($imgIdStart, $limit);
							}
							else{
								$imgIdStart = $migrationManager->migrateFieldDerivatives($imgIdStart, $limit);
							}
							?>
						</ol>
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
								<option value="">Select a Collection</option>
								<option value="">-----------------------------</option>
								<option value="0">Field Images</option>
								<?php
								$collArr = $toolManager->getCollectionMeta();
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
							<legend>Transfer Source Query Term</legend>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<span class="fieldLabel">Thumbnail Matching Term (thumbnailUrl):</span>
									<input name="matchTermThumbnail" type="text" value="<?= htmlspecialchars($matchTermThumbnail); ?>" style="width:300px" />
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<span class="fieldLabel">Web Image (medium) Matching Term (url):</span>
									<input name="matchTermWeb" type="text" value="<?= htmlspecialchars($matchTermWeb); ?>" style="width:300px" />
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<span class="fieldLabel">Large Image Matching Term (originalurl):</span>
									<input name="matchTermLarge" type="text" value="<?= htmlspecialchars($matchTermLarge); ?>" style="width:300px" />
								</div>
							</div>
						</fieldset>
					</div>
					<div class="fieldRowDiv">
						<fieldset>
							<legend>Path Variables</legend>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<span class="fieldLabel">Image Root URL (imgRootUrl):</span>
									<input name="imgRootUrl" type="text" value="<?= ($imgRootUrl ? htmlspecialchars($imgRootUrl) : $MEDIA_ROOT_URL); ?>" style="width:400px" />
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<span class="fieldLabel">Image Root Path (imgRootPath):</span>
									<input name="imgRootPath" type="text" value="<?= ($imgRootPath ? htmlspecialchars($imgRootPath) : $MEDIA_ROOT_PATH); ?>" style="width:400px" />
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<span class="fieldLabel">Target Sub-Path:</span>
									<input name="imgSubPath" type="text" value="<?= htmlspecialchars($imgSubPath) ?>" style="width:400px" />
								</div>
							</div>
						</fieldset>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<input type="checkbox" name="copyover" value="1" <?= ($copyover ? 'checked' : '') ?>>
							<span class="fieldLabel">copyover existing target images</span>
						</div>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<span class="fieldLabel">imgId start:</span>
							<input type="text" name="imgidstart" value="<?= $imgIdStart; ?>" />
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
				</form>
			</fieldset>
		</div>
		<?php
	}
	else echo '<div>Permissions issue; are you logged in?</div>';
	?>
</body>

<?php
include_once('../config/dbconnection.php');

class MediaMigration {

	private $conn;
	private $collid;
	private $collMetaArr;

	private $transferThumbnail = false;
	private $transferWeb = false;
	private $transferLarge = false;
	private $matchTermThumbnail;
	private $matchTermWeb;
	private $matchTermLarge;
	private $deleteSource = false;
	private $imgRootUrl;
	private $imgRootPath;
	private $imgSubPath;
	private $sourcePathPrefix;
	private $copyOverExistingImages = false;

	private $logFH;
	private $verboseMode = 0;

	function __construct() {
	}

	function __destruct(){
		if(!($this->conn === null) && !$this->isConnInherited) $this->conn->close();
		if($this->logFH){
			fwrite($this->logFH,"\n\n");
			fclose($this->logFH);
		}
	}

	private function setConn() {
		$this->conn = MySQLiConnectionFactory::getCon($conType);
	}

	//NEON migration
	public function migrateNeonMedia(){
		$sourceUrlPrefix = 'https://media01.symbiota.org/media/neon';
		$replacementUrl = '/media/neon';
		$sourcePathPrefix = '/mnt/biokic/biokic/media/neon';
		$destinationPathPrefix = '/mnt/biokic/media/neon';
		$sql = 'SELECT mediaID, originalUrl, url, thumbnailUrl, mediaMD5, pixelXDimension, pixelYDimension, fileSize, fileSizeThumbnail, fileSizeMedium
			FROM media
			WHERE originalUrl LIKE "' . $sourceUrlPrefix . '%" and occid IS NOT NULL LIMIT 1';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$updateArr = array();
			$urlFieldArr = array('originalUrl', 'url', 'thumbnailUrl');
			foreach($urlFieldArr as $urlField){
				$pathFrag = substr($urlField, strlen($sourceUrlPrefix));
				$sourcePath = $sourcePathPrefix . $pathFrag;
				$destinationPath = $destinationPathPrefix . $pathFrag;
				if(file_exists($sourcePath) && !file_exists($destinationPath)){
					if(rename($sourcePath, $destinationPath)){
						if(strpos($urlField, $sourceUrlPrefix) === 0){
							$updateArr[$urlField] = $replacementUrl . substr($urlField, strlen($sourceUrlPrefix));
						}
					}
				}
			}
			if($updateArr){
				$filePath = $r->originalUrl;
				if(!$r->mediaMD5){
					$filePath = $r->originalUrl;
					if(isset($updateArr['originalUrl'])) $filePath = $updateArr['originalUrl'];
					$updateArr['mediaMD5'] = md5_file($filePath);
				}
				if(!$r->pixelXDimension){
					$dim = getimagesize($filePath);
					if ($dim !== false) {
						$updateArr['pixelXDimension'] = $dim[0];
						$updateArr['pixelYDimension'] = $dim[1];
					}
				}
				if(!$r->fileSize){
					$kilobytes = filesize($filename) / 1024;
					$updateArr['fileSize'] = $kilobytes;
					//thumbnail image
					$tnPath = $r->thumbnailUrl;
					if(isset($updateArr['thumbnailUrl'])) $tnPath = $updateArr['thumbnailUrl'];
					$tnKilobytes = filesize($tnPath) / 1024;
					$updateArr['fileSizeThumbnail'] = $tnKilobytes;
					//medium image
					$medPath = $r->url;
					if(isset($updateArr['url'])) $medPath = $updateArr['url'];
					$medKilobytes = filesize($medPath) / 1024;
					$updateArr['fileSizeMedium'] = $medKilobytes;
				}
				$this->databaseMediaRecord($r->mediaID, $updateArr);
			}
		}
		$rs->free();

		/*
		 * ALTER TABLE `symbneon`.`media`
		 * ADD COLUMN `fileSize` INT NULL AFTER `pixelXDimension`,
		 * ADD COLUMN `fileSizeThumbnail` INT NULL AFTER `fileSize`,
		 * ADD COLUMN `fileSizeMedium` INT NULL AFTER `fileSizeThumbnail`;
		 */
	}

	//General migration scritps
	public function migrateCollectionDerivatives($imgIdStart, $limit){
		//Migrates images based on catalog number; NULL or weak catalogNumbers are skipped
		set_time_limit(1200);
		$this->verboseMode = 3;
		$this->setConn();
		$this->setLogFH();
		if(!$this->imgRootUrl){
			$this->outputStr('FATAL ERROR: imgRootUrl is not defined');
			return false;
		}
		if(!$this->imgRootPath){
			$this->outputStr('FATAL ERROR: imgRootPath is not defined');
			return false;
		}

		if($this->collid && is_numeric($limit) && $this->imgRootUrl && $this->imgRootPath){
			if($this->transferThumbnail || $this->transferWeb || $this->transferLarge){
				if($this->matchTermThumbnail || $this->matchTermWeb || $this->matchTermLarge){
					echo '<ul>';
					$this->setTargetPaths();
					$processingCnt = 0;
					$sqlBase = 'FROM media m INNER JOIN omoccurrences o ON m.occid = o.occid WHERE o.collid = ' . $this->collid . ' AND m.mediaType = "image" ';
					if($this->matchTermThumbnail) $sqlBase .= 'AND thumbnailurl LIKE "'.$this->matchTermThumbnail.'%" ';
					if($this->matchTermWeb) $sqlBase .= 'AND url LIKE "'.$this->matchTermWeb.'%" ';
					if($this->matchTermLarge) $sqlBase .= 'AND originalurl LIKE "'.$this->matchTermLarge.'%" ';
					$targetCount = 0;
					$sqlCount = 'SELECT COUNT(m.mediaID) as cnt '.$sqlBase.' ';
					if($imgIdStart && is_numeric($imgIdStart)) $sqlCount .= 'AND mediaID > '.$imgIdStart.' ';
					$rsCount = $this->conn->query($sqlCount);
					while($rCount = $rsCount->fetch_object()){
						$targetCount = $rCount->cnt;
					}
					$rsCount->free();
					$this->outputStr('Starting remapping of '.$limit.' out of '.$targetCount.' possible target media ');
					do{
						$imgArr = array();
						$sql = 'SELECT m.mediaID, m.thumbnailurl, m.url, m.originalurl, o.catalognumber, o.occid '.$sqlBase;
						if($imgIdStart && is_numeric($imgIdStart)) $sql .= 'AND mediaID > '.$imgIdStart.' ';
						$sql .= 'ORDER BY mediaID LIMIT 100';
						//$this->outputStr('sql used: '. $sql);
						$rs = $this->conn->query($sql);
						while($r = $rs->fetch_object()){
							$imgIdStart = $r->mediaID;
							$pathFrag = '';
							if(preg_match('/^(\D*).*(\d{4,})/', $r->catalognumber, $m)){
								$catNum = $m[2];
								if($catNum){
									if(strlen($catNum)<8) $catNum = str_pad($catNum,8,'0',STR_PAD_LEFT);
									$pathFrag = $m[1].substr($catNum,0,strlen($catNum)-4).'/';
								}
							}
							if(!$pathFrag) $pathFrag = date('Ymd').'/';
							if(!file_exists($this->imgRootPath.$pathFrag)) mkdir($this->imgRootPath.$pathFrag);
							$this->outputStr($processingCnt.': Processing: <a href="../../individual/index.php?occid=' . htmlspecialchars($r->occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($r->occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>');
							if($this->transferThumbnail && $r->thumbnailurl){
								$fileName = basename($r->thumbnailurl);
								$targetPath = $this->imgRootPath.$pathFrag.$fileName;
								$targetUrl = $this->imgRootUrl.$pathFrag.$fileName;
								$thumbPath = $this->getLocalPath($r->thumbnailurl);
								if(file_exists($thumbPath)){
									if($this->copyOverExistingImages || !file_exists($targetPath)){
										if(copy($thumbPath, $targetPath)){
											$imgArr[$r->mediaID]['tn'] = $targetUrl;
											$this->outputStr('Copied: '.$thumbPath.' => '.$targetPath,1);
											if($this->deleteSource){
												if(unlink($thumbPath)){
													$this->outputStr('Source deleted: '.$thumbPath,1);
												}
												else{
													$this->outputStr('ERROR deleting source (file permissions?): '.$thumbPath,1);
												}
											}
										}
									}
									else{
										$this->outputStr('Skipped: target file already exists (' . $targetPath . ')', 1);
									}
								}
								else{
									$this->outputStr('Skipped: source thumbnail does not exist (' . $thumbPath . ')', 1);
								}
							}
							if($this->transferWeb && $r->url){
								$fileName = basename($r->url);
								$targetPath = $this->imgRootPath.$pathFrag.$fileName;
								$targetUrl = $this->imgRootUrl.$pathFrag.$fileName;
								$urlPath = $this->getLocalPath($r->url);
								if(file_exists($urlPath)){
									if($this->copyOverExistingImages || !file_exists($targetPath)){
										if(copy($urlPath, $targetPath)){
											$imgArr[$r->mediaID]['web'] = $targetUrl;
											$this->outputStr('Copied: '.$urlPath.' => '.$targetPath,1);
											if($this->deleteSource){
												if(unlink($urlPath)){
													$this->outputStr('Source delete: '.$urlPath,1);
												}
												else{
													$this->outputStr('ERROR deleting source (file permissions?): '.$urlPath,1);
												}
											}
										}
									}
									else{
										$this->outputStr('Skipped: target file already exists (' . $targetPath . ')', 1);
									}
								}
								else{
									$this->outputStr('Skipped: source file does not exist (' . $urlPath . ')', 1);
								}
							}
							if($this->transferLarge && $r->originalurl){
								$fileName = basename($r->originalurl);
								$targetPath = $this->imgRootPath.$pathFrag.$fileName;
								$targetUrl = $this->imgRootUrl.$pathFrag.$fileName;
								$origPath = $this->getLocalPath($r->originalurl);
								if(file_exists($origPath)){
									if($this->copyOverExistingImages || !file_exists($targetPath)){
										if(copy($origPath, $targetPath)){
											$imgArr[$r->mediaID]['lg'] = $targetUrl;
											$this->outputStr('Copied: '.$origPath.' => '.$targetPath,1);
											if($this->deleteSource){
												if(unlink($origPath)){
													$this->outputStr('Source deleted: '.$origPath,1);
												}
												else{
													$this->outputStr('ERROR deleting source (file permissions?): '.$origPath,1);
												}
											}
										}
									}
									else{
										$this->outputStr('Skipped: target file already exists (' . $targetPath . ')', 1);
									}
								}
								else{
									$this->outputStr('Skipped: source file does not exist (' . $origPath . ')', 1);
								}
							}
							$processingCnt++;
							$limit--;
							if($limit < 1) break;
						}
						$rs->free();
						$this->databaseImageArr($imgArr);
						$cnt = count($imgArr);
						$this->outputStr($processingCnt.' image records remapped ('.date('Y-m-d H:i:s').')');
						unset($imgArr);
					}while($cnt && $limit);
					echo '</ul>';
				}
			}
		}
		return $imgIdStart;
	}

	//Support functions
	private function getLocalPath($imageUrl){
		if($this->sourcePathPrefix){
			$adjustedUrl = str_replace($this->sourcePathPrefix, $GLOBALS['MEDIA_ROOT_PATH'], $imageUrl);
			if(file_exists($adjustedUrl)) return $adjustedUrl;
		}
		if(file_exists($imageUrl)){
			return $imageUrl;
		}
		if(strpos($imageUrl, $GLOBALS['MEDIA_ROOT_URL']) !== false){
			$adjustedUrl = str_replace($GLOBALS['MEDIA_ROOT_URL'], $GLOBALS['MEDIA_ROOT_PATH'], $imageUrl);
			if(file_exists($adjustedUrl)) return $adjustedUrl;
		}
		$prefix = substr($GLOBALS['MEDIA_ROOT_PATH'], 0, strlen($GLOBALS['MEDIA_ROOT_PATH']) - strlen($GLOBALS['MEDIA_ROOT_URL']));
		if(file_exists($prefix.$imageUrl)){
			$this->sourcePathPrefix = $prefix;
			return $prefix.$imageUrl;
		}
		return $imageUrl;
	}

	private function databaseImageArr($inputArr){
		$this->outputStr('Remapping ' . count($inputArr) . ' media records');
		$fieldArr = array('thumbnailurl' => 's', 'url' => 's', 'originalurl' => 's');
		foreach($inputArr as $mediaID => $mediaArr){
			$this->databaseMediaRecord($mediaID, $mediaArr);
		}
		$this->outputStr('Done!', 1);
	}

	private function databaseMediaRecord($mediaID, $inputArr){
		$fieldArr = array('thumbnailurl' => 's', 'url' => 's', 'originalurl' => 's');
		$paramArr = array();
		$typeStr = '';
		$sqlFrag = '';
		foreach($inputArr as $field => $value){
			$field = strtolower($field);
			if(isset($fieldArr[$field])){
				$sqlFrag .= $field . ' = ? ';
				$paramArr[] = $value;
				$typeStr .= $fieldArr[$field];
			}
		}
		if($sqlFrag){
			$sql = 'UPDATE media SET ' . trim($sqlFrag, ' ,') . ' WHERE mediaID = ' . $mediaID;
			//$this->outputStr($sql);
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param($typeStr, ...$paramArr);
				$stmt->execute();
				if($stmt->error){
					$this->outputStr('ERROR saving new paths (mediaID = ' . $mediaID . '): ' . $stmt->error, 1);
				}
				elseif(!$stmt->affected_rows){
					$this->outputStr('Nothing changed (mediaID = ' . $mediaID . ')', 1);
				}
				$stmt->close();
			}
		}
	}

	private function setTargetPaths(){
		if($this->imgRootPath && $this->imgRootUrl){
			if($this->collid){
				$this->imgRootPath .= $this->collMetaArr['code'].'/';
				$this->imgRootUrl .= $this->collMetaArr['code'].'/';
			}
			elseif($this->collid === 0){
				$this->imgRootPath .= 'fieldimg/';
				$this->imgRootUrl .= 'fieldimg/';
			}
			if(!file_exists($this->imgRootPath)) mkdir($this->imgRootPath);
		}
	}

	private function setLogFH(){
		$logPath = 'logs/mediaMigration_' . date('Y-m-d') . '.log';
		$this->logFH = fopen($logPath, 'a');
	}

	private function outputStr($str, $indexLevel = 0, $tag = 'li'){
		//verboseMode: 0 = silent, 1 = log, 2 = out to screen, 3 = both
		if($str && $this->verboseMode){
			if($this->verboseMode == 3 || $this->verboseMode == 1){
				if($this->logFH){
					fwrite($this->logFH, str_repeat("\t", $indexLevel) . strip_tags($str) . "\n");
				}
			}
			if($this->verboseMode == 3 || $this->verboseMode == 2){
				echo '<' . $tag . ' style="' . ($indexLevel ? 'margin-left:' . ($indexLevel * 15) . 'px' : '') . '">' . $str . '</' . $tag . '>';
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

	public function setMatchTermThumbnail($str){
		$this->matchTermThumbnail = $str;
	}

	public function setMatchTermWeb($str){
		$this->matchTermWeb = $str;
	}

	public function setMatchTermLarge($str){
		$this->matchTermLarge = $str;
	}

	public function setDeleteSource($bool){
		$this->deleteSource = $bool;
	}

	public function setImgRootUrl($url){
		if(substr($url, -1) != '/') $url .= '/';
		$this->imgRootUrl = $url;
	}

	public function setImgRootPath($url){
		if(substr($url, -1) != '/') $url .= '/';
		$this->imgRootPath = $url;
	}

	public function setImgSubPath($path){
		$this->imgSubPath = $path;
	}

	public function setCopyOverExistingImages($bool){
		if($bool) $this->copyOverExistingImages = true;
		else $this->copyOverExistingImages = false;
	}
}
?>
