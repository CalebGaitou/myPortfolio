<?php
    session_start();

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: login.php");
        exit();
    }

    include("dbHandler.php");
    /** @var PDO|null $dbHandler */

    $fileID = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$dbHandler || !$fileID) {
        http_response_code(404);
        exit('File not found.');
    }

    $role = $_SESSION['role'] ?? null;
    $userID = $_SESSION['user_id'] ?? null;

    $stmt = $dbHandler->prepare(
        "SELECT f.storedFilename, f.originalFilename, f.linkUrl, c.folderPath
         FROM files f
         JOIN categories c ON c.categoryID = f.categoryID
         WHERE f.fileID = :fileID;"
    );
    $stmt->execute([':fileID' => $fileID]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$file) {
        http_response_code(404);
        exit('File not found.');
    }

    if ($role !== 'admin') {
        $accessStmt = $dbHandler->prepare("SELECT 1 FROM file_access WHERE fileID = :fileID AND userID = :userID;");
        $accessStmt->execute([':fileID' => $fileID, ':userID' => $userID]);
        if (!$accessStmt->fetchColumn()) {
            http_response_code(403);
            exit('You do not have access to this file.');
        }
    }

    if ($file['linkUrl']) {
        header('Location: ' . $file['linkUrl']);
        exit();
    }

    $path = rtrim($file['folderPath'], '/') . '/' . $file['storedFilename'];

    if (!is_file($path)) {
        http_response_code(404);
        exit('File not found.');
    }

    $mimeType = mime_content_type($path) ?: 'application/octet-stream';
    if (str_starts_with($mimeType, 'text/')) {
        // Browsers don't have a native viewer for things like text/x-java and
        // just download them instead of showing the code; text/plain displays inline.
        $mimeType = 'text/plain; charset=UTF-8';
    }
    $safeName = preg_replace('/[\r\n"]/', '', $file['originalFilename']);

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: inline; filename="' . $safeName . '"');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit();
?>
