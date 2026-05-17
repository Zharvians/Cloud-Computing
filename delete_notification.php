<?php

require 'config.php';

if(!isset($_SESSION['user'])){
    exit;
}

$user = $_SESSION['user'];

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("
    UPDATE notifications
    SET deleted_by_receiver = 1
    WHERE id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: mail.php");
exit;