<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

use MongoDB\BSON\ObjectId;

// ============================================================================
// Admin User Management Handler
// ============================================================================

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

// ============================================================================
// Suspend/Unsuspend User Function
// ============================================================================

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
            ? "⛔ User suspended successfully." 
            : "🔓 User unsuspended successfully.";
    } else {
        $_SESSION['error_message'] = "No changes made.";
    }

    header("Location: manageUsers.php");
    exit;
}

// ============================================================================
// Update User Role Function
// ============================================================================

function handleUpdateRole() {
    global $db;
    
    $email = $_POST['email'] ?? '';
    $newRole = $_POST['role'] ?? '';

    if (empty($email) || empty($newRole)) {
        $_SESSION['error_message'] = "Email and role are required.";
        header("Location: manageUsers.php");
        exit;
    }

    // Prevent changing your own role
    if ($email === $_SESSION['user']['email']) {
        $_SESSION['error_message'] = "You cannot change your own role.";
        header("Location: manageUsers.php");
        exit;
    }

    // Validate role
    $validRoles = ['user', 'moderator', 'admin'];
    if (!in_array($newRole, $validRoles)) {
        $_SESSION['error_message'] = "Invalid role.";
        header("Location: manageUsers.php");
        exit;
    }

    // Update role
    $update = $db->users->updateOne(
        ['email' => $email],
        ['$set' => ['role' => $newRole]]
    );

    if ($update->getModifiedCount() > 0) {
        $_SESSION['success_message'] = "Role updated successfully.";
    } else {
        $_SESSION['error_message'] = "No changes made.";
    }

    header("Location: manageUsers.php");
    exit;
}

// ============================================================================
// Delete User Function
// ============================================================================

function handleDeleteUser() {
    global $db;
    
    $email = $_GET['email'] ?? '';

    if (empty($email)) {
        $_SESSION['error_message'] = "Email is required.";
        header("Location: manageUsers.php");
        exit;
    }

    // Prevent deleting yourself
    if ($email === $_SESSION['user']['email']) {
        $_SESSION['error_message'] = "You cannot delete yourself.";
        header("Location: manageUsers.php");
        exit;
    }

    // Check if user exists
    $user = $db->users->findOne(['email' => $email]);
    if (!$user) {
        $_SESSION['error_message'] = "User not found.";
        header("Location: manageUsers.php");
        exit;
    }

    // Delete user
    $delete = $db->users->deleteOne(['email' => $email]);

    if ($delete->getDeletedCount() > 0) {
        $_SESSION['success_message'] = "User deleted successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to delete user.";
    }

    header("Location: manageUsers.php");
    exit;
}

// ============================================================================
// Toggle Admin Function
// ============================================================================

function handleToggleAdmin() {
    global $db;
    
    $email = $_GET['email'] ?? '';

    if (empty($email)) {
        $_SESSION['error_message'] = "Email is required.";
        header("Location: manageUsers.php");
        exit;
    }

    // Prevent toggling yourself
    if ($email === $_SESSION['user']['email']) {
        $_SESSION['error_message'] = "You cannot change your own admin status.";
        header("Location: manageUsers.php");
        exit;
    }

    // Get current user
    $user = $db->users->findOne(['email' => $email]);
    if (!$user) {
        $_SESSION['error_message'] = "User not found.";
        header("Location: manageUsers.php");
        exit;
    }

    // Toggle admin status
    $currentRole = $user['role'] ?? 'user';
    $newRole = ($currentRole === 'admin') ? 'user' : 'admin';

    $update = $db->users->updateOne(
        ['email' => $email],
        ['$set' => ['role' => $newRole]]
    );

    if ($update->getModifiedCount() > 0) {
        $_SESSION['success_message'] = $newRole === 'admin' 
            ? "User promoted to admin." 
            : "User demoted from admin.";
    } else {
        $_SESSION['error_message'] = "No changes made.";
    }

    header("Location: manageUsers.php");
    exit;
}
?> 