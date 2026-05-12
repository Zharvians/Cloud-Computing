<?php

require 'config.php';

if(!isset($_SESSION['user'])){
    header("Location:login.php");
    exit;
}

$user = $_SESSION['user'];
$status = $_GET['status'] ?? '';

$uploadDir = "uploads/";
$files = [];

if($user['role'] === 'admin'){

    $result = $conn->query("
        SELECT files.*, users.username
        FROM files
        JOIN users
        ON files.user_id = users.id
        ORDER BY uploaded_at DESC
    ");

}else{

    $stmt = $conn->prepare("
        SELECT * FROM files
        WHERE user_id=?
        ORDER BY uploaded_at DESC
    ");

    $stmt->bind_param("i",$user['id']);
    $stmt->execute();

    $result = $stmt->get_result();
}

$files = [];

while ($row = $result->fetch_assoc()) {
     $files[] = [
        'name' => $row['name'],
        'size' => $row['size'],
        'modified' => strtotime($row['uploaded_at']),
        'username' => $row['username'] ?? 'Unknown'
    ];
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
    return match($ext) {
        'jpg','jpeg','png','gif' => '🖼️',
        'pdf' => '📕',
        'zip','rar' => '📦',
        'txt' => '📄',
        default => '📁'
    };
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cloud Storage</title>

<style>
* {margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}

body {
    background: linear-gradient(135deg,#0f172a,#1e3a8a);
    color: white;
}

/* Layout */
.wrapper {
    display: grid;
    grid-template-columns: 240px 1fr;
    min-height: 100vh;
}

/* Sidebar */
.sidebar {
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(15px);
    padding: 20px;
    border-right: 1px solid rgba(255,255,255,0.1);
}

.sidebar h2 {
    margin-bottom: 20px;
}

.menu a {
    display: block;
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 8px;
    color: white;
    text-decoration: none;
    transition: 0.2s;
}

.menu a:hover {
    background: rgba(255,255,255,0.1);
}

/* Main */
.main {
    padding: 30px;
}

/* Topbar */
.topbar {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

/* Cards */
.card {
    background: rgba(255,255,255,0.08);
    backdrop-filter: blur(20px);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 20px;
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
.file {
    background: rgba(255,255,255,0.1);
    padding: 15px;
    border-radius: 12px;
    transition: 0.2s;
}

.file:hover {
    transform: translateY(-5px);
    background: rgba(255,255,255,0.15);
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
}

.actions a {
    text-decoration: none;
    font-size: 12px;
    margin-right: 8px;
}
    
    .preview {
    width: 100%;
    aspect-ratio: 1/1; /* 🔥 bikin kotak 1:1 */
    background: rgba(255,255,255,0.05);
    border-radius: 10px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.preview img {
    width: 100%;
    height: 100%;
    object-fit: cover; /* 🔥 biar ga gepeng */
    cursor: pointer;
    transition: 0.2s;
}

.preview img:hover {
    transform: scale(1.05);
}

.download {color:#60a5fa;}
.delete {color:#f87171;}

.success {background:#16a34a;padding:10px;border-radius:8px;margin-bottom:10px;}
.error {background:#dc2626;padding:10px;border-radius:8px;margin-bottom:10px;}
</style>
</head>

<body>

<div class="wrapper">

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>☁️ Cloud</h2>
        <div class="menu">
            <a href="#">📁 File</a>
            <a href="#">📤 Upload</a>
        </div>
    </div>

    <!-- Main -->
    <div class="main">

        <div class="topbar">

    <h1>lunar Storage by Muhammad Ade Ramadhani</h1>

    <div style="display:flex;gap:15px;align-items:center;">

        <div>
            Total: <?= count($files) ?> file
        </div>

        <div>
            <?= htmlspecialchars($user['username']) ?>
            (<?= $user['role'] ?>)
        </div>

        <a href="logout.php"
           style="
           color:white;
           text-decoration:none;
           background:#ef4444;
           padding:8px 12px;
           border-radius:8px;
           ">
           Logout
        </a>

    </div>

</div>

        <?php if ($status === 'upload_success'): ?>
    <div class="success">Upload berhasil</div>
<?php elseif ($status === 'delete_success'): ?>
    <div class="success">File dihapus</div>
<?php elseif ($status === 'error'): ?>
    <div class="error">Terjadi error</div>
<?php endif; ?>

        <!-- Upload -->
        <div class="card">
            <form action="upload.php" method="post" enctype="multipart/form-data" class="upload">
                <input type="file" name="fileToUpload" required>
                <button>Upload</button>
            </form>
        </div>

        <!-- Files -->
        <div class="grid">
            <?php foreach ($files as $file): ?>
            <div class="file">
                <div class="preview">
    <?php if (isImage($file['name'])): ?>
        <img src="uploads/<?= htmlspecialchars($file['name']) ?>"
             onclick="openModal(this.src)">
    <?php else: ?>
        <div class="file-icon"><?= fileIcon($file['name']) ?></div>
    <?php endif; ?>
</div>
                <div class="file-name"><?= htmlspecialchars($file['name']) ?></div>
                <div class="file-meta"><?= formatFileSize($file['size']) ?></div>
                <div class="file-meta"><?= date("d M Y", $file['modified']) ?></div>
                
                <?php if($user['role'] === 'admin'): ?>

                <div class="file-meta">
                    Upload by: <?= htmlspecialchars($file['username']) ?>
                </div>

                <?php endif; ?>

                <div class="actions">
                    <a class="download" href="download.php?file=<?= urlencode($file['name']) ?>">Download</a>
                    <?php if($user['role'] === 'admin'): ?>

                    <a class="delete"
                    href="delete.php?file=<?= urlencode($file['name']) ?>"
                    onclick="return confirm('Hapus?')">
                    Delete
                    </a>

                    <?php endif; ?>
                  
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

</div>
    
    <!-- Modal -->
<div id="imgModal" style="
    display:none;
    position:fixed;
    top:0;left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.8);
    justify-content:center;
    align-items:center;
    z-index:999;
">
    <img id="modalImg" style="
        max-width:90%;
        max-height:90%;
        border-radius:10px;
    ">
</div>
    
    <script>
function openModal(src) {
    const modal = document.getElementById("imgModal");
    const img = document.getElementById("modalImg");

    img.src = src;
    modal.style.display = "flex";
}

document.getElementById("imgModal").onclick = function() {
    this.style.display = "none";
}
       
if (window.location.search.includes("status=")) {
    window.history.replaceState({}, document.title, window.location.pathname);
}
</script>
</script>

</body>
</html>