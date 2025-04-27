<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use MongoDB\BSON\ObjectId;

// Admin access required
if (!isset($_SESSION['user']) || !$_SESSION['user']['isAdmin']) {
    header("Location: ../index.php");
    exit;
}

$email = $_GET['email'] ?? null;
$action = $_GET['action'] ?? null;

if (!$email || !$action) {
    $_SESSION['error_message'] = "❌ Missing information.";
    header("Location: manageUsers.php");
    exit;
}

try {
    $user = $db->users->findOne(['email' => $email]);

    if (!$user) {
        $_SESSION['error_message'] = "❌ User not found.";
    } elseif ($email === $_SESSION['user']['email']) {
        $_SESSION['error_message'] = "❌ You cannot suspend yourself!";
    } else {
        if ($action === 'suspend') {
            $db->users->updateOne(['email' => $email], ['$set' => ['suspended' => true]]);
            $_SESSION['success_message'] = "⛔ User suspended successfully.";
        } elseif ($action === 'unsuspend') {
            $db->users->updateOne(['email' => $email], ['$unset' => ['suspended' => ""]]);
            $_SESSION['success_message'] = "🔓 User unsuspended successfully.";
        } else {
            $_SESSION['error_message'] = "❌ Invalid action.";
        }
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "❌ Error: " . $e->getMessage();
}

header("Location: manageUsers.php");
exit;
?>
