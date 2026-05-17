<?php

require 'config.php';

if(!isset($_SESSION['user'])){
    header("Location:index.php");
    exit;
}

$user = $_SESSION['user'];

/* ========================= */
/* VALIDASI ID */
/* ========================= */

if(empty($_GET['id'])){
    header("Location:trash.php");
    exit;
}

$id = (int) $_GET['id'];

/* ========================= */
/* AMBIL DATA FILE */
/* ========================= */

$stmt = $conn->prepare("
    SELECT *
    FROM files
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows <= 0){
    header("Location:trash.php");
    exit;
}

$file = $result->fetch_assoc();

/* ========================= */
/* CEK AKSES */
/* ========================= */

if(
    $user['role'] !== 'admin'
    &&
    $file['user_id'] != $user['id']
){
    header("Location:trash.php");
    exit;
}

/* ========================= */
/* HAPUS FILE FISIK */
/* ========================= */

$trashPath =
"trash/" . $file['trash_name'];

if(file_exists($trashPath)){
    unlink($trashPath);
}

/* ========================= */
/* HAPUS DATABASE */
/* ========================= */

$delete = $conn->prepare("
    DELETE FROM files
    WHERE id = ?
");

$delete->bind_param("i", $id);
$delete->execute();

/* ========================= */
/* REDIRECT */
/* ========================= */

header("Location:trash.php");
exit;