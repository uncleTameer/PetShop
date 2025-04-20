<?php
require 'dbConnect.php';
session_start();

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // ✅ Basic Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
    } else {
        // Check if user already exists
        $existing = $db->users->findOne(['email' => $email]);
        if ($existing) {
            $message = "Email already exists.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $isAdmin = isset($_POST['isAdmin']) && $_POST['isAdmin'] == '1';
            $insert = $db->users->insertOne([
                'fullName' => $fullName,
                'email' => $email,
                'password' => $hashedPassword,
                'isAdmin' => $isAdmin
            ]);

            if ($insert->getInsertedCount() == 1) {
                $_SESSION['user'] = [
                    'id' => (string)$insert->getInsertedId(),
                    'name' => $fullName,
                    'email' => $email,
                    'isAdmin' => $isAdmin
                ];
                $_SESSION['success_message'] = "🎉 Registration successful! Welcome, $fullName.";
                header("Location: " . ($isAdmin ? "../admin/dashboard.php" : "../index.php"));
                exit;
            } else {
                $message = "Something went wrong. Try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <script src="../js/bootstrap.bundle.min.js" defer></script>
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
  <div class="card shadow p-4" style="width: 100%; max-width: 450px;">
    <h3 class="text-center mb-4">🐾 Create Account</h3>

    <?php if ($message): ?>
      <div class="alert alert-danger text-center"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php">
      <div class="form-floating mb-3">
        <input type="text" name="fullName" id="fullName" class="form-control" placeholder="Full Name" required>
        <label for="fullName">Full Name</label>
      </div>

      <div class="form-floating mb-3">
        <input type="email" name="email" id="email" class="form-control" placeholder="Email" required>
        <label for="email">Email</label>
      </div>

      <div class="form-floating mb-3">
        <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
        <label for="password">Password (min 6 chars)</label>
      </div>

      <?php if (isset($_SESSION['user']) && $_SESSION['user']['isAdmin']): ?>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" name="isAdmin" id="isAdmin" value="1">
          <label class="form-check-label" for="isAdmin">
            Register as Admin
          </label>
        </div>
      <?php endif; ?>

      <button type="submit" class="btn btn-primary w-100">🚀 Register</button>
    </form>

    <div class="text-center mt-3">
      <small>Already have an account? <a href="login.php">Login</a></small>
    </div>
  </div>
</div>

</body>
</html>
