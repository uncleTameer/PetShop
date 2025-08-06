<?php
require 'dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['user'])) {
    $_SESSION['error_message'] = "Session expired.";
    header("Location: ../php/login.php");
    exit;
}

$userId = new ObjectId($_SESSION['user']['id']);
$user = $db->users->findOne(['_id' => $userId]);

if (!$user) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: ../index.php");
    exit;
}

// Sanitize inputs
$firstName = trim($_POST['firstName'] ?? '');
$lastName  = trim($_POST['lastName'] ?? '');
$fullName  = $firstName . ' ' . $lastName;
$phone     = trim($_POST['phone'] ?? '');
$country   = trim($_POST['country'] ?? '');
$city      = trim($_POST['city'] ?? '');
$address   = trim($_POST['address'] ?? '');
$zipCode   = trim($_POST['zipCode'] ?? '');

// Prepare update array
$updateData = [
    'fullName' => $fullName,
    'phone'    => $phone,
    'country'  => $country,
    'city'     => $city,
    'address'  => $address,
    'zipCode'  => $zipCode
];

// ✅ Handle profile picture upload
if (!empty($_FILES['profilePicture']['name'])) {
    $ext = pathinfo($_FILES['profilePicture']['name'], PATHINFO_EXTENSION);
    $newFileName = uniqid() . '.' . $ext;
    $uploadDir = '../uploads/';
    $targetPath = $uploadDir . $newFileName;

    if (move_uploaded_file($_FILES['profilePicture']['tmp_name'], $targetPath)) {
        $updateData['profilePicture'] = $newFileName;

        // Optional: delete old picture if not default
        if (!empty($user['profilePicture']) && $user['profilePicture'] !== 'default.png') {
            $oldPath = $uploadDir . $user['profilePicture'];
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        // Update session picture
        $_SESSION['user']['profilePicture'] = $newFileName;
    } else {
        $_SESSION['error_message'] = "❌ Failed to upload profile picture.";
        header("Location: ../editProfile.php");
        exit;
    }
}

// ✅ Handle password update
$newPassword     = $_POST['newPassword'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';

if (!empty($newPassword)) {
    if ($newPassword !== $confirmPassword) {
        $_SESSION['error_message'] = "❌ Passwords do not match.";
        header("Location: ../editProfile.php");
        exit;
    }
    if (strlen($newPassword) < 6) {
        $_SESSION['error_message'] = "❌ Password must be at least 6 characters.";
        header("Location: ../editProfile.php");
        exit;
    }
    $updateData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
}

// ✅ Update in DB
$result = $db->users->updateOne(
    ['_id' => $userId],
    ['$set' => $updateData]
);

// Update session full name
$_SESSION['user']['name'] = $fullName;

$_SESSION['success_message'] = "✅ Profile updated successfully.";
header("Location: ../editProfile.php");
exit;
