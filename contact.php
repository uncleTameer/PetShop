<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact Us - Horse & Camel</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <script src="js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-4">
  <a class="navbar-brand" href="index.php">🏠 Home</a>
  <div class="ms-auto text-white">
    <?php if (isset($_SESSION['user'])): ?>
      Hello, <?= htmlspecialchars($_SESSION['user']['name']) ?>
      <a href="php/logout.php" class="btn btn-outline-light btn-sm ms-3">Logout</a>
    <?php else: ?>
      <a href="php/login.php" class="btn btn-outline-light btn-sm me-2">Login</a>
      <a href="php/register.php" class="btn btn-outline-light btn-sm">Register</a>
    <?php endif; ?>
    <a href="cart.php" class="btn btn-outline-warning btn-sm ms-3">🛒 Cart</a>
  </div>
</nav>

<div class="container py-5">
  <h2 class="text-center mb-4">📬 Contact Us</h2>

  <div class="row justify-content-center">
    <div class="col-md-8">

      <div class="alert alert-info text-center">
        We usually reply within 24 hours!
      </div>

      <form action="php/sendContact.php" method="POST" class="border p-4 rounded shadow-sm bg-light">
        <div class="mb-3">
          <label for="name" class="form-label">Your Name</label>
          <input type="text" name="name" id="name" class="form-control" required value="<?= isset($_SESSION['user']['name']) ? htmlspecialchars($_SESSION['user']['name']) : '' ?>">
        </div>

        <div class="mb-3">
          <label for="email" class="form-label">Your Email</label>
          <input type="email" name="email" id="email" class="form-control" required value="<?= isset($_SESSION['user']['email']) ? htmlspecialchars($_SESSION['user']['email']) : '' ?>">
        </div>

        <div class="mb-3">
          <label for="message" class="form-label">Message</label>
          <textarea name="message" id="message" rows="5" class="form-control" required></textarea>
        </div>

        <button type="submit" class="btn btn-primary w-100">✉️ Send Message</button>
      </form>

    </div>
  </div>
</div>

</body>
</html>
