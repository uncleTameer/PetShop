<?php
require_once 'dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    $_SESSION['error_message'] = "Please login first to add items to cart.";
    header("Location: ../shop.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = $_POST['name'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 1);
    $redirectUrl = $_POST['redirect'] ?? '../shop.php';

    if (!$productName) {
        $_SESSION['error_message'] = "Product name is required.";
        header("Location: " . $redirectUrl);
        exit;
    }

    // Get product details
    $product = $db->products->findOne(['name' => $productName]);
    
    if (!$product) {
        $_SESSION['error_message'] = "Product not found.";
        header("Location: " . $redirectUrl);
        exit;
    }

    // Check stock
    if ($product['stock'] < $quantity) {
        $_SESSION['error_message'] = "Not enough stock available.";
        header("Location: " . $redirectUrl);
        exit;
    }

    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Check if product already in cart
    $productId = (string)$product['_id'];
    
    if (isset($_SESSION['cart'][$productId])) {
        // Update existing item quantity
        $_SESSION['cart'][$productId]['quantity'] += $quantity;
        $_SESSION['success_message'] = "Updated quantity in cart!";
    } else {
        // Add new item
        $_SESSION['cart'][$productId] = [
            'name' => $productName,
            'price' => $product['price'],
            'quantity' => $quantity,
            'image' => $product['image'] ?? 'default.png'
        ];
        $_SESSION['success_message'] = "Added to cart successfully!";
    }

    header("Location: " . $redirectUrl);
    exit;
} else {
    header("Location: ../shop.php");
    exit;
}
?> 