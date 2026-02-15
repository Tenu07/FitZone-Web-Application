<?php
require 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get user's membership if exists
$stmt = $pdo->prepare("SELECT p.name, p.price, m.start_date, m.end_date 
                      FROM memberships m 
                      JOIN plans p ON m.plan_id = p.id 
                      WHERE m.user_id = ? AND m.end_date >= CURDATE() 
                      ORDER BY m.end_date DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$membership = $stmt->fetch();

// Get upcoming appointments - UPDATED QUERY TO JOIN WITH USERS TABLE
$stmt = $pdo->prepare("SELECT a.id, a.date, u.name as trainer_name, t.specialization 
                      FROM appointments a 
                      JOIN trainers t ON a.trainer_id = t.id 
                      JOIN users u ON t.user_id = u.id
                      WHERE a.user_id = ? AND a.date >= NOW() 
                      ORDER BY a.date ASC LIMIT 3");
$stmt->execute([$_SESSION['user_id']]);
$appointments = $stmt->fetchAll();

// Handle appointment cancellation
if (isset($_POST['cancel_appointment'])) {
    $appointment_id = $_POST['appointment_id'];
    $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$appointment_id, $_SESSION['user_id']])) {
        header("Location: dashboard.php?cancel_success=1");
        exit;
    }
}

// Get workout stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total_workouts, 
                      MAX(date) as last_workout 
                      FROM workouts 
                      WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$workout_stats = $stmt->fetch();

