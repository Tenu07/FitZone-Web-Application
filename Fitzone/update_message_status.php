<?php
require_once __DIR__ . '/config.php';
// Allow Admin, Staff, or Manager to update status
require_role(['admin', 'staff', 'manager']);

// Determine dashboard redirect URL based on role
$dashboard_url = 'staff_dashboard.php#message-center'; // Default for staff/manager
if (has_role('admin')) {
    $dashboard_url = 'admin_dashboard.php#message-center';
}

$message_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$new_status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING);

// Validate inputs
if (!$message_id) {
    $_SESSION['error'] = "Invalid Message ID.";
    header("Location: " . $dashboard_url);
    exit;
}

$allowed_statuses = ['read', 'replied', 'spam', 'new']; // Define allowed statuses
if (!$new_status || !in_array($new_status, $allowed_statuses)) {
     $_SESSION['error'] = "Invalid status provided.";
     header("Location: " . $dashboard_url);
     exit;
}

try {
    // Check if message exists first (optional but good practice)
    $stmt_check = $pdo->prepare("SELECT id FROM contact_submissions WHERE id = ?");
    $stmt_check->execute([$message_id]);
    if (!$stmt_check->fetch()) {
         $_SESSION['error'] = "Message not found.";
         header("Location: " . $dashboard_url);
         exit;
    }

    // Prepare update statement
    $sql = "UPDATE contact_submissions SET status = :status";
    $params = [':status' => $new_status, ':id' => $message_id];

    // If marking as replied, also set replier info
    if ($new_status === 'replied') {
        $sql .= ", replied_by = :replied_by, replied_at = NOW()";
        $params[':replied_by'] = $_SESSION['user_id'];
    } else {
         // If changing status *away* from replied, clear replier info (optional logic)
         // $sql .= ", replied_by = NULL, replied_at = NULL";
    }

    $sql .= " WHERE id = :id";
    $stmt_update = $pdo->prepare($sql);

    if ($stmt_update->execute($params)) {
        $_SESSION['success'] = "Message status updated to '" . ucfirst($new_status) . "'.";
         // Log action (optional)
         // $pdo->prepare("INSERT INTO audit_logs (...) VALUES (...)")->execute([...]);
    } else {
        $_SESSION['error'] = "Failed to update message status.";
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error updating status: " . $e->getMessage();
    error_log($_SESSION['error']);
}

// Redirect back to the dashboard message section
header("Location: " . $dashboard_url);
exit;

?>
