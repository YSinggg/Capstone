<?php
session_start();

// Initialize variables
$errors = [];
$success = false;
$password_success = false;

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

    // Get current user data
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
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Profile Update Form
        if (isset($_POST['update_profile'])) {
            // Sanitize inputs
            $fullname = trim($db->real_escape_string($_POST['fullname'] ?? ''));
            $email = trim($db->real_escape_string($_POST['email'] ?? ''));
            $height = !empty($_POST['height']) ? (int)$_POST['height'] : null;
            $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
            $gender = trim($db->real_escape_string($_POST['gender'] ?? ''));
            $age = !empty($_POST['age']) ? (int)$_POST['age'] : null;
            $fitness_goal = trim($db->real_escape_string($_POST['fitness_goal'] ?? ''));
            
            // Validate inputs
            if (empty($fullname)) {
                $errors['fullname'] = "Full name is required";
            }
            
            if (empty($email)) {
                $errors['email'] = "Email is required";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = "Invalid email format";
            }
            
            // Check if email exists (excluding current user)
            $email_check = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $email_check->bind_param("si", $email, $_SESSION['user_id']);
            $email_check->execute();
            $email_check->store_result();
            
            if ($email_check->num_rows > 0) {
                $errors['email'] = "Email is already registered";
            }
            $email_check->close();
            
            // Update profile if no errors
            if (empty($errors)) {
                $update_stmt = $db->prepare("UPDATE users SET fullname = ?, email = ?, height = ?, weight = ?, gender = ?, age = ?, fitness_goal = ? WHERE id = ?");
                $update_stmt->bind_param("ssiisssi", $fullname, $email, $height, $weight, $gender, $age, $fitness_goal, $_SESSION['user_id']);
                
                if ($update_stmt->execute()) {
                    $success = true;
                    // Refresh user data
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                } else {
                    $errors['database'] = "Failed to update profile: " . $update_stmt->error;
                }
                $update_stmt->close();
            }
        }
        
        // Password Change Form
        if (isset($_POST['change_password'])) {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validate password fields
            if (empty($current_password)) {
                $errors['current_password'] = "Current password is required";
            }
            
            if (empty($new_password)) {
                $errors['new_password'] = "New password is required";
            } elseif (strlen($new_password) < 8) {
                $errors['new_password'] = "Password must be at least 8 characters";
            }
            
            if (empty($confirm_password)) {
                $errors['confirm_password'] = "Confirm password is required";
            } elseif ($new_password !== $confirm_password) {
                $errors['confirm_password'] = "Passwords do not match";
            }
            
            // Verify current password
            if (empty($errors)) {
                $password_check = $db->prepare("SELECT password FROM users WHERE id = ?");
                $password_check->bind_param("i", $_SESSION['user_id']);
                $password_check->execute();
                $password_check->bind_result($hashed_password);
                $password_check->fetch();
                $password_check->close();
                
                if (!password_verify($current_password, $hashed_password)) {
                    $errors['current_password'] = "Current password is incorrect";
                } else {
                    // Update password
                    $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $password_update = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $password_update->bind_param("si", $new_password_hashed, $_SESSION['user_id']);
                    
                    if ($password_update->execute()) {
                        $password_success = true;
                    } else {
                        $errors['database'] = "Failed to update password: " . $password_update->error;
                    }
                    $password_update->close();
                }
            }
        }
    }
    
    $stmt->close();
    $db->close();

} catch (Exception $e) {
    error_log("Settings page error: " . $e->getMessage());
    $errors['database'] = "We're experiencing technical difficulties. Please try again later.";
}

