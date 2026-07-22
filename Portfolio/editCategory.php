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

    if (!$dbHandler) {
        http_response_code(500);
        exit('Something went wrong, please try again.');
    }

    $categoryID = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'categoryID', FILTER_VALIDATE_INT);

    if (!$categoryID) {
        http_response_code(404);
        exit('Category not found.');
    }

    $stmt = $dbHandler->prepare("SELECT `categoryID`, `parentCategoryID`, `yearNumber`, `name`, `slug`, `folderPath` FROM `categories` WHERE `categoryID` = :id;");
    $stmt->execute([':id' => $categoryID]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        http_response_code(404);
        exit('Category not found.');
    }

    $childCountStmt = $dbHandler->prepare("SELECT COUNT(*) FROM `categories` WHERE `parentCategoryID` = :id;");
    $childCountStmt->execute([':id' => $categoryID]);
    $hasChildren = (int) $childCountStmt->fetchColumn() > 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (
            empty($_POST['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            $message = ['type' => 'error', 'text' => 'Invalid request, please try again.'];
        } else {
            $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
            $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4]]);
            $parentCategoryID = filter_input(INPUT_POST, 'parentCategoryID', FILTER_VALIDATE_INT) ?: null;
            $newSlug = slugifyCategoryName($name);

            $parent = null;
            if ($parentCategoryID) {
                $parentStmt = $dbHandler->prepare("SELECT `categoryID`, `yearNumber`, `slug`, `folderPath`, `parentCategoryID` FROM `categories` WHERE `categoryID` = :id;");
                $parentStmt->execute([':id' => $parentCategoryID]);
                $parent = $parentStmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($name === '' || $newSlug === '' || (!$parentCategoryID && !$year)) {
                $message = ['type' => 'error', 'text' => 'Please enter a category name, and either pick a year or a parent folder.'];
            } elseif ($parentCategoryID === $categoryID) {
                $message = ['type' => 'error', 'text' => 'A category cannot be its own parent.'];
            } elseif ($parentCategoryID && $hasChildren) {
                $message = ['type' => 'error', 'text' => 'This category has sub-folders of its own, so it can\'t be nested inside another category.'];
            } elseif ($parentCategoryID && (!$parent || $parent['parentCategoryID'] !== null)) {
                $message = ['type' => 'error', 'text' => 'That parent folder is not valid (it must be a top-level category).'];
            } else {
                $slugCheckStmt = $dbHandler->prepare("SELECT 1 FROM `categories` WHERE `slug` = :slug AND `categoryID` != :id;");
                $slugCheckStmt->execute([':slug' => $newSlug, ':id' => $categoryID]);

                if ($slugCheckStmt->fetchColumn()) {
                    $message = ['type' => 'error', 'text' => 'A category with that name already exists.'];
                } else {
                    $effectiveYear = $parent ? (int) $parent['yearNumber'] : $year;
                    $newFolderPath = $parent
                        ? rtrim($parent['folderPath'], '/') . '/' . ucfirst($newSlug) . '/'
                        : "Files/Year{$effectiveYear}/" . ucfirst($newSlug) . '/';

                    $oldFolderPath = rtrim($category['folderPath'], '/');
                    $newFolderPathTrimmed = rtrim($newFolderPath, '/');
                    $moveOk = true;

                    if ($newFolderPathTrimmed !== $oldFolderPath) {
                        $parentDir = dirname($newFolderPathTrimmed);
                        if (!is_dir($parentDir) && !mkdir($parentDir, 0755, true)) {
                            $moveOk = false;
                        } elseif (is_dir($oldFolderPath)) {
                            $moveOk = rename($oldFolderPath, $newFolderPathTrimmed);
                        } else {
                            $moveOk = mkdir($newFolderPathTrimmed, 0755, true);
                        }
                    }

                    if (!$moveOk) {
                        $message = ['type' => 'error', 'text' => 'Could not move the folder on disk, please try again.'];
                    } else {
                        try {
                            $dbHandler->beginTransaction();

                            $updateStmt = $dbHandler->prepare(
                                "UPDATE `categories` SET `name` = :name, `slug` = :slug, `folderPath` = :folderPath, `yearNumber` = :yearNumber, `parentCategoryID` = :parentCategoryID WHERE `categoryID` = :id;"
                            );
                            $updateStmt->execute([
                                ':name' => $name,
                                ':slug' => $newSlug,
                                ':folderPath' => $newFolderPath,
                                ':yearNumber' => $effectiveYear,
                                ':parentCategoryID' => $parentCategoryID,
                                ':id' => $categoryID,
                            ]);

                            if ($hasChildren) {
                                $childrenStmt = $dbHandler->prepare("SELECT `categoryID`, `folderPath` FROM `categories` WHERE `parentCategoryID` = :id;");
                                $childrenStmt->execute([':id' => $categoryID]);
                                $children = $childrenStmt->fetchAll(PDO::FETCH_ASSOC);

                                $childUpdateStmt = $dbHandler->prepare("UPDATE `categories` SET `folderPath` = :folderPath, `yearNumber` = :yearNumber WHERE `categoryID` = :id;");
                                foreach ($children as $child) {
                                    $childNewFolderPath = str_replace($oldFolderPath, $newFolderPathTrimmed, $child['folderPath']);
                                    $childUpdateStmt->execute([
                                        ':folderPath' => $childNewFolderPath,
                                        ':yearNumber' => $effectiveYear,
                                        ':id' => $child['categoryID'],
                                    ]);
                                }
                            }

                            $dbHandler->commit();

                            $category['name'] = $name;
                            $category['slug'] = $newSlug;
                            $category['folderPath'] = $newFolderPath;
                            $category['yearNumber'] = $effectiveYear;
                            $category['parentCategoryID'] = $parentCategoryID;

                            $message = ['type' => 'success', 'text' => 'Category updated.'];
                        } catch (Exception $ex) {
                            $dbHandler->rollBack();
                            error_log($ex->getMessage());
                            $message = ['type' => 'error', 'text' => 'Something went wrong saving the category.'];
                        }
                    }
                }
            }
        }
    }

    $topLevelStmt = $dbHandler->prepare("SELECT `categoryID`, `yearNumber`, `name` FROM `categories` WHERE `parentCategoryID` IS NULL AND `categoryID` != :id ORDER BY `yearNumber`, `name`;");
    $topLevelStmt->execute([':id' => $categoryID]);
    $topLevelCategories = $topLevelStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./Css/variables.css">
    <link rel="stylesheet" href="./Css/header.css">
    <link rel="stylesheet" href="./Css/upload.css">
    <title>Edit Category - Portfolio</title>
