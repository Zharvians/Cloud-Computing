<?php
declare(strict_types=1);

require 'config.php';

$uploadDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'uploads') . DIRECTORY_SEPARATOR;

// Validasi parameter
if (empty($_GET['file'])) {
    header('Location: index.php?status=error');
    exit;
}

// Ambil & amankan nama file
$fileName = basename((string)$_GET['file']);
$filePath = realpath($uploadDir . $fileName);

// Validasi file
if ($filePath === false || strpos($filePath, $uploadDir) !== 0 || !is_file($filePath)) {
    header('Location: index.php?status=error');
    exit;
}

// Hapus file fisik
if (unlink($filePath)) {

    // 🔥 Hapus juga dari database
    $stmt = $conn->prepare("DELETE FROM files WHERE name = ?");
    $stmt->bind_param("s", $fileName);
    $stmt->execute();

    header('Location: index.php?status=delete_success');
    exit;
}

header('Location: index.php?status=error');
exit;