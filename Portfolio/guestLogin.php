<?php
    session_start();

    include("dbHandler.php");
    /** @var PDO|null $dbHandler */

    if (!$dbHandler) {
        header('Location: login.php');
        exit();
    }

    $stmt = $dbHandler->prepare("SELECT `userID`, `username`, `permission`, `role` FROM `users` WHERE `username` = 'Guest' LIMIT 1;");
    $stmt->execute();
    $guest = $stmt->fetch(PDO::FETCH_ASSOC);

    // Self-healing: create the shared Guest account on first use (or if it was ever deleted).
    if (!$guest) {
        try {
            $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
            $insertStmt = $dbHandler->prepare(
                "INSERT INTO `users` (`username`, `psw`, `email`, `permission`, `role`) VALUES ('Guest', :psw, 'guest@portfolio.local', 'visitor', 'visitor');"
            );
            $insertStmt->execute([':psw' => $randomPassword]);
            $guest = ['userID' => $dbHandler->lastInsertId(), 'username' => 'Guest', 'permission' => 'visitor', 'role' => 'visitor'];
        } catch (Exception $ex) {
            // Lost a race with another concurrent guest login; the row exists now.
            $stmt->execute();
            $guest = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $guest['userID'];
    $_SESSION['username'] = $guest['username'];
    $_SESSION['permission'] = $guest['permission'];
    $_SESSION['role'] = $guest['role'];
    $_SESSION['logged_in'] = true;

    header('Location: web.php');
    exit();
?>
