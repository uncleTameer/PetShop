<?php
require 'dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$userId = new MongoDB\BSON\ObjectId($_SESSION['user']['id']);
$user = $db->users->findOne(['_id' => $userId]);

if (!$user) {
    echo "<script>alert('User not found.'); window.location.href = 'index.php';</script>";
    exit;
}

$profileFile = !empty($user['profilePicture']) && file_exists("uploads/" . $user['profilePicture'])
    ? htmlspecialchars($user['profilePicture'])
    : 'default.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile - Horse & Camel</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <script src="js/bootstrap.bundle.min.js" defer></script>
  <style>
    body {
      background: #f8f9fa;
    }
    .profile-form {
      max-width: 700px;
      margin: 40px auto;
      background: white;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      padding: 30px;
    }
    .form-section:not(:last-child) {
      border-bottom: 1px solid #dee2e6;
      margin-bottom: 25px;
      padding-bottom: 20px;
    }
  </style>
</head>
<body>

<div class="container">
  <form class="profile-form" method="POST" action="updateProfile.php" enctype="multipart/form-data">
    <h3 class="text-center mb-4">👤 Edit Your Profile</h3>

    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success text-center"><?= $_SESSION['success_message'] ?></div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-danger text-center"><?= $_SESSION['error_message'] ?></div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Personal Info Section -->
    <div class="form-section">
      <h5>📇 Personal Information</h5>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">First Name</label>
          <input type="text" name="firstName" class="form-control" required value="<?= htmlspecialchars(explode(' ', $user['fullName'])[0] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Last Name</label>
          <input type="text" name="lastName" class="form-control" required value="<?= htmlspecialchars(explode(' ', $user['fullName'])[1] ?? '') ?>">
        </div>
      </div>

      <div class="mt-3">
        <label class="form-label">Phone Number</label>
        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
      </div>

      <div class="mt-3">
        <label class="form-label">Country</label>
        <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($user['country'] ?? '') ?>">
      </div>

      <div class="mt-3">
        <label class="form-label">City</label>
        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($user['city'] ?? '') ?>">
      </div>

      <div class="mt-3">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="3" placeholder="Enter your full address"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
      </div>

      <div class="mt-3">
        <label class="form-label">Zip Code</label>
        <input type="text" name="zipCode" class="form-control" value="<?= htmlspecialchars($user['zipCode'] ?? '') ?>" placeholder="Enter your zip/postal code">
      </div>
    </div>

    <!-- Profile Picture Upload -->
    <div class="form-section">
      <h5>🖼️ Profile Picture</h5>
      <div class="mb-2">
        <img src="uploads/<?= $profileFile ?>" alt="Profile" class="img-thumbnail" style="height: 100px;">
      </div>
      <input type="file" name="profilePicture" class="form-control">
    </div>

    <!-- Password Change -->
    <div class="form-section">
      <h5>🔒 Change Password</h5>
      <input type="password" name="newPassword" class="form-control mb-2" placeholder="New password (leave blank to keep current)">
      <input type="password" name="confirmPassword" class="form-control" placeholder="Confirm new password">
    </div>

    <!-- Account Info -->
    <div class="form-section">
      <h5>📅 Account Created On</h5>
      <p class="text-muted">
        <?= isset($user['createdAt']) ? date('F j, Y - H:i', $user['createdAt']->toDateTime()->getTimestamp()) : 'Not available' ?>
      </p>
    </div>

    <!-- Delete Account -->
    <div class="form-section text-center">
      <h5 class="text-danger">⚠️ Danger Zone</h5>
      <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">🗑️ Delete My Account</button>
    </div>

<!-- Back and Save Buttons -->
<div class="text-end d-flex justify-content-between align-items-center">
  <a href="index.php" class="btn btn-secondary">⬅️ Back to Home</a>
  <button type="submit" class="btn btn-success">💾 Save Changes</button>
</div>


<script>
  function confirmDelete() {
    if (confirm("Are you sure you want to delete your account? This action cannot be undone.")) {
      window.location.href = 'deleteAccount.php';
    }
  }
</script>

</body>
</html>
