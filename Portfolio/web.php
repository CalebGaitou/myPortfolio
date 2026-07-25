<?php
    session_start();

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true){
            header("Location: login.php");
            exit();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrfToken = $_SESSION['csrf_token'];

    $permission = htmlspecialchars($_SESSION['permission'] ?? '');
    $role = htmlspecialchars($_SESSION['role'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./Css/variables.css">
    <link rel="stylesheet" href="./Css/header.css">
    <link rel="stylesheet" href="./Css/web.css">
    <title>Portfolio</title>
</head>

<body class="appleDark">
    <?php
        $headerShowSearch = true;
        $headerTagline = ' to my Portfolio!';
        include 'header.php';
    ?>

    <div class="mainContainer">
        <aside class="sidebar">
            <div class="sidebarSection">

                <ul class="sidebarList">
                    <?php 
                        if($role === 'admin' || $permission === 'teacher'){
                            echo '<li><a href="upLoad.php">Submit Files</a></li>';
                        }
                        else{
                            echo '<li><a href="#">View All Projects</a></li>';
                        }
                        if($role === 'admin' && $permission === 'admin'){
                            echo '<li><a href="manageModules.php">Manage Modules</a></li>';
                        }
                    ?>
                    <li><a href="calendar.php">Calendar</a></li>
                    <li><a href="about.php">About Me</a></li>
                </ul>
                
                <h3>Modules</h3>
                <?php
                    if($role === 'admin' || $role === 'visitor'){
                        include("dbHandler.php");
                        /** @var PDO|null $dbHandler */
                        $availableYears = [];
                        if ($dbHandler) {
                            $yearsStmt = $dbHandler->query("SELECT DISTINCT `yearNumber` FROM `categories` WHERE `yearNumber` IS NOT NULL ORDER BY `yearNumber`;");
                            $availableYears = $yearsStmt->fetchAll(PDO::FETCH_COLUMN);
                        }
                        foreach ($availableYears as $availableYear) {
                            echo '<div class="moduleDetails">
                                <summary class="moduleSummary">
                                    <p><a href="./year.php?y=' . (int) $availableYear . '">Year ' . (int) $availableYear . '</a></p>
                                </summary>
                            </div>';
                        }
                    }
                    else{
                        echo '<p class = "moduleMessage">
                            Please upgrade to view modules.
                        </p>';
                    }
                ?>
            </div>
        </aside>

        <main class="content">
            <?php
                include("dbHandler.php");
                /** @var PDO|null $dbHandler */
                $announcement = null;
                if ($dbHandler) {
                    $announcementStmt = $dbHandler->prepare(
                        "SELECT a.message, a.postedAt, u.username AS postedByName
                         FROM announcements a
                         JOIN users u ON u.userID = a.postedByUserID
                         WHERE a.postedAt >= NOW() - INTERVAL 4 DAY
                         ORDER BY a.postedAt DESC
                         LIMIT 1;"
                    );
                    $announcementStmt->execute();
                    $announcement = $announcementStmt->fetch(PDO::FETCH_ASSOC);
                }
            ?>
            <?php if ($announcement): ?>
                <div class="announcementBox">
                    <h3>Announcement</h3>
                    <p><?php echo nl2br(htmlspecialchars($announcement['message'])); ?></p>
                    <p class="announcementMeta">
                        Posted by <?php echo htmlspecialchars($announcement['postedByName']); ?>
                        on <?php echo htmlspecialchars((new DateTime($announcement['postedAt']))->format('Y-m-d H:i')); ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($role === 'admin' && $permission === 'admin'): ?>
                <div class="announcementForm">
                    <h3>Post Announcement</h3>
                    <p class="announcementHint">Posting a new announcement replaces the current one. It disappears on its own after 4 days.</p>
                    <form action="postAnnouncement.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <textarea name="message" id="message" rows="4" required></textarea>
                        <button type="submit" class="saveBtn">Post Announcement</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($role === 'admin'): ?>
                <h1>Admin Panel</h1>
            <?php else: ?>
                <?php
                    include("dbHandler.php");
                    /** @var PDO|null $dbHandler */
                    $userID = $_SESSION['user_id'] ?? null;

                    $accessibleFiles = [];
                    if ($dbHandler) {
                        $stmt = $dbHandler->prepare(
                            "SELECT f.fileID, f.title, f.linkUrl,
                                    CASE WHEN c.parentCategoryID IS NOT NULL THEN CONCAT(parentCat.name, ' / ', c.name) ELSE c.name END AS categoryName,
                                    c.yearNumber
                             FROM files f
                             JOIN categories c ON c.categoryID = f.categoryID
                             LEFT JOIN categories parentCat ON parentCat.categoryID = c.parentCategoryID
                             JOIN file_access fa ON fa.fileID = f.fileID AND fa.userID = :userID
                             ORDER BY f.uploadedAt DESC
                             LIMIT 6;"
                        );
                        $stmt->execute([':userID' => $userID]);
                        $accessibleFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                ?>
                <h1>Library</h1>
                <p class="libraryIntro">
                    This is My academic portfolio for the NHL Stenden IT programme. Files here are only
                    visible if they've been explicitly shared with you &mdash; go to to the modueles in the
                    sidebar to browse through specific years.
                </p>

                <?php if ($accessibleFiles): ?>
                    <div class="gridContainer">
                        <?php foreach ($accessibleFiles as $file): ?>
                            <a class="card" href="viewFile.php?id=<?php echo (int) $file['fileID']; ?>">
                                <span class="cardTitle"><?php echo ($file['linkUrl'] ? '&#128279; ' : '') . htmlspecialchars($file['title']); ?></span>
                                <span class="cardMeta">Year <?php echo (int) $file['yearNumber']; ?> &middot; <?php echo htmlspecialchars($file['categoryName']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="moduleMessage">No files have been shared with you yet.</p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if($role === 'admin' && $permission === 'admin'){ ?>
                <div class="tableContainer">
                    <table class="editPermissions">
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Permission</th>
                            <th>Edit Permission</th>
                            <th>Role</th>
                            <th>Edit Role</th>
                            <th>Delete</th>
                        </tr> 
                            <?php    
                                include("dbHandler.php");

                                if($dbHandler){
                                    try{
                                        $stmt = $dbHandler->prepare("SELECT `userID`, `username`, `email`, `permission`, `role` FROM `users`;");
                                        $stmt->execute();
                                        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach($tables as $table){
                                            echo '<tr>
                                                    <td class="allCells" title="' . htmlspecialchars($table["username"]) . '">' . htmlspecialchars($table["username"]) . '</td>
                                                    <td class="allCells" title="' . htmlspecialchars($table["email"]) . '">' . htmlspecialchars($table["email"]) . '</td>
                                                    <td class="allCells">' . htmlspecialchars($table["permission"]) . '</td>
                                                    <td class="allCells">
                                                        <form action="updatePermissions.php" method="POST">
                                                            <div class="editActionsContainer">
                                                                <input type="hidden" name="userID" value="' . (int)$table["userID"] . '">
                                                                <input type="hidden" name="csrf_token" value="' . $csrfToken . '">
                                                                    <select name="setPermission" id="setPermission">
                                                                        <option value="set" selected disabled>Set Permission</option>
                                                                        <option value="teacher">Teacher</option>
                                                                        <option value="student">Student</option>
                                                                        <option value="employer">Employer</option>
                                                                        <option value="visitor">Visitor</option>
                                                                    </select>
                                                                <button type="submit" name="update" class="saveBtn">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </td>
                                                    <td class="allCells">' . htmlspecialchars($table["role"]) . '</td>
                                                    <td class="allCells">
                                                        <form action="updateRole.php" method="POST">
                                                            <div class="editActionsContainer">
                                                                <input type="hidden" name="userID" value="' . (int)$table["userID"] . '">
                                                                <input type="hidden" name="csrf_token" value="' . $csrfToken . '">
                                                                    <select name="setRole" id="setRole">
                                                                        <option value="set" selected disabled>Set Role</option>
                                                                        <option value="admin">Admin</option>
                                                                        <option value="visitor">Visitor</option>
                                                                    </select>
                                                                <button type="submit" name="update" class="saveBtn">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </td>
                                                    <td class="allCells">
                                                        <form action="delete.php" method="POST" class="deleteForm">
                                                            <input type="hidden" name="id" value="' . (int)$table["userID"] . '">
                                                            <input type="hidden" name="csrf_token" value="' . $csrfToken . '">
                                                            <button type="submit" class="deleteBtn">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>';
                                        }
                                    }
                                    catch(Exception $ex){
                                        error_log($ex->getMessage());
                                        echo '<tr><td colspan="6" class="allCells error">Something went wrong loading users.</td></tr>';
                                    }
                                }
                                else{
                                    echo"<div class = 'error'>
                                            Something went wrong try again.
                                        </div>";
                                }
                            ?>
                    </table>
                </div>  
            <?php } ?>
        </main>
    </div>
</body>
</html>