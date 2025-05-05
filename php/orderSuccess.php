<?php
require_once('dbConnect.php');
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['user']) || !isset($_SESSION['last_order_id'])) {
    header("Location: index.php");
    exit;
}

$orderId = new ObjectId($_SESSION['last_order_id']);
$order = $db->orders->findOne(['_id' => $orderId]);

if (!$order) {
    echo "Order not found.";
    exit;
}

unset($_SESSION['last_order_id']);

$userName = htmlspecialchars($_SESSION['user']['name'] ?? $_SESSION['user']['fullName']);
$orderTotal = number_format($order['total'], 2);
$orderDate = $order['createdAt']->toDateTime()->format('d/m/Y H:i');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Confirmation</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <script src="../js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4 mb-4">
  <a class="navbar-brand" href="index.php">🏠 Pet Shop</a>
  <div class="ms-auto text-white">
    <?= $userName ?>
    <a href="myOrders.php" class="btn btn-outline-light btn-sm ms-2">📦 My Orders</a>
    <a href="php/logout.php" class="btn btn-outline-light btn-sm ms-2">Logout</a>
    <a href="cart.php" class="btn btn-warning btn-sm ms-3">🛒 Cart</a>
  </div>
</nav>

<div class="container py-5 text-center">
  <h2 class="mb-4">✅ Thank you, <?= $userName ?>!</h2>
  <p class="lead">Your order was placed successfully.</p>
  <p class="text-success">✉️ A confirmation email has been sent to <strong><?= htmlspecialchars($_SESSION['user']['email']) ?></strong>.</p>

  <div class="card my-4 mx-auto shadow" style="max-width: 600px;">
    <div class="card-body">
      <h5 class="card-title">🧾 Order Summary</h5>
      <p><strong>Order ID:</strong> <?= substr((string)$order['_id'], -5) ?></p>
      <p><strong>Placed On:</strong> <?= $orderDate ?></p>
      <p><strong>Total:</strong> ₪<?= $orderTotal ?></p>
      <hr>
      <h6>Items:</h6>
      <ul class="list-group text-start">
        <?php foreach ($order['items'] as $item): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?>
            <span>₪<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <a href="../shop.php" class="btn btn-primary me-2">🛍 Keep Shopping</a>
  <a href="../myOrders.php" class="btn btn-outline-secondary">📦 View My Orders</a>
</div>

</body>
</html>
