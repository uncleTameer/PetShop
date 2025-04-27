<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use MongoDB\BSON\ObjectId;

// Check if admin is logged in
if (!isset($_SESSION['user']) || !$_SESSION['user']['isAdmin']) {
    header("Location: ../index.php");
    exit;
}

// Validate ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "❌ No user ID provided.";
    header("Location: manageUsers.php");
    exit;
}

$id = $_GET['id'];

try {
    $user = $db->users->findOne(['_id' => new ObjectId($id)]);

    if (!$user) {
        $_SESSION['error_message'] = "❌ User not found.";
    } elseif ($user['email'] === $_SESSION['user']['email']) {
        $_SESSION['error_message'] = "❌ You cannot delete yourself!";
    } else {
        $db->users->deleteOne(['_id' => new ObjectId($id)]);
        $_SESSION['success_message'] = "🗑️ User deleted successfully!";
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "❌ Error: " . $e->getMessage();
}

header("Location: manageUsers.php");
exit;
?>
