<?php

require 'config.php';

if($_SESSION['user']['role'] !== 'admin'){
    header("Location:index.php");
    exit;
}

if($_SESSION['user']['id'] == $id){
    $_SESSION['user']['role'] = $role;
}

$id = (int)($_GET['id'] ?? 0);

$role = $_GET['role'] ?? '';

$allowed = ['admin','user','viewer'];

if(!$id || !in_array($role,$allowed)){
    header("Location:manage_users.php");
    exit;
}

$stmt = $conn->prepare("
UPDATE users
SET role=?
WHERE id=?
");

$stmt->bind_param("si",$role,$id);
$stmt->execute();

header("Location:manage_users.php");
exit;