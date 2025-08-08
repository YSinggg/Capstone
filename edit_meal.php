<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meal_id = $_POST['meal_id'];
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

    try {
        // Verify the meal belongs to the user before updating
        $stmt = $pdo->prepare("UPDATE meals SET 
            meal_type = ?, 
            meal_name = ?, 
            meal_time = ?, 
            calories = ?, 
            protein = ?, 
            carbs = ?, 
            fats = ?, 
            serving_size = ?, 
            notes = ?
            WHERE id = ? AND user_id = ?");
        
        $stmt->execute([$meal_type, $meal_name, $meal_time, $calories, $protein, $carbs, $fats, $serving_size, $notes, $meal_id, $user_id]);
        
        header("Location: diet_plan.php");
        exit;
    } catch (PDOException $e) {
        die("Error updating meal: " . $e->getMessage());
    }
} else {
    header("Location: diet_plan.php");
    exit;
}
?>