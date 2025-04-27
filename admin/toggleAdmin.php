<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || !$_SESSION['user']['isAdmin']) {
    header("Location: ../index.php");
    exit;
}

$email = $_GET['email'] ?? '';
$make = $_GET['make'] ?? '';

if (!$email || !in_array($make, ['0', '1'])) {
    header("Location: manageUsers.php");
    exit;
}

if ($email === $_SESSION['user']['email']) {
    // 🛡️ Prevent self-demotion
    $_SESSION['error_message'] = "You cannot change your own admin status.";
    header("Location: manageUsers.php");
    exit;
}

$isAdmin = $make === '1';

$update = $db->users->updateOne(
    ['email' => $email],
    ['$set' => ['isAdmin' => $isAdmin]]
);

$_SESSION['success_message'] = $isAdmin ? "User promoted to admin." : "User demoted to regular user.";
header("Location: manageUsers.php");
exit;
