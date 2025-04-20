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
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <h2>Register</h2>
  <?php if ($message): ?>
    <p style="color:red;"><?= $message ?></p>
  <?php endif; ?>
  <form method="POST" action="register.php">
  <input type="text" name="fullName" placeholder="Full Name" required><br><br>
  <input type="email" name="email" placeholder="Email" required><br><br>
  <input type="password" name="password" placeholder="Password (min 6 chars)" required><br><br>

  <?php if (isset($_SESSION['user']) && $_SESSION['user']['isAdmin']): ?>
  <label>
    <input type="checkbox" name="isAdmin" value="1">
    Register as Admin
  </label><br><br>
  <?php endif; ?>

  <button type="submit">Register</button>
</form>
</body>
</html>
