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

    $message = trim($_POST['message'] ?? '');

    if ($dbHandler && $message !== '') {
        try {
            $dbHandler->beginTransaction();

            $dbHandler->exec("DELETE FROM `announcements`;");

            $stmt = $dbHandler->prepare(
                "INSERT INTO `announcements` (`message`, `postedByUserID`) VALUES (:message, :postedByUserID);"
            );
            $stmt->execute([
                ':message' => $message,
                ':postedByUserID' => $_SESSION['user_id'],
            ]);

            $dbHandler->commit();
        } catch (Exception $ex) {
            $dbHandler->rollBack();
            error_log($ex->getMessage());
            header("Location: web.php?error=1");
            exit();
        }
    }

    header("Location: web.php");
    exit();
?>
