<?php
require_once 'mailConfig.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendOrderConfirmationEmail($userEmail, $userName, $orderId, $orderDetails, $total) {
    try {
        $mail = getMailer();
        
        // Recipients
        $mail->addAddress($userEmail, $userName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Order Confirmation - Horse & Camel Shop';
        
        // Create HTML email body
        $htmlBody = createOrderEmailHTML($userName, $orderId, $orderDetails, $total);
        $mail->Body = $htmlBody;
        
        // Plain text version
        $mail->AltBody = createOrderEmailText($userName, $orderId, $orderDetails, $total);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

function createOrderEmailHTML($userName, $orderId, $orderDetails, $total) {
    $itemsHtml = '';
    foreach ($orderDetails as $item) {
        $itemsHtml .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['name']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$item['quantity']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>₪" . number_format($item['price'], 2) . "</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>₪" . number_format($item['price'] * $item['quantity'], 2) . "</td>
            </tr>";
    }
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Order Confirmation</title>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto;'>
        <div style='background-color: #f8f9fa; padding: 20px; text-align: center;'>
            <h1 style='color: #28a745; margin: 0;'>🐴🐫 Horse & Camel Shop</h1>
            <p style='margin: 10px 0 0 0; color: #666;'>Thank you for your order!</p>
        </div>
        
        <div style='padding: 20px; background-color: white;'>
            <h2 style='color: #333;'>Order Confirmation</h2>
            <p>Dear <strong>{$userName}</strong>,</p>
            <p>Thank you for your order! We're excited to fulfill your request.</p>
            
            <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <h3 style='margin: 0 0 10px 0;'>Order Details</h3>
                <p><strong>Order ID:</strong> {$orderId}</p>
                <p><strong>Order Date:</strong> " . date('F j, Y \a\t g:i A') . "</p>
            </div>
            
            <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                <thead>
                    <tr style='background-color: #f8f9fa;'>
                        <th style='padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6;'>Product</th>
                        <th style='padding: 10px; text-align: center; border-bottom: 2px solid #dee2e6;'>Quantity</th>
                        <th style='padding: 10px; text-align: right; border-bottom: 2px solid #dee2e6;'>Price</th>
                        <th style='padding: 10px; text-align: right; border-bottom: 2px solid #dee2e6;'>Total</th>
                    </tr>
                </thead>
                <tbody>
                    {$itemsHtml}
                </tbody>
                <tfoot>
                    <tr style='background-color: #f8f9fa; font-weight: bold;'>
                        <td colspan='3' style='padding: 15px; text-align: right; border-top: 2px solid #dee2e6;'>Total:</td>
                        <td style='padding: 15px; text-align: right; border-top: 2px solid #dee2e6;'>₪" . number_format($total, 2) . "</td>
                    </tr>
                </tfoot>
            </table>
            
            <div style='background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 20px 0;'>
                <h4 style='margin: 0 0 10px 0; color: #155724;'>What's Next?</h4>
                <ul style='margin: 0; padding-left: 20px; color: #155724;'>
                    <li>We'll process your order within 24 hours</li>
                    <li>You'll receive a shipping confirmation email</li>
                    <li>Track your order status in your account</li>
                </ul>
            </div>
            
            <p>If you have any questions about your order, please don't hesitate to contact us.</p>
            
            <p>Best regards,<br>
            <strong>The Horse & Camel Shop Team</strong></p>
        </div>
        
        <div style='background-color: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px;'>
            <p>This email was sent to confirm your order. Please do not reply to this email.</p>
            <p>© 2024 Horse & Camel Shop. All rights reserved.</p>
        </div>
    </body>
    </html>";
}

function createOrderEmailText($userName, $orderId, $orderDetails, $total) {
    $text = "Order Confirmation - Horse & Camel Shop\n\n";
    $text .= "Dear {$userName},\n\n";
    $text .= "Thank you for your order! We're excited to fulfill your request.\n\n";
    $text .= "Order Details:\n";
    $text .= "Order ID: {$orderId}\n";
    $text .= "Order Date: " . date('F j, Y \a\t g:i A') . "\n\n";
    $text .= "Items:\n";
    
    foreach ($orderDetails as $item) {
        $text .= "- {$item['name']} (Qty: {$item['quantity']}) - ₪" . number_format($item['price'] * $item['quantity'], 2) . "\n";
    }
    
    $text .= "\nTotal: ₪" . number_format($total, 2) . "\n\n";
    $text .= "What's Next?\n";
    $text .= "- We'll process your order within 24 hours\n";
    $text .= "- You'll receive a shipping confirmation email\n";
    $text .= "- Track your order status in your account\n\n";
    $text .= "If you have any questions about your order, please don't hesitate to contact us.\n\n";
    $text .= "Best regards,\nThe Horse & Camel Shop Team";
    
    return $text;
}

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
?>
