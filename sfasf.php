<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config.php';

if(!isset($_SESSION['user'])){
    die("Akses ditolak");
}

$user = $_SESSION['user'];

$role   = $user['role'];
$userId = $user['id'];

$tab    = $_GET['tab'] ?? 'notifications';
$filter = $_GET['filter'] ?? 'all';

$today = date('Y-m-d');

/* =========================
   NOTIFICATIONS
========================= */

$notifSQL = "
SELECT 
    notifications.*,
    users.username AS sender_name
FROM notifications
LEFT JOIN users 
    ON notifications.sender_id = users.id
WHERE (
    notifications.target_role = 'all'
    OR notifications.target_role = ?
    OR notifications.target_user_id = ?
)
AND notifications.deleted_by_receiver = 0
";

$params = [$role, $userId];
$types  = "si";

/* FILTER */

if($filter === 'unread'){
    $notifSQL .= " AND notifications.is_read = 0";
}

if($filter === 'today'){
    $notifSQL .= " AND DATE(notifications.created_at) = ?";
    $params[] = $today;
    $types .= "s";
}

$notifSQL .= " ORDER BY notifications.created_at DESC";

$notifStmt = $conn->prepare($notifSQL);
$notifStmt->bind_param($types, ...$params);
$notifStmt->execute();

$notifResult = $notifStmt->get_result();

/* =========================
   SENT HISTORY
========================= */

$sentResult = null;

