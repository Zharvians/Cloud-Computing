<?php

require 'config.php';

if($_SESSION['user']['role'] !== 'admin'){
    header("Location:index.php");
    exit;
}

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

if($user['role'] === 'viewer'){
    header("Location:index.php");
    exit;
}

$result = $conn->query("
SELECT id, username, password, role
FROM users
ORDER BY id DESC
");

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manage Users</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{
    min-height:100vh;
    padding:35px;
    color:white;
    overflow-x:auto;

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

/* stars */

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
        rgba(15,23,42,0.96) 0%,
        rgba(17,24,39,0.94) 45%,
        rgba(2,6,23,0.98) 100%
    );

    border-right:
    1px solid rgba(255,255,255,0.08);

    backdrop-filter:blur(24px);

    box-shadow:
    0 0 40px rgba(0,0,0,0.35),
    0 0 25px rgba(59,130,246,0.08);

    display:flex;
    flex-direction:column;

    overflow:hidden;

    z-index:100;
}

.sidebar::before{
    content:"";

    position:absolute;
    inset:0;

    background:
    radial-gradient(
        circle at top left,
        rgba(59,130,246,0.16),
        transparent 35%
    ),

    radial-gradient(
        circle at bottom right,
        rgba(168,85,247,0.14),
        transparent 40%
    );

    pointer-events:none;
}

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

.menu{
    position:relative;

    flex:1;

    display:flex;
    flex-direction:column;
    justify-content:center;

    gap:14px;

    z-index:2;
}

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
    transform:translateX(6px);

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
/* MAIN CONTENT */
/* ========================= */

.main-content{
    margin-left:280px;
    width:calc(100% - 280px);

    padding:35px;
}

/* topbar */

.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
    gap:20px;
    flex-wrap:wrap;
}

