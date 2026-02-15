<?php
require_once __DIR__ . '/config.php'; // Use require_once
require_role('customer'); // Only allow 'customer' role

// Get user data (already required by require_role)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get user's current active membership
$membership = null; // Initialize
try {
    $stmt = $pdo->prepare("SELECT p.name, p.price, m.start_date, m.end_date, m.status
                          FROM memberships m
                          JOIN plans p ON m.plan_id = p.id
                          WHERE m.user_id = ? AND m.status = 'active' AND m.end_date >= CURDATE()
                          ORDER BY m.end_date DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $membership = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Error fetching membership: " . $e->getMessage());
    // Handle error appropriately, maybe show a message
}


// Get upcoming appointments
$appointments = []; // Initialize
try {
    // This query assumes 'trainer_id' in 'appointments' links directly to 'users' table ID where role='trainer'
    // Adjust JOIN if your 'appointments.trainer_id' links to a different table or column
    $stmt = $pdo->prepare("SELECT a.id, a.date, u_trainer.name as trainer_name, u_trainer.position as trainer_position
                          FROM appointments a
                          JOIN users u_trainer ON a.trainer_id = u_trainer.id -- Assuming trainer_id links to users.id
                          WHERE a.user_id = ? AND a.date >= NOW() AND a.status = 'scheduled' -- Only show scheduled
                          ORDER BY a.date ASC LIMIT 3");
    $stmt->execute([$_SESSION['user_id']]);
    $appointments = $stmt->fetchAll();
} catch (PDOException $e) {
     error_log("Error fetching appointments: " . $e->getMessage());
    // Handle error appropriately
}


// Handle appointment cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    $appointment_id = $_POST['appointment_id'];
    // Add validation: Ensure the appointment belongs to the logged-in user
    $checkStmt = $pdo->prepare("SELECT id FROM appointments WHERE id = ? AND user_id = ? AND status = 'scheduled'");
    $checkStmt->execute([$appointment_id, $_SESSION['user_id']]);
    if ($checkStmt->fetch()) {
        // Update status to 'cancelled' instead of deleting
        $updateStmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled', cancellation_reason = 'Cancelled by member' WHERE id = ?");
        if ($updateStmt->execute([$appointment_id])) {
             // Add notification for cancellation
             try {
                 $pdo->prepare("INSERT INTO notifications (user_id, title, message, notification_type, related_id) VALUES (?, ?, ?, 'appointment', ?)")
                     ->execute([$_SESSION['user_id'], 'Appointment Cancelled', 'Your appointment has been cancelled.', $appointment_id]);
             } catch (PDOException $e) {
                 error_log("Failed to create cancellation notification: " . $e->getMessage());
             }
            header("Location: member_dashboard.php?cancel_success=1");
            exit;
        } else {
             header("Location: member_dashboard.php?cancel_error=1");
             exit;
        }
    } else {
        // Appointment not found, doesn't belong to user, or not cancellable
        header("Location: member_dashboard.php?cancel_error=1");
        exit;
    }
}

// Get workout stats
$workout_stats = null; // Initialize
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_workouts,
                          MAX(date) as last_workout
                          FROM workouts
                          WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $workout_stats = $stmt->fetch();
} catch (PDOException $e) {
     error_log("Error fetching workout stats: " . $e->getMessage());
    // Handle error appropriately
}

