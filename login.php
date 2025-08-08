<?php
session_start();

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'fitness_management';
$connection = mysqli_connect($host, $user, $password, $database);

if ($connection === false) {
    die('Connection failed: ' . mysqli_connect_error());
}

$successMessage = "";
$errorMessage = "";
$activeForm = "register"; // Default to registration form

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {  
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password = $_POST['password']; // Don't escape password before hashing

    $query = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($connection, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        
        // Verify password against hash
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['email'] = $email; 
            $_SESSION['fullname'] = $row['fullname'];
            
            // Store fitness data in session
            $_SESSION['height'] = $row['height'];
            $_SESSION['weight'] = $row['weight'];
            $_SESSION['fitness_goal'] = $row['fitness_goal'];
            
            header("Location: userhome.php");
            exit();
        } else {
            $errorMessage = "Invalid email or password.";
        }
    } else {
        $errorMessage = "No account found with that email.";
    }
} 
elseif (isset($_POST['register'])) {  
    $activeForm = "register";

    // Validate required fields
    $required = ['fullname', 'email', 'password', 'age', 'gender', 'height', 'weight'];
    $missing = array_diff($required, array_keys($_POST));
    
    if (!empty($missing)) {
        $errorMessage = "Missing required fields: " . implode(', ', $missing);
    } 
    elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Invalid email format";
    }
    elseif (strlen($_POST['password']) < 8) {
        $errorMessage = "Password must be at least 8 characters";
    }
    else {
        // Escape all inputs
        $fullname = mysqli_real_escape_string($connection, $_POST['fullname']);
        $email = mysqli_real_escape_string($connection, $_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password
        $age = (int)$_POST['age'];
        $gender = mysqli_real_escape_string($connection, $_POST['gender']);
        $height = (float)$_POST['height'];
        $weight = (float)$_POST['weight'];
        $fitness_goal = mysqli_real_escape_string($connection, $_POST['fitness_goal']);

        // Check if email already exists
        $checkEmail = "SELECT id FROM users WHERE email='$email'";
        $emailResult = mysqli_query($connection, $checkEmail);
        
        if (mysqli_num_rows($emailResult) > 0) {
            $errorMessage = "Email already registered. Please login instead.";
        } else {
            // Insert user with fitness data
            $query = "INSERT INTO users (fullname, email, password, age, gender, height, weight, fitness_goal) 
                      VALUES ('$fullname', '$email', '$password', $age, '$gender', $height, $weight, '$fitness_goal')";
                      
            if (mysqli_query($connection, $query)) {
                $successMessage = "Registration successful! You can now log in.";
                $activeForm = "login";
            } else {
                $errorMessage = "Registration failed: " . mysqli_error($connection);
            }
        }
    }
}

mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurus+ | Login</title>
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
        
        /* Navbar Styling */
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
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #3aa856;
        }

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

        /* Mobile Responsive Navbar */
        /* Add this to your media queries section */
        @media screen and (min-width: 1025px) {
            #menu-toggle {
                display: none;
            }
            .nav-links {
                display: flex !important; /* Ensure nav links are always visible on desktop */
            }
        }

        @media screen and (max-width: 1024px) {
            #menu-toggle {
                display: block; /* Show hamburger menu on mobile */
            }
            .nav-links {
                display: none; /* Hide nav links by default on mobile */
            }
            .nav-links.show {
                display: flex; /* Show nav links when menu is toggled */
            }
        }

        /* Login/Register Container */
        .container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .card {
            background: white;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        h2 {
            color: var(--dark-color);
            margin-bottom: 25px;
            text-align: center;
            font-size: 28px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            z-index: 2; /* Ensure icon stays above input */
            pointer-events: none; /* Allow clicks to pass through to input */
        }

        input {
            width: 100%;
            padding: 12px 15px 12px 45px; /* Increased left padding for icon */
            margin: 5px 0;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 16px;
            transition: border 0.3s;
            box-sizing: border-box; /* Include padding in width calculation */
            position: relative;
        }

        select {
            width: 100%;
            padding: 12px 15px 12px 45px; /* Match input padding */
            margin: 5px 0;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 16px;
            background-color: white;
            appearance: none; /* Remove default dropdown arrow */
            -webkit-appearance: none;
            -moz-appearance: none;
            box-sizing: border-box;
        }

        /* Add dropdown arrow for select elements */
        .select-wrapper {
            position: relative;
        }

        .select-wrapper::after {
            content: "\f078";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: #7f8c8d;
        }
        
        .button {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 14px;
            border: none;
            width: 100%;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .button:hover {
            background: linear-gradient(to right, #3a7bd5, #3aa856);
            transform: translateY(-2px);
        }
        
        .switch {
            font-size: 14px;
            margin-top: 20px;
            text-align: center;
            color: #7f8c8d;
        }
        
        .switch a {
            color: var(--primary-color);
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .switch a:hover {
            color: var(--secondary-color);
        }
        
        .message {
            margin: 15px 0;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        
        .success {
            background-color: rgba(46, 204, 113, 0.2);
            color: var(--success-color);
        }
        
        .error {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--danger-color);
        }
        
        /* Fitness-specific fields */
        .fitness-fields {
            display: <?php echo $activeForm == 'register' ? 'block' : 'none'; ?>;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        select {
            width: 100%;
            padding: 12px 15px;
            margin: 5px 0;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 16px;
            background-color: white;
        }
        
        /* Footer */
        .footer {
            background-color: var(--dark-color);
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: auto;
        }
        
        /* Dark mode styles */
        .dark-mode {
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
            color: var(--light-color);
        }
        
        .dark-mode .card {
            background-color: #34495e;
            color: white;
        }
        
        .dark-mode input,
        .dark-mode select {
            background-color: #2c3e50;
            border-color: #4a6278;
            color: white;
        }
        
        .dark-mode .navbar,
        .dark-mode .footer {
            background-color: #1a252f;
        }
        
        .dark-mode .nav-links a {
            color: var(--light-color);
        }
        
        .dark-mode .fitness-fields {
            border-top-color: #4a6278;
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card {
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .card {
                padding: 30px 20px;
                width: 90%;
            }
            
            header {
                padding: 15px 20px;
            }
        }
        .input-group {
            display: flex;
            gap: 10px;
        }
        
        .input-group .form-group {
            flex: 1;
        }
    </style>
</head>
<body>
    <header>
        <button id="menu-toggle" aria-label="Toggle navigation menu" aria-expanded="false">
            ☰
        </button>
        <div class="logo">
            <i class="fas fa-dumbbell"></i> Kurus+
        </div>
        <ul class="nav-links">
            <li><a href="home.html">Home</a></li>
            <li><a href="feature.html">Features</a></li>
            <li><a href="about.html">About</a></li>
            <li><a href="Guideline.html">Guideline</a></li>
        </ul>
        <button class="dark-mode-toggle" onclick="toggleDarkMode()">
            <i class="fas fa-moon"></i> Dark Mode
        </button>
    </header>

    <div class="container">
        <div class="card">
            <h2 id="form-title">
                <?php echo $activeForm == "login" ? "Welcome Back!" : "Start Your Fitness Journey"; ?>
            </h2>

            <!-- Success/Error Message -->
            <?php if (!empty($successMessage)): ?>
                <div class="message success"><?php echo $successMessage; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($errorMessage)): ?>
                <div class="message error"><?php echo $errorMessage; ?></div>
            <?php endif; ?>

            <!-- Login Form -->
            <form id="login-form" method="POST" action="" style="display: <?php echo $activeForm == 'login' ? 'block' : 'none'; ?>;">
                <div class="form-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                
                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                
                <button class="button" type="submit" name="login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                
                <div class="switch">
                    New to Kurus+? <a href="#" onclick="toggleForm('register')">Create account</a>
                </div>
            </form>

            <!-- Register Form -->
            <form id="register-form" method="POST" action="" style="display: <?php echo $activeForm == 'register' ? 'block' : 'none'; ?>;">
                <div class="form-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="fullname" placeholder="Full Name" value="<?php echo $_POST['fullname'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password (min 8 characters)" minlength="8" required>
                </div>
                
                <!-- Personal Details -->
                <div class="input-group">
                    <div class="form-group">
                        <i class="fas fa-birthday-cake"></i>
                        <input type="number" name="age" placeholder="Age" min="12" max="100" value="<?php echo $_POST['age'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group select-wrapper">
                        <i class="fas fa-venus-mars"></i>
                        <select name="gender" required>
                            <option value="">Gender</option>
                            <option value="male" <?php echo ($_POST['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($_POST['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo ($_POST['gender'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <!-- Physical Measurements -->
                <div class="input-group">
                    <div class="form-group">
                        <i class="fas fa-ruler-vertical"></i>
                        <input type="number" step="0.1" name="height" placeholder="Height (cm)" min="100" max="250" value="<?php echo $_POST['height'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <i class="fas fa-weight"></i>
                        <input type="number" step="0.1" name="weight" placeholder="Weight (kg)" min="30" max="300" value="<?php echo $_POST['weight'] ?? ''; ?>" required>
                    </div>
                </div>
                
                <!-- Fitness Goal -->
                <div class="form-group">
                    <i class="fas fa-bullseye"></i>
                    <select name="fitness_goal" required>
                        <option value="">Fitness Goal</option>
                        <option value="weight_loss" <?php echo ($_POST['fitness_goal'] ?? '') == 'weight_loss' ? 'selected' : ''; ?>>Weight Loss</option>
                        <option value="muscle_gain" <?php echo ($_POST['fitness_goal'] ?? '') == 'muscle_gain' ? 'selected' : ''; ?>>Muscle Gain</option>
                        <option value="endurance" <?php echo ($_POST['fitness_goal'] ?? '') == 'endurance' ? 'selected' : ''; ?>>Endurance</option>
                        <option value="maintenance" <?php echo ($_POST['fitness_goal'] ?? '') == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                </div>
                
                <button class="button" type="submit" name="register">
                    <i class="fas fa-user-plus"></i> Register
                </button>
                
                <div class="switch">
                    Already have an account? <a href="#" onclick="toggleForm('login')">Login here</a>
                </div>
            </form>
        </div>
    </div>

    <div class="footer">
        © 2025 Kurus+ Your Fitness Management System. All rights reserved.
    </div>

    <script>
         // Toggle menu for mobile
        document.getElementById("menu-toggle").addEventListener("click", function() {
            const navLinks = document.querySelector(".nav-links");
            navLinks.classList.toggle("show");
            
            // Update hamburger icon to X when menu is open
            if (navLinks.classList.contains("show")) {
                this.innerHTML = "✕"; // X icon
            } else {
                this.innerHTML = "☰"; // Hamburger icon
            }
        });

        // Dark mode functionality
        function toggleDarkMode() {
            document.body.classList.toggle("dark-mode");
            const icon = document.querySelector(".dark-mode-toggle i");
            
            if (document.body.classList.contains("dark-mode")) {
                localStorage.setItem("darkMode", "enabled");
                icon.classList.remove("fa-moon");
                icon.classList.add("fa-sun");
                document.querySelector(".dark-mode-toggle").innerHTML = '<i class="fas fa-sun"></i> Light Mode';
            } else {
                localStorage.setItem("darkMode", "disabled");
                icon.classList.remove("fa-sun");
                icon.classList.add("fa-moon");
                document.querySelector(".dark-mode-toggle").innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
            }
        }

        // Check for dark mode preference on load
        if (localStorage.getItem("darkMode") === "enabled") {
            document.body.classList.add("dark-mode");
            document.querySelector(".dark-mode-toggle i").classList.remove("fa-moon");
            document.querySelector(".dark-mode-toggle i").classList.add("fa-sun");
            document.querySelector(".dark-mode-toggle").innerHTML = '<i class="fas fa-sun"></i> Light Mode';
        }

        // Toggle between login and register forms
        function toggleForm(formType) {
            const loginForm = document.getElementById("login-form");
            const registerForm = document.getElementById("register-form");
            const formTitle = document.getElementById("form-title");
            const fitnessFields = document.getElementById("fitness-fields");
            
            if (formType === "login") {
                loginForm.style.display = "block";
                registerForm.style.display = "none";
                formTitle.textContent = "Welcome Back!";
                fitnessFields.style.display = "none";
            } else {
                loginForm.style.display = "none";
                registerForm.style.display = "block";
                formTitle.textContent = "Start Your Fitness Journey";
                fitnessFields.style.display = "block";
            }
        }
    </script>
</body>
</html>
