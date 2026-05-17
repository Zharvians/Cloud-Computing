<?php

require 'config.php';

if(!isset($_SESSION['user'])){
    exit;
}

$user = $_SESSION['user'];

if($user['role'] !== 'admin'){
    exit;
}

$id = (int)($_POST['id'] ?? 0);

$deleteReceiver =
isset($_POST['delete_receiver']);

/* =========================
   HAPUS UNTUK SENDER
========================= */

if($deleteReceiver){

    /* hapus untuk semua */

    $stmt = $conn->prepare("
        UPDATE notifications
        SET
            deleted_by_sender = 1,
            deleted_by_receiver = 1
        WHERE id = ?
        AND sender_id = ?
    ");

    $stmt->bind_param(
        "ii",
        $id,
        $user['id']
    );

}else{

    /* hapus hanya sender */

    $stmt = $conn->prepare("
        UPDATE notifications
        SET deleted_by_sender = 1
        WHERE id = ?
        AND sender_id = ?
    ");

    $stmt->bind_param(
        "ii",
        $id,
        $user['id']
    );
}

$stmt->execute();

header("Location: mail.php?tab=sent");
exit;