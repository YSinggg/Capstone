<?php
session_start();
require 'db.php'; // this should give you $pdo (PDO), not $conn

header('Content-Type: application/json; charset=utf-8');

try {
    // Require login (optional but recommended)
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }

    // Validate id
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid goal ID']);
        exit;
    }

    $goal_id = (int) $_GET['id'];
    $user_id = (int) $_SESSION['user_id'];

    // NOTE: table name is "goals" (plural)
    $stmt = $pdo->prepare(
        "SELECT goal_id, user_id, title, description, target_value, current_value, target_date, status, created_at
         FROM goals
         WHERE goal_id = ? AND user_id = ?"
    );
    $stmt->execute([$goal_id, $user_id]);
    $goal = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($goal) {
        echo json_encode($goal);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Goal not found']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
