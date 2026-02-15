<?php
require 'config.php';

$search = isset($_GET['q']) ? "%".htmlspecialchars($_GET['q'])."%" : '';

// Search Trainers
$stmt = $pdo->prepare("SELECT * FROM trainers 
                      WHERE name LIKE ? OR specialization LIKE ?");
$stmt->execute([$search, $search]);
$trainers = $stmt->fetchAll();

// Search Blog Posts
$stmt = $pdo->prepare("SELECT * FROM posts 
                      WHERE title LIKE ? OR content LIKE ?");
$stmt->execute([$search, $search]);
$posts = $stmt->fetchAll();
?>