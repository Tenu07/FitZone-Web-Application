<?php
// Ensure config.php exists in the same directory
require __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get current membership
try {
    $stmt = $pdo->prepare("SELECT p.name, p.description, p.price, p.duration, 
                          m.id, m.start_date, m.end_date, m.status 
                          FROM memberships m 
                          JOIN plans p ON m.plan_id = p.id 
                          WHERE m.user_id = ? 
                          ORDER BY m.end_date DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $memberships = $stmt->fetchAll();
    $plans = $pdo->query("SELECT * FROM plans WHERE status IS NOT NULL AND status = 'active'")->fetchAll();
    // Get available plans
    $plans = $pdo->query("SELECT * FROM plans WHERE status = 'active'")->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership - FitZone Fitness Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a6bff;
            --secondary-color: #f8f9fa;
            --text-color: #333;
            --light-text: #777;
            --border-color: #e0e0e0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: var(--text-color);
        }
        
        .dashboard-header {
            background-color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--text-color);
            text-decoration: none;
        }
        
        .logo span {
            color: var(--primary-color);
        }
        
        .user-nav {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .nav-link {
            color: var(--text-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logout-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
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
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .welcome-text p {
            color: var(--light-text);
        }
        
        .btn {
            background-color: var(--primary-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
        }
        
        .dashboard-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .membership-history {
            margin-top: 2rem;
        }
        
        .plan-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .plan-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .plan-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .plan-features {
            margin: 1rem 0;
            padding-left: 1.5rem;
        }
        
        .plan-features li {
            margin-bottom: 0.5rem;
        }
        
        .membership-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .membership-table th, 
        .membership-table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .membership-table th {
            background-color: #f8f9fa;
        }
        
        .membership-status {
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .membership-status.active {
            background-color: #e6f7ee;
            color: #00a854;
        }
        
        .membership-status.expired {
            background-color: #fff2f0;
            color: #f5222d;
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
                <h1>Your Membership</h1>
                <p>Manage your FitZone membership</p>
            </div>
            <?php if (empty($memberships) || $memberships[0]['status'] != 'active'): ?>
                <a href="membership_plans.php" class="btn">Get Membership</a>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($memberships)): ?>
            <div class="dashboard-card">
                <h3>Current Membership</h3>
                <div class="plan-card">
                    <div class="plan-header">
                        <h4><?= htmlspecialchars($memberships[0]['name']) ?></h4>
                        <div class="plan-price">Rs. <?= number_format($memberships[0]['price'], 2) ?>/month</div>
                    </div>
                    <p><?= htmlspecialchars($memberships[0]['description']) ?></p>
                    <ul class="plan-features">
                        <li>Access to all gym facilities</li>
                        <li>Free locker usage</li>
                        <li>Discounts on personal training</li>
                        <li>Priority class booking</li>
                    </ul>
                    <div class="membership-info">
                        <p><strong>Status:</strong> 
                            <span class="membership-status <?= $memberships[0]['status'] === 'active' ? 'active' : 'expired' ?>">
                                <?= ucfirst($memberships[0]['status']) ?>
                            </span>
                        </p>
                        <p><strong>Start Date:</strong> <?= date('F j, Y', strtotime($memberships[0]['start_date'])) ?></p>
                        <p><strong>End Date:</strong> <?= date('F j, Y', strtotime($memberships[0]['end_date'])) ?></p>
                        <p><strong>Days Remaining:</strong> 
                            <?= max(0, floor((strtotime($memberships[0]['end_date']) - time()) / (60 * 60 * 24))) ?>
                        </p>
                    </div>
                    <?php if ($memberships[0]['status'] === 'active'): ?>
                        <a href="renew_membership.php?id=<?= $memberships[0]['id'] ?>" class="btn">Renew Membership</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="dashboard-card membership-history">
                <h3>Membership History</h3>
                <table class="membership-table">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Price</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($memberships as $membership): ?>
                            <tr>
                                <td><?= htmlspecialchars($membership['name']) ?></td>
                                <td>Rs. <?= number_format($membership['price'], 2) ?></td>
                                <td><?= date('M j, Y', strtotime($membership['start_date'])) ?></td>
                                <td><?= date('M j, Y', strtotime($membership['end_date'])) ?></td>
                                <td>
                                    <span class="membership-status <?= $membership['status'] === 'active' ? 'active' : 'expired' ?>">
                                        <?= ucfirst($membership['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="dashboard-card">
                <h3>No Active Membership</h3>
                <p>You don't currently have an active membership. Choose a plan to get started!</p>
                <a href="membership_plans.php" class="btn">View Membership Plans</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>