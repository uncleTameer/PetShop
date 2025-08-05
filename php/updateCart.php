<?php
require_once 'dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantities = $_POST['quantities'] ?? [];
    
    if (empty($quantities)) {
        $_SESSION['error_message'] = "No quantities provided.";
        header("Location: ../cart.php");
        exit;
    }

    $updated = false;
    
    // Update quantities
    foreach ($quantities as $productId => $quantity) {
        $quantity = (int)$quantity;
        
        if ($quantity <= 0) {
            // Remove item if quantity is 0 or negative
            if (isset($_SESSION['cart'][$productId])) {
                unset($_SESSION['cart'][$productId]);
                $updated = true;
            }
        } else {
            // Update quantity
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]['quantity'] = $quantity;
                $updated = true;
            }
        }
    }

    if ($updated) {
        $_SESSION['success_message'] = "Cart updated successfully!";
    } else {
        $_SESSION['error_message'] = "No changes made to cart.";
    }

    header("Location: ../cart.php");
    exit;
} else {
    header("Location: ../cart.php");
    exit;
}
?> 