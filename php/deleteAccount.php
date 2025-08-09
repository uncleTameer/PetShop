<?php
require 'dbConnect.php';
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$userId = new MongoDB\BSON\ObjectId($_SESSION['user']['id']);

// Fetch user to get profile picture filename
$user = $db->users->findOne(['_id' => $userId]);

// Delete profile picture from uploads folder (if exists)
if (!empty($user['profilePicture'])) {
    $imagePath = __DIR__ . '/../uploads/' . $user['profilePicture'];
    if (file_exists($imagePath)) {
        unlink($imagePath); // Delete the image file
    }
}

// Delete user from database
$db->users->deleteOne(['_id' => $userId]);

// Clear session
session_unset();
session_destroy();

// Show logout message
session_start();
$_SESSION['logout_message'] = "👋 Your account has been successfully deleted.";
header("Location: index.php");
exit;
?>
