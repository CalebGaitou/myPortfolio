<?php
    session_start();

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: login.php");
        exit();
    }

    $yearNumber = filter_input(INPUT_GET, 'y', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4]]);

    if (!$yearNumber) {
        header("Location: web.php");
        exit();
    }

    $permission = htmlspecialchars($_SESSION['permission'] ?? '');
    $role = $_SESSION['role'] ?? '';
    $userID = $_SESSION['user_id'] ?? null;
    $canDelete = $role === 'admin' && $permission === 'admin';

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrfToken = $_SESSION['csrf_token'];

    include("dbHandler.php");
    /** @var PDO|null $dbHandler */

    $files = [];
    if ($dbHandler) {
        if ($role === 'admin') {
            $stmt = $dbHandler->prepare(
                "SELECT f.fileID, f.title, f.description, f.uploadedAt, f.linkUrl,
                        CASE WHEN c.parentCategoryID IS NOT NULL THEN CONCAT(parentCat.name, ' / ', c.name) ELSE c.name END AS categoryName,
                        u.username AS uploaderName
                 FROM files f
                 JOIN categories c ON c.categoryID = f.categoryID
                 LEFT JOIN categories parentCat ON parentCat.categoryID = c.parentCategoryID
                 JOIN users u ON u.userID = f.uploaderUserID
                 WHERE c.yearNumber = :yearNumber
                 ORDER BY f.uploadedAt DESC;"
            );
            $stmt->execute([':yearNumber' => $yearNumber]);
        } else {
            $stmt = $dbHandler->prepare(
                "SELECT f.fileID, f.title, f.description, f.uploadedAt, f.linkUrl,
                        CASE WHEN c.parentCategoryID IS NOT NULL THEN CONCAT(parentCat.name, ' / ', c.name) ELSE c.name END AS categoryName,
                        u.username AS uploaderName
                 FROM files f
                 JOIN categories c ON c.categoryID = f.categoryID
                 LEFT JOIN categories parentCat ON parentCat.categoryID = c.parentCategoryID
                 JOIN users u ON u.userID = f.uploaderUserID
                 JOIN file_access fa ON fa.fileID = f.fileID AND fa.userID = :userID
                 WHERE c.yearNumber = :yearNumber
                 ORDER BY f.uploadedAt DESC;"
            );
            $stmt->execute([':userID' => $userID, ':yearNumber' => $yearNumber]);
        }
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./Css/variables.css">
    <link rel="stylesheet" href="./Css/header.css">
    <link rel="stylesheet" href="./Css/web.css">
    <title>Year <?php echo (int) $yearNumber; ?> - Portfolio</title>
</head>

<body class="appleDark">
    <?php include 'header.php'; ?>

    <div class="mainContainer">
        <aside class="sidebar">
            <div class="sidebarSection">
                <h3>Navigation</h3>
                <ul class="sidebarList">
                    <li><a href="web.php">&larr; Back to Portfolio</a></li>
                    <?php if ($role === 'admin' || $permission === 'teacher'): ?>
                        <li><a href="upLoad.php">Submit Files</a></li>
                    <?php endif; ?>
                    <?php if ($canDelete): ?>
                        <li><a href="manageModules.php">Manage Modules</a></li>
                    <?php endif; ?>
                    <li><a href="calendar.php">Calendar</a></li>
                    <li><a href="about.php">About Me</a></li>
                </ul>
            </div>
        </aside>

        <main class="content">
            <h1>Year <?php echo (int) $yearNumber; ?></h1>

            <?php if (!$files): ?>
                <p class="moduleMessage">No files to show yet.</p>
            <?php else: ?>
                <div class="tableContainer">
                    <table class="filesTable">
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Uploaded By</th>
                            <th>Date</th>
                            <th>View</th>
                            <?php if ($canDelete): ?><th>Access</th><th>Delete</th><?php endif; ?>
                        </tr>
                        <?php foreach ($files as $file): ?>
                            <tr>
                                <td class="allCells" title="<?php echo htmlspecialchars($file['title']); ?>"><?php echo htmlspecialchars($file['title']); ?></td>
                                <td class="allCells" title="<?php echo htmlspecialchars($file['description']); ?>"><?php echo htmlspecialchars($file['description']); ?></td>
                                <td class="allCells"><?php echo htmlspecialchars($file['categoryName']); ?></td>
                                <td class="allCells"><?php echo htmlspecialchars($file['uploaderName']); ?></td>
                                <td class="allCells"><?php echo htmlspecialchars((new DateTime($file['uploadedAt']))->format('Y-m-d H:i')); ?></td>
                                <td class="allCells"><a href="viewFile.php?id=<?php echo (int) $file['fileID']; ?>"><?php echo $file['linkUrl'] ? 'Open Link' : 'View'; ?></a></td>
                                <?php if ($canDelete): ?>
                                    <td class="allCells"><a href="manageAccess.php?id=<?php echo (int) $file['fileID']; ?>">Access</a></td>
                                    <td class="allCells">
                                        <form action="deleteFile.php" method="POST" class="deleteForm" onsubmit="return confirm('Delete this file? This cannot be undone.');">
                                            <input type="hidden" name="fileID" value="<?php echo (int) $file['fileID']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="redirect" value="year.php">
                                            <input type="hidden" name="redirectYear" value="<?php echo (int) $yearNumber; ?>">
                                            <button type="submit" class="deleteBtn">Delete</button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
