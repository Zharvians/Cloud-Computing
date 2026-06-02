<?php

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require 'config.php';

if(!isset($_SESSION['user'])){

    echo json_encode([
        'success' => false
    ]);

    exit;
}

$user = $_SESSION['user'];

/* ========================= */
/* 🔔 GET LATEST NOTIFICATION */
/* ========================= */

$query = $conn->prepare("
    SELECT
        notifications.id,
        notifications.title,
        notifications.message,
        notifications.created_at,
        users.username AS sender_name

    FROM notifications

    JOIN users
    ON notifications.sender_id = users.id

    WHERE
    (
        notifications.target_role = 'all'
        OR notifications.target_role = ?
        OR notifications.target_user_id = ?
    )

    AND notifications.sender_id != ?

    ORDER BY notifications.created_at DESC

    LIMIT 1
");

$query->bind_param(
    "sii",
    $user['role'],
    $user['id'],
    $user['id']
);

$query->execute();

$result = $query->get_result();

/* ========================= */
/* 📦 RESPONSE */
/* ========================= */

if($notif = $result->fetch_assoc()){

    echo json_encode([

        'success' => true,

        'id' => (int)$notif['id'],

        'title' => $notif['title'],

        'message' => $notif['message'],

        'sender' => $notif['sender_name'],

        'created_at' => $notif['created_at']

    ]);

}else{

    echo json_encode([
        'success' => false
    ]);
}
?>