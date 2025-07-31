<?php
require_once __DIR__ . '/../vendor/autoload.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $client = new MongoDB\Client("mongodb+srv://TandJ:HrsNdCml@cluster0.mjqwdkf.mongodb.net/");
    $db = $client->PetShopProject;
} catch (Exception $e) {
    echo "<div style='padding: 20px; background-color: #ffdddd; border: 1px solid red; color: darkred; font-family: sans-serif;'>
            <strong>⚠️ Failed to connect to the database. Please try again later.</strong>
          </div>";
    exit;
}
?>
