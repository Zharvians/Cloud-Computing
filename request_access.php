<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

if($user['role'] !== 'viewer'){
    die("Only viewer can request.");
}

$check = $conn->prepare("
    SELECT * FROM requests
    WHERE user_id=? AND status='pending'
");

$check->bind_param("i", $user['id']);
$check->execute();

$result = $check->get_result();

if($result->num_rows > 0){
    die("Kamu sudah mengirim request.");
}

$message =
"Saya ingin meminta akses upload file di Lunar Cloud.";

$stmt = $conn->prepare("
    INSERT INTO requests(user_id,message)
    VALUES(?,?)
");

$stmt->bind_param("is", $user['id'], $message);
$stmt->execute();

header("Location:index.php?status=request_sent");