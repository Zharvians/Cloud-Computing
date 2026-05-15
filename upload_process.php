<!-- upload_process.php -->

<?php
declare(strict_types=1);

require 'config.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

$uploadDir =
realpath(__DIR__) .
DIRECTORY_SEPARATOR .
'uploads' .
DIRECTORY_SEPARATOR;

/* Pastikan folder uploads ada */

if (!is_dir($uploadDir)) {

    if (
        !mkdir($uploadDir, 0755, true)
        && !is_dir($uploadDir)
    ) {

        header('Location: index.php?status=error');
        exit;
    }
}

/* Validasi Request */

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    || !isset($_FILES['fileToUpload'])
) {

    header('Location: index.php?status=error');
    exit;
}

$file = $_FILES['fileToUpload'];

/* Cek Upload Error */

if ($file['error'] !== UPLOAD_ERR_OK) {

    header('Location: index.php?status=error');
    exit;
}

/* Max 10MB */

$maxFileSize =
10 * 1024 * 1024;

if ((int)$file['size'] > $maxFileSize) {

    header('Location: index.php?status=error');
    exit;
}

/* MIME Validation */

$finfo =
finfo_open(FILEINFO_MIME_TYPE);

$mimeType =
$finfo
? finfo_file($finfo, $file['tmp_name'])
: '';

if ($finfo) {
    finfo_close($finfo);
}

/* Allowed Types */

$allowedTypes = [

    // IMAGE
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',

    // PDF
    'application/pdf',

    // WORD
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',

    // EXCEL
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

    // POWERPOINT
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',

    // CSV
    'text/csv',

    // TXT
    'text/plain',

    // ZIP
    'application/zip',
    'application/x-rar-compressed',

    // VIDEO
    'video/mp4',
    'video/webm'
];

if (!in_array($mimeType, $allowedTypes, true)) {

    header('Location: index.php?status=error');
    exit;
}

/* Sanitize Name */

$originalName =
(string)$file['name'];

$baseName =
basename($originalName);

$sanitizedFileName =
preg_replace(
    '/[^A-Za-z0-9._-]/',
    '_',
    $baseName
);

if (!$sanitizedFileName) {

    header('Location: index.php?status=error');
    exit;
}

/* Hindari overwrite */

$targetPath =
$uploadDir .
$sanitizedFileName;

if (file_exists($targetPath)) {

    $fileInfo =
    pathinfo($sanitizedFileName);

    $nameOnly =
    $fileInfo['filename'] ?? 'file';

    $extension =
    isset($fileInfo['extension'])
    ? '.' . $fileInfo['extension']
    : '';

    $sanitizedFileName =
    $nameOnly .
    '_' .
    date('Ymd_His') .
    $extension;

    $targetPath =
    $uploadDir .
    $sanitizedFileName;
}

/* Upload */

if (
    move_uploaded_file(
        $file['tmp_name'],
        $targetPath
    )
) {

    $userId =
    $_SESSION['user']['id'];

    $stmt =
    $conn->prepare("
        INSERT INTO files
        (name, size, user_id)
        VALUES (?, ?, ?)
    ");

    $stmt->bind_param(
        "sii",
        $sanitizedFileName,
        $file['size'],
        $userId
    );

    $stmt->execute();

    header(
        'Location: index.php?status=upload_success'
    );

    exit;
}

/* Error fallback */

header('Location: index.php?status=error');
exit;
