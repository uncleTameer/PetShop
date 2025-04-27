<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['user']) || !$_SESSION['user']['isAdmin']) {
    header("Location: ../index.php");
    exit;
}

$users = $db->users->find();
$myEmail = $_SESSION['user']['email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users - Horse & Camel</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <script src="../js/bootstrap.bundle.min.js" defer></script>
  <style>
    /* Hover effect for table rows */
    tbody tr:hover {
      background-color: #f8f9fa;
      transition: background-color 0.3s ease;
    }

    /* Button animation */
    .btn {
      transition: all 0.3s ease;
    }
    .btn:hover {
      transform: scale(1.05);
    }

    /* Smooth fade-in for alerts */
    .alert {
      animation: fadeIn 0.8s ease forwards;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Section divider */
    .section-divider {
      height: 2px;
      background: linear-gradient(to right, #0d6efd, #6610f2);
      margin: 10px auto 30px;
      width: 80px;
      border-radius: 10px;
    }
  </style>
</head>
<body>

<?php if (isset($_SESSION['success_message'])): ?>
  <div class="alert alert-success text-center m-3"><?= $_SESSION['success_message'] ?></div>
  <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
  <div class="alert alert-danger text-center m-3"><?= $_SESSION['error_message'] ?></div>
  <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<nav class="navbar navbar-dark bg-dark px-4 mb-4">
  <a class="navbar-brand" href="dashboard.php">⬅ Admin Dashboard</a>
  <div class="ms-auto text-white">
    <?= htmlspecialchars($_SESSION['user']['name']) ?>
    <a href="../php/logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
  </div>
</nav>

<div class="container py-4">
  <h2 class="text-center">🧑‍💼 Manage Users</h2>
  <div class="section-divider"></div>

  <table class="table table-hover table-bordered text-center align-middle">
    <thead class="table-dark">
      <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $user): ?>
        <tr>
          <td><?= htmlspecialchars($user['fullName']) ?></td>
          <td><?= htmlspecialchars($user['email']) ?></td>
          <td>
            <?php if (isset($user['isAdmin']) && $user['isAdmin']): ?>
              <span class="badge bg-primary">Admin</span>
            <?php else: ?>
              <span class="badge bg-secondary">User</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($user['email'] !== $myEmail): ?>
              <div class="d-flex flex-column align-items-center gap-2">
                <?php if (isset($user['isAdmin']) && $user['isAdmin']): ?>
                  <a href="toggleAdmin.php?email=<?= urlencode($user['email']) ?>&make=0" class="btn btn-sm btn-warning">Demote</a>
                <?php else: ?>
                  <a href="toggleAdmin.php?email=<?= urlencode($user['email']) ?>&make=1" class="btn btn-sm btn-success">Promote</a>
                <?php endif; ?>

                <?php if (isset($user['suspended']) && $user['suspended']): ?>
                  <a href="suspendUser.php?email=<?= urlencode($user['email']) ?>&action=unsuspend" class="btn btn-sm btn-info">🔓 Unsuspend</a>
                <?php else: ?>
                  <a href="suspendUser.php?email=<?= urlencode($user['email']) ?>&action=suspend" class="btn btn-sm btn-secondary">⛔ Suspend</a>
                <?php endif; ?>

                <a href="deleteUser.php?id=<?= $user['_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this user?');">🗑️ Delete</a>
              </div>
            <?php else: ?>
              <em>(you)</em>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
// Auto-fade success and error alerts
document.addEventListener('DOMContentLoaded', function() {
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      alert.style.opacity = '0';
      alert.style.transform = 'translateY(-10px)';
      setTimeout(() => alert.remove(), 500);
    }, 3000);
  });
});
</script>

</body>
</html>
