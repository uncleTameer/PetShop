<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use MongoDB\BSON\ObjectId;

// Check admin permission
if (!in_array($_SESSION['user']['role'], ['admin', 'moderator'])) {
    header("Location: ../index.php");
    exit;
}
// Accept ID from GET or POST (either)
$id = $_GET['id'] ?? $_POST['id'] ?? null;

// Decide where to redirect after deletion
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? 'manageProducts.php';

if (!$id) {
    $_SESSION['error_message'] = "❌ No product ID provided.";
    header("Location: $redirect");
    exit;
}

try {
    $product = $db->products->findOne(['_id' => new ObjectId($id)]);

    if (!$product) {
        $_SESSION['error_message'] = "❌ Product not found.";
        header("Location: $redirect");
        exit;
    }

    // Delete image if exists
    if (isset($product['image'])) {
        $imagePath = '../' . $product['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    $db->products->deleteOne(['_id' => new ObjectId($id)]);

    $_SESSION['success_message'] = "🗑️ Product deleted successfully.";
    header("Location: $redirect");
    exit;

} catch (Exception $e) {
    $_SESSION['error_message'] = "❌ Error: " . $e->getMessage();
    header("Location: $redirect");
    exit;
}
?>
