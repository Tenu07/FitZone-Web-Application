<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get all appointments
$stmt = $pdo->prepare("SELECT a.id, a.date, u.name as trainer_name, t.specialization, a.status 
                      FROM appointments a 
                      JOIN trainers t ON a.trainer_id = t.id 
                      JOIN users u ON t.user_id = u.id
                      WHERE a.user_id = ? 
                      ORDER BY a.date DESC");
$stmt->execute([$_SESSION['user_id']]);
$appointments = $stmt->fetchAll();

// Handle appointment cancellation
if (isset($_POST['cancel_appointment'])) {
    $appointment_id = $_POST['appointment_id'];
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$appointment_id, $_SESSION['user_id']])) {
        header("Location: appointments.php?cancel_success=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - FitZone Fitness Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --dark: #1b263b;
            --light: #f8f9fa;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #560bad;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
            color: #333;
        }

        .dashboard-header {
            background-color: var(--dark);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .logo {
            color: white;
            font-size: 1.8rem;
            font-weight: bold;
            text-decoration: none;
        }

        .logo span {
            color: var(--primary-light);
        }

        .user-nav {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: var(--primary-light);
        }

        .logout-btn {
            background-color: var(--danger);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #d90429;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .welcome-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .welcome-text h1 {
            color: var(--dark);
            margin: 0;
            font-size: 2rem;
        }

        .welcome-text p {
            color: #6c757d;
            margin: 0.5rem 0 0;
        }

        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }

        .btn:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .btn-danger {
            background-color: var(--danger);
        }

        .btn-danger:hover {
            background-color: #b5179e;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .filter-controls {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.6rem 1.2rem;
            background-color: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }

        .filter-btn.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .filter-btn:hover:not(.active) {
            background-color: #f1f3f5;
        }

        .appointment-card {
            background-color: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 4px solid var(--primary);
        }

        .appointment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .appointment-date {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }

        .appointment-status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-scheduled {
            background-color: #e3f2fd;
            color: var(--primary);
        }

        .status-confirmed {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-completed {
            background-color: #f1f8e9;
            color: #5d4037;
        }

        .status-cancelled {
            background-color: #ffebee;
            color: var(--danger);
        }

        .appointment-trainer {
            color: #495057;
            margin: 0.5rem 0;
            font-size: 1rem;
        }

        .appointment-specialization {
            display: inline-block;
            background-color: #f8f9fa;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            color: #495057;
            margin-top: 0.5rem;
        }

        .appointment-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 0;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .empty-state i {
            font-size: 3rem;
            color: #adb5bd;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        @media (max-width: 768px) {
            .welcome-section {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .appointment-header {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .appointment-actions {
                flex-direction: column;
                gap: 0.5rem;
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
                <i class="fas fa-check-circle"></i> Appointment cancelled successfully!
            </div>
        <?php endif; ?>
        
        <div class="welcome-section">
            <div class="welcome-text">
                <h1>Your Appointments</h1>
                <p>View and manage your training sessions</p>
            </div>
            <a href="book_appointment.php" class="btn">
                <i class="fas fa-plus"></i> Book New Appointment
            </a>
        </div>
        
        <div class="filter-controls">
            <button class="filter-btn active" data-filter="all">All Appointments</button>
            <button class="filter-btn" data-filter="scheduled">Upcoming</button>
            <button class="filter-btn" data-filter="confirmed">Confirmed</button>
            <button class="filter-btn" data-filter="completed">Completed</button>
            <button class="filter-btn" data-filter="cancelled">Cancelled</button>
        </div>
        
        <?php if ($appointments): ?>
            <div class="appointments-list">
                <?php foreach ($appointments as $appointment): ?>
                    <div class="appointment-card" data-status="<?= strtolower($appointment['status']) ?>">
                        <div class="appointment-header">
                            <p class="appointment-date">
                                <i class="far fa-calendar-alt"></i> 
                                <?= date('l, F j, Y \a\t g:i A', strtotime($appointment['date'])) ?>
                            </p>
                            <span class="appointment-status status-<?= strtolower($appointment['status']) ?>">
                                <?= ucfirst($appointment['status']) ?>
                            </span>
                        </div>
                        
                        <p class="appointment-trainer">
                            <i class="fas fa-user-tie"></i> 
                            With <?= htmlspecialchars($appointment['trainer_name']) ?>
                        </p>
                        
                        <span class="appointment-specialization">
                            <i class="fas fa-dumbbell"></i> 
                            <?= htmlspecialchars($appointment['specialization']) ?>
                        </span>
                        
                        <?php if ($appointment['status'] == 'scheduled' || $appointment['status'] == 'confirmed'): ?>
                            <div class="appointment-actions">
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                    <button type="submit" name="cancel_appointment" class="btn btn-danger btn-sm" 
                                            onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                        <i class="fas fa-times"></i> Cancel Appointment
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="far fa-calendar-times"></i>
                <h3>No Appointments Found</h3>
                <p>You don't have any appointments scheduled yet.</p>
                <a href="book_appointment.php" class="btn">
                    <i class="fas fa-plus"></i> Book Your First Appointment
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Filter appointments by status
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                document.querySelectorAll('.appointment-card').forEach(card => {
                    if (filter === 'all') {
                        card.style.display = 'block';
                    } else {
                        card.style.display = card.dataset.status === filter ? 'block' : 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>