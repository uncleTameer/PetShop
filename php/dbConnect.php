<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;

try {
    // Correct database name to match Compass!
    $mongoClient = new Client("mongodb+srv://TandJ:HrsNdCml@cluster0.mjqwdkf.mongodb.net/PetShopProject?retryWrites=true&w=majority&tls=true");
    $db = $mongoClient->selectDatabase('PetShopProject'); // ✅ Fix here too

} catch (Exception $e) {
    die('Failed to connect to MongoDB: ' . $e->getMessage());
}
?>
