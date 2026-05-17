<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config.php';

/* =========================
   CHECK ADMIN
========================= */

if(
    !isset($_SESSION['user']) ||
    $_SESSION['user']['role'] !== 'admin'
){
    die("No access");
}

/* =========================
   VALIDATE ID
========================= */

if(!isset($_GET['id'])){
    die("Request ID tidak ditemukan");
}

$id = (int) $_GET['id'];

/* =========================
   AMBIL DATA REQUEST
========================= */

$get = $conn->prepare("
    SELECT *
    FROM requests
    WHERE id = ?
");

$get->bind_param("i", $id);
$get->execute();

$result = $get->get_result();

if($result->num_rows < 1){
    die("Request tidak ditemukan");
}

$request = $result->fetch_assoc();

/* =========================
   UPDATE STATUS
========================= */

$stmt = $conn->prepare("
    UPDATE requests
    SET status = 'rejected'
    WHERE id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

/* =========================
   KIRIM NOTIF KE USER
========================= */

$adminId = $_SESSION['user']['id'];
$userId  = $request['user_id'];

$message = "Permintaan viewer kamu ditolak oleh admin.";

$notif = $conn->prepare("
    INSERT INTO notifications
    (
        sender_id,
        target_user_id,
        target_role,
        message,
        is_read,
        created_at
    )
    VALUES
    (
        ?, ?, '', ?, 0, NOW()
    )
");

$notif->bind_param(
    "iis",
    $adminId,
    $userId,
    $message
);

$notif->execute();

/* =========================
   REDIRECT
========================= */

header("Location: mail.php?tab=requests");
exit;
?>