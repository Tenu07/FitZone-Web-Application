<?php
// Start session to potentially get user role for customized messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = $_SESSION['role'] ?? 'guest';
$dashboard_link = 'login.php'; // Default link

// Determine appropriate dashboard link
switch ($role) {
    case 'admin':
        $dashboard_link = 'admin_dashboard.php';
        break;
    case 'staff':
    case 'trainer':
    case 'manager':
        $dashboard_link = 'staff_dashboard.php';
        break;
    case 'customer':
        $dashboard_link = 'member_dashboard.php';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - FitZone</title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
         :root { --primary-color: #00b4d8; --danger-color: #d9534f; }
        body { font-family: 'Poppins', sans-serif; background-color: #f5f7fa; color: #333; display: flex; justify-content: center; align-items: center; min-height: 100vh; text-align: center; padding: 1rem; }
        .container { background-color: white; padding: 3rem; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); max-width: 500px; }
        i { font-size: 3rem; color: var(--danger-color); margin-bottom: 1rem; }
        h1 { color: var(--danger-color); margin-bottom: 1rem; font-weight: 600; }
        p { margin-bottom: 1.5rem; color: #555; line-height: 1.6; }
        a { display: inline-block; padding: 0.8rem 1.5rem; background-color: var(--primary-color); color: white; text-decoration: none; border-radius: 50px; font-weight: 500; transition: background-color 0.3s; }
        a:hover { background-color: #0096b4; }
    </style>
</head>
<body>
    <div class="container">
        <i class="fas fa-ban"></i>
        <h1>Access Denied</h1>
        <p>Sorry, you do not have the necessary permissions to access this page.</p>
        <a href="<?= htmlspecialchars($dashboard_link) ?>">Return to Your Dashboard</a>
    </div>
</body>
</html>