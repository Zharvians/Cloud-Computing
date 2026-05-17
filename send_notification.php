<?php

require 'config.php';

if(!isset($_SESSION['user'])){
    exit("Akses ditolak");
}

$user = $_SESSION['user'];

if($user['role'] !== 'admin'){
    exit("Khusus admin");
}

/* =========================
   GET INPUT
========================= */

$title = trim($_POST['title'] ?? '');
$message = trim($_POST['message'] ?? '');

$targetRole =
trim($_POST['target_role'] ?? '');

$targetUserId =
$_POST['target_user_id'] ?? null;

/* =========================
   VALIDATION
========================= */

if($message === ''){
    exit("Message kosong");
}

if($title === ''){
    $title = "Notification";
}

/* =========================
   FIX EMPTY USER
========================= */

if($targetUserId === ''){
    $targetUserId = null;
}

/* =========================
   PRIORITY
========================= */

if($targetUserId !== null){

    $targetUserId = (int)$targetUserId;

    $targetRole = null;

}else{

    if($targetRole === ''){
        $targetRole = 'all';
    }

}

/* =========================
   INSERT
========================= */

$stmt = $conn->prepare("
    INSERT INTO notifications
    (
        sender_id,
        target_role,
        target_user_id,
        title,
        message,
        created_at
    )
    VALUES
    (
        ?, ?, ?, ?, ?, NOW()
    )
");

if(!$stmt){
    die($conn->error);
}

$stmt->bind_param(
    "isiss",
    $user['id'],
    $targetRole,
    $targetUserId,
    $title,
    $message
);

if(!$stmt->execute()){
    die($stmt->error);
}

/* =========================
   REDIRECT
========================= */

header("Location:index.php?status=notif_sent");
exit;