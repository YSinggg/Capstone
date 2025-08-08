<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'fitness_management';

try {
    $db = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($db->connect_error) {
        throw new Exception("Database connection failed: " . $db->connect_error);
    }

    // Get user data from database
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->error);
    }
    
    $stmt->bind_param("i", $_SESSION['user_id']);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("User not found in database");
    }
    
    $user = $result->fetch_assoc();
    
    // Get workout stats
    $workout_stmt = $db->prepare("SELECT COUNT(*) as workout_count FROM workouts WHERE user_id = ?");
    $workout_stmt->bind_param("i", $_SESSION['user_id']);
    $workout_stmt->execute();
    $workout_result = $workout_stmt->get_result();
    $workout_data = $workout_result->fetch_assoc();
    $workout_count = $workout_data['workout_count'] ?? 0;
    
    // Get goal stats
    $goal_stmt = $db->prepare("SELECT COUNT(*) as goal_count FROM goals WHERE user_id = ? AND status = 'completed'");
    $goal_stmt->bind_param("i", $_SESSION['user_id']);
    $goal_stmt->execute();
    $goal_result = $goal_stmt->get_result();
    $goal_data = $goal_result->fetch_assoc();
    $goal_count = $goal_data['goal_count'] ?? 0;
    
    // Get streak data (simplified example - you'll need to implement your own streak logic)
    $streak_stmt = $db->prepare("SELECT MAX(streak) as max_streak FROM (SELECT COUNT(*) as streak FROM (SELECT DISTINCT DATE(workout_date) as date FROM workouts WHERE user_id = ? ORDER BY date DESC) as dates) as streaks");
    $streak_stmt->bind_param("i", $_SESSION['user_id']);
    $streak_stmt->execute();
    $streak_result = $streak_stmt->get_result();
    $streak_data = $streak_result->fetch_assoc();
    $streak_count = $streak_data['max_streak'] ?? 0;
    
    // Get nutrition stats from diet plan
    $nutrition_stmt = $db->prepare("SELECT SUM(calories) as total_calories, SUM(protein) as total_protein FROM meals WHERE user_id = ? AND date = CURDATE()");
    $nutrition_stmt->bind_param("i", $_SESSION['user_id']);
    $nutrition_stmt->execute();
    $nutrition_result = $nutrition_stmt->get_result();
    $nutrition_data = $nutrition_result->fetch_assoc();
    $calories_today = $nutrition_data['total_calories'] ?? 0;
    $protein_today = $nutrition_data['total_protein'] ?? 0;
    
    // Close statements and connection
    $stmt->close();
    $workout_stmt->close();
    $goal_stmt->close();
    $streak_stmt->close();
    $nutrition_stmt->close();
    $db->close();

} catch (Exception $e) {
    error_log("Profile page error: " . $e->getMessage());
    die("We're experiencing technical difficulties. Please try again later.");
}
include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | FitTrack</title>
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
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            color: var(--text);
            line-height: 1.6;
        }
        
        .member-since {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        /* Stats Section */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            padding: 1.5rem;
        }
        
        .stat-card {
            text-align: center;
            padding: 1.5rem;
            background: var(--light);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Info Sections */
        .info-section {
            padding: 2rem;
        }
        
        .section-title {
            font-size: 1.4rem;
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-title i {
            color: var(--secondary);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .info-item {
            margin-bottom: 1rem;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .info-value {
            padding: 0.75rem;
            background: var(--light);
            border-radius: 6px;
            font-size: 0.95rem;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #3a7bd5;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        /* Footer */
        footer {
            background: var(--dark);
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 2rem;
        }
        /* Progress Bar */
        .progress {
                height: 8px;
                border-radius: 4px;
                background-color: #e9ecef;
                margin-top: 0.5rem;
            }

            .progress-bar {
                height: 100%;
                border-radius: 4px;
                background-color: var(--secondary);
                transition: width 0.6s ease;
            }

            .stat-card {
                position: relative;
                text-align: center;
                padding: 1.5rem;
                background: var(--light);
                border-radius: 8px;
                transition: all 0.3s ease;
            }

            .stat-card a {
                color: var(--primary);
                text-decoration: none;
                transition: all 0.2s ease;
            }

            .stat-card a:hover {
                color: var(--secondary);
                text-decoration: underline;
            }
            .dark-mode main {
                background-color: #1a1a1a;
                color: #f0f0f0;
            }

            .dark-mode .profile-card,
            .dark-mode .info-section,
            .dark-mode .info-value,
            .dark-mode .stat-card {
                background-color: #2a2a2a;
                color: #f0f0f0;
            }

            .dark-mode .stat-value {
                color: #4aa3ff;
            }

            .dark-mode .stat-label,
            .dark-mode .info-label {
                color: #bbb;
            }

            .dark-mode .btn-primary {
                background-color: #4a8fe7;
                color: #fff;
            }

            .dark-mode .btn-primary:hover {
                background-color: #3a7bd5;
            }

            .dark-mode .progress {
                background-color: #444;
            }

            .dark-mode .progress-bar {
                background-color: #44c767;
            }

            .dark-mode footer {
                background-color: #111;
                color: #999;
            }

            .dark-mode .section-title i {
                color: #44c767;
            }

    </style>
</head>
<body>
    <main>
        <div class="profile-container">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h1 class="profile-title"><?php echo htmlspecialchars($user['fullname'] ?? 'FitTrack User'); ?></h1>
                    <p class="profile-subtitle">Fitness Enthusiast</p>
                    <div class="member-since">
                        <i class="far fa-calendar-alt"></i>
                        <span>Member since <?php echo date('F Y', strtotime($user['_created_at'] ?? 'now')); ?></span>
                    </div>
                </div>
                
                <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $workout_count; ?></div>
                <div class="stat-label">Workouts</div>
                <a href="fitness_management.php" style="font-size: 0.8rem; margin-top: 0.5rem; display: inline-block;">View Workouts</a>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $goal_count; ?></div>
                <div class="stat-label">Goals Achieved</div>
                <a href="fitness_management.php" style="font-size: 0.8rem; margin-top: 0.5rem; display: inline-block;">View Goals</a>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $streak_count; ?></div>
                <div class="stat-label">Day Streak</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">
                    <?php 
                    if (!empty($user['height'])) {
                        $height_m = $user['height'] / 100;
                        $bmi = $user['weight'] / ($height_m * $height_m);
                        echo number_format($bmi, 1);
                    } else {
                        echo '---';
                    }
                    ?>
                </div>
                <div class="stat-label">BMI</div>
                <a href="diet_plan.php" style="font-size: 0.8rem; margin-top: 0.5rem; display: inline-block;">View Nutrition</a>
            </div>
        </div>
        <div class="info-section">
            <h2 class="section-title"><i class="fas fa-utensils"></i> Today's Nutrition</h2>
            
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Calories</span>
                    <div class="info-value">
                        <?php echo $calories_today; ?> kcal
                        <div class="progress" style="height: 5px; margin-top: 5px;">
                            <div class="progress-bar" style="width: <?php echo min(100, ($calories_today/2000)*100); ?>%;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Protein</span>
                    <div class="info-value">
                        <?php echo $protein_today; ?>g
                        <?php if (!empty($user['weight'])): ?>
                        <div class="progress" style="height: 5px; margin-top: 5px;">
                            <div class="progress-bar" style="width: <?php echo min(100, ($protein_today/($user['weight']*2.2))*100); ?>%;"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <a href="diet_plan.php" class="btn btn-primary" style="margin-top: 1.5rem;">
                <i class="fas fa-utensils"></i> View Full Nutrition
            </a>
        </div>
                
                <div class="info-section">
                    <h2 class="section-title"><i class="fas fa-user-circle"></i> Personal Information</h2>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Username</span>
                            <div class="info-value"><?php echo htmlspecialchars($user['fullname'] ?? 'Not specified'); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <div class="info-value"><?php echo htmlspecialchars($user['email'] ?? 'Not specified'); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Height</span>
                            <div class="info-value">
                                <?php 
                                if (!empty($user['height'])) {
                                    echo htmlspecialchars($user['height']) . ' cm';
                                } else {
                                    echo 'Not specified';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Weight</span>
                            <div class="info-value">
                                <?php 
                                if (!empty($user['weight'])) {
                                    echo htmlspecialchars($user['weight']) . ' kg';
                                } else {
                                    echo 'Not specified';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Gender</span>
                            <div class="info-value"><?php echo htmlspecialchars($user['gender'] ?? 'Not specified'); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Age</span>
                            <div class="info-value"><?php echo htmlspecialchars($user['age'] ?? 'Not specified'); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Fitness Goal</span>
                            <div class="info-value"><?php echo htmlspecialchars($user['fitness_goal'] ?? 'Not specified'); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">User Role</span>
                            <div class="info-value"><?php echo htmlspecialchars($user['_user_role'] ?? 'Not specified'); ?></div>
                        </div>
                    </div>
                    
                    <a href="settings.php" class="btn btn-primary" style="margin-top: 1.5rem;">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>© <?php echo date('Y'); ?> Kurus+ Your Fitness Management System. All rights reserved.</p>
    </footer>
</body>
</html>