<?php
require 'dbConnect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

function flash_and_redirect($type, $msg, $to) {
    $_SESSION[$type === 'error' ? 'error_message' : 'success_message'] = $msg;
    header("Location: {$to}");
    exit;
}

/* -------------------- CSRF -------------------- */
$token = $_POST['csrf'] ?? '';
if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
    flash_and_redirect('error', "⛔ Invalid request. Please try again.", "editProfile.php");
}

/* -------------------- Session / User -------------------- */
if (empty($_SESSION['user']['id'])) {
    flash_and_redirect('error', "Session expired.", "login.php");
}
$userIdStr = $_SESSION['user']['id']; // <- was missing before

try {
    $userId = new ObjectId($userIdStr); // throws if not 24-hex
} catch (\Throwable $e) {
    flash_and_redirect('error', "Invalid session user.", "login.php");
}

// Load current user (need email/password/profilePicture)
$user = $db->users->findOne(
    ['_id' => $userId],
    ['projection' => ['email'=>1,'password'=>1,'profilePicture'=>1]]
);
if (!$user) {
    flash_and_redirect('error', "User not found.", "index.php");
}

/* -------------------- Inputs -------------------- */
$firstName = trim($_POST['firstName'] ?? '');
$lastName  = trim($_POST['lastName'] ?? '');
$fullName  = trim(preg_replace('/\s+/', ' ', $firstName . ' ' . $lastName));
$phone     = trim($_POST['phone'] ?? '');
$country   = trim($_POST['country'] ?? '');
$city      = trim($_POST['city'] ?? '');
$address   = trim($_POST['address'] ?? '');
$zipCode   = trim($_POST['zipCode'] ?? '');

// Email change (optional)
$emailInput     = $_POST['email'] ?? '';
$emailNew       = strtolower(trim($emailInput));
$currentEmail   = strtolower(trim((string)($user['email'] ?? '')));
$emailChanged   = ($emailNew !== '' && $emailNew !== $currentEmail);

// Password change (optional)
$currentPassword    = $_POST['currentPassword'] ?? '';
$newPassword        = $_POST['newPassword'] ?? '';
$confirmPassword    = $_POST['confirmPassword'] ?? '';
$wantsPwdChange     = ($newPassword !== '' || $confirmPassword !== '');

/* -------------------- Validation -------------------- */
if ($firstName === '' || $lastName === '') {
    flash_and_redirect('error', "❌ First and last name are required.", "editProfile.php");
}
if (mb_strlen($fullName) > 100) {
    flash_and_redirect('error', "❌ Name is too long.", "editProfile.php");
}
if ($emailChanged && !filter_var($emailNew, FILTER_VALIDATE_EMAIL)) {
    flash_and_redirect('error', "❌ Please provide a valid email address.", "editProfile.php");
}

if ($wantsPwdChange) {
    if ($newPassword !== $confirmPassword) {
        flash_and_redirect('error', "❌ New passwords do not match.", "editProfile.php");
    }
    if (strlen($newPassword) < 6) {
        flash_and_redirect('error', "❌ New password must be at least 6 characters.", "editProfile.php");
    }
}

// Only verify current password if changing email or password
if ($emailChanged || $wantsPwdChange) {
    $hash = $user['password'] ?? password_hash('dummy', PASSWORD_DEFAULT);
    if (!password_verify($currentPassword, $hash)) {
        $msg = $emailChanged
            ? "❌ Please enter your current password to change email."
            : "❌ Current password is incorrect.";
        flash_and_redirect('error', $msg, "editProfile.php");
    }
}

/* -------------------- Build update doc -------------------- */
$updateSet = [
    'fullName'  => $fullName,
    'phone'     => $phone,
    'country'   => $country,
    'city'      => $city,
    'address'   => $address,
    'zipCode'   => $zipCode,
    'updatedAt' => new UTCDateTime()
];

/* -------------------- Avatar upload (optional, hardened) -------------------- */
if (!empty($_FILES['profilePicture']['name'])) {
    $file = $_FILES['profilePicture'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        flash_and_redirect('error', "❌ Failed to upload profile picture.", "editProfile.php");
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        flash_and_redirect('error', "❌ Invalid upload.", "editProfile.php");
    }
    if ((int)$file['size'] > 2 * 1024 * 1024) { // 2MB
        flash_and_redirect('error', "❌ Avatar too large (max 2MB).", "editProfile.php");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']) ?: '';
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($allowed[$mime])) {
        flash_and_redirect('error', "❌ Invalid avatar format. Use JPG/PNG/WEBP.", "editProfile.php");
    }

    $uploadDir = realpath(__DIR__ . '/../uploads');
    if ($uploadDir === false) {
        flash_and_redirect('error', "❌ Uploads folder not found.", "editProfile.php");
    }

    $ext = $allowed[$mime];
    $newFileName = 'avatar_' . (string)$userId . '_' . time() . '.' . $ext;
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        flash_and_redirect('error', "❌ Failed to save profile picture.", "editProfile.php");
    }

    // delete old if present & not default
    if (!empty($user['profilePicture']) && $user['profilePicture'] !== 'default.png') {
        $old = $uploadDir . DIRECTORY_SEPARATOR . basename((string)$user['profilePicture']);
        if (is_file($old)) @unlink($old);
    }

    $updateSet['profilePicture'] = $newFileName;
    $_SESSION['user']['profilePicture'] = $newFileName;
}

/* -------------------- Password change -------------------- */
if ($wantsPwdChange) {
    $updateSet['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
}

/* -------------------- Email change (unique + set) -------------------- */
if ($emailChanged) {
    $exists = $db->users->findOne(
        ['email' => $emailNew, '_id' => ['$ne' => $userId]],
        ['projection' => ['_id' => 1]]
    );
    if ($exists) {
        flash_and_redirect('error', "❌ Email is already in use.", "editProfile.php");
    }
    $updateSet['email'] = $emailNew;
}

/* -------------------- Persist -------------------- */
$res = $db->users->updateOne(['_id' => $userId], ['$set' => $updateSet]);

if ($res->getMatchedCount() !== 1) {
    flash_and_redirect('error', "❌ Account not found.", "editProfile.php");
}

/* -------------------- Session + redirect -------------------- */
$_SESSION['user']['name'] = $fullName;

// Rotate CSRF after sensitive update
$_SESSION['csrf'] = bin2hex(random_bytes(32));

if ($emailChanged) {
    // Force logout so they re-auth with new email
    session_regenerate_id(true);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    session_start();
    $_SESSION['success_message'] = "✅ Email updated. Please log in again using your new address.";
    header("Location: login.php"); // same folder
    exit;
}

// Friendly message when nothing changed
$_SESSION['success_message'] =
    $res->getModifiedCount() >= 1
    ? "✅ Profile updated successfully."
    : "ℹ️ No changes detected; profile is already up to date.";

header("Location: editProfile.php");
exit;
