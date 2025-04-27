<?php
require_once 'php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user'])) {
    header("Location: php/login.php");
    exit;
}

use MongoDB\BSON\ObjectId;

$userId = new ObjectId($_SESSION['user']['id']);
$orders = $db->orders->find(['userId' => $userId], ['sort' => ['createdAt' => -1]]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Orders</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4 mb-4">
  <a class="navbar-brand" href="index.php">⬅ Back to Homepage</a>
  <div class="ms-auto text-white">
    <?= htmlspecialchars($_SESSION['user']['name']) ?>
    <a href="php/logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
  </div>
</nav>

<div class="container py-4">
  <h2 class="text-center mb-4">📦 My Orders</h2>

  <?php if (!$orders->isDead()): ?>
    <div class="table-responsive">
      <table class="table table-bordered text-center align-middle">
        <thead class="table-dark">
          <tr>
            <th>Order ID</th>
            <th>Total (₪)</th>
            <th>Status</th>
            <th>Items</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order): ?>
            <?php
              $date = isset($order['createdAt']) ? $order['createdAt']->toDateTime()->format('d/m/Y H:i') : 'Unknown';
              $status = $order['status'] ?? 'Pending';
              $idShort = substr((string)$order['_id'], -5);
              $rowClass = '';

              if ($status === 'Cancelled') $rowClass = 'table-danger';
              elseif ($status === 'Shipped') $rowClass = 'table-success';
              elseif ($status === 'Pending') $rowClass = 'table-warning';
            ?>
            <tr class="<?= $rowClass ?>">
              <td>
                <a href="orderDetails.php?id=<?= $order['_id'] ?>" class="text-decoration-none">
                  #<?= $idShort ?>
                </a>
              </td>
              <td>₪<?= number_format($order['total'], 2) ?></td>
              <td>
                <span class="badge bg-<?= 
                  $status === 'Cancelled' ? 'danger' :
                  ($status === 'Shipped' ? 'success' : 'warning') ?>">
                  <?= $status ?>
                </span>

                <?php if ($status === 'Pending'): ?>
                  <a href="php/cancelOrder.php?id=<?= $order['_id'] ?>" 
                     class="btn btn-sm btn-outline-danger ms-2"
                     title="Click to cancel this order"
                     onclick="return confirm('Are you sure you want to cancel this order?');">
                    ❌ Cancel
                  </a>
                <?php elseif ($status === 'Cancelled' && isset($order['updatedAt'])): ?>
                  <small class="d-block text-muted mt-1">
                    🕓 Cancelled on <?= $order['updatedAt']->toDateTime()->format('d/m/Y H:i') ?>
                  </small>
                <?php endif; ?>
              </td>
              <td><?= isset($order['items']) ? count($order['items']) : 0 ?></td>
              <td><?= $date ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="alert alert-info text-center">You haven't placed any orders yet.</div>
  <?php endif; ?>
</div>

</body>
</html>
