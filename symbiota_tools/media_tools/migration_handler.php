<?php
/*
 * Script assists in migrating images from a remote server to the portal mount
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('MediaMigration.php');


$transferThumbnail = 1;
$transferWeb = 1;
$transferLarge = 1;
$deleteSource = 1;
$urlMatchTerm = '';
$sourcePathPrefix = '';
$targetPathPrefix = '';
$targetUrlPrefix = '';

//Use if run remotely from database (e.g. via input/output files)
$dataSourceFile = '';

//Use if run in conjunction with database
$mediaIdStart = 0;
$limit = 1000;
$conditionArr = [['','','']];		//format: [['<FIELD_NAME>','<CONDITION>','<VALUE>']]  e.g. [['fileSize','ISNULL',''],['originalUrl','LIKE','/media/%'],['occid','NOTNULL','']]
$userName = '';
$pwd = '';
$database = '';
$host = '';


$migrationManager = new MediaMigration();
$migrationManager->setVerboseMode(2);
$migrationManager->setTransferThumbnail($transferThumbnail);
$migrationManager->setTransferWeb($transferWeb);
$migrationManager->setTransferLarge($transferLarge);
$migrationManager->setDeleteSource($deleteSource);
$migrationManager->setUrlMatchTerm($urlMatchTerm);
$migrationManager->setSourcePathPrefix($sourcePathPrefix);
$migrationManager->setTargetPathPrefix($targetPathPrefix);
$migrationManager->setTargetUrlPrefix($targetUrlPrefix);
if($dataSourceFile){
	$migrationManager->migrateMediaViaDataFile($dataSourceFile);
}
else{
	$migrationManager->setDatabaseConnection($userName, $pwd, $database, $host);
	$migrationManager->setConditionStr($conditionArr);
	$migrationManager->migrateMediaViaDatabase($mediaIdStart, $limit);
}
?>
