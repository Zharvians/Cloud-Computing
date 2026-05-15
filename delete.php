<?php

declare(strict_types=1);

require 'config.php';

if(
    !isset($_SESSION['user']) ||
    $_SESSION['user']['role'] !== 'admin'
){
    header("Location:index.php");
    exit;
}

/* ========================= */
/* FOLDER */
/* ========================= */

$uploadDir = __DIR__ . '/uploads/';
$trashDir  = __DIR__ . '/trash/';

if(!is_dir($trashDir)){
    mkdir($trashDir, 0777, true);
}

/* ========================= */
/* VALIDASI */
/* ========================= */

if(empty($_GET['id'])){
    header("Location:index.php?status=error");
    exit;
}

$id = (int) $_GET['id'];

/* ========================= */
/* AMBIL FILE */
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
    header("Location:index.php?status=error");
    exit;
}

$file = $result->fetch_assoc();

$fileName = $file['name'];

$sourcePath = $uploadDir . $fileName;

/* cek file */

if(!file_exists($sourcePath)){
    header("Location:index.php?status=error");
    exit;
}

/* ========================= */
/* NAMA BARU TRASH */
/* ========================= */

$newFileName =
time() . "_" . $fileName;

$trashPath =
$trashDir . $newFileName;

if(!file_exists($sourcePath)){
    // file sudah hilang tapi data masih ada
    $update = $conn->prepare("
        UPDATE files
        SET is_deleted = 1
        WHERE id = ?
    ");
    $update->bind_param("i", $id);
    $update->execute();

    header("Location:index.php?status=missing_file");
    exit;
}

/* ========================= */
/* PINDAH KE TRASH */
/* ========================= */

if(rename($sourcePath, $trashPath)){

    $update = $conn->prepare("
        UPDATE files
        SET
            is_deleted = 1,
            trash_name = ?
        WHERE id = ?
    ");

    $update->bind_param(
        "si",
        $newFileName,
        $id
    );

    $update->execute();

    header("Location:index.php?status=delete_success");
    exit;
}

/* ========================= */
/* ERROR */
/* ========================= */

header("Location:index.php?status=error");
exit;