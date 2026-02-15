<?php
require 'config.php';

// Get all active trainers with their upcoming classes
$trainers = [];
try {
    $stmt = $pdo->prepare("SELECT u.*, 
                          (SELECT COUNT(*) FROM classes WHERE trainer_id = u.id AND status = 'upcoming') as class_count
                          FROM users u
                          WHERE u.role = 'trainer' AND u.is_active = 1
                          ORDER BY u.name");
    $stmt->execute();
    $trainers = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error loading trainers: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Trainers - FitZone</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'header.php'; ?>
    
    <div class="container mx-auto px-4 py-12">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Meet Our Expert Trainers</h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">Our certified professionals are here to guide you on your fitness journey</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 max-w-4xl mx-auto">
                <?= $error ?>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($trainers as $trainer): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden transition-transform duration-300 hover:scale-105">
                    <div class="h-64 overflow-hidden">
                        <img src="images/trainers/<?= htmlspecialchars($trainer['profile_photo'] ?? 'default-trainer.jpg') ?>" 
                             alt="<?= htmlspecialchars($trainer['name']) ?>" 
                             class="w-full h-full object-cover">
                    </div>
                    
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($trainer['name']) ?></h3>
                                <p class="text-blue-600 font-medium">
                                    <?= htmlspecialchars($trainer['position'] ?? 'Trainer') ?>
                                </p>
                            </div>
                            <span class="bg-blue-100 text-blue-800 text-sm font-semibold px-3 py-1 rounded-full">
                                <?= $trainer['class_count'] ?> classes
                            </span>
                        </div>
                        
                        <p class="text-gray-600 mb-6"><?= htmlspecialchars($trainer['fitness_goals'] ?? 'No bio available') ?></p>
                        
                        <div class="flex space-x-3">
                            <a href="trainer_details.php?id=<?= $trainer['id'] ?>" 
                               class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center font-medium py-2 px-4 rounded-lg transition duration-300">
                                View Profile
                            </a>
                            <a href="classes.php?trainer=<?= $trainer['id'] ?>" 
                               class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 text-center font-medium py-2 px-4 rounded-lg transition duration-300">
                                View Classes
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>
