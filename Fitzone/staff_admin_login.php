<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect based on role
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
     switch ($_SESSION['role']) {
         case 'admin':
             header("Location: admin_dashboard.php"); exit;
         case 'staff':
         case 'trainer':
         case 'manager':
             header("Location: staff_dashboard.php"); exit;
         case 'customer':
             header("Location: member_dashboard.php"); exit;
         default:
             header("Location: logout.php"); exit;
     }
}

require_once __DIR__ . '/config.php'; // Use require_once

$error = '';
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $email_value = $email;

    if (empty($email) || empty($password)) {
        $error = "Email and password are required.";
    } else {
        try {
            // Authenticate against the main users table, checking for specific roles
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role IN ('admin', 'staff', 'trainer', 'manager') AND is_active = TRUE");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];

                 // Update last login time
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } else { // Staff, Trainer, Manager
                    header("Location: staff_dashboard.php");
                }
                exit;
            } else {
                $error = "Invalid credentials or insufficient permissions.";
            }
        } catch (PDOException $e) {
            $error = "Login error. Please try again later.";
            error_log("Staff/Admin Login Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff/Admin Login - FitZone</title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Re-use or adapt styles from login.php */
        :root {
            --primary-color: #00b4d8;
            --primary-dark: #0096b4;
            --secondary-color: #1a1a1a;
            --danger-color: #d9534f;
            --light-bg: #f8f9fa;
            --text-color: #333;
            --border-color: #ddd;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            margin: 0; padding: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh;
        }
        .login-container {
            background: white; padding: 2.5rem; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); width: 100%; max-width: 420px;
        }
         .logo-container { text-align: center; margin-bottom: 1rem; }
         .logo { color: var(--secondary-color); font-size: 2rem; font-weight: 700; text-decoration: none; }
         .logo i { margin-right: 0.5rem; color: var(--primary-color); }
         .logo span { color: var(--primary-color); }
        h2 { text-align: center; color: var(--text-color); margin-bottom: 1.5rem; font-weight: 600; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #555; }
        .form-group input { width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--border-color); border-radius: 5px; box-sizing: border-box; font-family: inherit; transition: border-color 0.3s ease; }
        .form-group input:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(0, 180, 216, 0.2); }
        button { width: 100%; padding: 0.9rem; background-color: var(--primary-color); color: white; border: none; border-radius: 50px; cursor: pointer; font-size: 1rem; font-weight: 500; margin-top: 1rem; transition: background-color 0.3s ease, transform 0.2s ease; text-transform: uppercase; letter-spacing: 1px; }
        button:hover { background-color: var(--primary-dark); transform: translateY(-2px); }
        .alert { text-align: center; margin-bottom: 1rem; padding: 0.8rem; border-radius: 5px; font-size: 0.95rem; }
        .error { color: var(--danger-color); background-color: #fdd; border: 1px solid var(--danger-color); }
        .links-container { text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: #666; }
        .links-container a { color: var(--primary-color); text-decoration: none; font-weight: 500; }
        .links-container a:hover { text-decoration: underline; color: var(--primary-dark); }
    </style>
</head>
<body>
    <div class="login-container">
         <div class="logo-container">
             <a href="index.php" class="logo">
                 <i class="fas fa-dumbbell"></i>Fit<span>Zone</span>
             </a>
         </div>
        <h2>Staff & Admin Portal</h2>

        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="staff_admin_login.php" method="POST">
            <div class="form-group">
                <label for="email">Work Email Address</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email_value) ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>

        <div class="links-container">
            Not staff or admin? <a href="login.php">Member Login</a>
        </div>
    </div>
</body>
</html>