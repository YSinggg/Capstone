<?php
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $meal_type = $_POST['meal_type'];
    $meal_name = $_POST['meal_name'];
    $calories = $_POST['calories'];
    $protein = $_POST['protein'];
    $carbs = $_POST['carbs'];
    $fats = $_POST['fats'];
    $date = date('Y-m-d');

    $stmt = $pdo->prepare("INSERT INTO meals (user_id, meal_type, meal_name, calories, protein, carbs, fats, date) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $meal_type, $meal_name, $calories, $protein, $carbs, $fats, $date]);

    header('Location: nutrition.php');
    exit;
}
?>