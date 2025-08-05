<?php
require_once('dbConnect.php');
require_once('emailSystem.php');

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

    // Convert cart to array format for database storage
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
    $orderDetailsHtml = "<h3>Thank you for your order, " . htmlspecialchars($_SESSION['user']['name']) . "!</h3>"
        . "<p><strong>Order ID:</strong> " . substr($orderId, -5) . "<br>"
        . "<strong>Total:</strong> ₪" . number_format($total, 2) . "</p>"
        . "<h4>Items:</h4><ul>";
    foreach ($cart as $item) {
        $orderDetailsHtml .= "<li>" . htmlspecialchars($item['name']) . " × " . $item['quantity'] . " (₪" . number_format($item['price'] * $item['quantity'], 2) . ")</li>";
    }
    $orderDetailsHtml .= "</ul>";
    
    $orderDetailsText = "Thank you for your order, " . $_SESSION['user']['name'] . "!\nOrder ID: " . substr($orderId, -5) . "\nTotal: ₪" . number_format($total, 2) . "\nItems:\n";
    foreach ($cart as $item) {
        $orderDetailsText .= $item['name'] . " × " . $item['quantity'] . " (₪" . number_format($item['price'] * $item['quantity'], 2) . ")\n";
    }
    
    $emailSent = sendOrderConfirmationMail(
        $_SESSION['user']['email'],
        $_SESSION['user']['name'],
        $orderDetailsHtml,
        $orderDetailsText
    );

    unset($_SESSION['cart']);
    $_SESSION['last_order_id'] = $result->getInsertedId();
    
    if ($emailSent === true) {
        $_SESSION['success_message'] = "Order placed successfully! Check your email for confirmation.";
    } else {
        $_SESSION['success_message'] = "Order placed successfully! (Email notification failed)";
    }
    
    header("Location: orderSuccess.php");
    exit;
}
?>
