<?php
require 'config.php';

if(isset($_SESSION['user'])){
    header("Location:index.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s",$username);
    $stmt->execute();

    $result = $stmt->get_result();

    if($user = $result->fetch_assoc()){

        if(password_verify($password,$user['password'])){

            $_SESSION['user'] = [
                'id'=>$user['id'],
                'username'=>$user['username'],
                'role'=>$user['role']
            ];

            header("Location:index.php");
            exit;
        }
    }

    $error = "Login gagal";
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Login</title>

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
    width:320px;
    background:rgba(255,255,255,0.08);
    padding:30px;
    border-radius:20px;
    backdrop-filter:blur(20px);
}

input{
    width:100%;
    padding:12px;
    margin-top:10px;
    border:none;
    border-radius:10px;
}

button{
    width:100%;
    padding:12px;
    margin-top:15px;
    border:none;
    border-radius:10px;
    background:#2563eb;
    color:white;
    cursor:pointer;
}
</style>
</head>

<body>

<div class="box">

<h2>Login</h2>

<?php if(isset($error)): ?>
<p><?= $error ?></p>
<?php endif; ?>

<form method="post">

<input type="text"
       name="username"
       placeholder="Username"
       required>

<input type="password"
       name="password"
       placeholder="Password"
       required>

<button>Login</button>

</form>

</div>

</body>
</html>