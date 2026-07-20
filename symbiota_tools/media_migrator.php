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

$transferThumbnail = (array_key_exists('transferThumbnail', $_POST) ? $_POST['transferThumbnail'] : 0);
$transferWeb = (array_key_exists('transferWeb', $_POST)?$_POST['transferWeb']:0);
$transferLarge = (array_key_exists('transferLarge', $_POST)?$_POST['transferLarge']:0);
$matchTermThumbnail = (array_key_exists('matchTermThumbnail', $_POST)?$_POST['matchTermThumbnail']:'');
$matchTermWeb = (array_key_exists('matchTermWeb', $_POST)?$_POST['matchTermWeb']:'');
$matchTermLarge = (array_key_exists('matchTermLarge', $_POST)?$_POST['matchTermLarge']:'');
$deleteSource = (array_key_exists('deleteSource', $_POST)?$_POST['deleteSource']:0);
$imgRootUrl = (array_key_exists('imgRootUrl', $_POST)?$_POST['imgRootUrl']:'');
$imgRootPath = (array_key_exists('imgRootPath', $_POST)?$_POST['imgRootPath']:'');
$imgSubPath = (array_key_exists('imgSubPath', $_POST)?$_POST['imgSubPath']:'');
$copyover = (!empty($_POST['copyover']) ? 1 : 0);
$submit = (array_key_exists('submitbutton', $_POST)?$_POST['submitbutton']:'');

//Sanitation
if(!is_numeric($transferThumbnail)) $transferThumbnail = 0;
if(!is_numeric($transferWeb)) $transferWeb = 0;
if(!is_numeric($transferLarge)) $transferLarge = 0;
if(!is_numeric($deleteSource)) $deleteSource = 0;

$toolManager = new MediaResolutionTools();
$toolManager->setCollid($collid);

