<?php
require 'dbConnect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $fullName = $firstName . ' ' . $lastName;
    $email = trim($_POST['email'] ?? '');
    $confirmEmail = trim($_POST['confirmEmail'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!isset($_POST['terms'])) {
        $message = "❌ You must accept the Terms and Conditions.";
    } elseif ($email !== $confirmEmail) {
        $message = "❌ Emails do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Invalid email format.";
    } elseif (strlen($password) < 6) {
        $message = "❌ Password must be at least 6 characters.";
    } else {
        $existing = $db->users->findOne(['email' => $email]);
        if ($existing) {
            $message = "❌ Email already exists.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Determine role (admins can promote others)
            $role = 'user';
            if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') {
                if (isset($_POST['role']) && $_POST['role'] === 'admin') {
                    $role = 'admin';
                }
            }

            $insert = $db->users->insertOne([
                'fullName'   => $fullName,
                'email'      => $email,
                'password'   => $hashedPassword,
                'role'       => $role,
                'createdAt'  => new MongoDB\BSON\UTCDateTime()
            ]);

            if ($insert->getInsertedCount() === 1) {
                $_SESSION['user'] = [
                    'id'    => (string)$insert->getInsertedId(),
                    'name'  => $fullName,
                    'email' => $email,
                    'role'  => $role
                ];
                $_SESSION['success_message'] = "🎉 Registration successful! Welcome, $fullName.";
                header("Location: " . ($role === 'admin' ? "../admin/dashboard.php" : "../index.php"));
                exit;
            } else {
                $message = "❌ Something went wrong. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - Horse & Camel</title>
  <link href="../css/bootstrap.min.css" rel="stylesheet">
  <script src="../js/bootstrap.bundle.min.js" defer></script>
  <style>
    body {
      background: url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1650&q=80') no-repeat center center fixed;
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
      width: 100%;
      max-width: 500px;
      animation: fadeIn 1s ease forwards;
    }
    @keyframes fadeIn {
      0% { opacity: 0; transform: translateY(-20px); }
      100% { opacity: 1; transform: translateY(0); }
    }
    .form-icon {
      font-size: 2rem;
    }
  </style>
</head>
<body>

<div class="auth-form">
  <h2 class="text-center mb-3">📝 Create Account</h2>
  <p class="text-center text-muted mb-4">Join our family! 🐎🐫</p>

  <?php if (!empty($message)): ?>
    <div class="alert alert-danger text-center flash-message"><?= $message ?></div>
  <?php endif; ?>

  <form method="POST" action="register.php" novalidate>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">First Name</label>
        <input type="text" name="firstName" class="form-control" placeholder="John" required>
      </div>

      <div class="col-md-6">
        <label class="form-label">Last Name</label>
        <input type="text" name="lastName" class="form-control" placeholder="Doe" required>
      </div>
    </div>

    <div class="mt-3">
      <label class="form-label">Email Address</label>
      <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
    </div>

    <div class="mt-3">
      <label class="form-label">Confirm Email</label>
      <input type="email" name="confirmEmail" class="form-control" placeholder="Re-type your email" required>
    </div>

    <div class="mt-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" id="passwordInput" class="form-control" placeholder="At least 6 characters" required>
      <small class="text-muted">Minimum 6 characters.</small>
      <div class="progress mt-2">
        <div id="passwordStrength" class="progress-bar" style="width: 0%;"></div>
      </div>
    </div>

    <div class="form-check mt-3 mb-3">
      <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
      <label class="form-check-label" for="terms">
        I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>.
      </label>
    </div>

    <button type="submit" class="btn btn-success w-100 mt-2">Register</button>

    <div class="text-center mt-3">
      <small class="text-muted">Already have an account?</small><br>
      <a href="login.php" class="btn btn-outline-primary btn-sm mt-2">🔑 Login</a>
    </div>
  </form>
</div>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Welcome to Horse & Camel! 🐎🐫</p>
        <ul>
          <li>Respect community rules.</li>
          <li>Provide honest and accurate information.</li>
          <li>Use the website for personal purposes only.</li>
          <li>Your data is protected but not guaranteed 100% secure.</li>
          <li>Accounts misused may be suspended.</li>
        </ul>
        <p>Thank you for trusting us! ❤️</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
// Flash message fade out
document.addEventListener('DOMContentLoaded', function() {
  const flash = document.querySelector('.flash-message');
  if (flash) {
    setTimeout(() => {
      flash.style.transition = 'opacity 0.5s ease';
      flash.style.opacity = '0';
      setTimeout(() => flash.remove(), 500);
    }, 3000);
  }

  // Password strength bar
  const passwordInput = document.getElementById('passwordInput');
  const strengthBar = document.getElementById('passwordStrength');
  passwordInput.addEventListener('input', function() {
    const val = passwordInput.value;
    let strength = 0;
    if (val.length >= 6) strength += 1;
    if (/[A-Z]/.test(val)) strength += 1;
    if (/[0-9]/.test(val)) strength += 1;
    if (/[^A-Za-z0-9]/.test(val)) strength += 1;

    strengthBar.style.width = (strength * 25) + '%';
    if (strength <= 1) {
      strengthBar.className = 'progress-bar bg-danger';
    } else if (strength === 2) {
      strengthBar.className = 'progress-bar bg-warning';
    } else {
      strengthBar.className = 'progress-bar bg-success';
    }
  });
});
</script>

</body>
</html>


