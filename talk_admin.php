<?php

require 'config.php';

if(!isset($_SESSION['user'])){
    header("Location:login.php");
    exit;
}

$user = $_SESSION['user'];

if($user['role'] !== 'user'){
    exit("Access denied");
}

/* cooldown 1 jam */

$cooldownStmt = $conn->prepare("
    SELECT created_at
    FROM support_messages
    WHERE sender_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");

$cooldownStmt->bind_param("i", $user['id']);
$cooldownStmt->execute();

$last =
$cooldownStmt
->get_result()
->fetch_assoc();

$canSend = true;
$remaining = 0;

if($last){

    $lastTime = strtotime($last['created_at']);
    $now = time();

    $diff = $now - $lastTime;

    if($diff < 3600){

        $canSend = false;
        $remaining = 3600 - $diff;

    }

}

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="style.css">
<title>Talk To Admin</title>
</head>

<body>

<div class="wrapper">

<div class="card" style="max-width:700px;margin:auto;">

<h2>💬 Talk To Admin</h2>

<p>
Need help? Report bugs? Ask something directly to admin.
</p>

<br>

<?php if(!$canSend): ?>

<div class="error">

⏳ Cooldown active.

Wait
<?= gmdate("i \m\i\\n s \d\e\t\i\k", $remaining) ?>

</div>

<?php else: ?>

<form action="send_support.php" method="POST">

<input
type="text"
name="subject"
placeholder="Subject..."
required

style="
width:100%;
padding:15px;
margin-bottom:15px;
border-radius:15px;
border:none;
">

<textarea
name="message"
placeholder="Type your message..."
required

style="
width:100%;
height:180px;
padding:15px;
border-radius:15px;
border:none;
resize:none;
margin-bottom:20px;
"></textarea>

<button
type="submit"
style="
width:100%;
padding:15px;
border-radius:15px;
">

🚀 Send Message

</button>

</form>

<?php endif; ?>

<br>

<a href="index.php">
⬅ Back
</a>

</div>

</div>

</body>
</html>