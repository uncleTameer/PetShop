<?php
require_once '../php/dbConnect.php';
session_start();

if (!isset($_SESSION['user']) || !$_SESSION['user']['isAdmin']) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['orderId'], $_POST['status'])) {
    $orderId = new MongoDB\BSON\ObjectId($_POST['orderId']);
    $newStatus = $_POST['status'];

    $db->orders->updateOne(
        ['_id' => $orderId],
        ['$set' => ['status' => $newStatus]]
    );
}

header("Location: manageOrders.php");
exit;
