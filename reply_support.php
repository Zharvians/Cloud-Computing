<?php

require 'config.php';

if(!isset($_SESSION['user'])){
    die("Akses ditolak");
}

$user = $_SESSION['user'];

if($user['role'] !== 'admin'){
    die("Hanya admin");
}

$id = (int)$_POST['id'];

$reply = trim($_POST['reply']);

/* ========================= */
/* 📩 AMBIL DATA MESSAGE */
/* ========================= */

$get = $conn->prepare("
    SELECT *
    FROM support_messages
    WHERE id = ?
");

$get->bind_param("i", $id);

$get->execute();

$data =
$get
->get_result()
->fetch_assoc();

if(!$data){
    die("Message tidak ditemukan");
}

/* ========================= */
/* ✅ UPDATE SUPPORT */
/* ========================= */

$update = $conn->prepare("
    UPDATE support_messages
    SET
        admin_reply = ?,
        status = 'replied'
    WHERE id = ?
");

$update->bind_param(
    "si",
    $reply,
    $id
);

$update->execute();

/* ========================= */
/* 🔔 NOTIF KE USER */
/* ========================= */

$title =
"💬 Admin Replied";

$message =
"Admin replied to your support message:\n\n"
. $reply;

$targetUser =
$data['sender_id'];

$notif = $conn->prepare("
    INSERT INTO notifications
    (
        sender_id,
        title,
        message,
        target_user_id
    )
    VALUES (?,?,?,?)
");

$notif->bind_param(
    "issi",
    $user['id'],
    $title,
    $message,
    $targetUser
);

$notif->execute();

header("Location:mail.php");
exit;