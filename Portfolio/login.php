<?php
    session_start();

    if (isset($_GET['signup']) && $_GET['signup'] == 'success'){
    echo "<div class='success'>
            Your account has been created, you can now login.
        </div>";
    }

    if($_SERVER ["REQUEST_METHOD"] == "POST"){

        $userName = $_POST["userName"] ?? '';
        $psw = $_POST["psw"] ?? '';

        if(empty($userName) || empty($psw)){
            echo "<div class = 'error'>
                    Please fill out all fields.
                </div>";
        }
        elseif(strtolower($userName) === 'guest'){
            echo "<div class = 'error'>
                    Invalid username or password.
                </div>";
        }
        else{
            include ("dbHandler.php");

            if($dbHandler){
                try{
                    $stmt = $dbHandler->prepare("SELECT `userID`, `username`, `psw`, `permission`, `role` FROM `users` WHERE `username` = :userName LIMIT 1;");
                    $stmt->bindParam("userName", $userName, PDO::PARAM_STR);
                    $stmt->execute();

                    $users = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Always run password_verify, even for an unknown username, so the
                    // response time doesn't reveal whether the username exists.
                    $hashToCheck = $users['psw'] ?? '$2y$12$usqIiT8yFV5x2r7c93cKAOVMhIVzM3XxjxCUpi6cA0YvXutXvI5Xu';
                    $passwordMatches = password_verify($psw, $hashToCheck);

                    if($users && $passwordMatches){
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $users['userID'];
                        $_SESSION['username'] = $users['username'];
                        $_SESSION['permission'] = $users['permission'];
                        $_SESSION['role'] = $users['role'];
                        $_SESSION['logged_in'] = true;

                        header("location: web.php");
                        exit();
                    }
                    else{
                        echo "<div class = 'error'>
                                Invalid username or password.
                            </div>";
                    }
                }
                catch(Exception $ex){
                    error_log($ex->getMessage());
                    echo "<div class = 'error'>
                            Something went wrong, please try again.
                        </div>";
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
    <link rel="stylesheet" href="./Css/login.css">
    <title>Login</title>
</head>

<body class="appleDark">
    <div class="loginContainer">
        <form action="login.php" method="POST" class="form">
            <p class="welcom">
                Welcome to my portfolio
            </p>

            <p class="school">
                Student at NHL Stenden University of Applied Sciences Emmen
            </p>

            <p class="userName">
                <label for="userName">Username:</label>
                <input type="text" name="userName" id="userName">
            </p>

            <p class="password">
                <label for="psw">Password:</label>
                <input type="password" name="psw" id="psw">
            </p>

            <p class="login">
                <input type="submit" name="submit" id="submit" value="Login">
            </p>

            <p class="signupLink">
                Don't have an account? <a href="signIn.php">Sign Up</a>
            </p>
            
            <p class="visit">
                Don't want to make an account? <a href="guestLogin.php">Login as Guest</a>
            </p>

            <p class="copyRight">
                &copy Caleb Gaïtou
            </p>
        </form>
    </div>
</body>
</html>