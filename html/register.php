<?php

 if(empty($_POST['user']) || empty($_POST['passw']) || empty($_POST['farbe'])){
   header("Location: login.html");
   echo("Error invalid credentials.");
   exit();
  }

  $name = $_POST['user'];
  $passw = password_hash($_POST['passw'],  PASSWORD_DEFAULT);
  $farbe = $_POST['farbe'];

  try{
    $conn = new mysqli("db", "root", "CQdqhhD3Q2ED%5du8kq*vmYdP", "customers");
  }catch(mysqli_sql_exception $e){
    echo("Database error.");
    exit();
  }

  if($conn -> connect_errno){
    echo "Error connecting to database";
    exit();
  }
  // escape string
  $result = $conn->execute_query('INSERT INTO users () VALUES (?, ?, ?)', [$conn->real_escape_string($name), $passw, $farbe]);

  if(!$result){
    echo("User not found.");
    exit();
  }

  echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/dark.css">';


   echo "<h1>Succesfully registered.</h1>";
   echo '<a href="index.php"><button>Back</button></a>';
   exit();
?>
