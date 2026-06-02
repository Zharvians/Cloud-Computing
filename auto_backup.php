<?php

$backupRoot = __DIR__ . '/backup';

if (!is_dir($backupRoot)) {
    mkdir($backupRoot, 0777, true);
}

$folders = glob($backupRoot . '/*', GLOB_ONLYDIR);

$needBackup = false;

if (empty($folders)) {

    $needBackup = true;

} else {

    rsort($folders);

    $latestFolder = basename($folders[0]);

    $lastBackupDate = strtotime($latestFolder);

    if (
        time() - $lastBackupDate
        >= (7 * 24 * 60 * 60)
    ) {

        $needBackup = true;

    }

}

if ($needBackup) {

    include __DIR__ . '/backup_system.php';

}