<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config.php';

if(
    !isset($_SESSION['user']) ||
    $_SESSION['user']['role'] !== 'admin'
){
    die("Akses ditolak");
}

$result = $conn->query("
    SELECT requests.*,
    users.username
    FROM requests
    JOIN users
    ON requests.user_id = users.id
    ORDER BY created_at DESC
");

?>

<!DOCTYPE html>
<html>
<head>
<title>Mail Admin</title>
<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{
    min-height:100vh;
    padding:30px;
    color:white;
    overflow-x:hidden;

    background:
    radial-gradient(circle at top,
    rgba(0,255,200,0.18),
    transparent 25%),

    radial-gradient(circle at right,
    rgba(0,140,255,0.18),
    transparent 30%),

    linear-gradient(to bottom,
    #020617 0%,
    #0f172a 40%,
    #111827 70%,
    #020617 100%);
}

/* Stars */

body::before{
    content:"";
    position:fixed;
    inset:0;

    background-image:
    radial-gradient(white 1px, transparent 1px);

    background-size:40px 40px;

    opacity:0.08;

    pointer-events:none;
}

/* Title */

h1{
    font-size:38px;
    margin-bottom:30px;

    background:linear-gradient(
        90deg,
        #ffffff,
        #60a5fa,
        #c084fc
    );

    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

/* Back button */

.back-btn{
    display:inline-block;
    margin-bottom:25px;

    padding:14px 22px;

    border-radius:18px;

    text-decoration:none;
    color:white;

    background:
    linear-gradient(
        135deg,
        rgba(59,130,246,0.35),
        rgba(147,51,234,0.35)
    );

    border:1px solid rgba(255,255,255,0.08);

    backdrop-filter:blur(20px);

    transition:0.3s;
}

.back-btn:hover{
    transform:translateY(-3px);

    box-shadow:
    0 10px 25px rgba(59,130,246,0.25);
}

/* Card */

.card{
    background:rgba(255,255,255,0.06);

    backdrop-filter:blur(25px);

    border:1px solid rgba(255,255,255,0.08);

    border-radius:28px;

    padding:25px;

    margin-bottom:22px;

    box-shadow:
    0 10px 40px rgba(0,0,0,0.25);

    transition:0.3s;
}

.card:hover{
    transform:translateY(-4px);

    box-shadow:
    0 0 30px rgba(59,130,246,0.2);
}

.card h3{
    font-size:22px;
    margin-bottom:12px;
}

.card p{
    line-height:1.7;
    opacity:0.9;
}

small{
    opacity:0.7;
}

/* Buttons */

.action-btn{
    display:inline-block;

    padding:12px 18px;

    border-radius:14px;

    text-decoration:none;
    color:white;

    margin-right:10px;

    transition:0.25s;
}

.action-btn:hover{
    transform:translateY(-2px);
}

.approve{
    background:
    linear-gradient(
        135deg,
        #16a34a,
        #22c55e
    );
}

.reject{
    background:
    linear-gradient(
        135deg,
        #dc2626,
        #ef4444
    );
}

/* Scrollbar */

::-webkit-scrollbar{
    width:10px;
}

::-webkit-scrollbar-thumb{
    background:linear-gradient(
        #3b82f6,
        #9333ea
    );

    border-radius:20px;
}

</style>
</head>
<body>

<h1>📩 Request Mail</h1>

<br>

<a href="index.php" class="back-btn">
    ← Kembali ke Dashboard
</a>

<br><br>

<?php while($r = $result->fetch_assoc()): ?>

<div class="card">

    <h3>
        <?= htmlspecialchars($r['username']) ?>
    </h3>

    <p>
        <?= htmlspecialchars($r['message']) ?>
    </p>

    <br>

    <small>
        Status:
        <?= $r['status'] ?>
    </small>

    <br><br>

    <?php if($r['status'] === 'pending'): ?>

        <a
        class="action-btn approve"
        href="approve_request.php?id=<?= $r['id'] ?>">
            Approve
        </a>

        <a
        class="action-btn reject"
        href="reject_request.php?id=<?= $r['id'] ?>">
            Reject
        </a>

    <?php endif; ?>

</div>

<?php endwhile; ?>

</body>
</html>