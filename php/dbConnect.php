<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Composer autoloader

try {
    // Connect to MongoDB
    $client = new MongoDB\Client("mongodb://localhost:27017");

    // Select your database
    $db = $client->PetShopProject;

    echo "✅ Connected to MongoDB!";
} catch (Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
