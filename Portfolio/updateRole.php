<?php
    include("dbHandler.php");

    $id = $_GET['userID'];
    $role = $_GET['setRole'];
        
    if($dbHandler && $id && $role){
        try{
            $stmt = $dbHandler->prepare("UPDATE `users` SET `role` = :setRole WHERE `userID` = :id;");
            $stmt->execute([
                ':setRole' => $role,
                ':id' => $id
            ]);
            $stmt->closeCursor();
            header("Location: web.php");
            exit();
        } 
        catch(Exception $ex){
            echo $ex;
        }
    }
?>