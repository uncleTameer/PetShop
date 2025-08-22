<?php
require_once '../php/dbConnect.php';

use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: dashboard.php");
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
    $categoryId = $_POST['categoryId'];
    $stock = intval($_POST['stock']);
    $lowStockThreshold = intval($_POST['lowStockThreshold'] ?? 5);

    $updateData = [
        'name' => $name,
        'price' => $price,
        'categoryId' => new MongoDB\BSON\ObjectId($categoryId),
        'stock' => $stock,
        'lowStockThreshold' => $lowStockThreshold,
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
  <link rel="stylesheet" href="../css/western-theme.css">
  <script src="../js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4">
  <a class="navbar-brand" href="manageProducts.php">⬅ Back to Products</a>
  <div class="d-flex align-items-center text-white me-2">
        <?php if (!empty($_SESSION['user']['profilePicture'])): ?>
          <img src="uploads/<?= htmlspecialchars($_SESSION['user']['profilePicture']) ?>" 
               alt="Profile" class="rounded-circle me-2" 
               style="width: 35px; height: 35px; object-fit: cover;">
        <?php else: ?>
          <img src="uploads/default.png" 
               alt="Default" class="rounded-circle me-2" 
               style="width: 35px; height: 35px; object-fit: cover;">
        <?php endif; ?>
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
      <select name="categoryId" class="form-control" required>
        <option value="">Select a category...</option>
                 <?php 
         $categories = $db->categories->find([], ['sort' => ['name' => 1]]);
         foreach ($categories as $category): 
         ?>
          <option value="<?= $category['_id'] ?>" 
                  <?= (isset($product['categoryId']) && $product['categoryId'] == $category['_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($category['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <small class="form-text text-muted">
        <a href="manageCategories.php" target="_blank">➕ Create new category</a>
      </small>
    </div>

    <div class="mb-3">
      <label>Stock</label>
      <input type="number" name="stock" class="form-control" value="<?= $product['stock'] ?>" required>
    </div>

    <div class="mb-3">
      <label>Low Stock Threshold</label>
      <input type="number" name="lowStockThreshold" class="form-control" 
             value="<?= $product['lowStockThreshold'] ?? 5 ?>" min="1" max="100" required>
      <small class="form-text text-muted">Alert when stock falls below this number</small>
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

