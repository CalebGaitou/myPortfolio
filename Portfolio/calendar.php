<?php
    session_start();

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: login.php");
        exit();
    }

    // Outlook's published "HTML" calendar link (Outlook on the web > Calendar >
    // Settings > Shared calendars > Publish a calendar). Read-only, view only —
    // anyone with this exact URL can view the calendar, so treat it like any
    // other shared secret link.
    $calendarEmbedUrl = "https://outlook.office365.com/owa/calendar/c8c9903190b54e469d05b934584c07ef@student.nhlstenden.com/1d67b6a9ca3741889c384cdd14fe582414789045714294495415/calendar.html";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./Css/variables.css">
    <link rel="stylesheet" href="./Css/header.css">
    <link rel="stylesheet" href="./Css/web.css">
    <title>Calendar - Portfolio</title>
</head>

<body class="appleDark">
    <?php include 'header.php'; ?>

    <div class="mainContainer">
        <aside class="sidebar">
            <div class="sidebarSection">
                <h3>Navigation</h3>
                <ul class="sidebarList">
                    <li><a href="web.php">&larr; Back to Portfolio</a></li>
                    <li><a href="about.php">About Me</a></li>
                </ul>
            </div>
        </aside>

        <main class="content">
            <h1>Calendar</h1>
            <p class="libraryIntro">My current schedule, kept up to date from my Outlook calendar.</p>

            <div class="calendarEmbed">
                <iframe
                    src="<?php echo htmlspecialchars($calendarEmbedUrl); ?>"
                    frameborder="0"
                    scrolling="no"
                    title="Caleb's schedule"
                ></iframe>
            </div>
        </main>
    </div>
</body>
</html>
