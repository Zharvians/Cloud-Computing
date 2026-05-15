<?php

require 'config.php';

if(!isset($_GET['id'])){
    die("Folder not found");
}

$folderId = (int)$_GET['id'];

$folderStmt = $conn->prepare("
    SELECT *
    FROM folders
    WHERE id = ?
");

$folderStmt->bind_param(
    "i",
    $folderId
);

$folderStmt->execute();

$folder =
$folderStmt
->get_result()
->fetch_assoc();

if(!$folder){
    die("Folder missing");
}

$zipName =
$folder['name'] . ".zip";

$zip = new ZipArchive();

$tempZip =
tempnam(sys_get_temp_dir(), 'zip');

$zip->open(
    $tempZip,
    ZipArchive::CREATE
);

$fileStmt = $conn->prepare("
    SELECT *
    FROM files
    WHERE folder_id = ?
    AND is_deleted = 0
");

$fileStmt->bind_param(
    "i",
    $folderId
);

$fileStmt->execute();

$files =
$fileStmt->get_result();

while($file = $files->fetch_assoc()){

    $path =
    "uploads/" . $file['name'];

    if(file_exists($path)){

        $zip->addFile(
            $path,
            $file['name']
        );

    }

}

$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="'.$zipName.'"');
header('Content-Length: ' . filesize($tempZip));

readfile($tempZip);

unlink($tempZip);