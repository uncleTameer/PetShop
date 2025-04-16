<?php
require_once 'mailConfig.php';

try {
    $mail = getMailer();

    // Recipient
    $mail->addAddress('customer@example.com', 'Valued Customer');

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Thank You for Your Purchase!';
    $mail->Body    = 'Your order has been received and is being processed. 🐾';
    $mail->AltBody = 'Your order has been received and is being processed.';

    $mail->send();
    echo 'Email has been sent successfully';
} catch (Exception $e) {
    echo "Mailer Error: {$mail->ErrorInfo}";
}
