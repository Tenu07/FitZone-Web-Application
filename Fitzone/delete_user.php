<?php
require_once __DIR__ . '/config.php'; // Use require_once and absolute path
require_role('admin'); // Only Admins can delete users

$user_to_delete = null;
$user_id = null;
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']); // Clear messages after displaying

// --- Get User ID and Fetch User Data ---
if (isset($_GET['id'])) {
    $user_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if (!$user_id) {
        $_SESSION['error'] = "Invalid User ID format.";
        header("Location: admin_dashboard.php#staff-management");
        exit;
    }

    // Prevent admin from deleting themselves
    if ($user_id === $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot delete your own account.";
        header("Location: admin_dashboard.php#staff-management");
        exit;
    }

    try {
        // Fetch user details to confirm deletion
        $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_to_delete = $stmt->fetch();

        if (!$user_to_delete) {
            $_SESSION['error'] = "User not found.";
            header("Location: admin_dashboard.php#staff-management");
            exit;
        }
        // Optional: Add checks here if the user shouldn't be deleted (e.g., assigned tasks)
        // Example: Check if trainer is assigned to classes
        if ($user_to_delete['role'] === 'trainer') {
            $stmt_check_classes = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE trainer_id = ? AND status = 'upcoming'");
            $stmt_check_classes->execute([$user_id]);
            if ($stmt_check_classes->fetchColumn() > 0) {
                 $_SESSION['error'] = "Cannot delete trainer assigned to upcoming classes. Please reassign classes first.";
                 header("Location: admin_dashboard.php#staff-management");
                 exit;
            }
        }

    } catch (PDOException $e) {
        $error = "Database error fetching user: " . $e->getMessage();
        error_log($error);
        // Display error on this page instead of redirecting immediately
    }

} else {
    $_SESSION['error'] = "No User ID specified for deletion.";
    header("Location: admin_dashboard.php#staff-management");
    exit;
}

// --- Handle Deletion Confirmation ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // Double-check the ID from the form matches the one from GET
     $submitted_user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
     if ($submitted_user_id !== $user_id) {
         $_SESSION['error'] = "Deletion confirmation error. Please try again.";
         header("Location: admin_dashboard.php#staff-management");
         exit;
     }

    try {
        // Perform the deletion
        $stmt_delete = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt_delete->execute([$user_id])) {
            $_SESSION['success'] = "User '" . htmlspecialchars($user_to_delete['name']) . "' deleted successfully.";
            // Log the action (if audit log table exists)
            // $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)")
            //    ->execute([$_SESSION['user_id'], 'delete_user', 'users', $user_id]);
            header("Location: admin_dashboard.php#staff-management");
            exit;
        } else {
            $error = "Failed to delete user. Please try again.";
        }
    } catch (PDOException $e) {
        // Catch potential foreign key constraint errors if user is linked elsewhere
        if ($e->getCode() == '23000') { // Integrity constraint violation
             $error = "Cannot delete user. They might be linked to other records (e.g., memberships, appointments). Please check dependencies.";
        } else {
            $error = "Database error during deletion: " . $e->getMessage();
        }
        error_log($error);
         // Show error on the confirmation page
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm User Deletion - FitZone Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Re-use styles from edit_staff.php or admin_dashboard.php */
        :root {
            --primary-color: #00b4d8;
            --secondary-color: #6c757d;
            --danger-color: #dc3545;
            --danger-dark: #c82333;
            --border-color: #dee2e6;
        }
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { max-width: 550px; margin: 2rem auto; padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; border-top: 5px solid var(--danger-color); }
        h1 { color: var(--danger-color); margin-bottom: 1rem; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
        h1 i { font-size: 1.5rem; }
        p { margin-bottom: 1.5rem; color: #555; line-height: 1.6; }
        strong { color: #000; }
        .user-details { background-color: #f8f9fa; padding: 1rem; border-radius: 5px; margin-bottom: 1.5rem; border: 1px solid var(--border-color); text-align: left; }
        .user-details p { margin: 0.5rem 0; }
        .button-group { display: flex; justify-content: center; gap: 1rem; margin-top: 2rem; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; border: none; border-radius: 50px; cursor: pointer; font-size: 0.95rem; font-weight: 500; text-decoration: none; transition: all 0.3s; }
        .btn-danger { background-color: var(--danger-color); color: white; }
        .btn-danger:hover { background-color: var(--danger-dark); transform: translateY(-2px); }
        .btn-secondary { background-color: var(--secondary-color); color: white; }
        .btn-secondary:hover { background-color: #5a6268; transform: translateY(-2px); }
        .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px; border: 1px solid transparent; text-align: left; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($user_to_delete): ?>
            <p>Are you absolutely sure you want to delete the following user? This action cannot be undone.</p>
            <div class="user-details">
                <p><strong>ID:</strong> <?= htmlspecialchars($user_to_delete['id']) ?></p>
                <p><strong>Name:</strong> <?= htmlspecialchars($user_to_delete['name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($user_to_delete['email']) ?></p>
                <p><strong>Role:</strong> <?= ucfirst(htmlspecialchars($user_to_delete['role'])) ?></p>
            </div>

            <form method="POST" action="delete_user.php?id=<?= $user_id ?>">
                <input type="hidden" name="user_id" value="<?= $user_id ?>">
                <div class="button-group">
                    <button type="submit" name="confirm_delete" class="btn btn-danger">Yes, Delete User</button>
                    <a href="admin_dashboard.php#staff-management" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php elseif (!$error): ?>
             <p>User information could not be loaded.</p>
              <a href="admin_dashboard.php#staff-management" class="btn btn-secondary">Back to Dashboard</a>
        <?php endif; ?>
    </div>
</body>
</html>
