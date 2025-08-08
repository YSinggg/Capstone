<?php
session_start();

// Set JSON content type header at the very beginning
header('Content-Type: application/json');

// Security and validation checks
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST requests allowed']);
    exit;
}

// Get input data (works with both JSON and form-data)
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// Validate CSRF token
if (empty($input['csrf_token']) || $input['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Validate plan ID
$planId = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$planId || $planId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid plan ID']);
    exit;
}

// Database configuration
$host = 'localhost';
$dbname = 'fitness_management';
$username_db = 'root';
$password = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username_db, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // Begin transaction
    $pdo->beginTransaction();

    // 1. Get the plan details
    $stmt = $pdo->prepare("SELECT * FROM meal_plans WHERE id = ?");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch();
    
    if (!$plan) {
        throw new Exception('Meal plan not found');
    }

    // 2. Update user's targets
    $updateStmt = $pdo->prepare("UPDATE users SET 
                                calorie_target = :calories,
                                fitness_goal = :goal
                                WHERE id = :user_id");
    
    $updateStmt->execute([
        ':calories' => $plan['calories'],
        ':goal' => $plan['goal'],
        ':user_id' => $_SESSION['user_id']
    ]);

    // 3. Clear existing meals for today (optional - remove if you want to keep existing meals)
    $deleteStmt = $pdo->prepare("DELETE FROM meals WHERE user_id = ? AND date = CURDATE()");
    $deleteStmt->execute([$_SESSION['user_id']]);

    // 4. Generate and insert sample meals (simplified version)
    $meals = [
        [
            'meal_name' => 'Sample Breakfast',
            'meal_type' => 'breakfast',
            'calories' => round($plan['calories'] * 0.25),
            'protein' => round($plan['protein'] * 0.25),
            'carbs' => round($plan['carbs'] * 0.25),
            'fats' => round($plan['fats'] * 0.25),
            'meal_time' => '08:00:00',
            'notes' => 'Plan: ' . $plan['plan_name']
        ],
        [
            'meal_name' => 'Sample Lunch',
            'meal_type' => 'lunch',
            'calories' => round($plan['calories'] * 0.35),
            'protein' => round($plan['protein'] * 0.35),
            'carbs' => round($plan['carbs'] * 0.35),
            'fats' => round($plan['fats'] * 0.35),
            'meal_time' => '12:30:00',
            'notes' => 'Plan: ' . $plan['plan_name']
        ],
        [
            'meal_name' => 'Sample Dinner',
            'meal_type' => 'dinner',
            'calories' => round($plan['calories'] * 0.30),
            'protein' => round($plan['protein'] * 0.30),
            'carbs' => round($plan['carbs'] * 0.30),
            'fats' => round($plan['fats'] * 0.30),
            'meal_time' => '18:30:00',
            'notes' => 'Plan: ' . $plan['plan_name']
        ],
        [
            'meal_name' => 'Healthy Snack',
            'meal_type' => 'snack',
            'calories' => round($plan['calories'] * 0.10),
            'protein' => round($plan['protein'] * 0.10),
            'carbs' => round($plan['carbs'] * 0.10),
            'fats' => round($plan['fats'] * 0.10),
            'meal_time' => '15:30:00',
            'notes' => 'Plan: ' . $plan['plan_name']
        ]
    ];

    // Insert meals
    $insertStmt = $pdo->prepare("INSERT INTO meals 
                                (user_id, meal_name, meal_type, calories, protein, carbs, fats, date, meal_time, notes) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)");
    
    $insertedCount = 0;
    foreach ($meals as $meal) {
        $insertStmt->execute([
            $_SESSION['user_id'],
            $meal['meal_name'],
            $meal['meal_type'],
            $meal['calories'],
            $meal['protein'],
            $meal['carbs'],
            $meal['fats'],
            $meal['meal_time'],
            $meal['notes']
        ]);
        $insertedCount++;
    }

    // Commit transaction
    $pdo->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Meal plan applied successfully',
        'plan_name' => $plan['plan_name'],
        'meals_added' => $insertedCount,
        'new_targets' => [
            'calories' => $plan['calories'],
            'protein' => $plan['protein'],
            'carbs' => $plan['carbs'],
            'fats' => $plan['fats']
        ]
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit;
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}