<?php
require 'dbConnect.php';
session_start();

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = $db->users->findOne(['email' => $email]);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => (string)$user->_id,
            'name' => $user['fullName'],
            'email' => $user['email'],
            'isAdmin' => $user['email'] === 'admin@admin.com'
        ];

        header("Location: ../" . ($_SESSION['user']['isAdmin'] ? "admin/dashboard.php" : "index.php"));
        exit;
    } else {
        $message = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - Pet Shop</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <script src="../js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<div class="container py-5">
  <h2 class="mb-4 text-center">🔐 Login</h2>

  <?php if ($message): ?>
    <div class="alert alert-danger text-center"><?= $message ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php" class="mx-auto" style="max-width: 400px;">
    <div class="mb-3">
      <label>Email</label>
      <input type="email" name="email" class="form-control" required>
    </div>

    <div class="mb-3">
      <label>Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-primary w-100">Login</button>

    <hr class="my-4">

    <a href="../googleLogin.php" class="btn btn-danger w-100">
      <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google logo" style="height: 20px; margin-right: 10px;">
      Sign in with Google
    </a>
  </form>
</div>

</body>
</html>
