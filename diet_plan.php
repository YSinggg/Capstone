<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$username = '';
if ($isLoggedIn) {
    $username = $_SESSION['username'] ?? 'User';
}

// Security and validation checks
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Validate user_id is numeric
$user_id = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
if ($user_id === false) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database connection
$host = 'localhost';
$dbname = 'fitness_management';
$username_db = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username_db, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("We're experiencing technical difficulties. Please try again later.");
}

// Get user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("User data fetch failed: " . $e->getMessage());
    die("We're experiencing technical difficulties. Please try again later.");
}

// Assign user variables
$username = $user['username'] ?? 'User';
$user_email = $user['email'];
$user_age = $user['age'] ?? null;
$user_weight = $user['weight'] ?? null;
$user_height = $user['height'] ?? null;
$user_gender = $user['gender'] ?? 'male';
$fitness_goal = $user['fitness_goal'] ?? 'maintenance';
$calorie_target = $user['calorie_target'] ?? 2000;

// Get today's meals
$today = date('Y-m-d');
try {
    $stmt = $pdo->prepare("SELECT * FROM meals WHERE user_id = ? AND date = ? ORDER BY meal_time ASC");
    $stmt->execute([$user_id, $today]);
    $meals = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Meals fetch failed: " . $e->getMessage());
    $meals = [];
}

// Calculate daily totals
$daily_totals = ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fats' => 0];
foreach ($meals as $meal) {
    $daily_totals['calories'] += $meal['calories'];
    $daily_totals['protein'] += $meal['protein'];
    $daily_totals['carbs'] += $meal['carbs'];
    $daily_totals['fats'] += $meal['fats'];
}

// Calculate percentages
$calorie_percentage = $calorie_target > 0 ? min(100, ($daily_totals['calories']/$calorie_target)*100) : 0;
$protein_target = $user_weight ? $user_weight * 2.2 : 100;
$protein_percentage = $protein_target > 0 ? min(100, ($daily_totals['protein']/$protein_target)*100) : 0;

// Get water intake
try {
    $stmt = $pdo->prepare("SELECT SUM(glasses) as total FROM water_intake WHERE user_id = ? AND date = ?");
    $stmt->execute([$user_id, $today]);
    $water = $stmt->fetch();
    $water_intake = $water['total'] ?? 0;
} catch (PDOException $e) {
    error_log("Water intake fetch failed: " . $e->getMessage());
    $water_intake = 0;
}
$water_percentage = min(100, ($water_intake/8)*100);

