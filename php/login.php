<?php
require 'dbConnect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Always clean leftover messages
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Invalid email format.";
    } else {
        $user = $db->users->findOne(['email' => $email]);

        if ($user) {
            // Check if account is locked
            if (!empty($user['locked']) && $user['locked'] === true) {
                $lockoutTime = $user['lockoutTime'] ?? 0;
                $currentTime = time();
                $lockoutDuration = 10; // 10 seconds lockout
                
                if ($currentTime - $lockoutTime < $lockoutDuration) {
                    $remainingTime = ceil(($lockoutDuration - ($currentTime - $lockoutTime)) / 60);
                    $message = "⛔ Account is locked due to multiple failed login attempts. Please try again in {$remainingTime} minutes.";
                } else {
                    // Unlock account after timeout
                    $db->users->updateOne(
                        ['email' => $email],
                        ['$unset' => ['locked' => '', 'lockoutTime' => '', 'loginAttempts' => '']]
                    );
                    $user['locked'] = false;
                    $user['loginAttempts'] = 0;
                }
            }
            
            // If account is not locked, proceed with login
            if (empty($user['locked']) || $user['locked'] !== true) {
                if (password_verify($password, $user['password'])) {
                    // Reset login attempts on successful login
                    $db->users->updateOne(
                        ['email' => $email],
                        ['$unset' => ['loginAttempts' => '', 'lockoutTime' => '']]
                    );
                    
                    // ✅ Role assignment
                    $role = $user['role'] ?? 'user';

                    $_SESSION['user'] = [
                        'id'       => (string)$user->_id,
                        'name'     => $user['fullName'],
                        'email'    => $user['email'],
                        'role'     => $role,
                        'profilePicture' => $user['profilePicture'] ?? null
                    ];

                    $_SESSION['success_message'] = "🎉 Welcome back, " . htmlspecialchars($user['fullName']) . "!";

                    // ✅ Redirect based on role
                    if ($role === 'admin') {
                                header("Location: ../admin/dashboard.php");
    } else {
        header("Location: index.php");
                    }
                    exit;
                } else {
                    // Increment failed login attempts
                    $loginAttempts = ($user['loginAttempts'] ?? 0) + 1;
                    $maxAttempts = 3;
                    
                    if ($loginAttempts >= $maxAttempts) {
                        // Lock account
                        $db->users->updateOne(
                            ['email' => $email],
                            [
                                '$set' => [
                                    'locked' => true,
                                    'lockoutTime' => time(),
                                    'loginAttempts' => $loginAttempts
                                ]
                            ]
                        );
                        
                        // Send notification to admins
                        $admins = $db->users->find(['role' => 'admin'])->toArray();
                        foreach ($admins as $admin) {
                            $notification = [
                                'type' => 'login_lockout',
                                'userId' => $user->_id,
                                'userEmail' => $email,
                                'userName' => $user['fullName'],
                                'timestamp' => new MongoDB\BSON\UTCDateTime(),
                                'message' => "User {$user['fullName']} ({$email}) has been locked out due to multiple failed login attempts."
                            ];
                            $db->notifications->insertOne($notification);
                        }
                        
                        $message = "⛔ Account locked due to multiple failed login attempts. Please try again in 15 minutes.";
                    } else {
                        // Update login attempts
                        $db->users->updateOne(
                            ['email' => $email],
                            ['$set' => ['loginAttempts' => $loginAttempts]]
                        );
                        
                        $remainingAttempts = $maxAttempts - $loginAttempts;
                        $message = "❌ Invalid email or password. {$remainingAttempts} attempts remaining.";
                    }
                }
            }
        } else {
            $message = "❌ Invalid email or password.";
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
      background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), 
                  url('https://sdmntprwestus.oaiusercontent.com/files/00000000-5334-6230-a493-b1ed1b78023a/raw?se=2025-07-29T15%3A42%3A01Z&sp=r&sv=2024-08-04&sr=b&scid=2a4aa1a8-1374-5eb8-be0d-eead747c67cf&skoid=789f404f-91a9-4b2f-932c-c44965c11d82&sktid=a48cca56-e6da-484e-a814-9c849652bcb3&skt=2025-07-28T19%3A08%3A09Z&ske=2025-07-29T19%3A08%3A09Z&sks=b&skv=2024-08-04&sig=uaIMRfjGaX/1Wo1SYHkemAXuHuzIaWBCDTTFHbRrmqk%3D') no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .auth-form {
      background: rgba(255, 255, 255, 0.9);
      padding: 45px 35px;
      border-radius: 15px;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
      animation: fadeIn 1s ease;
      width: 100%;
      max-width: 420px;
      backdrop-filter: blur(5px);
    }

    @keyframes fadeIn {
      0% { opacity: 0; transform: translateY(-20px); }
      100% { opacity: 1; transform: translateY(0); }
    }

    .btn-primary, .btn-danger, .btn-outline-secondary {
      transition: all 0.3s ease;
    }

    .btn-primary:hover, .btn-danger:hover, .btn-outline-secondary:hover {
      transform: scale(1.04);
    }

    .flash-message {
      animation: slideDown 0.5s ease;
    }

    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .form-label {
      font-weight: bold;
    }
  </style>
</head>
<body>

<div class="auth-form">
  <div class="mx-auto shadow p-5 rounded" style="max-width: 450px; background-color: #f8f9fa;">
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

      <a href="googleLogin.php" class="btn btn-danger w-100 mb-3">
        <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google logo" style="height: 20px; margin-right: 8px;">
        Sign in with Google
      </a>

      <div class="text-center mt-2">
        <small class="text-muted">Don't have an account?</small><br>
        <a href="register.php" class="btn btn-outline-secondary btn-sm mt-2">📝 Create New Account</a>
      </div>
    </form>
  </div>
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
