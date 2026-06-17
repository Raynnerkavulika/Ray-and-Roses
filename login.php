<?php
session_start();
require_once 'config/database.php';

$error_message = '';

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    // Redirect regular users to dashboard
    $redirect_page = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'dashboard.php';
    unset($_SESSION['redirect_after_login']);
    header("Location: $redirect_page");
    exit();
}

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']) ? true : false;
    
    if(empty($email) || empty($password)) {
        $error_message = "Please fill in all fields";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address";
    } else {
        // Check if user exists
        $sql = "SELECT id, first_name, last_name, email, password FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if(password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
                
                // Set remember me cookie
                if($remember_me) {
                    $token = bin2hex(random_bytes(32));
                    $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                    
                    $update_sql = "UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("ssi", $token, $expiry, $user['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
                }
                
                // Redirect to dashboard or requested page
                $redirect_page = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'dashboard.php';
                unset($_SESSION['redirect_after_login']);
                header("Location: $redirect_page");
                exit();
            } else {
                $error_message = "Invalid email or password";
            }
        } else {
            $error_message = "Invalid email or password";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Petal & Stem</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #fff5ef 0%, #ffe8e0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
        }

        .auth-container {
            display: flex;
            max-width: 750px;
            width: 100%;
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-left {
            flex: 0.8;
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            padding: 1.8rem;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .auth-left::before {
            content: '🌸';
            position: absolute;
            font-size: 200px;
            opacity: 0.08;
            bottom: -40px;
            right: -40px;
        }

        .auth-left h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .auth-left p {
            line-height: 1.4;
            opacity: 0.85;
            font-size: 0.75rem;
        }

        .auth-right {
            flex: 1.2;
            padding: 1.8rem;
        }

        .auth-right h3 {
            font-size: 1.4rem;
            color: #3d2a1f;
            margin-bottom: 0.2rem;
        }

        .auth-right .subtitle {
            color: #7a5a48;
            margin-bottom: 1rem;
            font-size: 0.75rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 1rem;
        }

        .input-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #c45c4a;
            font-size: 0.9rem;
            z-index: 1;
        }

        .input-group input {
            width: 100%;
            padding: 0.6rem 55px 0.6rem 38px;
            border: 2px solid #f0e0d4;
            border-radius: 10px;
            font-size: 0.85rem;
            transition: 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .input-group input:focus {
            outline: none;
            border-color: #c45c4a;
            box-shadow: 0 0 0 2px rgba(196,92,74,0.1);
        }

        /* Password toggle button */
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #7a5a48;
            cursor: pointer;
            font-size: 1rem;
            padding: 8px 10px;
            z-index: 2;
            border-radius: 4px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .toggle-password:hover {
            color: #c45c4a;
            background: rgba(196, 92, 74, 0.08);
        }

        .toggle-password:focus {
            outline: none;
        }

        .checkbox-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            cursor: pointer;
            font-size: 0.75rem;
            color: #5a3f2c;
        }

        .checkbox-group input {
            width: 14px;
            height: 14px;
        }

        .forgot-link {
            color: #c45c4a;
            text-decoration: none;
            font-size: 0.75rem;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            color: white;
            border: none;
            padding: 0.6rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .login-btn:hover {
            background: linear-gradient(135deg, #a84a3a, #d47358);
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(196,92,74,0.3);
        }

        .register-link {
            text-align: center;
            margin-top: 1rem;
            color: #7a5a48;
            font-size: 0.75rem;
        }

        .register-link a {
            color: #c45c4a;
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .social-login {
            margin-top: 1rem;
        }

        .social-login p {
            text-align: center;
            color: #7a5a48;
            margin-bottom: 0.8rem;
            position: relative;
            font-size: 0.7rem;
        }

        .social-login p::before,
        .social-login p::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 30%;
            height: 1px;
            background: #f0e0d4;
        }

        .social-login p::before {
            left: 0;
        }

        .social-login p::after {
            right: 0;
        }

        .social-icons {
            display: flex;
            gap: 0.8rem;
            justify-content: center;
            margin-bottom: 0.8rem;
        }

        .social-icons a {
            width: 32px;
            height: 32px;
            background: #f5f0eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #c45c4a;
            font-size: 0.9rem;
            transition: 0.3s;
            text-decoration: none;
        }

        .social-icons a:hover {
            background: #c45c4a;
            color: white;
            transform: translateY(-2px);
        }

        .home-link-container {
            text-align: center;
            margin-top: 0.5rem;
        }

        .home-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #f5f0eb;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            text-decoration: none;
            color: #c45c4a;
            font-weight: 600;
            transition: 0.3s;
            font-family: 'Inter', sans-serif;
            font-size: 0.7rem;
        }

        .home-link:hover {
            background: #c45c4a;
            color: white;
            transform: translateY(-2px);
        }

        .error-message {
            background: #fee;
            color: #c45c4a;
            padding: 0.5rem;
            border-radius: 8px;
            margin-bottom: 0.8rem;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
                max-width: 360px;
            }
            .auth-left {
                padding: 1rem;
                text-align: center;
            }
            .auth-right {
                padding: 1rem;
            }
            .input-group input {
                padding: 0.6rem 50px 0.6rem 35px;
            }
            .toggle-password {
                right: 12px;
                padding: 6px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-left">
            <h2>Welcome Back!</h2>
            <p>Sign in to continue your floral journey</p>
            <div style="margin-top: 1rem;">
                <i class="fas fa-flower" style="font-size: 2rem; opacity: 0.5;"></i>
            </div>
        </div>
        <div class="auth-right">
            <h3>Sign In</h3>
            <p class="subtitle">Access your flower paradise</p>
            
            <?php if($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="loginForm">
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="email" placeholder="Email Address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility()" aria-label="Toggle password visibility">
                        <i class="fas fa-eye" id="passwordEyeIcon"></i>
                    </button>
                </div>
                
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="remember_me" id="remember_me"> Remember me
                    </label>
                    <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
                </div>
                
                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            
            <div class="register-link">
                Don't have an account? <a href="register.php">Create one for free</a>
            </div>
            
            <div class="social-login">
                <p>Or continue with</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-google"></i></a>
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-apple"></i></a>
                </div>
                
                <div class="home-link-container">
                    <a href="index.php" class="home-link">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePasswordVisibility() {
            const input = document.getElementById('password');
            const eyeIcon = document.getElementById('passwordEyeIcon');
            
            if (!input || !eyeIcon) return;
            
            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                const errorDiv = document.querySelector('.error-message');
                if (errorDiv) {
                    errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please fill in all fields';
                    errorDiv.style.display = 'flex';
                } else {
                    alert('Please fill in all fields');
                }
                return false;
            }
            
            // Validate email format
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                const errorDiv = document.querySelector('.error-message');
                if (errorDiv) {
                    errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please enter a valid email address';
                    errorDiv.style.display = 'flex';
                }
                return false;
            }
            
            return true;
        });

        // Hide error message after 5 seconds
        setTimeout(() => {
            const errorDiv = document.querySelector('.error-message');
            if (errorDiv) {
                errorDiv.style.display = 'none';
            }
        }, 5000);
    </script>
</body>
</html>