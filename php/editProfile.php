<?php
require 'dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    // Harden cookies (set these in php.ini in prod)
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    // ini_set('session.cookie_secure', 1); // enable on HTTPS
    session_start();
}

// CSRF token
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

use MongoDB\BSON\ObjectId;

// auth gate
if (empty($_SESSION['user'])) {
    $_SESSION['error_message'] = "Please login first.";
    header("Location: login.php");
    exit;
}

$userIdStr = $_SESSION['user']['id'] ?? '';
try {
    $userId = new ObjectId($userIdStr);
} catch (\Throwable $e) {
    $_SESSION['error_message'] = "Invalid session user.";
    header("Location: login.php");
    exit;
}

$user = $db->users->findOne(['_id' => $userId]);
if (!$user) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: ../php/index.php");
    exit;
}

// name split (robust)
list($firstNamePrefill, $lastNamePrefill) = array_pad(preg_split('/\s+/', (string)($user['fullName'] ?? ''), 2), 2, '');

// avatar paths
$uploadDisk = realpath(__DIR__ . '/../uploads') ?: __DIR__ . '/../uploads';
$uploadWeb  = '../uploads/';
$storedFile = basename((string)($user['profilePicture'] ?? ''));
$avatarFile = ($storedFile && is_file($uploadDisk . '/' . $storedFile)) ? $storedFile : 'default.png';
$avatarUrl  = $uploadWeb . rawurlencode($avatarFile);

// flashes
$success = $_SESSION['success_message'] ?? '';
$error   = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile - Horse & Camel</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <script src="../js/bootstrap.bundle.min.js" defer></script>
  <style>
    body { background:#f8f9fa; }
    .profile-form {
      max-width: 700px; margin:40px auto; background:#fff; border-radius:10px;
      box-shadow:0 0 15px rgba(0,0,0,0.1); padding:30px;
    }
    .form-section:not(:last-child){ border-bottom:1px solid #dee2e6; margin-bottom:25px; padding-bottom:20px; }
    .flash-message{ animation: fadeIn .4s ease; }
    @keyframes fadeIn { from {opacity:0; transform: translateY(-8px);} to {opacity:1; transform:none;} }
  </style>
</head>
<body>

<div class="container">
  <form class="profile-form" method="POST" action="updateProfile.php" enctype="multipart/form-data" novalidate>
    <h3 class="text-center mb-4">👤 Edit Your Profile</h3>

    <?php if ($success): ?>
      <div class="alert alert-success text-center flash-message"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-danger text-center flash-message"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">

    <!-- Personal Info -->
    <div class="form-section">
      <h5>📇 Personal Information</h5>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">First Name</label>
          <input type="text" name="firstName" class="form-control" required
                 value="<?= htmlspecialchars($firstNamePrefill, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Last Name</label>
          <input type="text" name="lastName" class="form-control" required
                 value="<?= htmlspecialchars($lastNamePrefill, ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>

      <div class="mt-3">
        <label class="form-label">Phone Number</label>
        <input type="text" name="phone" class="form-control"
               value="<?= htmlspecialchars((string)($user['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="mt-3">
        <label class="form-label">Country</label>
        <input type="text" name="country" class="form-control"
               value="<?= htmlspecialchars((string)($user['country'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="mt-3">
        <label class="form-label">City</label>
        <input type="text" name="city" class="form-control"
               value="<?= htmlspecialchars((string)($user['city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="mt-3">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="3"
                  placeholder="Enter your full address"><?= htmlspecialchars((string)($user['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
      </div>

      <div class="mt-3">
        <label class="form-label">Zip Code</label>
        <input type="text" name="zipCode" class="form-control" placeholder="Enter your zip/postal code"
               value="<?= htmlspecialchars((string)($user['zipCode'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      </div>
    </div>

    <!-- Account / Email -->
    <div class="form-section">
      <h5>📧 Account Email</h5>
      <input type="email" name="email" class="form-control"
             value="<?= htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
      <small class="text-muted">Changing your email requires your current password and will log you out.</small>
    </div>

    <!-- Profile Picture -->
    <div class="form-section">
      <h5>🖼️ Profile Picture</h5>
      <div class="mb-2">
        <img id="avatarPreview" src="<?= $avatarUrl ?>" alt="Profile" class="img-thumbnail" style="height:100px;">
      </div>
      <input type="file" name="profilePicture" class="form-control" accept="image/png, image/jpeg, image/webp">
      <small class="text-muted">JPG/PNG/WEBP, max 2MB.</small>
    </div>

    <!-- Password Change -->
    <div class="form-section">
      <h5>🔒 Change Password</h5>
      <input type="password" name="newPassword" class="form-control mb-2" placeholder="New password (leave blank to keep current)">
      <input type="password" name="confirmPassword" class="form-control" placeholder="Confirm new password">
      <small class="text-muted d-block mt-2">To change your email or password, enter your current password below.</small>
      <input type="password" name="currentPassword" class="form-control mt-2" placeholder="Current password">
    </div>

    <!-- Account Created -->
    <div class="form-section">
      <h5>📅 Account Created On</h5>
      <p class="text-muted mb-0">
        <?= isset($user['createdAt']) ? date('F j, Y - H:i', $user['createdAt']->toDateTime()->getTimestamp()) : 'Not available' ?>
      </p>
    </div>

    <!-- Danger Zone -->
    <div class="form-section text-center">
      <h5 class="text-danger">⚠️ Danger Zone</h5>
      <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">🗑️ Delete My Account</button>
    </div>

    <!-- Back / Save -->
    <div class="d-flex justify-content-between align-items-center">
      <a href="index.php" class="btn btn-secondary">⬅️ Back to Home</a>
      <button type="submit" class="btn btn-success">💾 Save Changes</button>
    </div>
  </form>
</div>

<script>
// Flash fade-out
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.flash-message').forEach(el => {
    setTimeout(() => { el.style.transition='opacity .5s, transform .5s'; el.style.opacity='0'; el.style.transform='translateY(-6px)';
      setTimeout(()=>el.remove(), 500);
    }, 3000);
  });

  // Avatar preview
  const fileInput = document.querySelector('input[name="profilePicture"]');
  const preview = document.getElementById('avatarPreview');
  if (fileInput && preview) {
    fileInput.addEventListener('change', (e) => {
      const f = e.target.files[0];
      if (f) preview.src = URL.createObjectURL(f);
    });
  }
});

function confirmDelete() {
  if (confirm("Are you sure you want to delete your account? This action cannot be undone.")) {
    window.location.href = 'deleteAccount.php';
  }
}
</script>

</body>
</html>