.title{
    font-size:38px;
    font-weight:800;

    background:linear-gradient(
        90deg,
        #ffffff,
        #60a5fa,
        #c084fc
    );

    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

/* back button */

.back{
    text-decoration:none;
    color:white;

    padding:14px 22px;

    border-radius:18px;

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

.back:hover{
    transform:translateY(-3px);

    box-shadow:
    0 10px 25px rgba(59,130,246,0.25);
}

/* search */

.search{
    width:100%;

    padding:18px 22px;

    border:none;
    outline:none;

    border-radius:22px;

    font-size:15px;

    margin-bottom:25px;

    background:rgba(255,255,255,0.08);

    color:white;

    border:1px solid rgba(255,255,255,0.08);

    backdrop-filter:blur(20px);

    transition:0.3s;
}

.search:focus{
    transform:scale(1.01);

    box-shadow:
    0 0 30px rgba(59,130,246,0.25);
}

.search::placeholder{
    color:rgba(255,255,255,0.6);
}

/* container */

.container{
    background:rgba(255,255,255,0.06);

    border:1px solid rgba(255,255,255,0.08);

    backdrop-filter:blur(25px);

    border-radius:28px;

    overflow:hidden;

    box-shadow:
    0 10px 40px rgba(0,0,0,0.25);
}

/* table */

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:rgba(255,255,255,0.08);

    padding:20px;

    text-align:left;

    font-size:14px;

    letter-spacing:1px;
}

td{
    padding:20px;

    border-top:
    1px solid rgba(255,255,255,0.06);

    font-size:14px;
}

tr{
    transition:0.25s;
}

tr:hover{
    background:rgba(255,255,255,0.04);
}

/* role badge */

.role{
    padding:8px 14px;

    border-radius:999px;

    font-size:12px;

    display:inline-block;

    font-weight:700;
}

.admin{
    background:
    linear-gradient(
        135deg,
        #dc2626,
        #ef4444
    );
}

.user{
    background:
    linear-gradient(
        135deg,
        #2563eb,
        #3b82f6
    );
}

.viewer{
    background:
    linear-gradient(
        135deg,
        #16a34a,
        #22c55e
    );
}

/* actions */

.actions{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
}

.actions a{
    text-decoration:none;

    color:white;

    padding:10px 14px;

    border-radius:14px;

    font-size:12px;

    transition:0.25s;

    font-weight:600;

    border:1px solid rgba(255,255,255,0.08);

    backdrop-filter:blur(18px);

    box-shadow:
    0 4px 18px rgba(0,0,0,0.22);
}

.actions a:hover{
    transform:translateY(-2px);
}

/* buttons */

.edit{
    background:
    linear-gradient(
        135deg,
        #1d4ed8,
        #2563eb
    );
}

.viewerbtn{
    background:
    linear-gradient(
        135deg,
        #15803d,
        #16a34a
    );
}

.adminbtn{
    background:
    linear-gradient(
        135deg,
        #b91c1c,
        #dc2626
    );
}

.delete{
    background:
    linear-gradient(
        135deg,
        #991b1b,
        #ef4444
    );
}
    
.edit:hover,
.viewerbtn:hover,
.adminbtn:hover,
.delete:hover{
    transform:translateY(-3px);

    box-shadow:
    0 10px 25px rgba(59,130,246,0.22);
}

/* password */

.password{
    max-width:240px;

    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;

    opacity:0.75;

    font-size:12px;
}

/* scrollbar */

::-webkit-scrollbar{
    height:10px;
    width:10px;
}

::-webkit-scrollbar-thumb{
    background:
    linear-gradient(
        #3b82f6,
        #9333ea
    );

    border-radius:20px;
}

</style>
</head>

<div class="layout">

<!-- Sidebar -->
<div class="sidebar">

    <div>
        <h2>☁️ Lunar Cloud</h2>
    </div>

    <div class="menu">

        <?php if($user['role'] === 'admin'): ?>
            <a href="manage_users.php">
                👥 Manage Users
            </a>
        <?php endif; ?>

        <a href="backup_manager.php">
            📦 Backup Manager
        </a>

        <a href="mail.php">
            📩 Mail
        </a>

        <a href="index.php">
            📁 File Manager
        </a>

        <a href="upload_page.php">
            📤 Upload
        </a>

        <a href="trash.php">
            🗑️ Trash
        </a>

    </div>

</div>

<div class="main-content">

<div class="topbar">

    <div class="title">
        👥 Manage Users
    </div>

</div>

<input
type="text"
class="search"
id="searchInput"
placeholder="Search username...">

<div class="container">

<table id="userTable">

<tr>
<th>ID</th>
<th>Username</th>
<th>Password</th>
<th>Role</th>
<th>Action</th>
</tr>

<?php while($u = $result->fetch_assoc()): ?>

<tr>

<td>
<?= $u['id'] ?>
</td>

<td>
<?= htmlspecialchars($u['username']) ?>
</td>

<td class="password">
<?= htmlspecialchars($u['password']) ?>
</td>

<td>

<span class="role <?= $u['role'] ?>">
<?= $u['role'] ?>
</span>

</td>

<td>

<div class="actions">

<a class="edit"
href="change_role.php?id=<?= $u['id'] ?>&role=user">
User
</a>

<a class="viewerbtn"
href="change_role.php?id=<?= $u['id'] ?>&role=viewer">
Viewer
</a>

<a class="adminbtn"
href="change_role.php?id=<?= $u['id'] ?>&role=admin">
Admin
</a>

<a class="edit"
href="edit_user.php?id=<?= $u['id'] ?>">
Edit
</a>

<a class="delete"
href="delete_user.php?id=<?= $u['id'] ?>"
onclick="return confirm('Hapus user ini?')">
Delete
</a>

</div>

</td>

</tr>

<?php endwhile; ?>

</table>

</div>

<script>

const searchInput =
document.getElementById("searchInput");

searchInput.addEventListener("keyup", function(){

    let value =
    this.value.toLowerCase();

    let rows =
    document.querySelectorAll("#userTable tr");

    rows.forEach((row,index)=>{

        if(index === 0) return;

        let text =
        row.innerText.toLowerCase();

        row.style.display =
        text.includes(value)
        ? ""
        : "none";

    });

});

</script>
    
</div>
</div>

</body>
</html>