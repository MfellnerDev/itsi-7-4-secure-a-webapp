<?php

session_start();


if (!((isset($_SESSION['logged_in'])) && $_SESSION['logged_in'])) {
	header('Location: /login.html');
	die();
}

$user = $_SESSION['username'];


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

$row = $result->fetch_assoc();


?>


<html>
  <head>
    <title>Login</title>
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/dark.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.net.min.js"></script>
  </head>

  <body>
	<div id="bg" style="position:absolute;width:100%;height:100%;z-index:-1000;top:0px;left:0px"></div>

	<h1> My First Website </h1>
	<h2>My Page</h2>

Your favorite color is: <?php echo $row['color']; ?>

<br><a href="logout.php"><button>Log out</button></a>


<script>
VANTA.NET({
  el: "#bg",
  mouseControls: false,
  touchControls: false,
  gyroControls: false,
  minHeight: 200.00,
  minWidth: 200.00,
  scale: 1.00,
  scaleMobile: 1.00
})
</script>

  
  </body>

</html>
