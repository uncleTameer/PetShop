<?php
require __DIR__ . '/../vendor/autoload.php';

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->PetShop; // Change if your DB name is different
} catch (Exception $e) {
    die("Error connecting to MongoDB: " . $e->getMessage());
}
?>
