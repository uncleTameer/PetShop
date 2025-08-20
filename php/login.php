<?php
require 'dbConnect.php';

if (session_status() === PHP_SESSION_NONE) {
    // Safer cookies (enable 'secure' once you’re on HTTPS)
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    // ini_set('session.cookie_secure', 1);
    session_start();
}

use MongoDB\BSON\UTCDateTime;

/* -------------------- CSRF token -------------------- */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/* -------------------- Flash messages -------------------- */
/* IMPORTANT: capture BEFORE unsetting so Google OAuth errors show */
$error_flash   = $_SESSION['error_message']   ?? '';
$success_flash = $_SESSION['success_message'] ?? '';
unset($_SESSION['error_message'], $_SESSION['success_message']);

$message = '';
$postedEmail = '';

/* -------------------- POST handling -------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* CSRF check */
    $token = $_POST['csrf'] ?? '';
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        $message = "⛔ Invalid request. Please reload the page and try again.";
    }

    /* Inputs */
    $email = strtolower(trim($_POST['email'] ?? ''));
    $postedEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); // sticky value
    $password = $_POST['password'] ?? '';

    /* Optional soft IP throttle (per 15 minutes) */
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ipKey = 'ip_fail_' . $ip;
    $_SESSION[$ipKey] = $_SESSION[$ipKey] ?? ['n' => 0, 't' => time()];
    if (time() - $_SESSION[$ipKey]['t'] > 900) { // reset window after 15 min
        $_SESSION[$ipKey] = ['n' => 0, 't' => time()];
    }
    if ($message === '' && $_SESSION[$ipKey]['n'] > 40) {
        $message = "⛔ Too many attempts from this IP. Please wait and try again.";
    }

    /* Basic validation */
    if ($message === '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '')) {
        $message = "❌ Invalid email or password.";
    }

    if ($message === '') {
        // Fetch minimal fields
        $user = $db->users->findOne(
            ['email' => $email],
            ['projection' => [
                '_id' => 1, 'fullName' => 1, 'email' => 1, 'password' => 1,
                'role' => 1, 'profilePicture' => 1,
                'locked' => 1, 'lockoutTime' => 1, 'loginAttempts' => 1,
                'preferences' => 1
            ]]
        );

        // Timing-safe pad to avoid user enumeration
        static $DUMMY_HASH = null;
        if ($DUMMY_HASH === null) $DUMMY_HASH = password_hash('dummy_password', PASSWORD_DEFAULT);
        $hashToVerify = $user['password'] ?? $DUMMY_HASH;

        // Lockout policy
        $lockoutDuration = 15 * 60; // 15 minutes
        $maxAttempts     = 3;

        // If account locked, check window
        if ($user && !empty($user['locked'])) {
            $lockoutTime = (int)($user['lockoutTime'] ?? 0);
            $elapsed = time() - $lockoutTime;

            if ($elapsed < $lockoutDuration) {
                $remainingMinutes = ceil(($lockoutDuration - $elapsed) / 60);
                $message = "⛔ Account is locked due to multiple failed attempts. Try again in {$remainingMinutes} minute(s).";
            } else {
                // Unlock after timeout
                $db->users->updateOne(
                    ['_id' => $user['_id']],
                    ['$unset' => ['locked' => '', 'lockoutTime' => '', 'loginAttempts' => '']]
                );
                $user['locked'] = false;
                $user['loginAttempts'] = 0;
            }
        }

        // Proceed if not locked
        if ($message === '') {
            $ok = password_verify($password, $hashToVerify);

            if ($user && $ok) {
                // Reset counters/lock + audit last login info
                $db->users->updateOne(
                    ['_id' => $user['_id']],
                    [
                        '$unset' => ['loginAttempts' => '', 'lockoutTime' => '', 'locked' => ''],
                        '$set'   => [
                            'lastLoginAt' => new UTCDateTime(),
                            'lastLoginIp' => $ip,
                            'lastLoginUA' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300)
                        ]
                    ]
                );

                // Opportunistic rehash
                if (!empty($user['password']) && password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    $db->users->updateOne(
                        ['_id' => $user['_id']],
                        ['$set' => ['password' => password_hash($password, PASSWORD_DEFAULT)]]
                    );
                }

                session_regenerate_id(true);
                $_SESSION['csrf'] = bin2hex(random_bytes(32));

                $role = $user['role'] ?? 'user';
                $_SESSION['user'] = [
                    'id'       => (string)$user['_id'],
                    'name'     => (string)$user['fullName'],
                    'email'    => (string)$user['email'],
                    'role'     => $role,
                    'profilePicture' => $user['profilePicture'] ?? null
                ];

                if (!empty($user['preferences']['defaultCategoryId'])) {
                    $_SESSION['preferences']['defaultCategoryId'] = (string)$user['preferences']['defaultCategoryId'];
                }

                $_SESSION[$ipKey] = ['n' => 0, 't' => time()];
                $_SESSION['success_message'] = "🎉 Welcome back, " . htmlspecialchars((string)$user['fullName'], ENT_QUOTES, 'UTF-8') . "!";

                if ($role === 'admin') {
                    header("Location: ../admin/dashboard.php", true, 303);
                } else {
                    header("Location: index.php", true, 303); // /php/index.php
                }
                exit;

            } else {
                // Fail path
                $_SESSION[$ipKey]['n']++;

                if ($user) {
                    $loginAttempts = (int)($user['loginAttempts'] ?? 0) + 1;

                    if ($loginAttempts >= $maxAttempts) {
                        $db->users->updateOne(
                            ['_id' => $user['_id']],
                            ['$set' => [
                                'locked' => true,
                                'lockoutTime' => time(),
                                'loginAttempts' => $loginAttempts
                            ]]
                        );

                        // Simple admin notification
                        $admins = $db->users->find(['role' => 'admin'], ['projection' => ['_id' => 1]])->toArray();
                        foreach ($admins as $admin) {
                            $db->notifications->insertOne([
                                'type' => 'login_lockout',
                                'userId' => $user['_id'],
                                'userEmail' => $email,
                                'userName' => $user['fullName'] ?? '',
                                'timestamp' => new UTCDateTime(),
                                'message' => "User " . ($user['fullName'] ?? '') . " ({$email}) has been locked out due to multiple failed login attempts."
                            ]);
                        }

                        $message = "⛔ Account locked due to multiple failed attempts. Try again in 15 minutes.";
                    } else {
                        $db->users->updateOne(
                            ['_id' => $user['_id']],
                            ['$set' => ['loginAttempts' => $loginAttempts]]
                        );
                        $remaining = $maxAttempts - $loginAttempts;
                        $message = "❌ Invalid email or password. {$remaining} attempt(s) remaining.";
                    }
                } else {
                    $message = "❌ Invalid email or password.";
                }

                if ($_SESSION[$ipKey]['n'] > 40) {
                    $message = "⛔ Too many attempts from this IP. Please wait and try again.";
                }
            }
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
                  url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1650&q=80') no-repeat center center fixed;
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
      animation: fadeIn 0.8s ease;
      width: 100%;
      max-width: 420px;
      backdrop-filter: blur(5px);
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-12px);} to { opacity: 1; transform: none; } }
    .btn-primary, .btn-danger, .btn-outline-secondary { transition: all 0.3s ease; }
    .btn-primary:hover, .btn-danger:hover, .btn-outline-secondary:hover { transform: scale(1.04); }
    .flash-message { animation: slideDown 0.4s ease; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-10px);} to { opacity: 1; transform: none; } }
    .form-label { font-weight: bold; }
  </style>
