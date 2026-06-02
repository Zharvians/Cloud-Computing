<?php

ob_start();

require 'config.php';

if(!isset($_SESSION['user'])){
    exit;
}

if($_SESSION['user']['role'] !== 'admin'){
    exit;
}

if(!isset($_GET['id'])){
    header("Location: mail.php?tab=support");
    exit;
}

$id = (int) $_GET['id'];

/* =========================
   GET SUPPORT MESSAGE
========================= */

$get = $conn->prepare("
    SELECT *
    FROM support_messages
    WHERE id = ?
");

$get->bind_param("i", $id);
$get->execute();

$data = $get
->get_result()
->fetch_assoc();

if(!$data){
    header("Location: mail.php?tab=support");
    exit;
}

/* =========================
   UPDATE STATUS
========================= */

$rejectMessage =
"Your support request has been rejected by admin.";

$update = $conn->prepare("
    UPDATE support_messages
    SET
        status = 'rejected',
        admin_reply = ?
    WHERE id = ?
");

$update->bind_param(
    "si",
    $rejectMessage,
    $id
);

$update->execute();

/* =========================
   SEND NOTIFICATION
========================= */

$title =
"Admin Rejected Your Support Message";

$message =
"Your support message was rejected by admin.";

$senderId = $_SESSION['user']['id'];

$notif = $conn->prepare("
    INSERT INTO notifications (
        sender_id,
        target_role,
        target_user_id,
        title,
        message,
        is_read,
        deleted_by_receiver,
        deleted_by_sender,
        created_at
    )
    VALUES (
        ?, 
        NULL,
        ?, 
        ?, 
        ?, 
        0,
        0,
        0,
        NOW()
    )
");

$notif->bind_param(
    "iiss",
    $senderId,
    $data['sender_id'],
    $title,
    $message
);

$notif->execute();

/* =========================
   FORCE REDIRECT
========================= */

header("Location: mail.php?tab=support");
ob_end_flush();
exit;