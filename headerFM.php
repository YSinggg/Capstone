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
            --light-gray: #f5f1f2ff;
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
        [data-theme="dark"] {
            --primary: #5d9cec;
            --secondary: #48cfad;
            --accent: #ff6b6b;
            --dark: #f8f9fa;
            --light: #1a1a1a
            --text: #f8f9fa;
            --gray: #95a5a6;
            --white: #34495e;
        }
         body {
            background-color: #ffffff;
            color: #000000;
            transition: 0.3s ease all;
        }

        body.dark-mode {
            background-color: #1a1a1a; /* Soft dark gray */
            color: #f0f0f0;
        }

        .card, .container, .content-area {
            background-color: #ffffff;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            transition: 0.3s ease all;
        }

        body.dark-mode .card,
        body.dark-mode .container,
        body.dark-mode .content-area {
            background-color: #3c3c3c; /* Medium gray for content boxes */
            color: #e0e0e0;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.05);
        }

        .toggle-darkmode {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 999;
            background: #5a5a5a;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
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
document.addEventListener('DOMContentLoaded', function () {
  // ===== Mobile menu =====
  const menuToggle = document.getElementById('menu-toggle');
  const navLinks   = document.querySelector('.nav-links');

  if (menuToggle && navLinks) {
    menuToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      const isExpanded = navLinks.classList.toggle('show');
      this.innerHTML = isExpanded ? '✕' : '☰';
      this.setAttribute('aria-expanded', isExpanded);
    });
  }

  // Close mobile menu when clicking nav links (<=1024px)
  document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth <= 1024 && navLinks) {
        navLinks.classList.remove('show');
        if (menuToggle) {
          menuToggle.innerHTML = '☰';
          menuToggle.setAttribute('aria-expanded', 'false');
        }
      }
    });
  });

  // Reset menu state on resize
  window.addEventListener('resize', function () {
    if (window.innerWidth > 1024 && navLinks) {
      navLinks.classList.remove('show');
      if (menuToggle) {
        menuToggle.innerHTML = '☰';
        menuToggle.setAttribute('aria-expanded', 'false');
      }
    }
  });

  // ===== Profile dropdown =====
  const profileToggle   = document.getElementById('profile-toggle');
  const profileDropdown = document.getElementById('profile-dropdown');

  if (profileToggle && profileDropdown) {
    profileToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      const isExpanded = profileDropdown.classList.toggle('show');
      this.setAttribute('aria-expanded', isExpanded);
    });

    document.addEventListener('click', function (e) {
      if (!profileToggle.contains(e.target)) {
        profileDropdown.classList.remove('show');
        profileToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // ===== THEME (single source of truth: localStorage 'theme' = 'dark'|'light') =====
  const darkModeToggleBtn = document.querySelector('.dark-mode-toggle');

  function applyTheme(theme) {
    // Attribute (for CSS variables)
    document.documentElement.setAttribute('data-theme', theme);
    // Class (for legacy dark styles)
    document.body.classList.toggle('dark-mode', theme === 'dark');

    // Update toggle button
    if (darkModeToggleBtn) {
      if (theme === 'dark') {
        darkModeToggleBtn.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
      } else {
        darkModeToggleBtn.innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
      }
    }
  }

  function initializeTheme() {
    const saved = localStorage.getItem('theme');
    const systemPrefersDark = window.matchMedia &&
                              window.matchMedia('(prefers-color-scheme: dark)').matches;
    const theme = saved ? saved : (systemPrefersDark ? 'dark' : 'light');
    localStorage.setItem('theme', theme); // normalize storage
    applyTheme(theme);
  }

  function toggleTheme() {
    const current = localStorage.getItem('theme') || 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    localStorage.setItem('theme', next);
    applyTheme(next);
  }

  // Wire the button AFTER it's in the DOM
  if (darkModeToggleBtn) {
    darkModeToggleBtn.addEventListener('click', toggleTheme);
  }

  // Apply saved theme on load
  initializeTheme();

  // (Optional) Sync theme if changed in another tab
  window.addEventListener('storage', (e) => {
    if (e.key === 'theme' && e.newValue) {
      applyTheme(e.newValue);
    }
  });
});
</script>
