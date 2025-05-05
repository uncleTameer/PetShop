<?php
require 'dbConnect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Invalid email format.";
    } else {
        $user = $db->users->findOne(['email' => $email]);

        if (!$user) {
            $message = "❌ No account found with that email.";
        } elseif (!password_verify($password, $user['password'])) {
            $message = "❌ Incorrect password.";
        } else {
            // Login successful
            $_SESSION['user'] = [
                'id' => (string)$user['_id'],
                'name' => $user['fullName'] ?? $user['name'],
                'email' => $user['email'],
                'isAdmin' => $user['isAdmin'] ?? false
            ];
            $_SESSION['success_message'] = "🎉 Welcome back, {$_SESSION['user']['name']}!";
            header("Location: " . ($_SESSION['user']['isAdmin'] ? "../admin/dashboard.php" : "../index.php"));
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - Horse & Camel</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <script src="../js/bootstrap.bundle.min.js" defer></script>
  <style>
    body {
      background: url('https://images.unsplash.com/photo-1549924231-f129b911e442?ixlib=rb-4.0.3&auto=format&fit=crop&w=1650&q=80') no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .auth-form {
      background-color: rgba(255, 255, 255, 0.95);
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
      animation: fadeIn 1s ease forwards;
      width: 100%;
      max-width: 450px;
    }
    @keyframes fadeIn {
      0% { opacity: 0; transform: translateY(-20px); }
      100% { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

<div class="auth-form">
  <h2 class="text-center mb-3">🔒 Welcome Back!</h2>
  <p class="text-center text-muted mb-4">Log in to continue shopping 🐴🐪</p>

  <?php if ($message): ?>
    <div class="alert alert-danger text-center flash-message"><?= $message ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php">
    <div class="mb-3">
      <label class="form-label">Email Address</label>
      <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" placeholder="Enter password" required>
    </div>

    <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>

    <a href="../googleLogin.php" class="btn btn-danger w-100 mb-3">
      <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google logo" style="height: 20px; margin-right: 8px;">
      Sign in with Google
    </a>

    <div class="text-center mt-2">
      <small class="text-muted">Don't have an account?</small><br>
      <a href="register.php" class="btn btn-outline-secondary btn-sm mt-2">📝 Create New Account</a>
    </div>
  </form>
</div>

<script>
// Auto-hide flash message after 3 seconds
document.addEventListener('DOMContentLoaded', function() {
  const alertBox = document.querySelector('.flash-message');
  if (alertBox) {
    setTimeout(() => {
      alertBox.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      alertBox.style.opacity = '0';
      alertBox.style.transform = 'translateY(-10px)';
      setTimeout(() => alertBox.remove(), 500);
    }, 3000);
  }
});
</script>

</body>
</html>