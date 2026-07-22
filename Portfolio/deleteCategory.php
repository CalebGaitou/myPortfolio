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

    include("dbHandler.php");
    /** @var PDO|null $dbHandler */

    function removeEmptyFolderTree(string $path): void {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $itemPath = $path . '/' . $item;
            if (is_dir($itemPath)) {
                removeEmptyFolderTree($itemPath);
            }
        }
        @rmdir($path); // only succeeds if actually empty; leaves anything unexpected in place
    }

    $categoryID = filter_input(INPUT_POST, 'categoryID', FILTER_VALIDATE_INT);

    if (!$dbHandler || !$categoryID) {
        header("Location: manageModules.php");
        exit();
    }

    $stmt = $dbHandler->prepare("SELECT `folderPath` FROM `categories` WHERE `categoryID` = :id;");
    $stmt->execute([':id' => $categoryID]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        header("Location: manageModules.php");
        exit();
    }

    $fileCountStmt = $dbHandler->prepare(
        "SELECT COUNT(*) FROM `files`
         WHERE `categoryID` = :id
            OR `categoryID` IN (SELECT `categoryID` FROM `categories` WHERE `parentCategoryID` = :id);"
    );
    $fileCountStmt->execute([':id' => $categoryID]);

    if ((int) $fileCountStmt->fetchColumn() > 0) {
        header("Location: manageModules.php?deleteError=hasFiles");
        exit();
    }

    try {
        $deleteStmt = $dbHandler->prepare("DELETE FROM `categories` WHERE `categoryID` = :id;");
        $deleteStmt->execute([':id' => $categoryID]);

        removeEmptyFolderTree(rtrim($category['folderPath'], '/'));

        header("Location: manageModules.php?deleted=1");
        exit();
    } catch (Exception $ex) {
        error_log($ex->getMessage());
        header("Location: manageModules.php?deleteError=1");
        exit();
    }
?>