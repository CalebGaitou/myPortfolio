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

    include("categoryHelpers.php");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (
            empty($_POST['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            $message = ['type' => 'error', 'text' => 'Invalid request, please try again.'];
        } elseif (!$dbHandler) {
            $message = ['type' => 'error', 'text' => 'Something went wrong, please try again.'];
        } else {
            $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
            $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4]]);
            $parentCategoryID = filter_input(INPUT_POST, 'parentCategoryID', FILTER_VALIDATE_INT) ?: null;
            $slug = slugifyCategoryName($name);

            $parent = null;
            if ($parentCategoryID) {
                $parentStmt = $dbHandler->prepare("SELECT `categoryID`, `yearNumber`, `folderPath`, `parentCategoryID` FROM `categories` WHERE `categoryID` = :id;");
                $parentStmt->execute([':id' => $parentCategoryID]);
                $parent = $parentStmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($name === '' || $slug === '' || (!$parentCategoryID && !$year)) {
                $message = ['type' => 'error', 'text' => 'Please enter a category name, and either pick a year or a parent folder.'];
            } elseif ($parentCategoryID && !$parent) {
                $message = ['type' => 'error', 'text' => 'That parent folder no longer exists.'];
            } elseif ($parentCategoryID && $parent['parentCategoryID'] !== null) {
                $message = ['type' => 'error', 'text' => 'A sub-folder cannot itself contain another sub-folder.'];
            } else {
                $checkStmt = $dbHandler->prepare("SELECT 1 FROM `categories` WHERE `slug` = :slug;");
                $checkStmt->execute([':slug' => $slug]);

                if ($checkStmt->fetchColumn()) {
                    $message = ['type' => 'error', 'text' => 'A category with that name already exists.'];
                } else {
                    if ($parent) {
                        $year = (int) $parent['yearNumber'];
                        $folderPath = rtrim($parent['folderPath'], '/') . '/' . ucfirst($slug) . '/';
                    } else {
                        $folderPath = "Files/Year{$year}/" . ucfirst($slug) . '/';
                    }

                    if (!is_dir($folderPath) && !mkdir($folderPath, 0755, true)) {
                        $message = ['type' => 'error', 'text' => 'Could not create the folder for this category.'];
                    } else {
                        try {
                            $stmt = $dbHandler->prepare(
                                "INSERT INTO `categories` (`parentCategoryID`, `yearNumber`, `name`, `slug`, `folderPath`) VALUES (:parentCategoryID, :yearNumber, :name, :slug, :folderPath);"
                            );
                            $stmt->execute([
                                ':parentCategoryID' => $parentCategoryID,
                                ':yearNumber' => $year,
                                ':name' => $name,
                                ':slug' => $slug,
                                ':folderPath' => $folderPath,
                            ]);
                            $message = ['type' => 'success', 'text' => 'Category added.'];
                        } catch (Exception $ex) {
                            error_log($ex->getMessage());
                            $message = ['type' => 'error', 'text' => 'Something went wrong saving the category.'];
                        }
                    }
                }
            }
        }
    }

    $categories = [];
    if ($dbHandler) {
        $stmt = $dbHandler->query(
            "SELECT c.`categoryID`, c.`parentCategoryID`, c.`yearNumber`, c.`name`, c.`slug`, c.`folderPath`, parent.`name` AS parentName
             FROM `categories` c
             LEFT JOIN `categories` parent ON parent.`categoryID` = c.`parentCategoryID`
             ORDER BY c.`yearNumber` IS NULL DESC, c.`yearNumber`, COALESCE(c.`parentCategoryID`, c.`categoryID`), c.`parentCategoryID` IS NOT NULL, c.`name`;"
        );
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $topLevelCategories = array_values(array_filter($categories, fn($c) => $c['parentCategoryID'] === null));

    if (!isset($message)) {
        if (isset($_GET['deleted'])) {
            $message = ['type' => 'success', 'text' => 'Category deleted.'];
        } elseif (isset($_GET['deleteError']) && $_GET['deleteError'] === 'hasFiles') {
            $message = ['type' => 'error', 'text' => 'That category (or its sub-folder) still has files in it. Move or delete those first.'];
        } elseif (isset($_GET['deleteError'])) {
            $message = ['type' => 'error', 'text' => 'Something went wrong deleting the category.'];
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
    <link rel="stylesheet" href="./Css/web.css">
    <link rel="stylesheet" href="./Css/upload.css">
    <title>Manage Modules - Portfolio</title>
</head>

<body class="appleDark">
    <?php if (isset($message)): ?>
        <div class="<?php echo $message['type']; ?>"><?php echo htmlspecialchars($message['text']); ?></div>
    <?php endif; ?>

    <?php include 'header.php'; ?>

    <div class="mainContainer">
        <main class="content">
            <a href="web.php" class="backLink">&larr; Back to Portfolio</a>
            <h1>Manage Modules</h1>

            <div class="uploadContainer" style="width: auto; max-width: 420px;">
                <form action="manageModules.php" method="POST" class="form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                    <p>
                        <label for="name">Category Name:</label>
                        <input type="text" name="name" id="name" required>
                    </p>

                    <p>
                        <label for="parentCategoryID">Parent Folder:</label>
                        <select name="parentCategoryID" id="parentCategoryID">
                            <option value="" selected>None — top-level category</option>
                            <?php foreach ($topLevelCategories as $parent): ?>
                                <option value="<?php echo (int) $parent['categoryID']; ?>">
                                    <?php echo htmlspecialchars(($parent['yearNumber'] ? 'Year ' . $parent['yearNumber'] . ' - ' : '') . $parent['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </p>

                    <p>
                        <label for="year">Year (ignored if a parent folder is picked above):</label>
                        <select name="year" id="year">
                            <option value="" selected disabled>Pick a year</option>
                            <option value="1">Year 1</option>
                            <option value="2">Year 2</option>
                            <option value="3">Year 3</option>
                            <option value="4">Year 4</option>
                        </select>
                    </p>

                    <p>
                        <input type="submit" id="Submit" value="Add Category">
                    </p>
                </form>
            </div>

            <div class="tableContainer">
                <table class="filesTable">
                    <tr>
                        <th>Year</th>
                        <th>Name</th>
                        <th>Parent</th>
                        <th>Folder</th>
                        <th>Edit</th>
                        <th>Delete</th>
                    </tr>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td class="allCells"><?php echo $category['yearNumber'] ? 'Year ' . (int) $category['yearNumber'] : '-'; ?></td>
                            <td class="allCells"><?php echo ($category['parentCategoryID'] ? '— ' : '') . htmlspecialchars($category['name']); ?></td>
                            <td class="allCells"><?php echo $category['parentName'] ? htmlspecialchars($category['parentName']) : '-'; ?></td>
                            <td class="allCells"><?php echo htmlspecialchars($category['folderPath']); ?></td>
                            <td class="allCells"><a href="editCategory.php?id=<?php echo (int) $category['categoryID']; ?>">Edit</a></td>
                            <td class="allCells">
                                <form action="deleteCategory.php" method="POST" class="deleteForm" onsubmit="return confirm('Delete this category? Its files (if any) must already be moved or deleted.');">
                                    <input type="hidden" name="categoryID" value="<?php echo (int) $category['categoryID']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <button type="submit" class="deleteBtn">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
