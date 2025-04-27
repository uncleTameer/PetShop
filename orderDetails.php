<?php
require 'php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['user']) || !$_GET['id']) {
    header("Location: index.php");
    exit;
}

use MongoDB\BSON\ObjectId;

try {
    $orderId = new ObjectId($_GET['id']);
    $order = $db->orders->findOne(['_id' => $orderId]);

    if (!$order) {
        throw new Exception("Order not found.");
    }
} catch (Exception $e) {
    die("Invalid Order ID.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Details</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <script src="js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4">
  <a class="navbar-brand" href="myOrders.php">⬅ Back to My Orders</a>
  <div class="ms-auto text-white">
    <?= htmlspecialchars($_SESSION['user']['name']) ?>
    <a href="php/logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
  </div>
</nav>

<div class="container py-4">
  <h2 class="mb-4 text-center">📄 Order Details</h2>

  <div class="mb-3">
    <strong>Order ID:</strong> <?= $order['_id'] ?><br>
    <strong>Total:</strong> ₪<?= number_format($order['total'], 2) ?><br>
    <strong>Status:</strong> <?= $order['status'] ?? 'Pending' ?><br>
    <strong>Placed On:</strong> <?= isset($order['createdAt']) ? $order['createdAt']->toDateTime()->format('d/m/Y H:i') : 'Unknown' ?>
  </div>

  <h4>🧺 Items</h4>
  <table class="table table-bordered text-center">
    <thead class="table-dark">
      <tr>
        <th>Product</th>
        <th>Price (₪)</th>
        <th>Quantity</th>
        <th>Subtotal (₪)</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($order['items'] as $item): ?>
        <tr>
          <td><?= htmlspecialchars($item['name']) ?></td>
          <td><?= number_format($item['price'], 2) ?></td>
          <td><?= $item['quantity'] ?></td>
          <td><?= number_format($item['price'] * $item['quantity'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

</body>
</html>
