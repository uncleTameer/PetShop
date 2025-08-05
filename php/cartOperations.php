<?php
require_once 'dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================================
// Cart Operations Handler
// ============================================================================

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        handleAddToCart();
        break;
    case 'remove':
        handleRemoveFromCart();
        break;
    case 'update':
        handleUpdateCart();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

// ============================================================================
// Add to Cart Function
// ============================================================================

function handleAddToCart() {
    global $db;
    
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'message' => 'Please login first']);
        exit;
    }

    $productName = $_POST['name'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 1);

    if (!$productName) {
        echo json_encode(['success' => false, 'message' => 'Product name required']);
        exit;
    }

    // Get product details
    $product = $db->products->findOne(['name' => $productName]);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    // Check stock
    if ($product['stock'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
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
    } else {
        // Add new item
        $_SESSION['cart'][$productId] = [
            'name' => $productName,
            'price' => $product['price'],
            'quantity' => $quantity,
            'image' => $product['image'] ?? 'default.png'
        ];
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Added to cart successfully',
        'cartCount' => count($_SESSION['cart'])
    ]);
}

// ============================================================================
// Remove from Cart Function
// ============================================================================

function handleRemoveFromCart() {
    $productId = $_GET['id'] ?? $_POST['id'] ?? '';

    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        exit;
    }

    // Remove item from cart
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        echo json_encode([
            'success' => true, 
            'message' => 'Item removed from cart',
            'cartCount' => count($_SESSION['cart'])
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
    }
}

// ============================================================================
// Update Cart Function
// ============================================================================

function handleUpdateCart() {
    $quantities = $_POST['quantities'] ?? [];
    
    if (empty($quantities)) {
        echo json_encode(['success' => false, 'message' => 'No quantities provided']);
        exit;
    }

    $updated = false;
    $newTotal = 0;
    
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

    // Calculate new total
    foreach ($_SESSION['cart'] as $item) {
        $newTotal += $item['price'] * $item['quantity'];
    }

    if ($updated) {
        echo json_encode([
            'success' => true, 
            'message' => 'Cart updated successfully',
            'newTotal' => number_format($newTotal, 2),
            'cartCount' => count($_SESSION['cart'])
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes made to cart']);
    }
}
?> 