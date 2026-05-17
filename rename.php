<?php

require 'config.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

$type = $_POST['type'];
$id = (int)$_POST['id'];
$newName = trim($_POST['new_name']);

if($newName === ''){
    header("Location:index.php?status=error");
    exit;
}

/* ========================= */
/* 📂 RENAME FOLDER */
/* ========================= */

if($type === 'folder'){

    $stmt = $conn->prepare("
        UPDATE folders
        SET name = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "si",
        $newName,
        $id
    );

    $stmt->execute();

}

/* ========================= */
/* 📄 RENAME FILE */
/* ========================= */

if($type === 'file'){

    $get = $conn->prepare("
        SELECT name
        FROM files
        WHERE id = ?
    ");

    $get->bind_param("i",$id);
    $get->execute();

    $old =
    $get->get_result()
    ->fetch_assoc();

    if($old){

        $oldName = $old['name'];

        $oldPath =
        "uploads/" . $oldName;

        $ext =
        pathinfo(
            $oldName,
            PATHINFO_EXTENSION
        );

        /* biar extension aman */

        if(!str_contains($newName,'.')){
            $newName .= "." . $ext;
        }

        $newPath =
        "uploads/" . $newName;

        if(file_exists($oldPath)){

            rename($oldPath,$newPath);

            $update = $conn->prepare("
                UPDATE files
                SET name = ?
                WHERE id = ?
            ");

            $update->bind_param(
                "si",
                $newName,
                $id
            );

            $update->execute();

        }

    }

}

header("Location:index.php?status=rename_success");
exit;