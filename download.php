<?php
declare(strict_types=1);

$uploadDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'uploads') . DIRECTORY_SEPARATOR;

if (empty($_GET['file'])) {
    http_response_code(400);
    exit('Parameter file tidak valid.');
}

$fileName = basename((string)$_GET['file']);
$filePath = realpath($uploadDir . $fileName);

if ($filePath === false || strpos($filePath, $uploadDir) !== 0 || !is_file($filePath)) {
    http_response_code(404);
    exit('File tidak ditemukan.');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = $finfo ? finfo_file($finfo, $filePath) : 'application/octet-stream';
if ($finfo) finfo_close($finfo);

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . rawurlencode($fileName) . '"');
header('Content-Length: ' . filesize($filePath));

readfile($filePath);
exit;