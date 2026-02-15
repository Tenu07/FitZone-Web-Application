<?php
require 'config.php';
try {
    $stmt = $pdo->query("SHOW TABLES");
    echo "Database connection successful! Found tables:";
    print_r($stmt->fetchAll());
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>