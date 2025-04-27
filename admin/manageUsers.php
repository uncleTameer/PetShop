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
  <title>Manage Users</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <script src="../js/bootstrap.bundle.min.js" defer></script>
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


<nav class="navbar navbar-dark bg-dark px-4">
  <a class="navbar-brand" href="dashboard.php">⬅ Admin Dashboard</a>
  <div class="ms-auto text-white">
    <?= htmlspecialchars($_SESSION['user']['name']) ?>
    <a href="../php/logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
  </div>
</nav>

<div class="container py-4">
  <h2 class="mb-4 text-center">🧑‍💼 Manage Users</h2>

  <table class="table table-bordered table-striped text-center">
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
          <td><?= (isset($user['isAdmin']) && $user['isAdmin']) ? 'Admin' : 'User' ?></td>
          <td>
            <?php if ($user['email'] !== $myEmail): ?>
              <?php if (isset($user['isAdmin']) && $user['isAdmin']): ?>
                <a href="toggleAdmin.php?email=<?= urlencode($user['email']) ?>&make=0" class="btn btn-sm btn-danger">Demote</a>
              <?php else: ?>
                <a href="toggleAdmin.php?email=<?= urlencode($user['email']) ?>&make=1" class="btn btn-sm btn-success">Promote</a>
              <?php endif; ?>
            <?php else: ?>
              <em>(you)</em>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

</body>
</html>
