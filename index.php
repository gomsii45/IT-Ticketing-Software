<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['username']) || empty($_POST['password'])) {
        $error = "Please fill in all fields";
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['username'] = $user['username'];
                
                // Redirect based on user type
                if ($user['user_type'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: user_dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid username or password";
            }
        } catch (PDOException $e) {
            $error = "Login failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Support Portal - Quality Austria</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="login-page">
        <div class="login-container">
            <div class="login-logo">
                <img src="images/logo.jpg" alt="Quality Austria">
            </div>
            
            <h2>IT Support Portal</h2>
            <p class="login-subtitle">Welcome to Quality Austria Central Asia</p>

            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php 
                    $error = $_GET['error'];
                    if ($error === 'invalid') {
                        echo 'Invalid username or password';
                    } elseif ($error === 'empty') {
                        echo 'Please fill in all fields';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <!-- Change this section in your form -->
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Username
                    </label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Add this before the closing </form> tag in your login form -->
                            <button type="submit" class="login-button">
                                <i class="fas fa-sign-in-alt"></i>
                                Login
                            </button>
                        </form>
                
                        <div class="register-section">
                            <p>Don't have an account?</p>
                            <a href="register.php" class="register-link">Register Now</a>
                        </div>
                
                        <div class="footer-text">
                <p>© <?php echo date('Y'); ?> Quality Austria Central Asia</p>
                <p class="slogan">Succeed with Quality</p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleBtn.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>