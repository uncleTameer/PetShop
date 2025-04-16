<?php
session_start();
$cart = $_SESSION['cart'] ?? [];
$total = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Cart</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <script src="js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<div class="container py-4">
  <h2 class="mb-4">🛒 Your Cart</h2>

  <?php if (empty($cart)): ?>
    <div class="alert alert-info">Your cart is empty.</div>
    <a href="shop.php" class="btn btn-primary">Back to Shop</a>
  <?php else: ?>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Product</th>
          <th>Unit Price</th>
          <th>Quantity</th>
          <th>Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cart as $product): ?>
          <?php $subtotal = $product['price'] * $product['quantity']; ?>
          <?php $total += $subtotal; ?>
          <tr>
            <td><?= htmlspecialchars($product['name']) ?></td>
            <td>₪<?= number_format($product['price'], 2) ?></td>
            <td><?= $product['quantity'] ?></td>
            <td>₪<?= number_format($subtotal, 2) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="3" class="text-end">Total</th>
          <th>₪<?= number_format($total, 2) ?></th>
        </tr>
      </tfoot>
    </table>
    <a href="shop.php" class="btn btn-secondary">Continue Shopping</a>
    <a href="php/submitOrder.php" class="btn btn-success">Proceed to Checkout</a>
  <?php endif; ?>
</div>

</body>
</html>
