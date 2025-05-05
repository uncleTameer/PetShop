<?php
session_start();
ob_start();

echo "<h1>📩 FORM SUBMITTED</h1>";
echo "<p><strong>POST Destination:</strong> sendContact-test.php</p>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<hr>";
echo "<p><strong>Now we wait...</strong></p>";
echo "<script>
    console.log('[DEBUG] JS script loaded in sendContact-test.php');
    setTimeout(() => console.log('✅ Still on sendContact-test.php after 3 seconds'), 3000);
</script>";

ob_end_flush();
exit;
