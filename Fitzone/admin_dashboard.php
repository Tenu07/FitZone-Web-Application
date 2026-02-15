<?php
// No need to start session here if config.php does it
require_once __DIR__ . '/config.php'; // Use require_once and absolute path
require_role('admin'); // Only Admins can access this page

$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']); // Clear messages

// --- Handle form submission for adding staff ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING)); // Use 'name' field
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $password = $_POST['password']; // Don't trim password
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
    $position = trim(filter_input(INPUT_POST, 'position', FILTER_SANITIZE_STRING));

    // Basic Validation
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "Name, Email, Password, and Role are required.";
    } elseif (!$email) {
         $error = "Invalid Email format.";
    } elseif (strlen($password) < 8) { // Add password length check
        $error = "Password must be at least 8 characters long.";
    } elseif (!in_array($role, ['trainer', 'staff', 'admin', 'manager'])) { // Validate allowed roles
        $error = "Invalid Role selected for staff creation.";
    } else {
        // Check if email exists
        try {
            $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt_check->execute([$email]);
            if ($stmt_check->fetch()) {
                $error = "Email address already exists.";
            } else {
                // Hash password and insert into USERS table
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_insert = $pdo->prepare(
                    "INSERT INTO users (name, email, password, role, position, is_active, created_at, updated_at)
                     VALUES (:name, :email, :password, :role, :position, TRUE, NOW(), NOW())"
                );
                $params = [
                    ':name' => $name,
                    ':email' => $email,
                    ':password' => $hashed_password,
                    ':role' => $role,
                    ':position' => $position ?: null
                ];

                if ($stmt_insert->execute($params)) {
                    $success = "Staff/Admin account created successfully!";
                    // Optionally add to permissions table if needed
                    // $new_user_id = $pdo->lastInsertId();
                    // $pdo->prepare("INSERT INTO staff_permissions (user_id, ...) VALUES (?, ...)")->execute([$new_user_id, ...]);

                } else {
                    $error = "Error creating account. Please try again.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error during staff creation: " . $e->getMessage();
            error_log($error);
        }
    }
}

// --- Handle form submission for adding class ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING));
    $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
    $trainer_id = filter_input(INPUT_POST, 'trainer_id', FILTER_VALIDATE_INT);
    $start_time_str = $_POST['start_time'] ?? '';
    $end_time_str = $_POST['end_time'] ?? '';
    $capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT);
    $difficulty = filter_input(INPUT_POST, 'difficulty', FILTER_SANITIZE_STRING);
    $image_url = trim(filter_input(INPUT_POST, 'image_url', FILTER_SANITIZE_URL));
    $status = 'upcoming'; // Default status for new classes

    // Basic Validation
    if (empty($title) || empty($start_time_str) || empty($end_time_str) || $capacity === false || $capacity < 1) {
        $error = "Class Title, Start Time, End Time, and a valid Capacity are required.";
    } elseif (!in_array($difficulty, ['beginner', 'intermediate', 'advanced', 'all_levels'])) {
        $error = "Invalid Difficulty selected.";
    } else {
        $start_time = strtotime($start_time_str);
        $end_time = strtotime($end_time_str);

        if ($start_time === false || $end_time === false) {
            $error = "Invalid Start or End Time format.";
        } elseif ($end_time <= $start_time) {
            $error = "End Time must be after Start Time.";
        } else {
            $start_time_db = date('Y-m-d H:i:s', $start_time);
            $end_time_db = date('Y-m-d H:i:s', $end_time);

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO classes
                    (title, description, trainer_id, start_time, end_time, capacity, difficulty, image, status, created_at, updated_at)
                    VALUES (:title, :description, :trainer_id, :start_time, :end_time, :capacity, :difficulty, :image, :status, NOW(), NOW())
                ");
                 $params = [
                    ':title' => $title,
                    ':description' => $description ?: null,
                    ':trainer_id' => $trainer_id ?: null, // Allow null trainer
                    ':start_time' => $start_time_db,
                    ':end_time' => $end_time_db,
                    ':capacity' => $capacity,
                    ':difficulty' => $difficulty,
                    ':image' => $image_url ?: null,
                    ':status' => $status
                ];

                if ($stmt->execute($params)) {
                    $success = "Class added successfully!";
                } else {
                     $error = "Error adding class. Please try again.";
                }
            } catch (PDOException $e) {
                $error = "Database error adding class: " . $e->getMessage();
                error_log($error);
            }
        }
    }
}


