<?php
require 'php/dbConnect.php';
session_start();

$products = $db->products->find();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Shop - Pet Shop</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <script src="js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

  <div class="container py-4">
    <h2 class="text-center mb-4">All Products</h2>
    <div class="row">

      <?php foreach ($products as $product): ?>
        <div class="col-md-4 mb-4">
          <div class="card h-100">
            <img src="<?= $product['image'] ?>" class="card-img-top" alt="<?= $product['name'] ?>" style="height: 200px; object-fit: cover;">
            <div class="card-body">
              <h5 class="card-title"><?= $product['name'] ?></h5>
              <p class="card-text">₪<?= number_format($product['price'], 2) ?></p>
              <p class="card-text"><small class="text-muted">Stock: <?= $product['stock'] ?></small></p>
              <form method="POST" action="php/addToCart.php">
                <input type="hidden" name="productId" value="<?= $product['_id'] ?>">
                <input type="hidden" name="name" value="<?= $product['name'] ?>">
                <input type="hidden" name="price" value="<?= $product['price'] ?>">
                <button type="submit" class="btn btn-primary w-100">Add to Cart</button>
              </form>

            </div>
          </div>
        </div>
      <?php endforeach; ?>

    </div>
  </div>

</body>
</html>