</head>
<body class="appleDark">
    <?php if (isset($message)): ?>
        <div class="<?php echo $message['type']; ?>"><?php echo htmlspecialchars($message['text']); ?></div>
    <?php endif; ?>

    <?php include 'header.php'; ?>

    <div class="centerWrap">
    <div class="uploadContainer">
        <a href="manageModules.php" class="backLink">&larr; Back to Manage Modules</a>
        <h1>Edit Category</h1>
        <p class="hint">Folder: <?php echo htmlspecialchars($category['folderPath']); ?></p>
        <?php if ($hasChildren): ?>
            <p class="hint">This category has sub-folder(s), so it can't be nested inside another category.</p>
        <?php endif; ?>

        <form action="editCategory.php" method="POST" class="form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="categoryID" value="<?php echo (int) $category['categoryID']; ?>">

            <p>
                <label for="name">Category Name:</label>
                <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
            </p>

            <?php if (!$hasChildren): ?>
                <p>
                    <label for="parentCategoryID">Parent Folder:</label>
                    <select name="parentCategoryID" id="parentCategoryID">
                        <option value="" <?php echo !$category['parentCategoryID'] ? 'selected' : ''; ?>>None — top-level category</option>
                        <?php foreach ($topLevelCategories as $parent): ?>
                            <option value="<?php echo (int) $parent['categoryID']; ?>" <?php echo $category['parentCategoryID'] == $parent['categoryID'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(($parent['yearNumber'] ? 'Year ' . $parent['yearNumber'] . ' - ' : '') . $parent['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>
            <?php endif; ?>

            <p>
                <label for="year">Year (ignored if a parent folder is picked above):</label>
                <select name="year" id="year">
                    <option value="" disabled>Pick a year</option>
                    <?php for ($y = 1; $y <= 4; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo (int) $category['yearNumber'] === $y ? 'selected' : ''; ?>>Year <?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </p>

            <p>
                <input type="submit" id="Submit" value="Save Changes">
            </p>
        </form>
    </div>
    </div>
</body>
</html>