<?php
require_once __DIR__ . '/config.php'; // Use require_once and absolute path
require_role(['admin', 'staff', 'manager']); // Allow Admin, Staff, or Manager

$class_to_edit = null;
$class_id = null;
$trainers = [];
$error = $_SESSION['error'] ?? null; // Get error from session if redirected
$success = $_SESSION['success'] ?? null; // Get success message from session
unset($_SESSION['error'], $_SESSION['success']); // Clear messages after displaying

// --- Fetch Class Data for Editing ---
if (isset($_GET['id'])) {
    $class_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($class_id) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
            $stmt->execute([$class_id]);
            $class_to_edit = $stmt->fetch();

            if (!$class_to_edit) {
                $_SESSION['error'] = "Class not found.";
                 // Redirect back based on user role
                 $redirect_url = has_role('admin') ? 'admin_dashboard.php#class-management' : 'staff_dashboard.php#class-management';
                 header("Location: " . $redirect_url);
                exit;
            }

            // Fetch available trainers (users with role 'trainer')
            $stmt_trainers = $pdo->query("SELECT id, name FROM users WHERE role = 'trainer' AND is_active = TRUE ORDER BY name");
            $trainers = $stmt_trainers->fetchAll();

        } catch (PDOException $e) {
            $error = "Error fetching class data: " . $e->getMessage();
            error_log($error);
        }
    } else {
        $_SESSION['error'] = "Invalid Class ID.";
         $redirect_url = has_role('admin') ? 'admin_dashboard.php#class-management' : 'staff_dashboard.php#class-management';
         header("Location: " . $redirect_url);
        exit;
    }
} else {
     $_SESSION['error'] = "No Class ID specified for editing.";
      $redirect_url = has_role('admin') ? 'admin_dashboard.php#class-management' : 'staff_dashboard.php#class-management';
      header("Location: " . $redirect_url);
     exit;
}

