<?php
    // Expects session_start() to already have run.
    // Optional before include: $headerShowSearch (bool), $headerTagline (raw HTML string).
    $headerShowSearch = $headerShowSearch ?? false;
    $headerTagline = $headerTagline ?? '';
    $headerUsername = htmlspecialchars($_SESSION['username'] ?? '');
    $headerPermission = htmlspecialchars($_SESSION['permission'] ?? '');
?>
<nav class="topNav">
    <div class="navLeft">
        <span class="welcome">Welcome, dear <strong><?php echo $headerUsername; ?></strong><?php echo $headerTagline; ?></span>
        <a href="logout.php" class="btnLogout">Log Out</a>
    </div>
    <?php if ($headerShowSearch): ?>
        <div class="navCenter">
            <input type="text" placeholder="Search Portfolio..." class="searchBar">
        </div>
    <?php endif; ?>
    <div class="navRight">
        <span class="userPermission">Permission: <strong><?php echo $headerPermission; ?></strong></span>
    </div>
</nav>
