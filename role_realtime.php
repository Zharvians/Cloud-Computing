<?php

if(isset($_SESSION['user'])){

    $id = $_SESSION['user']['id'];

    $stmt = $conn->prepare("
    SELECT role
    FROM users
    WHERE id=?
    ");

    $stmt->bind_param("i",$id);
    $stmt->execute();

    $data =
    $stmt->get_result()->fetch_assoc();

    if($data){

        $_SESSION['user']['role'] =
        $data['role'];

    }

}