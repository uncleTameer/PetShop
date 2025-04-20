<?php
session_start();
session_destroy();

session_start(); // Start a new session to store message
$_SESSION['logout_message'] = "You have been logged out.";
header("Location: ../index.php");
exit;
