<?php

require 'config.php';

if(!isset($_SESSION['user'])){
    exit;
}

$folder = $_GET['folder'] ?? '';

$backupPath =
__DIR__ . "/backup/" . basename($folder);

function deleteFolder($dir){

    if(!is_dir($dir)){
        return;
    }

    $files = array_diff(
        scandir($dir),
        ['.','..']
    );

    foreach($files as $file){

        $path = $dir . '/' . $file;

        if(is_dir($path)){
            deleteFolder($path);
        }else{
            unlink($path);
        }
    }

    rmdir($dir);
}

deleteFolder($backupPath);

header("Location: backup_manager.php");
exit;