$isEditor = false;
if($IS_ADMIN) $isEditor = true;
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title>Media Tools</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
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
				if($submit){
					if($submit == 'transferImages'){
						?>
						<fieldset>
							<legend>Action Panel</legend>
							<ol>
							<?php
							$toolManager->setVerboseMode(2);
							$toolManager->setTransferThumbnail($transferThumbnail);
							$toolManager->setTransferWeb($transferWeb);
							$toolManager->setTransferLarge($transferLarge);
							$toolManager->setMatchTermThumbnail($matchTermThumbnail);
							$toolManager->setMatchTermWeb($matchTermWeb);
							$toolManager->setMatchTermLarge($matchTermLarge);
							$toolManager->setDeleteSource($deleteSource);
							$toolManager->setImgRootUrl($imgRootUrl);
							$toolManager->setImgRootPath($imgRootPath);
							$toolManager->setImgSubPath($imgSubPath);
							$toolManager->setCopyOverExistingImages($copyover);
							if($collid) $imgIdStart = $toolManager->migrateCollectionDerivatives($imgIdStart, $limit);
							else $imgIdStart = $toolManager->migrateFieldDerivatives($imgIdStart, $limit);
							?>
							</ol>
						</fieldset>
						<?php
					}
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
									<input name="transferThumbnail" type="checkbox" value="1" <?php echo ($transferThumbnail?'CHECKED':''); ?> />
									<span class="fieldLabel">Transfer Thumbnail</span>
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<input name="transferWeb" type="checkbox" value="1" <?php echo ($transferWeb?'CHECKED':''); ?> />
									<span class="fieldLabel">Transfer Web View (medium)</span>
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<input name="transferLarge" type="checkbox" value="1" <?php echo ($transferLarge?'CHECKED':''); ?> />
									<span class="fieldLabel">Transfer Large Image</span>
								</div>
							</div>
							<div class="fieldRowDiv" style="padding-top:10px">
								<div class="fieldDiv">
									<input name="deleteSource" type="checkbox" value="1" <?php echo ($deleteSource?'CHECKED':''); ?> />
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
									<input name="matchTermThumbnail" type="text" value="<?php echo htmlspecialchars($matchTermThumbnail); ?>" style="width:300px" />
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<span class="fieldLabel">Web Image (medium) Matching Term (url):</span>
									<input name="matchTermWeb" type="text" value="<?php echo htmlspecialchars($matchTermWeb); ?>" style="width:300px" />
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<span class="fieldLabel">Large Image Matching Term (originalurl):</span>
									<input name="matchTermLarge" type="text" value="<?php echo htmlspecialchars($matchTermLarge); ?>" style="width:300px" />
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
									<input name="imgRootUrl" type="text" value="<?php echo ($imgRootUrl ? htmlspecialchars($imgRootUrl) : $MEDIA_ROOT_URL); ?>" style="width:400px" />
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<span class="fieldLabel">Image Root Path (imgRootPath):</span>
									<input name="imgRootPath" type="text" value="<?php echo ($imgRootPath ? htmlspecialchars($imgRootPath) : $MEDIA_ROOT_PATH); ?>" style="width:400px" />
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
							<input type="text" name="imgidstart" value="<?php echo $imgIdStart; ?>" />
						</div>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<span class="fieldLabel">Batch limit:</span>
							<input type="text" name="limit" value="<?php echo $limit; ?>" />
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

	private $debugMode = false;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon('write');
	}

	function __destruct(){
		if(!($this->conn === null) && !$this->isConnInherited) $this->conn->close();
		if($this->logFH){
			fwrite($this->logFH,"\n\n");
			fclose($this->logFH);
		}
	}

	public function migrateFieldDerivatives($imgIdStart, $limit){
		set_time_limit(1200);
		$this->verboseMode = 3;
		$logPath = $GLOBALS['SERVER_ROOT'] . '/content/logs/imageprocessing/';
		if(!file_exists($logPath)) mkdir($logPath);
		$logPath .= 'fieldDerivativeMigration_' . date('Ym') . '.log';
		$this->setLogFH($logPath);
		//Needs to be reworked
		$this->debugMode = true;
		$imgId = 0;
		if(is_numeric($limit) && is_numeric($this->collid) && $this->imgRootUrl && $this->imgRootPath){
			if($this->transferThumbnail && $this->transferWeb && $this->transferLarge){
				if($this->matchTermThumbnail || $this->matchTermWeb || $this->matchTermLarge){
					echo '<ul>';
					$this->setTargetPaths();
					$dirCnt = 0;
					do{
						$imgArr = array();
						$pathFrag = date('Ym');
						if(!file_exists($this->imgRootPath.$pathFrag)) mkdir($this->imgRootPath.$pathFrag);
						$subDir = str_pad($dirCnt,4,'0',STR_PAD_LEFT);
						while(file_exists($this->imgRootPath.$pathFrag.'/'.$subDir)){
							$dirCnt ++;
							$subDir = str_pad($dirCnt,4,'0',STR_PAD_LEFT);
						}
						$pathFrag .= '/'.$subDir;
						$dirCnt ++;
						$sql = 'SELECT mediaID, thumbnailurl, url, originalurl FROM media WHERE occid IS NULL ';
						if($this->collid) $sql = 'SELECT m.thumbnailurl, m.url, m.originalurl FROM media m INNER JOIN omoccurrences o ON m.occid = o.occid WHERE o.collid = '.$this->collid;
						if($this->matchTermThumbnail) $sql .= ' AND thumbnailurl LIKE "'.$this->matchTermThumbnail.'%" ';
						if($this->matchTermWeb) $sql .= ' AND url LIKE "'.$this->matchTermWeb.'%" ';
						if($this->matchTermLarge) $sql .= ' AND originalurl LIKE "'.$this->matchTermLarge.'%" ';
						if($imgIdStart && is_numeric($imgIdStart)) $sql .= 'AND mediaID > '.$imgIdStart.' ';
						$sql .= 'ORDER BY mediaID ';
						$sql .= 'LIMIT 1000';
						echo $sql.'<br/>';
						$rs = $this->conn->query($sql);
						while($r = $rs->fetch_object()){
							$imgId = $r->mediaID;
							if($this->transferThumbnail){
								$filePath = $pathFrag;
								if(substr($r->thumbnailurl,-1) != '/') $filePath .= '/';
								echo $r->thumbnailurl.' => '.$this->imgRootPath.$filePath.'<br/>';
							}
							if($this->transferWeb){
								$filePath = $pathFrag;
								if(substr($r->url,-1) != '/') $filePath .= '/';
								echo $r->url.' => '.$this->imgRootPath.$filePath.'<br/>';
							}
							if($this->transferLarge){
								$filePath = $pathFrag;
								if(substr($r->originalurl,-1) != '/') $filePath .= '/';
								echo $r->originalurl.' => '.$this->imgRootPath.$filePath.'<br/>';
							}
							$limit--;
							if($limit < 1) break;
						}
						$rs->free();
						$this->databaseImageArr($imgArr);
						$cnt = count($imgArr);
						$this->logOrEcho($cnt.' image records remapped');
						unset($imgArr);
					}while($cnt && $limit);
					echo '</ul>';
				}
			}
		}
		return $imgId;
	}

	public function migrateCollectionDerivatives($imgIdStart, $limit){
		//Migrates images based on catalog number; NULL or weak catalogNumbers are skipped
		set_time_limit(1200);
		$this->verboseMode = 3;
		$logPath = $GLOBALS['SERVER_ROOT'] . '/content/logs/imageprocessing/';
		if(!file_exists($logPath)) mkdir($logPath);
		$logPath .= 'imgMigration_' . date('Ym') . '.log';
		$this->setLogFH($logPath);
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
					$this->logOrEcho('Starting remapping of '.$limit.' out of '.$targetCount.' possible target media ');
					do{
						$imgArr = array();
						$sql = 'SELECT m.mediaID, m.thumbnailurl, m.url, m.originalurl, o.catalognumber, o.occid '.$sqlBase;
						if($imgIdStart && is_numeric($imgIdStart)) $sql .= 'AND mediaID > '.$imgIdStart.' ';
						$sql .= 'ORDER BY mediaID LIMIT 100';
						//$this->logOrEcho('sql used: '. $sql);
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
							$this->logOrEcho($processingCnt.': Processing: <a href="../../individual/index.php?occid=' . htmlspecialchars($r->occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($r->occid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>');
							if($this->transferThumbnail && $r->thumbnailurl){
								$fileName = basename($r->thumbnailurl);
								$targetPath = $this->imgRootPath.$pathFrag.$fileName;
								$targetUrl = $this->imgRootUrl.$pathFrag.$fileName;
								$thumbPath = $this->getLocalPath($r->thumbnailurl);
								if(file_exists($thumbPath)){
									if($this->copyOverExistingImages || !file_exists($targetPath)){
										if(copy($thumbPath, $targetPath)){
											$imgArr[$r->mediaID]['tn'] = $targetUrl;
											$this->logOrEcho('Copied: '.$thumbPath.' => '.$targetPath,1);
											if($this->deleteSource){
												if(unlink($thumbPath)){
													$this->logOrEcho('Source deleted: '.$thumbPath,1);
												}
												else{
													$this->logOrEcho('ERROR deleting source (file permissions?): '.$thumbPath,1);
												}
											}
										}
									}
									else{
										$this->logOrEcho('Skipped: target file already exists (' . $targetPath . ')', 1);
									}
								}
								else{
									$this->logOrEcho('Skipped: source thumbnail does not exist (' . $thumbPath . ')', 1);
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
											$this->logOrEcho('Copied: '.$urlPath.' => '.$targetPath,1);
											if($this->deleteSource){
												if(unlink($urlPath)){
													$this->logOrEcho('Source delete: '.$urlPath,1);
												}
												else{
													$this->logOrEcho('ERROR deleting source (file permissions?): '.$urlPath,1);
												}
											}
										}
									}
									else{
										$this->logOrEcho('Skipped: target file already exists (' . $targetPath . ')', 1);
									}
								}
								else{
									$this->logOrEcho('Skipped: source file does not exist (' . $urlPath . ')', 1);
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
											$this->logOrEcho('Copied: '.$origPath.' => '.$targetPath,1);
											if($this->deleteSource){
												if(unlink($origPath)){
													$this->logOrEcho('Source deleted: '.$origPath,1);
												}
												else{
													$this->logOrEcho('ERROR deleting source (file permissions?): '.$origPath,1);
												}
											}
										}
									}
									else{
										$this->logOrEcho('Skipped: target file already exists (' . $targetPath . ')', 1);
									}
								}
								else{
									$this->logOrEcho('Skipped: source file does not exist (' . $origPath . ')', 1);
								}
							}
							$processingCnt++;
							$limit--;
							if($limit < 1) break;
						}
						$rs->free();
						$this->databaseImageArr($imgArr);
						$cnt = count($imgArr);
						$this->logOrEcho($processingCnt.' image records remapped ('.date('Y-m-d H:i:s').')');
						unset($imgArr);
					}while($cnt && $limit);
					echo '</ul>';
				}
			}
		}
		return $imgIdStart;
	}

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

	private function databaseImageArr($imgArr){
		foreach($imgArr as $imgID => $iArr){
			$sqlFrag = '';
			if(isset($iArr['tn'])) $sqlFrag .= 'thumbnailurl = "'.$iArr['tn'].'"';
			if(isset($iArr['web'])) $sqlFrag .= ',url = "'.$iArr['web'].'"';
			if(isset($iArr['lg'])) $sqlFrag .= ',originalurl = "'.$iArr['lg'].'"';
			if($sqlFrag){
				$sql = 'UPDATE media SET '.trim($sqlFrag,' ,').' WHERE mediaType = "image" AND mediaID = '.$imgID;
				if($this->debugMode) $this->logOrEcho($sql);
				if(!$this->conn->query($sql)) $this->logOrEcho('ERROR saving new paths: '.$this->conn->error,1);
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
