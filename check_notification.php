<?php

header('Content-Type: application/json');

require 'config.php';

if(!isset($_SESSION['user'])){
    exit;
}

$user = $_SESSION['user'];

$query = $conn->prepare("
    SELECT
        notifications.*,
        users.username AS sender_name
    FROM notifications
    JOIN users
    ON notifications.sender_id = users.id

    WHERE
    (
        target_role = 'all'
        OR target_role = ?
        OR target_user_id = ?
    )

    AND sender_id != ?
    AND popup_shown = 0

    ORDER BY created_at DESC
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

if($notif = $result->fetch_assoc()){

    $update = $conn->prepare("
        UPDATE notifications
        SET popup_shown = 1
        WHERE id = ?
    ");

    $update->bind_param(
        "i",
        $notif['id']
    );

    $update->execute();

    echo json_encode([
        'success' => true,
        'title' => $notif['title'],
        'message' => $notif['message']
    ]);

}else{

    echo json_encode([
        'success' => false
    ]);
}