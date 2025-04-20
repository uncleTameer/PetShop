<?php
require 'dbConnect.php';
session_start();
use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['user']) || !isset($_GET['id'])) {
    header("Location: ../myOrders.php");
    exit;
}

$orderId = new ObjectId($_GET['id']);

// Find the order
$order = $db->orders->findOne([
    '_id' => $orderId,
    'userId' => new ObjectId($_SESSION['user']['id'])
]);

// Validate ownership + status
if (!$order || ($order['status'] ?? '') !== 'Pending') {
    $_SESSION['error_message'] = "❌ This order cannot be cancelled.";
    header("Location: ../myOrders.php");
    exit;
}

// 1. Restore stock
foreach ($order['items'] as $item) {
    $db->products->updateOne(
        ['name' => $item['name']],
        ['$inc' => ['stock' => $item['quantity']]]
    );
}

// 2. Mark as cancelled + save timestamp
$db->orders->updateOne(
    ['_id' => $orderId],
    [
        '$set' => [
            'status' => 'Cancelled',
            'updatedAt' => new MongoDB\BSON\UTCDateTime()
        ]
    ]
);

$_SESSION['success_message'] = "✅ Order cancelled successfully.";
header("Location: ../myOrders.php");
exit;
