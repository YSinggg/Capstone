<?php
session_start();
require 'db.php'; // Create this file for database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $meal_type = $_POST['meal_type'];
    $meal_name = $_POST['meal_name'];
    $meal_time = $_POST['meal_time'];
    $calories = $_POST['calories'];
    $protein = $_POST['protein'];
    $carbs = $_POST['carbs'];
    $fats = $_POST['fats'];
    $serving_size = $_POST['serving_size'] ?? null;
    $notes = $_POST['notes'] ?? null;
    $date = date('Y-m-d');

    try {
        $stmt = $pdo->prepare("INSERT INTO meals (user_id, meal_type, meal_name, meal_time, date, calories, protein, carbs, fats, serving_size, notes) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $meal_type, $meal_name, $meal_time, $date, $calories, $protein, $carbs, $fats, $serving_size, $notes]);
        
        header("Location: diet_plan.php");
        exit;
    } catch (PDOException $e) {
        die("Error adding meal: " . $e->getMessage());
    }
} else {
    header("Location: diet_plan.php");
    exit;
}
?>