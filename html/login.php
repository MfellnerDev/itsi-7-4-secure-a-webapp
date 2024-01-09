<?php

  session_start();

  /*
  if(!(isset($_POST['user']) && isset($_POST['passw']))){
    echo("Credential error.");
    exit(); 
  }
  */

 if(empty($_POST['user']) || empty($_POST['passw'])){
   header("Location: login.html");
   echo("Error invalid credentials.");
   exit();
  }

  $user = $_POST['user'];
  $passw = $_POST['passw'];

  try{
    $conn = new mysqli("db", "root", "supersecure", "customers");
  }catch(mysqli_sql_exception $e){
    echo("Database error.");
    exit();
  }

  if($conn -> connect_errno){
    echo "Error connecting to database";
    exit();
  }

  $result = $conn->execute_query('SELECT * FROM users WHERE username = ?', [$user]);
 

  if ($result->num_rows === 0) {
    echo "User/Password not found.<br>";
    echo '<a href="index.php"><button>Back</button></a>';
    exit();
  }


  $row = $result->fetch_assoc();
  $pw =  $row['password'];
  if ($pw !== $passw) {
    echo "User/Password not found.";
        echo '<a href="index.php"><button>Back</button></a>';
    exit();
  }

  $_SESSION['logged_in'] = true;
  $_SESSION['username'] = $user;

  header('Location: /');

?>

