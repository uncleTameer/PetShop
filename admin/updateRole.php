<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['error_message'] = "Unauthorized access.";
    header("Location: manageUsers.php");
    exit;
}

// Get parameters
$email = $_GET['email'] ?? '';
$newRole = $_GET['role'] ?? 'user';

// Allow only valid roles
$validRoles = ['user', 'admin'];
if (!in_array($newRole, $validRoles)) {
    $_SESSION['error_message'] = "❌ Invalid role.";
    header("Location: manageUsers.php");
    exit;
}

// Prevent admin from demoting themselves
if ($_SESSION['user']['email'] === $email) {
    $_SESSION['error_message'] = "❌ You can't change your own role.";
    header("Location: manageUsers.php");
    exit;
}

// Locate the user
$user = $db->users->findOne(['email' => $email]);
if (!$user) {
    $_SESSION['error_message'] = "❌ User not found.";
    header("Location: manageUsers.php");
    exit;
}

// Perform update
$update = $db->users->updateOne(
    ['email' => $email],
    ['$set' => ['role' => $newRole]]
);

if ($update->getModifiedCount() > 0) {
    $_SESSION['success_message'] = "✅ Role updated to '$newRole' for $email.";
} else {
    $_SESSION['error_message'] = "⚠️ No changes were made.";
}

header("Location: manageUsers.php");
exit;
?>
