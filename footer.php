<?php
// footer.php - A reusable footer component

// Check if user is logged in and session variables exist
$isLoggedIn = isset($_SESSION['user_id']);
$username = '';
if ($isLoggedIn) {
    $username = $_SESSION['username'] ?? 'User'; // Provide default if username not set
}
?>

<footer style="background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%); color: white; padding: 2rem 0; margin-top: auto;">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5 style="color: var(--secondary); margin-bottom: 1rem;">Kurus+</h5>
                <p>Your personal fitness companion to track workouts, nutrition, and health goals.</p>
                <div class="social-icons mt-3">
                    <a href="#" style="color: white; margin-right: 1rem;"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" style="color: white; margin-right: 1rem;"><i class="fab fa-twitter"></i></a>
                    <a href="#" style="color: white; margin-right: 1rem;"><i class="fab fa-instagram"></i></a>
                    <a href="#" style="color: white;"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="col-md-2 mb-4">
                <h5 style="color: var(--secondary); margin-bottom: 1rem;">Quick Links</h5>
                <ul style="list-style: none; padding: 0;">
                    <li><a href="userhome.php" style="color: white; text-decoration: none;">Home</a></li>
                    <li><a href="feature.php" style="color: white; text-decoration: none;">Features</a></li>
                    <li><a href="about.php" style="color: white; text-decoration: none;">About</a></li>
                    <li><a href="guideline.php" style="color: white; text-decoration: none;">Guidelines</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4">
                <h5 style="color: var(--secondary); margin-bottom: 1rem;">Support</h5>
                <ul style="list-style: none; padding: 0;">
                    <li><a href="contact.php" style="color: white; text-decoration: none;">Contact Us</a></li>
                    <li><a href="faq.php" style="color: white; text-decoration: none;">FAQ</a></li>
                    <li><a href="privacy.php" style="color: white; text-decoration: none;">Privacy Policy</a></li>
                    <li><a href="terms.php" style="color: white; text-decoration: none;">Terms of Service</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4">
                <h5 style="color: var(--secondary); margin-bottom: 1rem;">Newsletter</h5>
                <p>Subscribe to get updates on new features and tips.</p>
                <form class="mt-3">
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Your email" style="border-radius: 0;">
                        <button class="btn btn-primary" type="submit" style="background-color: var(--secondary); border-color: var(--secondary);">Subscribe</button>
                    </div>
                </form>
            </div>
        </div>
        <hr style="background-color: rgba(255,255,255,0.1);">
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0">© <?php echo date('Y'); ?> Kurus+. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <button class="dark-mode-toggle" onclick="toggleDarkMode()" style="background: var(--primary); color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">
                    <i class="fas fa-moon"></i> Dark Mode
                </button>
            </div>
        </div>
    </div>
</footer>

<script>
// Dark mode functionality
function toggleDarkMode() {
    document.body.classList.toggle("dark-mode");
    const icon = document.querySelector(".dark-mode-toggle i");
    const darkModeToggle = document.querySelector(".dark-mode-toggle");
    
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

// Check for saved user preference or system preference on page load
function checkDarkModePreference() {
    // Check localStorage first
    if (localStorage.getItem("darkMode") === "enabled") {
        document.body.classList.add("dark-mode");
        document.querySelector(".dark-mode-toggle").innerHTML = '<i class="fas fa-sun"></i> Light Mode';
        return;
    } 
    
    // If no localStorage preference, check system preference
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches && 
        localStorage.getItem("darkMode") === null) {
        document.body.classList.add("dark-mode");
        document.querySelector(".dark-mode-toggle").innerHTML = '<i class="fas fa-sun"></i> Light Mode';
        localStorage.setItem("darkMode", "enabled");
    }
}

// Initialize dark mode
document.addEventListener('DOMContentLoaded', checkDarkModePreference);

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
</script>