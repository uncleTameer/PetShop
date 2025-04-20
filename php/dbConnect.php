<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Composer autoloader

try {
    // Connect to MongoDB
    $client = new MongoDB\Client("mongodb+srv://TandJ:HrsNdCml@cluster0.mjqwdkf.mongodb.net/?retryWrites=true&w=majority&ssl=true");


    // Select your database
    $db = $client->PetShopProject;

     "✅ Connected to MongoDB!";
} catch (Exception $e) {
     "❌ Connection failed: " . $e->getMessage();
}
