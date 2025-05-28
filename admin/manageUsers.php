<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!in_array($_SESSION['user']['role'], ['admin', 'moderator'])) {
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
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <style>
    tbody tr:hover {
      background-color: #f8f9fa;
      transition: background-color 0.3s ease;
    }
    .btn {
      transition: all 0.3s ease;
    }
    .btn:hover {
      transform: scale(1.05);
    }
    .alert {
      animation: fadeIn 0.8s ease forwards;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
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
  <div class="alert alert-success text-center m-3"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
  <div class="alert alert-danger text-center m-3"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
<?php endif; ?>

<nav class="navbar navbar-dark bg-dark px-4 mb-4">
  <a class="navbar-brand" href="dashboard.php">⬅ Admin Dashboard</a>
  <div class="d-flex align-items-center ms-auto text-white">
    <?php
      $imgPath = '../uploads/' . ($_SESSION['user']['profilePicture'] ?? 'default.png');
      if (!file_exists($imgPath)) $imgPath = '../uploads/default.png';
    ?>
    <img src="<?= $imgPath ?>" alt="Profile" class="rounded-circle me-2" style="width: 35px; height: 35px; object-fit: cover;">
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
            <?php
              $role = $user['role'] ?? 'user';
              $badgeClass = $role === 'admin' ? 'primary' : ($role === 'moderator' ? 'warning' : 'secondary');
            ?>
            <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($role) ?></span>
            <?php if (!empty($user['suspended'])): ?>
              <span class="badge bg-danger ms-1">Suspended</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($user['email'] !== $myEmail): ?>
              <div class="d-flex flex-column align-items-center gap-2">
                <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                  <!-- Role Promotion/Demotion -->
                  <?php if ($role === 'admin'): ?>
                    <a href="updateRole.php?email=<?= urlencode($user['email']) ?>&role=user" class="btn btn-sm btn-warning">Demote</a>
                  <?php elseif ($role === 'moderator'): ?>
                    <a href="updateRole.php?email=<?= urlencode($user['email']) ?>&role=admin" class="btn btn-sm btn-success">Promote to Admin</a>
                    <a href="updateRole.php?email=<?= urlencode($user['email']) ?>&role=user" class="btn btn-sm btn-warning">Demote</a>
                  <?php else: ?>
                    <a href="updateRole.php?email=<?= urlencode($user['email']) ?>&role=moderator" class="btn btn-sm btn-info">Promote to Mod</a>
                    <a href="updateRole.php?email=<?= urlencode($user['email']) ?>&role=admin" class="btn btn-sm btn-success">Promote to Admin</a>
                  <?php endif; ?>
                <?php endif; ?>

                <!-- Suspend/Unsuspend -->
                <?php if (in_array($_SESSION['user']['role'], ['admin', 'moderator'])): ?>
                  <?php if (!empty($user['suspended'])): ?>
                    <a href="suspendUser.php?email=<?= urlencode($user['email']) ?>&action=unsuspend" class="btn btn-sm btn-info">🔓 Unsuspend</a>
                  <?php else: ?>
                    <a href="suspendUser.php?email=<?= urlencode($user['email']) ?>&action=suspend" class="btn btn-sm btn-secondary">⛔ Suspend</a>
                  <?php endif; ?>
                <?php endif; ?>

                <!-- Delete (Admins only) -->
                <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                  <a href="deleteUser.php?id=<?= $user['_id'] ?>" class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('Are you sure you want to delete this user?');">🗑️ Delete</a>
                <?php endif; ?>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
  $(document).ready(function () {
    $('table').DataTable({ pageLength: 10 });
    $('.alert').each(function () {
      setTimeout(() => $(this).fadeOut(500), 3000);
    });
  });
</script>

</body>
</html>
