<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $fullName = $firstName . ' ' . $lastName;
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $address = trim($_POST['address'] ?? '');
    $zipCode = trim($_POST['zipCode'] ?? '');

    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $message = "❌ All fields are required.";
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

            $insert = $db->users->insertOne([
                'fullName'   => $fullName,
                'email'      => $email,
                'password'   => $hashedPassword,
                'role'       => $role,
                'address'    => $address,
                'zipCode'    => $zipCode,
                'createdAt'  => new MongoDB\BSON\UTCDateTime()
            ]);

            if ($insert->getInsertedCount() === 1) {
                $_SESSION['success_message'] = "✅ Account created successfully for $fullName!";
                header("Location: manageUsers.php");
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
    <title>Create Account - Admin</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="../js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4">
    <a class="navbar-brand" href="dashboard.php">⬅ Admin Dashboard</a>
    <div class="ms-auto text-white">
        <?= htmlspecialchars($_SESSION['user']['name']) ?>
        <a href="../php/logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
    </div>
</nav>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">👤 Create New Account</h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="firstName" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" name="lastName" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-control" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">ZIP/Postal Code</label>
                            <input type="text" name="zipCode" class="form-control" required>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">➕ Create Account</button>
                            <a href="manageUsers.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html> 