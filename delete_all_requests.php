<?php

require 'config.php';
session_start();

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin'){
    die("Akses ditolak");
}

/* =========================
   DELETE ALL REQUESTS
========================= */
$conn->query("DELETE FROM requests");

/* =========================
   DELETE ALL RELATED NOTIFICATIONS
   (yang punya request_id)
========================= */
$conn->query("DELETE FROM notifications WHERE request_id IS NOT NULL");

header("Location: mail.php?tab=requests");
exit;