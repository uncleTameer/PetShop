<?php
require_once __DIR__ . '/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Allow guest users to add to cart (no login required)
// Guest cart will be stored in session

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = $_POST['name'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 1);
    $redirectUrl = $_POST['redirect'] ?? 'shop.php';

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
        $rawImage = $product['image'] ?? 'default.png';
        // Normalize path: remove leading ../ if present
        $normalizedImage = preg_replace('#^\.\./#', '', $rawImage);
        // Ensure it starts with uploads/ or fallback
        if (strpos($normalizedImage, 'uploads/') !== 0) {
            $normalizedImage = 'uploads/' . basename($normalizedImage);
        }
        $_SESSION['cart'][$productId] = [
            'name' => $productName,
            'price' => $product['price'],
            'quantity' => $quantity,
            'image' => $normalizedImage
        ];
        $_SESSION['success_message'] = "Added to cart successfully!";
    }

    header("Location: " . $redirectUrl);
    exit;
} else {
    header("Location: shop.php");
    exit;
}
?> 