</head>
<body>

<div class="auth-form">
  <h2 class="text-center mb-3">🔒 Welcome Back!</h2>
  <p class="text-center text-muted mb-4">Log in to continue shopping 🐴🐪</p>

  <?php if (!empty($success_flash)): ?>
    <div class="alert alert-success text-center flash-message"><?= htmlspecialchars($success_flash, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <?php if (!empty($error_flash)): ?>
    <div class="alert alert-danger text-center flash-message"><?= htmlspecialchars($error_flash, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <?php if (!empty($message)): ?>
    <div class="alert alert-danger text-center flash-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php" novalidate>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
    <div class="mb-3">
      <label class="form-label">Email Address</label>
      <input type="email" name="email" class="form-control" placeholder="example@email.com" required autofocus value="<?= $postedEmail ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" placeholder="Enter password" required>
    </div>

    <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>

    <a href="googleLogin.php" class="btn btn-danger w-100 mb-3">
      <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google logo" style="height:20px; margin-right:8px;">
      Sign in with Google
    </a>

    <div class="text-center mt-2">
      <small class="text-muted">Don't have an account?</small><br>
      <a href="register.php" class="btn btn-outline-secondary btn-sm mt-2">📝 Create New Account</a>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.flash-message').forEach((el) => {
    setTimeout(() => {
      el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      el.style.opacity = '0';
      el.style.transform = 'translateY(-8px)';
      setTimeout(() => el.remove(), 500);
    }, 3000);
  });
});
</script>

</body>
</html>
