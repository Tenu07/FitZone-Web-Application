<?php
require_once __DIR__ . '/config.php'; // Use require_once and absolute path
require_role('admin'); // Only Admins can access this page

$user_to_edit = null;
$user_id = null;
$error = $_SESSION['error'] ?? null; // Get error from session if redirected
$success = $_SESSION['success'] ?? null; // Get success message from session
unset($_SESSION['error'], $_SESSION['success']); // Clear messages after displaying

// --- Fetch User Data for Editing ---
if (isset($_GET['id'])) {
    $user_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($user_id) {
        try {
            // Fetch user details from the main users table
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_to_edit = $stmt->fetch();

            if (!$user_to_edit) {
                $_SESSION['error'] = "User not found.";
                header("Location: admin_dashboard.php#staff-management"); // Redirect back to staff section
                exit;
            }
            // Optional: Prevent admin from editing themselves via this form?
            // if ($user_to_edit['id'] == $_SESSION['user_id']) { ... }

        } catch (PDOException $e) {
            $error = "Error fetching user data: " . $e->getMessage();
            error_log($error);
        }
    } else {
        $_SESSION['error'] = "Invalid User ID.";
        header("Location: admin_dashboard.php#staff-management");
        exit;
    }
} else {
     $_SESSION['error'] = "No User ID specified for editing.";
     header("Location: admin_dashboard.php#staff-management");
     exit;
}


