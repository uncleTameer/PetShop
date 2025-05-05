
<?php
// ======= register.php =======

require_once __DIR__ . '/php/dbConnect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!isset($_POST['terms'])) {
        $message = "\u274C You must accept the Terms and Conditions.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "\u274C Invalid email format.";
    } elseif (strlen($password) < 6) {
        $message = "\u274C Password must be at least 6 characters.";
    } else {
        $collection = (new MongoDB\Client)->PetShopProject->users;
        $existingUser = $collection->findOne(['email' => $email]);

        if ($existingUser) {
            $message = "\u274C Email already exists.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $collection->insertOne([
                'fullName' => $fullName,
                'email' => $email,
                'password' => $hashedPassword,
                'authProvider' => 'local',
                'createdAt' => new MongoDB\BSON\UTCDateTime()
            ]);

            $message = "\u2705 Registration successful! You can now login.";
        }
    }
}

?>
