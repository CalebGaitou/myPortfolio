<?php
    if($_SERVER["REQUEST_METHOD"] == "POST"){

        $userName = $_POST["userName"] ?? '';
        $psw = $_POST["psw"] ?? '';
        $pswConfirm = $_POST["pswConfirm"] ?? '';
        $email = filter_input(INPUT_POST, "email", FILTER_VALIDATE_EMAIL);

        if($psw == $pswConfirm){
           if(empty($userName) || empty($psw) || empty($email)){
            echo "<div class = 'error'>
                    Please fill out all fields with a valid email address.
                </div>";
            }
            else{
                include("dbHandler.php");

                if($dbHandler){
                    try{

                        $hashedPsw = password_hash($psw, PASSWORD_DEFAULT);

                        $stmt = $dbHandler->prepare("INSERT INTO `users` (`username`, `psw`, `email`)
                                                        VALUES (:userName, :psw, :email);");
                        $stmt->bindParam('userName', $userName, PDO::PARAM_STR);
                        $stmt->bindParam('psw', $hashedPsw, PDO::PARAM_STR);
                        $stmt->bindParam('email', $email, PDO::PARAM_STR);
                        $stmt->execute();
                        $stmt->closeCursor();

                        header("location: login.php?signup=success");
                        exit();
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
        else{
            echo "<div class = 'error'>
                    Please make sure the passwords match.
                </div>";
        }
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Portfolio/Css/variables.css">
    <link rel="stylesheet" href="../Portfolio/Css/signIn.css">
    <title>Sign In</title>
</head>

<body class="appleDark"> 
    <div class="glassContainer"> 
        <form action="signIn.php" method="POST" class="form">

            <p class="userName">
                <label for="userName">Username:</label>
                <input type="text" name="userName" id="userName">
            </p>

            <p class="password">
                <label for="psw">Password:</label>
                <input type="password" name="psw" id="psw">
            </p>

            <p class="passwordConfirm">
                <label for="pswConfirm">Confirm Your Password:</label>
                <input type="password" name="pswConfirm" id="pswConfirm">
            </p>

            <p class="email">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email">
            </p>

            <p class="submit">
                <input type="submit" name="submit" id="submit" value="Sign In">
            </p>

            <p class="loginLink">
                Already have an account? <a href="login.php">Login</a>
            </p>
        </form>
    </div>
</body>
</html>