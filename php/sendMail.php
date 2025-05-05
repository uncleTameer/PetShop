<?php
require_once('dbConnect.php');
require_once('sendMail.php'); // 🔥 Load PHPMailer logic

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

    // Send email confirmation 🔥
    $to = $_SESSION['user']['email'];
    $userName = htmlspecialchars($_SESSION['user']['name'] ?? $_SESSION['user']['fullName']);
    $subject = "🐪 Horse & Camel - Order Confirmation";
    $body = "
        <h2>Thank you for your order, {$userName}!</h2>
        <p>We’ve received your order and are preparing it for shipping.</p>
        <p><strong>Total:</strong> ₪" . number_format($total, 2) . "</p>
        <p>We’ll notify you as soon as your order is shipped.</p>
        <hr>
        <small>This message was sent from Horse & Camel 🐴🐫</small>
    ";

    sendEmail($to, $subject, $body);

    unset($_SESSION['cart']);
    $_SESSION['last_order_id'] = $result->getInsertedId();

    header("Location: orderSuccess.php");
    exit;
}
?>
