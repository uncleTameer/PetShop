	<?php
	ob_start();
	session_start();
	require_once 'dbConnect.php';       // MongoDB
	require_once 'sendMail.php';       // PHPMailer

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sendContact'])) {
		$name = trim($_POST['name'] ?? '');
		$email = trim($_POST['email'] ?? '');
		$message = trim($_POST['message'] ?? '');

		if ($name && $email && $message) {
			// Construct email
			$subject = "📬 New Contact Form Message from $name";
			$body = "
				<h3>New Contact Message</h3>
				<p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
				<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
				<p><strong>Message:</strong></p>
				<p>" . nl2br(htmlspecialchars($message)) . "</p>
				<hr>
				<small>This message was sent from Horse & Camel website.</small>
			";

			// Send Email
			$to = "petshop.servicee@gmail.com";
			$sent = sendEmail($to, $subject, $body);

			// Store in MongoDB
			$db->contactMessages->insertOne([
				'name' => $name,
				'email' => $email,
				'message' => $message,
				'status' => 'new',
				'createdAt' => new MongoDB\BSON\UTCDateTime()
			]);

			if ($sent) {
				$_SESSION['success_message'] = "✅ Thank you, $name! Your message was sent successfully.";
				$consoleMsg = "[DEBUG] Email sent and message logged.";
			} else {
				$_SESSION['error_message'] = "❌ Failed to send message. Please try again later.";
				$consoleMsg = "[ERROR] Email failed to send.";
			}

		} else {
			$_SESSION['error_message'] = "❌ Please fill all fields.";
			$consoleMsg = "[VALIDATION ERROR] Missing fields.";
		}

		// JS Redirect with Console Log
	
header("Location: http://localhost/PetShop/contact.php");
exit;

	} else {
		$_SESSION['error_message'] = "❌ Invalid request method.";
		echo "<script>console.log('[ERROR] Invalid request method');</script>";
		echo "<script>window.location.href = 'http://localhost/PetShop/contact.php';</script>";
		exit;
		ob_end_flush(); // very last line of the file

	}
		