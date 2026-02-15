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

// Get available trainers
$trainers = [];
try {
    $stmt = $pdo->prepare("SELECT id, name, specialization FROM trainers WHERE status = 'active'");
    $stmt->execute();
    $trainers = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error loading trainers: " . $e->getMessage();
}

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trainer_id = $_POST['trainer_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $notes = trim($_POST['notes']);
    
    // Validate inputs
    if (empty($trainer_id) || empty($date) || empty($time)) {
        $message = '<div class="alert error">Please fill all required fields</div>';
    } else {
        $datetime = date('Y-m-d H:i:s', strtotime("$date $time"));
        
        // Check for existing appointment
        $stmt = $pdo->prepare("SELECT id FROM appointments WHERE trainer_id = ? AND date = ?");
        $stmt->execute([$trainer_id, $datetime]);
        
        if ($stmt->fetch()) {
            $message = '<div class="alert error">Trainer is not available at this time</div>';
        } else {
            // Create appointment
            $stmt = $pdo->prepare("INSERT INTO appointments (user_id, trainer_id, date, notes) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $trainer_id, $datetime, $notes])) {
                // Create notification
                $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
                   ->execute([$_SESSION['user_id'], "Appointment booked for " . date('M j, Y g:i A', strtotime($datetime))]);
                
                header("Location: dashboard.php?booking_success=1");
                exit;
            } else {
                $message = '<div class="alert error">Error booking appointment. Please try again.</div>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - FitZone Fitness Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #00b4d8;
            --secondary-color: #1a1a1a;
            --danger-color: #d9534f;
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
        
        .booking-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .page-title {
            color: var(--primary-color);
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        
        .required:after {
            content: " *";
            color: var(--danger-color);
        }
        
        select, input[type="date"], input[type="time"], textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        
        textarea {
            height: 100px;
            resize: vertical;
        }
        
        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #0096b4;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        
        .error {
            background-color: #f2dede;
            color: #a94442;
        }
        
        .trainer-card {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 1px solid #eee;
            border-radius: 4px;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        
        .trainer-card:hover {
            border-color: var(--primary-color);
            background-color: var(--light-bg);
        }
        
        .trainer-radio {
            margin-right: 1rem;
        }
        
        .trainer-info {
            flex: 1;
        }
        
        .trainer-name {
            font-weight: bold;
            margin: 0 0 0.3rem 0;
        }
        
        .trainer-specialty {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .booking-container {
                margin: 1rem;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="dashboard-header">
        <a href="index.php" class="logo">Fit<span>Zone</span></a>
        <a href="dashboard.php" class="logout-btn">Back to Dashboard</a>
    </header>

    <div class="booking-container">
        <h1 class="page-title">Book Training Session</h1>
        
        <?php echo $message; ?>
        
        <form action="book_appointment.php" method="POST">
            <div class="form-group">
                <label class="required">Select Trainer</label>
                <?php if (!empty($trainers)): ?>
                    <?php foreach ($trainers as $trainer): ?>
                        <label class="trainer-card">
                            <input type="radio" name="trainer_id" value="<?= $trainer['id'] ?>" class="trainer-radio" required>
                            <div class="trainer-info">
                                <p class="trainer-name"><?= htmlspecialchars($trainer['name']) ?></p>
                                <p class="trainer-specialty"><?= htmlspecialchars($trainer['specialization']) ?></p>
                            </div>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No trainers available at this time.</p>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="date" class="required">Date</label>
                <input type="date" id="date" name="date" min="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="time" class="required">Time</label>
                <input type="time" id="time" name="time" min="06:00" max="20:00" required>
                <small>Available between 6:00 AM and 8:00 PM</small>
            </div>
            
            <div class="form-group">
                <label for="notes">Additional Notes</label>
                <textarea id="notes" name="notes" placeholder="Any special requests or requirements..."></textarea>
            </div>
            
            <button type="submit" class="btn">Book Appointment</button>
            <a href="dashboard.php" class="btn" style="background-color: #6c757d; margin-left: 1rem;">Cancel</a>
        </form>
    </div>

    <script>
        // Set default time to next available hour
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const nextHour = new Date(now.getTime() + 60 * 60 * 1000);
            
            // Set default date to today
            document.getElementById('date').valueAsDate = new Date();
            
            // Set default time to next full hour (e.g., 2:00, 3:00)
            const hours = nextHour.getHours().toString().padStart(2, '0');
            document.getElementById('time').value = `${hours}:00`;
            
            // Disable past dates
            document.getElementById('date').min = new Date().toISOString().split('T')[0];
        });
    </script>
</body>
</html>