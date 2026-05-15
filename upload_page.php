<?php
require 'config.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

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

/* Upload Box */

.upload-box{

    width:100%;
    max-width:540px;

    padding:40px;

    border-radius:32px;

    background:rgba(255,255,255,0.06);

    backdrop-filter:blur(25px);

    border:1px solid rgba(255,255,255,0.08);

    box-shadow:
    0 10px 50px rgba(0,0,0,0.45);

    position:relative;
    overflow:hidden;
}

/* overlay glass */

.upload-box::before{
    content:'';
    position:absolute;
    inset:0;

    background:
    linear-gradient(
        135deg,
        rgba(255,255,255,0.08),
        transparent
    );

    pointer-events:none;
}

/* Title */

.upload-box h1{

    font-size:36px;
    font-weight:800;

    margin-bottom:12px;

    background:
    linear-gradient(
        90deg,
        #ffffff,
        #60a5fa,
        #c084fc
    );

    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

.subtitle{
    opacity:0.7;
    margin-bottom:30px;
}

/* Upload Form */

.upload-form{
    display:flex;
    flex-direction:column;
    gap:22px;
}

/* Drop Area */

.drop-area{

    border:2px dashed rgba(255,255,255,0.18);

    border-radius:28px;

    padding:55px 20px;

    text-align:center;

    cursor:pointer;

    transition:0.3s;

    background:rgba(255,255,255,0.03);
}

.drop-area:hover{

    transform:translateY(-3px);

    border-color:#60a5fa;

    box-shadow:
    0 0 35px rgba(59,130,246,0.25);
}

.drop-icon{
    font-size:62px;
    margin-bottom:16px;
}

.drop-title{
    font-size:18px;
    font-weight:700;
}

.drop-sub{
    margin-top:8px;
    opacity:0.65;
    font-size:13px;
}

/* Hide Input */

input[type=file]{
    display:none;
}

/* File Name */

.file-name{
    text-align:center;
    font-size:14px;
    opacity:0.75;
}

/* Button */

button{

    padding:18px;

    border:none;
    border-radius:20px;

    background:
    linear-gradient(
        135deg,
        #3b82f6,
        #9333ea
    );

    color:white;

    cursor:pointer;

    font-size:16px;
    font-weight:600;

    transition:0.3s;

    box-shadow:
    0 0 30px rgba(59,130,246,0.35);
}

button:hover{

    transform:
    translateY(-3px);

    box-shadow:
    0 12px 40px rgba(59,130,246,0.45);
}

/* Back Button */

.back-btn{

    display:block;

    margin-top:22px;

    text-align:center;

    text-decoration:none;

    padding:16px;

    border-radius:20px;

    background:rgba(255,255,255,0.08);

    color:white;

    transition:0.3s;
}

.back-btn:hover{

    background:rgba(255,255,255,0.14);

    transform:translateY(-2px);
}

/* User Box */

.user-box{

    margin-top:28px;

    padding:18px;

    border-radius:20px;

    background:rgba(255,255,255,0.05);

    border:1px solid rgba(255,255,255,0.08);
}

.user-box p{
    margin-top:5px;
    opacity:0.7;
}

/* Responsive */

@media(max-width:600px){

    body{
        padding:20px;
    }

    .upload-box{
        padding:28px;
    }

}

</style>
</head>

<body>

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

    <h1>
        ☁️ Upload File
    </h1>

    <p class="subtitle">
        Upload your files securely into Lunar Cloud Storage.
    </p>

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
                📤
            </div>

            <div class="drop-title">
                Click to choose file
            </div>

            <div class="drop-sub">
                Maximum upload size 10MB
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
            Upload Sekarang
        </button>

    </form>

    <a
    href="index.php"
    class="back-btn">

        ← Kembali

    </a>

    <div class="user-box">

        <strong>
            <?= htmlspecialchars($user['username']) ?>
        </strong>

        <p>
            Role:
            <?= htmlspecialchars($user['role']) ?>
        </p>

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

</body>
</html>