// --- Handle Form Submission (Update User) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    // Validate hidden user ID matches the one being edited
    $submitted_user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    if ($submitted_user_id !== $user_id) {
        $_SESSION['error'] = "Form submission error. Please try again.";
        header("Location: admin_dashboard.php#staff-management");
        exit;
    }

    // Sanitize and validate inputs
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
    $position = trim(filter_input(INPUT_POST, 'position', FILTER_SANITIZE_STRING));
    $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING)); // Basic sanitize, more specific validation might be needed
    $address = trim(filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING));
    $is_active = isset($_POST['is_active']) ? 1 : 0; // Checkbox value
    $new_password = $_POST['password']; // Don't trim passwords

    // Basic Validation
    if (empty($name) || empty($email) || empty($role)) {
        $error = "Name, Email, and Role are required.";
    } elseif (!$email) {
        $error = "Invalid Email format.";
    } elseif (!in_array($role, ['customer', 'trainer', 'staff', 'admin', 'manager'])) { // Validate role
        $error = "Invalid Role selected.";
    } else {
        // Check if email already exists for another user
        try {
            $stmt_check_email = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt_check_email->execute([$email, $user_id]);
            if ($stmt_check_email->fetch()) {
                $error = "This email address is already registered by another user.";
            } else {
                // Prepare SQL Update
                $sql_parts = [
                    "name = :name",
                    "email = :email",
                    "role = :role",
                    "position = :position",
                    "phone = :phone",
                    "address = :address",
                    "is_active = :is_active"
                ];
                $params = [
                    ':name' => $name,
                    ':email' => $email,
                    ':role' => $role,
                    ':position' => $position ?: null, // Store null if empty
                    ':phone' => $phone ?: null,
                    ':address' => $address ?: null,
                    ':is_active' => $is_active,
                    ':id' => $user_id
                ];

                // Only update password if provided
                if (!empty($new_password)) {
                    if (strlen($new_password) < 8) { // Add password validation
                         $error = "New password must be at least 8 characters long.";
                    } else {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $sql_parts[] = "password = :password";
                        $params[':password'] = $hashed_password;
                    }
                }

                // Execute Update only if no password error occurred
                 if (!$error) {
                    $sql = "UPDATE users SET " . implode(", ", $sql_parts) . " WHERE id = :id";
                    $stmt_update = $pdo->prepare($sql);

                    if ($stmt_update->execute($params)) {
                        $_SESSION['success'] = "User details updated successfully!";
                        // Redirect to prevent form resubmission
                        header("Location: admin_dashboard.php#staff-management");
                        exit;
                    } else {
                        $error = "Failed to update user details. Please try again.";
                    }
                 }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
            error_log($error);
        }
    }
     // If there was an error, repopulate the $user_to_edit array with submitted values for sticky form
     if ($error) {
         $user_to_edit['name'] = $name;
         $user_to_edit['email'] = $_POST['email']; // Use original POST email before filter_var
         $user_to_edit['role'] = $role;
         $user_to_edit['position'] = $position;
         $user_to_edit['phone'] = $phone;
         $user_to_edit['address'] = $address;
         $user_to_edit['is_active'] = $is_active;
     }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - FitZone Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Basic Admin Form Styles */
        :root {
            --primary-color: #00b4d8;
            --secondary-color: #6c757d;
            --danger-color: #d9534f;
            --success-color: #5cb85c;
            --light-bg: #f8f9fa;
            --border-color: #dee2e6;
        }
        body { font-family: 'Poppins', sans-serif; background-color: #f5f7fa; margin: 0; padding: 0; }
        .container { max-width: 700px; margin: 2rem auto; padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: var(--primary-color); margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; font-weight: 600; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #495057; }
        input[type="text"], input[type="email"], input[type="password"], select, textarea {
            width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 4px; font-size: 1rem; box-sizing: border-box; transition: border-color 0.3s;
        }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(0, 180, 216, 0.2); }
        textarea { min-height: 80px; resize: vertical; }
        .form-check { display: flex; align-items: center; gap: 0.5rem; }
        .form-check-input { width: auto; margin-top: 0; }
        .form-check-label { margin-bottom: 0; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; border: none; border-radius: 50px; cursor: pointer; font-size: 0.95rem; font-weight: 500; text-decoration: none; transition: all 0.3s; }
        .btn-primary { background-color: var(--primary-color); color: white; }
        .btn-primary:hover { background-color: #0096b4; transform: translateY(-2px); }
        .btn-secondary { background-color: var(--secondary-color); color: white; margin-left: 0.5rem; }
        .btn-secondary:hover { background-color: #5a6268; transform: translateY(-2px); }
        .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px; border: 1px solid transparent; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .alert-success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .required:after { content: " *"; color: var(--danger-color); }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit User: <?= htmlspecialchars($user_to_edit['name'] ?? 'N/A') ?></h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($user_to_edit): ?>
            <form method="POST" action="edit_staff.php?id=<?= $user_id ?>">
                <input type="hidden" name="user_id" value="<?= $user_id ?>">

                <div class="form-group">
                    <label for="name" class="required">Full Name</label>
                    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($user_to_edit['name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email" class="required">Email</label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($user_to_edit['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">New Password (leave blank to keep current)</label>
                    <input type="password" id="password" name="password" placeholder="Enter new password (min 8 chars)">
                     <small>Password will only be updated if a value is entered.</small>
                </div>

                <div class="form-group">
                    <label for="role" class="required">Role</label>
                    <select id="role" name="role" required>
                        <option value="customer" <?= ($user_to_edit['role'] ?? '') == 'customer' ? 'selected' : '' ?>>Customer</option>
                        <option value="trainer" <?= ($user_to_edit['role'] ?? '') == 'trainer' ? 'selected' : '' ?>>Trainer</option>
                        <option value="staff" <?= ($user_to_edit['role'] ?? '') == 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="manager" <?= ($user_to_edit['role'] ?? '') == 'manager' ? 'selected' : '' ?>>Manager</option>
                        <option value="admin" <?= ($user_to_edit['role'] ?? '') == 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>

                 <div class="form-group">
                    <label for="position">Position/Title</label>
                    <input type="text" id="position" name="position" value="<?= htmlspecialchars($user_to_edit['position'] ?? '') ?>" placeholder="e.g., Personal Trainer, Front Desk">
                </div>

                 <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user_to_edit['phone'] ?? '') ?>">
                </div>

                 <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address"><?= htmlspecialchars($user_to_edit['address'] ?? '') ?></textarea>
                </div>

                 <div class="form-group form-check">
                    <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input" <?= ($user_to_edit['is_active'] ?? 0) == 1 ? 'checked' : '' ?>>
                    <label for="is_active" class="form-check-label">Account Active</label>
                 </div>


                <button type="submit" name="update_staff" class="btn btn-primary">Save Changes</button>
                <a href="admin_dashboard.php#staff-management" class="btn btn-secondary">Cancel</a>
            </form>
        <?php else: ?>
            <p>Could not load user data.</p>
             <a href="admin_dashboard.php#staff-management" class="btn btn-secondary">Back to Dashboard</a>
        <?php endif; ?>
    </div>
</body>
</html>
