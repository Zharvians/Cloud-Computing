<?php
require 'config.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $role = "viewer";

    $check = $conn->prepare("SELECT id FROM users WHERE username=?");
    $check->bind_param("s",$username);
    $check->execute();

    if($check->get_result()->num_rows > 0){

        $error = "Username sudah digunakan";

    }else{

        $stmt = $conn->prepare("
            INSERT INTO users(username,password,role)
            VALUES(?,?,'viewer')
        ");

        $stmt->bind_param("ss",$username,$password);

        if($stmt->execute()){

            header("Location:login.php");
            exit;

        }else{
            $error = "Gagal membuat akun";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Register</title>

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
    width:350px;
    background:rgba(255,255,255,0.08);
    padding:35px;
    border-radius:24px;
    backdrop-filter:blur(20px);
}

input{
    width:100%;
    padding:14px;
    margin-top:14px;
    border:none;
    border-radius:14px;
}

button{
    width:100%;
    padding:14px;
    margin-top:18px;
    border:none;
    border-radius:14px;
    background:#2563eb;
    color:white;
    cursor:pointer;
}

a{
    color:#60a5fa;
    text-decoration:none;
}

</style>
</head>
<body>

<div class="box">

<h2>Create Account</h2>

<?php if(isset($error)): ?>
<p><?= $error ?></p>
<?php endif; ?>

<form method="post">

<input
type="text"
name="username"
placeholder="Username"
required>

<input
type="password"
name="password"
placeholder="Password"
required>

<button>Create Account</button>

</form>

<br>
<a href="login.php">← Back to login</a>

</div>

</body>
</html>