// --- Fetch Data for Dashboard Display ---
$totalMembers = 0;
$activeMemberships = 0;
$monthlyRevenue = 0;
$recentMemberships = [];
$recentUsers = [];
$staffMembers = [];
$allClasses = [];
$trainers = [];
$contactMessages = [];

try {
    // Total members (customers)
    $totalMembers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();

    // Active memberships
    $activeMemberships = $pdo->query("SELECT COUNT(*) FROM memberships WHERE status = 'active' AND end_date >= CURDATE()")->fetchColumn();

    // Monthly revenue (Simplified: Sum of plan prices for memberships started in last 30 days)
    // A more accurate calculation would involve a payments table.
    $monthlyRevenue = $pdo->query("
        SELECT SUM(p.price)
        FROM memberships m
        JOIN plans p ON m.plan_id = p.id
        WHERE m.start_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND m.payment_status = 'paid' -- Assuming payment status
    ")->fetchColumn();

    // Recent memberships
    $recentMemberships = $pdo->query("
        SELECT u.name as user_name, p.name as plan_name, m.start_date, m.end_date, m.status
        FROM memberships m
        JOIN users u ON m.user_id = u.id
        JOIN plans p ON m.plan_id = p.id
        ORDER BY m.start_date DESC
        LIMIT 5
    ")->fetchAll();

    // Recent users (all roles)
    $recentUsers = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

    // Get staff members (non-customers)
    $staffMembers = $pdo->query("SELECT id, name, email, role, position, created_at FROM users WHERE role != 'customer' ORDER BY created_at DESC")->fetchAll();

    // Get all classes with trainer names (JOINING WITH USERS TABLE) - *** FIXED QUERY ***
    $allClasses = $pdo->query("
        SELECT c.*, u.name as trainer_name
        FROM classes c
        LEFT JOIN users u ON c.trainer_id = u.id AND u.role = 'trainer' -- Join users table for trainer name
        ORDER BY c.start_time DESC
    ")->fetchAll();

    // Get trainers for class assignment dropdown
    $trainers = $pdo->query("SELECT id, name FROM users WHERE role = 'trainer' AND is_active = TRUE ORDER BY name")->fetchAll();

    // Get Contact Messages
    $contactMessages = $pdo->query("SELECT * FROM contact_submissions ORDER BY submission_date DESC LIMIT 10")->fetchAll();


} catch (PDOException $e) {
    // Display error prominently if data fetching fails
    $dashboard_error = "Database error fetching dashboard data: " . $e->getMessage();
    error_log($dashboard_error);
    // Consider dying here or showing a specific error section
    // die($dashboard_error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FitZone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Comprehensive Admin Styles */
        :root {
            --primary-color: #00b4d8;
            --primary-dark: #0096b4;
            --secondary-color: #6c757d;
            --dark-color: #1a1a1a;
            --light-color: #f8f9fa;
            --border-color: #dee2e6;
            --text-color: #333;
            --muted-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --white: #ffffff;
            --card-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7f6; color: var(--text-color); line-height: 1.6; }

        /* Header */
        .dashboard-header { background-color: var(--dark-color); color: var(--white); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 1010; }
        .logo { font-size: 1.6rem; font-weight: 700; color: var(--white); text-decoration: none; display: flex; align-items: center; }
        .logo i { margin-right: 0.5rem; color: var(--primary-color); }
        .logo span { color: var(--primary-color); }
        .user-nav { display: flex; align-items: center; gap: 1rem; }
        .user-nav span { font-size: 0.9rem; }
        .logout-btn { background-color: var(--primary-color); color: var(--white); padding: 0.5rem 1rem; border-radius: 50px; text-decoration: none; font-size: 0.85rem; font-weight: 500; transition: background-color 0.3s; }
        .logout-btn:hover { background-color: var(--primary-dark); }

        /* Main Container */
        .dashboard-container { max-width: 1400px; margin: 1.5rem auto; padding: 0 1.5rem; }

        /* Welcome & Quick Actions */
        .welcome-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; background: var(--white); padding: 1.5rem; border-radius: 8px; box-shadow: var(--card-shadow); flex-wrap: wrap; gap: 1rem; }
        .welcome-text h1 { font-size: 1.5rem; margin: 0 0 0.25rem 0; font-weight: 600; color: var(--dark-color); }
        .welcome-text p { color: var(--muted-color); margin: 0; font-size: 0.9rem; }
        .quick-actions a { margin-left: 0.5rem; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem; }
        .stat-card { background-color: var(--white); border-radius: 8px; box-shadow: var(--card-shadow); padding: 1.5rem; text-align: center; border-left: 4px solid var(--primary-color); transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card h3 { color: var(--muted-color); margin: 0 0 0.5rem 0; font-size: 0.9rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card p { font-size: 2rem; font-weight: 700; color: var(--dark-color); margin: 0; }

        /* Dashboard Cards */
        .dashboard-card { background-color: var(--white); border-radius: 8px; box-shadow: var(--card-shadow); padding: 1.5rem; margin-bottom: 1.5rem; }
        .dashboard-card h2 { font-size: 1.2rem; font-weight: 600; color: var(--dark-color); margin: 0 0 1rem 0; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 0.5rem; }
        .dashboard-card h2 i { color: var(--primary-color); }

        /* Tables */
        .table-responsive { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .data-table th, .data-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        .data-table thead th { background-color: var(--light-color); font-weight: 600; color: var(--muted-color); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.5px; }
        .data-table tbody tr:hover { background-color: #f8f9fa; }
        .data-table td:last-child { white-space: nowrap; } /* Prevent actions wrapping */

        /* Status Badges */
        .status-badge { padding: 0.25rem 0.6rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-active, .status-paid, .status-completed, .status-upcoming, .status-read, .status-replied { background-color: #d4edda; color: #155724; }
        .status-expired, .status-cancelled, .status-failed, .status-spam { background-color: #f8d7da; color: #721c24; }
        .status-pending, .status-new, .status-scheduled { background-color: #fff3cd; color: #856404; }
        .status-partial { background-color: #d1ecf1; color: #0c5460; }

        /* Action Links/Buttons */
        .action-link { color: var(--primary-color); text-decoration: none; margin-right: 0.75rem; font-weight: 500; }
        .action-link:hover { text-decoration: underline; color: var(--primary-dark); }
        .action-link.delete { color: var(--danger-color); }
        .action-link.delete:hover { color: #a71d2a; }

        /* Forms */
        .form-section { margin-bottom: 1.5rem; padding: 1.5rem; border: 1px solid var(--border-color); border-radius: 5px; background-color: var(--light-color); }
        .form-section h3 { font-size: 1rem; font-weight: 600; margin: 0 0 1rem 0; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.3rem; font-weight: 500; font-size: 0.9rem; color: #495057; }
        .form-control { width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-color); border-radius: 4px; font-size: 0.95rem; font-family: inherit; }
        .form-control:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(0, 180, 216, 0.2); }
        textarea.form-control { min-height: 60px; resize: vertical; }

        /* Buttons */
        .btn { display: inline-block; padding: 0.6rem 1.2rem; border: none; border-radius: 50px; cursor: pointer; font-size: 0.9rem; font-weight: 500; text-decoration: none; transition: all 0.3s; text-align: center; }
        .btn-primary { background-color: var(--primary-color); color: var(--white); }
        .btn-primary:hover { background-color: var(--primary-dark); transform: translateY(-1px); }
        .btn-secondary { background-color: var(--secondary-color); color: var(--white); }
        .btn-secondary:hover { background-color: #5a6268; transform: translateY(-1px); }
        .btn-danger { background-color: var(--danger-color); color: var(--white); }
        .btn-danger:hover { background-color: #c82333; transform: translateY(-1px); }
        .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.8rem; }

        /* Layout */
        .grid-container { display: grid; gap: 1.5rem; }
        .grid-col-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-col-3 { grid-template-columns: repeat(3, 1fr); }

        /* Alerts */
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; border: 1px solid transparent; font-size: 0.95rem; }
        .alert-success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }

        /* Responsive */
        @media (max-width: 1200px) {
            .grid-col-2, .grid-col-3 { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .dashboard-header { padding: 0.8rem 1rem; }
            .logo { font-size: 1.4rem; }
            .dashboard-container { margin: 1rem auto; padding: 0 0.75rem; }
            .welcome-section { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .stats-grid { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; }
            .stat-card p { font-size: 1.6rem; }
            .dashboard-card { padding: 1rem; }
            .dashboard-card h2 { font-size: 1.1rem; }
            .data-table { font-size: 0.85rem; }
            .data-table th, .data-table td { padding: 0.6rem; }
            .btn { width: 100%; margin-bottom: 0.5rem; }
            .quick-actions a { margin-left: 0; }
            .form-section { padding: 1rem; }
        }
    </style>
</head>
<body>
    <header class="dashboard-header">
        <a href="admin_dashboard.php" class="logo"><i class="fas fa-user-shield"></i> Fit<span>Zone</span> Admin</a>
        <div class="user-nav">
            <span><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></span>
            <a href="logout.php" class="logout-btn">Logout <i class="fas fa-sign-out-alt"></i></a>
        </div>
    </header>

    <div class="dashboard-container">

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
         <?php if (isset($dashboard_error)): ?>
            <div class="alert alert-danger"><strong>Dashboard Error:</strong> <?= htmlspecialchars($dashboard_error) ?></div>
        <?php endif; ?>

        <div class="welcome-section">
            <div class="welcome-text">
                <h1>Admin Dashboard</h1>
                <p>Overview of your fitness center operations.</p>
            </div>
            <div class="quick-actions">
                 <a href="admin_dashboard.php#staff-management" class="btn btn-secondary btn-sm"><i class="fas fa-users-cog"></i> Manage Staff</a>
                 <a href="admin_dashboard.php#class-management" class="btn btn-secondary btn-sm"><i class="fas fa-chalkboard-teacher"></i> Manage Classes</a>
                 <a href="admin_dashboard.php#message-center" class="btn btn-secondary btn-sm"><i class="fas fa-envelope"></i> Messages</a>
                 </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-users"></i> Total Customers</h3>
                <p><?= number_format($totalMembers ?? 0) ?></p>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-user-check"></i> Active Memberships</h3>
                <p><?= number_format($activeMemberships ?? 0) ?></p>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-dollar-sign"></i> Revenue (Last 30d)</h3>
                <p>Rs <?= number_format($monthlyRevenue ?? 0, 2) ?></p>
            </div>
        </div>

        <div class="grid-container grid-col-2">
            <div class="dashboard-card">
                <h2><i class="fas fa-history"></i> Recent Memberships</h2>
                 <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr><th>Member</th><th>Plan</th><th>Start Date</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentMemberships)): ?>
                                <?php foreach ($recentMemberships as $membership): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($membership['user_name']) ?></td>
                                        <td><?= htmlspecialchars($membership['plan_name']) ?></td>
                                        <td><?= date('M j, Y', strtotime($membership['start_date'])) ?></td>
                                        <td><span class="status-badge status-<?= strtolower($membership['status']) ?>"><?= ucfirst($membership['status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4">No recent memberships found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                 </div>
            </div>

            <div class="dashboard-card">
                <h2><i class="fas fa-user-plus"></i> Recently Joined Users</h2>
                 <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr>
                        </thead>
                        <tbody>
                             <?php if (!empty($recentUsers)): ?>
                                <?php foreach ($recentUsers as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['name']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= ucfirst(htmlspecialchars($user['role'])) ?></td>
                                        <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4">No recent users found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                 </div>
            </div>
        </div>


        <div class="dashboard-card" id="staff-management">
            <h2><i class="fas fa-users-cog"></i> Staff & Admin Management</h2>
            <div class="grid-container grid-col-2">
                <div class="form-section">
                    <h3><i class="fas fa-user-plus"></i> Add New Staff/Admin</h3>
                    <form method="POST" action="admin_dashboard.php#staff-management">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password (min 8 chars)</label>
                            <input type="password" id="password" name="password" class="form-control" required minlength="8">
                        </div>
                         <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="trainer">Trainer</option>
                                <option value="staff">Staff</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                         <div class="form-group">
                            <label for="position">Position/Title (Optional)</label>
                            <input type="text" id="position" name="position" class="form-control" placeholder="e.g., Head Trainer, Front Desk Lead">
                        </div>
                        <button type="submit" name="add_staff" class="btn btn-primary">Add User</button>
                    </form>
                </div>

                <div class="staff-list-section">
                     <h3><i class="fas fa-list"></i> Current Staff & Admins</h3>
                     <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr><th>Name</th><th>Email</th><th>Role</th><th>Position</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($staffMembers)): ?>
                                    <?php foreach ($staffMembers as $staff): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($staff['name']) ?></td>
                                            <td><?= htmlspecialchars($staff['email']) ?></td>
                                            <td><?= ucfirst(htmlspecialchars($staff['role'])) ?></td>
                                            <td><?= htmlspecialchars($staff['position'] ?? 'N/A') ?></td>
                                            <td>
                                                <a href="edit_staff.php?id=<?= $staff['id'] ?>" class="action-link" title="Edit"><i class="fas fa-edit"></i></a>
                                                <?php if ($staff['id'] != $_SESSION['user_id']): // Prevent self-deletion ?>
                                                <a href="delete_user.php?id=<?= $staff['id'] ?>" class="action-link delete" title="Delete" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')"><i class="fas fa-trash-alt"></i></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5">No staff or admin users found (except maybe yourself).</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                     </div>
                </div>
            </div>
        </div>

        <div class="dashboard-card" id="class-management">
            <h2><i class="fas fa-chalkboard-teacher"></i> Class Management</h2>
             <div class="grid-container grid-col-2">
                 <div class="form-section">
                     <h3><i class="fas fa-plus-circle"></i> Add New Class</h3>
                    <form method="POST" action="admin_dashboard.php#class-management">
                        <div class="form-group">
                            <label for="title">Class Title</label>
                            <input type="text" id="title" name="title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="2"></textarea>
                        </div>
                         <div class="form-group">
                            <label for="trainer_id">Trainer (Optional)</label>
                            <select id="trainer_id" name="trainer_id" class="form-control">
                                 <option value="">-- Select Trainer --</option>
                                <?php foreach ($trainers as $trainer): ?>
                                    <option value="<?= $trainer['id'] ?>"><?= htmlspecialchars($trainer['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                         <div class="input-group">
                             <div class="form-group">
                                <label for="start_time">Start Time</label>
                                <input type="datetime-local" id="start_time" name="start_time" class="form-control" required>
                            </div>
                             <div class="form-group">
                                <label for="end_time">End Time</label>
                                <input type="datetime-local" id="end_time" name="end_time" class="form-control" required>
                            </div>
                         </div>
                          <div class="input-group">
                            <div class="form-group">
                                <label for="capacity">Capacity</label>
                                <input type="number" id="capacity" name="capacity" class="form-control" min="1" required>
                            </div>
                            <div class="form-group">
                                <label for="difficulty">Difficulty</label>
                                <select id="difficulty" name="difficulty" class="form-control" required>
                                    <option value="beginner">Beginner</option>
                                    <option value="intermediate">Intermediate</option>
                                    <option value="advanced">Advanced</option>
                                    <option value="all_levels" selected>All Levels</option>
                                </select>
                            </div>
                         </div>
                         <div class="form-group">
                            <label for="image_url">Image URL (Optional)</label>
                            <input type="url" id="image_url" name="image_url" class="form-control" placeholder="https://...">
                        </div>
                        <button type="submit" name="add_class" class="btn btn-primary">Add Class</button>
                    </form>
                </div>

                 <div class="class-list-section">
                     <h3><i class="fas fa-list-ul"></i> Existing Classes</h3>
                     <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr><th>Title</th><th>Trainer</th><th>Time</th><th>Cap.</th><th>Status</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($allClasses)): ?>
                                    <?php foreach ($allClasses as $class): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($class['title']) ?></td>
                                            <td><?= htmlspecialchars($class['trainer_name'] ?? 'N/A') ?></td>
                                            <td><?= date('M j, g:i A', strtotime($class['start_time'])) ?></td>
                                            <td><?= $class['capacity'] ?></td>
                                            <td><span class="status-badge status-<?= strtolower($class['status']) ?>"><?= ucfirst($class['status']) ?></span></td>
                                            <td>
                                                <a href="edit_class.php?id=<?= $class['id'] ?>" class="action-link" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="delete_class.php?id=<?= $class['id'] ?>" class="action-link delete" title="Delete" onclick="return confirm('Are you sure you want to delete this class? This may affect registrations.')"><i class="fas fa-trash-alt"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                     <tr><td colspan="6">No classes found. Add one using the form.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                     </div>
                </div>
             </div>
        </div>

         <div class="dashboard-card" id="message-center">
             <h2><i class="fas fa-envelope-open-text"></i> Contact Messages</h2>
             <div class="table-responsive">
                 <table class="data-table">
                     <thead>
                         <tr>
                             <th>Received</th>
                             <th>From</th>
                             <th>Subject</th>
                             <th>Status</th>
                             <th>Actions</th>
                         </tr>
                     </thead>
                     <tbody>
                         <?php if (!empty($contactMessages)): ?>
                             <?php foreach ($contactMessages as $msg): ?>
                                 <tr>
                                     <td><?= date('M j, Y H:i', strtotime($msg['submission_date'])) ?></td>
                                     <td><?= htmlspecialchars($msg['name']) ?> (<a href="mailto:<?= htmlspecialchars($msg['email']) ?>"><?= htmlspecialchars($msg['email']) ?></a>)</td>
                                     <td><?= htmlspecialchars($msg['subject']) ?></td>
                                     <td><span class="status-badge status-<?= strtolower($msg['status']) ?>"><?= ucfirst($msg['status']) ?></span></td>
                                     <td>
                                         <a href="view_message.php?id=<?= $msg['id'] ?>" class="action-link" title="View/Reply"><i class="fas fa-eye"></i> View</a>
                                         <a href="update_message_status.php?id=<?= $msg['id'] ?>&status=read" class="action-link" title="Mark as Read"><i class="fas fa-check"></i></a>
                                         <a href="update_message_status.php?id=<?= $msg['id'] ?>&status=spam" class="action-link delete" title="Mark as Spam"><i class="fas fa-ban"></i></a>
                                     </td>
                                 </tr>
                             <?php endforeach; ?>
                         <?php else: ?>
                             <tr><td colspan="5">No contact messages found.</td></tr>
                         <?php endif; ?>
                     </tbody>
                 </table>
             </div>
             </div>


    </div> <?php include 'footer.php'; // Include a common footer if you have one ?>

</body>
</html>
