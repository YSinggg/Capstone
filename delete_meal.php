<?php
session_start();

// Security checks
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Not logged in']));
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Invalid CSRF token']));
}

// Validate meal ID
$mealId = filter_var($_POST['meal_id'], FILTER_VALIDATE_INT);
if ($mealId === false || $mealId <= 0) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Invalid meal ID']));
}

// Database connection
$host = 'localhost';
$dbname = 'fitness_management';
$username_db = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username_db, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

try {
    // Verify the meal belongs to the current user
    $stmt = $pdo->prepare("SELECT id FROM meals WHERE id = ? AND user_id = ?");
    $stmt->execute([$mealId, $_SESSION['user_id']]);
    $meal = $stmt->fetch();
    
    if (!$meal) {
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'message' => 'Meal not found or not authorized']));
    }

    // Delete the meal
    $stmt = $pdo->prepare("DELETE FROM meals WHERE id = ?");
    $stmt->execute([$mealId]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Meal deleted successfully']);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]));
}