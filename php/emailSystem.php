<?php
// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ============================================================================
// PHPMailer Configuration
// ============================================================================

function getMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 0;
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'petshop.servicee@gmail.com';
    $mail->Password = 'bzboeuozobackgda';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Sender
    $mail->setFrom('petshop.servicee@gmail.com', 'PetShop Service');

    return $mail;
}

// ============================================================================
// Email Sending Functions
// ============================================================================

function sendContactMail($name, $email, $message) {
    try {
        $mail = getMailer();
        // Always send from the shop email
        $mail->setFrom('petshop.servicee@gmail.com', 'PetShop Service');
        // Recipient is the shop email
        $mail->addAddress('petshop.servicee@gmail.com', 'PetShop Service');
        // Set Reply-To as the user's email
        $mail->addReplyTo($email, $name);
        $mail->isHTML(true);
        $mail->Subject = 'New Contact Form Message';
        $mail->Body    = "<b>Name:</b> $name<br><b>Email:</b> $email<br><b>Message:</b><br>" . nl2br(htmlspecialchars($message));
        $mail->AltBody = "Name: $name\nEmail: $email\nMessage:\n$message";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo;
    }
}

function sendOrderConfirmationMail($toEmail, $toName, $orderDetailsHtml, $orderDetailsText) {
    try {
        $mail = getMailer();
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = 'Your PetShop Order Confirmation';
        $mail->Body    = $orderDetailsHtml;
        $mail->AltBody = $orderDetailsText;
        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo;
    }
}

// ============================================================================
// Contact Form Handler
// ============================================================================

function handleContactForm() {
    session_start();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name && $email && $message) {
            $mailResult = sendContactMail($name, $email, $message);
            if ($mailResult === true) {
                $_SESSION['success_message'] = "✅ Thank you, $name! Your message was received.";
            } else {
                $_SESSION['error_message'] = "❌ Failed to send message: $mailResult";
            }
            header("Location: index.php");
            exit;
        } else {
            $_SESSION['error_message'] = "❌ Please fill all fields.";
            header("Location: contact.php");
            exit;
        }
    } else {
        header("Location: contact.php");
        exit;
    }
}

// Auto-handle contact form if this file is called directly
if (basename($_SERVER['PHP_SELF']) === 'emailSystem.php' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    handleContactForm();
}
?> 