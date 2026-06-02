<?php

require 'config.php';

$folder =
$_GET['folder'] ?? '';

$source =
__DIR__ . "/backup/" . basename($folder);

if(!is_dir($source)){
    die("Backup tidak ditemukan");
}

$zipName = $folder . ".zip";

$tmpZip =
sys_get_temp_dir() . "/" . $zipName;

$zip = new ZipArchive();

if(
    $zip->open(
        $tmpZip,
        ZipArchive::CREATE |
        ZipArchive::OVERWRITE
    ) !== TRUE
){
    die("Gagal membuat ZIP");
}

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($source),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach($files as $file){

    if(!$file->isDir()){

        $filePath =
        $file->getRealPath();

        $relativePath =
        substr(
            $filePath,
            strlen($source) + 1
        );

        $zip->addFile(
            $filePath,
            $relativePath
        );
    }
}

$zip->close();

header('Content-Type: application/zip');
header(
'Content-Disposition: attachment; filename="'.$zipName.'"'
);
header('Content-Length: ' . filesize($tmpZip));

readfile($tmpZip);

unlink($tmpZip);
exit;