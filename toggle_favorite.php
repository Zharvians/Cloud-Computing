<?php

require 'config.php';

if(!isset($_SESSION['user'])){
    exit;
}

$id = (int) $_GET['id'];

$stmt = $conn->prepare("
    UPDATE files
    SET is_favorite =
        IF(is_favorite = 1, 0, 1)
    WHERE id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

header("Location:index.php");