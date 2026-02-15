<?php
require_once __DIR__ . '/config.php';
// Allow Admin, Staff, or Manager to delete classes
require_role(['admin', 'staff', 'manager']);

$class_to_delete = null;
$class_id = null;
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']); // Clear messages

// Determine dashboard redirect URL based on role
$dashboard_url = 'staff_dashboard.php#class-management'; // Default for staff/manager
if (has_role('admin')) {
    $dashboard_url = 'admin_dashboard.php#class-management';
}

// --- Get Class ID and Fetch Class Data ---
if (isset($_GET['id'])) {
    $class_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if (!$class_id) {
        $_SESSION['error'] = "Invalid Class ID format.";
        header("Location: " . $dashboard_url);
        exit;
    }

    try {
        // Fetch class details to confirm deletion
        $stmt = $pdo->prepare("SELECT id, title FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $class_to_delete = $stmt->fetch();

        if (!$class_to_delete) {
            $_SESSION['error'] = "Class not found.";
            header("Location: " . $dashboard_url);
            exit;
        }

        // Check for active registrations (optional but recommended)
        $stmt_check_regs = $pdo->prepare("SELECT COUNT(*) FROM class_registrations WHERE class_id = ? AND attendance_status = 'registered'");
        $stmt_check_regs->execute([$class_id]);
        if ($stmt_check_regs->fetchColumn() > 0) {
            $_SESSION['error'] = "Cannot delete class with active registrations. Please manage registrations first.";
            header("Location: " . $dashboard_url);
            exit;
        }

    } catch (PDOException $e) {
        $error = "Database error fetching class: " . $e->getMessage();
        error_log($error);
        // Display error on this page
    }

} else {
    $_SESSION['error'] = "No Class ID specified for deletion.";
    header("Location: " . $dashboard_url);
    exit;
}

// --- Handle Deletion Confirmation ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $submitted_class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
    if ($submitted_class_id !== $class_id) {
        $_SESSION['error'] = "Deletion confirmation error. Please try again.";
        header("Location: " . $dashboard_url);
        exit;
    }

    try {
        // Delete related registrations first (optional, depends on desired behavior)
        // $pdo->prepare("DELETE FROM class_registrations WHERE class_id = ?")->execute([$class_id]);

        // Delete the class
        $stmt_delete = $pdo->prepare("DELETE FROM classes WHERE id = ?");
        if ($stmt_delete->execute([$class_id])) {
            $_SESSION['success'] = "Class '" . htmlspecialchars($class_to_delete['title']) . "' deleted successfully.";
            // Log action (optional)
            // $pdo->prepare("INSERT INTO audit_logs (...) VALUES (...)")->execute([...]);
            header("Location: " . $dashboard_url);
            exit;
        } else {
            $error = "Failed to delete class. Please try again.";
        }
    } catch (PDOException $e) {
        $error = "Database error during deletion: " . $e->getMessage();
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
    <title>Confirm Class Deletion - FitZone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Re-use styles from delete_user.php */
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
        .class-details { background-color: #f8f9fa; padding: 1rem; border-radius: 5px; margin-bottom: 1.5rem; border: 1px solid var(--border-color); text-align: left; }
        .class-details p { margin: 0.5rem 0; }
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

        <?php if ($class_to_delete): ?>
            <p>Are you sure you want to delete the following class? This action cannot be undone.</p>
            <div class="class-details">
                <p><strong>ID:</strong> <?= htmlspecialchars($class_to_delete['id']) ?></p>
                <p><strong>Title:</strong> <?= htmlspecialchars($class_to_delete['title']) ?></p>
            </div>

            <form method="POST" action="delete_class.php?id=<?= $class_id ?>">
                <input type="hidden" name="class_id" value="<?= $class_id ?>">
                <div class="button-group">
                    <button type="submit" name="confirm_delete" class="btn btn-danger">Yes, Delete Class</button>
                    <a href="<?= $dashboard_url ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php elseif (!$error): ?>
             <p>Class information could not be loaded.</p>
              <a href="<?= $dashboard_url ?>" class="btn btn-secondary">Back to Dashboard</a>
        <?php endif; ?>
    </div>
</body>
</html>
