<?php
require '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  $_SESSION['error_message'] = "⛔ Unauthorized access.";
  header("Location: manageOrders.php");
  exit;
}

function isValidObjectId($id) {
  return preg_match('/^[a-f\d]{24}$/i', $id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $orderId = $_POST['orderId'] ?? '';
  $status = $_POST['status'] ?? '';

  if (!isValidObjectId($orderId) || !in_array($status, ['Pending', 'Shipped', 'Cancelled', 'Delivered'])) {
    $_SESSION['error_message'] = "❌ Invalid request data.";
    header("Location: manageOrders.php");
    exit;
  }

  $updateResult = $db->orders->updateOne(
    ['_id' => new ObjectId($orderId)],
    ['$set' => ['status' => $status]]
  );

  if ($updateResult->getModifiedCount() > 0) {
    $_SESSION['success_message'] = "✅ Order status updated to \"$status\".";
  } else {
    $_SESSION['error_message'] = "⚠️ No changes made or invalid order ID.";
  }

  header("Location: manageOrders.php");
  exit;
}
?>
