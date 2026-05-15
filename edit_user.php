<?php

require 'config.php';

if($_SESSION['user']['role'] !== 'admin'){
    header("Location:index.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("
SELECT *
FROM users
WHERE id=?
");

$stmt->bind_param("i",$id);
$stmt->execute();

$userData = $stmt->get_result()->fetch_assoc();

if(!$userData){
    exit("User tidak ditemukan");
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $username = strtolower(trim($_POST['username']));
    $role = $_POST['role'];

    if(!empty($_POST['password'])){

        $password =
        password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
        UPDATE users
        SET username=?, password=?, role=?
        WHERE id=?
        ");

        $stmt->bind_param(
        "sssi",
        $username,
        $password,
        $role,
        $id
        );

    }else{

        $stmt = $conn->prepare("
        UPDATE users
        SET username=?, role=?
        WHERE id=?
        ");

        $stmt->bind_param(
        "ssi",
        $username,
        $role,
        $id
        );

    }

    $stmt->execute();

    header("Location:manage_users.php");
    exit;
}

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Edit User</title>

<style>

body{
    background:#0f172a;
    font-family:sans-serif;
    color:white;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.box{
    width:400px;
    background:rgba(255,255,255,0.08);
    padding:30px;
    border-radius:20px;
    backdrop-filter:blur(20px);
}

input,select{
    width:100%;
    padding:14px;
    margin-top:12px;
    border:none;
    border-radius:12px;
}

button{
    width:100%;
    padding:14px;
    margin-top:15px;
    border:none;
    border-radius:12px;
    background:#2563eb;
    color:white;
    cursor:pointer;
}

</style>
</head>

<body>

<div class="box">

<h2>Edit User</h2>

<form method="post">

<input
type="text"
name="username"
value="<?= htmlspecialchars($userData['username']) ?>"
required>

<input
type="text"
name="password"
placeholder="Password baru (opsional)">

<select name="role">

<option value="admin"
<?= $userData['role']=='admin'?'selected':'' ?>>
Admin
</option>

<option value="user"
<?= $userData['role']=='user'?'selected':'' ?>>
User
</option>

<option value="viewer"
<?= $userData['role']=='viewer'?'selected':'' ?>>
Viewer
</option>

</select>

<button>Simpan</button>

</form>

</div>

</body>
</html>