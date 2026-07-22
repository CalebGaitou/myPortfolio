<?php

    $dbHandler = null;

    try{
        $dbHandler = new PDO("mysql:host=mysql;dbname=portfolio;chardset=utf8", "root", "qwerty");
        $dbHandler->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch(Exception $ex){
        echo $ex;
    }
?>