<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['user']) || !$_SESSION['user']['isAdmin']) {
    header("Location: ../index.php");
    exit;
}

// Fetch all orders
$orders = $db->orders->find([], ['sort' => ['createdAt' => -1]]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Orders</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
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
  <h2 class="text-center mb-4">📦 All Orders</h2>

  <table class="table table-bordered table-striped text-center align-middle">
    <thead class="table-dark">
      <tr>
        <th>Order ID</th>
        <th>Customer</th>
        <th>Email</th>
        <th>Total (₪)</th>
        <th>Items</th>
        <th>Status</th>
        <th>Placed On</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($orders as $order): ?>
  <?php
    $user = isset($order['userId']) ? $db->users->findOne(['_id' => $order['userId']]) : null;
    $date = isset($order['createdAt']) ? $order['createdAt']->toDateTime()->format('d/m/Y') : 'Unknown';
    $status = $order['status'] ?? 'Pending';

    $rowClass = '';
    if ($status === 'Shipped') $rowClass = 'table-success';
    elseif ($status === 'Cancelled') $rowClass = 'table-danger';
    elseif ($status === 'Pending') $rowClass = 'table-warning';
  ?>
  <tr class="<?= $rowClass ?>">
    <td>
      <a href="orderDetails.php?id=<?= $order['_id'] ?>" class="text-decoration-none">
        <?= substr((string)$order['_id'], -5) ?>
      </a>
    </td>
    <td><?= htmlspecialchars($user['fullName'] ?? 'Unknown') ?></td>
    <td><?= htmlspecialchars($user['email'] ?? '-') ?></td>
    <td>₪<?= number_format($order['total'] ?? 0, 2) ?></td>
    <td><?= isset($order['items']) ? count($order['items']) : 0 ?></td>
    <td>
      <form method="POST" action="updateOrderStatus.php" class="d-flex justify-content-center align-items-center">
        <input type="hidden" name="orderId" value="<?= $order['_id'] ?>">
        <select name="status" class="form-select form-select-sm me-2" onchange="this.form.submit()">
          <?php
            $statuses = ['Pending', 'Shipped', 'Cancelled'];
            foreach ($statuses as $option) {
              $selected = $status === $option ? 'selected' : '';
              echo "<option value=\"$option\" $selected>$option</option>";
            }
          ?>
        </select>
      </form>
    </td>
    <td><?= $date ?></td>
  </tr>
<?php endforeach; ?>

    </tbody>
  </table>
</div>

</body>
</html>
