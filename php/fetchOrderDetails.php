<?php
require 'dbConnect.php';
use MongoDB\BSON\ObjectId;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
  $orderId = new ObjectId($_POST['id']);
  $order = $db->orders->findOne(['_id' => $orderId]);

  if ($order) {
    $user = $db->users->findOne(['_id' => $order['userId']]);
    echo "<strong>Customer:</strong> " . htmlspecialchars($user['fullName']) . "<br>";
    echo "<strong>Email:</strong> " . htmlspecialchars($user['email']) . "<br><hr>";

    echo "<ul class='list-group'>";
    foreach ($order['items'] as $item) {
      echo "<li class='list-group-item d-flex justify-content-between'>
              <span>" . htmlspecialchars($item['name']) . " x" . $item['quantity'] . "</span>
              <span>₪" . number_format($item['price'] * $item['quantity'], 2) . "</span>
            </li>";
    }
    echo "</ul><hr>";
    echo "<strong>Total:</strong> ₪" . number_format($order['total'], 2) . "<br>";
    echo "<strong>Status:</strong> " . htmlspecialchars($order['status'] ?? 'Pending');
  } else {
    echo "Order not found.";
  }
}
?>
