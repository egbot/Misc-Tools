<?php
/*
 * Script assists in migrating images from a remote server to the portal mount
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../config/symbini.php');
include_once('MediaMigration.php');

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
//$urlMatchTerm = 'https://media01.symbiota.org/media/neon';
//if(!$sourcePathPrefix) $sourcePathPrefix = '/mnt/biokic/biokic/media/neon';
//if(!$targetPathPrefix) $targetPathPrefix = '/mnt/biokic/media/neon';
//if(!$sourcePathPrefix) $sourcePathPrefix = '/temp/NEON/migration/source/media/neon';
//if(!$targetPathPrefix) $targetPathPrefix = '/temp/NEON/migration/target/media/neon';
//if(!$urlPrefix) $urlPrefix = '/media';

if(!$targetPathPrefix && !empty($MEDIA_ROOT_PATH)) $targetPathPrefix = $MEDIA_ROOT_PATH;
if(!$urlPrefix && !empty($MEDIA_ROOT_URL)) $urlPrefix = $MEDIA_ROOT_URL;

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
		.fieldRowDiv{ clear:both; margin: 10px; }
		.fieldDiv{ float:left; margin: 2px 10px 2px 0px; }
		.fieldDiv button{ margin-top: 10px; }
		button{ margin: 20px }
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
								$migrationManager->setDeleteSource($deleteSource);
								$migrationManager->setUrlMatchTerm($urlMatchTerm);
								$migrationManager->setSourcePathPrefix($sourcePathPrefix);
								$migrationManager->setTargetPathPrefix($targetPathPrefix);
								$migrationManager->setUrlPrefix($urlPrefix);
								//$mediaIdStart = $migrationManager->migrateMediaViaDatabase($mediaIdStart, $limit);
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
				<form action="media_migrator.php" method="post" onsubmit="return verifyMigrationCode(this)">
					<div class="fieldRowDiv">
						<fieldset>
							<legend>Path Variables</legend>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<label for="urlMatchTerm">URL Matching Term (e.g. query string):</label>
									<input id="urlMatchTerm" name="urlMatchTerm" type="text" value="<?= htmlspecialchars($urlMatchTerm) ?>" style="width:300px" required >
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<label for="sourcePathPrefix">Source Path:</label>
									<input id="sourcePathPrefix" name="sourcePathPrefix" type="text" value="<?= htmlspecialchars($sourcePathPrefix) ?>" style="width:400px" required >
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<label for="targetPathPrefix">Target Path (imgRootPath):</label>
									<input id="targetPathPrefix" name="targetPathPrefix" type="text" value="<?= htmlspecialchars($targetPathPrefix) ?>" style="width:400px" required >
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<label for="urlPrefix">URL Path Prefix (e.g. imgRootUrl):</label>
									<input id="urlPrefix" name="urlPrefix" type="text" value="<?= htmlspecialchars($urlPrefix) ?>" style="width:400px" />
								</div>
							</div>
						</fieldset>
					</div>
					<div class="fieldRowDiv">
						<fieldset>
							<legend>Transfer Options</legend>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<input id="transferThumbnail" name="transferThumbnail" type="checkbox" value="1" <?= ($transferThumbnail?'CHECKED':''); ?> />
									<label for="transferThumbnail">Transfer Thumbnail</label>
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<input id="transferWeb" name="transferWeb" type="checkbox" value="1" <?= ($transferWeb ? 'CHECKED' : '') ?> />
									<label for="transferWeb">Transfer Web View (medium)</label>
								</div>
							</div>
							<div class="fieldRowDiv">
								<div class="fieldDiv">
									<input id="transferLarge" name="transferLarge" type="checkbox" value="1" <?= ($transferLarge ? 'CHECKED' : '') ?> />
									<label for="transferLarge">Transfer Large Image</label>
								</div>
							</div>
							<div class="fieldRowDiv" style="padding-top:10px">
								<div class="fieldDiv">
									<input id="deleteSource" name="deleteSource" type="checkbox" value="1" <?= ($deleteSource ? 'CHECKED' : '') ?> />
									<label for="deleteSource">Delete source images</label>
								</div>
							</div>
						</fieldset>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<label for="collid">Collection ID (collid):</label>
							<select id="collid" name="collid">
								<option value="unselected">Select a Collection</option>
								<option value="unselected">-----------------------------</option>
								<option value="">Field Images</option>
								<option value="0" <?= ($collid == 0 ? 'SELECTED' : '') ?>>All Collection Images</option>
								<?php
								$collArr = $migrationManager->getCollectionMeta();
								foreach($collArr as $id => $collName){
									echo '<option value="' . $id . '" ' . ($collid==$id ? 'SELECTED' : '') . '>' . $collName . '</option>';
								}
								?>
							</select>
						</div>
					</div>
					<div class="fieldRowDiv" stlye="padding-top: 15px">
						<div class="fieldDiv">
							<label for="mediaIdStart">imgId start:</label>
							<input id="mediaIdStart" name="mediaIdStart" type="text" value="<?= $mediaIdStart; ?>" />
						</div>
					</div>
					<div class="fieldRowDiv">
						<div class="fieldDiv">
							<label for="limit">Batch limit:</label>
							<input id="limit" name="limit" type="text" value="<?= $limit; ?>" />
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
