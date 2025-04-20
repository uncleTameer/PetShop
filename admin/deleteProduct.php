<?php
require_once '../php/dbConnect.php';
session_start();

use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['user']) || !$_SESSION['user']['isAdmin']) {
    header("Location: ../index.php");
    exit;
}

if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "❌ No product ID provided.";
    header("Location: manageProducts.php");
    exit;
}

$id = $_GET['id'];

try {
    $product = $db->products->findOne(['_id' => new ObjectId($id)]);

    if (!$product) {
        $_SESSION['error_message'] = "❌ Product not found.";
        header("Location: manageProducts.php");
        exit;
    }

    // Optional: remove the image file from /uploads/
    if (isset($product['image'])) {
        $imagePath = '../' . $product['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    $db->products->deleteOne(['_id' => new ObjectId($id)]);

    $_SESSION['success_message'] = "🗑️ Product deleted successfully.";
    header("Location: manageProducts.php");
    exit;

} catch (Exception $e) {
    $_SESSION['error_message'] = "❌ Error: " . $e->getMessage();
    header("Location: manageProducts.php");
    exit;
}
