<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $meal_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM meals WHERE id = ? AND user_id = ?");
        $stmt->execute([$meal_id, $user_id]);
        $meal = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($meal) {
            header('Content-Type: application/json');
            echo json_encode($meal);
        } else {
            http_response_code(404); // Not found
        }
    } catch (PDOException $e) {
        http_response_code(500); // Server error
        die("Error fetching meal: " . $e->getMessage());
    }
} else {
    http_response_code(400); // Bad request
    exit;
}
?>