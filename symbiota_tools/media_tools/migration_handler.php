<?php
/*
 * Script assists in migrating images from a remote server to the portal mount
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('MediaMigration.php');


$dataSourceFile = '';
$transferThumbnail = 1;
$transferWeb = 1;
$transferLarge = 1;
$deleteSource = 1;
$sourcePathPrefix = '';
$targetPathPrefix = '';
$urlPrefix = '';


$migrationManager = new MediaMigration();
$migrationManager->setVerboseMode(2);

$migrationManager->setTransferThumbnail($transferThumbnail);
$migrationManager->setTransferWeb($transferWeb);
$migrationManager->setTransferLarge($transferLarge);
$migrationManager->setUrlMatchTerm($urlMatchTerm);
$migrationManager->setDeleteSource($deleteSource);
$migrationManager->setSourcePathPrefix($sourcePathPrefix);
$migrationManager->setTargetPathPrefix($targetPathPrefix);
$migrationManager->setUrlPrefix($urlPrefix);
$mediaIdStart = $migrationManager->migrateMedia($mediaIdStart, $limit);

?>
