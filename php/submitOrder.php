<?php
require_once('dbConnect.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    $_SESSION['error_message'] = "You must be logged in to place an order.";
    header("Location: ../php/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        $_SESSION['error_message'] = "❌ Your cart is empty.";
        header("Location: ../cart.php");
        exit;
    }

    $cart = array_values($_SESSION['cart']);
    $total = 0;

    foreach ($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    $order = [
        'userId' => new MongoDB\BSON\ObjectId($_SESSION['user']['id']),
        'items' => $cart,
        'total' => $total,
        'createdAt' => new MongoDB\BSON\UTCDateTime(),
        'status' => 'Pending'
    ];

    $result = $db->orders->insertOne($order);

    // Decrease stock
    foreach ($cart as $item) {
        $db->products->updateOne(
            ['name' => $item['name']],
            ['$inc' => ['stock' => -$item['quantity']]]
        );
    }

    unset($_SESSION['cart']);
    $_SESSION['last_order_id'] = $result->getInsertedId();
    header("Location: orderSuccess.php");
    exit;
}
?>
