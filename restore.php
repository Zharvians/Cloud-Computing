<?php

require 'config.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

if(empty($_GET['file'])){
    header("Location: trash.php");
    exit;
}

$trashName = basename($_GET['file']);

$stmt = $conn->prepare("
    SELECT *
    FROM files
    WHERE trash_name = ?
    LIMIT 1
");

$stmt->bind_param("s", $trashName);
$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows <= 0){
    header("Location: trash.php");
    exit;
}

$file = $result->fetch_assoc();

/* ========================= */
/* VALIDASI AKSES */
/* ========================= */

if(
    $user['role'] !== 'admin'
    &&
    $file['user_id'] != $user['id']
){
    header("Location: trash.php");
    exit;
}

$trashPath  = "trash/" . $file['trash_name'];
$uploadPath = "uploads/" . $file['name'];

if(file_exists($trashPath)){

    rename($trashPath, $uploadPath);

    $update = $conn->prepare("
        UPDATE files
        SET
            is_deleted = 0,
            trash_name = NULL
        WHERE id = ?
    ");

    $update->bind_param("i", $file['id']);
    $update->execute();
}

header("Location: trash.php");
exit;