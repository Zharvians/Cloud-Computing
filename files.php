<?php

require 'config.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

$type = $_GET['type'] ?? 'all';

$user = $_SESSION['user'];

function isImage($name){

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    return in_array($ext, [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp'
    ]);
}

function isDocument($name){

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    return in_array($ext, [

        'pdf',

        'doc',
        'docx',

        'xls',
        'xlsx',

        'csv',

        'ppt',
        'pptx',

        'txt',

        'zip',
        'rar'
    ]);
}

if($user['role'] === 'admin'){

    $result = $conn->query("
        SELECT files.*, users.username
        FROM files
        JOIN users
        ON files.user_id = users.id
        ORDER BY
        CASE
            WHEN name REGEXP '\\\\.(jpg|jpeg|png|gif|webp)$'
            THEN 0
            ELSE 1
        END,
        uploaded_at DESC
    ");

}else{

    $stmt = $conn->prepare("
        SELECT *
        FROM files
        WHERE user_id=?
        ORDER BY
        CASE
            WHEN name REGEXP '\\\\.(jpg|jpeg|png|gif|webp)$'
            THEN 0
            ELSE 1
        END,
        uploaded_at DESC
    ");

    $stmt->bind_param("i",$user['id']);
    $stmt->execute();

    $result = $stmt->get_result();
}

$files = [];

while($row = $result->fetch_assoc()){

    $name = $row['name'];

    if($type === 'photo' && !isImage($name)){
        continue;
    }

    if($type === 'doc' && !isDocument($name)){
        continue;
    }

    $files[] = $row;
}
?>