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
    LIMIT 5
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
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="responsive.css">
<title>Lunar Cloud Storage</title>
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
<div class="sidebar" id="mobileSidebar">

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
        
        <?php elseif ($status === 'message_sent'): ?>

            <script>
            showToast("Message sent to admin");
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
        
       <?php if($user['role'] === 'user'): ?>

            <button
            onclick="openSupportModal()"
            class="drawer-link-btn">

                💬 Talk To Admin

            </button>

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
    
<!-- ========================= -->
<!-- 💬 SUPPORT MODAL -->
<!-- ========================= -->

<div id="supportModal" class="img-modal">

    <div class="card"
    style="
    width:90%;
    max-width:500px;
    position:relative;
    ">

        <span
        class="close-modal"
        onclick="closeSupportModal()"
        style="
        top:15px;
        right:20px;
        color:white;
        ">

            ✕

        </span>

        <h2 style="margin-bottom:20px;">
            💬 Talk To Admin
        </h2>

        <form action="send_support.php" method="POST">

            <input
            type="text"
            name="subject"
            placeholder="Subject..."
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
            placeholder="Type your message..."
            required

            style="
            width:100%;
            height:140px;
            padding:15px;
            border:none;
            border-radius:15px;
            resize:none;
            margin-bottom:20px;
            background:rgba(255,255,255,0.12);
            color:white;
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


/* ========================= */
/* ☰ SETTINGS DRAWER */
/* ========================= */

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
    
/* notif */

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
    
/* ========================= */
/* 💬 SUPPORT MODAL */
/* ========================= */

function openSupportModal(){

    document
    .getElementById("supportModal")
    .classList.add("active");
}

function closeSupportModal(){

    document
    .getElementById("supportModal")
    .classList.remove("active");
}

document
.getElementById("supportModal")
.addEventListener("click",(e)=>{

    if(e.target.id === "supportModal"){

        closeSupportModal();

    }

});

document.addEventListener("click",(e)=>{

    const sidebar =
    document.querySelector(".sidebar");

    const menuBtn =
    document.querySelector(".menu-btn");

    if(
        window.innerWidth <= 768 &&
        sidebar.classList.contains("active") &&
        !sidebar.contains(e.target) &&
        !menuBtn.contains(e.target)
    ){

        sidebar.classList.remove("active");

    }

});
  
let startX = 0;

mobileSidebar.addEventListener("touchstart",(e)=>{

    startX = e.touches[0].clientX;

});

mobileSidebar.addEventListener("touchmove",(e)=>{

    let currentX = e.touches[0].clientX;

    if(currentX - startX > 20){

        mobileSidebar.classList.add("expand");

    }

});

mobileSidebar.addEventListener("touchend",()=>{

    setTimeout(()=>{

        mobileSidebar.classList.remove("expand");

    },1500);

});
</script>

</body>
</html>