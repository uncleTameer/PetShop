<?php
require 'dbConnect.php';

if (session_status() === PHP_SESSION_NONE) {
    // (Optional) safer cookies; enable 'secure' when on HTTPS
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    // ini_set('session.cookie_secure', 1);
    session_start();
}

// CSRF token (create if missing)
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

use MongoDB\BSON\ObjectId; // ✅ correct casing

$message = '';

// Load categories for the select (sorted by name)
$categoriesCursor = $db->categories->find([], ['sort' => ['name' => 1]]);
$categories = iterator_to_array($categoriesCursor, false);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF check FIRST
    $token = $_POST['csrf'] ?? '';
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        $message = "⛔ Invalid request. Please reload the page and try again.";
    }

    if ($message === '') {
        // Pull + normalize
        $firstName  = trim($_POST['firstName']  ?? '');
        $lastName   = trim($_POST['lastName']   ?? '');
        $fullName   = trim(preg_replace('/\s+/', ' ', $firstName . ' ' . $lastName));
        $emailRaw   = trim($_POST['email']      ?? '');
        $email      = strtolower($emailRaw); // ✅ normalize for uniqueness
        $confirmEmail = strtolower(trim($_POST['confirmEmail'] ?? ''));
        $password   = $_POST['password'] ?? '';
        $address    = trim($_POST['address']    ?? '');
        $zipCode    = trim($_POST['zipCode']    ?? '');
        $defaultCategoryRaw = trim($_POST['defaultCategory'] ?? '');
        $termsAccepted = isset($_POST['terms']); // ✅ fixed name

        // Basic validations (server-side, not trusting only HTML)
        if (!$termsAccepted) {
            $message = "❌ You must accept the Terms and Conditions.";
        } elseif ($firstName === '' || $lastName === '') {
            $message = "❌ Please provide your first and last name.";
        } elseif ($address === '' || $zipCode === '') {
            $message = "❌ Address and ZIP/Postal Code are required.";
        } elseif ($email === '' || $confirmEmail === '') {
            $message = "❌ Please fill in both email fields.";
        } elseif ($email !== $confirmEmail) {
            $message = "❌ Emails do not match.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "❌ Invalid email format.";
        } elseif (strlen($password) < 6) {
            $message = "❌ Password must be at least 6 characters.";
        } elseif ($defaultCategoryRaw === '') {
            $message = "❌ Please choose your default category.";
        } else {
            // Validate category id + existence
            try {
                $defaultCategoryId = new ObjectId($defaultCategoryRaw);
            } catch (Exception $e) {
                $message = "❌ Invalid category.";
            }

            if (empty($message)) {
                $catExists = $db->categories->findOne(
                    ['_id' => $defaultCategoryId],
                    ['projection' => ['_id' => 1]]
                );
                if (!$catExists) {
                    $message = "❌ Selected category does not exist.";
                }
            }

            if (empty($message)) {
                // Ensure unique email (recommend: create a unique index on users.email)
                $existing = $db->users->findOne(['email' => $email], ['projection' => ['_id' => 1]]);
                if ($existing) {
                    $message = "❌ Email already exists.";
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    // Determine role (admins can promote others; ignore arbitrary values)
                    $role = 'user';
                    if (!empty($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') {
                        $role = (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'admin' : 'user';
                    }

                    // Insert user with default category preference + audit fields
                    $nowUtc = new MongoDB\BSON\UTCDateTime();
                    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300);

                    $insert = $db->users->insertOne([
                        'fullName'    => $fullName,
                        'email'       => $email,
                        'password'    => $hashedPassword,
                        'role'        => $role,
                        'address'     => $address,
                        'zipCode'     => $zipCode,
                        'preferences' => [
                            'defaultCategoryId' => $defaultCategoryId
                        ],
                        'createdAt'   => $nowUtc,
                        'createdIp'   => $ip,
                        'createdUA'   => $ua,
                        // seed last-login with creation time
                        'lastLoginAt' => $nowUtc,
                        'lastLoginIp' => $ip,
                        'lastLoginUA' => $ua
                    ]);

                    if ($insert->getInsertedCount() === 1) {
                        // Create session
                        $_SESSION['user'] = [
                            'id'    => (string)$insert->getInsertedId(),
                            'name'  => $fullName,
                            'email' => $email,
                            'role'  => $role
                        ];

                        // Cache preference in session so shop uses it immediately
                        $_SESSION['preferences']['defaultCategoryId'] = (string)$defaultCategoryId;

                        // Rotate CSRF token after privileged action
                        $_SESSION['csrf'] = bin2hex(random_bytes(32));

                        $_SESSION['success_message'] = "Registration successful! Welcome, " . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . ".";
                        header("Location: " . ($role === 'admin' ? "../admin/dashboard.php" : "../php/index.php"));
                        exit;
                    } else {
                        $message = "❌ Something went wrong. Please try again.";
                    }
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
    .form-icon { font-size: 2rem; }
  </style>
</head>
<body>

<div class="auth-form">
  <h2 class="text-center mb-3">📝 Create Account</h2>
  <p class="text-center text-muted mb-4">Join our family! 🐎🐫</p>

  <?php if (!empty($message)): ?>
    <div class="alert alert-danger text-center flash-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <form method="POST" action="register.php" novalidate>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">

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
      <label class="form-label">Address</label>
      <input type="text" name="address" class="form-control" placeholder="Your full address" required>
    </div>

    <div class="mt-3">
      <label class="form-label">ZIP/Postal Code</label>
      <input type="text" name="zipCode" class="form-control" placeholder="ZIP or postal code" required>
    </div>

    <div class="mt-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" id="passwordInput" class="form-control" placeholder="At least 6 characters" required>
      <small class="text-muted">Minimum 6 characters.</small>
      <div class="progress mt-2">
        <div id="passwordStrength" class="progress-bar" style="width: 0%;"></div>
      </div>
    </div>

    <div class="mt-3">
      <label class="form-label">Default Category</label>
      <select name="defaultCategory" class="form-select" required <?= empty($categories) ? 'disabled' : '' ?>>
        <option value="">— Choose a category —</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= (string)$cat['_id'] ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (empty($categories)): ?>
        <div class="text-danger small mt-1">No categories found. Please ask an admin to add categories first.</div>
      <?php endif; ?>
    </div>

    <div class="form-check mt-3 mb-3">
      <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
      <label class="form-check-label" for="terms">
        I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>.
      </label>
    </div>

    <button type="submit" class="btn btn-success w-100 mt-2" <?= empty($categories) ? 'disabled' : '' ?>>Register</button>

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
  if (passwordInput && strengthBar) {
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
  }
});
</script>

</body>
</html>
