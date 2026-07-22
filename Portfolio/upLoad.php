<?php
    session_start();

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: login.php");
        exit();
    }

    // Same condition web.php uses to decide who sees the "Submit Files" link.
    if (($_SESSION['role'] ?? null) !== 'admin' && ($_SESSION['permission'] ?? null) !== 'teacher') {
        http_response_code(403);
        exit('Forbidden.');
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrfToken = $_SESSION['csrf_token'];

    include("dbHandler.php");
    /** @var PDO|null $dbHandler */

    $categories = [];
    if ($dbHandler) {
        $stmt = $dbHandler->query(
            "SELECT `categoryID`, `parentCategoryID`, `yearNumber`, `name`, `slug`, `folderPath`
             FROM `categories`
             ORDER BY `yearNumber` IS NULL DESC, `yearNumber`, COALESCE(`parentCategoryID`, `categoryID`), `parentCategoryID` IS NOT NULL, `name`;"
        );
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $categoriesBySlug = [];
    foreach ($categories as $category) {
        $categoriesBySlug[$category['slug']] = $category;
    }

    $visitors = [];
    if ($dbHandler) {
        $stmt = $dbHandler->query("SELECT `userID`, `username` FROM `users` WHERE `role` = 'visitor' ORDER BY `username`;");
        $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    include("fileTypes.php");
    /** @var int $maxFileSize */
    /** @var string[] $fileAccepted */

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        if (
            empty($_POST['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            $message = ['type' => 'error', 'text' => 'Invalid request, please try again.'];
        } else {
            $title = trim(filter_input(INPUT_POST, "nameFile", FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
            $description = trim(filter_input(INPUT_POST, "description", FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
            $destination = $_POST['destination'] ?? '';
            $grantedUserIDs = array_map('intval', $_POST['access'] ?? []);
            $link = trim($_POST['link'] ?? '');

            $error = $_FILES['upload']['error'] ?? UPLOAD_ERR_NO_FILE;
            $fileName = $_FILES['upload']['name'] ?? '';
            $tmpName = $_FILES['upload']['tmp_name'] ?? '';
            $fileSize = $_FILES['upload']['size'] ?? 0;
            $hasFile = $error !== UPLOAD_ERR_NO_FILE;

            $validUrl = $link !== '' && filter_var($link, FILTER_VALIDATE_URL) !== false && in_array(parse_url($link, PHP_URL_SCHEME), ['http', 'https'], true);

            if ($title === '' || $description === '' || !isset($categoriesBySlug[$destination])) {
                $message = ['type' => 'error', 'text' => 'Please make sure all the fields are filled out.'];
            } elseif ($hasFile && $link !== '') {
                $message = ['type' => 'error', 'text' => 'Please provide either a file or a link, not both.'];
            } elseif (!$hasFile && $link === '') {
                $message = ['type' => 'error', 'text' => 'Please provide either a file or a link.'];
            } elseif ($link !== '' && !$validUrl) {
                $message = ['type' => 'error', 'text' => 'Please enter a valid http:// or https:// link.'];
            } elseif ($hasFile && in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                $message = ['type' => 'error', 'text' => 'The file is too large (max 100 MB).'];
            } elseif ($hasFile && $error !== UPLOAD_ERR_OK) {
                $message = ['type' => 'error', 'text' => 'Something went wrong during the upload process, try again later.'];
            } elseif ($hasFile && $fileSize > $maxFileSize) {
                $message = ['type' => 'error', 'text' => 'The file is too large (max 100 MB).'];
            } else {
                $category = $categoriesBySlug[$destination];
                $storedFilename = null;
                $originalFilename = null;
                $destinationPath = null;

                if ($validUrl) {
                    $moveOk = true;
                } else {
                    $fileType = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($fileType, $tmpName);

                    if (!in_array($mimeType, $fileAccepted, true)) {
                        $message = ['type' => 'error', 'text' => 'Please make sure the filetype of the file you want to submit is accepted.'];
                        $moveOk = false;
                    } else {
                        $originalFilename = basename($fileName);
                        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
                        $storedFilename = bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . $extension : '');
                        $destinationPath = rtrim($category['folderPath'], '/') . '/' . $storedFilename;

                        if (!is_dir($category['folderPath']) || !move_uploaded_file($tmpName, $destinationPath)) {
                            $message = ['type' => 'error', 'text' => 'Something went wrong during the upload process, try again later.'];
                            $moveOk = false;
                        } else {
                            $moveOk = true;
                        }
                    }
                }

                if ($moveOk) {
                    try {
                        $dbHandler->beginTransaction();

                        $stmt = $dbHandler->prepare(
                            "INSERT INTO `files` (`categoryID`, `title`, `description`, `storedFilename`, `originalFilename`, `linkUrl`, `uploaderUserID`)
                             VALUES (:categoryID, :title, :description, :storedFilename, :originalFilename, :linkUrl, :uploaderUserID);"
                        );
                        $stmt->execute([
                            ':categoryID' => $category['categoryID'],
                            ':title' => $title,
                            ':description' => $description,
                            ':storedFilename' => $storedFilename,
                            ':originalFilename' => $originalFilename,
                            ':linkUrl' => $validUrl ? $link : null,
                            ':uploaderUserID' => $_SESSION['user_id'],
                        ]);
                        $fileID = $dbHandler->lastInsertId();

                        if ($grantedUserIDs) {
                            $validVisitorIDs = array_column($visitors, 'userID');
                            $accessStmt = $dbHandler->prepare("INSERT INTO `file_access` (`fileID`, `userID`) VALUES (:fileID, :userID);");
                            foreach (array_intersect($grantedUserIDs, $validVisitorIDs) as $visitorID) {
                                $accessStmt->execute([':fileID' => $fileID, ':userID' => $visitorID]);
                            }
                        }

                        $dbHandler->commit();
                        $message = ['type' => 'success', 'text' => 'Upload was successful, you can now view it on the website.'];
                    } catch (Exception $ex) {
                        $dbHandler->rollBack();
                        if ($storedFilename) {
                            unlink($destinationPath);
                        }
                        error_log($ex->getMessage());
                        $message = ['type' => 'error', 'text' => 'Something went wrong saving the file, try again later.'];
                    }
                }
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./Css/variables.css">
    <link rel="stylesheet" href="./Css/header.css">
    <link rel="stylesheet" href="./Css/upload.css">
    <title>Upload Files</title>
</head>
<body class="appleDark">
    <?php if (isset($message)): ?>
        <div class="<?php echo $message['type']; ?>"><?php echo htmlspecialchars($message['text']); ?></div>
    <?php endif; ?>

    <?php include 'header.php'; ?>

    <div class="centerWrap">
    <div class="uploadContainer">
        <a href="web.php" class="backLink">&larr; Back to Portfolio</a>
        <h1>Upload A File/Link</h1>
        <p class="hint">Provide either a file or a link below, not both.</p>
        <form action="upLoad.php" method="POST" enctype="multipart/form-data" class="form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

            <p>
                <label for="nameFile">Title:</label>
                <input type="text" name="nameFile" id="nameFile" required>
            </p>

            <p>
                <label for="description">Description:</label>
                <input type="text" name="description" id="description" required>
            </p>

            <p>
                <label for="upload">File:</label>
                <input type="file" name="upload" id="upload">
            </p>

            <p>
                <label for="link">Link:</label>
                <input type="text" name="link" id="link" placeholder="https://...">
            </p>

            <p>
                <label for="destination">Category:</label>
                <select name="destination" id="destination" required>
                    <option value="" selected disabled>Pick the appropriate category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['slug']); ?>">
                            <?php echo htmlspecialchars(($category['parentCategoryID'] ? '— ' : (($category['yearNumber'] ? 'Year ' . $category['yearNumber'] . ' - ' : ''))) . $category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <fieldset class="accessList">
                <legend>Grant access to visitors</legend>
                <?php if (!$visitors): ?>
                    <p class="hint">No visitor accounts yet.</p>
                <?php endif; ?>
                <?php foreach ($visitors as $visitor): ?>
                    <label class="accessOption">
                        <input type="checkbox" name="access[]" value="<?php echo (int) $visitor['userID']; ?>">
                        <?php echo htmlspecialchars($visitor['username']); ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <p>
                <input type="submit" name="Submit" id="Submit" value="Upload">
            </p>
        </form>
    </div>
    </div>
</body>
</html>
