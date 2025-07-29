<?php
require_once 'mailConfig.php';

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
