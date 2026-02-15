<?php
session_start();
require 'config.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin.php");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No staff member specified";
    header("Location: admin_dashboard.php");
    exit;
}

$staff_id = (int)$_GET['id'];

// Check if staff exists
$stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch();

if (!$staff) {
    $_SESSION['error'] = "Staff member not found";
    header("Location: admin_dashboard.php");
    exit;
}

// Prevent deleting own account
if ($staff_id == $_SESSION['admin_id']) {
    $_SESSION['error'] = "You cannot delete your own account";
    header("Location: admin_dashboard.php");
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        // First, check if staff is assigned to any classes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE instructor_id = ?");
        $stmt->execute([$staff_id]);
        $class_count = $stmt->fetchColumn();
        
        if ($class_count > 0) {
            $_SESSION['error'] = "Cannot delete staff member assigned to classes. Reassign classes first.";
            header("Location: admin_dashboard.php");
            exit;
        }
        
        // Delete the staff member
        $stmt = $pdo->prepare("DELETE FROM staff WHERE id = ?");
        $stmt->execute([$staff_id]);
        
        $_SESSION['success'] = "Staff member deleted successfully";
        header("Location: admin_dashboard.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting staff member: " . $e->getMessage();
        header("Location: admin_dashboard.php");
        exit;
    }
}

// If not POST request, show confirmation page
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Staff - FitZone Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }
        .alert-danger {
            background-color: #fff2f0;
            color: #f5222d;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            margin: 0 0.5rem;
        }
        .btn-danger {
            background-color: #f5222d;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Delete Staff Member</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <p>Are you sure you want to delete the staff member "<?= htmlspecialchars($staff['username']) ?>"?</p>
        
        <form method="POST" action="delete_staff.php?id=<?= $staff_id ?>">
            <button type="submit" name="confirm_delete" class="btn btn-danger">Yes, Delete</button>
            <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>