// Get recommended meal plans
try {
    $stmt = $pdo->prepare("SELECT * FROM meal_plans WHERE goal = ? OR goal = 'general' ORDER BY goal DESC");
    $stmt->execute([$fitness_goal]);
    $meal_plans = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Meal plans fetch failed: " . $e->getMessage());
    $meal_plans = [];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_water'])) {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die("Invalid CSRF token");
        }
        
        $glasses = filter_var($_POST['glasses'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 20]]);
        if ($glasses === false) {
            die("Invalid water amount");
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO water_intake (user_id, glasses, date) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $glasses, $today]);
            header("Location: diet_plan.php");
            exit;
        } catch (PDOException $e) {
            error_log("Water intake insert failed: " . $e->getMessage());
            die("Error logging water intake. Please try again.");
        }
    }
}
include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diet & Nutrition | Kurus+</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            --light-gray: #f1f3f5;
        }
        
        /* Main content styles */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            color: var(--text);
            line-height: 1.6;
        }
        
        .diet-hero {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), 
                        url('https://images.unsplash.com/photo-1490645935967-10de6ba17061?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 6rem 1rem;
        }
        
        .diet-hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .diet-hero p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 2rem;
        }
        
        .diet-container {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 1rem;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .section-title h2 {
            font-size: 2.2rem;
            color: var(--dark);
            margin-bottom: 1rem;
        }
        
        .section-title p {
            color: var(--gray);
            max-width: 700px;
            margin: 0 auto;
        }
        
        /* Nutrition Dashboard */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
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
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .card-icon {
            font-size: 1.8rem;
            color: var(--secondary);
            margin-right: 1rem;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }
        
        .card-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0.5rem 0;
        }
        
        .card-target {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .progress-container {
            margin-top: 1rem;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
        }
        
        .progress-bar {
            height: 100%;
            border-radius: 4px;
            background-color: var(--secondary);
            transition: width 0.6s ease;
        }
        
        /* Meals Table */
        .meals-section {
            background: var(--white);
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 3rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-title-inline {
            font-size: 1.5rem;
            color: var(--dark);
            margin: 0;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #3a7bd5;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .meals-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .meals-table th {
            background-color: var(--light);
            color: var(--dark);
            font-weight: 600;
            padding: 1rem;
            text-align: left;
        }
        
        .meals-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .meals-table tr:hover {
            background-color: rgba(74, 143, 231, 0.05);
        }
        
        .meal-type {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            background-color: var(--light);
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--dark);
        }
        
        .btn-sm {
            padding: 0.3rem 0.6rem;
            font-size: 0.9rem;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-danger {
            background-color: var(--accent);
            border-color: var(--accent);
        }
        
        .btn-danger:hover {
            background-color: #e05555;
            border-color: #e05555;
        }
        
        /* Meal Plans */
        .meal-plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .plan-card {
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .plan-header {
            background-color: var(--primary);
            color: white;
            padding: 1.5rem;
        }
        
        .plan-title {
            margin: 0;
            font-size: 1.3rem;
        }
        
        .plan-badge {
            display: inline-block;
            margin-top: 0.5rem;
            padding: 0.3rem 0.8rem;
            background-color: rgba(255,255,255,0.2);
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .plan-body {
            padding: 1.5rem;
        }
        
        .plan-description {
            color: var(--gray);
            margin-bottom: 1.5rem;
        }
        
        .plan-macros {
            display: grid;
            grid-template-columns: repeat(4, 1fr));
            gap: 1rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .macro-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .macro-label {
            font-size: 0.8rem;
            color: var(--gray);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        /* MODAL STYLES */
        .modal {
            --modal-padding: 2rem;
            --modal-radius: 12px;
            --modal-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .modal-content {
            border: none;
            border-radius: var(--modal-radius);
            box-shadow: var(--modal-shadow);
            overflow: hidden;
        }

        .modal-header {
            padding: 1.5rem var(--modal-padding);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            background: var(--white);
            position: relative;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }

        .modal-body {
            padding: var(--modal-padding);
        }

        .modal-footer {
            padding: 1rem var(--modal-padding);
            border-top: 1px solid rgba(0,0,0,0.05);
            background: #f9fafb;
        }

        .btn-close {
            background: none;
            font-size: 1.2rem;
            opacity: 0.5;
            transition: all 0.2s ease;
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
        }

        .btn-close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }

        /* Form Styles */
        .form-label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control, .form-select {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
            width: 100%;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74, 143, 231, 0.1);
            outline: none;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        .input-with-icon {
            padding-left: 3rem;
        }

        /* Custom Radio Buttons */
        .meal-type-options {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .meal-type-option {
            flex: 1;
        }

        .meal-type-option input[type="radio"] {
            display: none;
        }

        .meal-type-option label {
            display: block;
            padding: 0.75rem 1rem;
            text-align: center;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .meal-type-option input[type="radio"]:checked + label {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Water Intake Modal */
        .water-amount {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .water-glass {
            font-size: 2rem;
            color: #4a8fe7;
            margin: 0 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .water-glass.active {
            color: #1e5bb9;
            transform: scale(1.2);
        }

        /* Responsive styles */
        @media (max-width: 992px) {
            .dashboard-cards {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .diet-hero h1 {
                font-size: 2.2rem;
            }
            
            .section-title h2 {
                font-size: 1.8rem;
            }

            .modal-header, .modal-body, .modal-footer {
                padding: 1.5rem;
            }
        }
        
        /* Dark mode styles */
        .dark-mode {
            background: #1a1a1a;
            color: #f0f0f0;
        }
        
        .dark-mode .dashboard-card,
        .dark-mode .meals-section,
        .dark-mode .plan-card,
        .dark-mode .modal-content,
        .dark-mode .modal-header,
        .dark-mode .modal-footer {
            background: #2d2d2d;
            color: #f0f0f0;
        }
        
        .dark-mode .card-title,
        .dark-mode .section-title-inline,
        .dark-mode .meals-table th,
        .dark-mode .macro-value,
        .dark-mode .modal-title,
        .dark-mode .form-label {
            color: #f0f0f0;
        }
        
        .dark-mode .card-target,
        .dark-mode .plan-description,
        .dark-mode .macro-label,
        .dark-mode .meals-table td {
            color: #ccc;
        }
        
        .dark-mode .meals-table tr:hover {
            background-color: rgba(74, 143, 231, 0.1);
        }
        
        .dark-mode .progress {
            background-color: #444;
        }

        .dark-mode .form-control,
        .dark-mode .form-select {
            background-color: #333;
            border-color: #444;
            color: #f0f0f0;
        }

        .dark-mode .meal-type-option label {
            background-color: #333;
            border-color: #444;
            color: #f0f0f0;
        }

        .dark-mode .modal-footer {
            background: #252525;
            border-top-color: #444;
        }

        .alert {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }

        .alert-success {
            background-color: #44c767;
            color: white;
        }

        .alert-error {
            background-color: #ff6b6b;
            color: white;
        }

        .fade-out {
            opacity: 0;
            transform: translateX(-50%) translateY(-20px);
        }

        /* Add this if using SweetAlert */
        .swal2-popup {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body>
    <section class="diet-hero">
        <h1>Your Nutrition Dashboard</h1>
        <p>Track your meals, monitor macros, and stay on top of your nutrition goals</p>
    </section>

    <div class="diet-container">
        <!-- Nutrition Summary -->
        <section>
            <div class="section-title">
                <h2>Today's Nutrition</h2>
                <p><?= date('l, F j, Y') ?></p>
            </div>
            
            <div class="dashboard-cards">
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-fire"></i>
                        </div>
                        <h3 class="card-title">Calories</h3>
                    </div>
                    <div class="card-value"><?= $daily_totals['calories'] ?></div>
                    <div class="card-target">of <?= $calorie_target ?> target</div>
                    <div class="progress-container">
                        <div class="progress-label">
                            <span><?= round($calorie_percentage) ?>%</span>
                            <span><?= $calorie_target ?> cal</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?= $calorie_percentage ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                        <h3 class="card-title">Protein</h3>
                    </div>
                    <div class="card-value"><?= $daily_totals['protein'] ?>g</div>
                    <div class="card-target">of <?= round($protein_target) ?>g target</div>
                    <div class="progress-container">
                        <div class="progress-label">
                            <span><?= round($protein_percentage) ?>%</span>
                            <span><?= round($protein_target) ?>g</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?= $protein_percentage ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-bread-slice"></i>
                        </div>
                        <h3 class="card-title">Carbs</h3>
                    </div>
                    <div class="card-value"><?= $daily_totals['carbs'] ?>g</div>
                    <div class="card-target">of <?= round($calorie_target*0.4/4) ?>g target</div>
                    <div class="progress-container">
                        <div class="progress-label">
                            <span><?= round(($daily_totals['carbs']/($calorie_target*0.4/4))*100) ?>%</span>
                            <span><?= round($calorie_target*0.4/4) ?>g</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?= min(100, ($daily_totals['carbs']/($calorie_target*0.4/4))*100) ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <h3 class="card-title">Fats</h3>
                    </div>
                    <div class="card-value"><?= $daily_totals['fats'] ?>g</div>
                    <div class="card-target">of <?= round($calorie_target*0.3/9) ?>g target</div>
                    <div class="progress-container">
                        <div class="progress-label">
                            <span><?= round(($daily_totals['fats']/($calorie_target*0.3/9))*100) ?>%</span>
                            <span><?= round($calorie_target*0.3/9) ?>g</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?= min(100, ($daily_totals['fats']/($calorie_target*0.3/9))*100) ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-card" style="margin-top: 1.5rem;">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-tint"></i>
                    </div>
                    <h3 class="card-title">Hydration</h3>
                </div>
                <div class="card-value"><?= $water_intake ?> glasses</div>
                <div class="card-target">of 8 glasses target</div>
                <div class="progress-container">
                    <div class="progress-label">
                        <span><?= round($water_percentage) ?>%</span>
                        <span>8 glasses</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" style="width: <?= $water_percentage ?>%"></div>
                    </div>
                </div>
                <button class="btn-primary" style="margin-top: 1rem;" data-bs-toggle="modal" data-bs-target="#waterModal">
                    <i class="fas fa-plus"></i> Add Water
                </button>
            </div>
        </section>
        
        <!-- Today's Meals -->
        <section class="meals-section">
            <div class="section-header">
                <h3 class="section-title-inline">Today's Meals</h3>
                <button class="btn-primary" data-bs-toggle="modal" data-bs-target="#mealModal">
                    <i class="fas fa-plus"></i> Add Meal
                </button>
            </div>
            
            <?php if (empty($meals)): ?>
                <div class="empty-state">
                    <i class="fas fa-utensils"></i>
                    <h4>No meals logged today</h4>
                    <p>Add your first meal to get started tracking your nutrition</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="meals-table">
                        <thead>
                            <tr>
                                <th>Meal</th>
                                <th>Time</th>
                                <th>Calories</th>
                                <th>Protein</th>
                                <th>Carbs</th>
                                <th>Fats</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($meals as $meal): ?>
                            <tr>
                                <td>
                                    <span class="meal-type"><?= ucfirst($meal['meal_type']) ?></span>
                                    <div style="margin-top: 0.5rem;"><?= htmlspecialchars($meal['meal_name']) ?></div>
                                </td>
                                <td><?= date('h:i A', strtotime($meal['meal_time'])) ?></td>
                                <td><?= $meal['calories'] ?></td>
                                <td><?= $meal['protein'] ?>g</td>
                                <td><?= $meal['carbs'] ?>g</td>
                                <td><?= $meal['fats'] ?>g</td>
                                <td>
                                    <button class="btn-sm btn-outline edit-meal" data-id="<?= $meal['id'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-sm btn-danger delete-meal" data-id="<?= $meal['id'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
        
        <!-- Recommended Meal Plans -->
        <section>
            <div class="section-title">
                <h2>Recommended Meal Plans</h2>
                <p>Choose a plan that matches your fitness goals</p>
            </div>
            
            <div class="meal-plans-grid">
                <?php foreach ($meal_plans as $plan): ?>
                <div class="plan-card">
                    <div class="plan-header">
                        <h3 class="plan-title"><?= htmlspecialchars($plan['plan_name']) ?></h3>
                        <span class="plan-badge"><?= ucfirst(str_replace('_', ' ', $plan['goal'])) ?></span>
                    </div>
                    <div class="plan-body">
                        <p class="plan-description"><?= htmlspecialchars($plan['description']) ?></p>
                        <div class="plan-macros">
                            <div>
                                <div class="macro-value"><?= $plan['calories'] ?></div>
                                <div class="macro-label">Calories</div>
                            </div>
                            <div>
                                <div class="macro-value"><?= $plan['protein'] ?>g</div>
                                <div class="macro-label">Protein</div>
                            </div>
                            <div>
                                <div class="macro-value"><?= $plan['carbs'] ?>g</div>
                                <div class="macro-label">Carbs</div>
                            </div>
                            <div>
                                <div class="macro-value"><?= $plan['fats'] ?>g</div>
                                <div class="macro-label">Fats</div>
                            </div>
                        </div>
                        <button class="btn-primary w-100 select-plan" data-id="<?= $plan['id'] ?>">
                            Select Plan
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <!-- Add Meal Modal -->
    <div class="modal fade" id="mealModal" tabindex="-1" aria-labelledby="mealModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mealModalLabel">Log Your Meal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fas fa-times"></i></button>
                </div>
                <form action="add_meal.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="modal-body">
                        <div class="mb-4">
                            <h6 class="form-label">Meal Type</h6>
                            <div class="meal-type-options">
                                <div class="meal-type-option">
                                    <input type="radio" id="breakfast" name="meal_type" value="breakfast" checked>
                                    <label for="breakfast"><i class="fas fa-egg me-2"></i> Breakfast</label>
                                </div>
                                <div class="meal-type-option">
                                    <input type="radio" id="lunch" name="meal_type" value="lunch">
                                    <label for="lunch"><i class="fas fa-hamburger me-2"></i> Lunch</label>
                                </div>
                                <div class="meal-type-option">
                                    <input type="radio" id="dinner" name="meal_type" value="dinner">
                                    <label for="dinner"><i class="fas fa-utensils me-2"></i> Dinner</label>
                                </div>
                                <div class="meal-type-option">
                                    <input type="radio" id="snack" name="meal_type" value="snack">
                                    <label for="snack"><i class="fas fa-apple-alt me-2"></i> Snack</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="mealName" class="form-label">Meal Name</label>
                                <div class="input-group">
                                    <i class="fas fa-utensils input-icon"></i>
                                    <input type="text" class="form-control input-with-icon" id="mealName" name="meal_name" placeholder="e.g. Chicken Salad" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="mealTime" class="form-label">Time</label>
                                <div class="input-group">
                                    <i class="fas fa-clock input-icon"></i>
                                    <input type="time" class="form-control input-with-icon" id="mealTime" name="meal_time" value="<?= date('H:i') ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="form-label">Nutrition Information</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="calories" class="form-label">Calories</label>
                                    <div class="input-group">
                                        <i class="fas fa-fire input-icon"></i>
                                        <input type="number" class="form-control input-with-icon" id="calories" name="calories" placeholder="0" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="servingSize" class="form-label">Serving Size (optional)</label>
                                    <div class="input-group">
                                        <i class="fas fa-weight input-icon"></i>
                                        <input type="text" class="form-control input-with-icon" id="servingSize" name="serving_size" placeholder="e.g. 1 cup">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="protein" class="form-label">Protein (g)</label>
                                    <input type="number" step="0.1" class="form-control" id="protein" name="protein" placeholder="0" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="carbs" class="form-label">Carbs (g)</label>
                                    <input type="number" step="0.1" class="form-control" id="carbs" name="carbs" placeholder="0" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="fats" class="form-label">Fats (g)</label>
                                    <input type="number" step="0.1" class="form-control" id="fats" name="fats" placeholder="0" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any additional details about this meal"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save me-2"></i> Save Meal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Water Intake Modal -->
    <div class="modal fade" id="waterModal" tabindex="-1" aria-labelledby="waterModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="waterModalLabel">Log Water Intake</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fas fa-times"></i></button>
                </div>
                <form method="POST" action="diet_plan.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <h6 class="form-label">How many glasses of water did you drink?</h6>
                            <div class="water-amount">
                                <i class="fas fa-minus-circle text-muted me-3" style="font-size: 1.5rem; cursor: pointer;" id="decreaseWater"></i>
                                <input type="number" class="form-control text-center" id="glasses" name="glasses" min="1" max="20" value="1" style="max-width: 80px; display: inline-block;" required>
                                <i class="fas fa-plus-circle text-primary ms-3" style="font-size: 1.5rem; cursor: pointer;" id="increaseWater"></i>
                            </div>
                        </div>
                        
                        <div class="text-center mb-3">
                            <p class="text-muted">Current total today: <strong><?= $water_intake ?></strong> glasses</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_water" class="btn-primary">
                            <i class="fas fa-tint me-2"></i> Log Water
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Meal Modal -->
    <div class="modal fade" id="editMealModal" tabindex="-1" aria-labelledby="editMealModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editMealModalLabel">Edit Meal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fas fa-times"></i></button>
                </div>
                <form id="editMealForm" action="edit_meal.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" id="editMealId" name="meal_id">
                    <div class="modal-body">
                        <div class="mb-4">
                            <h6 class="form-label">Meal Type</h6>
                            <div class="meal-type-options">
                                <div class="meal-type-option">
                                    <input type="radio" id="editBreakfast" name="meal_type" value="breakfast">
                                    <label for="editBreakfast"><i class="fas fa-egg me-2"></i> Breakfast</label>
                                </div>
                                <div class="meal-type-option">
                                    <input type="radio" id="editLunch" name="meal_type" value="lunch">
                                    <label for="editLunch"><i class="fas fa-hamburger me-2"></i> Lunch</label>
                                </div>
                                <div class="meal-type-option">
                                    <input type="radio" id="editDinner" name="meal_type" value="dinner">
                                    <label for="editDinner"><i class="fas fa-utensils me-2"></i> Dinner</label>
                                </div>
                                <div class="meal-type-option">
                                    <input type="radio" id="editSnack" name="meal_type" value="snack">
                                    <label for="editSnack"><i class="fas fa-apple-alt me-2"></i> Snack</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editMealName" class="form-label">Meal Name</label>
                                <div class="input-group">
                                    <i class="fas fa-utensils input-icon"></i>
                                    <input type="text" class="form-control input-with-icon" id="editMealName" name="meal_name" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editMealTime" class="form-label">Time</label>
                                <div class="input-group">
                                    <i class="fas fa-clock input-icon"></i>
                                    <input type="time" class="form-control input-with-icon" id="editMealTime" name="meal_time" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="form-label">Nutrition Information</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="editCalories" class="form-label">Calories</label>
                                    <div class="input-group">
                                        <i class="fas fa-fire input-icon"></i>
                                        <input type="number" class="form-control input-with-icon" id="editCalories" name="calories" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="editServingSize" class="form-label">Serving Size (optional)</label>
                                    <div class="input-group">
                                        <i class="fas fa-weight input-icon"></i>
                                        <input type="text" class="form-control input-with-icon" id="editServingSize" name="serving_size">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="editProtein" class="form-label">Protein (g)</label>
                                    <input type="number" step="0.1" class="form-control" id="editProtein" name="protein" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="editCarbs" class="form-label">Carbs (g)</label>
                                    <input type="number" step="0.1" class="form-control" id="editCarbs" name="carbs" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="editFats" class="form-label">Fats (g)</label>
                                    <input type="number" step="0.1" class="form-control" id="editFats" name="fats" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editNotes" class="form-label">Notes (optional)</label>
                            <textarea class="form-control" id="editNotes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save me-2"></i> Update Meal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Water intake controls
            const increaseWater = document.getElementById('increaseWater');
            const decreaseWater = document.getElementById('decreaseWater');
            const waterInput = document.getElementById('glasses');
            
            if (increaseWater && decreaseWater && waterInput) {
                increaseWater.addEventListener('click', function() {
                    waterInput.value = parseInt(waterInput.value) + 1;
                });
                
                decreaseWater.addEventListener('click', function() {
                    if (parseInt(waterInput.value) > 1) {
                        waterInput.value = parseInt(waterInput.value) - 1;
                    }
                });
            }

            // Edit meal buttons
            document.querySelectorAll('.edit-meal').forEach(button => {
                button.addEventListener('click', function() {
                    const mealId = this.dataset.id;
                    fetch(`get_meal.php?id=${mealId}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(meal => {
                            if (!meal) {
                                throw new Error('Meal not found');
                            }
                            
                            document.getElementById('editMealId').value = meal.id;
                            document.querySelector(`#editMealModal input[name="meal_type"][value="${meal.meal_type}"]`).checked = true;
                            document.getElementById('editMealName').value = meal.meal_name;
                            document.getElementById('editMealTime').value = meal.meal_time.substring(0, 5);
                            document.getElementById('editCalories').value = meal.calories;
                            document.getElementById('editProtein').value = meal.protein;
                            document.getElementById('editCarbs').value = meal.carbs;
                            document.getElementById('editFats').value = meal.fats;
                            document.getElementById('editServingSize').value = meal.serving_size || '';
                            document.getElementById('editNotes').value = meal.notes || '';
                            
                            const modal = new bootstrap.Modal(document.getElementById('editMealModal'));
                            modal.show();
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error loading meal data: ' + error.message);
                        });
                });
            });
            
            // Select plan buttons
            document.querySelectorAll('.select-plan').forEach(button => {
                button.addEventListener('click', async function(e) {
                    e.preventDefault();
                    console.log('Select Plan button clicked');
                    
                    const planId = this.dataset.id;
                    const planCard = this.closest('.plan-card');
                    const planName = planCard.querySelector('.plan-title').textContent;
                    
                    // Get all macro values from the card
                    const macroValues = planCard.querySelectorAll('.macro-value');
                    const planMacros = {
                        calories: macroValues[0].textContent,
                        protein: macroValues[1].textContent + 'g',
                        carbs: macroValues[2].textContent + 'g',
                        fats: macroValues[3].textContent + 'g'
                    };
                    
                    if (!planId) {
                        console.error('No plan ID found');
                        await Swal.fire({
                            title: 'Error',
                            text: 'Invalid plan selection',
                            icon: 'error'
                        });
                        return;
                    }

                    try {
                        // Show loading state
                        const originalText = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying...';
                        this.disabled = true;
                        
                        // Send request with proper headers
                        const response = await fetch('apply_plan.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                id: planId,
                                csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                            })
                        });

                        // Check for HTTP errors
                        if (!response.ok) {
                            const error = await response.json();
                            throw new Error(error.message || 'Failed to apply plan');
                        }

                        const result = await response.json();
                        
                        if (!result.success) {
                            throw new Error(result.message || 'Failed to apply plan');
                        }
                        
                        // Show success with detailed information
                        await Swal.fire({
                            title: 'Plan Applied!',
                            html: `
                                <div class="text-start">
                                    <p>Successfully applied <strong>${planName}</strong></p>
                                    <p><strong>New Daily Targets:</strong></p>
                                    <ul>
                                        <li>Calories: ${planMacros.calories}</li>
                                        <li>Protein: ${planMacros.protein}</li>
                                        <li>Carbs: ${planMacros.carbs}</li>
                                        <li>Fats: ${planMacros.fats}</li>
                                    </ul>
                                    <p class="mt-2"><small>${result.meals_added} meals generated for today</small></p>
                                </div>
                            `,
                            icon: 'success',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#44c767'
                        });
                        
                        // Refresh the page to show updated data
                        window.location.reload();
                        
                    } catch (error) {
                        console.error('Error:', error);
                        await Swal.fire({
                            title: 'Error',
                            text: error.message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                        
                        // Restore button state
                        this.innerHTML = 'Select Plan';
                        this.disabled = false;
                    }
                });
            });

            // Helper function to show error messages
            async function showError(message) {
                await Swal.fire({
                    title: 'Error',
                    text: message,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ff6b6b'
                });
            }

            // Delete meal buttons
            document.querySelectorAll('.delete-meal').forEach(button => {
                button.addEventListener('click', async function() {
                    const mealId = this.dataset.id;
                    const mealRow = this.closest('tr');
                    const mealName = mealRow.querySelector('td:first-child div').textContent;
                    
                    // Confirmation dialog
                    const confirmation = await Swal.fire({
                        title: 'Delete Meal?',
                        html: `Are you sure you want to delete <strong>${mealName}</strong>?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Delete',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#ff6b6b',
                        backdrop: true
                    });

                    if (confirmation.isConfirmed) {
                        try {
                            // Show loading state
                            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                            this.disabled = true;
                            
                            // Send delete request
                            const response = await fetch('delete_meal.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `meal_id=${mealId}&csrf_token=<?= $_SESSION['csrf_token'] ?>`
                            });

                            const result = await response.json();
                            
                            if (result.success) {
                                // Remove the row from the table
                                mealRow.remove();
                                
                                // Show success message
                                await Swal.fire({
                                    title: 'Deleted!',
                                    text: 'The meal has been deleted.',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                
                                // Reload the page to update totals
                                window.location.reload();
                            } else {
                                throw new Error(result.message || 'Failed to delete meal');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            await Swal.fire({
                                title: 'Error',
                                text: error.message,
                                icon: 'error'
                            });
                        } finally {
                            // Reset button state
                            this.innerHTML = '<i class="fas fa-trash"></i>';
                            this.disabled = false;
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>