<?php
    // Shared between upLoad.php and uploadFolder.php so the accepted types
    // and size limit can't drift out of sync between the two upload paths.

    $maxFileSize = 100 * 1024 * 1024; // 100 MB

    $fileAccepted = [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/pdf',
        'image/png',
        'image/jpeg',
        'image/gif',
        'text/javascript',
        'text/x-java-source',
        'text/x-java',
        'application/zip',
        'application/x-zip-compressed',
    ];
?>
