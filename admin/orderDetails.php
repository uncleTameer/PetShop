<?php
require_once '../php/dbConnect.php';
session_start();

if (!isset($_SESSION['user']) || !$_SESSION['user']['isAdmin']) {
    header("Location: ../index.php");
    exit;
}

use MongoDB\BSON\ObjectId;

$orderId = $_GET['id'] ?? '';
if (!$orderId) {
    echo "❌ Invalid order ID.";
    exit;
}

$order = $db->orders->findOne(['_id' => new ObjectId($orderId)]);
if (!$order) {
    echo "❌ Order not found.";
    exit;
}

$user = isset($order['userId']) ? $db->users->findOne(['_id' => $order['userId']]) : null;
$createdAt = isset($order['createdAt']) ? $order['createdAt']->toDateTime()->format('d/m/Y H:i') : 'Unknown';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Details</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body>

<div class="container py-4">
  <h2 class="mb-4">🧾 Order Details</h2>

  <div class="mb-4">
    <p><strong>Order ID:</strong> <?= $orderId ?></p>
    <p><strong>Customer:</strong> <?= $user['fullName'] ?? 'Unknown' ?></p>
    <p><strong>Email:</strong> <?= $user['email'] ?? '-' ?></p>
    <p><strong>Status:</strong> <?= $order['status'] ?? 'Pending' ?></p>
    <p><strong>Placed On:</strong> <?= $createdAt ?></p>
    <p><strong>Total:</strong> ₪<?= number_format($order['total'], 2) ?></p>
  </div>

  <h5>📦 Items:</h5>
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Product</th>
        <th>Qty</th>
        <th>Price (₪)</th>
        <th>Subtotal (₪)</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($order['items'] ?? $order['cart'] ?? [] as $item): ?>
        <tr>
          <td><?= $item['name'] ?></td>
          <td><?= $item['quantity'] ?></td>
          <td><?= number_format($item['price'], 2) ?></td>
          <td><?= number_format($item['price'] * $item['quantity'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <a href="manageOrders.php" class="btn btn-secondary mt-3">⬅ Back to Orders</a>
</div>

</body>
</html>
