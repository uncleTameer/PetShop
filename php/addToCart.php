<?php
session_start();

$productId = $_POST['productId'] ?? null;
$name = $_POST['name'] ?? '';
$price = $_POST['price'] ?? 0;

if (!$productId) {
    die("Invalid product ID");
}

// Initialize cart array if not already set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// If product already in cart, increase quantity
if (isset($_SESSION['cart'][$productId])) {
    $_SESSION['cart'][$productId]['quantity'] += 1;
} else {
    $_SESSION['cart'][$productId] = [
        'name' => $name,
        'price' => $price,
        'quantity' => 1
    ];
}

header("Location: ../shop.php");
exit;
