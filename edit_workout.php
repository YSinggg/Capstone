<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['fullname'] ?? 'User';
$user_weight = $_SESSION['weight'] ?? 70; // Default to 70kg if weight not set

// Check if workout ID is provided
if (!isset($_GET['id'])) {
    header('Location: fitness_management.php');
    exit();
}

$workout_id = $_GET['id'];

// Fetch workout data
try {
    $stmt = $pdo->prepare("SELECT * FROM workouts WHERE workout_id = ? AND user_id = ?");
    $stmt->execute([$workout_id, $user_id]);
    $workout = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$workout) {
        $_SESSION['error'] = "Workout not found or you don't have permission to edit it.";
        header('Location: fitness_management.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching workout: " . $e->getMessage();
    header('Location: fitness_management.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_workout'])) {
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

        $stmt = $pdo->prepare("UPDATE workouts 
                              SET workout_type = ?, workout_date = ?, duration_minutes = ?, 
                                  calories_burned = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                              WHERE workout_id = ? AND user_id = ?");
        $stmt->execute([$workout_type, $workout_date, $duration, $calories, $notes, $workout_id, $user_id]);
        $_SESSION['success'] = "Workout updated successfully!";
        header('Location: fitness_management.php');
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating workout: " . $e->getMessage();
    }
}

// Display messages
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Workout | Kurus+</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #4a8fe7;
            --secondary: #44c767;
            --accent: #ff6b6b;
            --dark: #2c3e50;
            --light: #f8f9fa;
            --text: #333;
            --gray: #6c757d;
            --white: #ffffff;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: var(--text);
            line-height: 1.6;
        }
        
        /* Header Styles */
        header {
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
            padding: 0.8rem 2rem;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
        }
        
        .logo i {
            margin-right: 10px;
            color: var(--secondary);
        }
        
        /* Main Content Styles */
        .main-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .edit-container {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .page-header h1 {
            font-size: 2rem;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            font-size: 1rem;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
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
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--gray);
            color: var(--gray);
        }
        
        .btn-outline:hover {
            background: #f8f9fa;
        }
        
        /* Alert Styles */
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .edit-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <a href="index.php" class="logo">
                <i class="fas fa-dumbbell"></i> Kurus+
            </a>
            <nav>
                <ul style="display: flex; list-style: none; gap: 1.5rem;">
                    <li><a href="userhome.php" style="color: white; text-decoration: none;">Home</a></li>
                    <li><a href="fitness_management.php" style="color: white; text-decoration: none;">Fitness</a></li>
                    <li><a href="diet_plan.php" style="color: white; text-decoration: none;">Nutrition</a></li>
                </ul>
            </nav>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span style="color: white;"><?php echo htmlspecialchars($username); ?></span>
                <a href="profile.php" style="color: white;"><i class="fas fa-user-circle" style="font-size: 1.5rem;"></i></a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="edit-container">
            <div class="page-header">
                <h1>Edit Workout</h1>
            </div>
            
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
            
            <form method="POST" action="edit_workout.php?id=<?php echo $workout_id; ?>">
                <div class="form-group">
                    <label for="workout-type">Workout Type</label>
                    <select id="workout-type" name="workout_type" class="form-control" required onchange="calculateCalories()">
                        <option value="strength" <?php echo $workout['workout_type'] === 'strength' ? 'selected' : ''; ?>>Strength Training</option>
                        <option value="cardio" <?php echo $workout['workout_type'] === 'cardio' ? 'selected' : ''; ?>>Cardio</option>
                        <option value="hiit" <?php echo $workout['workout_type'] === 'hiit' ? 'selected' : ''; ?>>HIIT</option>
                        <option value="yoga" <?php echo $workout['workout_type'] === 'yoga' ? 'selected' : ''; ?>>Yoga/Pilates</option>
                        <option value="other" <?php echo $workout['workout_type'] === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="workout-date">Date</label>
                    <input type="date" id="workout-date" name="workout_date" class="form-control" required 
                           value="<?php echo htmlspecialchars($workout['workout_date']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="workout-duration">Duration (minutes)</label>
                    <input type="number" id="workout-duration" name="workout_duration" class="form-control" min="1" required
                           value="<?php echo htmlspecialchars($workout['duration_minutes']); ?>"
                           onchange="calculateCalories()" onkeyup="calculateCalories()">
                </div>
                
                <div class="form-group">
                    <label for="calories-burned">Calories Burned</label>
                    <input type="number" id="calories-burned" name="calories_burned" class="form-control" min="1"
                           value="<?php echo $workout['calories_burned'] ? htmlspecialchars($workout['calories_burned']) : ''; ?>">
                    <small class="text-muted">Calculated based on your weight (<?php echo $user_weight; ?>kg)</small>
                </div>
                
                <div class="form-group">
                    <label for="workout-notes">Notes</label>
                    <textarea id="workout-notes" name="workout_notes" class="form-control" rows="4"><?php echo htmlspecialchars($workout['notes'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-actions">
                    <a href="fitness_management.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" name="update_workout" class="btn">Update Workout</button>
                </div>
            </form>
        </div>
    </div>

    <footer style="background: var(--dark); color: white; padding: 2rem; text-align: center;">
        <p>© <?php echo date('Y'); ?> Kurus+ Your Fitness Management System. All rights reserved.</p>
    </footer>

    <script>
        // Calorie Calculator
        function calculateCalories() {
            const workoutType = document.getElementById('workout-type').value;
            const duration = parseFloat(document.getElementById('workout-duration').value);
            
            if (!workoutType || !duration || duration <= 0) {
                document.getElementById('calories-burned').value = '';
                return;
            }

            // MET values for different workout types (Metabolic Equivalent of Task)
            const metValues = {
                'strength': 6.0,
                'cardio': 7.0,
                'hiit': 8.0,
                'yoga': 2.5,
                'other': 5.0
            };

            // Get user's weight from PHP variable
            const userWeight = <?php echo $user_weight; ?>;
            
            // Calories burned formula: MET * weight in kg * time in hours
            const caloriesBurned = metValues[workoutType] * userWeight * (duration / 60);
            
            document.getElementById('calories-burned').value = Math.round(caloriesBurned);
        }

        // Initialize calculator on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateCalories();
        });
    </script>
</body>
</html>