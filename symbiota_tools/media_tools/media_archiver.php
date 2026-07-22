<?php
/*
 * Navigates through submitted image ids (imgid) and removes image records from database and deletes or moves physical image to an archive directory
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../config/symbini.php');

$collid = (array_key_exists('collid', $_POST) ? filter_var($_POST['collid'], FILTER_SANITIZE_NUMBER_INT) : '');
$imgIdStart = (array_key_exists('imgidstart', $_POST) ? filter_var($_POST['imgidstart'], FILTER_SANITIZE_NUMBER_INT) : 0);
$limit = (array_key_exists('limit', $_POST) ? filter_var($_POST['limit'], FILTER_SANITIZE_NUMBER_INT) : 10000);

$archiveImages = (array_key_exists('archiveimg', $_POST)?$_POST['archiveimg']:0);
$delThumb = (array_key_exists('delthumb', $_POST)?$_POST['delthumb']:0);
$delWeb = (array_key_exists('delweb', $_POST)?$_POST['delweb']:0);
$delLarge = (array_key_exists('dellarge', $_POST)?$_POST['dellarge']:0);
$imgidStr = (array_key_exists('imgidstr', $_POST)?$_POST['imgidstr']:'');

$toolManager = new MediaArchiver();
$toolManager->setCollid($collid);

$isEditor = false;
if($IS_ADMIN) $isEditor = true;
?>
<!DOCTYPE html>
<html lang="<?= $LANG_TAG ?>">
<head>
	<title>Media Tools</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= $CHARSET; ?>"/>
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
				if($submit){
					if($submit == 'Process Images'){
						if($archiveImages) $toolManager->setArchiveImages($archiveImages);
						$toolManager->setDeleteThumbnail($delThumb);
						$toolManager->setDeleteWebImage($delWeb);
						$toolManager->setDeleteOriginal($delLarge);
						$toolManager->setImgidArr($imgidStr);
						$imgidEnd = $toolManager->archiveImageFiles($imgIdStart, $limit);
					}
					else{
						$delThumb = 1;
						$delWeb = 1;
						$delLarge = 1;
					}
				}
				?>
			</div>
			<fieldset>
				<legend>Image Archival/Removal Tools</legend>
				<div>This tool can be used to stash (i.e. archive) or delete images that are currently stored locally (server must have write access to images)</div>
				<form action="media_scripts.php" method="post">
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
						<div class="fieldDiv">
							<b>Starting Image ID:</b> <input type="text" name="imgidstart" value="<?= $imgidEnd; ?>" /><br />
						</div>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<b>Batch limit: </b><input type="text" name="limit" value="<?= $limit; ?>" /><br />
						</div>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<fieldset>
								<legend>Action</legend>
								<input type="radio" name="archiveimg" value="0" <?= ($archiveImages?'':'CHECKED'); ?> /> Delete Images<br />
								<input type="radio" name="archiveimg" value="1" <?= ($archiveImages?'CHECKED':''); ?> /> Archive Images<br />
							</fieldset>
						</div>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<fieldset>
								<legend>Image Targets</legend>
								<input type="checkbox" name="delthumb" value="1" <?= ($delThumb?'CHECKED':''); ?> /> Delete Thumbnail Derivative<br />
								<input type="checkbox" name="delweb" value="1" <?= ($delWeb?'CHECKED':''); ?> /> Delete Web Derivative<br />
								<input type="checkbox" name="dellarge" value="1" <?= ($delLarge?'CHECKED':''); ?> /> Delete Large Derivative<br />
							</fieldset>
						</div>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<b>imgids (enter multiple values delimited by commas)</b><br/>
							<textarea name="imgidstr" rows="8" cols="100"></textarea>
						</div>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<button name="submitbutton" type="submit" value="Process Images">Process Images</button>
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
include_once('../config/dbconnection.php');

class MediaArchiver {

	private $conn;
	private $collid;
	private $collMetaArr;

	private $imgidArr;
	private $archiveImages = false;
	private $archiveDir;
	private $deleteThumbnail = false;
	private $deleteWeb = false;
	private $deleteOriginal = false;

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
		include_once('dbconnection.php');
		if(!empty($SERVER_ARR['password'])){
			try{
				$this->conn = new mysqli($SERVER_ARR['host'], $SERVER_ARR['username'], $SERVER_ARR['password'], $SERVER_ARR['database'], $SERVER_ARR['port']);
				if(isset($SERVER_ARR['charset']) && $SERVER_ARR['charset']) {
					if(!$this->conn->set_charset($SERVER_ARR['charset'])){
						throw new Exception('Error loading character set ' . $SERVER_ARR['charset'] . ': ' . $this->conn->error);
					}
				}
			}
			catch(Exception $e){
				echo $e->getMessage();
			}
		}
		echo 'ERROR: set your DB connection variables';
	}

	public function archiveImageFiles($imgidStart, $limit){
		set_time_limit(1200);
		$this->verboseMode = 3;
		$logPath = $GLOBALS['SERVER_ROOT'] . '/content/logs/imageprocessing/';
		if(!file_exists($logPath)) mkdir($logPath);
		$logPath .= 'imgArchive_' . date('Ym') . '.log';
		$this->setLogFH($logPath);
		if(!$imgidStart) $imgidStart = 0;
		if(!$this->imgidArr){
			$this->logOrEcho('ABORTED: Image ids (imgid) not supplied');
			return false;
		}
		$this->archiveDir = $GLOBALS['MEDIA_ROOT_PATH'].'/archive_'.date('Y-m-d');
		if(!file_exists($this->archiveDir)){
			if(!mkdir($this->archiveDir)) {
				$this->logOrEcho('ABORTED: unalbe to create archive directory ('.$this->archiveDir.')');
				return false;
			}
		}
		$createHeader = true;
		if(file_exists($this->archiveDir.'/mediaArchiveReport.csv')) $createHeader = false;
		$csvReportFH = fopen($this->archiveDir.'/mediaArchiveReport.csv', 'a');
		if(!$csvReportFH){
			$this->logOrEcho('ABORTED: unalbe to create archive file ('.$this->archiveDir.')');
			return false;
		}
		if($createHeader) fputcsv($csvReportFH, array('imgid','insertSQL'));
		//Remove images
		$imgidFinal = $imgidStart;
		$cnt = 0;
		$sql = 'SELECT m.* FROM media m ';
		if($this->collid) $sql .= 'INNER JOIN omoccurrences o ON m.occid = o.occid ';
		$sql .= 'WHERE (m.mediaID IN('.trim(implode(',',$this->imgidArr),', ').')) AND m.mediaType = "image" AND (m.mediaID > '.$imgidStart.') ';
		if($this->collid) $sql .= 'AND (o.collid = '.$this->collid.') ';
		$sql .= 'ORDER BY m.mediaID LIMIT '.$limit;
		//echo $sql;
		$rs = $this->conn->query($sql);
		echo '<ul>';
		while($r = $rs->fetch_assoc()){
			$imgId = $r['mediaID'];
			$derivArr = array('tn'=>1,'web'=>1,'lg'=>1);
			$delArr = array();
			if(!$r['thumbnailurl']) unset($derivArr['tn']);
			if(!$r['url']) unset($derivArr['web']);
			if(!$r['originalurl']) unset($derivArr['lg']);
			//Transfer images to archive folder
			if($this->deleteThumbnail && isset($derivArr['tn'])){
				if($this->archiveImage($r['thumbnailurl'], $imgId)){
					$delArr['tn'] = 1;
					unset($derivArr['tn']);
				}
			}
			if($this->deleteWeb && isset($derivArr['web'])){
				if($this->archiveImage($r['url'], $imgId)){
					$delArr['web'] = 1;
					unset($derivArr['web']);
				}
			}
			if($this->deleteOriginal && isset($derivArr['lg'])){
				if($this->archiveImage($r['originalurl'], $imgId)){
					$delArr['lg'] = 1;
					unset($derivArr['lg']);
				}
			}
			//Place INSERT sql into file in case record needs to be reintalled
			$insertArr = $r;
			unset($insertArr['mediaID']);
			unset($insertArr['initialtimestamp']);
			$insertStr = '';
			foreach($insertArr as $v){
				if($v) $insertStr .= ', "'.$v.'"';
				else $insertStr .= ', NULL';
			}
			$insSql = 'INSERT INTO media ('.implode(',', array_keys($insertArr)).') VALUES('.substr($insertStr,1).');';
			fputcsv($csvReportFH,array($imgId,'record deleted',$insSql));
			//Adjust database record
			$sqlImg = '';
			if($derivArr){
				if(isset($delArr['tn'])) $sqlImg .= ', thumbnailurl = NULL';
				if(isset($delArr['web'])) $sqlImg .= ', url = "empty"';
				if(isset($delArr['lg'])) $sqlImg .= ', originalurl = NULL';
				if($sqlImg) $sqlImg = 'UPDATE media SET '.substr($sqlImg,1).' WHERE mediaID = '.$imgId;
			}
			else{
				$sqlImg = 'DELETE FROM media WHERE mediaID = '.$imgId;
			}
			if($sqlImg){
				if(!$this->conn->query($sqlImg)){
					$this->logOrEcho('ERROR: '.$this->conn->error,1);
					$this->logOrEcho('sqlImg: '.$sqlImg,2);
				}
			}
			if($cnt && $cnt%100 == 0){
				$this->logOrEcho($cnt.' media checked');
				ob_flush();
				flush();
			}
			$cnt++;
			$imgidFinal = $imgId;
		}
		echo '</ul>';
		$rs->free();
		fclose($csvReportFH);
		$this->logOrEcho('Done! '.$cnt.' media handled');
		return $imgidFinal;
	}

	private function archiveImage($imgFilePath, $imgid){
		$status = false;
		if($imgFilePath){
			if(substr($imgFilePath,0,4) == 'http') {
				$imgFilePath = substr($imgFilePath,strpos($imgFilePath,"/",9));
			}
			$path = str_replace($GLOBALS['MEDIA_ROOT_URL'], $GLOBALS['MEDIA_ROOT_PATH'], $imgFilePath);
			if(is_writable($path)){
				if($this->archiveImages){
					$fileName = substr($path, strrpos($path, '/'));
					if(rename($path,$this->archiveDir.'/'.$fileName)) $status = true;
				}
				else{
					if(unlink($path)) $status = true;
				}
			}
			else{
				$this->logOrEcho('ERROR: image unwritable (imgid: <a href="' . $GLOBALS['CLIENT_ROOT'] . '/imagelib/imgdetails.php?mediaid=' . $imgid . '" target="_blank">' . $imgid . '</a>, path: ' . htmlspecialchars($path, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ')');
			}
		}
		return $status;
	}

	//support and data return functions
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

	private function setLogFH($logPath){
		$this->logFH = fopen($logPath, 'a');
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

	public function setImgidArr($imgidStr){
		$imgidStr = str_replace(';', ' ', $imgidStr);
		$imgidStr = str_replace(',', ' ', $imgidStr);
		$imgidStr = trim(preg_replace('/\s\s+/',' ',$imgidStr),',');
		if($imgidStr){
			if(preg_match('/^[\d\s]+$/',$imgidStr)){
				$this->imgidArr = explode(' ',$imgidStr);
			}
		}
	}

	public function setArchiveImages($b){
		if($b) $this->archiveImages = true;
	}

	public function setDeleteThumbnail($delTn){
		if($delTn) $this->deleteThumbnail = true;
		else $this->deleteThumbnail = false;
	}

	public function setDeleteWebImage($delWeb){
		if($delWeb) $this->deleteWeb = true;
		else $this->deleteWeb = false;
	}

	public function setDeleteOriginal($delOrig){
		if($delOrig) $this->deleteOriginal = true;
		else $this->deleteOriginal = false;
	}
}
?>
