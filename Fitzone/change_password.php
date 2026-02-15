<?php
require 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Initialize variables
$message = '';
$current_password = '';
$new_password = '';
$confirm_password = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!password_verify($current_password, $user['password'])) {
        $message = '<div class="alert error">Current password is incorrect</div>';
    } elseif (empty($new_password) || strlen($new_password) < 8) {
        $message = '<div class="alert error">New password must be at least 8 characters</div>';
    } elseif (!preg_match("/[A-Z]/", $new_password) || !preg_match("/[0-9]/", $new_password) || !preg_match("/[^A-Za-z0-9]/", $new_password)) {
        $message = '<div class="alert error">Password must contain at least one uppercase letter, one number, and one special character</div>';
    } elseif ($new_password !== $confirm_password) {
        $message = '<div class="alert error">New passwords do not match</div>';
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        
        if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
            // Send email notification
            $subject = "FitZone Password Changed";
            $message = "Your FitZone account password was successfully changed on " . date('Y-m-d H:i:s');
            $headers = "From: no-reply@fitzone.com";
            mail($user['email'], $subject, $message, $headers);
            
            $message = '<div class="alert success">Password changed successfully! You will be logged out shortly.</div>';
            // Clear form fields
            $current_password = $new_password = $confirm_password = '';
            
            // Logout after 3 seconds
            header("refresh:3;url=logout.php");
        } else {
            $message = '<div class="alert error">Error updating password. Please try again.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - FitZone Fitness Center</title>
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
        
        .password-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .password-title {
            color: var(--primary-color);
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .password-title i {
            font-size: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
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
        
        input[type="password"] {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
            padding-right: 40px;
        }
        
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        
        .strength-weak {
            color: var(--danger-color);
        }
        
        .strength-medium {
            color: var(--warning-color);
        }
        
        .strength-strong {
            color: var(--success-color);
        }
        
        .password-rules {
            background-color: var(--light-bg);
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        
        .password-rules ul {
            margin: 0.5rem 0 0 0;
            padding-left: 1.5rem;
        }
        
        .password-rules li {
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
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
        
        .success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 38px;
            cursor: pointer;
            color: #666;
            background: none;
            border: none;
            font-size: 1rem;
        }
        
        @media (max-width: 768px) {
            .password-container {
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

    <div class="password-container">
        <h1 class="password-title"><i class="fas fa-lock"></i> Change Password</h1>
        
        <?php echo $message; ?>
        
        <div class="password-rules">
            <p><strong>Password Requirements:</strong></p>
            <ul>
                <li>Minimum 8 characters</li>
                <li>At least one uppercase letter</li>
                <li>At least one number</li>
                <li>At least one special character</li>
                <li>Should not match your current password</li>
            </ul>
        </div>
        
        <form action="change_password.php" method="POST" id="passwordForm">
            <div class="form-group">
                <label for="current_password" class="required">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
                <button type="button" class="toggle-password" onclick="togglePassword('current_password')">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            
            <div class="form-group">
                <label for="new_password" class="required">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
                <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                    <i class="fas fa-eye"></i>
                </button>
                <div class="password-strength" id="passwordStrength">
                    Password strength: <span id="strengthText">weak</span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="required">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            
            <button type="submit" class="btn">Change Password</button>
            <a href="dashboard.php" class="btn" style="background-color: #6c757d; margin-left: 1rem;">Cancel</a>
        </form>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling.querySelector('i');
            input.type = input.type === 'password' ? 'text' : 'password';
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        }
        
        // Password strength indicator
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthText = document.getElementById('strengthText');
            const strengthMeter = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthText.textContent = 'none';
                strengthMeter.className = 'password-strength';
                return;
            }
            
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            
            // Complexity checks
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Update display
            let strengthClass, strengthLabel;
            if (strength <= 2) {
                strengthClass = 'strength-weak';
                strengthLabel = 'weak';
            } else if (strength <= 4) {
                strengthClass = 'strength-medium';
                strengthLabel = 'medium';
            } else {
                strengthClass = 'strength-strong';
                strengthLabel = 'strong';
            }
            
            strengthText.textContent = strengthLabel;
            strengthMeter.className = 'password-strength ' + strengthClass;
        });
        
        // Form validation
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
                document.getElementById('confirm_password').focus();
            }
            
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                document.getElementById('new_password').focus();
            }
            
            if (!/[A-Z]/.test(newPassword) || !/[0-9]/.test(newPassword) || !/[^A-Za-z0-9]/.test(newPassword)) {
                e.preventDefault();
                alert('Password must contain at least one uppercase letter, one number, and one special character!');
                document.getElementById('new_password').focus();
            }
        });
    </script>
</body>
</html>