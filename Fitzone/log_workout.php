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

// Get workout types
$workout_types = [];
try {
    $stmt = $pdo->prepare("SELECT id, name FROM workout_types WHERE active = 1");
    $stmt->execute();
    $workout_types = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error loading workout types: " . $e->getMessage();
}

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $workout_type = $_POST['workout_type'];
    $duration = (int)$_POST['duration'];
    $calories = !empty($_POST['calories']) ? (int)$_POST['calories'] : null;
    $notes = trim($_POST['notes']);
    $date = $_POST['workout_date'];
    
    // Validate inputs
    if (empty($workout_type) || empty($duration) || $duration <= 0) {
        $message = '<div class="alert error">Please select workout type and enter valid duration</div>';
    } else {
        // Insert workout log
        try {
            $stmt = $pdo->prepare("INSERT INTO workouts (user_id, workout_type_id, date, duration, calories, notes) 
                                 VALUES (?, ?, ?, ?, ?, ?)");
            $success = $stmt->execute([
                $_SESSION['user_id'],
                $workout_type,
                $date ?: date('Y-m-d H:i:s'),
                $duration,
                $calories,
                $notes
            ]);
            
            if ($success) {
                // Create achievement notification if duration > 60 minutes
                if ($duration >= 60) {
                    $pdo->prepare("INSERT INTO notifications (user_id, message) 
                                 VALUES (?, ?)")
                       ->execute([
                           $_SESSION['user_id'],
                           "Great job! You completed a {$duration}-minute workout"
                       ]);
                }
                
                header("Location: dashboard.php?workout_logged=1");
                exit;
            }
        } catch (PDOException $e) {
            $message = '<div class="alert error">Error logging workout: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Workout - FitZone Fitness Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #00b4d8;
            --secondary-color: #1a1a1a;
            --danger-color: #d9534f;
            --success-color: #5cb85c;
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
        
        .workout-container {
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .page-title i {
            font-size: 1.5rem;
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
        
        select, input[type="number"], input[type="date"], textarea {
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
        
        .input-group {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .input-group input {
            flex: 1;
        }
        
        .input-group .unit {
            width: 60px;
            color: #666;
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
        
        .btn-success {
            background-color: var(--success-color);
        }
        
        .btn-success:hover {
            background-color: #449d44;
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
        
        .success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        
        @media (max-width: 768px) {
            .workout-container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .input-group {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .input-group .unit {
                width: auto;
                margin-left: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="dashboard-header">
        <a href="index.php" class="logo">Fit<span>Zone</span></a>
        <a href="dashboard.php" class="logout-btn">Back to Dashboard</a>
    </header>

    <div class="workout-container">
        <h1 class="page-title"><i class="fas fa-dumbbell"></i> Log Workout</h1>
        
        <?php echo $message; ?>
        
        <form action="log_workout.php" method="POST">
            <div class="form-group">
                <label for="workout_type" class="required">Workout Type</label>
                <select id="workout_type" name="workout_type" required>
                    <option value="">-- Select Workout Type --</option>
                    <?php foreach ($workout_types as $type): ?>
                        <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="workout_date">Date</label>
                <input type="date" id="workout_date" name="workout_date" 
                       value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
            </div>
            
            <div class="form-group">
                <label for="duration" class="required">Duration</label>
                <div class="input-group">
                    <input type="number" id="duration" name="duration" min="1" max="300" required>
                    <span class="unit">minutes</span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="calories">Calories Burned</label>
                <div class="input-group">
                    <input type="number" id="calories" name="calories" min="1" max="2000">
                    <span class="unit">kcal</span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="notes">Workout Notes</label>
                <textarea id="notes" name="notes" placeholder="Describe your workout (exercises, sets, reps, how you felt, etc.)"></textarea>
            </div>
            
            <button type="submit" class="btn btn-success">Log Workout</button>
            <a href="dashboard.php" class="btn" style="background-color: #6c757d; margin-left: 1rem;">Cancel</a>
        </form>
    </div>

    <script>
        // Set default values and constraints
        document.addEventListener('DOMContentLoaded', function() {
            // Set duration to 30 minutes by default
            document.getElementById('duration').value = 30;
            
            // Calculate calories based on duration (approximate)
            document.getElementById('duration').addEventListener('input', function() {
                const duration = parseInt(this.value) || 0;
                const caloriesInput = document.getElementById('calories');
                
                if (!caloriesInput.value) {
                    // Estimate calories (average 5-10 kcal per minute depending on intensity)
                    const estimatedCalories = Math.round(duration * 7.5);
                    caloriesInput.value = estimatedCalories > 0 ? estimatedCalories : '';
                }
            });
        });
    </script>
</body>
</html>