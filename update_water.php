<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $glasses = $_POST['glasses'];
    $date = date('Y-m-d');

    try {
        // Check if entry exists for today
        $stmt = $pdo->prepare("SELECT id FROM water_intake WHERE user_id = ? AND date = ?");
        $stmt->execute([$user_id, $date]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing entry
            $stmt = $pdo->prepare("UPDATE water_intake SET glasses = glasses + ? WHERE id = ?");
            $stmt->execute([$glasses, $existing['id']]);
        } else {
            // Create new entry
            $stmt = $pdo->prepare("INSERT INTO water_intake (user_id, glasses, date) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $glasses, $date]);
        }
        
        header('Location: index.php');
        exit;
    } catch (PDOException $e) {
        die("Error updating water intake: " . $e->getMessage());
    }
} else {
    header('Location: index.php');
    exit;
}
?>