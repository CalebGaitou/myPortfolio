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

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrfToken = $_SESSION['csrf_token'];

    include("dbHandler.php");
    /** @var PDO|null $dbHandler */

    $fileID = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'fileID', FILTER_VALIDATE_INT);

    if (!$dbHandler || !$fileID) {
        http_response_code(404);
        exit('File not found.');
    }

    $stmt = $dbHandler->prepare("SELECT `fileID`, `title` FROM `files` WHERE `fileID` = :fileID;");
    $stmt->execute([':fileID' => $fileID]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$file) {
        http_response_code(404);
        exit('File not found.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (
            empty($_POST['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            $message = ['type' => 'error', 'text' => 'Invalid request, please try again.'];
        } else {
            $visitorRows = $dbHandler->prepare("SELECT `userID` FROM `users` WHERE `role` = 'visitor';");
            $visitorRows->execute();
            $validVisitorIDs = $visitorRows->fetchAll(PDO::FETCH_COLUMN);

            $selectedUserIDs = array_map('intval', $_POST['access'] ?? []);
            $grantedUserIDs = array_intersect($selectedUserIDs, $validVisitorIDs);

            try {
                $dbHandler->beginTransaction();

                $deleteStmt = $dbHandler->prepare("DELETE FROM `file_access` WHERE `fileID` = :fileID;");
                $deleteStmt->execute([':fileID' => $fileID]);

                if ($grantedUserIDs) {
                    $insertStmt = $dbHandler->prepare("INSERT INTO `file_access` (`fileID`, `userID`) VALUES (:fileID, :userID);");
                    foreach ($grantedUserIDs as $userID) {
                        $insertStmt->execute([':fileID' => $fileID, ':userID' => $userID]);
                    }
                }

                $dbHandler->commit();
                $message = ['type' => 'success', 'text' => 'Access updated.'];
            } catch (Exception $ex) {
                $dbHandler->rollBack();
                error_log($ex->getMessage());
                $message = ['type' => 'error', 'text' => 'Something went wrong updating access.'];
            }
        }
    }

    $stmt = $dbHandler->prepare(
        "SELECT u.`userID`, u.`username`, (fa.`userID` IS NOT NULL) AS hasAccess
         FROM `users` u
         LEFT JOIN `file_access` fa ON fa.`userID` = u.`userID` AND fa.`fileID` = :fileID
         WHERE u.`role` = 'visitor'
         ORDER BY u.`username`;"
    );
    $stmt->execute([':fileID' => $fileID]);
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./Css/variables.css">
    <link rel="stylesheet" href="./Css/header.css">
    <link rel="stylesheet" href="./Css/upload.css">
    <title>Manage Access - Portfolio</title>
</head>
<body class="appleDark">
    <?php if (isset($message)): ?>
        <div class="<?php echo $message['type']; ?>"><?php echo htmlspecialchars($message['text']); ?></div>
    <?php endif; ?>

    <?php include 'header.php'; ?>

    <div class="centerWrap">
    <div class="uploadContainer">
        <a href="year1.php" class="backLink">&larr; Back to Year 1</a>
        <h1>Manage Access</h1>
        <p class="hint"><?php echo htmlspecialchars($file['title']); ?></p>

        <form action="manageAccess.php" method="POST" class="form">
            <input type="hidden" name="fileID" value="<?php echo (int) $file['fileID']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

            <fieldset class="accessList">
                <legend>Visitors with access</legend>
                <?php if (!$visitors): ?>
                    <p class="hint">No visitor accounts yet.</p>
                <?php endif; ?>
                <?php foreach ($visitors as $visitor): ?>
                    <label class="accessOption">
                        <input type="checkbox" name="access[]" value="<?php echo (int) $visitor['userID']; ?>" <?php echo $visitor['hasAccess'] ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($visitor['username']); ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <p>
                <input type="submit" id="Submit" value="Save Access">
            </p>
        </form>
    </div>
    </div>
</body>
</html>
