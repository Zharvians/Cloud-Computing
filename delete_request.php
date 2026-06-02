<?php

require 'config.php';
session_start();

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin'){
    die("Akses ditolak");
}

if(!isset($_GET['id'])){
    die("ID tidak ditemukan");
}

$id = (int) $_GET['id'];

/* =========================
   DELETE REQUEST
========================= */
$stmt = $conn->prepare("DELETE FROM requests WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

/* =========================
   DELETE NOTIFICATION TERKAIT
   (WAJIB ADA COLUMN request_id)
========================= */
$stmt2 = $conn->prepare("
    DELETE FROM notifications 
    WHERE request_id = ?
");
$stmt2->bind_param("i", $id);
$stmt2->execute();

/* OPTIONAL DEBUG (hapus kalau sudah stabil)
echo "REQ: ".$stmt->affected_rows." | NOTIF: ".$stmt2->affected_rows;
exit;
*/

header("Location: mail.php?tab=requests");
exit;