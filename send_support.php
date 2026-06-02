<?php

require 'config.php';

if(!isset($_SESSION['user'])){
    die("Akses ditolak");
}

$user = $_SESSION['user'];

if($user['role'] !== 'user'){
    die("Hanya user");
}

$subject = trim($_POST['subject']);
$message = trim($_POST['message']);

/* ========================= */
/* ⏳ COOLDOWN 1 JAM */
/* ========================= */

$cooldown = $conn->prepare("
    SELECT created_at
    FROM support_messages
    WHERE sender_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");

$cooldown->bind_param(
    "i",
    $user['id']
);

$cooldown->execute();

$result = $cooldown
->get_result()
->fetch_assoc();

if($result){

    $lastTime = strtotime($result['created_at']);
    $now = time();

    if(($now - $lastTime) < 3600){

        die("Cooldown 1 jam belum selesai");
    }
}

/* ========================= */
/* 💬 INSERT MESSAGE */
/* ========================= */

$stmt = $conn->prepare("
    INSERT INTO support_messages
    (
        sender_id,
        subject,
        message
    )
    VALUES (?,?,?)
");

$stmt->bind_param(
    "iss",
    $user['id'],
    $subject,
    $message
);

$stmt->execute();

/* ========================= */
/* 🔔 NOTIF KE ADMIN */
/* ========================= */

$notifTitle =
"💬 New Support Message";

$notifMessage =
$user['username'] .
" sent a message to admin.";

$notif = $conn->prepare("
    INSERT INTO notifications
    (
        sender_id,
        title,
        message,
        target_role
    )
    VALUES (?,?,?,?)
");

$targetRole = "admin";

$notif->bind_param(
    "isss",
    $user['id'],
    $notifTitle,
    $notifMessage,
    $targetRole
);

$notif->execute();

header("Location:index.php?status=support_sent");
exit;