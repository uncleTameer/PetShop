<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['quantities'])) {
    foreach ($_POST['quantities'] as $id => $qty) {
        if (isset($_SESSION['cart'][$id]) && $qty > 0) {
            $_SESSION['cart'][$id]['quantity'] = intval($qty);
        }
    }
}

header("Location: ../cart.php");
exit;
