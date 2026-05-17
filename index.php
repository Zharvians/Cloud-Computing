<?php

require 'config.php';

if(!isset($_SESSION['user'])){
    header("Location:login.php");
    exit;
}

$currentFolder = isset($_GET['folder'])
    ? (int)$_GET['folder']
    : 0;

$user = $_SESSION['user'];
$status = $_GET['status'] ?? '';

$uploadDir = "uploads/";
$files = [];

if($user['role'] === 'admin'){

    $result = $conn->query("
        SELECT files.*, users.username, users.role AS uploader_role
        FROM files
        JOIN users
        ON files.user_id = users.id
        WHERE is_deleted = 0
        AND (
            folder_id = $currentFolder
            OR (folder_id IS NULL AND $currentFolder = 0)
        )
        ORDER BY uploaded_at DESC
    ");

}elseif($user['role'] === 'viewer'){

    $result = $conn->query("
        SELECT files.*, users.username, users.role AS uploader_role
        FROM files
        JOIN users
        ON files.user_id = users.id
        WHERE is_public = 1
        AND is_deleted = 0
        AND (
            folder_id = $currentFolder
            OR (folder_id IS NULL AND $currentFolder = 0)
        )
        ORDER BY uploaded_at DESC
    ");

}else{

    $stmt = $conn->prepare("
        SELECT files.*, users.username
        FROM files
        JOIN users ON files.user_id = users.id
        WHERE files.user_id=?
        AND is_deleted = 0
        AND (
            folder_id = ?
            OR (folder_id IS NULL AND ? = 0)
        )
        ORDER BY uploaded_at DESC
    ");

    $stmt->bind_param(
        "iii",
        $user['id'],
        $currentFolder,
        $currentFolder
    );

    $stmt->execute();

    $result = $stmt->get_result();
}

while ($row = $result->fetch_assoc()) {

    $files[] = [
        'id' => $row['id'],
        'user_id' => $row['user_id'],
        'name' => $row['name'],
        'size' => $row['size'],
        'modified' => strtotime($row['uploaded_at']),
        'username' => $row['username'] ?? 'Unknown',
        'uploader_role' => $row['uploader_role'] ?? '-',
        'is_public' => $row['is_public'] ?? 0,

        'is_favorite' => $row['is_favorite'] ?? 0
    ];
}

/* ========================= */
/* 🔔 NOTIFICATION SYSTEM */
/* ========================= */

$notifQuery = $conn->prepare("
    SELECT
        notifications.*,
        users.username AS sender_name
    FROM notifications
    JOIN users
    ON notifications.sender_id = users.id

    WHERE
    (
        target_role = 'all'
        OR target_role = ?
        OR target_user_id = ?
    )
    AND sender_id != ?

    AND is_read = 0

    ORDER BY created_at DESC
    LIMIT 1
");

$notifQuery->bind_param(
    "sii",
    $user['role'],
    $user['id'],
    $user['id']
);

$notifQuery->execute();

$latestNotif =
$notifQuery
->get_result()
->fetch_assoc();

if($latestNotif){

    $readStmt = $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE id = ?
    ");

    $readStmt->bind_param(
        "i",
        $latestNotif['id']
    );

    $readStmt->execute();
}

/* ========================= */
/* 📂 TOTAL FOLDER */
/* ========================= */

if($user['role'] === 'admin'){

    $folderCountStmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM folders
    ");

}else{

    $folderCountStmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM folders
        WHERE user_id = ?
    ");

    $folderCountStmt->bind_param(
        "i",
        $user['id']
    );
}

$folderCountStmt->execute();

$totalFolders =
$folderCountStmt
->get_result()
->fetch_assoc()['total'] ?? 0;

$totalSize = 0;
$totalImages = 0;
$totalDocs = 0;

/* ========================= */
/* 💾 TOTAL STORAGE */
/* ========================= */

if($user['role'] === 'admin'){

    $storageStmt = $conn->prepare("
        SELECT COALESCE(SUM(size),0) as total
        FROM files
        WHERE is_deleted = 0
    ");

}else{

    $storageStmt = $conn->prepare("
        SELECT COALESCE(SUM(size),0) as total
        FROM files
        WHERE user_id = ?
        AND is_deleted = 0
    ");

    $storageStmt->bind_param(
        "i",
        $user['id']
    );
}

$storageStmt->execute();

$totalSize =
$storageStmt
->get_result()
->fetch_assoc()['total'] ?? 0;

/* ========================= */
/* 📊 STORAGE STATS */
/* ========================= */

foreach($files as $f){

    $totalSize += $f['size'];

    if(isImage($f['name'])){
        $totalImages++;
    }else{
        $totalDocs++;
    }
}

function isImage($name) {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg','jpeg','png','gif']);
}

function formatFileSize(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . " MB";
    if ($bytes >= 1024) return round($bytes / 1024, 2) . " KB";
    return $bytes . " B";
}

function fileIcon($name) {

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    switch($ext){

        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
        case 'webp':
            return '🖼️';

        case 'pdf':
            return '📕';

        case 'doc':
        case 'docx':
            return '📝';

        case 'xls':
        case 'xlsx':
            return '📊';

        case 'csv':
            return '📈';

        case 'ppt':
        case 'pptx':
            return '📽️';

        case 'zip':
        case 'rar':
            return '📦';

        case 'mp4':
        case 'webm':
            return '🎥';

        case 'txt':
            return '📄';

        default:
            return '📁';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lunar Cloud Storage</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{
    min-height:100vh;
    color:white;
    overflow-x:hidden;
    transition:0.5s;
    position:relative;

    background:
    linear-gradient(to bottom,
    #87ceeb 0%,
    #4facfe 35%,
    #38bdf8 60%,
    #0ea5e9 100%);
}

/* ===== BACKGROUND EFFECT ===== */

body::before{
    content:"";
    position:fixed;
    inset:0;
    background:
    radial-gradient(circle at top,
    rgba(255,255,255,0.35),
    transparent 40%);
    pointer-events:none;
    z-index:-2;
}

/* Ombak laut */

body::after{
    content:"";
    position:fixed;
    bottom:-120px;
    left:0;
    width:200%;
    height:300px;
    background:
    rgba(255,255,255,0.08);
    border-radius:45%;
    animation:wave 12s linear infinite;
    z-index:-1;
}

@keyframes wave{
    0%{
        transform:translateX(0);
    }
    100%{
        transform:translateX(-50%);
    }
}
    
@keyframes auroraMove{
    0%{
        background-position:0% 50%;
    }

    100%{
        background-position:100% 50%;
    }
}

/* ===== MODE GELAP ===== */

body{
    background:
    linear-gradient(to bottom,
    #87ceeb 0%,
    #4facfe 35%,
    #38bdf8 60%,
    #0ea5e9 100%);
    color:#ffffff;
}

/* Aurora Night */

body.dark-mode{
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
    animation: auroraMove 10s infinite alternate;
}

/* Bintang */

.stars{
    position:fixed;
    opacity:0;
    transition:0.5s;
    inset:0;
    overflow:hidden;
    z-index:-1;
}

.star{
    position:absolute;
    width:3px;
    height:3px;
    background:white;
    border-radius:50%;
    opacity:0.8;
    animation:blink 2s infinite alternate;
}
    
body.dark-mode .stars{
    opacity:1;
}

@keyframes blink{
    from{
        opacity:0.2;
    }
    to{
        opacity:1;
    }
}

/* Card Mewah */

.sidebar,
.card,
.file,
.drawer{
    background:rgba(255,255,255,0.08);
    border:1px solid rgba(255,255,255,0.08);
    box-shadow:
    0 8px 30px rgba(0,0,0,0.25);
}

/* Glow */

.file:hover,
.card:hover{
    box-shadow:
    0 0 25px rgba(56,189,248,0.35);
}

/* Topbar */

.topbar h1{
    font-size:32px;
    font-weight:800;
    letter-spacing:1px;
    background:linear-gradient(90deg,#fff,#7dd3fc);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

/* Upload */

button{
    background:linear-gradient(135deg,#38bdf8,#2563eb);
    box-shadow:0 0 20px rgba(59,130,246,0.4);
}

button:hover{
    transform:translateY(-2px);
}

/* Sidebar Hover */

.menu a:hover{
    background:rgba(255,255,255,0.15);
    transform:translateX(5px);
}

/* Layout */
.wrapper {
    display: grid;
    grid-template-columns: 240px 1fr;
    min-height: 100vh;
}

/* Sidebar */
.sidebar{
    background:rgba(255,255,255,0.18);
    background:rgba(255,255,255,0.04);
    backdrop-filter:blur(25px);
    padding:25px;
    border-right:1px solid rgba(255,255,255,0.08);
    box-shadow:
    0 0 40px rgba(59,130,246,0.08);

    display:flex;
    flex-direction:column;
    justify-content:space-between;
}
    
.sidebar-bottom{
    display:flex;
    flex-direction:column;
    gap:12px;
}

.sidebar-bottom button{
    width:100%;
    padding:14px;
    border:none;
    border-radius:16px;
    cursor:pointer;

    background:rgba(255,255,255,0.12);

    color:inherit;

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

.sidebar h2 {
    margin-bottom: 20px;
}

.menu a{
    display:flex;
    align-items:center;
    gap:12px;
    margin-top:10px;

    padding:14px;
    margin-bottom:12px;

    border-radius:18px;

    color:white;
    text-decoration:none;

    transition:0.3s;

    background:rgba(255,255,255,0.03);
}
    
.dropdown-wrapper{
    margin-top:12px;
}

.files-row{
    display:flex;
    align-items:center;
    gap:8px;
}

.files-btn{
    flex:1;
    display:flex;
    align-items:center;

    padding:14px;

    border-radius:18px;

    text-decoration:none;

    color:inherit;

    background:rgba(255,255,255,0.15);

    transition:0.3s;
}

.files-btn:hover{
    transform:translateX(5px);

    background:
    linear-gradient(
        135deg,
        rgba(59,130,246,0.25),
        rgba(147,51,234,0.25)
    );
}

.arrow-btn{
    width:55px;
    height:52px;

    border:none;
    border-radius:18px;

    cursor:pointer;

    background:rgba(255,255,255,0.15);

    color:inherit;

    font-size:20px;

    transition:0.3s;
}

.arrow-btn:hover{
    background:
    linear-gradient(
        135deg,
        rgba(59,130,246,0.25),
        rgba(147,51,234,0.25)
    );
}

.dropdown-content{
    display:none;

    flex-direction:column;

    margin-top:10px;

    gap:10px;

    animation:dropdownAnim 0.25s ease;
}

.dropdown-content.active{
    display:flex;
}

.dropdown-content a{
    padding:14px;

    border-radius:16px;

    text-decoration:none;

    color:inherit;

    background:rgba(255,255,255,0.08);

    transition:0.3s;
}

.dropdown-content a:hover{
    transform:translateX(5px);

    background:rgba(255,255,255,0.16);
}

#arrowIcon{
    display:inline-block;
    transition:0.3s;
}

#arrowIcon.rotate{
    transform:rotate(180deg);
}

@keyframes dropdownAnim{
    from{
        opacity:0;
        transform:translateY(-8px);
    }

    to{
        opacity:1;
        transform:translateY(0);
    }
}
    
body .menu a{
    background:rgba(255,255,255,0.15);
}

.menu a:hover{
    transform:translateX(6px);

    background:
    linear-gradient(
        135deg,
        rgba(59,130,246,0.25),
        rgba(147,51,234,0.25)
    );

    box-shadow:
    0 10px 25px rgba(59,130,246,0.18);
}

/* Main */
.main {
    padding: 30px;
}

/* Topbar */
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;

    margin-bottom:30px;

    padding:20px 25px;

    border-radius:24px;

    background:rgba(255,255,255,0.05);

    backdrop-filter:blur(25px);

    border:1px solid rgba(255,255,255,0.06);
}
    
.topbar h1{
    font-size:30px;
    font-weight:700;

    background:linear-gradient(
        90deg,
        #ffffff,
        #60a5fa,
        #c084fc
    );

    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

/* Cards */
.card{
    background:rgba(255,255,255,0.05);
    backdrop-filter:blur(30px);
    border-radius:28px;
    padding:25px;
    margin-bottom:25px;

    border:1px solid rgba(255,255,255,0.08);

    box-shadow:
    0 10px 40px rgba(0,0,0,0.25);

    transition:0.3s;
}

.card:hover{
    transform:translateY(-3px);
}

/* Upload */
.upload {
    display: flex;
    gap: 10px;
}

input[type=file] {
    flex: 1;
    padding: 8px;
    border-radius: 8px;
    border: none;
}

button {
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    background: #3b82f6;
    color: white;
    cursor: pointer;
}

button:hover {
    background: #2563eb;
}

/* Grid Files */
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fill,minmax(180px,1fr));
    gap: 15px;
}

/* File Card */
.file{
    background:rgba(255,255,255,0.06);
    border-radius:24px;
    padding:15px;
    transition:0.3s;
    border:1px solid rgba(255,255,255,0.06);

    backdrop-filter:blur(25px);

    box-shadow:
    0 8px 30px rgba(0,0,0,0.25);

    position:relative;
    overflow:hidden;
}

.file::before{
    content:'';
    position:absolute;
    inset:0;
    background:linear-gradient(
        135deg,
        rgba(255,255,255,0.08),
        transparent
    );

    opacity:0;
    transition:0.3s;
    pointer-events:none;
}

.file:hover::before{
    opacity:1;
}

.file:hover{
    transform:
    translateY(-8px)
    scale(1.02);

    border-color:
    rgba(96,165,250,0.3);

    box-shadow:
    0 15px 50px rgba(37,99,235,0.25);
}

.file-icon {
    font-size: 30px;
    margin-bottom: 10px;
}

.file-name {
    font-size: 14px;
    word-break: break-all;
}

.file-meta {
    font-size: 12px;
    opacity: 0.7;
}

/* Actions */
.actions {
    margin-top: 10px;
	position:relative;
    z-index:5;
}

.actions a {
    text-decoration: none;
    font-size: 12px;
    margin-right: 8px;
}
    
.preview{
    width:100%;
    aspect-ratio:1/1;
    border-radius:18px;
    overflow:hidden;
    margin-bottom:14px;

    background:
    linear-gradient(
        135deg,
        rgba(255,255,255,0.08),
        rgba(255,255,255,0.03)
    );
}

.preview img {
    width: 100%;
    height: 100%;
    object-fit: cover; /* 🔥 biar ga gepeng */
    cursor: pointer;
    transition: 0.2s;

    position:relative;
    z-index:2;
}

.preview img:hover {
    transform: scale(1.05);
}
    
/* ========================= */
/* 📄 DOCUMENT PREVIEW */
/* ========================= */

.file-preview-doc{
    width:100%;
    height:100%;

    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;

    border-radius:18px;

    background:
    linear-gradient(
        135deg,
        rgba(59,130,246,0.18),
        rgba(147,51,234,0.18)
    );

    position:relative;
    overflow:hidden;
}

/* glow */

.file-preview-doc::before{
    content:'';
    position:absolute;
    inset:0;

    background:
    linear-gradient(
        135deg,
        rgba(255,255,255,0.08),
        transparent
    );
}

.doc-icon{
    font-size:58px;
    margin-bottom:10px;

    z-index:2;
}

.doc-ext{
    font-size:20px;
    font-weight:800;
    letter-spacing:2px;

    z-index:2;
}

.doc-label{
    margin-top:6px;

    font-size:11px;
    opacity:0.7;
    letter-spacing:2px;

    z-index:2;
}

.download {color:#60a5fa;}
.delete {color:#f87171;}

.success {background:#16a34a;padding:10px;border-radius:8px;margin-bottom:10px;}
.error {background:#dc2626;padding:10px;border-radius:8px;margin-bottom:10px;}
    
    .top-actions{
    display:flex;
    align-items:center;
    gap:15px;
}

.menu-btn{
    width:50px;
    height:50px;

    border:1px solid rgba(255,255,255,0.35);

    border-radius:18px;

    background:
    linear-gradient(
        135deg,
        rgba(59,130,246,0.45),
        rgba(147,51,234,0.35)
    );

    color:white;
    font-size:22px;

    cursor:pointer;

    backdrop-filter:blur(20px);

    transition:0.3s;
}

.menu-btn:hover{
    transform:rotate(90deg) scale(1.08);

    box-shadow:
    0 10px 30px rgba(59,130,246,0.4);
}

.drawer{
    position:fixed;
    top:0;
    right:-400px;
    width:340px;
    height:100%;
    background:rgba(15,23,42,0.95);
    backdrop-filter:blur(20px);
    transition:0.3s;
    z-index:9999;
    border-left:1px solid rgba(255,255,255,0.08);
}

.drawer.active{
    right:0;
}

.drawer-content{
    padding:30px;
}

.drawer-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
}

.close-btn{
    width:40px;
    height:40px;
    border:none;
    border-radius:12px;
    background:rgba(255,255,255,0.1);
    color:white;
    cursor:pointer;
}

.profile-box{
    background:rgba(255,255,255,0.06);
    border-radius:18px;
    padding:20px;
    margin-bottom:25px;
}
    
    .drawer-link{
    display:block;
    padding:14px;
    border-radius:14px;
    margin-top:12px;
    text-decoration:none;
    color:white;
    background:rgba(255,255,255,0.06);
    transition:0.2s;
}

.drawer-link:hover{
    background:rgba(255,255,255,0.12);
}

.drawer-link-btn{
    width:100%;
    padding:14px;
    border:none;
    border-radius:14px;
    margin-top:12px;
    background:rgba(255,255,255,0.06);
    color:white;
    cursor:pointer;
    text-align:left;
}

.logout-btn{
    background:#ef4444;
}

.profile-name{
    font-size:22px;
    font-weight:bold;
}

.profile-role{
    opacity:0.7;
    margin-top:5px;
}

/* ========================= */
/* ☀️ MODE SIANG */
/* ========================= */

body{
    color:#0f172a;

    background:
    linear-gradient(
        to bottom,
        #c7e9ff 0%,
        #9ed8ff 30%,
        #74c7ff 65%,
        #4baeff 100%
    );
}

/* efek awan soft */

body::before{
    content:"";
    position:fixed;
    inset:0;

    background:
    radial-gradient(circle at top left,
    rgba(255,255,255,0.45),
    transparent 30%),


    radial-gradient(circle at top right,
    rgba(255,255,255,0.35),
    transparent 25%);

    pointer-events:none;
    z-index:-2;
}

/* teks */

body .file-name,
body .file-meta,
body .menu a,
body .drawer-link,
body .profile-role,
body .top-actions,
body .profile-name{
    color:#0f172a;
}

/* glass card siang */

body .sidebar,
body .card,
body .file,
body .drawer,
body .topbar{
    background:rgba(255,255,255,0.22);

    backdrop-filter:blur(25px);

    border:1px solid rgba(255,255,255,0.28);

    box-shadow:
    0 8px 30px rgba(0,0,0,0.08);
}

/* ========================= */
/* 🌌 MODE MALAM */
/* ========================= */

body.dark-mode{
    color:white;
}

/* teks malam */

body.dark-mode .file-name,
body.dark-mode .file-meta,
body.dark-mode .menu a,
body.dark-mode .drawer-link,
body.dark-mode .profile-role,
body.dark-mode .top-actions,
body.dark-mode .profile-name{
    color:white;
}

/* card malam */

body.dark-mode .sidebar,
body.dark-mode .card,
body.dark-mode .file,
body.dark-mode .drawer,
body.dark-mode .topbar{
    background:rgba(255,255,255,0.06);
    border:1px solid rgba(255,255,255,0.08);
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

.grid{
    display:grid;
    grid-template-columns:
    repeat(auto-fill,minmax(220px,1fr));

    gap:22px;
}

.file-name{
    font-size:15px;
    font-weight:600;
    margin-top:10px;
}

.file-meta{
    margin-top:5px;
    opacity:0.72;
}

button{
    transition:0.3s;
}

button:hover{
    transform:translateY(-2px);
}
    
/* ========================= */
/* 🔎 SEARCH BOX */
/* ========================= */

.search-box{
    margin-bottom:25px;
}

.search-box input{
    width:100%;
    padding:18px 22px;

    border:none;
    outline:none;

    border-radius:22px;

    font-size:15px;

    background:rgba(255,255,255,0.18);

    backdrop-filter:blur(20px);

    color:inherit;

    border:1px solid rgba(255,255,255,0.18);

    transition:0.3s;
}

.search-box input:focus{
    transform:scale(1.01);

    box-shadow:
    0 0 30px rgba(59,130,246,0.25);
}

.search-box input::placeholder{
    color:rgba(255,255,255,0.65);
}

body:not(.dark-mode) .search-box input::placeholder{
    color:rgba(15,23,42,0.5);
}

/* ========================= */
/* 📊 STATS */
/* ========================= */

.stats-grid{
    display:grid;

    grid-template-columns:
    repeat(auto-fit,minmax(220px,1fr));

    gap:20px;

    margin-bottom:30px;
}

.stat-card{
    padding:25px;

    border-radius:28px;

    background:rgba(255,255,255,0.12);

    backdrop-filter:blur(25px);

    border:1px solid rgba(255,255,255,0.15);

    transition:0.3s;
}

.stat-card:hover{
    transform:translateY(-5px);

    box-shadow:
    0 0 35px rgba(59,130,246,0.25);
}

.stat-card h3{
    font-size:15px;
    opacity:0.8;
    margin-bottom:12px;
}

.stat-card p{
    font-size:30px;
    font-weight:800;
}
    
/* ========================= */
/* 🖼️ IMAGE MODAL */
/* ========================= */

.img-modal{
    position:fixed;
    inset:0;

    display:none;
    justify-content:center;
    align-items:center;

    background:rgba(0,0,0,0.88);

    backdrop-filter:blur(10px);

    z-index:999999;
}

#modalImg{
    max-width:90%;
    max-height:90%;

    border-radius:24px;

    box-shadow:
    0 0 60px rgba(255,255,255,0.15);

    animation:zoomIn 0.25s ease;
}

@keyframes zoomIn{
    from{
        transform:scale(0.7);
        opacity:0;
    }

    to{
        transform:scale(1);
        opacity:1;
    }
}

.close-modal{
    position:absolute;

    top:25px;
    right:35px;

    font-size:35px;
    color:white;

    cursor:pointer;

    transition:0.2s;
}

.close-modal:hover{
    transform:scale(1.15);
}

.img-modal.active {
    display: flex;
}
   
#loadingScreen{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.6);
    backdrop-filter:blur(20px);
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    z-index:999999;
    color:white;
}

.loader{
    width:60px;
    height:60px;
    border:5px solid rgba(255,255,255,0.2);
    border-top:5px solid #38bdf8;
    border-radius:50%;
    animation:spin 1s linear infinite;
}

@keyframes spin{
    to{transform:rotate(360deg);}
}
    
.quick-action{
    position:fixed;
    bottom:25px;
    left:25px;
    display:flex;
    flex-direction:column;
    gap:10px;
    z-index:9999;
}

.quick-action button{
    width:50px;
    height:50px;
    border-radius:16px;
    border:none;
    cursor:pointer;
    background:rgba(255,255,255,0.15);
    backdrop-filter:blur(20px);
    color:inherit;
    font-size:18px;
    transition:0.3s;
}

.quick-action button:hover{
    transform:scale(1.1);
    background:rgba(59,130,246,0.3);
}
    
.mini-stats{
    margin-top:20px;
    padding:15px;
    border-radius:18px;
    background:rgba(255,255,255,0.08);
    backdrop-filter:blur(20px);
    font-size:13px;
}
    
#toast{
    position:fixed;
    bottom:30px;
    right:30px;
    padding:15px 20px;
    border-radius:14px;
    background:rgba(0,0,0,0.7);
    color:white;
    opacity:0;
    transform:translateY(20px);
    transition:0.4s;
    z-index:99999;
}
    
.list-view{
    display:flex !important;
    flex-direction:column;
}

.list-view .file{
    display:flex;
    align-items:center;
    gap:20px;
}

.list-view .preview{
    width:80px;
    height:80px;
    aspect-ratio:auto;
}
    
/* ========================= */
/* 🎛️ FILTER BAR */
/* ========================= */

.filter-bar{
    display:flex;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
}

.filter-bar select,
.filter-bar input{
    padding:12px 16px;

    border:none;
    outline:none;

    border-radius:16px;

    background:rgba(255,255,255,0.15);

    backdrop-filter:blur(20px);

    color:inherit;

    border:1px solid rgba(255,255,255,0.12);

    transition:0.3s;
}

.filter-bar select:hover,
.filter-bar input:hover{
    transform:translateY(-2px);
}

.filter-bar input::placeholder{
    color:rgba(255,255,255,0.7);
}

body:not(.dark-mode)
.filter-bar input::placeholder{
    color:rgba(15,23,42,0.55);
}

/* ========================= */
/* 🌙 FIX FILTER DARK MODE */
/* ========================= */

body.dark-mode .filter-bar select,
body.dark-mode .filter-bar input{
    background:rgba(15,23,42,0.85);
    color:white;
    border:1px solid rgba(255,255,255,0.12);
}

/* option dropdown */

body.dark-mode .filter-bar select option{
    background:#0f172a;
    color:white;
}

/* mode terang */

body:not(.dark-mode) .filter-bar select,
body:not(.dark-mode) .filter-bar input{
    background:rgba(255,255,255,0.7);
    color:#0f172a;
}

body:not(.dark-mode) .filter-bar select option{
    background:white;
    color:#0f172a;
}

/* ========================= */
/* 📂 FILE FILTER TABS */
/* ========================= */

.file-tabs{
    display:flex;
    gap:14px;
    margin-bottom:25px;
    flex-wrap:wrap;
}

.tab-btn{
    padding:14px 22px;
    border:none;
    border-radius:18px;
    cursor:pointer;

    font-size:14px;
    font-weight:600;

    color:inherit;

    background:rgba(255,255,255,0.12);

    backdrop-filter:blur(20px);

    border:1px solid rgba(255,255,255,0.12);

    transition:0.3s;
}

.tab-btn:hover{
    transform:translateY(-2px);

    background:
    linear-gradient(
        135deg,
        rgba(59,130,246,0.25),
        rgba(147,51,234,0.25)
    );
}

.tab-btn.active{
    background:
    linear-gradient(
        135deg,
        #3b82f6,
        #9333ea
    );

    color:white;

    box-shadow:
    0 0 25px rgba(59,130,246,0.35);
}

/* dark mode */

body.dark-mode .tab-btn{
    background:rgba(255,255,255,0.08);
    color:white;
}

/* light mode */

body:not(.dark-mode) .tab-btn{
    background:rgba(255,255,255,0.55);
    color:#0f172a;
}    
    
.folder-item{
    border:1px solid rgba(255,255,255,0.15);
    background:
    linear-gradient(
        135deg,
        rgba(59,130,246,0.12),
        rgba(147,51,234,0.12)
    );
}

.folder-item .doc-icon{
    font-size:70px;
    filter:drop-shadow(
        0 0 15px rgba(59,130,246,0.45)
    );
}
    
#liveNotif{
    position:fixed;
    top:25px;
    right:25px;
    z-index:999999;
}

.live-card{
    min-width:320px;
    max-width:400px;

    background:rgba(15,23,42,0.95);

    color:white;

    padding:18px;

    border-radius:22px;

    backdrop-filter:blur(25px);

    border:1px solid rgba(255,255,255,0.08);

    box-shadow:
    0 10px 40px rgba(0,0,0,0.35);

    animation:slideNotif 0.4s ease;
    
    pointer-events:auto;
}

.live-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:10px;
}

.close-live{
    cursor:pointer;
    font-size:18px;
    opacity:0.7;
}

.close-live:hover{
    opacity:1;
}

@keyframes slideNotif{

    from{
        opacity:0;
        transform:translateX(100px);
    }

    to{
        opacity:1;
        transform:translateX(0);
    }
}
 
/* ========================= */
/* 📢 NOTIFICATION MODAL FIX */
/* ========================= */

/* INPUT + TEXTAREA + SELECT */

#notifModal input,
#notifModal textarea,
#notifModal select{
    background:rgba(255,255,255,0.12);
    color:inherit;

    border:1px solid rgba(255,255,255,0.08);

    backdrop-filter:blur(20px);

    transition:0.3s;
}

/* OPTION DROPDOWN */

#notifModal select option{
    background:#0f172a;
    color:white;
}

/* PLACEHOLDER */

#notifModal input::placeholder,
#notifModal textarea::placeholder{
    color:rgba(255,255,255,0.65);
}

/* LIGHT MODE */

body:not(.dark-mode) #notifModal input,
body:not(.dark-mode) #notifModal textarea,
body:not(.dark-mode) #notifModal select{
    background:rgba(255,255,255,0.75);
    color:#0f172a;
}

body:not(.dark-mode) #notifModal select option{
    background:white;
    color:#0f172a;
}

body:not(.dark-mode) #notifModal input::placeholder,
body:not(.dark-mode) #notifModal textarea::placeholder{
    color:rgba(15,23,42,0.5);
}

/* FOCUS EFFECT */

#notifModal input:focus,
#notifModal textarea:focus,
#notifModal select:focus{
    outline:none;

    box-shadow:
    0 0 25px rgba(59,130,246,0.25);

    border-color:rgba(96,165,250,0.4);
}

</style>
</head>

<body>
    
<div id="loadingScreen">
    <div class="loader"></div>
    <p>Loading Lunar Cloud Storage...</p>
</div>
    
<div class="stars">

<div class="star" style="top:10%;left:20%;"></div>
<div class="star" style="top:20%;left:70%;"></div>
<div class="star" style="top:50%;left:40%;"></div>
<div class="star" style="top:80%;left:90%;"></div>
<div class="star" style="top:40%;left:15%;"></div>
<div class="star" style="top:65%;left:75%;"></div>

</div>
    
<div id="toast"></div>
    
<div id="liveNotif"></div>

<div class="wrapper">

<!-- Sidebar -->
<div class="sidebar">

    <!-- ATAS -->
    <div>

        <h2>☁️ Lunar Cloud</h2>

        <div class="mini-stats">
            <p>📁 <?= count($files) ?> Files</p>
            <p>📁 <?= $totalFolders ?> Folder</p>
            <p>🖼️ <?= $totalImages ?> Images</p>
            <p>📄 <?= $totalDocs ?> Docs</p>
        </div>

    </div>

    <!-- TENGAH -->
    <div class="menu">

        <?php if($user['role'] === 'admin'): ?>
            <a href="manage_users.php">
                👥 Manage Users
            </a>
        <?php endif; ?>
        
            <a href="mail.php">
                📩 Mail
            </a>
        
        	<a href="#">
                📁 File Manager
            </a>

        <?php if($user['role'] !== 'viewer'): ?>
            <a href="upload_page.php?folder=<?= $currentFolder ?>">
                📤 Upload
            </a>
        <?php endif; ?>
        
        <?php if($user['role'] !== 'viewer'): ?>
           <a href="trash.php">
               🗑️ Trash
           </a>
        <?php endif; ?>

    </div>

    <!-- BAWAH -->
    <div class="sidebar-bottom">

        <button onclick="scrollToTop()">⬆ Top</button>

        <button onclick="toggleTheme()">🌙 Theme</button>

        <button onclick="document.getElementById('searchInput').focus()">
            🔎 Search
        </button>

    </div>

</div>

    <!-- Main -->
    <div class="main">

        <div class="topbar">
            
            <div class="filter-bar">

                <button onclick="toggleView()">
                    📐 View
                </button>

                <select id="sortSelect" onchange="sortFiles()">

                    <option value="newest">
                        🕒 Newest First
                    </option>

                    <option value="oldest">
                        📜 Earliest
                    </option>

                    <option value="name_asc">
                        🔤 Name (A-Z)
                    </option>

                    <option value="name_desc">
                        🔠 Name (Z-A)
                    </option>

                    <option value="size_small">
                        📦 Smallest Size
                    </option>

                    <option value="size_large">
                        💾 Largest Size
                    </option>

                    <option value="file_first">
                        📄 File First
                    </option>

                    <option value="folder_first">
                        📂 Folder First
                    </option>

                </select>

                <?php if($user['role'] === 'admin'): ?>

                <input
                    type="text"
                    id="searchInput"
                    placeholder="🔎 Search..."
                    onkeyup="searchAll()">

                <?php endif; ?>

            </div>

            <h1>Lunar Cloud Storage by Zharvian</h1>

            <div class="top-actions">

                <button class="menu-btn" onclick="openMenu()">
                    ☰
                </button>

            </div>

        </div>
        
        <div class="card">
            <h2>
                Landing successful... Welcome
                <?= htmlspecialchars($user['username']) ?>
                👋 
            </h2>

            <br>

            <p>
               Take control of your data with Lunar Cloud.
            </p>
        </div>

        <?php if ($status === 'upload_success'): ?>

            <script>
                showToast("Upload berhasil");
            </script>

        <?php elseif ($status === 'rename_success'): ?>

            <script>
                showToast("Rename berhasil");
            </script>

        <?php elseif ($status === 'delete_success'): ?>

            <div class="success">
                File dihapus
            </div>
        
        <?php elseif ($status === 'notif_sent'): ?>

            <script>
            showToast("Notification berhasil dikirim");
            </script>

        <?php elseif ($status === 'error'): ?>

            <div class="error">
                Terjadi error
            </div>

        <?php endif; ?>

        <?php if($user['role'] !== 'viewer'): ?>

            <div class="stats-grid">

                <div class="stat-card">
                    <h3>📁 Total File</h3>
                    <p><?= count($files) ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>📂 Total Folder</h3>
                    <p><?= $totalFolders ?></p>
                </div>

                <div class="stat-card">
                    <h3>💾 Storage</h3>
                    <p><?= formatFileSize($totalSize) ?></p>
                </div>

                <div class="stat-card">
                    <h3>🖼️ Images</h3>
                    <p><?= $totalImages ?></p>
                </div>

                <div class="stat-card">
                    <h3>📄 Documents</h3>
                    <p><?= $totalDocs ?></p>
                </div>

            </div>

        <?php endif; ?>
        
<?php if($currentFolder > 0): ?>

    <?php

    $parentQuery = $conn->prepare("
        SELECT parent_id
        FROM folders
        WHERE id = ?
    ");

    $parentQuery->bind_param(
        "i",
        $currentFolder
    );

    $parentQuery->execute();

    $parentResult =
    $parentQuery->get_result()
    ->fetch_assoc();

    $backFolder =
    $parentResult['parent_id'] ?? 0;

    ?>

    <div style="margin-bottom:20px;">

        <a
        href="index.php<?= $backFolder ? '?folder=' . $backFolder : '' ?>"
        class="tab-btn"
        style="text-decoration:none;display:inline-block;">

            ⬅ Back

        </a>

    </div>

<?php endif; ?>
        
<!-- FILTER FILE TYPE -->

<div class="file-tabs">

    <button class="tab-btn active"
    onclick="filterFiles('all', this)">
        📁 All Files
    </button>

    <button class="tab-btn"
    onclick="filterFiles('photo', this)">
        🖼️ Photos
    </button>

    <button class="tab-btn"
    onclick="filterFiles('doc', this)">
        📄 Documents
    </button>

    <button class="tab-btn"
    onclick="filterFiles('folder', this)">
        📂 Folders
    </button>

    <button class="tab-btn"
    onclick="filterFiles('favorite', this)">
        ⭐ Favorites
    </button>

    <?php if($user['role'] !== 'viewer'): ?>

    <button
    class="tab-btn"
    onclick="openFolderModal()">

        📂 New Folder

    </button>

    <?php endif; ?>

</div>

<?php

if($user['role'] === 'admin'){

    $folderQuery = $conn->prepare("
        SELECT
            folders.*,
            users.username
        FROM folders
        JOIN users
        ON folders.user_id = users.id
        WHERE (
            parent_id = ?
            OR (parent_id IS NULL AND ? = 0)
        )
        ORDER BY created_at DESC
    ");

    $folderQuery->bind_param(
        "ii",
        $currentFolder,
        $currentFolder
    );

}else{

    $folderQuery = $conn->prepare("
        SELECT
            folders.*,
            users.username
        FROM folders
        JOIN users
        ON folders.user_id = users.id
        WHERE folders.user_id = ?
        AND (
            parent_id = ?
            OR (parent_id IS NULL AND ? = 0)
        )
        ORDER BY created_at DESC
    ");

    $folderQuery->bind_param(
        "iii",
        $user['id'],
        $currentFolder,
        $currentFolder
    );

}

$folderQuery->execute();

$folders = $folderQuery->get_result();

?>

<!-- Files -->
<div class="grid" id="fileGrid">

    <?php while($folder = $folders->fetch_assoc()): ?>

    <div
    class="file folder-item"
    data-favorite="<?= $folder['is_favorite'] ?>"
    data-type="folder"
    data-name="<?= strtolower(htmlspecialchars($folder['name'])) ?>"
    data-size="0"
    data-date="<?= strtotime($folder['created_at']) ?>"
    >

    <div
    onclick="window.location='index.php?folder=<?= $folder['id'] ?>'"
    style="cursor:pointer;">

        <div class="preview">

            <div class="file-preview-doc">

                <div class="doc-icon">
                    📂
                </div>

                <div class="doc-ext">
                    FOLDER
                </div>

            </div>

        </div>

    <div class="file-name">
        <?= htmlspecialchars($folder['name']) ?>
    </div>
        
</div>

<?php

$countStmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM files
    WHERE folder_id = ?
    AND is_deleted = 0
");

$countStmt->bind_param(
    "i",
    $folder['id']
);

$countStmt->execute();

$totalFiles =
$countStmt
->get_result()
->fetch_assoc()['total'];

$sizeStmt = $conn->prepare("
    SELECT COALESCE(SUM(size),0) as total_size
    FROM files
    WHERE folder_id = ?
    AND is_deleted = 0
");

$sizeStmt->bind_param(
    "i",
    $folder['id']
);

$sizeStmt->execute();

$totalSizeFolder =
$sizeStmt
->get_result()
->fetch_assoc()['total_size'];

?>

<div class="file-meta">
    👤
    <?= htmlspecialchars($folder['username'] ?? $user['username']) ?>
</div>

<div class="file-meta">
    📦 <?= $totalFiles ?> file
</div>

<div class="file-meta">
    💾 <?= formatFileSize($totalSizeFolder) ?>
</div>

<div class="file-meta">
    📅
    <?= date("d M Y", strtotime($folder['created_at'])) ?>
</div>

<div class="actions">
    
    <a
    class="download"
    href="#"
    onclick="openRenameModal(
    'folder',
    <?= $folder['id'] ?>,
    '<?= htmlspecialchars($folder['name'], ENT_QUOTES) ?>'
    )">
        ✏️ Edit
    </a>

    <a
    class="download"
    href="download_folder.php?id=<?= $folder['id'] ?>">
        ZIP
    </a>
    
    <a
    class="download"
    href="toggle_folder_favorite.php?id=<?= $folder['id'] ?>">

        <?= $folder['is_favorite']
        ? '⭐ Unfavorite'
        : '☆ Favorite' ?>

    </a>

    <a
    class="download"
    href="toggle_folder_public.php?id=<?= $folder['id'] ?>">

        <?= $folder['is_public']
        ? 'Hide'
        : 'Public' ?>

    </a>

    <?php if(
        $user['role'] === 'admin'
        || $folder['user_id'] == $user['id']
    ): ?>

    <a
    class="delete"
    href="delete_folder.php?id=<?= $folder['id'] ?>"
    onclick="return confirm('Delete folder?')">

        Delete

    </a>

    <?php endif; ?>

</div>

    </div>

<?php endwhile; ?>
    
<?php foreach ($files as $file): ?>
    
    <?php

$fullPath = "uploads/" . $file['name'];

if(!file_exists($fullPath)){
    continue;
}

?>

<?php

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

$isPhoto = in_array($ext, [
    'jpg',
    'jpeg',
    'png',
    'gif',
    'webp'
]);

$typeClass = $isPhoto ? 'photo' : 'doc';

?>

<div
class="file"
data-type="<?= $typeClass ?>"
data-favorite="<?= $file['is_favorite'] ?>"
data-name="<?= strtolower(htmlspecialchars($file['name'])) ?>"
data-size="<?= $file['size'] ?>"
data-date="<?= $file['modified'] ?>"
data-uploader="<?= strtolower(htmlspecialchars($file['username'])) ?>"
>

    <div class="preview">

        <?php if ($isPhoto): ?>

            <img
            src="uploads/<?= htmlspecialchars($file['name']) ?>"
            onclick="openModal(this.src)">

        <?php else: ?>

            <div class="file-preview-doc">

                <div class="doc-icon">
                    <?= fileIcon($file['name']) ?>
                </div>

                <div class="doc-ext">
                    <?= strtoupper($ext) ?>
                </div>

                <div class="doc-label">
                    DOCUMENT
                </div>

            </div>

        <?php endif; ?>

    </div>

    <div class="file-name">
        <?= htmlspecialchars($file['name']) ?>
    </div>

   <div class="file-meta">
        👤
        <?= htmlspecialchars($file['username']) ?>

        <?php if($user['role'] === 'admin'): ?>
            (#<?= $file['user_id'] ?>)
        <?php endif; ?>
    </div>

    <div class="file-meta">
        💾
        <?= formatFileSize($file['size']) ?>
    </div>

    <div class="file-meta">
        📅
        <?= date("d M Y", $file['modified']) ?>
    </div>

    <?php if($user['role'] === 'admin'): ?>

        <div class="file-meta">
            🛡️
            <?= htmlspecialchars($file['uploader_role'] ?? '-') ?>
        </div>

    <?php endif; ?>

    <div class="actions">
        
        <a
        class="download"
        href="#"
        onclick="openRenameModal(
        'file',
        <?= $file['id'] ?>,
        '<?= htmlspecialchars($file['name'], ENT_QUOTES) ?>'
        )">
            ✏️ Edit
        </a>

        <a
        class="download"
        href="download.php?file=<?= urlencode($file['name']) ?>">
            Download
        </a>

        <a
        class="download"
        href="toggle_favorite.php?id=<?= $file['id'] ?>">

            <?= $file['is_favorite']
            ? '⭐ Unfavorite'
            : '☆ Favorite' ?>

        </a>

        <?php if(
            $user['role'] === 'admin'
            || $file['user_id'] == $user['id']
        ): ?>

            <?php if($user['role'] === 'admin'): ?>

                <a
                class="download"
                href="toggle_public.php?file=<?= urlencode($file['name']) ?>">

                    <?= $file['is_public'] ? 'Hide' : 'Public' ?>

                </a>

            <?php endif; ?>

            <a
            class="delete"
            href="delete.php?id=<?= $file['id'] ?>"
            onclick="return confirm('Hapus file ini?')">

                Delete

            </a>

        <?php endif; ?>

    </div>

</div>

<?php endforeach; ?>

</div>
    
<!-- ========================= -->
<!-- 🖼️ IMAGE MODAL -->
<!-- ========================= -->

<div id="imgModal" class="img-modal">
    <span class="close-modal">✕</span>
    <img id="modalImg">
</div>
    
    <!-- Drawer -->
<div id="drawer" class="drawer">

    <div class="drawer-content">

        <div class="drawer-top">

            <h2>⚙️ Settings</h2>

            <button onclick="closeMenu()" class="close-btn">
                ✕
            </button>

        </div>

        <div class="profile-box">

            <div class="profile-name">
                <?= htmlspecialchars($user['username']) ?>
            </div>

            <div class="profile-role">
                <?= htmlspecialchars($user['role']) ?>
            </div>

        </div>

        <a href="edit_profile.php" class="drawer-link">
            👤 Edit Profile
        </a>
        
        <?php if($user['role'] === 'viewer'): ?>

            <a href="request_access.php" class="drawer-link">
                🚀 Request Upload Access
            </a>

        <?php endif; ?>
        
        <?php if($user['role'] === 'admin'): ?>

        <button
        onclick="openNotifModal()"
        class="drawer-link-btn">

            📢 Send Notification

        </button>

        <?php endif; ?>

        <button onclick="toggleTheme()" class="drawer-link-btn">
            🌙 / ☀️ Toggle Theme
        </button>

        <a href="logout.php" class="drawer-link logout-btn">
            🚪 Logout
        </a>

    </div>

</div>
        
    </div>
    </div>
    
    <!-- ========================= -->
<!-- 📂 CREATE FOLDER MODAL -->
<!-- ========================= -->

<div id="folderModal" class="img-modal">

    <div class="card"
    style="
    width:90%;
    max-width:420px;
    position:relative;
    ">

        <span
        class="close-modal"
        onclick="closeFolderModal()"
        style="
        top:15px;
        right:20px;
        color:white;
        ">
            ✕
        </span>

        <h2 style="margin-bottom:20px;">
            📂 Create Folder
        </h2>

        <form action="create_folder.php?folder=<?= $currentFolder ?>" method="POST">

            <input
            type="text"
            name="folder_name"
            placeholder="Folder name..."
            required

            style="
            width:100%;
            padding:16px;
            border:none;
            outline:none;
            border-radius:16px;
            margin-bottom:20px;
            background:rgba(255,255,255,0.12);
            color:white;
            ">

            <button
            type="submit"
            style="
            width:100%;
            padding:15px;
            border-radius:16px;
            ">

                🚀 Create Folder

            </button>

        </form>

    </div>

</div>
    
<!-- ========================= -->
<!-- ✏️ RENAME MODAL -->
<!-- ========================= -->

<div id="renameModal" class="img-modal">

    <div class="card"
    style="
    width:90%;
    max-width:420px;
    position:relative;
    ">

        <span
        class="close-modal"
        onclick="closeRenameModal()"
        style="
        top:15px;
        right:20px;
        color:white;
        ">
            ✕
        </span>

        <h2 style="margin-bottom:20px;">
            ✏️ Rename
        </h2>

        <form action="rename.php" method="POST">

            <input type="hidden" name="type" id="renameType">
            <input type="hidden" name="id" id="renameId">

            <input
            type="text"
            name="new_name"
            id="renameInput"
            required

            style="
            width:100%;
            padding:16px;
            border:none;
            outline:none;
            border-radius:16px;
            margin-bottom:20px;
            background:rgba(255,255,255,0.12);
            color:white;
            ">

            <button
            type="submit"
            style="
            width:100%;
            padding:15px;
            border-radius:16px;
            ">

                💾 Save Rename

            </button>

        </form>

    </div>

</div>
    
<!-- ========================= -->
<!-- 📢 NOTIFICATION MODAL -->
<!-- ========================= -->

<div id="notifModal" class="img-modal">

    <div class="card"
    style="
    width:90%;
    max-width:500px;
    position:relative;
    ">

        <span
        class="close-modal"
        onclick="closeNotifModal()"
        style="
        top:15px;
        right:20px;
        color:white;
        ">

            ✕

        </span>

        <h2 style="margin-bottom:20px;">
            📢 Send Notification
        </h2>

        <form action="send_notification.php" method="POST">

            <input
            type="text"
            name="title"
            placeholder="Notification title..."
            required

            style="
            width:100%;
            padding:15px;
            border:none;
            border-radius:15px;
            margin-bottom:15px;
            background:rgba(255,255,255,0.12);
            color:white;
            ">

            <textarea
            name="message"
            placeholder="Type message..."
            required

            style="
            width:100%;
            height:140px;
            padding:15px;
            border:none;
            border-radius:15px;
            resize:none;
            margin-bottom:15px;
            background:rgba(255,255,255,0.12);
            color:white;
            "></textarea>

            <select
            name="target_role"

            style="
            width:100%;
            padding:15px;
            border:none;
            border-radius:15px;
            margin-bottom:20px;
            background:rgba(255,255,255,0.12);
            color:white;
            ">

                <option value="all">
                    🌍 All
                </option>

                <option value="user">
                    👤 User
                </option>

                <option value="viewer">
                    👁 Viewer
                </option>

            </select>
            
            <input
            type="number"
            name="target_user_id"
            placeholder="Specific User ID (optional)"

            style="
            width:100%;
            padding:15px;
            border:none;
            border-radius:15px;
            margin-bottom:20px;
            background:rgba(255,255,255,0.12);
            color:white;
            ">

            <button
            type="submit"
            style="
            width:100%;
            padding:15px;
            border-radius:15px;
            ">

                🚀 Send Notification

            </button>

        </form>

    </div>

</div>
    
<script>
window.addEventListener("load",()=>{

    document.getElementById("loadingScreen").style.display="none";

    // default sorting
    sortFiles();

});

const modal = document.getElementById("imgModal");
const modalImg = document.getElementById("modalImg");

function openModal(src) {
    console.log("clicked image:", src);
    modalImg.src = src;
    modal.classList.add("active");
}

// klik background atau tombol X
modal.addEventListener("click", (e) => {
    if (e.target === modal || e.target.closest(".close-modal")) {
        modal.classList.remove("active");
    }
});
    
/* ========================= */
/* 📂 FOLDER MODAL */
/* ========================= */

function openFolderModal(){

    document
    .getElementById("folderModal")
    .classList.add("active");
}

function closeFolderModal(){

    document
    .getElementById("folderModal")
    .classList.remove("active");
}

/* klik luar modal */

document
.getElementById("folderModal")
.addEventListener("click",(e)=>{

    if(e.target.id === "folderModal"){

        closeFolderModal();

    }

});
    
function toggleView(){
    document
    .getElementById("fileGrid")
    .classList.toggle("list-view");
}

// ESC close
document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
        modal.classList.remove("active");
    }
});
    
function scrollToTop(){
    window.scrollTo({top:0, behavior:"smooth"});
}

    if (window.location.search.includes("status=")) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }

            function openMenu(){
        document
        .getElementById("drawer")
        .classList.add("active");
    }

    function closeMenu(){
        document
        .getElementById("drawer")
        .classList.remove("active");
    }

    function toggleTheme(){

        document.body.classList.toggle("dark-mode");

        if(document.body.classList.contains("dark-mode")){
            localStorage.setItem("theme","dark");
        }else{
            localStorage.setItem("theme","light");
        }
    }

    if(localStorage.getItem("theme") === "dark"){
        document.body.classList.add("dark-mode");
    }
    
function toggleFilesMenu(event){

    event.stopPropagation();

    const dropdown =
    document.getElementById("filesDropdown");

    const arrow =
    document.getElementById("arrowIcon");

    dropdown.classList.toggle("active");

    arrow.classList.toggle("rotate");
}
    
function filterFiles(type, btn){

    const files =
    document.querySelectorAll("#fileGrid .file");

    files.forEach(file=>{

        const fileType =
        file.dataset.type;

        const isFolder =
        fileType === "folder";

        const isFavorite =
        file.dataset.favorite === "1";

        /* FAVORITE */

        if(type === "favorite"){

            if(isFavorite){
                file.style.display = "block";
            }else{
                file.style.display = "none";
            }

            return;
        }

        /* FOLDER */

        if(type === "folder"){

            file.style.display =
            isFolder ? "block" : "none";

            return;
        }

        /* ALL */

        if(type === "all"){

            file.style.display = "block";
            return;
        }

        /* PHOTO / DOC */

        if(
            !isFolder &&
            fileType === type
        ){

            file.style.display = "block";

        }else{

            file.style.display = "none";

        }

    });

    document.querySelectorAll(".tab-btn")
    .forEach(tab=>{
        tab.classList.remove("active");
    });

    btn.classList.add("active");
}
    
/* ========================= */
/* ✏️ RENAME MODAL */
/* ========================= */

function openRenameModal(type,id,name){

    document
    .getElementById("renameModal")
    .classList.add("active");

    document
    .getElementById("renameType")
    .value = type;

    document
    .getElementById("renameId")
    .value = id;

    document
    .getElementById("renameInput")
    .value = name;
}

function closeRenameModal(){

    document
    .getElementById("renameModal")
    .classList.remove("active");
}

document
.getElementById("renameModal")
.addEventListener("click",(e)=>{

    if(e.target.id === "renameModal"){

        closeRenameModal();

    }

});
    
/* ========================= */
/* Notif */
/* ========================= */
    
function openNotifModal(){

    document
    .getElementById("notifModal")
    .classList.add("active");
}

function closeNotifModal(){

    document
    .getElementById("notifModal")
    .classList.remove("active");
}

document
.getElementById("notifModal")
.addEventListener("click",(e)=>{

    if(e.target.id === "notifModal"){

        closeNotifModal();

    }

});
    
/* ========================= */
/* 🔃 SORT FILE */
/* ========================= */

function sortFiles(){

    const grid =
    document.getElementById("fileGrid");

    const sortValue =
    document.getElementById("sortSelect").value;

    const files =
    Array.from(document.querySelectorAll("#fileGrid .file"));

    files.sort((a,b)=>{

        const aFolder =
        a.dataset.type === "folder";

        const bFolder =
        b.dataset.type === "folder";

        /* ========================= */
        /* 📂 FILE / FOLDER PRIORITY */
        /* ========================= */

        if(sortValue === "folder_first"){

            if(aFolder && !bFolder) return -1;
            if(!aFolder && bFolder) return 1;

        }

        if(sortValue === "file_first"){

            if(!aFolder && bFolder) return -1;
            if(aFolder && !bFolder) return 1;

        }

        /* default = folder belakang */

        if(
            sortValue !== "folder_first"
            &&
            sortValue !== "file_first"
        ){

            if(aFolder && !bFolder) return 1;
            if(!aFolder && bFolder) return -1;

        }

        /* ========================= */
        /* 🔃 NORMAL SORT */
        /* ========================= */

        if(sortValue === "newest"){
            return b.dataset.date - a.dataset.date;
        }

        if(sortValue === "oldest"){
            return a.dataset.date - b.dataset.date;
        }

        if(sortValue === "name_asc"){
            return a.dataset.name.localeCompare(b.dataset.name);
        }

        if(sortValue === "name_desc"){
            return b.dataset.name.localeCompare(a.dataset.name);
        }

        if(sortValue === "size_small"){
            return a.dataset.size - b.dataset.size;
        }

        if(sortValue === "size_large"){
            return b.dataset.size - a.dataset.size;
        }

        return 0;

    });

    files.forEach(file=>{
        grid.appendChild(file);
    });
}

/* ========================= */
/* 🔎 SEARCH FILE + UPLOADER */
/* ========================= */

function searchAll(){

    const searchInput =
    document.getElementById("searchInput");

    if(!searchInput) return;

    const keyword =
    searchInput.value.toLowerCase();

    const files =
    document.querySelectorAll("#fileGrid .file");

    files.forEach(file=>{

        const fileName =
        file.dataset.name || "";

        const uploader =
        file.dataset.uploader || "";

        if(
            fileName.includes(keyword) ||
            uploader.includes(keyword)
        ){

            file.style.display = "block";

        }else{

            file.style.display = "none";
        }

    });

}   
    
<?php if($latestNotif): ?>

window.addEventListener("load",()=>{

    const live =
    document.getElementById("liveNotif");

    if(!live) return;

	live.innerHTML = `
    
    <div class="live-card" id="liveCard">

        <div class="live-top">

            <strong>
                🔔 <?= htmlspecialchars($latestNotif['title']) ?>
            </strong>

            <span
            class="close-live"
            onclick="closeLiveNotif()">

                ✕

            </span>

        </div>

        <div>
            <?= nl2br(htmlspecialchars($latestNotif['message'])) ?>
        </div>

    </div>
    `;

    setTimeout(()=>{

        const card =
        document.getElementById("liveCard");

        if(card){

            card.remove();

        }

    },5000);

    playNotifSound();

});

function closeLiveNotif(){

    const card =
    document.getElementById("liveCard");

    if(card){

        card.remove();

    }

}

function playNotifSound(){

    const audio = new Audio(
        "https://cdn.pixabay.com/download/audio/2022/03/15/audio_c8c8a73467.mp3"
    );

    audio.volume = 0.4;

    audio.play();

}

<?php endif; ?>
setInterval(checkNotification, 3000);

let notifTimeout = null;

function checkNotification(){

    fetch("check_notification.php")
    .then(res => res.json())
    .then(data => {

        if(data.success){

            showLiveNotification(
                data.title,
                data.message
            );

        }

    });

}

function showLiveNotification(title, message){

    const live =
    document.getElementById("liveNotif");

    live.innerHTML = "";

    const card = document.createElement("div");

    card.className = "live-card";
    card.id = "liveCard";

    card.innerHTML = `
        <div class="live-top">

            <strong>
                🔔 ${title}
            </strong>

            <span class="close-live">
                ✕
            </span>

        </div>

        <div>
            ${message}
        </div>
    `;

    live.appendChild(card);

    card
    .querySelector(".close-live")
    .addEventListener("click", closeLiveNotif);

    playNotifSound();

    if(notifTimeout){
        clearTimeout(notifTimeout);
    }

    notifTimeout = setTimeout(() => {

        closeLiveNotif();

    }, 5000);
}

function closeLiveNotif(){

    const card =
    document.getElementById("liveCard");

    if(card){
        card.remove();
    }

    if(notifTimeout){
        clearTimeout(notifTimeout);
    }
}
</script>

</body>
</html>