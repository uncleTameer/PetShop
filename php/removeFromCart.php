<?php
require_once __DIR__ . '/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['productId'] ?? '';
    
    if (!$productId) {
        $_SESSION['error_message'] = "Product ID is required.";
        header("Location: ../cart.php");
        exit;
    }

    // Remove item from cart
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        $_SESSION['success_message'] = "Item removed from cart!";
    } else {
        $_SESSION['error_message'] = "Item not found in cart.";
    }

    header("Location: ../cart.php");
    exit;
} else {
    header("Location: ../cart.php");
    exit;
}
?> 