if($role === 'admin'){

    $sentStmt = $conn->prepare("

        SELECT
            id,
            title,
            message,
            created_at,
            'notification' AS type
        FROM notifications
        WHERE sender_id = ?
        AND deleted_by_sender = 0

        UNION ALL

       SELECT
            id,
            subject AS title,
            message,
            created_at,
            'support' AS type
        FROM support_messages
        WHERE sender_id = ?
        AND deleted_by_sender = 0

        ORDER BY created_at DESC
    ");

    $sentStmt->bind_param(
        "ii",
        $userId,
        $userId
    );

}else{

    $sentStmt = $conn->prepare("

        SELECT
            id,
            subject AS title,
            message,
            created_at,
            'support' AS type
        FROM support_messages
        WHERE sender_id = ?
        AND deleted_by_sender = 0
        ORDER BY created_at DESC
    ");

    $sentStmt->bind_param(
        "i",
        $userId
    );
}

$sentStmt->execute();
$sentResult = $sentStmt->get_result();

/* =========================
   REQUESTS (ADMIN ONLY)
========================= */

$requestsResult = null;

if($role === 'admin'){

    $requestsResult = $conn->query("
        SELECT 
            requests.*,
            users.username
        FROM requests
        JOIN users 
            ON requests.user_id = users.id
        ORDER BY requests.created_at DESC
    ");
}

/* =========================
   SUPPORT CHAT (ADMIN ONLY)
========================= */

$supportQuery = null;

if($role === 'admin'){

    $supportQuery = $conn->query("
        SELECT
            support_messages.*,
            users.username
        FROM support_messages
        JOIN users
        ON support_messages.sender_id = users.id
        ORDER BY created_at DESC
    ");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Mail Center</title>

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

.top-menu{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:30px;
}

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

.action-btn{
    display:inline-block;

    padding:12px 18px;

    border-radius:14px;

    text-decoration:none;
    color:white;

    transition:0.25s;
}

.action-btn:hover{
    transform:translateY(-2px);
}

.blue{
    background:
    linear-gradient(
        135deg,
        #2563eb,
        #3b82f6
    );
}

.green{
    background:
    linear-gradient(
        135deg,
        #16a34a,
        #22c55e
    );
}

.red{
    background:
    linear-gradient(
        135deg,
        #dc2626,
        #ef4444
    );
}

.badge{
    display:inline-block;
    padding:6px 12px;
    border-radius:999px;
    font-size:13px;
    margin-top:10px;

    background:rgba(255,255,255,0.08);
}

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

.empty{
    opacity:0.7;
    padding:20px;
}

/* ========================= */
/* LAYOUT */
/* ========================= */

.layout{
    display:flex;
    min-height:100vh;
}

/* ========================= */
/* SIDEBAR */
/* ========================= */

/* ========================= */
/* SIDEBAR */
/* ========================= */

.sidebar{
    width:280px;

    position:fixed;
    top:0;
    left:0;
    bottom:0;

    padding:30px 22px;

    background:
    linear-gradient(
        180deg,
        rgba(15,23,42,0.95) 0%,
        rgba(17,24,39,0.92) 45%,
        rgba(2,6,23,0.96) 100%
    );

    backdrop-filter:blur(24px);

    border-right:
    1px solid rgba(255,255,255,0.08);

    box-shadow:
    0 0 40px rgba(0,0,0,0.35),
    0 0 25px rgba(59,130,246,0.08);

    display:flex;
    flex-direction:column;

    z-index:100;

    overflow:hidden;
}

/* glow effect */

.sidebar::before{
    content:"";

    position:absolute;
    inset:0;

    background:
    radial-gradient(
        circle at top left,
        rgba(59,130,246,0.18),
        transparent 35%
    ),

    radial-gradient(
        circle at bottom right,
        rgba(168,85,247,0.15),
        transparent 40%
    );

    pointer-events:none;
}

/* title */

.sidebar h2{
    position:relative;

    font-size:30px;
    font-weight:800;

    margin-bottom:40px;

    background:linear-gradient(
        90deg,
        #ffffff,
        #7dd3fc,
        #c084fc
    );

    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;

    z-index:2;
}

/* menu wrapper */

.menu{
    position:relative;

    flex:1;

    display:flex;
    flex-direction:column;
    justify-content:center;

    gap:14px;

    z-index:2;
}

/* buttons */

.menu a{
    display:flex;
    align-items:center;

    gap:12px;

    padding:15px 18px;

    border-radius:18px;

    text-decoration:none;
    color:white;

    background:rgba(255,255,255,0.05);

    border:
    1px solid rgba(255,255,255,0.06);

    backdrop-filter:blur(18px);

    transition:0.28s;
}

.menu a:hover{

    transform:
    translateX(6px);

    background:
    linear-gradient(
        135deg,
        rgba(59,130,246,0.22),
        rgba(168,85,247,0.22)
    );

    box-shadow:
    0 10px 30px rgba(59,130,246,0.18);
}

/* ========================= */
/* CONTENT */
/* ========================= */

.main-content{
    margin-left:280px;
    width:calc(100% - 280px);

    padding:35px;
}
/* ========================= */
/* 📊 MINI STATS */
/* ========================= */

.mini-stats{
    margin-top:20px;

    padding:18px;

    border-radius:22px;

    background:rgba(255,255,255,0.08);

    backdrop-filter:blur(20px);

    border:1px solid rgba(255,255,255,0.08);

    font-size:13px;

    line-height:1.8;
}

/* ========================= */
/* 🔻 SIDEBAR BOTTOM */
/* ========================= */

.sidebar-bottom{
    display:flex;
    flex-direction:column;
    gap:12px;
}

.sidebar-bottom button{

    width:100%;

    padding:14px;

    border:none;

    border-radius:18px;

    cursor:pointer;

    color:inherit;

    background:rgba(255,255,255,0.12);

    backdrop-filter:blur(20px);

    transition:0.3s;
}

.sidebar-bottom button:hover{

    transform:translateY(-2px);

    background:
    linear-gradient(
        135deg,
        rgba(59,130,246,0.3),
        rgba(147,51,234,0.3)
    );
}

/* ========================= */
/* 🌙 DARK MODE */
/* ========================= */

body.dark-mode .sidebar{
    background:rgba(255,255,255,0.06);
}

body.dark-mode .menu a,
body.dark-mode .mini-stats,
body.dark-mode .sidebar-bottom button{
    color:white;
}
    
</style>
</head>
<body>

<div class="layout">
    
<!-- Sidebar -->
<div class="sidebar">

    <!-- ATAS -->
    <div>

        <h2>☁️ Lunar Cloud</h2>

    </div>

        <!-- MENU -->
    <div class="menu">

        <?php if($user['role'] === 'admin'): ?>
            <a href="manage_users.php">
                👥 Manage Users
            </a>
        <?php endif; ?>

        <a href="mail.php">
            📩 Mail
        </a>

        <a href="index.php">
            📁 File Manager
        </a>

        <?php if($user['role'] !== 'viewer'): ?>

            <a href="upload_page.php">
                📤 Upload
            </a>

            <a href="trash.php">
                🗑️ Trash
            </a>

        <?php endif; ?>

    </div>

</div>
    
<div class="main-content">

<h1>📩 Mail Center</h1>

<div class="top-menu">

    <?php if($role === 'admin'): ?>

        <a href="?tab=requests"
           class="action-btn green">
           🧾 Requests
        </a>

        <a href="?tab=support"
           class="action-btn blue">
           💬 User Chat
        </a>

    <?php endif; ?>

    <a href="?tab=notifications&filter=all"
       class="action-btn blue">
       🔔 All
    </a>

    <a href="?tab=notifications&filter=unread"
       class="action-btn blue">
       📩 Unread
    </a>

    <a href="?tab=notifications&filter=today"
       class="action-btn blue">
       📅 Today
    </a>
    
    <a href="?tab=sent"
       class="action-btn blue">
       📤 Sent History
    </a>

</div>

<!-- REQUESTS -->

<?php if(($tab === 'requests' || $tab === 'notifications') && $role === 'admin'): ?>

    <form
    method="POST"
    action="delete_all_requests.php"
    onsubmit="return confirm('Hapus semua request?')">

        <button
        class="action-btn red"
        style="border:none;cursor:pointer;">

            🗑 Delete All Requests

        </button>

    </form>

    <br><br>

    <?php if($requestsResult->num_rows > 0): ?>

        <?php while($r = $requestsResult->fetch_assoc()): ?>

            <div class="card">

                <h3>
                    <?= htmlspecialchars($r['username']) ?>
                </h3>

                <p>
                    <?= htmlspecialchars($r['message']) ?>
                </p>

                <div class="badge">
                    Status: <?= htmlspecialchars($r['status']) ?>
                </div>

                <?php if($r['status'] === 'pending'): ?>

                    <br><br>

                    <a class="action-btn green"
                       href="approve_request.php?id=<?= $r['id'] ?>">
                       ✅ Approve
                    </a>

                    <a class="action-btn red"
                       href="reject_request.php?id=<?= $r['id'] ?>">
                       ❌ Reject
                    </a>

                <?php endif; ?>
                
                	<a
                    href="delete_request.php?id=<?= $r['id'] ?>"
                    class="action-btn red"
                    onclick="return confirm('Delete request ini?')">

                        🗑 Delete

                    </a>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="card empty">
            No requests found..
        </div>

    <?php endif; ?>

<?php endif; ?>
    
<!-- SUPPORT CHAT -->

<?php if(($tab === 'support' || $tab === 'notifications') && $role === 'admin'): ?>

    <?php if($supportQuery && $supportQuery->num_rows > 0): ?>

        <?php while($msg = $supportQuery->fetch_assoc()): ?>

            <div class="card" style="margin-bottom:20px;">

                <h3>
                    💬 <?= htmlspecialchars($msg['subject']) ?>
                </h3>

                <p>
                    👤 From:
                    <b>
                        <?= htmlspecialchars($msg['username']) ?>
                    </b>

                    (#<?= $msg['sender_id'] ?>)
                </p>

                <br>

                <p>
                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                </p>

                <br>

                <div class="badge">
                    📌 Status:
                    <?= strtoupper($msg['status']) ?>
                </div>

                <br><br>

                <?php if($msg['status'] === 'pending'): ?>

                    <form
                    action="reply_support.php"
                    method="POST">

                        <input
                        type="hidden"
                        name="id"
                        value="<?= $msg['id'] ?>">

                        <textarea
                        name="reply"
                        required

                        style="
                        width:100%;
                        height:120px;
                        padding:15px;
                        border:none;
                        border-radius:16px;
                        margin-bottom:15px;
                        resize:none;
                        background:rgba(255,255,255,0.08);
                        color:white;
                        "></textarea>

                        <button
                        type="submit"
                        class="action-btn green"
                        style="border:none;cursor:pointer;">

                            ✅ Reply

                        </button>

                        <a
                        href="reject_support.php?id=<?= $msg['id'] ?>"
                        class="action-btn red"
                        onclick="return confirm('Reject message?')">

                            ❌ Reject

                        </a>
                        
                        <a
                        href="delete_support.php?id=<?= $msg['id'] ?>"
                        class="action-btn red"
                        style="margin-left:8px;"
                        onclick="return confirm('Delete support message?')">

                            🗑 Delete

                        </a>

                    </form>

               <?php else: ?>

                    <p style="margin-bottom:10px;">
                        💬 Admin Reply:
                    </p>

                    <div class="card">
                        <?= nl2br(htmlspecialchars($msg['admin_reply'])) ?>
                    </div>

                    <br>

                    <a
                    href="delete_support.php?id=<?= $msg['id'] ?>"
                    class="action-btn red"
                    onclick="return confirm('Delete support message?')">

                        🗑 Delete

                    </a>

                <?php endif; ?>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="card empty">
            No support chat found.
        </div>

    <?php endif; ?>

<?php endif; ?>
    
<!-- SENT HISTORY -->

<?php if($tab === 'sent' && $sentResult): ?>

   <form
    method="POST"
    action="delete_all_sent.php"
    onsubmit="return confirm('Hapus semua history notif?')">

        <?php if($role === 'admin'): ?>

            <label style="
                display:flex;
                align-items:center;
                gap:8px;
                margin-bottom:12px;
                opacity:.85;
            ">

                <input
                type="checkbox"
                name="delete_receiver"
                value="1">

                Hapus untuk penerima juga

            </label>

        <?php endif; ?>

        <button
        class="action-btn red"
        style="border:none;cursor:pointer;">

            🗑 Delete All

        </button>

    </form>

    <br><br>

    <?php if($sentResult->num_rows > 0): ?>

        <?php while($s = $sentResult->fetch_assoc()): ?>

            <div class="card">
                
                <?php if($s['type'] === 'support'): ?>

                    <div class="badge" style="margin-bottom:12px;">
                        💬 Support Message
                    </div>

                <?php else: ?>

                    <div class="badge" style="margin-bottom:12px;">
                        📩 Notification
                    </div>

                <?php endif; ?>

                <h3>
                    <?= htmlspecialchars($s['title']) ?>
                </h3>

                <p>
                    <?= htmlspecialchars($s['message']) ?>
                </p>

                <br>

                <small>
                    <?= date('d M Y H:i', strtotime($s['created_at'])) ?>
                </small>

                <br><br>

                <?php
                $formAction =
                ($s['type'] === 'support')
                ? 'delete_support.php'
                : 'delete_sent.php';
                ?>

                <form
                method="POST"
                action="<?= $formAction ?>"
                onsubmit="return confirm('Hapus item ini?')">

                    <input
                    type="hidden"
                    name="id"
                    value="<?= $s['id'] ?>">

                    <?php if($role === 'admin' && $s['type'] !== 'support'): ?>

                    <label style="
                        display:flex;
                        align-items:center;
                        gap:8px;
                        margin-bottom:12px;
                        opacity:.85;
                    ">

                        <input
                        type="checkbox"
                        name="delete_receiver"
                        value="1">

                        Hapus untuk penerima juga

                    </label>

                <?php endif; ?>

                    <button
                    class="action-btn red"
                    style="border:none;cursor:pointer;">

                        ❌ Delete

                    </button>

                </form>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="card empty">
            No notification history.
        </div>

    <?php endif; ?>

<?php endif; ?>


<!-- NOTIFICATIONS -->

<?php if($tab === 'notifications'): ?>

    <?php if($notifResult->num_rows > 0): ?>

        <?php while($r = $notifResult->fetch_assoc()): ?>

            <div class="card">

                <h3>
                    <?= htmlspecialchars($r['sender_name'] ?? 'System') ?>
                </h3>

               <?php

               $isAdminReply =
                strpos($r['title'], 'Admin Replied') !== false
                ||
                strpos($r['title'], 'Admin Rejected') !== false;

                if(!$isAdminReply):

                ?>

                <p>
                    <?= nl2br(htmlspecialchars($r['message'])) ?>
                </p>

                <?php endif; ?>

                <?php

                $isAdminReply =
                strpos($r['title'], 'Admin Replied') !== false;

                if($isAdminReply || strpos($r['title'], 'Rejected') !== false){

                   $supportStmt = $conn->prepare("
                        SELECT
                            subject,
                            message,
                            admin_reply
                        FROM support_messages
                        WHERE sender_id = ?
                        AND (
                            admin_reply IS NOT NULL
                            OR status = 'rejected'
                        )
                        ORDER BY created_at DESC
                        LIMIT 1
                    ");

                    $supportStmt->bind_param(
                        "i",
                        $userId
                    );

                    $supportStmt->execute();

                    $supportData =
                    $supportStmt
                    ->get_result()
                    ->fetch_assoc();

                    if($supportData):

                ?>

                    <div style="
                        margin-top:18px;
                        padding:18px;
                        border-radius:18px;
                        background:rgba(255,255,255,0.05);
                        border:1px solid rgba(255,255,255,0.08);
                    ">

                        <div style="
                            margin-bottom:14px;
                        ">

                            <div style="
                                opacity:.7;
                                font-size:13px;
                            ">
                                🧑 Your Question
                            </div>

                            <div style="
                                line-height:1.7;
                            ">

                                <b>
                                    <?= htmlspecialchars($supportData['subject']) ?>
                                </b>

                                <br><br>

                                <?= nl2br(htmlspecialchars($supportData['message'])) ?>

                            </div>

                        </div>

                        <div style="
                            margin-top:16px;
                            padding-top:16px;
                            border-top:1px solid rgba(255,255,255,0.08);
                        ">

                            <div style="
                                opacity:.7;
                                margin-bottom:6px;
                                font-size:13px;
                            ">
                                🛡️ Admin Reply
                            </div>

                            <div style="
                                line-height:1.7;
                            ">

                                <?= nl2br(htmlspecialchars($supportData['admin_reply'])) ?>

                            </div>

                        </div>

                    </div>

                <?php endif; } ?>

                <br>

                <small>
                    <?= date('d M Y H:i', strtotime($r['created_at'])) ?>
                </small>
                
                <br><br>

                <a href="delete_notification.php?id=<?= $r['id'] ?>"
                   class="action-btn red"
                   onclick="return confirm('Hapus notif ini?')">
                   🗑 Delete
                </a>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="card empty">
            No notifications found.
        </div>

    <?php endif; ?>

<?php endif; ?>
    
</div>
</div>

</body>
</html>