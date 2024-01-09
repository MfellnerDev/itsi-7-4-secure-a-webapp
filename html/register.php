<?php

 if(empty($_POST['user']) || empty($_POST['password']) || empty($_POST['color'])){
   header("Location: login.html");
   echo("Error invalid credentials.");
   exit();
  }

 $name = $_POST['user'];
 $password = password_hash($_POST['password'],  PASSWORD_DEFAULT);
 $color = $_POST['color'];

 try{
     $conn = new mysqli("db", "root", "Trimmer-Onslaught-Spherical-Overjoyed-Poise-Overrate-Botanical-Humorous-Crewless5-Fetch", "customers");
 }catch(mysqli_sql_exception $e){
     echo("Database error.");
     exit();
 }


  if($conn -> connect_errno){
    echo "Error connecting to database";
    exit();
  }
  // escape string
  $result = $conn->execute_query('INSERT INTO users () VALUES (?, ?, ?)', [$name, $password, $color]);

  if(!$result){
    echo("User not found.");
    exit();
  }

  echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/dark.css">';


   echo "<h1>Successfully registered.</h1>";
   echo '<a href="index.php"><button>Back</button></a>';
   exit();
?>
