<?php

session_start();

$conn = new mysqli(
    "sql211.infinityfree.com",
    "if0_41722830",
    "ZeroFurina24",
    "if0_41722830_cloud_storage"
);

header("Content-Type: application/json");

if(!isset($_SESSION['user'])){

    echo json_encode([
        "role" => null
    ]);

    exit;
}

$id = $_SESSION['user']['id'];

$stmt = $conn->prepare("
SELECT role
FROM users
WHERE id=?
");

$stmt->bind_param("i",$id);
$stmt->execute();

$user =
$stmt->get_result()->fetch_assoc();

echo json_encode([
    "role" => $user['role']
]);