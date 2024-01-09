<?php
session_start();

if (empty($_POST['user']) || empty($_POST['password'])) {
    header("Location: login.html");
    exit();
}

$user = $_POST['user'];
$password = $_POST['password'];

try {
    $conn = new mysqli("db", "root", "Trimmer-Onslaught-Spherical-Overjoyed-Poise-Overrate-Botanical-Humorous-Crewless5-Fetch", "customers");
} catch (mysqli_sql_exception $e) {
    error_log("Database error: " . $e->getMessage());
    header("Location: error.html");
    exit();
}

if ($conn->connect_errno) {
    error_log("Error connecting to database: " . $conn->connect_error);
    header("Location: error.html");
    exit();
}

// prepared statements for SQL injections -> more on that: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
$statement = $conn->prepare('SELECT * FROM users WHERE username = ?');
// "declare" the user var as a string, escapes it automatically
$statement->bind_param('s', $user);
$statement->execute();
$result = $statement->get_result();

if ($result->num_rows === 0) {
    header("Location: login.html");
    sleep(1);
    exit();
}

$row = $result->fetch_assoc();
$storedPassword = $row['password'];

// secure comparison with password_verify
if (!password_verify($password, $storedPassword)) {
    header("Location: login.html");
    sleep(1);
    exit();
}

$_SESSION['logged_in'] = true;
$_SESSION['username'] = $user;

header('Location: /');
?>
