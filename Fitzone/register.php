<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
     switch ($_SESSION['role']) {
         case 'admin': header("Location: admin_dashboard.php"); exit;
         case 'staff': case 'trainer': case 'manager': header("Location: staff_dashboard.php"); exit;
         case 'customer': header("Location: member_dashboard.php"); exit;
         default: header("Location: logout.php"); exit;
     }
}

require_once __DIR__ . '/config.php';

$error = '';
$success = '';
$name_value = '';
$email_value = '';

// Check if coming from a plan selection
$selected_plan_id = filter_input(INPUT_GET, 'plan', FILTER_VALIDATE_INT);
$selected_plan = null;
if ($selected_plan_id) {
    try {
        $stmt_plan = $pdo->prepare("SELECT id, name, price FROM plans WHERE id = ? AND status = 'active'");
        $stmt_plan->execute([$selected_plan_id]);
        $selected_plan = $stmt_plan->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching selected plan: " . $e->getMessage());
        // Non-critical error, proceed without plan info
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $password = $_POST['password']; // Don't trim password
    $confirm_password = $_POST['confirm_password'];
    $plan_id_form = filter_input(INPUT_POST, 'plan_id', FILTER_VALIDATE_INT); // Get plan ID from hidden field

    // Keep values for sticky form
    $name_value = $name;
    $email_value = $_POST['email']; // Use original POST email

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all required fields.";
    } elseif (!$email) {
        $error = "Invalid email address format.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        try {
            $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt_check->execute([$email]);

            if ($stmt_check->fetch()) {
                $error = "This email address is already registered. Please <a href='login.php'>login</a>.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user with 'customer' role
                $stmt_insert = $pdo->prepare(
                    "INSERT INTO users (name, email, password, role, is_active, created_at, updated_at)
                     VALUES (:name, :email, :password, 'customer', TRUE, NOW(), NOW())" // Default role is customer
                );
                $params = [
                    ':name' => $name,
                    ':email' => $email,
                    ':password' => $hashed_password
                ];

                if ($stmt_insert->execute($params)) {
                    $new_user_id = $pdo->lastInsertId();

                    // If a plan was selected (either via GET or hidden field), create membership
                    $plan_to_assign = $plan_id_form ?: $selected_plan_id;
                    if ($plan_to_assign) {
                        $stmt_get_plan = $pdo->prepare("SELECT duration FROM plans WHERE id = ?");
                        $stmt_get_plan->execute([$plan_to_assign]);
                        $plan_data = $stmt_get_plan->fetch();

                        if ($plan_data) {
                            $start_date = date('Y-m-d');
                            $end_date = date('Y-m-d', strtotime("+{$plan_data['duration']} months"));
                            $stmt_membership = $pdo->prepare(
                                "INSERT INTO memberships (user_id, plan_id, start_date, end_date, status, payment_status)
                                 VALUES (?, ?, ?, ?, 'active', 'paid')" // Assume paid for now, adjust if payment gateway needed
                            );
                             // Add error handling for membership insertion
                            if (!$stmt_membership->execute([$new_user_id, $plan_to_assign, $start_date, $end_date])) {
                                 error_log("Failed to create membership for user ID: $new_user_id, Plan ID: $plan_to_assign");
                                 // Set a session message maybe?
                            }
                        }
                    }

                    // Redirect to login page with success message
                    header("Location: login.php?registered=success");
                    exit;
                } else {
                    $error = "Registration failed. Please try again later.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error during registration. Please try again.";
            error_log("Registration Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - FitZone Fitness Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Re-use styles from login.php */
         :root {
            --primary-color: #00b4d8;
            --primary-dark: #0096b4;
            --secondary-color: #1a1a1a;
            --danger-color: #d9534f;
            --success-color: #5cb85c;
            --light-bg: #f8f9fa;
            --text-color: #333;
            --border-color: #ddd;
         }
        body {
            font-family: 'Poppins', sans-serif; background-color: #f5f5f5; margin: 0; padding: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 1rem 0; /* Add padding for smaller screens */
        }
        .register-container {
            background: white; padding: 2.5rem; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); width: 100%; max-width: 500px; margin: 1rem; /* Add margin */
        }
        .logo-container { text-align: center; margin-bottom: 1rem; }
        .logo { color: var(--secondary-color); font-size: 2rem; font-weight: 700; text-decoration: none; }
        .logo i { margin-right: 0.5rem; color: var(--primary-color); }
        .logo span { color: var(--primary-color); }
        h2 { text-align: center; color: var(--text-color); margin-bottom: 1.5rem; font-weight: 600; }
        .plan-info { background: #e9f7fb; border-left: 4px solid var(--primary-color); padding: 1rem; border-radius: 5px; margin-bottom: 1.5rem; text-align: center; font-size: 0.95rem; }
        .plan-info h3 { color: var(--primary-dark); margin: 0 0 0.5rem 0; font-size: 1.1rem; font-weight: 600; }
        .plan-info p { margin: 0; color: #555; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #555; }
        .form-group input { width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--border-color); border-radius: 5px; box-sizing: border-box; font-family: inherit; transition: border-color 0.3s ease; }
        .form-group input:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(0, 180, 216, 0.2); }
        button { width: 100%; padding: 0.9rem; background-color: var(--primary-color); color: white; border: none; border-radius: 50px; cursor: pointer; font-size: 1rem; font-weight: 500; margin-top: 1rem; transition: background-color 0.3s ease, transform 0.2s ease; text-transform: uppercase; letter-spacing: 1px; }
        button:hover { background-color: var(--primary-dark); transform: translateY(-2px); }
        .alert { text-align: center; margin-bottom: 1rem; padding: 0.8rem; border-radius: 5px; font-size: 0.95rem; }
        .error { color: var(--danger-color); background-color: #fdd; border: 1px solid var(--danger-color); }
        .login-link { text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: #666; }
        .login-link a { color: var(--primary-color); text-decoration: none; font-weight: 500; }
        .login-link a:hover { text-decoration: underline; color: var(--primary-dark); }
         .required:after { content: " *"; color: var(--danger-color); }
    </style>
</head>
<body>
    <div class="register-container">
         <div class="logo-container">
             <a href="index.php" class="logo">
                 <i class="fas fa-dumbbell"></i>Fit<span>Zone</span>
             </a>
         </div>
        <h2>Create Your Account</h2>

        <?php if ($selected_plan): ?>
        <div class="plan-info">
            <h3>Joining with: <?= htmlspecialchars($selected_plan['name']) ?> Plan</h3>
            <p>Rs. <?= number_format($selected_plan['price'], 2) ?> / month</p>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error"><?= $error /* Allow HTML in error for login link */ ?></div>
        <?php endif; ?>

        <form action="register.php<?= $selected_plan_id ? '?plan='.$selected_plan_id : '' ?>" method="POST">
            <input type="hidden" name="plan_id" value="<?= $selected_plan_id ? htmlspecialchars($selected_plan_id) : '' ?>">

            <div class="form-group">
                <label for="name" class="required">Full Name</label>
                <input type="text" id="name" name="name" required value="<?= htmlspecialchars($name_value) ?>">
            </div>
            <div class="form-group">
                <label for="email" class="required">Email Address</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email_value) ?>">
            </div>
            <div class="form-group">
                <label for="password" class="required">Password (min 8 characters)</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>
             <div class="form-group">
                <label for="confirm_password" class="required">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
            </div>
            <button type="submit">Register</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</body>
</html>
