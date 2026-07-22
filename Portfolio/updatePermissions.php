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

    $id = $_POST['userID'];
    $permission = $_POST['setPermission'];

    $allowedPermissions = ['teacher', 'student', 'employer', 'visitor'];

    if($dbHandler && $id && in_array($permission, $allowedPermissions, true)){
        try{
            $stmt = $dbHandler->prepare("UPDATE `users` SET `permission` = :setPermission WHERE `userID` = :id;");
            $stmt->execute([
                ':setPermission' => $permission,
                ':id' => $id
            ]);
            $stmt->closeCursor();
            header("Location: web.php");
            exit();
        }
        catch(Exception $ex){
            error_log($ex->getMessage());
            header("Location: web.php?error=1");
            exit();
        }
    }
?>
