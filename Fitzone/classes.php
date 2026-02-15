<?php
require 'config.php';

// Initialize variables
$classesByDay = [
    'Monday' => [],
    'Tuesday' => [],
    'Wednesday' => [],
    'Thursday' => [],
    'Friday' => [],
    'Saturday' => [],
    'Sunday' => []
];
$error = null;

try {
    // Corrected query joining with users table
    $stmt = $pdo->prepare("SELECT c.*, u.name as trainer_name 
                          FROM classes c
                          JOIN trainers t ON c.trainer_id = t.id
                          JOIN users u ON t.user_id = u.id
                          ORDER BY c.day_of_week, c.start_time");
    $stmt->execute();
    $classes = $stmt->fetchAll();
    
    // Group classes by day
    foreach ($classes as $class) {
        if (isset($classesByDay[$class['day_of_week']])) {
            $classesByDay[$class['day_of_week']][] = $class;
        }
    }
} catch (PDOException $e) {
    $error = "We're currently updating our class schedule. Please check back soon.";
    error_log("Class loading error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Classes - FitZone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #e6f7ff;
            --secondary: #1a1a1a;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --gray: #6c757d;
            --card-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            background-color: #f8fafc;
            color: #333;
        }
        
        header {
            background: linear-gradient(135deg, var(--primary), #3a0ca3);
            color: white;
            padding: 1.5rem 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: relative;
            z-index: 10;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logo i {
            font-size: 1.5rem;
        }
        
        .logo span {
            color: #f72585;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.7rem 1.5rem;
            background-color: white;
            color: var(--primary);
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            box-shadow: var(--card-shadow);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .container {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 1.5rem;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            color: var(--secondary);
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .page-title::after {
            content: '';
            display: block;
            width: 80px;
            height: 5px;
            background: linear-gradient(to right, var(--primary), var(--success));
            margin: 1rem auto 0;
            border-radius: 5px;
        }
        
        .day-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            margin-bottom: 3rem;
            transition: all 0.3s ease;
        }
        
        .day-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.12);
        }
        
        .day-header {
            background: linear-gradient(to right, var(--primary), #4895ef);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .day-name {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .day-name i {
            font-size: 1.3rem;
        }
        
        .class-count {
            background: white;
            color: var(--primary);
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 700;
        }
        
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            padding: 2rem;
        }
        
        .class-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            background: white;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .class-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .class-image {
            height: 220px;
            overflow: hidden;
            position: relative;
        }
        
        .class-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .class-card:hover .class-image img {
            transform: scale(1.1);
        }
        
        .class-content {
            padding: 1.8rem;
        }
        
        .class-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--secondary);
        }
        
        .class-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.2rem;
            font-size: 0.95rem;
            color: var(--gray);
        }
        
        .class-time, .class-trainer {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .class-desc {
            color: #555;
            margin-bottom: 1.8rem;
            font-size: 0.95rem;
            line-height: 1.7;
        }
        
        .class-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .difficulty {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .beginner {
            background: rgba(76, 201, 240, 0.1);
            color: var(--success);
        }
        
        .intermediate {
            background: rgba(248, 150, 30, 0.1);
            color: var(--warning);
        }
        
        .advanced {
            background: rgba(247, 37, 133, 0.1);
            color: var(--danger);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            grid-column: 1 / -1;
        }
        
        .empty-icon {
            font-size: 5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            opacity: 0.8;
        }
        
        .empty-title {
            font-size: 2rem;
            color: var(--secondary);
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .empty-text {
            color: var(--gray);
            margin-bottom: 2rem;
            font-size: 1.1rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.7;
        }
        
        .alert {
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 3rem;
            background: rgba(247, 37, 133, 0.1);
            color: var(--danger);
            border-left: 5px solid var(--danger);
            font-weight: 500;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .classes-grid {
                grid-template-columns: 1fr;
                padding: 1.5rem;
                gap: 1.5rem;
            }
            
            .container {
                padding: 0 1rem;
            }
            
            .page-title {
                font-size: 2rem;
                margin-bottom: 2rem;
            }
            
            .day-header {
                padding: 1rem 1.5rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.8rem;
            }
            
            .day-name {
                font-size: 1.3rem;
            }
            
            .empty-icon {
                font-size: 3.5rem;
            }
            
            .empty-title {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <a href="index.php" class="logo">
                <i class="fas fa-dumbbell"></i>Fit<span>Zone</span>
            </a>
            <a href="dashboard.php" class="btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">Our Fitness Classes</h1>
        
        <?php if ($error): ?>
            <div class="alert">
                <i class="fas fa-exclamation-circle fa-lg"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>
        
        <?php 
        $hasClasses = false;
        foreach ($classesByDay as $dayClasses) {
            if (!empty($dayClasses)) {
                $hasClasses = true;
                break;
            }
        }
        ?>
        
        <?php if ($hasClasses): ?>
            <?php foreach ($classesByDay as $day => $dayClasses): ?>
                <?php if (!empty($dayClasses)): ?>
                    <div class="day-card">
                        <div class="day-header">
                            <h2 class="day-name">
                                <i class="far fa-calendar-alt"></i> <?= htmlspecialchars($day) ?>
                            </h2>
                            <span class="class-count"><?= count($dayClasses) ?> classes</span>
                        </div>
                        
                        <div class="classes-grid">
                            <?php foreach ($dayClasses as $class): ?>
                                <div class="class-card">
                                    <div class="class-image">
                                        <img src="images/classes/<?= htmlspecialchars($class['image'] ?? 'default-class.jpg') ?>" 
                                             alt="<?= htmlspecialchars($class['name']) ?>">
                                    </div>
                                    <div class="class-content">
                                        <h3 class="class-title"><?= htmlspecialchars($class['name']) ?></h3>
                                        <div class="class-meta">
                                            <span class="class-time">
                                                <i class="far fa-clock"></i>
                                                <?= date('g:i A', strtotime($class['start_time'])) ?> - 
                                                <?= date('g:i A', strtotime($class['end_time'])) ?>
                                            </span>
                                            <span class="class-trainer">
                                                <i class="fas fa-user-tie"></i>
                                                <?= htmlspecialchars($class['trainer_name']) ?>
                                            </span>
                                        </div>
                                        <p class="class-desc">
                                            <?= htmlspecialchars($class['description']) ?>
                                        </p>
                                        <div class="class-footer">
                                            <span class="difficulty <?= strtolower($class['difficulty']) ?>">
                                                <i class="fas fa-bolt"></i>
                                                <?= $class['difficulty'] ?>
                                            </span>
                                            <?php if (isset($_SESSION['user_id'])): ?>
                                                <a href="book_class.php?class_id=<?= $class['id'] ?>" class="btn btn-primary">
                                                    <i class="fas fa-calendar-plus"></i> Book Now
                                                </a>
                                            <?php else: ?>
                                                <a href="login.php" class="btn btn-primary">
                                                    <i class="fas fa-sign-in-alt"></i> Login to Book
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="day-card">
                <div class="classes-grid">
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <h3 class="empty-title">No Classes Scheduled</h3>
                        <p class="empty-text">
                            We're currently preparing our class schedule. Please check back soon or contact our team for updates on upcoming classes.
                        </p>
                        <a href="contact.php" class="btn btn-primary">
                            <i class="fas fa-envelope"></i> Contact Us
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>