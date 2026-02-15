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

// Get all active membership plans
$plans = $pdo->query("SELECT * FROM plans WHERE status = 'active' ORDER BY price ASC")->fetchAll();

// Handle plan selection
if (isset($_POST['select_plan'])) {
    $plan_id = $_POST['plan_id'];
    
    // Get the selected plan
    $stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
    $stmt->execute([$plan_id]);
    $selected_plan = $stmt->fetch();
    
    if ($selected_plan) {
        // Calculate start and end dates
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+{$selected_plan['duration']} months"));
        
        // Check if user already has an active membership
        $stmt = $pdo->prepare("SELECT id FROM memberships WHERE user_id = ? AND end_date >= CURDATE()");
        $stmt->execute([$_SESSION['user_id']]);
        $existing_membership = $stmt->fetch();
        
        if ($existing_membership) {
            $error = "You already have an active membership. Please wait until it expires or contact support.";
        } else {
            // Create new membership
            $stmt = $pdo->prepare("INSERT INTO memberships (user_id, plan_id, start_date, end_date) 
                                  VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $plan_id, $start_date, $end_date])) {
                // Create notification
                $message = "You have successfully subscribed to the {$selected_plan['name']} plan. Your membership is active until {$end_date}.";
                $pdo->prepare("INSERT INTO notifications (user_id, title, message, notification_type, related_id) 
                              VALUES (?, ?, ?, 'membership', ?)")
                    ->execute([$_SESSION['user_id'], "New Membership", $message, $pdo->lastInsertId()]);
                
                header("Location: dashboard.php?membership_success=1");
                exit;
            } else {
                $error = "Failed to process your membership. Please try again.";
            }
        }
    } else {
        $error = "Invalid plan selected. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Plans - FitZone Fitness Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #00b4d8;
            --secondary-color: #1a1a1a;
            --light-bg: #f8f9fa;
            --danger-color: #d9534f;
            --success-color: #5cb85c;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .header {
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
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .page-title {
            color: var(--secondary-color);
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .plans-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .plan-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .plan-card:hover {
            transform: translateY(-5px);
        }
        
        .plan-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .plan-header h3 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .plan-price {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 1rem 0;
        }
        
        .plan-duration {
            font-size: 1rem;
            opacity: 0.8;
        }
        
        .plan-body {
            padding: 1.5rem;
        }
        
        .plan-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1.5rem 0;
        }
        
        .plan-features li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .plan-features li:last-child {
            border-bottom: none;
        }
        
        .plan-features i {
            color: var(--primary-color);
            margin-right: 0.5rem;
        }
        
        .plan-footer {
            padding: 0 1.5rem 1.5rem 1.5rem;
            text-align: center;
        }
        
        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: bold;
            width: 100%;
        }
        
        .btn:hover {
            background-color: #0096b4;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .popular-badge {
            position: absolute;
            top: 0;
            right: 20px;
            background-color: var(--success-color);
            color: white;
            padding: 0.3rem 1rem;
            border-radius: 0 0 5px 5px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .current-plan {
            background-color: #e7f5ff;
            border-left: 4px solid var(--primary-color);
            padding: 1rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .plans-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="index.php" class="logo">Fit<span>Zone</span></a>
        <div>
            <a href="dashboard.php" class="nav-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">Choose Your Membership Plan</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php
        // Check if user already has an active membership
        $stmt = $pdo->prepare("SELECT m.*, p.name as plan_name 
                              FROM memberships m 
                              JOIN plans p ON m.plan_id = p.id 
                              WHERE m.user_id = ? AND m.end_date >= CURDATE()");
        $stmt->execute([$_SESSION['user_id']]);
        $current_membership = $stmt->fetch();
        
        if ($current_membership): ?>
            <div class="current-plan">
                <h3>Your Current Membership</h3>
                <p><strong>Plan:</strong> <?= htmlspecialchars($current_membership['plan_name']) ?></p>
                <p><strong>Expires:</strong> <?= date('F j, Y', strtotime($current_membership['end_date'])) ?></p>
                <p>You can upgrade your plan by selecting a new one below.</p>
            </div>
        <?php endif; ?>
        
        <div class="plans-container">
            <?php foreach ($plans as $plan): ?>
                <div class="plan-card">
                    <?php if ($plan['name'] == 'Premium'): ?>
                        <div class="popular-badge">MOST POPULAR</div>
                    <?php endif; ?>
                    
                    <div class="plan-header">
                        <h3><?= htmlspecialchars($plan['name']) ?></h3>
                    </div>
                    
                    <div class="plan-body">
                        <div class="plan-price">Rs. <?= number_format($plan['price'], 2) ?></div>
                        <div class="plan-duration">per <?= $plan['duration'] ?> month<?= $plan['duration'] > 1 ? 's' : '' ?></div>
                        
                        <ul class="plan-features">
                            <?php 
                            $benefits = explode("\n", $plan['benefits']);
                            foreach ($benefits as $benefit): 
                                if (trim($benefit)): ?>
                                    <li><i class="fas fa-check"></i> <?= htmlspecialchars(trim($benefit)) ?></li>
                                <?php endif;
                            endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="plan-footer">
                        <form method="POST">
                            <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                            <button type="submit" name="select_plan" class="btn <?= $plan['name'] == 'Premium' ? '' : 'btn-outline' ?>">
                                <?= $current_membership ? 'Upgrade Plan' : 'Get Started' ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>