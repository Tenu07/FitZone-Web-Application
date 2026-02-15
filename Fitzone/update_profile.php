<?php
require 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Basic validation
    if (empty($name) || empty($email)) {
        $message = '<div class="alert error">Name and email are required</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert error">Please enter a valid email address</div>';
    } else {
        // Check if email already exists (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $message = '<div class="alert error">This email is already registered</div>';
        } else {
            // Update profile
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
            if ($stmt->execute([$name, $email, $phone, $address, $_SESSION['user_id']])) {
                $message = '<div class="alert success">Profile updated successfully!</div>';
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
            } else {
                $message = '<div class="alert error">Error updating profile. Please try again.</div>';
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
    <title>Update Profile - FitZone Fitness Center</title>
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
        
        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .profile-title {
            color: var(--primary-color);
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .profile-title i {
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
        
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        textarea {
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
        
        .success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        
        .profile-photo {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .photo-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            margin-right: 1.5rem;
        }
        
        .photo-upload {
            display: flex;
            flex-direction: column;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .profile-photo {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .photo-preview {
                margin-bottom: 1rem;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <header class="dashboard-header">
        <a href="index.php" class="logo">Fit<span>Zone</span></a>
        <a href="dashboard.php" class="logout-btn">Back to Dashboard</a>
    </header>

    <div class="profile-container">
        <h1 class="profile-title"><i class="fas fa-user-edit"></i> Update Profile</h1>
        
        <?php echo $message; ?>
        
        <div class="profile-photo">
            <img src="<?= !empty($user['profile_photo']) ? 'uploads/profiles/'.$user['profile_photo'] : 'images/default-profile.jpg' ?>" 
                 alt="Profile Photo" class="photo-preview" id="photoPreview">
            <div class="photo-upload">
                <input type="file" id="profilePhoto" accept="image/*" style="display: none;">
                <button type="button" class="btn" onclick="document.getElementById('profilePhoto').click()" 
                        style="margin-bottom: 0.5rem; background-color: #6c757d;">
                    <i class="fas fa-camera"></i> Change Photo
                </button>
                <small>Max 2MB (JPG, PNG)</small>
            </div>
        </div>
        
        <form action="update_profile.php" method="POST" id="profileForm">
            <div class="form-group">
                <label for="name" class="required">Full Name</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email" class="required">Email Address</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
            </div>
            
            <button type="submit" class="btn">Save Changes</button>
            <a href="dashboard.php" class="btn" style="background-color: #6c757d; margin-left: 1rem;">Cancel</a>
        </form>
    </div>

    <script>
        // Profile photo preview
        document.getElementById('profilePhoto').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    return;
                }
                
                if (!['image/jpeg', 'image/png'].includes(file.type)) {
                    alert('Only JPG and PNG images are allowed');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoPreview').src = e.target.result;
                    
                    // Here you would typically upload the image via AJAX
                    // For simplicity, we're just showing the preview
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return false;
            }
            return true;
        });
    </script>
</body>
</html>