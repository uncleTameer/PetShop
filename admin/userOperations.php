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

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'suspend':
        handleSuspendUser();
        break;
    case 'updateRole':
        handleUpdateRole();
        break;
    case 'delete':
        handleDeleteUser();
        break;
    case 'toggleAdmin':
        handleToggleAdmin();
        break;
    default:
        header("Location: manageUsers.php");
        exit;
}

function handleSuspendUser() {
    global $db;
    
    $email = $_GET['email'] ?? '';
    $suspendAction = $_GET['suspendAction'] ?? '';

    if (empty($email) || !in_array($suspendAction, ['suspend', 'unsuspend'])) {
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
        ['$set' => ['suspended' => $suspendAction === 'suspend']]
    );

    if ($update->getModifiedCount() > 0) {
        $_SESSION['success_message'] = $suspendAction === 'suspend'
            ? "⛔ User has been suspended."
            : "✅ User has been unsuspended.";
    } else {
        $_SESSION['error_message'] = "No changes were made.";
    }

    header("Location: manageUsers.php");
    exit;
}

function handleUpdateRole() {
    global $db;
    
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
}

function handleDeleteUser() {
    global $db;
    
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
}

function handleToggleAdmin() {
    global $db;
    
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
}
?> 