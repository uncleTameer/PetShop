<?php
// php/setDefaultCategory.php
require_once __DIR__ . '/dbConnect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use MongoDB\BSON\ObjectId;

try {
    // Value coming from the form ("" means clear)
    $raw = isset($_POST['categoryId']) ? trim($_POST['categoryId']) : '';

    // If user is not logged in, use SESSION only
    if (!isset($_SESSION['user'])) {
        if ($raw === '') {
            unset($_SESSION['preferences']['defaultCategoryId']);
            $_SESSION['success_message'] = 'העדפת קטגוריה אופסה בהצלחה.';
        } else {
            // Validate ObjectId
            try {
                $catId = new ObjectId($raw);
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'קטגוריה לא חוקית.';
                header('Location: shop.php');
                exit;
            }
            $_SESSION['preferences']['defaultCategoryId'] = (string)$catId;
            $_SESSION['success_message'] = 'העדפת קטגוריה נשמרה לאורח (למשך הסשן).';
        }
        header('Location: shop.php');
        exit;
    }

    // Logged-in user: update DB + keep session in sync
    $userId = new ObjectId($_SESSION['user']['id']);

    if ($raw === '') {
        // Clear preference
        $db->users->updateOne(
            ['_id' => $userId],
            ['$unset' => ['preferences.defaultCategoryId' => '']]
        );
        unset($_SESSION['preferences']['defaultCategoryId']);
        $_SESSION['success_message'] = 'העדפת קטגוריה אופסה בהצלחה.';
    } else {
        // Validate ObjectId and ensure category exists (optional but nice)
        try {
            $catId = new ObjectId($raw);
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'קטגוריה לא חוקית.';
            header('Location: shop.php');
            exit;
        }

        // Optional existence check:
        $exists = $db->categories->findOne(['_id' => $catId], ['projection' => ['_id' => 1]]);
        if (!$exists) {
            $_SESSION['error_message'] = 'קטגוריה לא נמצאה.';
            header('Location: shop.php');
            exit;
        }

        $db->users->updateOne(
            ['_id' => $userId],
            ['$set' => ['preferences.defaultCategoryId' => $catId]]
        );
        $_SESSION['preferences']['defaultCategoryId'] = (string)$catId;
        $_SESSION['success_message'] = 'העדפת קטגוריה נשמרה בהצלחה.';
    }

} catch (Throwable $t) {
    $_SESSION['error_message'] = 'שגיאה בשמירת ההעדפה: ' . $t->getMessage();
}

// Back to shop
header('Location: shop.php');
exit;
