<?php
require_once('dbConnect.php');
require_once('sendMail.php');

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
    $orderId = (string)$result->getInsertedId();

    // Decrease stock
    foreach ($cart as $item) {
        $db->products->updateOne(
            ['name' => $item['name']],
            ['$inc' => ['stock' => -$item['quantity']]]
        );
    }

    // Send order confirmation email
    $emailSent = sendOrderConfirmationEmail(
        $_SESSION['user']['email'],
        $_SESSION['user']['name'],
        $orderId,
        $cart,
        $total
    );

    unset($_SESSION['cart']);
    $_SESSION['last_order_id'] = $result->getInsertedId();
    
    if ($emailSent) {
        $_SESSION['success_message'] = "Order placed successfully! Check your email for confirmation.";
    } else {
        $_SESSION['success_message'] = "Order placed successfully! (Email notification failed)";
    }
    
    header("Location: orderSuccess.php");
    exit;
}
?>