// Get recent notifications
$stmt = $pdo->prepare("SELECT * FROM notifications 
                      WHERE user_id = ? 
                      ORDER BY created_at DESC 
                      LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FitZone Fitness Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #00b4d8;
            --secondary-color: #1a1a1a;
            --danger-color: #d9534f;
            --success-color: #5cb85c;
            --warning-color: #f0ad4e;
            --light-bg: #f8f9fa;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .dashboard-header {
            background-color: var(--secondary-color);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .logo {
            color: #fff;
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
        }
        
        .logo span {
            color: var(--primary-color);
        }
        
        .user-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 0.5rem;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        .logout-btn {
            color: white;
            text-decoration: none;
            background-color: var(--danger-color);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .logout-btn:hover {
            background-color: #c9302c;
        }
        
        .dashboard-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .welcome-section {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .welcome-text h1 {
            margin: 0;
            color: var(--secondary-color);
        }
        
        .welcome-text p {
            margin: 0.5rem 0 0 0;
            color: #666;
        }
        
        .quick-stats {
            display: flex;
            gap: 1.5rem;
        }
        
        .stat-card {
            background-color: var(--light-bg);
            padding: 1rem 1.5rem;
            border-radius: 8px;
            text-align: center;
            min-width: 120px;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin: 0.3rem 0 0 0;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .dashboard-card {
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }
        
        .card-header h3 {
            color: var(--primary-color);
            margin: 0;
        }
        
        .card-header a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .membership-info {
            margin-top: 1rem;
        }
        
        .membership-info p {
            margin: 0.5rem 0;
        }
        
        .membership-status {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .active {
            background-color: #dff0d8;
            color: #3c763d;
        }
        
        .expired {
            background-color: #f2dede;
            color: #a94442;
        }
        
        .appointment-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .appointment-item {
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .appointment-item:last-child {
            border-bottom: none;
        }
        
        .appointment-details {
            flex: 1;
        }
        
        .appointment-date {
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.3rem;
        }
        
        .appointment-trainer {
            color: #666;
            font-size: 0.9rem;
        }
        
        .appointment-actions {
            margin-left: 1rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 0.9rem;
        }
        
        .btn:hover {
            background-color: #0096b4;
        }
        
        .btn-sm {
            padding: 0.3rem 0.7rem;
            font-size: 0.8rem;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: #c9302c;
        }
        
        .btn-success {
            background-color: var(--success-color);
        }
        
        .btn-success:hover {
            background-color: #449d44;
        }
        
        .notification-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .notification-item {
            padding: 0.8rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-message {
            margin: 0;
            font-size: 0.9rem;
        }
        
        .notification-time {
            font-size: 0.8rem;
            color: #999;
            margin: 0.3rem 0 0 0;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .action-card {
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
        }
        
        .action-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .action-title {
            margin: 0 0 0.5rem 0;
            color: var(--secondary-color);
        }
        
        .action-desc {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-stats {
                flex-wrap: wrap;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 0 1rem;
            }
            
            .welcome-section {
                flex-direction: column;
                align-items: flex-start;
                gap: 1.5rem;
            }
            
            .quick-stats {
                width: 100%;
                justify-content: space-between;
            }
            
            .stat-card {
                flex: 1;
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <header class="dashboard-header">
        <a href="index.php" class="logo">Fit<span>Zone</span></a>
        <div class="user-nav">
            <a href="profile.php" class="nav-link"><i class="fas fa-user"></i> Profile</a>
            <a href="notifications.php" class="nav-link"><i class="fas fa-bell"></i></a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="dashboard-container">
        <?php if (isset($_GET['cancel_success'])): ?>
            <div class="alert alert-success">
                Appointment cancelled successfully!
            </div>
        <?php endif; ?>
        
        <div class="welcome-section">
            <div class="welcome-text">
                <?php if ($user): ?>
                    <h1>Welcome back, <?= htmlspecialchars($user['name']) ?>!</h1>
                    <p>You're logged in as <?= ucfirst(htmlspecialchars($user['role'])) ?> member</p>
                <?php else: ?>
                    <h1>Welcome back!</h1>
                    <p>Unable to retrieve user information.</p>
                <?php endif; ?>
            </div>
            <div class="quick-stats">
                <div class="stat-card">
                    <p class="stat-value"><?= $workout_stats ? $workout_stats['total_workouts'] : '0' ?></p>
                    <p class="stat-label">Workouts</p>
                </div>
                <div class="stat-card">
                    <p class="stat-value"><?= $appointments ? count($appointments) : '0' ?></p>
                    <p class="stat-label">Upcoming Sessions</p>
                </div>
                <div class="stat-card">
                    <p class="stat-value">
                        <?= $workout_stats && $workout_stats['last_workout'] ? 
                            date('M j', strtotime($workout_stats['last_workout'])) : 'N/A' ?>
                    </p>
                    <p class="stat-label">Last Workout</p>
                </div>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-id-card"></i> Your Membership</h3>
                    <a href="memberships.php">View Details</a>
                </div>
                <?php if ($membership): ?>
                    <div class="membership-info">
                        <p><strong>Plan:</strong> <?= htmlspecialchars($membership['name']) ?></p>
                        <p><strong>Price:</strong> Rs. <?= number_format($membership['price'], 2) ?>/month</p>
                        <p><strong>Start Date:</strong> <?= date('F j, Y', strtotime($membership['start_date'])) ?></p>
                        <p><strong>End Date:</strong> <?= date('F j, Y', strtotime($membership['end_date'])) ?></p>
                        <span class="membership-status active">Active</span>
                    </div>
                <?php else: ?>
                    <p>You don't have an active membership.</p>
                    <a href="membership_plans.php" class="btn">View Membership Plans</a>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-alt"></i> Upcoming Appointments</h3>
                    <a href="appointments.php">View All</a>
                </div>
                <?php if ($appointments): ?>
                    <ul class="appointment-list">
                        <?php foreach ($appointments as $appointment): ?>
                            <li class="appointment-item">
                                <div class="appointment-details">
                                    <p class="appointment-date">
                                        <?= date('D, M j, Y \a\t g:i A', strtotime($appointment['date'])) ?>
                                    </p>
                                    <p class="appointment-trainer">
                                        With <?= htmlspecialchars($appointment['trainer_name']) ?> 
                                        (<?= htmlspecialchars($appointment['specialization']) ?>)
                                    </p>
                                </div>
                                <div class="appointment-actions">
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
                    <p>You don't have any upcoming appointments.</p>
                <?php endif; ?>
                <a href="book_appointment.php" class="btn">Book New Appointment</a>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-bell"></i> Notifications</h3>
                    <a href="notifications.php">View All</a>
                </div>
                <?php if ($notifications): ?>
                    <ul class="notification-list">
                        <?php foreach ($notifications as $notification): ?>
                            <li class="notification-item">
                                <p class="notification-message"><?= htmlspecialchars($notification['message']) ?></p>
                                <p class="notification-time">
                                    <?= date('M j, g:i A', strtotime($notification['created_at'])) ?>
                                </p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>You don't have any notifications.</p>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> Fitness Progress</h3>
                    <a href="progress.php">View Details</a>
                </div>
                <div style="text-align: center; padding: 1rem 0;">
                    <img src="images/progress-chart.gif" alt="Progress Chart" style="max-width: 20%; height: auto;">
                    <p>Track your workout progress and achievements</p>
                    <a href="log_workout.php" class="btn btn-success">Log Workout</a>
                </div>
            </div>
        </div>
        
        <div class="quick-actions">
            <a href="update_profile.php" class="action-card">
                <div class="action-icon"><i class="fas fa-user-edit"></i></div>
                <h4 class="action-title">Update Profile</h4>
                <p class="action-desc">Edit your personal information</p>
            </a>
            
            <a href="change_password.php" class="action-card">
                <div class="action-icon"><i class="fas fa-lock"></i></div>
                <h4 class="action-title">Change Password</h4>
                <p class="action-desc">Update your account security</p>
            </a>
            
            <a href="classes.php" class="action-card">
                <div class="action-icon"><i class="fas fa-dumbbell"></i></div>
                <h4 class="action-title">View Classes</h4>
                <p class="action-desc">Browse our fitness classes</p>
            </a>
            
            <a href="trainers.php" class="action-card">
                <div class="action-icon"><i class="fas fa-users"></i></div>
                <h4 class="action-title">Our Trainers</h4>
                <p class="action-desc">Meet our expert trainers</p>
            </a>
        </div>
    </div>
</body>
</html>