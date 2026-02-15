<?php
require_once __DIR__ . '/config.php';
// Allow Staff, Trainer, Manager, or Admin access
require_role(['staff', 'trainer', 'manager', 'admin']);

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_name = $_SESSION['name'] ?? 'Staff Member';

$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']); // Clear messages

// --- Fetch Data Relevant to Staff/Trainer/Manager ---
$assignedClasses = [];
$allClasses = [];
$trainers = [];
$contactMessages = [];

try {
    // Fetch classes assigned to this trainer (if role is trainer)
    if ($user_role === 'trainer') {
        $stmt_assigned = $pdo->prepare("SELECT * FROM classes WHERE trainer_id = ? AND status = 'upcoming' ORDER BY start_time ASC");
        $stmt_assigned->execute([$user_id]);
        $assignedClasses = $stmt_assigned->fetchAll();
    }

    // Fetch all upcoming classes (for staff/manager to view/manage)
    // Admins might see this too, but have more options on admin_dashboard
    if (in_array($user_role, ['staff', 'manager', 'admin'])) {
         $stmt_all = $pdo->prepare("
            SELECT c.*, u.name as trainer_name
            FROM classes c
            LEFT JOIN users u ON c.trainer_id = u.id AND u.role = 'trainer'
            WHERE c.status = 'upcoming' -- Or filter as needed
            ORDER BY c.start_time ASC
            LIMIT 10 -- Limit for dashboard view
        ");
        $stmt_all->execute();
        $allClasses = $stmt_all->fetchAll();

         // Fetch trainers for the "Add Class" form
         $stmt_trainers = $pdo->query("SELECT id, name FROM users WHERE role = 'trainer' AND is_active = TRUE ORDER BY name");
         $trainers = $stmt_trainers->fetchAll();
    }

     // Fetch 'new' or 'read' contact messages (for staff/manager/admin)
     if (in_array($user_role, ['staff', 'manager', 'admin'])) {
        $stmt_msgs = $pdo->prepare("SELECT * FROM contact_submissions WHERE status IN ('new', 'read') ORDER BY submission_date DESC LIMIT 10");
        $stmt_msgs->execute();
        $contactMessages = $stmt_msgs->fetchAll();
     }


} catch (PDOException $e) {
    $dashboard_error = "Database error fetching dashboard data: " . $e->getMessage();
    error_log($dashboard_error);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - FitZone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Re-use styles from admin_dashboard.php, slightly adapted */
         :root {
            --primary-color: #00b4d8; /* Staff theme color */
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
        .dashboard-container { max-width: 1200px; margin: 1.5rem auto; padding: 0 1.5rem; }

        /* Welcome */
        .welcome-section { margin-bottom: 1.5rem; background: var(--white); padding: 1.5rem; border-radius: 8px; box-shadow: var(--card-shadow); }
        .welcome-text h1 { font-size: 1.5rem; margin: 0 0 0.25rem 0; font-weight: 600; color: var(--dark-color); }
        .welcome-text p { color: var(--muted-color); margin: 0; font-size: 0.9rem; }

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
        .data-table td:last-child { white-space: nowrap; }

         /* Status Badges & Actions */
         .status-badge { padding: 0.25rem 0.6rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
         .status-new { background-color: #fff3cd; color: #856404; }
         .status-read { background-color: #d1ecf1; color: #0c5460; }
         .status-replied { background-color: #d4edda; color: #155724; }
         .status-spam { background-color: #f8d7da; color: #721c24; }
         .status-upcoming { background-color: #cfe2ff; color: #0a58ca; }
         .action-link { color: var(--primary-color); text-decoration: none; margin-right: 0.75rem; font-weight: 500; }
         .action-link:hover { text-decoration: underline; color: var(--primary-dark); }
         .action-link.delete { color: var(--danger-color); }

         /* Forms */
        .form-section { margin-bottom: 1.5rem; padding: 1.5rem; border: 1px solid var(--border-color); border-radius: 5px; background-color: var(--light-color); }
        .form-section h3 { font-size: 1rem; font-weight: 600; margin: 0 0 1rem 0; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.3rem; font-weight: 500; font-size: 0.9rem; color: #495057; }
        .form-control { width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-color); border-radius: 4px; font-size: 0.95rem; font-family: inherit; }
        .form-control:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(0, 180, 216, 0.2); }
        textarea.form-control { min-height: 60px; }
        .input-group { display: flex; gap: 1rem; }
        .input-group > .form-group { flex: 1; }

         /* Buttons */
        .btn { display: inline-block; padding: 0.6rem 1.2rem; border: none; border-radius: 50px; cursor: pointer; font-size: 0.9rem; font-weight: 500; text-decoration: none; transition: all 0.3s; text-align: center; }
        .btn-primary { background-color: var(--primary-color); color: var(--white); }
        .btn-primary:hover { background-color: var(--primary-dark); transform: translateY(-1px); }
        .btn-secondary { background-color: var(--secondary-color); color: var(--white); }
        .btn-secondary:hover { background-color: #5a6268; transform: translateY(-1px); }

         /* Alerts */
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; border: 1px solid transparent; font-size: 0.95rem; }
        .alert-success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }

         /* Layout */
        .grid-container { display: grid; gap: 1.5rem; }
        .grid-col-2 { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); } /* Responsive grid */

         /* Responsive */
        @media (max-width: 768px) {
            .dashboard-header { padding: 0.8rem 1rem; }
            .logo { font-size: 1.4rem; }
            .dashboard-container { margin: 1rem auto; padding: 0 0.75rem; }
            .dashboard-card { padding: 1rem; }
            .dashboard-card h2 { font-size: 1.1rem; }
            .data-table { font-size: 0.85rem; }
            .data-table th, .data-table td { padding: 0.6rem; }
            .btn { width: 100%; margin-bottom: 0.5rem; }
            .action-link { margin-right: 0.5rem; }
            .input-group { flex-direction: column; gap: 0; }
            .input-group > .form-group { margin-bottom: 1rem; }
        }

    </style>
</head>
<body>
    <header class="dashboard-header">
        <a href="index.php" class="logo"><i class="fas fa-dumbbell"></i> Fit<span>Zone</span> Staff</a>
        <div class="user-nav">
            <span><i class="fas fa-user"></i> <?= htmlspecialchars($user_name) ?> (<?= ucfirst($user_role) ?>)</span>
             <a href="profile.php" class="btn btn-sm btn-secondary" title="Profile"><i class="fas fa-user-edit"></i></a>
            <a href="logout.php" class="logout-btn" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
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
                <h1>Staff Dashboard</h1>
                <p>Manage classes and member interactions.</p>
            </div>
            <a href="#class-management" class="btn btn-primary"><i class="fas fa-plus"></i> Add Class</a>
        </div>

         <div class="grid-container grid-col-2">

             <?php if (in_array($user_role, ['staff', 'manager', 'admin'])): ?>
             <div class="dashboard-card" id="class-management">
                <h2><i class="fas fa-chalkboard-teacher"></i> Class Management</h2>
                <div class="grid-container grid-col-2">
                    <div class="form-section">
                         <h3><i class="fas fa-plus-circle"></i> Add/Edit Class</h3>
                         <p><small>Use this form to add a new class or select a class below to edit.</small></p>
                         <form method="POST" action="edit_class.php"> <div class="form-group">
                                <label for="title_add">Class Title</label>
                                <input type="text" id="title_add" name="title" class="form-control" required>
                            </div>
                             <div class="form-group">
                                <label for="trainer_id_add">Trainer</label>
                                <select id="trainer_id_add" name="trainer_id" class="form-control">
                                    <option value="">-- Select --</option>
                                    <?php foreach ($trainers as $trainer): ?>
                                        <option value="<?= $trainer['id'] ?>"><?= htmlspecialchars($trainer['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                             <a href="edit_class.php" class="btn btn-primary">Add Full Class Details</a>
                         </form>
                    </div>

                    <div class="class-list-section">
                         <h3><i class="fas fa-list-ul"></i> Upcoming Classes</h3>
                         <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr><th>Title</th><th>Trainer</th><th>Time</th><th>Actions</th></tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($allClasses)): ?>
                                        <?php foreach ($allClasses as $class): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($class['title']) ?></td>
                                                <td><?= htmlspecialchars($class['trainer_name'] ?? 'N/A') ?></td>
                                                <td><?= date('M j, g:i A', strtotime($class['start_time'])) ?></td>
                                                <td>
                                                    <a href="edit_class.php?id=<?= $class['id'] ?>" class="action-link" title="Edit"><i class="fas fa-edit"></i></a>
                                                    <a href="delete_class.php?id=<?= $class['id'] ?>" class="action-link delete" title="Delete" onclick="return confirm('Are you sure?')"><i class="fas fa-trash-alt"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                         <tr><td colspan="4">No upcoming classes found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                         </div>
                    </div>
                </div>
             </div>
             <?php endif; ?>

             <?php if ($user_role === 'trainer'): ?>
             <div class="dashboard-card" id="assigned-classes">
                 <h2><i class="fas fa-calendar-check"></i> Your Upcoming Classes</h2>
                 <div class="table-responsive">
                     <table class="data-table">
                         <thead>
                             <tr><th>Title</th><th>Date & Time</th><th>Duration</th><th>Capacity</th></tr>
                         </thead>
                         <tbody>
                             <?php if (!empty($assignedClasses)): ?>
                                 <?php foreach ($assignedClasses as $class): ?>
                                     <tr>
                                         <td><?= htmlspecialchars($class['title']) ?></td>
                                         <td><?= date('M j, Y @ g:i A', strtotime($class['start_time'])) ?></td>
                                         <td><?= htmlspecialchars($class['duration'] ?? 'N/A') ?> min</td>
                                         <td><?= htmlspecialchars($class['capacity'] ?? 'N/A') ?></td>
                                         </tr>
                                 <?php endforeach; ?>
                             <?php else: ?>
                                 <tr><td colspan="4">You have no upcoming classes assigned.</td></tr>
                             <?php endif; ?>
                         </tbody>
                     </table>
                 </div>
             </div>
             <?php endif; ?>


             <?php if (in_array($user_role, ['staff', 'manager', 'admin'])): ?>
             <div class="dashboard-card" id="message-center">
                 <h2><i class="fas fa-envelope-open-text"></i> Recent Contact Messages</h2>
                 <div class="table-responsive">
                     <table class="data-table">
                         <thead>
                             <tr><th>Received</th><th>From</th><th>Subject</th><th>Status</th><th>Actions</th></tr>
                         </thead>
                         <tbody>
                             <?php if (!empty($contactMessages)): ?>
                                 <?php foreach ($contactMessages as $msg): ?>
                                     <tr>
                                         <td><?= date('M j, H:i', strtotime($msg['submission_date'])) ?></td>
                                         <td><?= htmlspecialchars($msg['name']) ?></td>
                                         <td><?= htmlspecialchars($msg['subject']) ?></td>
                                         <td><span class="status-badge status-<?= strtolower($msg['status']) ?>"><?= ucfirst($msg['status']) ?></span></td>
                                         <td>
                                             <a href="view_message.php?id=<?= $msg['id'] ?>" class="action-link" title="View/Reply"><i class="fas fa-eye"></i></a>
                                             <a href="update_message_status.php?id=<?= $msg['id'] ?>&status=read" class="action-link" title="Mark Read"><i class="fas fa-check"></i></a>
                                             <a href="update_message_status.php?id=<?= $msg['id'] ?>&status=spam" class="action-link delete" title="Mark Spam"><i class="fas fa-ban"></i></a>
                                         </td>
                                     </tr>
                                 <?php endforeach; ?>
                            <?php else: ?>
                                 <tr><td colspan="5">No new messages found.</td></tr>
                            <?php endif; ?>
                         </tbody>
                     </table>
                 </div>
                 </div>
             <?php endif; ?>

         </div> </div> <?php include 'footer.php'; // Include a common footer if you have one ?>

</body>
</html>
