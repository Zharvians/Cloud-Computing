<?php

require 'config.php';

if(!isset($_SESSION['user'])){
    die("Unauthorized");
}

if($_SESSION['user']['role'] !== 'admin'){
    die("Access denied");
}

$date = date('Y-m-d');

$backupDir = __DIR__ . "/backup/$date";

if(!is_dir($backupDir)){
    mkdir($backupDir, 0777, true);
}

/* ========================= */
/* ZIP FUNCTION */
/* ========================= */

function zipFolder($source, $destination){

    if(!extension_loaded('zip')){
        return false;
    }

    $zip = new ZipArchive();

    if(
        !$zip->open(
            $destination,
            ZipArchive::CREATE | ZipArchive::OVERWRITE
        )
    ){
        return false;
    }

    $source = realpath($source);

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $source,
            RecursiveDirectoryIterator::SKIP_DOTS
        ),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach($files as $file){

        $file = realpath($file);

        $relative =
        substr(
            $file,
            strlen($source) + 1
        );

        if(is_dir($file)){
            $zip->addEmptyDir($relative);
        }else{
            $zip->addFile($file,$relative);
        }
    }

    $zip->close();

    return true;
}

/* ========================= */
/* BACKUP UPLOADS */
/* ========================= */

if(is_dir(__DIR__ . "/uploads")){

    zipFolder(
        __DIR__ . "/uploads",
        $backupDir . "/uploads.zip"
    );

}

/* ========================= */
/* BACKUP TRASH */
/* ========================= */

if(is_dir(__DIR__ . "/trash")){

    zipFolder(
        __DIR__ . "/trash",
        $backupDir . "/trash.zip"
    );

}

/* ========================= */
/* BACKUP SOURCE CODE */
/* ========================= */

$sourceZip =
new ZipArchive();

$sourceZip->open(
    $backupDir . "/source_code.zip",
    ZipArchive::CREATE | ZipArchive::OVERWRITE
);

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        __DIR__,
        RecursiveDirectoryIterator::SKIP_DOTS
    )
);

foreach($files as $file){

    $filePath = $file->getRealPath();

    if(
        strpos($filePath, DIRECTORY_SEPARATOR . "backup" . DIRECTORY_SEPARATOR) !== false
    ){
        continue;
    }

    $relativePath =
    substr(
        $filePath,
        strlen(__DIR__) + 1
    );

    $sourceZip->addFile(
        $filePath,
        $relativePath
    );
}

$sourceZip->close();

/* ========================= */
/* BACKUP DATABASE */
/* ========================= */

$sql = "";

$tables = [];

$result = $conn->query("SHOW TABLES");

while($row = $result->fetch_array()){
    $tables[] = $row[0];
}

foreach($tables as $table){

    $create =
    $conn
    ->query("SHOW CREATE TABLE `$table`")
    ->fetch_assoc();

    $sql .= "\n\n";
    $sql .= $create['Create Table'];
    $sql .= ";\n\n";

    $rows =
    $conn->query(
        "SELECT * FROM `$table`"
    );

    while($row = $rows->fetch_assoc()){

        $values = [];

        foreach($row as $value){

            if($value === null){
                $values[] = "NULL";
            }else{
                $values[] =
                "'" .
                $conn->real_escape_string($value)
                . "'";
            }

        }

        $sql .=
        "INSERT INTO `$table` VALUES("
        . implode(",",$values)
        . ");\n";

    }

}

file_put_contents(
    $backupDir .
    "/database_cloud_storage_" .
    $date .
    ".sql",
    $sql
);

header("Location:index.php?status=backup_success");
exit;