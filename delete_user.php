<?php

require 'config.php';

if($_SESSION['user']['role'] !== 'admin'){
    header("Location:index.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if($id === $_SESSION['user']['id']){
    exit("Tidak bisa hapus akun sendiri");
}

$stmt = $conn->prepare("
DELETE FROM users
WHERE id=?
");

$stmt->bind_param("i",$id);
$stmt->execute();

header("Location:manage_users.php");
exit;