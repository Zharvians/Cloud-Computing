<?php

require 'config.php';

if($_SESSION['user']['role'] !== 'admin'){
    header("Location:index.php");
    exit;
}

if(empty($_GET['file'])){
    header("Location:index.php");
    exit;
}

$file = basename($_GET['file']);

$stmt = $conn->prepare("
UPDATE files
SET is_public = NOT is_public
WHERE name=?
");

$stmt->bind_param("s",$file);
$stmt->execute();

header("Location:index.php");
exit;