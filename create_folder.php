<?php

require 'config.php';

if(!isset($_SESSION['user'])){
    header("Location:login.php");
    exit;
}

$user = $_SESSION['user'];

if($user['role'] === 'viewer'){
    exit("Access denied");
}

$currentFolder = isset($_GET['folder'])
    ? (int)$_GET['folder']
    : 0;

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $folder =
    trim($_POST['folder_name']);

    if($folder !== ''){

        $stmt = $conn->prepare("
            INSERT INTO folders
            (user_id,name,parent_id)
            VALUES (?,?,?)
        ");

        $parentId =
        $currentFolder ?: null;

        $stmt->bind_param(
            "isi",
            $user['id'],
            $folder,
            $parentId
        );

        $stmt->execute();
    }

}

header("Location:index.php?folder=" . $currentFolder);
exit;
?>