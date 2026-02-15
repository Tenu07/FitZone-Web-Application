<?php
require 'config.php';

// Get trainer ID from URL
$trainerId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch trainer info
$trainer = null;
$classes = [];

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'trainer' AND is_active = 1");
    $stmt->execute([$trainerId]);
    $trainer = $stmt->fetch();

    if ($trainer) {
        // Fetch upcoming classes by this trainer
        $classStmt = $pdo->prepare("SELECT * FROM classes WHERE trainer_id = ? AND status = 'upcoming' ORDER BY start_time ASC");
        $classStmt->execute([$trainerId]);
        $classes = $classStmt->fetchAll();
    } else {
        $error = "Trainer not found.";
    }
} catch (PDOException $e) {
    $error = "Error loading trainer profile: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Profile - FitZone</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'header.php'; ?>

    <div class="container mx-auto px-4 py-10">
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?= $error ?>
            </div>
        <?php elseif ($trainer): ?>
            <div class="bg-white rounded-xl shadow-md p-8 flex flex-col md:flex-row items-center md:items-start space-y-6 md:space-y-0 md:space-x-10">
                <img src="images/trainers/<?= htmlspecialchars($trainer['profile_photo'] ?? 'default-trainer.jpg') ?>" 
                     alt="<?= htmlspecialchars($trainer['name']) ?>" 
                     class="w-48 h-48 object-cover rounded-full border-4 border-blue-600">

                <div>
                    <h2 class="text-3xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($trainer['name']) ?></h2>
                    <p class="text-blue-600 font-semibold mb-2"><?= htmlspecialchars($trainer['position'] ?? 'Trainer') ?></p>
                    <p class="text-gray-700 mb-4"><strong>Phone:</strong> <?= htmlspecialchars($trainer['phone'] ?? 'N/A') ?></p>
                    <p class="text-gray-600"><?= nl2br(htmlspecialchars($trainer['fitness_goals'] ?? 'No bio available.')) ?></p>
                </div>
            </div>

            <div class="mt-10">
                <h3 class="text-2xl font-bold text-gray-800 mb-4">Upcoming Classes</h3>

                <?php if (!empty($classes)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($classes as $class): ?>
                            <div class="bg-white shadow rounded-lg p-4">
                                <h4 class="text-xl font-semibold text-gray-800 mb-2"><?= htmlspecialchars($class['title']) ?></h4>
                                <p class="text-gray-600 mb-1"><strong>Date:</strong> <?= date('F j, Y', strtotime($class['start_time'])) ?></p>
                                <p class="text-gray-600 mb-1"><strong>Time:</strong> <?= date('g:i A', strtotime($class['start_time'])) ?> - <?= date('g:i A', strtotime($class['end_time'])) ?></p>
                                <p class="text-gray-600"><?= htmlspecialchars($class['description'] ?? '') ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">No upcoming classes at the moment.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
