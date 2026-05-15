<?php

require 'config.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("
    DELETE FROM folders
    WHERE id = ?
");

$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();

header("Location:index.php");