<?php
    session_start();

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: login.php");
        exit();
    }

    if (($_SESSION['role'] ?? null) !== 'admin' || ($_SESSION['permission'] ?? null) !== 'admin') {
        http_response_code(403);
        exit('Forbidden.');
    }

    if (
        $_SERVER['REQUEST_METHOD'] !== 'POST' ||
        empty($_POST['csrf_token']) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        exit('Invalid request.');
    }

    $allowedRedirects = ['web.php', 'year1.php', 'year2.php'];
    $redirect = $_POST['redirect'] ?? 'web.php';
    if (!in_array($redirect, $allowedRedirects, true)) {
        $redirect = 'web.php';
    }

    include("dbHandler.php");
    /** @var PDO|null $dbHandler */

    $fileID = filter_input(INPUT_POST, 'fileID', FILTER_VALIDATE_INT);

    if ($dbHandler && $fileID) {
        try {
            $stmt = $dbHandler->prepare(
                "SELECT `storedFilename`, `folderPath`
                 FROM `files` f
                 JOIN `categories` c ON c.`categoryID` = f.`categoryID`
                 WHERE f.`fileID` = :fileID;"
            );
            $stmt->execute([':fileID' => $fileID]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($file) {
                $deleteStmt = $dbHandler->prepare("DELETE FROM `files` WHERE `fileID` = :fileID;");
                $deleteStmt->execute([':fileID' => $fileID]);

                if ($file['storedFilename']) {
                    $path = rtrim($file['folderPath'], '/') . '/' . $file['storedFilename'];
                    if (is_file($path)) {
                        unlink($path);
                    }
                }
            }
        } catch (Exception $ex) {
            error_log($ex->getMessage());
            header("Location: $redirect?error=1");
            exit();
        }
    }

    header("Location: $redirect");
    exit();
?>
