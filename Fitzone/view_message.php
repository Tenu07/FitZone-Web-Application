<?php
require_once __DIR__ . '/config.php';
// Allow Admin, Staff, or Manager to view messages
require_role(['admin', 'staff', 'manager']);

$message_details = null;
$message_id = null;
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);

// Determine dashboard redirect URL based on role
$dashboard_url = 'staff_dashboard.php#message-center'; // Default for staff/manager
if (has_role('admin')) {
    $dashboard_url = 'admin_dashboard.php#message-center';
}

// --- Get Message ID and Fetch Data ---
if (isset($_GET['id'])) {
    $message_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($message_id) {
        try {
            // Fetch message details
            $stmt = $pdo->prepare("SELECT cs.*, u.name as replied_by_name
                                  FROM contact_submissions cs
                                  LEFT JOIN users u ON cs.replied_by = u.id
                                  WHERE cs.id = ?");
            $stmt->execute([$message_id]);
            $message_details = $stmt->fetch();

            if (!$message_details) {
                $_SESSION['error'] = "Message not found.";
                header("Location: " . $dashboard_url);
                exit;
            }

            // Mark as read if it's currently 'new'
            if ($message_details['status'] === 'new') {
                $stmt_read = $pdo->prepare("UPDATE contact_submissions SET status = 'read' WHERE id = ?");
                $stmt_read->execute([$message_id]);
                $message_details['status'] = 'read'; // Update status for display
            }

        } catch (PDOException $e) {
            $error = "Database error fetching message: " . $e->getMessage();
            error_log($error);
        }
    } else {
        $_SESSION['error'] = "Invalid Message ID.";
        header("Location: " . $dashboard_url);
        exit;
    }
} else {
    $_SESSION['error'] = "No Message ID specified.";
    header("Location: " . $dashboard_url);
    exit;
}

// --- Handle Marking as Replied ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_replied'])) {
     $submitted_message_id = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);
     if ($submitted_message_id === $message_id) {
         try {
             $stmt_reply = $pdo->prepare("UPDATE contact_submissions SET status = 'replied', replied_by = ?, replied_at = NOW() WHERE id = ?");
             if ($stmt_reply->execute([$_SESSION['user_id'], $message_id])) {
                 $_SESSION['success'] = "Message marked as replied.";
                 // Log action (optional)
                 // $pdo->prepare("INSERT INTO audit_logs (...) VALUES (...)")->execute([...]);
                 header("Location: view_message.php?id=" . $message_id); // Reload the page
                 exit;
             } else {
                  $error = "Failed to mark message as replied.";
             }
         } catch (PDOException $e) {
             $error = "Database error updating message status: " . $e->getMessage();
             error_log($error);
         }
     } else {
         $error = "Form submission error.";
     }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Contact Message - FitZone</title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Re-use styles */
        :root {
            --primary-color: #00b4d8;
            --secondary-color: #6c757d;
            --dark-color: #1a1a1a;
            --light-bg: #f8f9fa;
            --border-color: #dee2e6;
            --success-color: #28a745;
            --info-color: #17a2b8;
        }
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; color: #333; }
        .container { max-width: 800px; margin: 2rem auto; padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: var(--primary-color); margin: 0 0 1.5rem 0; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
        .message-details dl { display: grid; grid-template-columns: 150px 1fr; gap: 0.75rem 1rem; margin-bottom: 1.5rem; }
        .message-details dt { font-weight: 600; color: #555; text-align: right; }
        .message-details dd { margin: 0; word-break: break-word; }
        .message-body { background-color: var(--light-bg); border: 1px solid var(--border-color); padding: 1rem; border-radius: 5px; margin-top: 1rem; white-space: pre-wrap; /* Preserve line breaks */ line-height: 1.7; }
        .status-badge { padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
        .status-new { background-color: #fff3cd; color: #856404; }
        .status-read { background-color: #d1ecf1; color: #0c5460; }
        .status-replied { background-color: #d4edda; color: #155724; }
        .status-spam { background-color: #f8d7da; color: #721c24; }
        .actions { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; border: none; border-radius: 50px; cursor: pointer; font-size: 0.95rem; font-weight: 500; text-decoration: none; transition: all 0.3s; }
        .btn-primary { background-color: var(--primary-color); color: white; }
        .btn-primary:hover { background-color: #0096b4; }
        .btn-secondary { background-color: var(--secondary-color); color: white; }
        .btn-secondary:hover { background-color: #5a6268; }
        .btn-success { background-color: var(--success-color); color: white; }
        .btn-success:hover { background-color: #218838; }
        .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px; border: 1px solid transparent; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .alert-success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-envelope-open-text"></i> View Contact Message</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($message_details): ?>
            <div class="message-details">
                <dl>
                    <dt>Received:</dt>
                    <dd><?= date('M j, Y H:i A', strtotime($message_details['submission_date'])) ?></dd>

                    <dt>From:</dt>
                    <dd><?= htmlspecialchars($message_details['name']) ?></dd>

                    <dt>Email:</dt>
                    <dd><a href="mailto:<?= htmlspecialchars($message_details['email']) ?>"><?= htmlspecialchars($message_details['email']) ?></a></dd>

                    <dt>Phone:</dt>
                    <dd><?= htmlspecialchars($message_details['phone'] ?: 'N/A') ?></dd>

                    <dt>Subject:</dt>
                    <dd><?= htmlspecialchars($message_details['subject']) ?></dd>

                    <dt>Status:</dt>
                    <dd><span class="status-badge status-<?= strtolower($message_details['status']) ?>"><?= ucfirst($message_details['status']) ?></span></dd>

                    <?php if ($message_details['status'] === 'replied' && $message_details['replied_by']): ?>
                        <dt>Replied By:</dt>
                        <dd><?= htmlspecialchars($message_details['replied_by_name'] ?? 'Staff/Admin') ?> on <?= date('M j, Y H:i', strtotime($message_details['replied_at'])) ?></dd>
                    <?php endif; ?>

                    <dt>IP Address:</dt>
                    <dd><?= htmlspecialchars($message_details['ip_address'] ?? 'N/A') ?></dd>

                    <dt>User Agent:</dt>
                    <dd><?= htmlspecialchars($message_details['user_agent'] ?? 'N/A') ?></dd>

                    <dt>Message:</dt>
                    <dd class="message-body"><?= nl2br(htmlspecialchars($message_details['message'])) ?></dd>
                </dl>
            </div>

            <div class="actions">
                <a href="<?= $dashboard_url ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Messages</a>
                <?php if ($message_details['status'] !== 'replied' && $message_details['status'] !== 'spam'): ?>
                    <form method="POST" action="view_message.php?id=<?= $message_id ?>" style="display: inline;">
                         <input type="hidden" name="message_id" value="<?= $message_id ?>">
                         <button type="submit" name="mark_replied" class="btn btn-success"><i class="fas fa-check-circle"></i> Mark as Replied</button>
                    </form>
                 <?php endif; ?>
                 <a href="mailto:<?= htmlspecialchars($message_details['email']) ?>?subject=Re: <?= htmlspecialchars($message_details['subject']) ?>" class="btn btn-primary"><i class="fas fa-reply"></i> Reply via Email</a>
            </div>

        <?php elseif (!$error) : ?>
            <p>Could not load message details.</p>
             <a href="<?= $dashboard_url ?>" class="btn btn-secondary">Back to Messages</a>
        <?php endif; ?>
    </div>
</body>
</html>
