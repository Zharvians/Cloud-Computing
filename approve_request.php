<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config.php';

if(
    !isset($_SESSION['user']) ||
    $_SESSION['user']['role'] !== 'admin'
){
    die("No access");
}

$id = $_GET['id'];

$stmt = $conn->prepare("
    SELECT * FROM requests
    WHERE id=?
");

$stmt->bind_param("i",$id);
$stmt->execute();

$request = $stmt->get_result()->fetch_assoc();

if(!$request){
    die("Request not found");
}

/* ubah role */

$update = $conn->prepare("
    UPDATE users
    SET role='user'
    WHERE id=?
");

$update->bind_param("i",$request['user_id']);
$update->execute();

/* update request */

$done = $conn->prepare("
    UPDATE requests
    SET status='approved'
    WHERE id=?
");

$done->bind_param("i",$id);
$done->execute();

header("Location:mail.php");