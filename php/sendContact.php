<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name && $email && $message) {
        $_SESSION['success_message'] = "✅ Thank you, $name! Your message was received.";
        header("Location: ../index.php");
        exit;
    } else {
        $_SESSION['error_message'] = "❌ Please fill all fields.";
        header("Location: ../contact.php");
        exit;
    }
} else {
    header("Location: ../contact.php");
    exit;
}
?>
