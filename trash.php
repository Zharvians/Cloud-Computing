<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

if($user['role'] === 'viewer'){
    die("Akses ditolak");
}

/* ========================= */
/* AMBIL FILE SAMPAH */
/* ========================= */

if($user['role'] === 'admin'){

    $result = $conn->query("
        SELECT files.*, users.username
        FROM files
        JOIN users
        ON files.user_id = users.id
        WHERE files.is_deleted = 1
        ORDER BY files.uploaded_at DESC
    ");

}else{

    $stmt = $conn->prepare("
        SELECT files.*, users.username
        FROM files
        JOIN users
        ON files.user_id = users.id
        WHERE files.user_id = ?
        AND files.is_deleted = 1
        ORDER BY files.uploaded_at DESC
    ");

    $stmt->bind_param("i", $user['id']);
    $stmt->execute();

    $result = $stmt->get_result();
}

function formatFileSize($bytes){

    if($bytes >= 1048576){
        return round($bytes / 1048576, 2)." MB";
    }

    if($bytes >= 1024){
        return round($bytes / 1024, 2)." KB";
    }

    return $bytes." B";
}

function isImage($name){

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    return in_array($ext, [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp'
    ]);
}

?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Trash</title>

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
    margin-bottom:30px;

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

/* GRID */

.grid{
    display:grid;

    grid-template-columns:
    repeat(auto-fill,minmax(240px,1fr));

    gap:22px;
}

/* FILE CARD */

.file{
    background:rgba(255,255,255,0.06);

    border-radius:24px;

    padding:18px;

    transition:0.3s;

    border:1px solid rgba(255,255,255,0.06);

    backdrop-filter:blur(25px);

    box-shadow:
    0 8px 30px rgba(0,0,0,0.25);
}

.file:hover{
    transform:
    translateY(-6px);

    box-shadow:
    0 15px 45px rgba(37,99,235,0.25);
}

/* PREVIEW */

.preview{
    width:100%;
    aspect-ratio:1/1;

    border-radius:18px;

    overflow:hidden;

    margin-bottom:15px;

    background:
    linear-gradient(
        135deg,
        rgba(255,255,255,0.08),
        rgba(255,255,255,0.03)
    );
}

.preview img{
    width:100%;
    height:100%;
    object-fit:cover;
}

/* DOC */

.doc-preview{
    width:100%;
    height:100%;

    display:flex;
    justify-content:center;
    align-items:center;

    font-size:65px;
}

/* TEXT */

.file-name{
    font-size:15px;
    font-weight:600;

    word-break:break-all;

    margin-bottom:8px;
}

.file-meta{
    font-size:13px;
    opacity:0.7;

    margin-bottom:5px;
}

/* ACTIONS */

.actions{
    margin-top:18px;

    display:flex;
    gap:10px;
}

.btn{
    flex:1;

    text-align:center;

    padding:12px;

    border-radius:14px;

    text-decoration:none;

    color:white;

    font-size:14px;

    transition:0.25s;
}

.btn:hover{
    transform:translateY(-2px);
}

.restore{
    background:
    linear-gradient(
        135deg,
        #16a34a,
        #22c55e
    );
}

.delete{
    background:
    linear-gradient(
        135deg,
        #dc2626,
        #ef4444
    );
}

/* EMPTY */

.empty{
    margin-top:40px;

    text-align:center;

    opacity:0.7;

    font-size:18px;
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

<h1>🗑️ Trash Files</h1>

<a href="index.php" class="back-btn">
    ← Kembali ke Dashboard
</a>

<div class="grid">

<?php while($file = $result->fetch_assoc()): ?>

<?php

/* skip kalau file fisik hilang */

if(
    empty($file['trash_name']) ||
    !file_exists(__DIR__ . "/trash/" . $file['trash_name'])
){
    continue;
}

?>

<div class="file">

    <div class="preview">

        <?php if(isImage($file['name'])): ?>

            <img
				src="trash/<?= htmlspecialchars($file['trash_name']) ?>">

        <?php else: ?>

            <div class="doc-preview">
                📄
            </div>

        <?php endif; ?>

    </div>

    <div class="file-name">
        <?= htmlspecialchars($file['name']) ?>
    </div>

    <div class="file-meta">
        💾 <?= formatFileSize($file['size']) ?>
    </div>

    <div class="file-meta">
        👤 <?= htmlspecialchars($file['username']) ?>
    </div>

    <div class="file-meta">
        📅 <?= date("d M Y", strtotime($file['uploaded_at'])) ?>
    </div>

    <div class="actions">

        <a
            class="btn restore"
            href="restore.php?file=<?= urlencode($file['trash_name']) ?>">

                Restore

        </a>

        <?php if($user['role'] === 'admin'): ?>

        <a
        class="btn delete"
        href="permanent_delete.php?id=<?= $file['id'] ?>"
        onclick="return confirm('Hapus permanen?')">

            Delete

        </a>

        <?php endif; ?>

    </div>

</div>

<?php endwhile; ?>

</div>

<?php if(!$result || $result->num_rows <= 0): ?>

<div class="empty">
    Tidak ada file di sampah 🗑️
</div>

<?php endif; ?>

</body>
</html>