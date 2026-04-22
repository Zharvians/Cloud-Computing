<?php
declare(strict_types=1);

require 'config.php';

$uploadDir = realpath(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;

// Pastikan folder uploads ada
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        header('Location: index.php?status=error');
        exit;
    }
}

// Validasi request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['fileToUpload'])) {
    header('Location: index.php?status=error');
    exit;
}

$file = $_FILES['fileToUpload'];

// Cek error upload
if ($file['error'] !== UPLOAD_ERR_OK) {
    header('Location: index.php?status=error');
    exit;
}

// Batas ukuran file: 10 MB
$maxFileSize = 10 * 1024 * 1024;
if ((int)$file['size'] > $maxFileSize) {
    header('Location: index.php?status=error');
    exit;
}

// Validasi MIME type (anti file berbahaya)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
if ($finfo) {
    finfo_close($finfo);
}

// Whitelist file yang diizinkan
$allowedTypes = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'text/plain',
    'application/zip'
];

if (!in_array($mimeType, $allowedTypes, true)) {
    header('Location: index.php?status=error');
    exit;
}

// Ambil nama file dan sanitize
$originalName = (string)$file['name'];
$baseName = basename($originalName);
$sanitizedFileName = preg_replace('/[^A-Za-z0-9._-]/', '_', $baseName);

if (!$sanitizedFileName) {
    header('Location: index.php?status=error');
    exit;
}

// Hindari overwrite
$targetPath = $uploadDir . $sanitizedFileName;
if (file_exists($targetPath)) {
    $fileInfo = pathinfo($sanitizedFileName);
    $nameOnly = $fileInfo['filename'] ?? 'file';
    $extension = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';
    $sanitizedFileName = $nameOnly . '_' . date('Ymd_His') . $extension;
    $targetPath = $uploadDir . $sanitizedFileName;
}

// Pindahkan file
if (move_uploaded_file($file['tmp_name'], $targetPath)) {

    // Simpan ke database
    $stmt = $conn->prepare("INSERT INTO files (name, size) VALUES (?, ?)");
    $stmt->bind_param("si", $sanitizedFileName, $file['size']);
    $stmt->execute();

    header('Location: index.php?status=upload_success');
    exit;
}

// Fallback error
header('Location: index.php?status=error');
exit;