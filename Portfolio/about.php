<?php
    session_start();

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: login.php");
        exit();
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
    <link rel="stylesheet" href="./Css/about.css">
    <title>About Me - Portfolio</title>
</head>

<body class="appleDark">
    <?php include 'header.php'; ?>

    <div class="mainContainer">
        <main class="content aboutPage">
            <a href="web.php" class="backLink">&larr; Back to Portfolio</a>

            <div class="aboutCenter">
                <div class="aboutCard">
                    <img src="./Files/Personal/caleb-profile.jpg" class="avatar" alt="Caleb Gaïtou">

                    <div class="aboutText">
                        <h1>Caleb Gaïtou</h1>
                        <p class="tagline">Student at NHL Stenden University of Applied Sciences Emmen</p>

                        <p class="bio">
                            Hi, I'm Caleb — a Computer Science student at NHL Stenden in the Netherlands. This portfolio is where I
                            collect the evidence, projects, and reflections I build throughout my studies, from
                            Year 1 through graduation.
                        </p>

                        <h2>Education</h2>
                        <div class="resumeEntry">
                            <div class="resumeHeader">
                                <span class="resumeTitle">High School Diploma</span>
                                <span class="resumeDate">Aug 2019 – Jun 2024</span>
                            </div>
                            <p class="resumeSubtitle">Sahel Academy, Niamey, Niger</p>
                        </div>
                        <div class="resumeEntry">
                            <div class="resumeHeader">
                                <span class="resumeTitle">Bachelor Diploma — Information Technology</span>
                                <span class="resumeDate">Sep 2025 – Present</span>
                            </div>
                            <p class="resumeSubtitle">NHL Stenden, Emmen</p>
                            <ul class="resumeBullets">
                                <li>Computer networks and data communication.</li>
                                <li>Database management.</li>
                                <li>Web development.</li>
                                <li>Object oriented progaming.</li>
                            </ul>
                        </div>

                        <h2>Employment</h2>
                        <div class="resumeEntry">
                            <div class="resumeHeader">
                                <span class="resumeTitle">Server</span>
                                <span class="resumeDate">Sep 2024 – May 2025</span>
                            </div>
                            <p class="resumeSubtitle">Amrath Berghotel Amersfoort BV, Amersfoort, Netherlands</p>
                        </div>

                        <h2>IT Skills</h2>
                        <ul class="skillsList">
                            <li>PHP</li>
                            <li>MySQL / MariaDB</li>
                            <li>HTML &amp; CSS</li>
                            <li>OOP/Java</li>
                        </ul>

                        <h2>Soft Skills</h2>
                        <ul class="skillsList">
                            <li>Leadership</li>
                            <li>Strong work ethic</li>
                            <li>Good communication</li>
                            <li>Good in a team</li>
                        </ul>

                        <h2>Languages</h2>
                        <ul class="skillsList">
                            <li>English</li>
                            <li>French</li>
                            <li>Dutch</li>
                        </ul>

                        <h2>Interests</h2>
                        <ul class="resumeBullets">
                            <li>Sports: football, volleyball, softball, working out, biking</li>
                            <li>Playing video games, solo or with others</li>
                        </ul>

                        <h2>Contact</h2>
                        <ul class="contactList">
                            <li><a href="mailto:calebbgaitou@gmail.com">calebbgaitou@gmail.com</a></li>
                            <li><a href="#">My LinkedIn</a></li>
                            <li><a href="https://github.com/CalebGaitou">My GitHub</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
