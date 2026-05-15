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

    $error = "Username atau password salah";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Lunar Storage Login</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{
    height:100vh;
    overflow:hidden;
    display:flex;
    justify-content:center;
    align-items:center;
    background:
    radial-gradient(circle at top left,#2563eb,#0f172a 50%);
    color:white;
    position:relative;
}

/* Floating Glow */
.glow{
    position:absolute;
    border-radius:50%;
    filter:blur(80px);
    animation:float 8s infinite ease-in-out;
}

.glow1{
    width:300px;
    height:300px;
    background:#3b82f6;
    top:-100px;
    left:-100px;
}

.glow2{
    width:250px;
    height:250px;
    background:#9333ea;
    bottom:-100px;
    right:-100px;
}

@keyframes float{
    0%{transform:translateY(0px);}
    50%{transform:translateY(30px);}
    100%{transform:translateY(0px);}
}

/* Login Box */
.box{
    width:380px;
    padding:35px;
    border-radius:28px;

    background:rgba(255,255,255,0.08);

    backdrop-filter:blur(25px);

    border:1px solid rgba(255,255,255,0.1);

    box-shadow:
    0 0 40px rgba(59,130,246,0.3);

    z-index:10;

    animation:fadeIn 1s ease;
}

@keyframes fadeIn{
    from{
        opacity:0;
        transform:translateY(30px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

.logo{
    text-align:center;
    font-size:55px;
    margin-bottom:10px;
}

.title{
    text-align:center;
    font-size:28px;
    font-weight:bold;
}

.subtitle{
    text-align:center;
    opacity:0.7;
    margin-top:6px;
    margin-bottom:25px;
}

.input-group{
    margin-top:18px;
}

.input-group input{
    width:100%;
    padding:15px;
    border:none;
    outline:none;
    border-radius:16px;
    background:rgba(255,255,255,0.08);
    color:white;
    font-size:15px;
    transition:0.3s;
}

.input-group input:focus{
    background:rgba(255,255,255,0.14);
    box-shadow:0 0 20px rgba(59,130,246,0.5);
}

.input-group input::placeholder{
    color:rgba(255,255,255,0.6);
}

button{
    width:100%;
    padding:15px;
    margin-top:22px;
    border:none;
    border-radius:16px;
    background:linear-gradient(135deg,#2563eb,#3b82f6);
    color:white;
    font-size:15px;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
}

button:hover{
    transform:translateY(-3px);
    box-shadow:0 0 25px rgba(59,130,246,0.6);
}

.error{
    background:#ef4444;
    padding:12px;
    border-radius:14px;
    margin-top:15px;
    text-align:center;
}

.links{
    margin-top:20px;
    display:flex;
    justify-content:space-between;
    gap:10px;
}

.links a{
    flex:1;
    text-align:center;
    text-decoration:none;
    color:white;
    padding:12px;
    border-radius:14px;
    background:rgba(255,255,255,0.08);
    transition:0.3s;
    font-size:14px;
}

.links a:hover{
    background:rgba(255,255,255,0.15);
}

.credit{
    text-align:center;
    margin-top:20px;
    font-size:12px;
    opacity:0.6;
}

</style>
</head>

<body>

<div class="glow glow1"></div>
<div class="glow glow2"></div>

<div class="box">

    <div class="logo">☁️</div>

    <div class="title">
        Lunar Storage
    </div>

    <div class="subtitle">
        Secure Cloud Storage Platform
    </div>

    <?php if(isset($error)): ?>
        <div class="error">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="post">

        <div class="input-group">
            <input
            type="text"
            name="username"
            placeholder="Username"
            required>
        </div>

        <div class="input-group">
            <input
            type="password"
            name="password"
            placeholder="Password"
            required>
        </div>

        <button>
            Login
        </button>

    </form>

    <div class="links">

        <a href="register.php">
            ✨ Create Account
        </a>

        <a href="forgot_password.php">
            🔒 Forgot Password
        </a>

    </div>

    <div class="credit">
        © Zharvian - Muhammad Ade Ramadhani
    </div>

</div>

</body>
</html>