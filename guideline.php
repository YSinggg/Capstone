<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection (for header user info)
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'fitness_management';

try {
    $db = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($db->connect_error) {
        throw new Exception("Database connection failed: " . $db->connect_error);
    }

    // Get minimal user data for header
    $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $db->close();

} catch (Exception $e) {
    error_log("Guidelines page error: " . $e->getMessage());
    // Continue loading page even if DB fails (just for header)
}
include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Guidelines | Kurus+</title>
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
        
        /* footer */
        footer {
            background: var(--dark);
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 2rem;
        }
        
        /* Guidelines Page Specific Styles */
        .guidelines-hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
        }
        
        .guidelines-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .guidelines-hero p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto 2rem;
        }
        
        .guidelines-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .workout-category {
            margin-bottom: 3rem;
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .category-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--light);
        }
        
        .category-icon {
            width: 60px;
            height: 60px;
            background: var(--secondary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-right: 1.5rem;
        }
        
        .category-title {
            font-size: 1.8rem;
            color: var(--dark);
            margin: 0;
        }
        
        .category-description {
            margin-bottom: 2rem;
            font-size: 1.1rem;
            line-height: 1.7;
        }
        
        .guidelines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .guideline-card {
            background: var(--light);
            border-radius: 8px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .guideline-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .guideline-title {
            font-size: 1.3rem;
            color: var(--primary);
            margin-top: 0;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .guideline-title i {
            color: var(--secondary);
        }
        
        .guideline-content {
            font-size: 0.95rem;
            line-height: 1.7;
        }
        
        .guideline-content ul {
            padding-left: 1.2rem;
        }
        
        .guideline-content li {
            margin-bottom: 0.5rem;
        }
        
        .benefits-section, .tips-section {
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(74, 143, 231, 0.05);
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }
        
        .section-subtitle {
            font-size: 1.2rem;
            color: var(--dark);
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-subtitle i {
            color: var(--secondary);
        }
        
        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            margin: 2rem 0;
            border-radius: 8px;
        }
        
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .difficulty-level {
            display: inline-flex;
            align-items: center;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .beginner {
            background: rgba(68, 199, 103, 0.2);
            color: #2e7d32;
        }
        
        .intermediate {
            background: rgba(255, 171, 0, 0.2);
            color: #ff8f00;
        }
        
        .advanced {
            background: rgba(239, 83, 80, 0.2);
            color: #c62828;
        }
        
        /* Start Workout Card */
        .start-workout-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 350px;
            margin: 2rem auto;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .start-workout-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.15);
        }
        
        .start-workout-card .card-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
        }
        
        .start-workout-card h3 {
            font-size: 1.5rem;
            color: var(--dark);
            margin: 0 0 0.5rem;
        }
        
        .start-workout-card p {
            font-size: 1rem;
            color: var(--gray);
            margin-bottom: 1.5rem;
        }
        
        .start-workout-card .card-button {
            display: inline-block;
            background: var(--secondary);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .start-workout-card .card-button:hover {
            background: #3aa856;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(68, 199, 103, 0.3);
        }
        
        .start-workout-card .card-button i {
            margin-left: 0.5rem;
            transition: transform 0.3s ease;
        }
        
        .start-workout-card .card-button:hover i {
            transform: translateX(3px);
        }
        
        /* Video Guidelines Section */
        .video-guidelines {
            margin: 2rem 0;
        }
        
        .video-section-title {
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .video-section-title i {
            color: var(--accent);
        }
        
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .video-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .video-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .video-card h4 {
            padding: 1rem 1rem 0.5rem;
            margin: 0;
            font-size: 1.2rem;
            color: var(--primary);
        }
        
        .video-card p {
            padding: 0 1rem 1rem;
            margin: 0;
            color: var(--gray);
            font-size: 0.95rem;
        }
        
        .video-card .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
        }
        
        .video-card .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .category-header {
                flex-direction: column;
                text-align: center;
            }
            
            .category-icon {
                margin-right: 0;
                margin-bottom: 1rem;
            }
        }
        /* Meal Plans Section */
            .meal-plans {
                margin: 3rem 0;
            }
            
            .plan-filters {
                display: flex;
                gap: 0.8rem;
                flex-wrap: wrap;
                margin-bottom: 2rem;
            }
            
            .filter-btn {
                padding: 0.6rem 1.2rem;
                border: none;
                border-radius: 50px;
                background: var(--light);
                color: var(--dark);
                cursor: pointer;
                transition: all 0.3s ease;
                font-weight: 500;
            }
            
            .filter-btn:hover, .filter-btn.active {
                background: var(--primary);
                color: white;
            }
            
            .plans-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 1.5rem;
            }
            
            .plan-card {
                background: white;
                border-radius: 8px;
                padding: 1.5rem;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                transition: all 0.3s ease;
            }
            
            .plan-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            }
            
            .plan-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1rem;
            }
            
            .plan-header h4 {
                margin: 0;
                font-size: 1.2rem;
                color: var(--dark);
            }
            
            .plan-type {
                font-size: 0.8rem;
                padding: 0.3rem 0.8rem;
                border-radius: 50px;
                font-weight: 600;
            }
            
            .muscle-gain {
                background: rgba(74, 143, 231, 0.2);
                color: var(--primary);
            }
            
            .weight-loss {
                background: rgba(68, 199, 103, 0.2);
                color: #2e7d32;
            }
            
            .maintenance {
                background: rgba(255, 193, 7, 0.2);
                color: #ff8f00;
            }
            
            .vegan {
                background: rgba(102, 187, 106, 0.2);
                color: #388e3c;
            }
            
            .plan-description {
                color: var(--gray);
                font-size: 0.95rem;
                margin-bottom: 1.5rem;
            }
            
            .plan-macros {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
                margin-bottom: 1.5rem;
            }
            
            .macro {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                font-size: 0.9rem;
            }
            
            .macro i {
                color: var(--primary);
            }
            
            .sample-meals {
                margin-bottom: 1.5rem;
            }
            
            .sample-meals h5 {
                margin: 0 0 0.5rem;
                font-size: 1rem;
                color: var(--dark);
            }
            
            .sample-meals ul {
                padding-left: 1.2rem;
                margin: 0;
                font-size: 0.9rem;
            }
            
            .sample-meals li {
                margin-bottom: 0.3rem;
            }
            
            .sample-meals {
            margin-bottom: 0;
        }
            
            /* Nutrition Tips */
            .nutrition-tips {
                margin-top: 3rem;
            }
            
            .tips-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1.5rem;
                margin-top: 1.5rem;
            }
            
            .tip-card {
                background: white;
                border-radius: 8px;
                padding: 1.5rem;
                text-align: center;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            
            .tip-card i {
                font-size: 2rem;
                color: var(--primary);
                margin-bottom: 1rem;
            }
            
            .tip-card h4 {
                margin: 0.5rem 0;
                font-size: 1.1rem;
                color: var(--dark);
            }
            
            .tip-card p {
                margin: 0;
                font-size: 0.9rem;
                color: var(--gray);
            }
            /* Dark Mode Styles */
            body.dark-mode {
                background-color: #121212;
                color: #e0e0e0;
            }

            body.dark-mode .workout-category,
            body.dark-mode .guideline-card,
            body.dark-mode .benefits-section,
            body.dark-mode .tips-section,
            body.dark-mode .plan-card,
            body.dark-mode .tip-card,
            body.dark-mode .start-workout-card {
                background-color: #1e1e1e;
                color: #e0e0e0;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            }

            body.dark-mode .category-title,
            body.dark-mode .guideline-title,
            body.dark-mode .section-subtitle,
            body.dark-mode .plan-header h4,
            body.dark-mode .tip-card h4,
            body.dark-mode .start-workout-card h3 {
                color: #ffffff;
            }

            body.dark-mode .category-description,
            body.dark-mode .guideline-content,
            body.dark-mode .plan-description,
            body.dark-mode .tip-card p,
            body.dark-mode .start-workout-card p {
                color: #b0b0b0;
            }

            body.dark-mode .benefits-section,
            body.dark-mode .tips-section {
                background-color: rgba(74, 143, 231, 0.1);
                border-left: 4px solid var(--primary);
            }

            body.dark-mode .filter-btn {
                background-color: #2d2d2d;
                color: #e0e0e0;
            }

            body.dark-mode .filter-btn:hover,
            body.dark-mode .filter-btn.active {
                background-color: var(--primary);
                color: white;
            }

            /* Make sure links remain visible in dark mode */
            body.dark-mode a {
                color: #e0e0e0;
            }

            body.dark-mode .profile-dropdown {
                background-color: #2c3e50;
            }

            body.dark-mode .profile-dropdown a {
                color: #e0e0e0;
            }

            body.dark-mode .profile-dropdown a:hover {
                background-color: #3a4e63;
                color: white;
            }
    </style>
</head>
<body>
    <main>
        <section class="guidelines-hero">
            <h1>Fitness Guidelines</h1>
            <p>Learn proper techniques and best practices for all types of workouts</p>
            
            <div class="start-workout-card">
                <div class="card-icon">
                    <i class="fas fa-dumbbell"></i>
                </div>
                <h3>Ready to Begin?</h3>
                <p>Start your personalized workout plan today</p>
                <a href="fitness_management.php" class="card-button">
                    Start Workout <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </section>
        
        <div class="guidelines-container">
            <!-- Strength Training Section -->
            <div class="workout-category">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <h2 class="category-title">Strength Training</h2>
                </div>
                
                <p class="category-description">
                    Strength training involves using resistance to induce muscular contraction to build strength, 
                    anaerobic endurance, and size of skeletal muscles. It's essential for overall health, 
                    metabolism, and functional fitness.
                </p>
                
                <div class="video-guidelines">
                    <h3 class="video-section-title"><i class="fas fa-video"></i> Video Guidelines</h3>
                    <div class="video-grid">
                        <div class="video-card">
                            <h4>Beginner Strength Training</h4>
                            <div class="video-container">
                                <iframe src="https://www.youtube.com/embed/vcBig73ojpE" frameborder="0" allowfullscreen></iframe>
                            </div>
                            <p>Learn the fundamentals of strength training with proper form and technique.</p>
                        </div>
                        
                        <div class="video-card">
                            <h4>Essential Exercises</h4>
                            <div class="video-container">
                                <iframe 
                                    src="https://www.youtube.com/embed/QQYviTCnKWU" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen>
                                </iframe>
                            </div>
                            <p>Master the 4 essential strength training exercises for maximum results.</p>
                        </div>
                        
                        <div class="video-card">
                            <h4>Progressive Overload</h4>
                            <div class="video-container">
                                <iframe 
                                    src="https://www.youtube.com/embed/tAMVhU5BxTg" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen>
                                </iframe>
                            </div>
                            <p>Advanced techniques to continuously challenge your muscles for growth.</p>
                        </div>
                    </div>
                </div>
                
                <div class="guidelines-grid">
                    <div class="guideline-card">
                        <span class="difficulty-level beginner">Beginner</span>
                        <h3 class="guideline-title"><i class="fas fa-check-circle"></i> Basic Principles</h3>
                        <div class="guideline-content">
                            <ul>
                                <li>Start with bodyweight exercises before adding weights</li>
                                <li>Focus on proper form over heavy weights</li>
                                <li>Work all major muscle groups 2-3 times per week</li>
                                <li>Rest 48 hours between working the same muscle groups</li>
                                <li>Perform 2-4 sets of 8-12 repetitions per exercise</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="guideline-card">
                        <span class="difficulty-level intermediate">Intermediate</span>
                        <h3 class="guideline-title"><i class="fas fa-dumbbell"></i> Essential Exercises</h3>
                        <div class="guideline-content">
                            <ul>
                                <li><strong>Squats:</strong> For legs and core</li>
                                <li><strong>Deadlifts:</strong> For posterior chain</li>
                                <li><strong>Bench Press:</strong> For chest and arms</li>
                                <li><strong>Overhead Press:</strong> For shoulders</li>
                                <li><strong>Pull-ups/Rows:</strong> For back</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="guideline-card">
                        <span class="difficulty-level advanced">Advanced</span>
                        <h3 class="guideline-title"><i class="fas fa-chart-line"></i> Progressive Overload</h3>
                        <div class="guideline-content">
                            <ul>
                                <li>Gradually increase weight over time</li>
                                <li>Increase repetitions or sets</li>
                                <li>Decrease rest time between sets</li>
                                <li>Incorporate advanced techniques like drop sets</li>
                                <li>Change exercises every 4-6 weeks</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="benefits-section">
                    <h3 class="section-subtitle"><i class="fas fa-heart"></i> Benefits</h3>
                    <div class="guideline-content">
                        <ul>
                            <li>Increases muscle mass and strength</li>
                            <li>Boosts metabolism and fat burning</li>
                            <li>Strengthens bones and connective tissue</li>
                            <li>Improves posture and reduces injury risk</li>
                            <li>Enhances athletic performance</li>
                        </ul>
                    </div>
                </div>
                
                <div class="tips-section">
                    <h3 class="section-subtitle"><i class="fas fa-lightbulb"></i> Safety Tips</h3>
                    <div class="guideline-content">
                        <ul>
                            <li>Always warm up for 5-10 minutes before lifting</li>
                            <li>Use spotters for heavy lifts</li>
                            <li>Maintain proper breathing (exhale on exertion)</li>
                            <li>Start with lighter weights to learn form</li>
                            <li>Listen to your body and rest when needed</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Cardio Section -->
            <div class="workout-category">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-running"></i>
                    </div>
                    <h2 class="category-title">Cardiovascular Exercise</h2>
                </div>
                
                <p class="category-description">
                    Cardiovascular exercise improves heart and lung health while burning calories. 
                    It includes any activity that raises your heart rate for sustained periods.
                </p>
                
                <div class="video-guidelines">
                    <h3 class="video-section-title"><i class="fas fa-video"></i> Video Guidelines</h3>
                    <div class="video-grid">
                        <div class="video-card">
                            <h4>Cardio for Beginners</h4>
                            <div class="video-container">
                                <iframe src="https://www.youtube.com/embed/-yMkmCGkwXo" frameborder="0" allowfullscreen></iframe>
                            </div>
                            <p>Gentle introduction to cardiovascular exercise for all fitness levels.</p>
                        </div>

                        
                        <div class="video-card">
                            <h4>HIIT Cardio Workout</h4>
                            <div class="video-container">
                                <iframe src="https://www.youtube.com/embed/JlTzxaaYG5I" frameborder="0" allowfullscreen></iframe>
                            </div>
                            <p>High intensity interval training to maximize calorie burn in minimal time.</p>
                        </div>
                        
                        <div class="video-card">
                            <h4>Running Techniques</h4>
                            <div class="video-container">
                                <iframe src="https://www.youtube.com/embed/_kGESn8ArrU" frameborder="0" allowfullscreen></iframe>
                            </div>
                            <p>Proper running form and techniques to prevent injury and improve efficiency.</p>
                        </div>
                    </div>
                </div>
                
                <div class="guidelines-grid">
                    <div class="guideline-card">
                        <span class="difficulty-level beginner">Beginner</span>
                        <h3 class="guideline-title"><i class="fas fa-heartbeat"></i> Getting Started</h3>
                        <div class="guideline-content">
                            <ul>
                                <li>Start with 10-15 minutes of moderate activity</li>
                                <li>Choose low-impact options if needed (walking, cycling)</li>
                                <li>Aim for 3 days per week, gradually increasing</li>
                                <li>Use the "talk test" - you should be able to talk but not sing</li>
                                <li>Include a 5-minute warm-up and cool-down</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="guideline-card">
                        <span class="difficulty-level intermediate">Intermediate</span>
                        <h3 class="guideline-title"><i class="fas fa-tachometer-alt"></i> Types of Cardio</h3>
                        <div class="guideline-content">
                            <ul>
                                <li><strong>Steady State:</strong> Moderate pace for 30+ minutes</li>
                                <li><strong>Interval Training:</strong> Alternate high/low intensity</li>
                                <li><strong>Cross-Training:</strong> Mix different cardio types</li>
                                <li><strong>Fartlek:</strong> Speed play with varied pacing</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="guideline-card">
                        <span class="difficulty-level advanced">Advanced</span>
                        <h3 class="guideline-title"><i class="fas fa-bolt"></i> Advanced Techniques</h3>
                        <div class="guideline-content">
                            <ul>
                                <li>Incorporate hill or incline training</li>
                                <li>Try tempo runs at threshold pace</li>
                                <li>Use heart rate zones for targeted training</li>
                                <li>Combine cardio with strength (circuit training)</li>
                                <li>Progress to 5-6 days per week with varied intensity</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="benefits-section">
                    <h3 class="section-subtitle"><i class="fas fa-heart"></i> Benefits</h3>
                    <div class="guideline-content">
                        <ul>
                            <li>Strengthens heart and lungs</li>
                            <li>Burns calories and aids weight management</li>
                            <li>Reduces risk of chronic diseases</li>
                            <li>Improves mood and reduces stress</li>
                            <li>Increases stamina and endurance</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- HIIT Section -->
            <div class="workout-category">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-fire"></i>
                    </div>
                    <h2 class="category-title">HIIT (High Intensity Interval Training)</h2>
                </div>
                
                <p class="category-description">
                    HIIT alternates short bursts of intense exercise with recovery periods. 
                    It's time-efficient and provides excellent cardiovascular and metabolic benefits.
                </p>
                
                <div class="video-guidelines">
                    <h3 class="video-section-title"><i class="fas fa-video"></i> Video Guidelines</h3>
                    <div class="video-grid">
                        <div class="video-card">
                            <h4>HIIT Fundamentals</h4>
                            <div class="video-container">
                                <iframe src="https://www.youtube.com/embed/dNJ2gG-Jud4?start=13" frameborder="0" allowfullscreen></iframe>
                            </div>
                            <p>Learn the core principles of high intensity interval training.</p>
                        </div>
                        
                        <div class="video-card">
                            <h4>20-Minute HIIT Workout</h4>
                            <div class="video-container">
                                <iframe src="https://www.youtube.com/embed/ml6cT4AZdqI" frameborder="0" allowfullscreen></iframe>
                            </div>
                            <p>Full-body HIIT routine you can do anywhere with no equipment.</p>
                        </div>
                        
                        <div class="video-card">
                            <h4>HIIT Safety & Modifications</h4>
                            <div class="video-container">
                                <iframe src="https://www.youtube.com/embed/C4Y2JBckHJI" frameborder="0" allowfullscreen></iframe>
                            </div>
                            <p>How to perform HIIT safely and modify for different fitness levels.</p>
                        </div>
                    </div>
                </div>
                
                <div class="guidelines-grid">
                    <div class="guideline-card">
                        <span class="difficulty-level intermediate">Intermediate</span>
                        <h3 class="guideline-title"><i class="fas fa-stopwatch"></i> Basic Structure</h3>
                        <div class="guideline-content">
                            <ul>
                                <li>Warm up for 5-10 minutes</li>
                                <li>Alternate 20-60 seconds of maximum effort with 10-60 seconds rest</li>
                                <li>Repeat for 10-30 minutes total</li>
                                <li>Cool down for 5 minutes</li>
                                <li>Start with 1-2 sessions per week</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="guideline-card">
                        <span class="difficulty-level intermediate">Intermediate</span>
                        <h3 class="guideline-title"><i class="fas fa-dumbbell"></i> Sample Workouts</h3>
                        <div class="guideline-content">
                            <ul>
                                <li><strong>Tabata:</strong> 20 sec work/10 sec rest x 8 rounds</li>
                                <li><strong>30/30:</strong> 30 sec work/30 sec rest x 10-15 rounds</li>
                                <li><strong>Pyramid:</strong> 20/10, 30/20, 40/30, then back down</li>
                                <li><strong>EMOM:</strong> Every minute on the minute do a set</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="guideline-card">
                        <span class="difficulty-level advanced">Advanced</span>
                        <h3 class="guideline-title"><i class="fas fa-user-shield"></i> Safety Considerations</h3>
                        <div class="guideline-content">
                            <ul>
                                <li>Not recommended for complete beginners</li>
                                <li>Ensure proper form even when fatigued</li>
                                <li>Stay hydrated and watch for dizziness</li>
                                <li>Allow 48 hours between HIIT sessions</li>
                                <li>Combine with strength training for balance</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Yoga/Pilates Section -->
            <div class="workout-category">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-spa"></i>
                    </div>
                    <h2 class="category-title">Yoga & Pilates</h2>
                </div>
                
                <p class="category-description">
                    Yoga and Pilates focus on flexibility, core strength, and mind-body connection. 
                    They complement other forms of exercise and promote overall wellbeing.
                </p>
                
                <div class="video-guidelines">
                    <h3 class="video-section-title"><i class="fas fa-video"></i> Video Guidelines</h3>
                    <div class="video-grid">
                        <div class="video-card">
                            <h4>Yoga for Beginners</h4>
                            <div class="video-container">
                                <iframe src="https://www.youtube.com/embed/v7AYKMP6rOE" frameborder="0" allowfullscreen></iframe>
                            </div>
                            <p>Gentle introduction to basic yoga poses and breathing techniques.</p>
                        </div>
                        
                        <div class="video-card">
                            <h4>Pilates Fundamentals</h4>
                            <div class="video-container">
                                <iframe src="https://www.youtube.com/embed/Sw6sy8NZCSY" frameborder="0" allowfullscreen></iframe>
                            </div>
                            <p>Learn the core principles and exercises of Pilates.</p>
                        </div>
                        
                        <div class="video-card">
                            <h4>Morning Yoga Flow</h4>
                            <div class="video-container">
                                <iframe src="https://www.youtube.com/embed/4pKly2JojMw" frameborder="0" allowfullscreen></iframe>
                            </div>
                            <p>Energizing yoga sequence perfect for starting your day.</p>
                        </div>
                    </div>
                </div>
                
                <div class="guidelines-grid">
                    <div class="guideline-card">
                        <span class="difficulty-level beginner">Beginner</span>
                        <h3 class="guideline-title"><i class="fas fa-hands-helping"></i> Getting Started</h3>
                        <div class="guideline-content">
                            <ul>
                                <li>Start with beginner classes or videos</li>
                                <li>Focus on breathing and proper alignment</li>
                                <li>Use props (blocks, straps) as needed</li>
                                <li>Practice 2-3 times per week</li>
                                <li>Listen to your body - don't force positions</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="guideline-card">
                        <span class="difficulty-level intermediate">Intermediate</span>
                        <h3 class="guideline-title"><i class="fas fa-leaf"></i> Key Poses/Exercises</h3>
                        <div class="guideline-content">
                            <ul>
                                <li><strong>Yoga:</strong> Downward Dog, Warrior poses, Tree pose</li>
                                <li><strong>Pilates:</strong> The Hundred, Roll Up, Single Leg Stretch</li>
                                <li>Focus on core engagement in all movements</li>
                                <li>Practice proper breathing techniques</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="guideline-card">
                        <span class="difficulty-level advanced">Advanced</span>
                        <h3 class="guideline-title"><i class="fas fa-infinity"></i> Advanced Practice</h3>
                        <div class="guideline-content">
                            <ul>
                                <li>Explore advanced poses gradually</li>
                                <li>Try power yoga or reformer Pilates</li>
                                <li>Incorporate meditation and mindfulness</li>
                                <li>Attend workshops for specific skills</li>
                                <li>Consider teacher training for deep knowledge</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="benefits-section">
                    <h3 class="section-subtitle"><i class="fas fa-heart"></i> Benefits</h3>
                    <div class="guideline-content">
                        <ul>
                            <li>Improves flexibility and mobility</li>
                            <li>Strengthens core muscles</li>
                            <li>Reduces stress and anxiety</li>
                            <li>Enhances body awareness</li>
                            <li>Improves posture and balance</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Additional Workouts Section -->
            <div class="workout-category">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <h2 class="category-title">Additional Workout Types</h2>
                </div>
                
                <p class="category-description">
                    Explore these other effective workout styles to keep your routine varied and engaging.
                </p>
                
                <div class="video-guidelines">
                    <h3 class="video-section-title"><i class="fas fa-video"></i> Video Guidelines</h3>
                    <div class="video-grid">
                        <div class="video-card">
                            <h4>Swimming Techniques</h4>
                            <div class="video-container">
                                <iframe src="https://www.youtube.com/embed/pFN2n7CRqhw" frameborder="0" allowfullscreen></iframe>
                            </div>
                            <p>Learn proper swimming form for all major strokes.</p>
                        </div>
                        
                        <div class="video-card">
                            <h4>Cycling Workout</h4>
                            <div class="video-container">
                                <iframe src="https://www.youtube.com/embed/TY0f2mgR3GI" frameborder="0" allowfullscreen></iframe>
                            </div>
                            <p>Indoor cycling routine for all fitness levels.</p>
                        </div>
                        
                        <div class="video-card">
                            <h4>Functional Training</h4>
                            <div class="video-container">
                                <iframe src="https://www.youtube.com/embed/Vc9lqjIKfoo" frameborder="0" allowfullscreen></iframe>
                            </div>
                            <p>Exercises that improve movements for daily life activities.</p>
                        </div>
                    </div>
                </div>
                
                <div class="guidelines-grid">
                    <div class="guideline-card">
                        <h3 class="guideline-title"><i class="fas fa-swimmer"></i> Swimming</h3>
                        <div class="guideline-content">
                            <ul>
                                <li>Full-body, low-impact workout</li>
                                <li>Start with 20-30 minutes, 2-3x/week</li>
                                <li>Learn proper stroke techniques</li>
                                <li>Use different strokes to work all muscles</li>
                                <li>Great for recovery and cross-training</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="guideline-card">
                        <h3 class="guideline-title"><i class="fas fa-biking"></i> Cycling</h3>
                        <div class="guideline-content">
                            <ul>
                                <li>Outdoor or stationary bike options</li>
                                <li>Adjust seat height to prevent knee strain</li>
                                <li>Vary terrain/resistance for intensity</li>
                                <li>Use proper cycling posture</li>
                                <li>Combine endurance and sprint intervals</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="guideline-card">
                        <h3 class="guideline-title"><i class="fas fa-hiking"></i> Functional Training</h3>
                        <div class="guideline-content">
                            <ul>
                                <li>Mimics real-life movements</li>
                                <li>Uses kettlebells, TRX, resistance bands</li>
                                <li>Focuses on multi-joint exercises</li>
                                <li>Improves daily movement patterns</li>
                                <li>Great for all fitness levels</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
               <div class="workout-category">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h2 class="category-title">Nutrition Guidelines & Meal Plans</h2>
                </div>
                
                <p class="category-description">
                    Proper nutrition is just as important as exercise for achieving your fitness goals. 
                    These meal plans are designed to complement your workout routine and help you reach your objectives.
                </p>
                
                <div class="video-guidelines">
                    <h3 class="video-section-title"><i class="fas fa-video"></i> Nutrition Basics</h3>
                    <div class="video-grid">
                        <div class="video-card">
                            <h4>Meal Planning 101</h4>
                            <div class="video-container">
                                <iframe src="https://www.youtube.com/embed/aADukThvjXQ" frameborder="0" allowfullscreen></iframe>
                            </div>
                            <p>Learn how to create balanced meals that support your fitness goals.</p>
                        </div>
                        
                        <div class="video-card">
                            <h4>Macros Explained</h4>
                            <div class="video-container">
                                <iframe src="https://www.youtube.com/embed/kcBpFu4cuBA?start=14" frameborder="0" allowfullscreen></iframe>
                            </div>
                            <p>Understanding proteins, carbs, and fats for optimal performance.</p>
                        </div>
                        
                        <div class="video-card">
                            <h4>Meal Prep Guide</h4>
                            <div class="video-container">
                                <iframe src="https://www.youtube.com/embed/NO-EbXMB4gc" frameborder="0" allowfullscreen></iframe>
                            </div>
                            <p>Time-saving strategies for preparing healthy meals all week.</p>
                        </div>
                    </div>
                </div>
                
                <div class="meal-plans">
                    <h3 class="section-subtitle"><i class="fas fa-clipboard-list"></i> Available Meal Plans</h3>
                    
                    <div class="plan-filters">
                        <button class="filter-btn active" onclick="filterPlans('all')">All Plans</button>
                        <button class="filter-btn" onclick="filterPlans('muscle_gain')">Muscle Gain</button>
                        <button class="filter-btn" onclick="filterPlans('weight_loss')">Weight Loss</button>
                        <button class="filter-btn" onclick="filterPlans('maintenance')">Maintenance</button>
                        <button class="filter-btn" onclick="filterPlans('vegan')">Vegan</button>
                    </div>
                    
                    <div class="plans-grid">
                        <!-- Lean Muscle Builder -->
                        <div class="plan-card muscle_gain">
                            <div class="plan-header">
                                <h4>Lean Muscle Builder</h4>
                                <span class="plan-type muscle-gain">Muscle Gain</span>
                            </div>
                            <p class="plan-description">High protein plan designed to support muscle growth while maintaining lean physique</p>
                            <div class="plan-macros">
                                <div class="macro">
                                    <i class="fas fa-bolt"></i>
                                    <span>2800 kcal</span>
                                </div>
                                <div class="macro">
                                    <i class="fas fa-dumbbell"></i>
                                    <span>180g protein</span>
                                </div>
                                <div class="macro">
                                    <i class="fas fa-bread-slice"></i>
                                    <span>250g carbs</span>
                                </div>
                                <div class="macro">
                                    <i class="fas fa-oil-can"></i>
                                    <span>90g fats</span>
                                </div>
                            </div>
                            <div class="sample-meals">
                                <h5>Sample Day:</h5>
                                <ul>
                                    <li><strong>Breakfast:</strong> Egg whites, oatmeal, almonds</li>
                                    <li><strong>Lunch:</strong> Grilled chicken, quinoa, broccoli</li>
                                    <li><strong>Dinner:</strong> Salmon, sweet potato, asparagus</li>
                                    <li><strong>Snacks:</strong> Greek yogurt, protein shake</li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Balanced Weight Loss -->
                        <div class="plan-card weight_loss">
                            <div class="plan-header">
                                <h4>Balanced Weight Loss</h4>
                                <span class="plan-type weight-loss">Weight Loss</span>
                            </div>
                            <p class="plan-description">Moderate carb, high protein plan for sustainable fat loss</p>
                            <div class="plan-macros">
                                <div class="macro">
                                    <i class="fas fa-bolt"></i>
                                    <span>1800 kcal</span>
                                </div>
                                <div class="macro">
                                    <i class="fas fa-dumbbell"></i>
                                    <span>140g protein</span>
                                </div>
                                <div class="macro">
                                    <i class="fas fa-bread-slice"></i>
                                    <span>150g carbs</span>
                                </div>
                                <div class="macro">
                                    <i class="fas fa-oil-can"></i>
                                    <span>60g fats</span>
                                </div>
                            </div>
                            <div class="sample-meals">
                                <h5>Sample Day:</h5>
                                <ul>
                                    <li><strong>Breakfast:</strong> Scrambled eggs, avocado, berries</li>
                                    <li><strong>Lunch:</strong> Turkey breast, brown rice, mixed veggies</li>
                                    <li><strong>Dinner:</strong> Grilled fish, cauliflower rice, zucchini</li>
                                    <li><strong>Snacks:</strong> Cottage cheese, almonds</li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Maintenance Mode -->
                        <div class="plan-card maintenance">
                            <div class="plan-header">
                                <h4>Maintenance Mode</h4>
                                <span class="plan-type maintenance">Maintenance</span>
                            </div>
                            <p class="plan-description">Balanced macros to maintain current weight and performance</p>
                            <div class="plan-macros">
                                <div class="macro">
                                    <i class="fas fa-bolt"></i>
                                    <span>2300 kcal</span>
                                </div>
                                <div class="macro">
                                    <i class="fas fa-dumbbell"></i>
                                    <span>120g protein</span>
                                </div>
                                <div class="macro">
                                    <i class="fas fa-bread-slice"></i>
                                    <span>250g carbs</span>
                                </div>
                                <div class="macro">
                                    <i class="fas fa-oil-can"></i>
                                    <span>80g fats</span>
                                </div>
                            </div>
                            <div class="sample-meals">
                                <h5>Sample Day:</h5>
                                <ul>
                                    <li><strong>Breakfast:</strong> Whole eggs, whole wheat toast, fruit</li>
                                    <li><strong>Lunch:</strong> Chicken wrap with whole grain tortilla</li>
                                    <li><strong>Dinner:</strong> Lean steak, mashed potatoes, green beans</li>
                                    <li><strong>Snacks:</strong> Protein bar, mixed nuts</li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- High Protein Vegan -->
                        <div class="plan-card muscle_gain vegan">
                            <div class="plan-header">
                                <h4>High Protein Vegan</h4>
                                <span class="plan-type vegan">Vegan</span>
                            </div>
                            <p class="plan-description">Plant-based meal plan with complete proteins for muscle maintenance</p>
                            <div class="plan-macros">
                                <div class="macro">
                                    <i class="fas fa-bolt"></i>
                                    <span>2500 kcal</span>
                                </div>
                                <div class="macro">
                                    <i class="fas fa-dumbbell"></i>
                                    <span>160g protein</span>
                                </div>
                                <div class="macro">
                                    <i class="fas fa-bread-slice"></i>
                                    <span>300g carbs</span>
                                </div>
                                <div class="macro">
                                    <i class="fas fa-oil-can"></i>
                                    <span>70g fats</span>
                                </div>
                            </div>
                            <div class="sample-meals">
                                <h5>Sample Day:</h5>
                                <ul>
                                    <li><strong>Breakfast:</strong> Tofu scramble, quinoa, spinach</li>
                                    <li><strong>Lunch:</strong> Lentil curry, brown rice</li>
                                    <li><strong>Dinner:</strong> Tempeh stir-fry with mixed vegetables</li>
                                    <li><strong>Snacks:</strong> Edamame, vegan protein shake</li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Low Carb Fat Burner -->
                        <div class="plan-card weight_loss">
                            <div class="plan-header">
                                <h4>Low Carb Fat Burner</h4>
                                <span class="plan-type weight-loss">Weight Loss</span>
                            </div>
                            <p class="plan-description">Reduced carbohydrate plan to promote fat burning</p>
                            <div class="plan-macros">
                                <div class="macro">
                                    <i class="fas fa-bolt"></i>
                                    <span>1600 kcal</span>
                                </div>
                                <div class="macro">
                                    <i class="fas fa-dumbbell"></i>
                                    <span>130g protein</span>
                                </div>
                                <div class="macro">
                                    <i class="fas fa-bread-slice"></i>
                                    <span>100g carbs</span>
                                </div>
                                <div class="macro">
                                    <i class="fas fa-oil-can"></i>
                                    <span>90g fats</span>
                                </div>
                            </div>
                            <div class="sample-meals">
                                <h5>Sample Day:</h5>
                                <ul>
                                    <li><strong>Breakfast:</strong> Omelet with cheese and veggies</li>
                                    <li><strong>Lunch:</strong> Chicken salad with olive oil dressing</li>
                                    <li><strong>Dinner:</strong> Pork chops with roasted Brussels sprouts</li>
                                    <li><strong>Snacks:</strong> Cheese cubes, avocado</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="nutrition-tips">
                    <h3 class="section-subtitle"><i class="fas fa-lightbulb"></i> General Nutrition Tips</h3>
                    <div class="tips-grid">
                        <div class="tip-card">
                            <i class="fas fa-clock"></i>
                            <h4>Meal Timing</h4>
                            <p>Space meals 3-4 hours apart to maintain energy levels and metabolism.</p>
                        </div>
                        <div class="tip-card">
                            <i class="fas fa-glass-whiskey"></i>
                            <h4>Hydration</h4>
                            <p>Drink at least 2-3 liters of water daily, more if you're active.</p>
                        </div>
                        <div class="tip-card">
                            <i class="fas fa-apple-alt"></i>
                            <h4>Whole Foods</h4>
                            <p>Focus on minimally processed foods for better nutrient absorption.</p>
                        </div>
                        <div class="tip-card">
                            <i class="fas fa-weight"></i>
                            <h4>Portion Control</h4>
                            <p>Use measuring tools initially to understand proper portion sizes.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>© <?php echo date('Y'); ?> Kurus+ Your Fitness Management System. All rights reserved.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filter functionality for meal plans
            const filterBtns = document.querySelectorAll('.filter-btn');
            const planCards = document.querySelectorAll('.plan-card');
            
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filter = this.getAttribute('onclick').replace("filterPlans('", "").replace("')", "");
                    
                    planCards.forEach(card => {
                        if (filter === 'all' || card.classList.contains(filter)) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });
        });


        function filterPlans(category) {
            
        }
    </script>
</body>
</html>