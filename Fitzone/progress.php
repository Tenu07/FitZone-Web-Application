<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get workout history
$stmt = $pdo->prepare("SELECT w.date, wt.name as workout_type, w.duration, w.calories_burned 
                      FROM workouts w
                      JOIN workout_types wt ON w.workout_type_id = wt.id
                      WHERE w.user_id = ? 
                      ORDER BY w.date DESC 
                      LIMIT 10");
$stmt->execute([$_SESSION['user_id']]);
$workouts = $stmt->fetchAll();

// Get progress metrics
$stmt = $pdo->prepare("SELECT 
                      COUNT(*) as total_workouts,
                      SUM(duration) as total_minutes,
                      SUM(calories_burned) as total_calories,
                      MIN(date) as first_workout
                      FROM workouts 
                      WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

// Get weight tracking data (fixed column name)
$stmt = $pdo->prepare("SELECT measurement_date as date, weight FROM weight_tracking WHERE user_id = ? ORDER BY measurement_date");
$stmt->execute([$_SESSION['user_id']]);
$weight_data = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Progress - FitZone Fitness Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* All your existing CSS styles remain the same */
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

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .progress-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .progress-stat {
            background-color: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s;
        }

        .progress-stat:hover {
            transform: translateY(-5px);
        }

        .progress-stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
            margin: 0;
        }

        .progress-stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin: 0.3rem 0 0 0;
        }

        .chart-container {
            background-color: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .chart-container h3 {
            margin-top: 0;
            color: var(--dark);
        }

        .workout-history {
            background-color: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .workout-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .workout-table th, 
        .workout-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .workout-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark);
        }

        .workout-table tr:hover {
            background-color: #f8f9fa;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 0;
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

        @media (max-width: 768px) {
            .welcome-section {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .progress-stats {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 480px) {
            .progress-stats {
                grid-template-columns: 1fr;
            }
            
            .workout-table {
                display: block;
                overflow-x: auto;
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
        <div class="welcome-section">
            <div class="welcome-text">
                <h1>Your Fitness Progress</h1>
                <p>Track your journey and achievements</p>
            </div>
            <a href="log_workout.php" class="btn">
                <i class="fas fa-plus"></i> Log New Workout
            </a>
        </div>
        
        <div class="progress-stats">
            <div class="progress-stat">
                <p class="progress-stat-value"><?= $stats ? $stats['total_workouts'] : '0' ?></p>
                <p class="progress-stat-label">Total Workouts</p>
            </div>
            <div class="progress-stat">
                <p class="progress-stat-value"><?= $stats ? floor(($stats['total_minutes'] ?? 0) / 60) : '0' ?>h <?= $stats ? ($stats['total_minutes'] ?? 0) % 60 : '0' ?>m</p>
                <p class="progress-stat-label">Total Time</p>
            </div>
            <div class="progress-stat">
                <p class="progress-stat-value"><?= $stats && isset($stats['total_calories']) ? number_format((float)$stats['total_calories']) : '0' ?></p>
                <p class="progress-stat-label">Calories Burned</p>
            </div>
            <div class="progress-stat">
                <p class="progress-stat-value"><?= $stats && isset($stats['first_workout']) ? date('M Y', strtotime($stats['first_workout'])) : 'N/A' ?></p>
                <p class="progress-stat-label">Member Since</p>
            </div>
        </div>
        
        <div class="chart-container">
            <h3><i class="fas fa-weight"></i> Weight Progress</h3>
            <?php if ($weight_data): ?>
                <div style="height: 300px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; border-radius: 8px;">
                    <!-- This would be replaced with a real chart in production -->
                    <p>Weight chart would display here</p>
                </div>
                <a href="log_weight.php" class="btn btn-sm">
                    <i class="fas fa-plus"></i> Log Weight
                </a>
            <?php else: ?>
                <div class="empty-state" style="padding: 2rem 0;">
                    <i class="fas fa-weight"></i>
                    <h3>No Weight Data</h3>
                    <p>Start tracking your weight to see progress over time</p>
                    <a href="log_weight.php" class="btn btn-sm">
                        <i class="fas fa-plus"></i> Log First Weight
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="chart-container">
            <h3><i class="fas fa-chart-line"></i> Workout Frequency</h3>
            <div style="height: 300px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                <!-- This would be replaced with a real chart in production -->
                <p>Workout frequency chart would display here</p>
            </div>
        </div>
        
        <div class="workout-history">
            <h3><i class="fas fa-history"></i> Recent Workouts</h3>
            <?php if ($workouts): ?>
                <table class="workout-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Workout Type</th>
                            <th>Duration</th>
                            <th>Calories</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($workouts as $workout): ?>
                            <tr>
                                <td><?= date('M j, Y', strtotime($workout['date'])) ?></td>
                                <td><?= htmlspecialchars($workout['workout_type']) ?></td>
                                <td><?= floor(($workout['duration'] ?? 0) / 60) ?>h <?= ($workout['duration'] ?? 0) % 60 ?>m</td>
                                <td><?= isset($workout['calories_burned']) ? number_format((float)$workout['calories_burned']) : '0' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="workout_history.php" class="btn" style="margin-top: 1rem;">
                    <i class="fas fa-list"></i> View Full History
                </a>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-dumbbell"></i>
                    <h3>No Workouts Recorded</h3>
                    <p>Get started by logging your first workout</p>
                    <a href="log_workout.php" class="btn">
                        <i class="fas fa-plus"></i> Log First Workout
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>