include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | FitTrack</title>
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
            --danger: #dc3545;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            color: var(--text);
            line-height: 1.6;
        }
        
        .settings-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .settings-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .settings-title {
            font-size: 2rem;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .settings-subtitle {
            color: var(--gray);
            font-size: 1rem;
        }
        
        .settings-card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .card-title {
            font-size: 1.3rem;
            color: var(--dark);
            margin-top: 0;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .card-title i {
            color: var(--primary);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74, 143, 231, 0.1);
        }
        
        .error-message {
            color: var(--danger);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        
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
            cursor: pointer;
            border: none;
            font-size: 1rem;
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
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .password-success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        /* Dark mode styles */
        .dark-mode .settings-card {
            background-color: #2a2a2a;
            color: #f0f0f0;
        }
        
        .dark-mode .card-title {
            color: #f0f0f0;
        }
        
        .dark-mode .form-label {
            color: #ddd;
        }
        
        .dark-mode .form-control {
            background-color: #3a3a3a;
            border-color: #444;
            color: #f0f0f0;
        }
        
        .dark-mode .form-control:focus {
            border-color: #4aa3ff;
            box-shadow: 0 0 0 3px rgba(74, 163, 255, 0.1);
        }
        
        .dark-mode .success-message {
            background: #2a4a2f;
            color: #a3d9b1;
        }
    </style>
</head>
<body>
    <main class="settings-container">
        <div class="settings-header">
            <h1 class="settings-title">Account Settings</h1>
            <p class="settings-subtitle">Manage your profile and preferences</p>
        </div>
        
        <?php if (!empty($errors['database'])): ?>
            <div class="error-message" style="padding: 1rem; background: #f8d7da; color: #721c24; border-radius: 6px; margin-bottom: 1.5rem;">
                <?php echo $errors['database']; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <span>Your profile has been updated successfully!</span>
            </div>
        <?php endif; ?>
        
        <?php if ($password_success): ?>
            <div class="password-success-message">
                <i class="fas fa-check-circle"></i>
                <span>Your password has been changed successfully!</span>
            </div>
        <?php endif; ?>
        
        <div class="settings-card">
            <h2 class="card-title"><i class="fas fa-user-edit"></i> Profile Information</h2>
            
            <form method="POST" action="settings.php">
                <input type="hidden" name="update_profile" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fullname" class="form-label">Full Name</label>
                        <input type="text" id="fullname" name="fullname" class="form-control" 
                               value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>" required>
                        <?php if (isset($errors['fullname'])): ?>
                            <span class="error-message"><?php echo $errors['fullname']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <span class="error-message"><?php echo $errors['email']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="height" class="form-label">Height (cm)</label>
                        <input type="number" id="height" name="height" class="form-control" 
                               value="<?php echo !empty($user['height']) ? htmlspecialchars($user['height']) : ''; ?>">
                        <?php if (isset($errors['height'])): ?>
                            <span class="error-message"><?php echo $errors['height']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="weight" class="form-label">Weight (kg)</label>
                        <input type="number" step="0.1" id="weight" name="weight" class="form-control" 
                               value="<?php echo !empty($user['weight']) ? htmlspecialchars($user['weight']) : ''; ?>">
                        <?php if (isset($errors['weight'])): ?>
                            <span class="error-message"><?php echo $errors['weight']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="gender" class="form-label">Gender</label>
                        <select id="gender" name="gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo (isset($user['gender']) && $user['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (isset($user['gender']) && $user['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo (isset($user['gender']) && $user['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="age" class="form-label">Age</label>
                        <input type="number" id="age" name="age" class="form-control" 
                               value="<?php echo !empty($user['age']) ? htmlspecialchars($user['age']) : ''; ?>">
                        <?php if (isset($errors['age'])): ?>
                            <span class="error-message"><?php echo $errors['age']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="fitness_goal" class="form-label">Fitness Goal</label>
                    <select id="fitness_goal" name="fitness_goal" class="form-control">
                        <option value="">Select Fitness Goal</option>
                        <option value="Weight Loss" <?php echo (isset($user['fitness_goal']) && $user['fitness_goal'] === 'Weight Loss') ? 'selected' : ''; ?>>Weight Loss</option>
                        <option value="Muscle Gain" <?php echo (isset($user['fitness_goal']) && $user['fitness_goal'] === 'Muscle Gain') ? 'selected' : ''; ?>>Muscle Gain</option>
                        <option value="Endurance" <?php echo (isset($user['fitness_goal']) && $user['fitness_goal'] === 'Endurance') ? 'selected' : ''; ?>>Endurance</option>
                        <option value="Maintenance" <?php echo (isset($user['fitness_goal']) && $user['fitness_goal'] === 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                        <option value="General Fitness" <?php echo (isset($user['fitness_goal']) && $user['fitness_goal'] === 'General Fitness') ? 'selected' : ''; ?>>General Fitness</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>
        
        <div class="settings-card">
            <h2 class="card-title"><i class="fas fa-lock"></i> Change Password</h2>
            
            <form method="POST" action="settings.php">
                <input type="hidden" name="change_password" value="1">
                
                <div class="form-group">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                    <?php if (isset($errors['current_password'])): ?>
                        <span class="error-message"><?php echo $errors['current_password']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                        <small style="display: block; margin-top: 0.25rem; color: var(--gray);">Minimum 8 characters</small>
                        <?php if (isset($errors['new_password'])): ?>
                            <span class="error-message"><?php echo $errors['new_password']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <span class="error-message"><?php echo $errors['confirm_password']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>
            
        
        <div class="settings-card">
            <h2 class="card-title"><i class="fas fa-user-cog"></i> Account Actions</h2>
            
            <div class="form-group">
                <a href="profile.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Profile
                </a>
            </div>
            
        
        </div>
    </main>

    
</body>
</html>