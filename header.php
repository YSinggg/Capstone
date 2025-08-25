<?php

// Check if user is logged in and session variables exist
$isLoggedIn = isset($_SESSION['user_id']);
$username = '';
if ($isLoggedIn) {
    $username = $_SESSION['username'] ?? 'User'; // Provide default if username not set
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurus+</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            --light-gray: #f1f3f5;
        }
        
        /* Header styles matching feature page */
        header {
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
            padding: 0.8rem 2rem;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-content {
            display: flex;
            align-items: center;
            width: 100%;
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
            margin-right: 2rem;
        }

        .logo i {
            margin-right: 10px;
            color: var(--secondary);
            font-size: 1.5rem;
        }

        .logo:hover {
            opacity: 0.9;
        }

        #menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            margin-right: 1rem;
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 1.5rem;
            margin: 0;
            padding: 0;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s ease;
            padding: 0.5rem 0;
            position: relative;
        }

        .nav-links a:hover {
            color: var(--secondary);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--secondary);
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-right {
            display: flex;
            align-items: center;
            margin-left: auto;
            gap: 1rem;
        }

        /* Profile Dropdown Styles */
        .profile-container {
            position: relative;
        }

        .profile-btn {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .profile-btn:hover {
            background: #3aa856;
            transform: translateY(-2px);
        }

        .profile-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            min-width: 200px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            z-index: 100;
            margin-top: 10px;
            overflow: hidden;
        }

        .profile-dropdown.show {
            display: block;
            animation: fadeIn 0.2s ease-out;
        }

        .profile-dropdown a {
            color: var(--dark);
            padding: 0.8rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .profile-dropdown a:hover {
            background: var(--light);
            color: var(--primary);
        }

        .profile-dropdown a i {
            width: 20px;
            text-align: center;
        }

        .profile-header {
            padding: 0.8rem 1rem;
            background: var(--secondary);
            color: white;
            font-weight: 600;
        }

        .divider {
            height: 1px;
            background: #eee;
            margin: 0.3rem 0;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive styles */
        @media (max-width: 992px) {
            #menu-toggle {
                display: block;
            }

            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: var(--dark);
                flex-direction: column;
                padding: 1rem 0;
                box-shadow: 0 5px 10px rgba(0,0,0,0.1);
            }

            .nav-links.show {
                display: flex;
            }

            .nav-links a {
                padding: 0.8rem 2rem;
            }

            .nav-right {
                margin-left: 0;
            }
        }
        
        /* Dark mode styles */
        .dark-mode {
            background: #1a1a1a;
            color: #f0f0f0;
        }
        
        .dark-mode .profile-dropdown,
        .dark-mode .profile-dropdown a {
            background: #2d2d2d;
            color: #f0f0f0;
        }
        
        .dark-mode .profile-dropdown a:hover {
            background: #444;
            color: var(--primary);
        }
        
        .dark-mode-toggle {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s ease;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dark-mode-toggle:hover {
            background: #3a7bd5;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <header>
        <button id="menu-toggle">☰</button>
        <div class="logo">
            <i class="fas fa-dumbbell"></i> Kurus+
        </div>
        <ul class="nav-links">
            <li><a href="userhome.php" class="active">Home</a></li>
            <li><a href="feature.php">Features</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="guideline.php">Guideline</a></li>
            <?php if($isLoggedIn): ?>
                <li class="profile-container">
                    <button class="profile-btn" id="profile-toggle">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($username); ?>
                        <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="profile-dropdown" id="profile-dropdown">
                        <div class="profile-header">My Profile</div>
                        <a href="profile.php"><i class="fas fa-user-circle"></i> View Profile</a>
                        <a href="fitness_management.php"><i class="fas fa-dumbbell"></i> My Workouts</a>
                        <a href="diet_plan.php"><i class="fas fa-utensils"></i> Nutrition</a>
                        <div class="divider"></div>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <a href="login.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </li>
            <?php else: ?>
                <li><a href="login.php" class="btn">Login</a></li>
            <?php endif; ?>
        </ul>
        <button class="dark-mode-toggle" onclick="toggleDarkMode()">
            <i class="fas fa-moon"></i> Dark Mode
        </button>
    </header>

    <script>
    // Wait for DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle menu for mobile
        const menuToggle = document.getElementById("menu-toggle");
        const navLinks = document.querySelector(".nav-links");
        
        if (menuToggle) {
            menuToggle.addEventListener("click", function(e) {
                e.stopPropagation();
                const isExpanded = navLinks.classList.toggle("show");
                
                // Update icon and aria attributes
                this.innerHTML = isExpanded ? "✕" : "☰";
                this.setAttribute("aria-expanded", isExpanded);
            });
        }

        // Dark mode functionality with system preference detection
        const darkModeToggle = document.querySelector(".dark-mode-toggle");
        const icon = darkModeToggle.querySelector("i");

        function updateDarkMode() {
            if (document.body.classList.contains("dark-mode")) {
                localStorage.setItem("darkMode", "enabled");
                icon.classList.remove("fa-moon");
                icon.classList.add("fa-sun");
                darkModeToggle.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
            } else {
                localStorage.setItem("darkMode", "disabled");
                icon.classList.remove("fa-sun");
                icon.classList.add("fa-moon");
                darkModeToggle.innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
            }
        }

        darkModeToggle.addEventListener("click", function() {
            document.body.classList.toggle("dark-mode");
            updateDarkMode();
        });

        if (localStorage.getItem("darkMode") === "enabled") {
            document.body.classList.add("dark-mode");
            updateDarkMode();
        }

        // Check for saved user preference or system preference
        function checkDarkModePreference() {
            // Check localStorage first
            if (localStorage.getItem("darkMode") === "enabled") {
                document.body.classList.add("dark-mode");
                darkModeToggle.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
                return;
            } 
            
            // If no localStorage preference, check system preference
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches && 
                localStorage.getItem("darkMode") === null) {
                document.body.classList.add("dark-mode");
                darkModeToggle.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
                localStorage.setItem("darkMode", "enabled");
            }
        }

        // Initialize dark mode
        checkDarkModePreference();

        // Listen for system preference changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (localStorage.getItem("darkMode") === null) {
                if (e.matches) {
                    document.body.classList.add("dark-mode");
                    document.querySelector(".dark-mode-toggle").innerHTML = '<i class="fas fa-sun"></i> Light Mode';
                } else {
                    document.body.classList.remove("dark-mode");
                    document.querySelector(".dark-mode-toggle").innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
                }
            }
        });

        // Close mobile menu when clicking on a link
        document.querySelectorAll(".nav-links a").forEach(link => {
            link.addEventListener("click", () => {
                if (window.innerWidth <= 1024) {
                    navLinks.classList.remove("show");
                    menuToggle.innerHTML = "☰";
                    menuToggle.setAttribute("aria-expanded", "false");
                }
            });
        });

        // Profile dropdown toggle
        const profileToggle = document.getElementById("profile-toggle");
        const profileDropdown = document.getElementById("profile-dropdown");
        
        if (profileToggle && profileDropdown) {
            profileToggle.addEventListener("click", function(e) {
                e.stopPropagation();
                const isExpanded = profileDropdown.classList.toggle("show");
                this.setAttribute("aria-expanded", isExpanded);
            });

            // Close dropdown when clicking outside
            document.addEventListener("click", function(e) {
                if (!profileToggle.contains(e.target)) {
                    profileDropdown.classList.remove("show");
                    profileToggle.setAttribute("aria-expanded", "false");
                }
            });
        }

        // Handle window resizing
        window.addEventListener('resize', function() {
            if (window.innerWidth > 1024) {
                navLinks.classList.remove("show");
                if (menuToggle) {
                    menuToggle.innerHTML = "☰";
                    menuToggle.setAttribute("aria-expanded", "false");
                }
            }
        });
    });
    </script>