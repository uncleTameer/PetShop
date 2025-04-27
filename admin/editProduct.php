<?php
require_once '../php/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['user']) || !$_SESSION['user']['isAdmin']) {
    header("Location: ../index.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: manageProducts.php");
    exit;
}

$id = $_GET['id'];
$product = $db->products->findOne(['_id' => new ObjectId($id)]);
$message = '';

if (!$product) {
    $message = "❌ Product not found.";
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    $stock = intval($_POST['stock']);

    $updateData = [
        'name' => $name,
        'price' => $price,
        'category' => $category,
        'stock' => $stock,
    ];

    // Handle optional new image
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $destination = $uploadDir . $filename;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
            $updateData['image'] = 'uploads/' . $filename;
        } else {
            $message = "❌ Failed to upload new image.";
        }
    }

    // Update in Mongo
    $db->products->updateOne(
        ['_id' => new ObjectId($id)],
        ['$set' => $updateData]
    );

    $_SESSION['success_message'] = "✅ Product updated successfully!";
    header("Location: manageProducts.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Product</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <script src="../js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4">
  <a class="navbar-brand" href="manageProducts.php">⬅ Back to Products</a>
  <div class="ms-auto text-white">
    <?= htmlspecialchars($_SESSION['user']['name']) ?>
    <a href="../php/logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
  </div>
</nav>

<div class="container py-5" style="max-width: 600px;">
  <h2 class="text-center mb-4">✏️ Edit Product</h2>

  <?php if ($message): ?>
    <div class="alert alert-warning text-center"><?= $message ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
      <label>Product Name</label>
      <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
    </div>

    <div class="mb-3">
      <label>Price (₪)</label>
      <input type="number" name="price" class="form-control" step="0.01" value="<?= $product['price'] ?>" required>
    </div>

    <div class="mb-3">
      <label>Category</label>
      <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($product['category']) ?>" required>
    </div>

    <div class="mb-3">
      <label>Stock</label>
      <input type="number" name="stock" class="form-control" value="<?= $product['stock'] ?>" required>
    </div>

    <div class="mb-3">
      <label>Current Image</label><br>
      <img src="../<?= $product['image'] ?>" style="height: 80px;"><br><br>
      <input type="file" name="image" class="form-control">
      <small class="text-muted">Leave blank to keep existing image</small>
    </div>

    <button type="submit" class="btn btn-primary w-100">Save Changes</button>
  </form>
</div>

</body>
</html>

