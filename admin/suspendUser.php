<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

use MongoDB\BSON\ObjectId;

// Only admins can access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['error_message'] = "Unauthorized access.";
    header("Location: manageUsers.php");
    exit;
}

$email = $_GET['email'] ?? '';
$action = $_GET['action'] ?? '';

if (empty($email) || !in_array($action, ['suspend', 'unsuspend'])) {
    $_SESSION['error_message'] = "Invalid request.";
    header("Location: manageUsers.php");
    exit;
}

// Prevent suspending yourself
if ($email === $_SESSION['user']['email']) {
    $_SESSION['error_message'] = "You cannot suspend or unsuspend yourself.";
    header("Location: manageUsers.php");
    exit;
}

// Prevent moderators from suspending admins
$user = $db->users->findOne(['email' => $email]);
if (!$user) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: manageUsers.php");
    exit;
}

// Perform the action
$update = $db->users->updateOne(
    ['email' => $email],
    ['$set' => ['suspended' => $action === 'suspend']]
);

if ($update->getModifiedCount() > 0) {
    $_SESSION['success_message'] = $action === 'suspend'
        ? "⛔ User has been suspended."
        : "✅ User has been unsuspended.";
} else {
    $_SESSION['error_message'] = "No changes were made.";
}

header("Location: manageUsers.php");
exit;
?>
