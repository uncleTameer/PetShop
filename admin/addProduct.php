<?php
require_once '../php/dbConnect.php';
session_start();

if (!isset($_SESSION['user']) || !$_SESSION['user']['isAdmin']) {
    header("Location: ../index.php");
    exit;
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    $stock = intval($_POST['stock']);

    // Validate image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $destination = $uploadDir . $filename;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
            // Save to MongoDB
            $db->products->insertOne([
                'name' => $name,
                'price' => $price,
                'category' => $category,
                'stock' => $stock,
                'image' => 'uploads/' . $filename // relative path
            ]);

            $_SESSION['success_message'] = "✅ Product '$name' added successfully!";
            header("Location: manageProducts.php");
            exit;
        } else {
            $message = "❌ Failed to move uploaded file.";
        }
    } else {
        $message = "❌ Please upload a valid image.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Product</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <script src="../js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4">
  <a class="navbar-brand" href="dashboard.php">⬅ Admin Dashboard</a>
  <div class="ms-auto text-white">
    <?= htmlspecialchars($_SESSION['user']['name']) ?>
    <a href="../php/logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
  </div>
</nav>

<div class="container py-5" style="max-width: 600px;">
  <h2 class="text-center mb-4">➕ Add New Product</h2>

  <?php if ($message): ?>
    <div class="alert alert-info text-center"><?= $message ?></div>
  <?php endif; ?>

  <form method="POST" action="addProduct.php" enctype="multipart/form-data">
  <div class="mb-3">
    <label>Product Name</label>
    <input type="text" name="name" class="form-control" required>
  </div>

  <div class="mb-3">
    <label>Price (₪)</label>
    <input type="number" name="price" class="form-control" step="0.01" required>
  </div>

  <div class="mb-3">
    <label>Category</label>
    <input type="text" name="category" class="form-control" required>
  </div>

  <div class="mb-3">
    <label>Stock</label>
    <input type="number" name="stock" class="form-control" required>
  </div>

  <div class="mb-3">
    <label>Product Image</label>
    <input type="file" name="image" class="form-control" required>
  </div>

  <button type="submit" name="submit" class="btn btn-primary w-100">Add Product</button>
</form>
