<?php
require 'dbConnect.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
    } else {
        $existing = $db->users->findOne(['email' => $email]);
        if ($existing) {
            $message = "Email already exists.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $isAdmin = isset($_SESSION['user']['isAdmin']) && $_SESSION['user']['isAdmin'] && isset($_POST['isAdmin']);

            $insert = $db->users->insertOne([
                'fullName' => $fullName,
                'email' => $email,
                'password' => $hashedPassword,
                'isAdmin' => $isAdmin
            ]);

            if ($insert->getInsertedCount() === 1) {
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
  <title>Register - Pet Shop</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <script src="../js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<div class="container py-5">
  <h2 class="mb-4 text-center">📝 Register</h2>

  <?php if ($message): ?>
    <div class="alert alert-danger text-center"><?= $message ?></div>
  <?php endif; ?>

  <form method="POST" action="register.php" class="mx-auto" style="max-width: 400px;">
    <div class="mb-3">
      <label>Full Name</label>
      <input type="text" name="fullName" class="form-control" required>
    </div>

    <div class="mb-3">
      <label>Email</label>
      <input type="email" name="email" class="form-control" required>
    </div>

    <div class="mb-3">
      <label>Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>

    <?php if (isset($_SESSION['user']['isAdmin']) && $_SESSION['user']['isAdmin']): ?>
      <div class="form-check mb-3">
        <input type="checkbox" name="isAdmin" value="1" class="form-check-input" id="isAdminCheck">
        <label class="form-check-label" for="isAdminCheck">Register as Admin</label>
      </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary w-100">Register</button>
  </form>
</div>

</body>
</html>
