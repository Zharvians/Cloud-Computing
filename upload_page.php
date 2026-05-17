<?php
require 'config.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

if($user['role'] === 'viewer'){
    header("Location:index.php");
    exit;
}

$currentFolder = isset($_GET['folder'])
    ? (int)$_GET['folder']
    : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Upload File • Lunar Cloud</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{
    min-height:100vh;

    display:flex;
    justify-content:center;
    align-items:center;

    overflow:hidden;

    color:white;

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

    animation:auroraMove 10s infinite alternate;
}

/* Layout */

.layout{
    display:flex;
    min-height:100vh;
}

/* Sidebar */

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

/* Main */

.main-content{
    margin-left:280px;

    width:calc(100% - 280px);

    min-height:100vh;

    display:flex;
    justify-content:center;
    align-items:center;

    padding:40px;
}

/* Aurora */

@keyframes auroraMove{

    0%{
        background-position:0% 50%;
    }

    100%{
        background-position:100% 50%;
    }

}

/* Glow */

body::before{
    content:"";
    position:fixed;
    inset:0;

    background:
    radial-gradient(circle at top,
    rgba(255,255,255,0.08),
    transparent 40%);

    pointer-events:none;
}

/* Stars */

.stars{
    position:fixed;
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

@keyframes blink{

    from{
        opacity:0.2;
    }

    to{
        opacity:1;
    }

}

/* ========================= */
/* UPLOAD CONTAINER */
/* ========================= */

.upload-box{
    width:100%;
    max-width:760px;

    padding:45px;

    border-radius:38px;

    position:relative;
    overflow:hidden;

    background:
    linear-gradient(
        180deg,
        rgba(255,255,255,0.08),
        rgba(255,255,255,0.03)
    );

    border:1px solid rgba(255,255,255,0.08);

    backdrop-filter:blur(30px);

    box-shadow:
    0 20px 80px rgba(0,0,0,0.45),
    0 0 40px rgba(59,130,246,0.12);
}

/* Glow Border */

.upload-box::before{
    content:"";
    position:absolute;
    inset:0;

    border-radius:38px;

    padding:1px;

    background:
    linear-gradient(
        135deg,
        rgba(59,130,246,0.5),
        rgba(168,85,247,0.35),
        transparent
    );

    -webkit-mask:
    linear-gradient(#fff 0 0) content-box,
    linear-gradient(#fff 0 0);

    -webkit-mask-composite:xor;
            mask-composite:exclude;

    pointer-events:none;
}

/* Floating blur */

.upload-box::after{
    content:"";
    position:absolute;

    width:260px;
    height:260px;

    background:
    radial-gradient(
        circle,
        rgba(59,130,246,0.18),
        transparent 70%
    );

    top:-80px;
    right:-80px;

    pointer-events:none;
}

/* ========================= */
/* HEADER */
/* ========================= */

.upload-header{
    position:relative;
    z-index:2;

    margin-bottom:35px;
}

.upload-box h1{
    font-size:48px;
    font-weight:900;

    letter-spacing:1px;

    margin-bottom:12px;

    background:
    linear-gradient(
        90deg,
        #ffffff,
        #7dd3fc,
        #c084fc
    );

    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

.subtitle{
    opacity:0.72;

    line-height:1.7;

    font-size:15px;

    max-width:520px;
}

/* ========================= */
/* STATS */
/* ========================= */

.upload-stats{
    display:grid;

    grid-template-columns:
    repeat(3,1fr);

    gap:14px;

    margin-bottom:30px;
}

.stat-card{
    padding:18px;

    border-radius:24px;

    background:rgba(255,255,255,0.05);

    border:1px solid rgba(255,255,255,0.06);

    backdrop-filter:blur(20px);
}

.stat-card span{
    display:block;

    font-size:12px;

    opacity:0.65;

    margin-bottom:8px;
}

.stat-card strong{
    font-size:20px;
}

/* ========================= */
/* FORM */
/* ========================= */

.upload-form{
    display:flex;
    flex-direction:column;

    gap:24px;

    position:relative;
    z-index:2;
}

/* ========================= */
/* DROP AREA */
/* ========================= */

.drop-area{
    position:relative;

    border:2px dashed rgba(255,255,255,0.14);

    border-radius:34px;

    padding:70px 30px;

    text-align:center;

    cursor:pointer;

    overflow:hidden;

    transition:0.35s;

    background:
    linear-gradient(
        180deg,
        rgba(255,255,255,0.05),
        rgba(255,255,255,0.02)
    );
}

.drop-area::before{
    content:"";
    position:absolute;
    inset:0;

    background:
    radial-gradient(
        circle at top,
        rgba(59,130,246,0.12),
        transparent 65%
    );

    opacity:0;

    transition:0.35s;
}

.drop-area:hover::before{
    opacity:1;
}

.drop-area:hover{
    transform:translateY(-5px);

    border-color:
    rgba(96,165,250,0.7);

    box-shadow:
    0 0 45px rgba(59,130,246,0.22);
}

.drop-icon{
    font-size:78px;

    margin-bottom:18px;

    filter:drop-shadow(
        0 0 20px rgba(96,165,250,0.45)
    );
}

.drop-title{
    font-size:24px;
    font-weight:800;

    margin-bottom:10px;
}

.drop-sub{
    opacity:0.68;

    font-size:14px;

    line-height:1.7;
}

/* ========================= */
/* INPUT */
/* ========================= */

input[type=file]{
    display:none;
}

/* ========================= */
/* FILE INFO */
/* ========================= */

.file-name{
    padding:18px 20px;

    border-radius:20px;

    background:rgba(255,255,255,0.04);

    border:1px solid rgba(255,255,255,0.06);

    text-align:center;

    font-size:15px;

    opacity:0.88;

    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

/* ========================= */
/* BUTTON */
/* ========================= */

button{
    position:relative;
    overflow:hidden;

    padding:20px;

    border:none;
    border-radius:24px;

    background:
    linear-gradient(
        135deg,
        #2563eb,
        #7c3aed
    );

    color:white;

    cursor:pointer;

    font-size:17px;
    font-weight:700;

    letter-spacing:0.5px;

    transition:0.3s;

    box-shadow:
    0 15px 40px rgba(59,130,246,0.35);
}

button::before{
    content:"";
    position:absolute;

    top:0;
    left:-120%;

    width:100%;
    height:100%;

    background:
    linear-gradient(
        90deg,
        transparent,
        rgba(255,255,255,0.25),
        transparent
    );

    transition:0.6s;
}

button:hover::before{
    left:120%;
}

button:hover{
    transform:translateY(-4px);

    box-shadow:
    0 20px 55px rgba(59,130,246,0.45);
}

/* ========================= */
/* USER BOX */
/* ========================= */

.user-box{
    margin-top:35px;

    display:flex;
    align-items:center;
    justify-content:space-between;

    gap:20px;

    padding:22px 24px;

    border-radius:28px;

    background:
    linear-gradient(
        135deg,
        rgba(255,255,255,0.06),
        rgba(255,255,255,0.03)
    );

    border:1px solid rgba(255,255,255,0.07);

    backdrop-filter:blur(20px);
}

.user-profile{
    display:flex;
    align-items:center;

    gap:18px;
}

.avatar{
    width:62px;
    height:62px;

    border-radius:50%;

    display:flex;
    justify-content:center;
    align-items:center;

    font-size:24px;

    background:
    linear-gradient(
        135deg,
        #3b82f6,
        #9333ea
    );

    box-shadow:
    0 0 30px rgba(59,130,246,0.35);
}

.user-info strong{
    display:block;

    font-size:18px;

    margin-bottom:4px;
}

.user-info p{
    opacity:0.68;
    font-size:14px;
}

.role-badge{
    padding:12px 18px;

    border-radius:999px;

    font-size:13px;
    font-weight:700;

    background:
    linear-gradient(
        135deg,
        rgba(59,130,246,0.25),
        rgba(168,85,247,0.25)
    );

    border:1px solid rgba(255,255,255,0.08);
}

/* Responsive */

@media(max-width:900px){

    .sidebar{
        width:100%;
        height:auto;
        position:relative;
    }

    .main-content{
        margin-left:0;
        width:100%;
        padding:25px;
    }

    .layout{
        flex-direction:column;
    }

}

@media(max-width:700px){

    .upload-box{
        padding:28px;
    }

    .upload-box h1{
        font-size:36px;
    }

    .upload-stats{
        grid-template-columns:1fr;
    }

    .user-box{
        flex-direction:column;
        align-items:flex-start;
    }

    .drop-area{
        padding:50px 20px;
    }

}

</style>
</head>

<body>

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
<!-- Stars -->

<div class="stars">

    <div class="star" style="top:10%;left:20%;"></div>
    <div class="star" style="top:20%;left:70%;"></div>
    <div class="star" style="top:50%;left:40%;"></div>
    <div class="star" style="top:80%;left:90%;"></div>
    <div class="star" style="top:40%;left:15%;"></div>
    <div class="star" style="top:65%;left:75%;"></div>

</div>

<div class="upload-box">

    <div class="upload-header">

        <h1>
            ☁️ Upload File
        </h1>

        <p class="subtitle">
            Securely upload and store your files inside Lunar Cloud.
            Fast, modern, encrypted, and accessible anywhere.
        </p>

    </div>

    <div class="upload-stats">

        <div class="stat-card">
            <span>MAX SIZE</span>
            <strong>10MB</strong>
        </div>

        <div class="stat-card">
            <span>STORAGE</span>
            <strong>Cloud Sync</strong>
        </div>

        <div class="stat-card">
            <span>SECURITY</span>
            <strong>Encrypted</strong>
        </div>

    </div>

    <form
        action="upload.php"
        method="post"
        enctype="multipart/form-data"
        class="upload-form">

        <input
            type="hidden"
            name="folder_id"
            value="<?= $currentFolder ?>">

        <label
        class="drop-area"
        for="fileInput">

            <div class="drop-icon">
                ☁️
            </div>

            <div class="drop-title">
                Drag & Drop Files
            </div>

            <div class="drop-sub">
                Click this area to browse files<br>
                Supported for documents, images, videos, and more.
            </div>

        </label>

        <input
        type="file"
        id="fileInput"
        name="fileToUpload"
        required>

        <div
        class="file-name"
        id="fileName">

            No file selected

        </div>

        <button type="submit">
            🚀 Upload to Lunar Cloud
        </button>

    </form>

    <div class="user-box">

        <div class="user-profile">

            <div class="avatar">
                👤
            </div>

            <div class="user-info">

                <strong>
                    <?= htmlspecialchars($user['username']) ?>
                </strong>

                <p>
                    Connected to Lunar Cloud System
                </p>

            </div>

        </div>

        <div class="role-badge">
            <?= strtoupper(htmlspecialchars($user['role'])) ?>
        </div>

    </div>

</div>

<script>

const fileInput =
document.getElementById("fileInput");

const fileName =
document.getElementById("fileName");

fileInput.addEventListener("change",()=>{

    if(fileInput.files.length > 0){

        fileName.textContent =
        "📄 " + fileInput.files[0].name;

    }else{

        fileName.textContent =
        "No file selected";

    }

});

</script>
    
</div>
</div>

</body>
</html>