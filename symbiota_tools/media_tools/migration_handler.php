<?php
/*
 * Script assists in migrating images from a remote server to the portal mount
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('MediaMigration.php');


$dataSourceFile = '';		//test_migration.csv
$transferThumbnail = 1;
$transferWeb = 1;
$transferLarge = 1;
$deleteSource = 1;
$sourcePathPrefix = '';		// /Temp/media/source/
$targetPathPrefix = '';
$urlPrefix = '';


$migrationManager = new MediaMigration();
if($dataSourceFile){
	$migrationManager->setVerboseMode(2);
	$migrationManager->setTransferThumbnail($transferThumbnail);
	$migrationManager->setTransferWeb($transferWeb);
	$migrationManager->setTransferLarge($transferLarge);
	$migrationManager->setUrlMatchTerm($urlMatchTerm);
	$migrationManager->setDeleteSource($deleteSource);
	$migrationManager->setSourcePathPrefix($sourcePathPrefix);
	$migrationManager->setTargetPathPrefix($targetPathPrefix);
	$migrationManager->setUrlPrefix($urlPrefix);
	$migrationManager->migrateMediaViaDataFile($dataSourceFile);
}
?>
