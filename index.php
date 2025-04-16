<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Pet Shop</title>

  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/style.css">
  <script src="js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

  <?php session_start(); ?>

  <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
    <a class="navbar-brand" href="#">Pet Shop</a>
    <div class="ms-auto">
      <?php if (isset($_SESSION['user'])): ?>
        <span class="navbar-text text-white me-3">
          Hello, <?= htmlspecialchars($_SESSION['user']['name']) ?>
        </span>
        <a href="php/logout.php" class="btn btn-outline-light">Logout</a>
      <?php else: ?>
        <a href="php/register.php" class="btn btn-outline-light me-2">Register</a>
        <a href="php/login.php" class="btn btn-outline-light">Login</a>
        <a href="cart.php" class="btn btn-outline-warning ms-2">🛒 Cart</a>
      <?php endif; ?>
    </div>
  </nav>

  <div class="container mt-5 text-center">
    <h1 class="display-4">Welcome to the Pet Shop!</h1>
    <p class="lead">Find the best products for your pets 🐶🐱🐦</p>
  </div>

</body>
</html>
