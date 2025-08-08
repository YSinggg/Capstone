<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection (to get user info for header)
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
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->error);
    }
    
    $stmt->bind_param("i", $_SESSION['user_id']);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $db->close();

} catch (Exception $e) {
    error_log("About page error: " . $e->getMessage());
    // Continue loading page even if DB fails since this is just for header
}
include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Kurus+</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Reuse all the same styles from profile.php */
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
        
        /* Dark Mode Styles */
        body.dark-mode {
            background-color: #121212;
            color: #e0e0e0;
        }

        body.dark-mode .mission-card,
        body.dark-mode .team-member,
        body.dark-mode .value-card {
            background-color: #1e1e1e;
            color: #e0e0e0;
        }

        body.dark-mode .member-name,
        body.dark-mode .value-title,
        body.dark-mode .section-title {
            color: #ffffff;
        }

        body.dark-mode .member-role {
            color: var(--secondary);
        }

        body.dark-mode .profile-dropdown {
            background-color: #1e1e1e;
            color: #e0e0e0;
        }

        body.dark-mode .profile-dropdown a {
            color: #e0e0e0;
        }

        body.dark-mode .profile-dropdown a:hover {
            background-color: #2d2d2d;
            color: var(--primary);
        }

        body.dark-mode .divider {
            background: #333;
        }

        /* About Page Specific Styles */
        .about-hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
        }
        
        .about-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .about-hero p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto 2rem;
        }
        
        .about-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .mission-section, .team-section, .values-section {
            margin-bottom: 3rem;
        }
        
        .section-title {
            font-size: 1.8rem;
            color: var(--dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .section-title i {
            color: var(--secondary);
        }
        
        .mission-card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .team-member {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .team-member:hover {
            transform: translateY(-5px);
        }
        
        .member-image {
            height: 250px;
            background-color: #eee;
            background-size: cover;
            background-position: center;
        }
        
        .member-info {
            padding: 1.5rem;
        }
        
        .member-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .member-role {
            color: var(--primary);
            font-weight: 500;
            margin-bottom: 1rem;
            display: block;
        }
        
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .value-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        
        .value-icon {
            background: var(--secondary);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .value-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.8rem;
            color: var(--dark);
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
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .footer-links a {
            color: white;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: var(--secondary);
        }
        
        .social-icons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .social-icons a {
            color: white;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }
        
        .social-icons a:hover {
            color: var(--secondary);
        }
    </style>
</head>
<body>
    <main>
        <section class="about-hero">
            <h1>About Kurus+</h1>
            <p>Your personal fitness companion for achieving health and wellness goals</p>
            <a href="userhome.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </section>
        
        <div class="about-content">
            <section class="mission-section">
                <h2 class="section-title"><i class="fas fa-bullseye"></i> Our Mission</h2>
                <div class="mission-card">
                    <p>At Kurus+, we believe that everyone deserves access to tools that make fitness tracking simple, intuitive, and motivating. Our mission is to empower individuals to take control of their health by providing a comprehensive platform that tracks workouts, nutrition, and progress all in one place.</p>
                    <p>We're committed to creating an inclusive fitness community where users of all levels can find the support and resources they need to achieve their personal health goals.</p>
                </div>
            </section>
            
            <section class="team-section">
                <h2 class="section-title"><i class="fas fa-users"></i> Meet the Team</h2>
                <div class="team-grid">
                    <div class="team-member">
                        <div class="member-image" style="background-image: url('4.jpg');"></div>
                        <div class="member-info">
                            <h3 class="member-name">Chong Wei Xuan</h3>
                            <span class="member-role">Founder & CEO</span>
                            <p>Fitness enthusiast with 10+ years in the health tech industry, passionate about making fitness accessible to everyone.</p>
                        </div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-image" style="background-image: url('2.jpg');"></div>
                        <div class="member-info">
                            <h3 class="member-name">Chong Wei Xuan</h3>
                            <span class="member-role">Head of Product</span>
                            <p>Certified nutritionist and product designer focused on creating user-friendly fitness experiences.</p>
                        </div>
                    </div>
                    
                    <div class="team-member">
                        <div class="member-image" style="background-image: url('3.jpg');"></div>
                        <div class="member-info">
                            <h3 class="member-name">Chong Wei Xuan</h3>
                            <span class="member-role">Lead Developer</span>
                            <p>Full-stack developer with a passion for building robust fitness tracking systems.</p>
                        </div>
                    </div>
                </div>
            </section>
            
            <section class="values-section">
                <h2 class="section-title"><i class="fas fa-heart"></i> Our Values</h2>
                <div class="values-grid">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <h3 class="value-title">User-Centric</h3>
                        <p>We put our users at the center of everything we do, designing features that truly meet their needs.</p>
                    </div>
                    
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h3 class="value-title">Innovation</h3>
                        <p>We constantly seek new ways to improve and innovate in the fitness tracking space.</p>
                    </div>
                    
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-hand-holding-heart"></i>
                        </div>
                        <h3 class="value-title">Community</h3>
                        <p>We believe in building a supportive community that motivates and inspires.</p>
                    </div>
                    
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="value-title">Progress</h3>
                        <p>We celebrate all progress, big and small, on the journey to better health.</p>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <footer>
        <p>© <?php echo date('Y'); ?> Kurus+ Your Fitness Management System. All rights reserved.</p>
    </footer>

</body>
</html>