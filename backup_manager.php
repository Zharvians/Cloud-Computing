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

$backupRoot = __DIR__ . "/backup";

$backups = [];

if(is_dir($backupRoot)){

    foreach(scandir($backupRoot) as $folder){

        if($folder == "." || $folder == ".."){
            continue;
        }

        if(is_dir($backupRoot . "/" . $folder)){
            $backups[] = $folder;
        }

    }

    rsort($backups);
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Backup Manager</title>
<link rel="stylesheet" href="responsive.css">
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
        #a31616,
        #c52222
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

.backup-card{
    background:
    linear-gradient(
        145deg,
        rgba(15,23,42,.95),
        rgba(30,41,59,.95)
    );

    border:1px solid rgba(255,255,255,.08);

    border-radius:28px;

    padding:25px;

    text-align:center;

    transition:.35s;

    backdrop-filter:blur(25px);

    position:relative;

    overflow:hidden;
}

.backup-card:hover{
    transform:translateY(-8px);

    box-shadow:
    0 20px 45px rgba(59,130,246,.25);
}

.backup-icon{
    font-size:70px;
    margin-bottom:15px;
}

.backup-name{
    font-size:18px;
    font-weight:700;
    margin-bottom:12px;
}

.backup-info{
    color:#94a3b8;
    margin-bottom:8px;
    font-size:14px;
}

.backup-actions{
    display:flex;
    gap:10px;
    margin-top:20px;
}

.download-btn,
.restore-btn{
    flex:1;
    text-decoration:none;
    color:white;
    padding:12px;
    border-radius:14px;
    font-weight:600;
}

.download-btn{
    background:
    linear-gradient(
        135deg,
        #3b82f6,
        #2563eb
    );
}

.restore-btn{
    background:
    linear-gradient(
        135deg,
        #db0e0e,
        #821409
    );
}

.delete-btn{
    display:block;

    margin-top:12px;

    text-decoration:none;

    color:white;

    padding:12px;

    border-radius:14px;

    background:
    linear-gradient(
        135deg,
        #ef4444,
        #dc2626
    );
}

.empty-state{
    text-align:center;
    margin-top:100px;
}

.empty-icon{
    font-size:100px;
    margin-bottom:20px;
}

</style>

</head>
<body>

<div class="layout">

    <aside class="sidebar">

        <h2>☁ Lunar Cloud</h2>

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

    </aside>

    <main class="main-content">

        <h1>📦 Backup Manager</h1>

        <?php if(count($backups)): ?>

        <div class="grid">

            <?php foreach($backups as $backup):

                $path = $backupRoot . "/" . $backup;

                $size = 0;

                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path)
                );

                foreach($iterator as $file){
                    if($file->isFile()){
                        $size += $file->getSize();
                    }
                }

                $sizeFormatted =
                    $size > 1048576
                    ? round($size/1048576,2)." MB"
                    : round($size/1024,2)." KB";

            ?>

            <div class="backup-card">

                <div class="backup-icon">
                    📦
                </div>

                <div class="backup-name">
                    <?= htmlspecialchars($backup) ?>
                </div>

                <div class="backup-info">
                    <span>💾 <?= $sizeFormatted ?></span>
                </div>

                <div class="backup-info">
                    <span>
                        🕒
                        <?= date(
                            "d M Y H:i",
                            filemtime($path)
                        ) ?>
                    </span>
                </div>

                <div class="backup-actions">

                    <a
                    href="delete_backup.php?folder=<?= urlencode($backup) ?>"
                    onclick="return confirm('Hapus backup ini?')"
                    class="restore-btn">
                    🗑 Hapus Backup
                    </a>

                    <a
                    href="download_backup.php?folder=<?= urlencode($backup) ?>"
                    class="download-btn">
                    📥 Download ZIP
                    </a>

                </div>

            </div>

            <?php endforeach; ?>

        </div>

        <?php else: ?>

            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h2>Belum Ada Backup</h2>
                <p>Backup yang dibuat akan muncul di sini.</p>
            </div>

        <?php endif; ?>

    </main>

</div>

</body>
</html>