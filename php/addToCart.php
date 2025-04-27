<?php
require 'dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
use MongoDB\BSON\ObjectId;

$productId = $_POST['productId'] ?? null;

if (!$productId) {
    header("Location: ../shop.php");
    exit;
}

// Fetch product info from DB
$product = $db->products->findOne(['_id' => new ObjectId($productId)]);

if (!$product || $product['stock'] <= 0) {
    $_SESSION['error_message'] = "⚠️ This product is out of stock.";
    header("Location: ../shop.php");
    exit;
}

// Get current quantity in cart
$currentQtyInCart = $_SESSION['cart'][$productId]['quantity'] ?? 0;

// If adding another one would exceed stock — block it
if ($currentQtyInCart + 1 > $product['stock']) {
    $_SESSION['error_message'] = "⚠️ Only {$product['stock']} left in stock. You already have $currentQtyInCart in your cart.";
    header("Location: ../shop.php");
    exit;
}

// Add or update cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_SESSION['cart'][$productId])) {
    $_SESSION['cart'][$productId]['quantity'] += 1;
} else {
    $_SESSION['cart'][$productId] = [
        'name' => $product['name'],
        'price' => $product['price'],
        'quantity' => 1
    ];
}

$_SESSION['success_message'] = "{$product['name']} added to cart!";
header("Location: ../shop.php");
exit;
