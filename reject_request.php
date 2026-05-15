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
    UPDATE requests
    SET status='rejected'
    WHERE id=?
");

$stmt->bind_param("i",$id);
$stmt->execute();

header("Location:mail.php");