<?php
require 'config.php'; // Include database connection

header('Content-Type: application/json'); // Set response type
$response = ['success' => false, 'message' => '']; // Initialize response

try {
    // Validate required fields
    $required = ['name', 'email', 'subject', 'message'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Please fill in all required fields.");
        }
    }

    // Sanitize input using modern filters
    $name = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = isset($_POST['phone']) ? htmlspecialchars($_POST['phone'], ENT_QUOTES, 'UTF-8') : null;
    $subject = htmlspecialchars($_POST['subject'], ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Please enter a valid email address.");
    }

    // Prepare SQL statement
    $stmt = $pdo->prepare("INSERT INTO contact_submissions 
                          (name, email, phone, subject, message, ip_address, user_agent) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)");

    // Get client info
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    // Execute the query
    if ($stmt->execute([$name, $email, $phone, $subject, $message, $ip_address, $user_agent])) {
        $response['success'] = true;
        $response['message'] = 'Thank you for your message! We will get back to you soon.';
    } else {
        throw new Exception("There was a problem submitting your message. Please try again.");
    }
} catch (PDOException $e) {
    $response['message'] = "Database error: " . $e->getMessage(); // Database error
} catch (Exception $e) {
    $response['message'] = $e->getMessage(); // Other errors
}

// Return JSON response
echo json_encode($response);
?>