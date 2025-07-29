<?php
// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function getMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 0;
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP
    $mail->SMTPAuth = true;
    $mail->Username = 'petshop.servicee@gmail.com'; // Shop email
    $mail->Password = 'bzboeuozobackgda';    // New Gmail App Password (no spaces)
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Sender
    $mail->setFrom('petshop.servicee@gmail.com', 'PetShop Service');

    return $mail;
}
