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
$username = $_SESSION['username'] ?? 'User';

// Initialize variables
$community_posts = [];
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Handle post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_post'])) {
    $content = htmlspecialchars($_POST['post_content'] ?? '');
    
    if (empty($content)) {
        $_SESSION['error'] = "Post content cannot be empty";
        header("Location: community.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO community_posts (user_id, content) VALUES (?, ?)");
        $stmt->execute([$user_id, $content]);
        $_SESSION['success'] = "Post added successfully!";
        header("Location: community.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding post: " . $e->getMessage();
    }
}

// Handle post like
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_post'])) {
    $post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    
    if ($post_id === false || $post_id === null) {
        $_SESSION['error'] = "Invalid post ID";
        header("Location: community.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE community_posts SET likes = likes + 1 WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $_SESSION['success'] = "Post liked!";
        header("Location: community.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error liking post: " . $e->getMessage();
    }
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    $content = htmlspecialchars($_POST['comment_content'] ?? '');
    
    if ($post_id === false || $post_id === null) {
        $_SESSION['error'] = "Invalid post ID";
        header("Location: community.php");
        exit();
    }
    
    if (empty($content)) {
        $_SESSION['error'] = "Comment cannot be empty";
        header("Location: community.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $user_id, $content]);
        $_SESSION['success'] = "Comment added successfully!";
        header("Location: community.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding comment: " . $e->getMessage();
    }
}

// Get all community posts (ordered by newest first)
try {
    $stmt = $pdo->prepare("SELECT cp.*, u.fullname as author_name 
                          FROM community_posts cp
                          JOIN users u ON cp.user_id = u.id
                          ORDER BY cp.created_at DESC");
    $stmt->execute();
    $community_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching community posts: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Support | Kurus+</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a8fe7;
            --secondary-color: #44c767;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Navbar Styling - Consistent with userhome.php */
        header {
            background: var(--dark-color);
            padding: 15px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            position: relative;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        
        .logo i {
            margin-right: 10px;
            color: var(--secondary-color);
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 25px;
            padding: 0;
            margin: 0;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: white;
            font-size: 1rem;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: var(--secondary-color);
        }

        .btn {
            background: var(--secondary-color);
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #3aa856;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        /* Profile Dropdown Styles */
        .profile-container {
            position: relative;
            display: inline-block;
        }

        .profile-btn {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .profile-btn:hover {
            background: #3aa856;
        }

        .profile-dropdown {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-radius: 5px;
            z-index: 1;
            margin-top: 5px;
            overflow: hidden;
        }

        .profile-dropdown a {
            color: var(--dark-color);
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s;
        }

        .profile-dropdown a:hover {
            background-color: #f1f1f1;
        }

        .profile-dropdown.show {
            display: block;
            animation: fadeIn 0.3s;
        }

        .profile-header {
            padding: 12px 16px;
            background: var(--secondary-color);
            color: white;
            font-weight: bold;
        }

        .divider {
            border-top: 1px solid #eee;
            margin: 5px 0;
        }

        /* Mobile Responsive Navbar */
        @media screen and (max-width: 1024px) {
            .nav-links {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 60px;
                left: 0;
                background: var(--dark-color);
                width: 100%;
                padding: 10px 0;
                text-align: center;
            }
            .nav-links.show {
                display: flex;
            }
            #menu-toggle {
                display: block;
                background: none;
                border: none;
                font-size: 24px;
                color: white;
                cursor: pointer;
            }
            .profile-container {
                width: 100%;
                text-align: center;
            }
            .profile-dropdown {
                position: static;
                width: 100%;
                box-shadow: none;
                border-radius: 0;
            }
        }

        @media screen and (min-width: 1025px) {
            #menu-toggle {
                display: none;
            }
        }

        /* Dark mode toggle button */
        .dark-mode-toggle {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .dark-mode-toggle:hover {
            background: #3a7bd5;
        }

        /* Dark mode styles */
        .dark-mode {
            background: #222;
            color: white;
        }

        .dark-mode .profile-dropdown {
            background-color: #333;
            border: 1px solid #444;
        }

        .dark-mode .profile-dropdown a {
            color: white;
        }

        .dark-mode .profile-dropdown a:hover {
            background-color: #444;
        }

        .dark-mode .divider {
            border-color: #444;
        }

        /* Main Content Styles */
        .main-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            flex: 1;
        }
        
        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .page-header p {
            color: var(--dark-color);
            font-size: 1.1rem;
            opacity: 0.8;
        }
        
        /* Community Section */
        .community-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .add-post-form {
            margin: 1.5rem 0;
            padding: 1rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
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
        
        .community-posts {
            margin-top: 1.5rem;
        }
        
        .post {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: white;
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
            background: var(--secondary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 1rem;
        }
        
        .post-author {
            font-weight: 600;
        }
        
        .post-time {
            color: #6c757d;
            font-size: 0.8rem;
        }
        
        .like-form {
            display: inline-block;
            margin-top: 0.5rem;
        }

        .like-button {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            padding: 0;
            font-size: 0.9rem;
        }

        .like-button:hover {
            color: var(--danger-color);
        }

        .comments-section {
            margin-top: 1rem;
            padding-left: 50px;
        }

        .comment {
            margin-bottom: 1rem;
            padding: 0.5rem;
            background: #f8f9fa;
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
            background: var(--primary-color);
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
        }

        .comment-time {
            color: #6c757d;
            font-size: 0.8rem;
        }

        .comment-content {
            font-size: 0.9rem;
            margin-left: 35px;
        }

        .add-comment-form {
            margin-top: 0.5rem;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Dark mode styles for community content */
        .dark-mode .community-container,
        .dark-mode .add-post-form,
        .dark-mode .post {
            background-color: #333;
            color: #f1f1f1;
        }

        .dark-mode .form-control {
            background-color: #444;
            color: white;
            border-color: #666;
        }

        .dark-mode .comment {
            background-color: #444;
            color: #f1f1f1;
        }

        .dark-mode .alert-success {
            background-color: #2e7d32;
            color: #d0ffd0;
        }

        .dark-mode .alert-error {
            background-color: #c62828;
            color: #ffd0d0;
        }

        /* Footer */
        .footer {
            background-color: var(--dark-color);
            color: white;
            text-align: center;
            padding: 30px 0;
            margin-top: auto;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: var(--secondary-color);
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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
            <li><a href="userhome.php">Home</a></li>
            <li><a href="features.php">Features</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="guideline.php">Guideline</a></li>
            <li class="profile-container">
                <button class="profile-btn" id="profile-toggle">
                    <i class="fas fa-user"></i>
                    <?php echo htmlspecialchars($username); ?>
                    <i class="fas fa-caret-down"></i>
                </button>
                <div class="profile-dropdown" id="profile-dropdown">
                    <div class="profile-header">My Profile</div>
                    <a href="profile.php"><i class="fas fa-user-circle"></i> View Profile</a>
                    <a href="workouts.php"><i class="fas fa-dumbbell"></i> My Workouts</a>
                    <a href="nutrition.php"><i class="fas fa-utensils"></i> Nutrition</a>
                    <div class="divider"></div>
                    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </li>
        </ul>
        <button class="dark-mode-toggle" onclick="toggleDarkMode()">
            <i class="fas fa-moon"></i> Dark Mode
        </button>
    </header>

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
            <h1>Community Support</h1>
            <p>Connect with other fitness enthusiasts, share your progress, and get inspired</p>
        </div>
        
        <div class="community-container">
            <!-- Add Post Form -->
            <div class="add-post-form">
                <form method="POST" action="community.php">
                    <div class="form-group">
                        <label for="post_content">Share something with the community</label>
                        <textarea id="post_content" name="post_content" class="form-control" rows="3" required></textarea>
                    </div>
                    <button type="submit" name="add_post" class="btn">Post</button>
                </form>
            </div>
            
            <div class="community-posts">
                <?php if (empty($community_posts)): ?>
                    <p style="text-align: center; color: #6c757d; padding: 1.5rem;">No community posts yet. Be the first to share!</p>
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
                                <form method="POST" action="community.php" class="like-form">
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
                                    <form method="POST" action="community.php" class="add-comment-form">
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
    </div>

    <footer class="footer">
        <p>© <?php echo date('Y'); ?> Kurus+ Your Fitness Management System. All rights reserved.</p>
    </footer>

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

            // Profile dropdown toggle
            const profileToggle = document.getElementById("profile-toggle");
            const profileDropdown = document.getElementById("profile-dropdown");
            
            if (profileToggle && profileDropdown) {
                profileToggle.addEventListener("click", function(e) {
                    e.stopPropagation();
                    profileDropdown.classList.toggle("show");
                });

                // Close dropdown when clicking outside
                document.addEventListener("click", function(e) {
                    if (!profileToggle.contains(e.target)) {
                        profileDropdown.classList.remove("show");
                    }
                });
            }

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

        // Dark mode functionality
        function toggleDarkMode() {
            document.body.classList.toggle("dark-mode");
            const darkModeToggle = document.querySelector(".dark-mode-toggle");
            const icon = darkModeToggle.querySelector("i");
            
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

        // Check for dark mode preference on load
        document.addEventListener("DOMContentLoaded", function() {
            if (localStorage.getItem("darkMode") === "enabled") {
                document.body.classList.add("dark-mode");
                const darkModeToggle = document.querySelector(".dark-mode-toggle");
                darkModeToggle.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
            }
        });

        // Function to focus on the post content textarea when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const postContent = document.getElementById('post_content');
            if (postContent) {
                postContent.focus();
            }
            
            // If there's an error, scroll to the alert message
            if (document.querySelector('.alert-error')) {
                document.querySelector('.alert-error').scrollIntoView({ behavior: 'smooth' });
            }
        });
    </script>
</body>
</html>