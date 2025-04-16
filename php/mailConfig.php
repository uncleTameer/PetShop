<?php
// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function getMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP
    $mail->SMTPAuth = true;
    $mail->Username = 'your.email@gmail.com'; // Your email
    $mail->Password = 'CamelAndHorse';    // App-specific password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    // Sender
    $mail->setFrom('your.email@gmail.com', 'Pet Shop');

    return $mail;
}
