<?php

require 'config.php';

if(!isset($_SESSION['user'])){
    header("Location:login.php");
    exit;
}

$userId = $_SESSION['user']['id'];

$stmt = $conn->prepare("
SELECT * FROM users
WHERE id=?
");

$stmt->bind_param("i",$userId);
$stmt->execute();

$result = $stmt->get_result();
$userData = $result->fetch_assoc();

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if(!empty($password)){

        $hashed = password_hash($password,PASSWORD_DEFAULT);

        $update = $conn->prepare("
        UPDATE users
        SET username=?, password=?
        WHERE id=?
        ");

        $update->bind_param(
            "ssi",
            $username,
            $hashed,
            $userId
        );

    }else{

        $update = $conn->prepare("
        UPDATE users
        SET username=?
        WHERE id=?
        ");

        $update->bind_param(
            "si",
            $username,
            $userId
        );
    }

    $update->execute();

    $_SESSION['user']['username'] = $username;

    header("Location:index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<title>Edit Profile</title>

<style>

body{
    background:#0f172a;
    font-family:sans-serif;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
    color:white;
}

.box{
    width:400px;
    background:rgba(255,255,255,0.08);
    padding:30px;
    border-radius:24px;
    backdrop-filter:blur(20px);
}

input{
    width:100%;
    padding:14px;
    margin-top:12px;
    border:none;
    border-radius:14px;
}

button{
    width:100%;
    padding:14px;
    margin-top:16px;
    border:none;
    border-radius:14px;
    background:#2563eb;
    color:white;
    cursor:pointer;
}

a{
    color:white;
    text-decoration:none;
    display:block;
    margin-top:15px;
    text-align:center;
}

</style>

</head>

<body>

<div class="box">

<h2>Edit Profile</h2>

<form method="post">

<input
type="text"
name="username"
value="<?= htmlspecialchars($userData['username']) ?>"
required>

<input
type="password"
name="password"
placeholder="Password baru">

<button>Simpan</button>

</form>

<a href="index.php">
Kembali
</a>

</div>

</body>
</html>