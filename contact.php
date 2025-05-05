<?php
session_start();
require_once 'php/dbConnect.php';
require_once 'php/mailConfig.php'; // for getMailer()

// Contact form processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sendContact'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name && $email && $message) {
        // Construct email
        $subject = "📬 New Contact Form Message from $name";
        $body = "
            <h3>New Contact Message</h3>
            <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
            <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
            <p><strong>Message:</strong></p>
            <p>" . nl2br(htmlspecialchars($message)) . "</p>
            <hr>
            <small>This message was sent from Horse & Camel website.</small>
        ";

        // Send Email
        try {
            $mail = getMailer();
            $mail->addAddress('petshop.servicee@gmail.com');
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $body;
            $mail->send();
            $sent = true;
        } catch (Exception $e) {
            $sent = false;
            // Log the error for debugging
            error_log('Mailer Error: ' . $mail->ErrorInfo);
            // Optionally, show the error on the page (for development only)
            $_SESSION['error_message'] = "❌ Mailer Error: " . $mail->ErrorInfo;
        }

        // Store in MongoDB
        $db->contactMessages->insertOne([
            'name' => $name,
            'email' => $email,
            'message' => $message,
            'status' => 'new',
            'createdAt' => new MongoDB\BSON\UTCDateTime()
        ]);

        if ($sent) {
            $_SESSION['success_message'] = "✅ Thank you, $name! Your message was sent successfully.";
        } else {
            $_SESSION['error_message'] = "❌ Failed to send message. Please try again later.";
        }
    } else {
        $_SESSION['error_message'] = "❌ Please fill all fields.";
    }

    header("Location: index.php");
    exit;
}
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
  <h2 class="text-center mb-4">�� Contact Us</h2>

  <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger text-center"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
  <?php endif; ?>

  <div class="row justify-content-center">
    <div class="col-md-8">

      <div class="alert alert-info text-center">
        We usually reply within 24 hours!
      </div>

      <form action="contact.php" method="POST" class="border p-4 rounded shadow-sm bg-light">
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

        <button type="submit" name="sendContact" class="btn btn-primary w-100">✉️ Send Message</button>
      </form>

    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const forms = document.querySelectorAll('form');
  forms.forEach(form => {
    if (!form.action.includes('contact.php')) {
      form.addEventListener('submit', e => {
        console.warn('[DEBUG] Blocked unrelated form submission.');
        e.preventDefault();
      });
    }
  });
});
</script>

</body>
</html>