// Get recent notifications
$notifications = []; // Initialize
try {
    $stmt = $pdo->prepare("SELECT * FROM notifications
                          WHERE user_id = ? AND is_read = FALSE -- Show unread first? or just recent?
                          ORDER BY created_at DESC
                          LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll();
     // Optionally mark these as read now or when they click 'View All'
} catch (PDOException $e) {
     error_log("Error fetching notifications: " . $e->getMessage());
    // Handle error appropriately
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - FitZone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Copied and adapted styles from original dashboard.php */
        :root {
            --primary-color: #00b4d8;
            --primary-dark: #0096b4;
            --secondary-color: #1a1a1a;
            --danger-color: #d9534f;
            --success-color: #5cb85c;
            --warning-color: #f0ad4e;
            --light-bg: #f8f9fa;
            --text-color: #333;
            --muted-color: #6c757d;
            --border-color: #dee2e6;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa; /* Lighter background */
            color: var(--text-color);
             line-height: 1.6;
        }

        .dashboard-header {
            background-color: var(--secondary-color);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky; /* Make header sticky */
            top: 0;
            z-index: 1000;
        }
         .logo { color: #fff; font-size: 1.5rem; font-weight: 700; text-decoration: none; display: flex; align-items: center; }
         .logo i { margin-right: 0.5rem; color: var(--primary-color); }
         .logo span { color: var(--primary-color); }

        .user-nav { display: flex; align-items: center; gap: 1rem; }
        .nav-link { color: white; text-decoration: none; padding: 0.5rem; border-radius: 5px; transition: background-color 0.3s; display: flex; align-items: center; gap: 0.5rem; font-size: 0.95rem; }
        .nav-link i { font-size: 1.1rem; }
        .nav-link:hover { background-color: rgba(255,255,255,0.1); }

         .logout-btn { display: inline-flex; align-items: center; gap: 0.5rem; color: white; text-decoration: none; background-color: var(--danger-color); padding: 0.5rem 1rem; border-radius: 50px; transition: background-color 0.3s; font-size: 0.9rem; font-weight: 500; }
         .logout-btn:hover { background-color: #c9302c; }

        .dashboard-container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }

        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 180, 216, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .welcome-text h1 { margin: 0; font-size: 1.8rem; font-weight: 600; }
        .welcome-text p { margin: 0.3rem 0 0 0; opacity: 0.9; font-size: 1rem; }

        .quick-stats { display: flex; gap: 1rem; flex-wrap: wrap; }
        .stat-card { background-color: rgba(255, 255, 255, 0.2); padding: 1rem; border-radius: 8px; text-align: center; min-width: 100px; transition: background-color 0.3s; }
        .stat-card:hover { background-color: rgba(255, 255, 255, 0.3); }
        .stat-value { font-size: 1.6rem; font-weight: 700; margin: 0; }
        .stat-label { font-size: 0.8rem; margin: 0.2rem 0 0 0; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px; }

        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem; }

        .dashboard-card { background-color: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 3px 10px rgba(0,0,0,0.05); transition: box-shadow 0.3s; }
        .dashboard-card:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color); }
        .card-header h3 { color: var(--secondary-color); margin: 0; font-size: 1.1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
        .card-header h3 i { color: var(--primary-color); }
        .card-header a { color: var(--primary-color); text-decoration: none; font-size: 0.85rem; font-weight: 500; }
        .card-header a:hover { text-decoration: underline; }

        .membership-info p { margin: 0.6rem 0; font-size: 0.95rem; }
        .membership-info strong { color: var(--secondary-color); margin-right: 0.5rem; }
        .membership-status { display: inline-block; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-active { background-color: #e6f7ee; color: #3c763d; }
        .status-expired { background-color: #fde8e8; color: var(--danger-color); }
        .no-membership { color: var(--muted-color); font-style: italic; }

        .list { list-style: none; padding: 0; margin: 0; }
        .list-item { padding: 0.9rem 0; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
        .list-item:last-child { border-bottom: none; }
        .item-details { flex-grow: 1; }
        .item-title { font-weight: 600; color: var(--primary-color); margin-bottom: 0.2rem; font-size: 1rem; }
        .item-subtitle { color: var(--muted-color); font-size: 0.85rem; }
        .item-actions { flex-shrink: 0; }

         .btn { display: inline-block; padding: 0.7rem 1.5rem; background-color: var(--primary-color); color: white; text-decoration: none; border-radius: 50px; border: none; cursor: pointer; transition: all 0.3s; font-size: 0.9rem; font-weight: 500; text-align: center; }
         .btn:hover { background-color: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0, 180, 216, 0.3); }
         .btn-block { display: block; width: 100%; margin-top: 1rem; }
         .btn-outline { background-color: transparent; color: var(--primary-color); border: 1px solid var(--primary-color); }
         .btn-outline:hover { background-color: var(--primary-color); color: white; }
         .btn-sm { padding: 0.4rem 1rem; font-size: 0.8rem; }
         .btn-danger { background-color: var(--danger-color); border-color: var(--danger-color); }
         .btn-danger:hover { background-color: #c9302c; border-color: #c9302c; box-shadow: 0 4px 8px rgba(217, 83, 79, 0.3); }
         .btn-success { background-color: var(--success-color); border-color: var(--success-color); }
         .btn-success:hover { background-color: #4cae4c; border-color: #4cae4c; box-shadow: 0 4px 8px rgba(92, 184, 92, 0.3); }

         .notification-message { margin: 0; font-size: 0.9rem; color: var(--text-color); }
         .notification-time { font-size: 0.75rem; color: var(--muted-color); margin: 0.2rem 0 0 0; }

         .quick-actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-top: 2rem; }
         .action-link-card { display: block; background-color: white; padding: 1.5rem 1rem; border-radius: 12px; box-shadow: 0 3px 10px rgba(0,0,0,0.05); text-align: center; transition: all 0.3s; text-decoration: none; color: inherit; }
         .action-link-card:hover { transform: translateY(-5px); box-shadow: 0 6px 15px rgba(0,0,0,0.1); }
         .action-icon { font-size: 1.8rem; color: var(--primary-color); margin-bottom: 0.8rem; display: block; }
         .action-title { margin: 0; font-size: 0.95rem; font-weight: 600; color: var(--secondary-color); }

         .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid transparent; display: flex; align-items: center; gap: 0.75rem; }
         .alert i { font-size: 1.2rem; }
         .alert-success { background-color: #e6f7ee; color: #3c763d; border-color: #c3e6cb; }
         .alert-danger { background-color: #fde8e8; color: var(--danger-color); border-color: #f5c6cb; }

         /* Responsive */
         @media (max-width: 992px) {
             .quick-stats { justify-content: center; }
         }
         @media (max-width: 768px) {
             .dashboard-container { padding: 0 0.75rem; margin-top: 1rem; }
             .welcome-section { flex-direction: column; align-items: flex-start; text-align: center; }
             .welcome-text h1 { font-size: 1.6rem; }
             .quick-stats { gap: 0.5rem; }
             .stat-card { padding: 0.8rem; min-width: 80px; }
             .stat-value { font-size: 1.4rem; }
             .dashboard-grid { grid-template-columns: 1fr; gap: 1rem; }
             .dashboard-card { padding: 1rem; }
             .card-header h3 { font-size: 1rem; }
             .list-item { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
             .item-actions { margin-top: 0.5rem; width: 100%; }
             .btn { width: 100%; }
             .btn-sm { width: auto; } /* Keep small buttons inline */
             .btn-danger { width: auto; } /* Keep cancel button inline */
             .quick-actions-grid { grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0.75rem; }
             .action-link-card { padding: 1rem 0.5rem; }
             .action-icon { font-size: 1.5rem; margin-bottom: 0.5rem; }
             .action-title { font-size: 0.85rem; }
         }

    </style>
</head>
<body>
    <header class="dashboard-header">
        <a href="index.php" class="logo"><i class="fas fa-dumbbell"></i>Fit<span>Zone</span></a>
        <div class="user-nav">
            <a href="member_dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="profile.php" class="nav-link"><i class="fas fa-user"></i> Profile</a>
            <a href="notifications.php" class="nav-link"><i class="fas fa-bell"></i></a>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <div class="dashboard-container">
        <?php if (isset($_GET['cancel_success'])): ?>
            <div class="alert alert-success">
                 <i class="fas fa-check-circle"></i> Appointment cancelled successfully!
            </div>
        <?php elseif (isset($_GET['cancel_error'])): ?>
             <div class="alert alert-danger">
                 <i class="fas fa-exclamation-circle"></i> Could not cancel the appointment. Please try again or contact support.
            </div>
        <?php elseif (isset($_GET['booking_success'])): ?>
             <div class="alert alert-success">
                 <i class="fas fa-calendar-check"></i> Appointment booked successfully!
            </div>
         <?php elseif (isset($_GET['workout_logged'])): ?>
             <div class="alert alert-success">
                 <i class="fas fa-dumbbell"></i> Workout logged successfully!
            </div>
          <?php elseif (isset($_GET['membership_success'])): ?>
             <div class="alert alert-success">
                 <i class="fas fa-star"></i> Membership activated successfully! Welcome aboard!
            </div>
        <?php endif; ?>

        <div class="welcome-section">
            <div class="welcome-text">
                <h1>Welcome, <?= htmlspecialchars($user['name'] ?? 'Member') ?>!</h1>
                <p>Ready to crush your fitness goals today?</p>
            </div>
             <div class="quick-stats">
                 <div class="stat-card">
                     <p class="stat-value"><?= $workout_stats ? number_format((float)($workout_stats['total_workouts'] ?? 0)) : '0' ?></p>
                     <p class="stat-label">Workouts Logged</p>
                 </div>
                 <div class="stat-card">
                    <p class="stat-value"><?= $appointments ? count($appointments) : '0' ?></p>
                    <p class="stat-label">Upcoming</p>
                 </div>
                 <div class="stat-card">
                     <p class="stat-value">
                         <?= $membership ? max(0, floor((strtotime($membership['end_date']) - time()) / (60 * 60 * 24))) : '0' ?>
                     </p>
                     <p class="stat-label">Days Left</p>
                 </div>
             </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-id-card"></i> Membership Status</h3>
                    <a href="memberships.php">Manage</a>
                </div>
                <?php if ($membership): ?>
                    <div class="membership-info">
                        <p><strong>Plan:</strong> <?= htmlspecialchars($membership['name']) ?></p>
                        <p><strong>Status:</strong> <span class="membership-status status-active"><?= ucfirst($membership['status']) ?></span></p>
                        <p><strong>Expires:</strong> <?= date('F j, Y', strtotime($membership['end_date'])) ?></p>
                    </div>
                    <a href="membership_plans.php" class="btn btn-outline btn-block">View/Upgrade Plans</a>
                <?php else: ?>
                    <p class="no-membership">You don't have an active membership.</p>
                    <a href="membership_plans.php" class="btn btn-success btn-block">Choose a Plan</a>
                <?php endif; ?>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-check"></i> Upcoming Sessions</h3>
                    <a href="appointments.php">View All</a>
                </div>
                <?php if ($appointments): ?>
                    <ul class="list">
                        <?php foreach ($appointments as $appointment): ?>
                            <li class="list-item">
                                <div class="item-details">
                                    <p class="item-title">
                                        <?= date('D, M j \a\t g:i A', strtotime($appointment['date'])) ?>
                                    </p>
                                    <p class="item-subtitle">
                                        With <?= htmlspecialchars($appointment['trainer_name']) ?>
                                         (<?= htmlspecialchars($appointment['trainer_position'] ?? 'Trainer') ?>)
                                    </p>
                                </div>
                                <div class="item-actions">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                        <button type="submit" name="cancel_appointment" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                            Cancel
                                        </button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No upcoming appointments.</p>
                <?php endif; ?>
                 <a href="book_appointment.php" class="btn btn-block">Book a Session</a>
            </div>

             <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-bell"></i> Recent Notifications</h3>
                    <a href="notifications.php">View All</a>
                </div>
                <?php if ($notifications): ?>
                    <ul class="list">
                        <?php foreach ($notifications as $notification): ?>
                            <li class="list-item" style="align-items: flex-start;">
                               <i class="fas fa-info-circle" style="color: var(--primary-color); margin-top: 0.1rem; font-size: 0.9rem;"></i>
                               <div class="item-details">
                                    <p class="notification-message"><?= htmlspecialchars($notification['message']) ?></p>
                                    <p class="notification-time">
                                        <?= date('M j, g:i A', strtotime($notification['created_at'])) ?>
                                    </p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No new notifications.</p>
                <?php endif; ?>
            </div>

             <div class="dashboard-card">
                 <div class="card-header">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                 </div>
                 <div class="quick-actions-grid">
                     <a href="log_workout.php" class="action-link-card">
                         <span class="action-icon"><i class="fas fa-dumbbell"></i></span>
                         <h4 class="action-title">Log Workout</h4>
                     </a>
                     <a href="classes.php" class="action-link-card">
                         <span class="action-icon"><i class="fas fa-calendar-alt"></i></span>
                         <h4 class="action-title">View Classes</h4>
                     </a>
                      <a href="progress.php" class="action-link-card">
                         <span class="action-icon"><i class="fas fa-chart-line"></i></span>
                         <h4 class="action-title">Track Progress</h4>
                     </a>
                     <a href="trainers.php" class="action-link-card">
                         <span class="action-icon"><i class="fas fa-users"></i></span>
                         <h4 class="action-title">Find Trainers</h4>
                     </a>
                     <a href="update_profile.php" class="action-link-card">
                         <span class="action-icon"><i class="fas fa-user-edit"></i></span>
                         <h4 class="action-title">Edit Profile</h4>
                     </a>
                     <a href="change_password.php" class="action-link-card">
                         <span class="action-icon"><i class="fas fa-lock"></i></span>
                         <h4 class="action-title">Change Password</h4>
                     </a>
                 </div>
            </div>

        </div> </div> <?php include 'footer.php'; // Include a common footer if you have one ?>

</body>
</html>