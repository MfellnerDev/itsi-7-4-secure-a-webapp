<?php
session_start();
$_SESSION['logged_in'] = false;
$_SESSSION['username'] = '';

header('Location: /');

?>