// --- Handle Form Submission (Update Class) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_class'])) {
     // Validate hidden class ID
    $submitted_class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
    if ($submitted_class_id !== $class_id) {
        $_SESSION['error'] = "Form submission error. Please try again.";
        $redirect_url = has_role('admin') ? 'admin_dashboard.php#class-management' : 'staff_dashboard.php#class-management';
        header("Location: " . $redirect_url);
        exit;
    }

    // Sanitize and validate inputs
    $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING));
    $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
    $trainer_id = filter_input(INPUT_POST, 'trainer_id', FILTER_VALIDATE_INT); // Can be null/empty
    $start_time_str = $_POST['start_time'] ?? '';
    $end_time_str = $_POST['end_time'] ?? '';
    $capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT);
    $difficulty = filter_input(INPUT_POST, 'difficulty', FILTER_SANITIZE_STRING);
    $image_url = trim(filter_input(INPUT_POST, 'image_url', FILTER_SANITIZE_URL)); // Basic URL sanitize
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);


    // Basic Validation
    if (empty($title) || empty($start_time_str) || empty($end_time_str) || $capacity === false || $capacity < 1) {
        $error = "Class Title, Start Time, End Time, and a valid Capacity are required.";
    } elseif (!in_array($difficulty, ['beginner', 'intermediate', 'advanced', 'all_levels'])) {
        $error = "Invalid Difficulty selected.";
    } elseif (!in_array($status, ['upcoming', 'completed', 'cancelled'])) {
         $error = "Invalid Status selected.";
    } else {
        // Validate times
        $start_time = strtotime($start_time_str);
        $end_time = strtotime($end_time_str);

        if ($start_time === false || $end_time === false) {
            $error = "Invalid Start or End Time format.";
        } elseif ($end_time <= $start_time) {
            $error = "End Time must be after Start Time.";
        } else {
            // Format times for database
            $start_time_db = date('Y-m-d H:i:s', $start_time);
            $end_time_db = date('Y-m-d H:i:s', $end_time);

            // Prepare SQL Update
            try {
                $sql = "UPDATE classes SET
                            title = :title,
                            description = :description,
                            trainer_id = :trainer_id,
                            start_time = :start_time,
                            end_time = :end_time,
                            capacity = :capacity,
                            difficulty = :difficulty,
                            image = :image,
                            status = :status
                        WHERE id = :id";

                $stmt_update = $pdo->prepare($sql);
                $params = [
                    ':title' => $title,
                    ':description' => $description ?: null,
                    ':trainer_id' => $trainer_id ?: null, // Allow unassigned trainer
                    ':start_time' => $start_time_db,
                    ':end_time' => $end_time_db,
                    ':capacity' => $capacity,
                    ':difficulty' => $difficulty,
                    ':image' => $image_url ?: null,
                    ':status' => $status,
                    ':id' => $class_id
                ];

                if ($stmt_update->execute($params)) {
                    $_SESSION['success'] = "Class details updated successfully!";
                     $redirect_url = has_role('admin') ? 'admin_dashboard.php#class-management' : 'staff_dashboard.php#class-management';
                     header("Location: " . $redirect_url);
                    exit;
                } else {
                    $error = "Failed to update class details. Please try again.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
                error_log($error);
            }
        }
    }
     // If there was an error, repopulate the $class_to_edit array with submitted values
      if ($error) {
          $class_to_edit['title'] = $title;
          $class_to_edit['description'] = $description;
          $class_to_edit['trainer_id'] = $trainer_id;
          $class_to_edit['start_time'] = $start_time_str; // Keep original string format
          $class_to_edit['end_time'] = $end_time_str;   // Keep original string format
          $class_to_edit['capacity'] = $_POST['capacity']; // Keep original POST capacity
          $class_to_edit['difficulty'] = $difficulty;
          $class_to_edit['image'] = $image_url;
          $class_to_edit['status'] = $status;
      }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Class - FitZone Admin/Staff</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <style>
        /* Basic Admin Form Styles - Re-use from edit_staff.php */
        :root {
            --primary-color: #00b4d8;
            --secondary-color: #6c757d;
            --danger-color: #d9534f;
            --success-color: #5cb85c;
            --light-bg: #f8f9fa;
            --border-color: #dee2e6;
        }
        body { font-family: 'Poppins', sans-serif; background-color: #f5f7fa; margin: 0; padding: 0; }
        .container { max-width: 700px; margin: 2rem auto; padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: var(--primary-color); margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; font-weight: 600; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #495057; }
        input[type="text"], input[type="number"], input[type="url"], input[type="datetime-local"], select, textarea {
            width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 4px; font-size: 1rem; box-sizing: border-box; transition: border-color 0.3s; font-family: inherit;
        }
         input:focus, select:focus, textarea:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(0, 180, 216, 0.2); }
        textarea { min-height: 80px; resize: vertical; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; border: none; border-radius: 50px; cursor: pointer; font-size: 0.95rem; font-weight: 500; text-decoration: none; transition: all 0.3s; }
        .btn-primary { background-color: var(--primary-color); color: white; }
        .btn-primary:hover { background-color: #0096b4; transform: translateY(-2px); }
        .btn-secondary { background-color: var(--secondary-color); color: white; margin-left: 0.5rem; }
        .btn-secondary:hover { background-color: #5a6268; transform: translateY(-2px); }
        .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px; border: 1px solid transparent; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .alert-success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .required:after { content: " *"; color: var(--danger-color); }
        .input-group { display: flex; gap: 1rem; }
        .input-group > .form-group { flex: 1; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Class: <?= htmlspecialchars($class_to_edit['title'] ?? 'N/A') ?></h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($class_to_edit): ?>
            <form method="POST" action="edit_class.php?id=<?= $class_id ?>">
                <input type="hidden" name="class_id" value="<?= $class_id ?>">

                <div class="form-group">
                    <label for="title" class="required">Class Title</label>
                    <input type="text" id="title" name="title" required value="<?= htmlspecialchars($class_to_edit['title'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"><?= htmlspecialchars($class_to_edit['description'] ?? '') ?></textarea>
                </div>

                 <div class="form-group">
                    <label for="trainer_id">Trainer</label>
                    <select id="trainer_id" name="trainer_id">
                        <option value="">-- Select Trainer (Optional) --</option>
                        <?php foreach ($trainers as $trainer): ?>
                            <option value="<?= $trainer['id'] ?>"
                                <?= (isset($class_to_edit['trainer_id']) && $class_to_edit['trainer_id'] == $trainer['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($trainer['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                 <div class="input-group">
                     <div class="form-group">
                        <label for="start_time" class="required">Start Time</label>
                        <input type="datetime-local" id="start_time" name="start_time" required
                               value="<?= isset($class_to_edit['start_time']) ? date('Y-m-d\TH:i', strtotime($class_to_edit['start_time'])) : '' ?>">
                    </div>
                     <div class="form-group">
                        <label for="end_time" class="required">End Time</label>
                        <input type="datetime-local" id="end_time" name="end_time" required
                               value="<?= isset($class_to_edit['end_time']) ? date('Y-m-d\TH:i', strtotime($class_to_edit['end_time'])) : '' ?>">
                    </div>
                 </div>


                <div class="input-group">
                    <div class="form-group">
                        <label for="capacity" class="required">Capacity</label>
                        <input type="number" id="capacity" name="capacity" min="1" required value="<?= htmlspecialchars($class_to_edit['capacity'] ?? '20') ?>">
                    </div>

                    <div class="form-group">
                        <label for="difficulty" class="required">Difficulty</label>
                        <select id="difficulty" name="difficulty" required>
                            <option value="beginner" <?= ($class_to_edit['difficulty'] ?? '') == 'beginner' ? 'selected' : '' ?>>Beginner</option>
                            <option value="intermediate" <?= ($class_to_edit['difficulty'] ?? '') == 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                            <option value="advanced" <?= ($class_to_edit['difficulty'] ?? '') == 'advanced' ? 'selected' : '' ?>>Advanced</option>
                            <option value="all_levels" <?= ($class_to_edit['difficulty'] ?? 'all_levels') == 'all_levels' ? 'selected' : '' ?>>All Levels</option>
                        </select>
                    </div>
                 </div>

                 <div class="form-group">
                    <label for="status" class="required">Status</label>
                    <select id="status" name="status" required>
                        <option value="upcoming" <?= ($class_to_edit['status'] ?? '') == 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                        <option value="completed" <?= ($class_to_edit['status'] ?? '') == 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= ($class_to_edit['status'] ?? '') == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                 <div class="form-group">
                    <label for="image_url">Image URL (Optional)</label>
                    <input type="url" id="image_url" name="image_url" value="<?= htmlspecialchars($class_to_edit['image'] ?? '') ?>" placeholder="https://example.com/image.jpg">
                </div>


                <button type="submit" name="update_class" class="btn btn-primary">Save Changes</button>
                 <?php
                    // Determine redirect URL based on role
                    $cancel_redirect_url = 'staff_dashboard.php#class-management'; // Default for staff/manager
                    if (has_role('admin')) {
                        $cancel_redirect_url = 'admin_dashboard.php#class-management';
                    }
                 ?>
                <a href="<?= $cancel_redirect_url ?>" class="btn btn-secondary">Cancel</a>
            </form>
        <?php else: ?>
            <p>Could not load class data.</p>
             <?php
                $back_redirect_url = has_role('admin') ? 'admin_dashboard.php#class-management' : 'staff_dashboard.php#class-management';
             ?>
             <a href="<?= $back_redirect_url ?>" class="btn btn-secondary">Back to Dashboard</a>
        <?php endif; ?>
    </div>
</body>
</html>
