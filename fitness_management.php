<?php
session_start();
require_once 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['fullname'] ?? 'User';
$user_weight = $_SESSION['weight'] ?? 70; // Default to 70kg if weight not set

// Initialize variables with default values
$workouts_this_week = 0;
$workout_change = 0;
$calories_burned = 0;
$goals_progress = 0;
$recent_workouts = [];
$user_goals = [];
$todays_plan = [];
$community_posts = [];
$selected_plan = null;

// Handle AJAX request for workout plans
if (isset($_GET['selected_date']) && isset($_GET['ajax'])) {
    $selected_date = $_GET['selected_date'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM workout_plans 
                             WHERE user_id = ? AND plan_date = ?");
        $stmt->execute([$user_id, $selected_date]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        if ($plan) {
            echo json_encode([
                'morning_routine' => $plan['morning_routine'],
                'evening_activity' => $plan['evening_activity'],
                'is_completed' => (bool)$plan['is_completed'],
                'plan_date' => $plan['plan_date']
            ]);
        } else {
            echo json_encode(['empty' => true]);
        }
        exit();
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}

// Handle AJAX request for monthly plans
if (isset($_GET['month']) && isset($_GET['year']) && isset($_GET['get_plans'])) {
    $month = intval($_GET['month']);
    $year = intval($_GET['year']);
    
    try {
        $startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $stmt = $pdo->prepare("SELECT plan_date FROM workout_plans 
                              WHERE user_id = ? 
                              AND plan_date BETWEEN ? AND ?");
        $stmt->execute([$user_id, $startDate, $endDate]);
        $plans = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        header('Content-Type: application/json');
        echo json_encode($plans);
        exit();
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}

// Calculate weekly stats
try {
    // Workouts this week
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM workouts 
                          WHERE user_id = ? 
                          AND workout_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()");
    $stmt->execute([$user_id]);
    $workouts_this_week = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Workouts last week
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM workouts 
                          WHERE user_id = ? 
                          AND workout_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $stmt->execute([$user_id]);
    $workouts_last_week = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $workout_change = $workouts_this_week - $workouts_last_week;

    // Calories burned this week
    $stmt = $pdo->prepare("SELECT SUM(calories_burned) as total FROM workouts 
                          WHERE user_id = ? 
                          AND workout_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()");
    $stmt->execute([$user_id]);
    $calories_burned = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Goals progress
    $stmt = $pdo->prepare("SELECT 
                            SUM(status = 'completed') as completed,
                            COUNT(*) as total 
                          FROM goals 
                          WHERE user_id = ? AND status IN ('active', 'completed')");
    $stmt->execute([$user_id]);
    $goals_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $goals_completed = $goals_data['completed'] ?? 0;
    $goals_total = $goals_data['total'] ?? 1;
    $goals_progress = $goals_total > 0 ? round(($goals_completed / $goals_total) * 100) : 0;
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching dashboard data: " . $e->getMessage();
}

// Handle workout submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_workout'])) {
    try {
        $workout_type = $_POST['workout_type'];
        $workout_date = $_POST['workout_date'];
        $duration = filter_input(INPUT_POST, 'workout_duration', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);
        $calories = isset($_POST['calories_burned']) ? filter_input(INPUT_POST, 'calories_burned', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0]
        ]) : null;
        $notes = htmlspecialchars($_POST['workout_notes'] ?? '');

        if ($duration === false) {
            throw new Exception("Invalid duration value");
        }

        $stmt = $pdo->prepare("INSERT INTO workouts (user_id, workout_type, workout_date, duration_minutes, calories_burned, notes) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $workout_type, $workout_date, $duration, $calories, $notes]);
        $_SESSION['success'] = "Workout logged successfully!";
        header("Location: fitness_management.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error logging workout: " . $e->getMessage();
    }
}

// Handle workout deletion
if (isset($_GET['delete_workout'])) {
    try {
        $workout_id = filter_input(INPUT_GET, 'delete_workout', FILTER_VALIDATE_INT);
        if ($workout_id === false || $workout_id === null) {
            throw new Exception("Invalid workout ID");
        }

        $stmt = $pdo->prepare("DELETE FROM workouts WHERE workout_id = ? AND user_id = ?");
        $stmt->execute([$workout_id, $user_id]);
        $_SESSION['success'] = "Workout deleted successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting workout: " . $e->getMessage();
    }
    header("Location: fitness_management.php");
    exit();
}

// Handle goal submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_goal'])) {
    try {
        $title = htmlspecialchars($_POST['goal_title'] ?? '');
        $description = htmlspecialchars($_POST['goal_description'] ?? '');
        $target_value = isset($_POST['target_value']) ? filter_input(INPUT_POST, 'target_value', FILTER_VALIDATE_FLOAT) : null;
        $target_date = $_POST['target_date'] ?? null;

        if (empty($title)) {
            throw new Exception("Goal title is required");
        }

        $stmt = $pdo->prepare("INSERT INTO goals (user_id, title, description, target_value, target_date) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $description, $target_value, $target_date]);
        $_SESSION['success'] = "Goal added successfully!";
        header("Location: fitness_management.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error adding goal: " . $e->getMessage();
    }
}

// Handle goal completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_complete'])) {
    try {
        $goal_id = filter_input(INPUT_POST, 'goal_id', FILTER_VALIDATE_INT);
        
        if ($goal_id === false || $goal_id === null) {
            throw new Exception("Invalid goal ID");
        }

        $stmt = $pdo->prepare("UPDATE goals 
                              SET status = 'completed', 
                                  current_value = CASE 
                                      WHEN target_value IS NOT NULL THEN target_value 
                                      ELSE current_value 
                                  END
                              WHERE goal_id = ? AND user_id = ?");
        $stmt->execute([$goal_id, $user_id]);
        
        $_SESSION['success'] = "Goal marked as complete!";
        header("Location: fitness_management.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error completing goal: " . $e->getMessage();
        header("Location: fitness_management.php");
        exit();
    }
}

// Handle goal editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_goal'])) {
    try {
        $goal_id = filter_input(INPUT_POST, 'goal_id', FILTER_VALIDATE_INT);
        $title = htmlspecialchars($_POST['goal_title'] ?? '');
        $description = htmlspecialchars($_POST['goal_description'] ?? '');
        $target_value = isset($_POST['target_value']) ? filter_input(INPUT_POST, 'target_value', FILTER_VALIDATE_FLOAT) : null;
        $current_value = isset($_POST['current_value']) ? filter_input(INPUT_POST, 'current_value', FILTER_VALIDATE_FLOAT) : null;
        $target_date = $_POST['target_date'] ?? null;
        $status = $_POST['status'] ?? 'active';

        if (empty($title)) {
            throw new Exception("Goal title is required");
        }

        $stmt = $pdo->prepare("UPDATE goals 
                              SET title = ?, 
                                  description = ?, 
                                  target_value = ?, 
                                  current_value = ?, 
                                  target_date = ?, 
                                  status = ?
                              WHERE goal_id = ? AND user_id = ?");
        $stmt->execute([$title, $description, $target_value, $current_value, $target_date, $status, $goal_id, $user_id]);
        
        $_SESSION['success'] = "Goal updated successfully!";
        header("Location: fitness_management.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating goal: " . $e->getMessage();
        header("Location: fitness_management.php");
        exit();
    }
}

// Handle workout plan update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_plan'])) {
    try {
        $plan_date = $_POST['plan_date'];
        $morning_routine = htmlspecialchars($_POST['morning_routine'] ?? '');
        $evening_activity = htmlspecialchars($_POST['evening_activity'] ?? '');
        $is_completed = isset($_POST['is_completed']) ? 1 : 0;

        // Check if plan exists
        $stmt = $pdo->prepare("SELECT plan_id FROM workout_plans WHERE user_id = ? AND plan_date = ?");
        $stmt->execute([$user_id, $plan_date]);
        
        if ($stmt->rowCount() > 0) {
            // Update existing plan
            $stmt = $pdo->prepare("UPDATE workout_plans 
                                  SET morning_routine = ?, evening_activity = ?, is_completed = ?
                                  WHERE user_id = ? AND plan_date = ?");
            $stmt->execute([$morning_routine, $evening_activity, $is_completed, $user_id, $plan_date]);
        } else {
            // Insert new plan
            $stmt = $pdo->prepare("INSERT INTO workout_plans (user_id, plan_date, morning_routine, evening_activity, is_completed)
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $plan_date, $morning_routine, $evening_activity, $is_completed]);
        }
        $_SESSION['success'] = "Workout plan updated successfully!";
        header("Location: fitness_management.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating workout plan: " . $e->getMessage();
    }
}

// Handle post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_post'])) {
    $content = htmlspecialchars($_POST['post_content'] ?? '');
    
    if (empty($content)) {
        $_SESSION['error'] = "Post content cannot be empty";
        header("Location: fitness_management.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO community_posts (user_id, content) VALUES (?, ?)");
        $stmt->execute([$user_id, $content]);
        $_SESSION['success'] = "Post added successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding post: " . $e->getMessage();
    }
    header("Location: fitness_management.php");
    exit();
}

// Handle post like
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_post'])) {
    $post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    
    if ($post_id === false || $post_id === null) {
        $_SESSION['error'] = "Invalid post ID";
        header("Location: fitness_management.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE community_posts SET likes = likes + 1 WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $_SESSION['success'] = "Post liked!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error liking post: " . $e->getMessage();
    }
    header("Location: fitness_management.php");
    exit();
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    $content = htmlspecialchars($_POST['comment_content'] ?? '');
    
    if ($post_id === false || $post_id === null) {
        $_SESSION['error'] = "Invalid post ID";
        header("Location: fitness_management.php");
        exit();
    }
    
    if (empty($content)) {
        $_SESSION['error'] = "Comment cannot be empty";
        header("Location: fitness_management.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $user_id, $content]);
        $_SESSION['success'] = "Comment added successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding comment: " . $e->getMessage();
    }
    header("Location: fitness_management.php");
    exit();
}
// Get user's recent workouts
try {
    $stmt = $pdo->prepare("SELECT * FROM workouts 
                          WHERE user_id = ? 
                          ORDER BY workout_date DESC, created_at DESC
                          LIMIT 5");
    $stmt->execute([$user_id]);
    $recent_workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching workouts: " . $e->getMessage();
}

// Get user's goals
try {
    $stmt = $pdo->prepare("SELECT * FROM goals 
                          WHERE user_id = ?
                          ORDER BY target_date ASC, created_at DESC");
    $stmt->execute([$user_id]);
    $user_goals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching goals: " . $e->getMessage();
}

// Get workout plan for today or selected date
$today = date('Y-m-d');
$selected_date = isset($_GET['selected_date']) ? $_GET['selected_date'] : $today;

try {
    $stmt = $pdo->prepare("SELECT * FROM workout_plans 
                          WHERE user_id = ? AND plan_date = ?");
    $stmt->execute([$user_id, $selected_date]);
    $selected_plan = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching workout plan: " . $e->getMessage();
}

// Get community posts
try {
    $stmt = $pdo->prepare("SELECT cp.*, u.fullname as author_name 
                          FROM community_posts cp
                          JOIN users u ON cp.user_id = u.id
                          ORDER BY cp.created_at DESC
                          LIMIT 3");
    $stmt->execute();
    $community_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching community posts: " . $e->getMessage();
}

// Display success/error messages
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

include 'headerFM.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Management | Kurus+</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #4a8fe7;
            --secondary: #44c767;
            --accent: #ff6b6b;
            --dark: #5b81a8ff;
            --light: #f8f9fa;
            --text: #333;
            --gray: #6c757d;
            --white: #ffffff;
            --goal-completed-bg: #d4edda;
            --goal-completed-text: #155724;
            --goal-pending-bg: #fff3cd;
            --goal-pending-text: #856404;
        }

        /* Dark Mode Variables */
        [data-theme="dark"] {
            --primary: #5d9cec;
            --secondary: #28a745;
            --accent: #ff6b6b;
            --dark: #5b81a8ff; /* Light text */
            --light: #3c3c3cff; /* Dark background */
            --text: #f8f9fa;
            --gray: #95a5a6;
            --white: #2d2d2d; /* Dark "white" */
            --goal-completed-bg: #1e5631;
            --goal-completed-text: #d4edda;
            --goal-pending-bg: #5a4a1a;
            --goal-pending-text: #fff3cd;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light);
            color: var(--text);
            line-height: 1.6;
            transition: background-color 0.3s, color 0.3s;
        }
        
        .main-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray);
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .page-header p {
            color: var(--gray);
            font-size: 1.1rem;
        }
        
        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .dashboard-card {
            background: var(--white);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            background: rgba(68, 199, 103, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }
        
        .card-icon i {
            font-size: 1.5rem;
            color: var(--secondary);
        }
        
        .card-title {
            font-size: 1.3rem;
            color: var(--dark);
        }
        
        /* Workout Tracking Section */
        .workout-form {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--gray);
            border-radius: 5px;
            font-family: inherit;
            font-size: 1rem;
            background-color: var(--white);
            color: var(--text);
            transition: border-color 0.3s, background-color 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
        }

        body.dark-mode .text-muted{
            color: #fff !important;
        }
        
        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #3a7bd5;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: var(--secondary);
        }
        
        .btn-secondary:hover {
            background: #3aa856;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        /* Progress Charts Section */
        .chart-container {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .chart-placeholder {
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--light);
            border-radius: 5px;
            margin-top: 1rem;
        }
        
        /* Workout Planner Section */
        .planner-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 768px) {
            .planner-container {
                grid-template-columns: 1fr;
            }
        }
        
        .calendar {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .calendar-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .calendar-nav button {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.3rem 0.6rem;
            color: var(--dark);
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
        }
        
        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            padding: 0.5rem;
            color: var(--dark);
        }
        
        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            cursor: pointer;
            position: relative;
            color: var(--text);
        }
        
        .calendar-day:hover {
            background: var(--gray);
        }
        
        .calendar-day.active {
            background: var(--primary);
            color: white;
        }
        
        .calendar-day.selected {
            background-color: var(--primary);
            color: white;
            font-weight: bold;
        }
        
        .calendar-day.today {
            border: 2px solid var(--secondary);
        }
        
        .calendar-day.has-plan::after {
            content: '';
            position: absolute;
            bottom: 5px;
            left: 50%;
            transform: translateX(-50%);
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: var(--secondary);
        }
        
        .prev-month-day, .next-month-day {
            color: var(--gray) !important;
            opacity: 0.5;
        }
        
        /* Workout Plan Cards */
        .workout-plans-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .plan-card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 1.5rem;
        }

        .plan-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .plan-card-header h4 {
            color: var(--dark);
            margin: 0;
        }

        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge.completed {
            background: var(--goal-completed-bg);
            color: var(--goal-completed-text);
        }

        .badge.pending {
            background: var(--goal-pending-bg);
            color: var(--goal-pending-text);
        }

        .plan-card-content {
            white-space: pre-line;
            color: var(--text);
        }

        .empty-plan {
            text-align: center;
            padding: 2rem;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
            cursor: pointer;
            color: var(--text);
        }
        
        /* Community Section */

        body.dark-mode input::placeholder,
        body.dark-mode textarea::placeholder {
            color: #fff; /* white placeholder for dark mode */
        }

        .community-container {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .add-post-form {
            margin: 1.5rem 0;
            padding: 1rem;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .community-posts {
            margin-top: 1.5rem;
        }
        
        .post {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .post-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--secondary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 1rem;
        }
        
        .post-author {
            font-weight: 600;
            color: var(--dark);
        }
        
        .post-time {
            color: var(--gray);
            font-size: 0.8rem;
        }
        
        .post-content {
            color: var(--text);
        }

        .like-form {
            display: inline-block;
            margin-top: 0.5rem;
        }

        .like-button {
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
            padding: 0;
            font-size: 0.9rem;
        }

        .like-button:hover {
            color: var(--accent);
        }

        .comments-section {
            margin-top: 1rem;
            padding-left: 50px;
        }

        .comment {
            margin-bottom: 1rem;
            padding: 0.5rem;
            background: var(--light);
            border-radius: 5px;
        }

        .comment-header {
            display: flex;
            align-items: center;
            margin-bottom: 0.3rem;
        }

        .comment-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
            margin-right: 0.5rem;
        }

        .comment-author {
            font-weight: 600;
            font-size: 0.9rem;
            margin-right: 0.5rem;
            color: var(--dark);
        }

        .comment-time {
            color: var(--gray);
            font-size: 0.8rem;
        }

        .comment-content {
            font-size: 0.9rem;
            margin-left: 35px;
            color: var(--text);
        }

        .add-comment-form {
            margin-top: 0.5rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: var(--white);
            margin: 10% auto;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            color: var(--text);
        }
        
        .close {
            color: var(--gray);
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: var(--dark);
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: var(--goal-completed-bg);
            color: var(--goal-completed-text);
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
        }
        
        th {
            background: var(--primary);
            color: white;
            text-align: left;
            padding: 1rem;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid var(--light);
            color: var(--text);
        }
        
        tr:hover {
            background-color: rgba(0,0,0,0.05);
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
        }
        
        #returnToToday {
            display: none;
            margin-bottom: 1rem;
        }
        
        .plan-date-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        /* Dark mode specific overrides */
        [data-theme="dark"] .alert-error {
            background-color: #5a1a1a;
            color: #f8d7da;
        }
        
        [data-theme="dark"] tr:hover {
            background-color: rgba(255,255,255,0.05);
        }
        
        
        
    </style>
</head>
<body>
    <div class="main-container">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h1>Fitness Management</h1>
            <p>Track your workouts, monitor progress, plan routines, and connect with the community</p>
        </div>
        
        <!-- Dashboard Overview -->
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <h2 class="card-title">Workouts This Week</h2>
                </div>
                <div style="font-size: 2rem; font-weight: 700; color: var(--secondary);"><?php echo $workouts_this_week; ?></div>
                <p style="color: var(--gray);">
                    <?php if ($workout_change > 0): ?>
                        +<?php echo $workout_change; ?> from last week
                    <?php elseif ($workout_change < 0): ?>
                        <?php echo $workout_change; ?> from last week
                    <?php else: ?>
                        Same as last week
                    <?php endif; ?>
                </p>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-fire"></i>
                    </div>
                    <h2 class="card-title">Calories Burned</h2>
                </div>
                <div style="font-size: 2rem; font-weight: 700; color: var(--secondary);"><?php echo number_format($calories_burned); ?></div>
                <p style="color: var(--gray);">Keep up the good work!</p>
            </div>
            
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h2 class="card-title">Goals Progress</h2>
                </div>
                <div style="font-size: 2rem; font-weight: 700; color: var(--secondary);"><?php echo $goals_progress; ?>%</div>
                <p style="color: var(--gray);">of goals achieved</p>
            </div>
        </div>
        
        <!-- Workout Tracking Section -->
        <section>
            <h2 style="margin-bottom: 1rem; color: var(--dark);">Workout Tracking</h2>
            <div class="workout-form">
                <form method="POST" action="fitness_management.php">
                    <div class="form-group">
                        <label for="workout-type">Workout Type</label>
                        <select id="workout-type" name="workout_type" class="form-control" required onchange="calculateCalories()">
                            <option value="">Select workout type</option>
                            <option value="strength">Strength Training</option>
                            <option value="cardio">Cardio</option>
                            <option value="hiit">HIIT</option>
                            <option value="yoga">Yoga/Pilates</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="workout-date">Date</label>
                        <input type="date" id="workout-date" name="workout_date" class="form-control" required 
                            value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="workout-duration">Duration (minutes)</label>
                        <input type="number" id="workout-duration" name="workout_duration" class="form-control" min="1" required
                            onchange="calculateCalories()" onkeyup="calculateCalories()">
                    </div>
                    
                    <div class="form-group">
                        <label for="calories-burned">Calories Burned</label>
                        <input type="number" id="calories-burned" name="calories_burned" class="form-control" min="1" readonly>
                        <small class="text-muted">Calculated based on your weight (<?php echo $user_weight; ?>kg)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="workout-notes">Notes</label>
                        <textarea id="workout-notes" name="workout_notes" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" name="log_workout" class="btn">Log Workout</button>
                </form>
            </div>
            
            <h3 style="margin: 1.5rem 0 1rem; color: var(--dark);">Recent Workouts</h3>
            <div style="background: var(--white); border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <?php if (empty($recent_workouts)): ?>
                    <p style="padding: 1.5rem; text-align: center; color: var(--gray);">No workouts logged yet.</p>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: var(--primary); color: white;">
                            <tr>
                                <th style="padding: 1rem; text-align: left;">Date</th>
                                <th style="padding: 1rem; text-align: left;">Type</th>
                                <th style="padding: 1rem; text-align: left;">Duration</th>
                                <th style="padding: 1rem; text-align: left;">Calories</th>
                                <th style="padding: 1rem; text-align: left;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_workouts as $workout): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 1rem;"><?php echo htmlspecialchars($workout['workout_date']); ?></td>
                                    <td style="padding: 1rem;"><?php echo ucfirst(htmlspecialchars($workout['workout_type'])); ?></td>
                                    <td style="padding: 1rem;"><?php echo htmlspecialchars($workout['duration_minutes']); ?> mins</td>
                                    <td style="padding: 1rem;"><?php echo $workout['calories_burned'] ? htmlspecialchars($workout['calories_burned']) : '-'; ?></td>
                                    <td style="padding: 1rem;">
                                        <a href="edit_workout.php?id=<?php echo $workout['workout_id']; ?>" style="color: var(--primary); text-decoration: none;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="fitness_management.php?delete_workout=<?php echo $workout['workout_id']; ?>" 
                                        style="color: var(--accent); text-decoration: none; margin-left: 0.5rem;"
                                        onclick="return confirm('Are you sure you want to delete this workout?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Goal Setting Section -->
        <section style="margin-top: 3rem;">
            <h2 style="margin-bottom: 1rem; color: var(--dark);">Goal Setting</h2>
            <div style="background: var(--white); padding: 2rem; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="color: var(--dark);">Current Goals</h3>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('goalModal').style.display='block'">+ Add Goal</button>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                    <?php if (empty($user_goals)): ?>
                        <p style="grid-column: 1 / -1; text-align: center; color: var(--gray);">No goals set yet.</p>
                    <?php else: ?>
                        <?php foreach ($user_goals as $goal): ?>
                            <div style="background: rgba(0,0,0,0.05); padding: 1.5rem; border-radius: 8px; 
                                       border-left: 4px solid <?php echo $goal['status'] === 'completed' ? 'var(--goal-completed)' : 'var(--goal-pending)'; ?>;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <h4 style="color: var(--dark);"><?php echo htmlspecialchars($goal['title']); ?></h4>
                                    <span style="color: <?php echo $goal['status'] === 'completed' ? 'var(--goal-completed-text)' : 'var(--goal-pending-text)'; ?>; font-weight: 600;">
                                        <?php echo ucfirst($goal['status']); ?>
                                    </span>
                                </div>
                                <?php if ($goal['description']): ?>
                                    <p style="color: var(--gray); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($goal['description']); ?></p>
                                <?php endif; ?>
                                <?php if ($goal['target_value']): ?>
                                    <p style="color: var(--gray); margin-bottom: 1rem;">
                                        Target: <?php echo htmlspecialchars($goal['target_value']); ?>
                                        Current: <?php echo htmlspecialchars($goal['current_value'] ?: '0'); ?>
                                        <?php if ($goal['target_date']): ?>
                                            by <?php echo htmlspecialchars($goal['target_date']); ?>
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($goal['status'] !== 'completed'): ?>
                                        <?php 
                                            $progress = $goal['current_value'] ? min(100, ($goal['current_value'] / $goal['target_value']) * 100) : 0;
                                        ?>
                                        <div style="height: 8px; background: var(--light); border-radius: 4px; overflow: hidden;">
                                            <div style="width: <?php echo $progress; ?>%; height: 100%; background: var(--secondary);"></div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <!-- Action buttons -->
                                <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                                    <button onclick="openEditGoalModal(<?php echo $goal['goal_id']; ?>)" 
                                            class="btn btn-sm" 
                                            style="background: var(--primary);">
                                        Edit
                                    </button>
                                    <?php if ($goal['status'] !== 'completed'): ?>
                                        <form method="POST" action="fitness_management.php" style="display: inline;">
                                            <input type="hidden" name="goal_id" value="<?php echo $goal['goal_id']; ?>">
                                            <input type="hidden" name="mark_complete" value="1">
                                            <button type="submit" class="btn btn-sm" style="background: var(--secondary);">
                                                Mark Complete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Goal Modal -->
        <div id="goalModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="document.getElementById('goalModal').style.display='none'">&times;</span>
                <h2 style="margin-bottom: 1.5rem;">Add New Goal</h2>
                <form method="POST" action="fitness_management.php">
                    <div class="form-group">
                        <label for="goal_title">Goal Title</label>
                        <input type="text" id="goal_title" name="goal_title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="goal_description">Description</label>
                        <textarea id="goal_description" name="goal_description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="target_value">Target Value (optional)</label>
                        <input type="number" id="target_value" name="target_value" class="form-control" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="target_date">Target Date (optional)</label>
                        <input type="date" id="target_date" name="target_date" class="form-control">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn" style="background: var(--gray);" onclick="document.getElementById('goalModal').style.display='none'">Cancel</button>
                        <button type="submit" name="add_goal" class="btn">Save Goal</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Goal Modal -->
        <div id="editGoalModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="document.getElementById('editGoalModal').style.display='none'">&times;</span>
                <h2>Edit Goal</h2>
                <form method="POST" action="fitness_management.php" id="editGoalForm">
                    <input type="hidden" name="goal_id" id="edit_goal_id">
                    <input type="hidden" name="edit_goal" value="1">
                    
                    <div class="form-group">
                        <label for="edit_goal_title">Goal Title</label>
                        <input type="text" id="edit_goal_title" name="goal_title" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_goal_description">Description</label>
                        <textarea id="edit_goal_description" name="goal_description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_target_value">Target Value</label>
                        <input type="number" id="edit_target_value" name="target_value" class="form-control" step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_current_value">Current Value</label>
                        <input type="number" id="edit_current_value" name="current_value" class="form-control" step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_target_date">Target Date</label>
                        <input type="date" id="edit_target_date" name="target_date" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_status">Status</label>
                        <select id="edit_status" name="status" class="form-control">
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn" style="background: var(--gray);" 
                                onclick="document.getElementById('editGoalModal').style.display='none'">Cancel</button>
                        <button type="submit" class="btn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    
    <!-- Workout Planner Section -->
    <section style="margin-top: 3rem;">
        <h2 id="workoutPlanner" style="margin-bottom: 1rem; color: var(--dark);">Workout Planner</h2>
        <div class="planner-container">
            <div class="calendar">
                <div class="calendar-header">
                    <div class="calendar-title" id="calendarTitle"><?php echo date('F Y'); ?></div>
                    <div class="calendar-nav">
                        <button id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                        <button id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="calendar-grid" id="calendarGrid">
                    <!-- Calendar will be generated by JavaScript -->
                </div>
            </div>
            
            <div class="workout-plans-container">
                <button onclick="returnToToday()" id="returnToToday" class="btn btn-sm" style="display: none;">Return to Today</button>
                
                <div class="plan-date-header">
                    <h3 style="color: var(--dark);">
                        Workout Plan for <?php echo date('F j, Y', strtotime($selected_date)); ?>
                    </h3>
                    <?php if ($selected_date === date('Y-m-d')): ?>
                        <button onclick="document.getElementById('addPlanModal').style.display='block'" class="btn btn-sm">
                            <i class="fas fa-plus"></i> Create Plan
                        </button>
                    <?php else: ?>
                        <button onclick="createPlanForDate('<?php echo $selected_date; ?>')" class="btn btn-sm">
                            <i class="fas fa-plus"></i> Create Plan
                        </button>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($selected_plan)): ?>
                    <div class="plan-card">
                        <div class="plan-card-header">
                            <h4>Morning Routine</h4>
                            <?php if ($selected_plan['is_completed']): ?>
                                <span class="badge completed">Completed</span>
                            <?php else: ?>
                                <span class="badge pending">Pending</span>
                            <?php endif; ?>
                        </div>
                        <div class="plan-card-content">
                            <?php echo nl2br(htmlspecialchars($selected_plan['morning_routine'])); ?>
                        </div>
                    </div>
                    
                    <div class="plan-card">
                        <div class="plan-card-header">
                            <h4>Evening Activity</h4>
                            <?php if ($selected_plan['is_completed']): ?>
                                <span class="badge completed">Completed</span>
                            <?php else: ?>
                                <span class="badge pending">Pending</span>
                            <?php endif; ?>
                        </div>
                        <div class="plan-card-content">
                            <?php echo nl2br(htmlspecialchars($selected_plan['evening_activity'])); ?>
                        </div>
                    </div>
                    
                    <form method="POST" action="fitness_management.php" class="plan-status-form">
                        <input type="hidden" name="update_plan" value="1">
                        <input type="hidden" name="plan_date" value="<?php echo $selected_date; ?>">
                        <input type="hidden" name="morning_routine" value="<?php echo htmlspecialchars($selected_plan['morning_routine']); ?>">
                        <input type="hidden" name="evening_activity" value="<?php echo htmlspecialchars($selected_plan['evening_activity']); ?>">
                        <label class="checkbox-container">
                            <input type="checkbox" name="is_completed" <?php echo $selected_plan['is_completed'] ? 'checked' : ''; ?> 
                                onchange="this.form.submit()">
                            Mark as completed
                        </label>
                    </form>
                <?php else: ?>
                    <div class="empty-plan">
                        <p>No workout plan for this date.</p>
                        <button onclick="<?php echo $selected_date === date('Y-m-d') ? 
                                        "document.getElementById('addPlanModal').style.display='block'" : 
                                        "createPlanForDate('$selected_date')"; ?>" 
                                class="btn">
                            Create Plan
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Add Plan Modal -->
        <div id="addPlanModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="document.getElementById('addPlanModal').style.display='none'">&times;</span>
                <h2>Create Workout Plan for <?php echo date('F j, Y'); ?></h2>
                <form method="POST" action="fitness_management.php">
                    <input type="hidden" name="plan_date" value="<?php echo date('Y-m-d'); ?>">
                    
                    <div class="form-group">
                        <label>Morning Routine</label>
                        <textarea name="morning_routine" class="form-control" rows="4" placeholder="Enter your morning workout routine"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Evening Activity</label>
                        <textarea name="evening_activity" class="form-control" rows="4" placeholder="Enter your evening activity"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn" style="background: var(--gray);" 
                                onclick="document.getElementById('addPlanModal').style.display='none'">Cancel</button>
                        <button type="submit" name="update_plan" class="btn">Save Plan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Community Support Section -->
        <section style="margin-top: 3rem; margin-bottom: 3rem;">
            <h2 style="margin-bottom: 1rem; color: var(--dark);">Community Support</h2>
            <div class="community-container">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="color: var(--dark);">Recent Community Posts</h3>
                    <a href="community.php" class="btn">View All</a>
                </div>
                
                <!-- Add Post Form -->
                <div class="add-post-form">
                    <form method="POST" action="fitness_management.php">
                        <div class="form-group">
                            <textarea name="post_content" class="form-control" rows="3" 
                                    placeholder="Share something with the community..." required></textarea>
                        </div>
                        <button type="submit" name="add_post" class="btn">Post</button>
                    </form>
                </div>
                
                <div class="community-posts">
                    <?php if (empty($community_posts)): ?>
                        <p style="text-align: center; color: var(--gray); padding: 1.5rem;">No community posts yet.</p>
                    <?php else: ?>
                        <?php foreach ($community_posts as $post): ?>
                            <div class="post">
                                <div class="post-header">
                                    <div class="post-avatar">
                                        <?php echo strtoupper(substr($post['author_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="post-author"><?php echo htmlspecialchars($post['author_name']); ?></div>
                                        <div class="post-time"><?php echo date('M j, Y g:i a', strtotime($post['created_at'])); ?></div>
                                    </div>
                                </div>
                                <div class="post-content">
                                    <p><?php echo htmlspecialchars($post['content']); ?></p>
                                    
                                    <!-- Like Button -->
                                    <form method="POST" action="fitness_management.php" class="like-form">
                                        <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                        <button type="submit" name="like_post" class="like-button">
                                            <i class="fas fa-heart"></i> <?php echo $post['likes']; ?>
                                        </button>
                                    </form>
                                    
                                    <!-- Comments Section -->
                                    <div class="comments-section">
                                        <?php
                                        try {
                                            $stmt = $pdo->prepare("SELECT c.*, u.fullname as author_name 
                                                                FROM comments c
                                                                JOIN users u ON c.user_id = u.id
                                                                WHERE c.post_id = ?
                                                                ORDER BY c.created_at ASC");
                                            $stmt->execute([$post['post_id']]);
                                            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        } catch (PDOException $e) {
                                            $comments = [];
                                        }
                                        ?>
                                        
                                        <?php foreach ($comments as $comment): ?>
                                            <div class="comment">
                                                <div class="comment-header">
                                                    <div class="comment-avatar">
                                                        <?php echo strtoupper(substr($comment['author_name'], 0, 1)); ?>
                                                    </div>
                                                    <div class="comment-author"><?php echo htmlspecialchars($comment['author_name']); ?></div>
                                                    <div class="comment-time"><?php echo date('M j, g:i a', strtotime($comment['created_at'])); ?></div>
                                                </div>
                                                <div class="comment-content"><?php echo htmlspecialchars($comment['content']); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <!-- Add Comment Form -->
                                        <form method="POST" action="fitness_management.php" class="add-comment-form">
                                            <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                            <div class="form-group" style="margin-bottom: 0.5rem;">
                                                <textarea name="comment_content" class="form-control" rows="2" 
                                                        placeholder="Write a comment..." required></textarea>
                                            </div>
                                            <button type="submit" name="add_comment" class="btn btn-sm">Post Comment</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>

    
    <script>
// Helper to get today's date in local timezone
function getTodayLocal() {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

// Track current month being viewed
let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();

// Function to generate calendar for a specific month/year
function generateCalendar(month, year) {
    const calendarGrid = document.getElementById('calendarGrid');
    const calendarTitle = document.getElementById('calendarTitle');
    
    calendarGrid.innerHTML = '';
    
    const monthNames = ["January", "February", "March", "April", "May", "June",
                        "July", "August", "September", "October", "November", "December"];
    calendarTitle.textContent = `${monthNames[month]} ${year}`;
    
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    const daysFromPrevMonth = firstDay;
    const prevMonthDays = new Date(year, month, 0).getDate();
    
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    dayNames.forEach(day => {
        const dayHeader = document.createElement('div');
        dayHeader.className = 'calendar-day-header';
        dayHeader.textContent = day;
        calendarGrid.appendChild(dayHeader);
    });
    
    for (let i = daysFromPrevMonth - 1; i >= 0; i--) {
        const day = document.createElement('div');
        day.className = 'calendar-day prev-month-day';
        day.textContent = prevMonthDays - i;
        day.style.color = 'var(--gray)';
        day.style.opacity = '0.5';
        calendarGrid.appendChild(day);
    }
    
    const today = new Date();
    const isCurrentMonth = month === today.getMonth() && year === today.getFullYear();
    const currentDateStr = getTodayLocal();
    
    fetch(`fitness_management.php?month=${month + 1}&year=${year}&get_plans=1`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(planDates => {
            if (!Array.isArray(planDates)) throw new Error('Invalid plan data received');
            
            for (let i = 1; i <= daysInMonth; i++) {
                const day = document.createElement('div');
                day.className = 'calendar-day';
                day.textContent = i;
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                day.dataset.date = dateStr;
                
                if (isCurrentMonth && i === today.getDate()) {
                    day.classList.add('today');
                }
                
                if (planDates.includes(dateStr)) {
                    day.classList.add('has-plan');
                }
                
                const urlParams = new URLSearchParams(window.location.search);
                const selectedDate = urlParams.get('selected_date');
                if (dateStr === selectedDate || (!selectedDate && dateStr === currentDateStr)) {
                    day.classList.add('selected');
                }
                
                day.addEventListener('click', function() {
                    loadPlanForDate(this.dataset.date);
                });
                
                calendarGrid.appendChild(day);
            }
            
            const totalCells = Math.ceil((daysFromPrevMonth + daysInMonth) / 7) * 7;
            const daysFromNextMonth = totalCells - (daysFromPrevMonth + daysInMonth);
            
            for (let i = 1; i <= daysFromNextMonth; i++) {
                const day = document.createElement('div');
                day.className = 'calendar-day next-month-day';
                day.textContent = i;
                day.style.color = 'var(--gray)';
                day.style.opacity = '0.5';
                calendarGrid.appendChild(day);
            }
        })
        .catch(error => {
            console.error('Error fetching plans:', error);
            for (let i = 1; i <= daysInMonth; i++) {
                const day = document.createElement('div');
                day.className = 'calendar-day';
                day.textContent = i;
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                day.dataset.date = dateStr;
                
                if (isCurrentMonth && i === today.getDate()) {
                    day.classList.add('today');
                }
                
                const urlParams = new URLSearchParams(window.location.search);
                const selectedDate = urlParams.get('selected_date');
                if (dateStr === selectedDate || (!selectedDate && dateStr === currentDateStr)) {
                    day.classList.add('selected');
                }
                
                day.addEventListener('click', function() {
                    loadPlanForDate(this.dataset.date);
                });
                
                calendarGrid.appendChild(day);
            }
        });
}

document.getElementById('prevMonth').addEventListener('click', function() {
    currentMonth--;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    generateCalendar(currentMonth, currentYear);
});

document.getElementById('nextMonth').addEventListener('click', function() {
    currentMonth++;
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    generateCalendar(currentMonth, currentYear);
});

function loadPlanForDate(date) {
    const formattedDate = new Date(date).toLocaleDateString('en-US', { 
        year: 'numeric', month: 'long', day: 'numeric' 
    });

    const existingHeader = document.querySelector('.plan-date-header h3');
    if (existingHeader) {
        existingHeader.textContent = `Workout Plan for ${formattedDate}`;
    }

    const today = getTodayLocal();
    const returnBtn = document.getElementById('returnToToday');
    if (returnBtn) {
        returnBtn.style.display = date === today ? 'none' : 'block';
    }

    document.querySelectorAll('.calendar-day').forEach(day => {
        day.classList.remove('selected');
        if (day.dataset.date === date) {
            day.classList.add('selected');
        }
    });

    window.location.href = `fitness_management.php?selected_date=${date}#workoutPlanner`;
}

function createPlanForDate(date) {
    const modal = document.getElementById('addPlanModal');
    modal.style.display = 'block';
    
    const form = modal.querySelector('form');
    let dateInput = form.querySelector('input[name="plan_date"]');
    
    if (!dateInput) {
        dateInput = document.createElement('input');
        dateInput.type = 'hidden';
        dateInput.name = 'plan_date';
        form.appendChild(dateInput);
    }
    
    dateInput.value = date;
    
    const formattedDate = new Date(date).toLocaleDateString('en-US', { 
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
    });
    modal.querySelector('h2').textContent = `Create Workout Plan for ${formattedDate}`;
}

function returnToToday() {
    const today = getTodayLocal();
    window.location.href = `fitness_management.php?selected_date=${today}`;
}

function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const selectedDate = urlParams.get('selected_date');
    
    if (selectedDate) {
        const date = new Date(selectedDate);
        currentMonth = date.getMonth();
        currentYear = date.getFullYear();
    }
    
    generateCalendar(currentMonth, currentYear);
    
    const today = getTodayLocal();
    if (selectedDate && selectedDate !== today) {
        document.getElementById('returnToToday').style.display = 'block';
    }
    
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = "none";
        }
    }
    
    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const selectedDate = urlParams.get('selected_date');
        const today = getTodayLocal();
        const dateToLoad = selectedDate || today;
        
        document.querySelectorAll('.calendar-day').forEach(day => {
            day.classList.remove('selected');
            if (day.dataset.date === dateToLoad) {
                day.classList.add('selected');
            }
        });
        
        loadPlanForDate(dateToLoad);
    });
});

function calculateCalories() {
    const workoutType = document.getElementById('workout-type').value;
    const duration = parseFloat(document.getElementById('workout-duration').value);
    const caloriesField = document.getElementById('calories-burned');
    
    if (!workoutType || !duration || duration <= 0) {
        caloriesField.value = '';
        return;
    }

    const metValues = {
        'strength': 6.0,
        'cardio': 7.0,
        'hiit': 8.0,
        'yoga': 2.5,
        'other': 5.0
    };

    const userWeight = <?php echo $user_weight; ?>;
    const caloriesBurned = metValues[workoutType] * userWeight * (duration / 60);
    
    caloriesField.value = Math.round(caloriesBurned);
}

document.getElementById('workout-type').addEventListener('change', calculateCalories);
document.getElementById('workout-duration').addEventListener('input', calculateCalories);

function openEditGoalModal(goalId) {
    fetch(`get_goal.php?id=${goalId}`)
        .then(response => response.json())
        .then(goal => {
            document.getElementById('edit_goal_id').value = goal.goal_id;
            document.getElementById('edit_goal_title').value = goal.title;
            document.getElementById('edit_goal_description').value = goal.description;
            document.getElementById('edit_target_value').value = goal.target_value || '';
            document.getElementById('edit_current_value').value = goal.current_value || '';
            document.getElementById('edit_target_date').value = goal.target_date || '';
            document.getElementById('edit_status').value = goal.status;
            document.getElementById('editGoalModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error loading goal:', error);
            alert('Error loading goal data');
        });
}
</script>

</body>
</html>