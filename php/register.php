<?php
require 'dbConnect.php';
session_start();

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check if user already exists
    $existing = $db->users->findOne(['email' => $email]);
    if ($existing) {
        $message = "Email already exists.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $insert = $db->users->insertOne([
            'fullName' => $fullName,
            'email' => $email,
            'password' => $hashedPassword
        ]);

        if ($insert->getInsertedCount() == 1) {
            $_SESSION['user'] = [
                'id' => (string)$insert->getInsertedId(),
                'name' => $fullName,
                'email' => $email,
                'isAdmin' => $email === 'admin@admin.com'
            ];
            header("Location: " . ($_SESSION['user']['isAdmin'] ? "../admin/dashboard.php" : "../index.html"));
            exit;
        } else {
            $message = "Something went wrong. Try again.";
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
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Register</button>
  </form>
</body>
</html>
