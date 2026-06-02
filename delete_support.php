<?php

require 'config.php';

if(!isset($_SESSION['user'])){
    die("Akses ditolak");
}

$user = $_SESSION['user'];

if($user['role'] !== 'admin'){
    die("Hanya admin");
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("
    DELETE FROM support_messages
    WHERE id = ?
");

$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();

header("Location:mail.php");
exit;