<?php
session_start();
require_once 'config/database.php';

$error_message = '';
$success_message = '';

// If user is already logged in, redirect to shop
if(isset($_SESSION['user_id'])) {
    header("Location: shop.php");
    exit();
}

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']) ? true : false;
    
    // Validation
    if(empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error_message = "Please fill in all required fields";
    } elseif($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } elseif(strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters";
    } elseif(!$terms) {
        $error_message = "Please agree to the Terms of Service";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address";
    } else {
        // Check if email already exists
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if($check_stmt->num_rows > 0) {
            $error_message = "Email already registered! Please login instead.";
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $verification_code = bin2hex(random_bytes(32));
            
            $insert_sql = "INSERT INTO users (first_name, last_name, email, phone, password, verification_code, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssssss", $first_name, $last_name, $email, $phone, $hashed_password, $verification_code);
            
            if($insert_stmt->execute()) {
                // Registration successful - redirect to login page
                $success_message = "Account created successfully! Redirecting to login...";
                echo '<meta http-equiv="refresh" content="2;url=login.php?registered=success">';
            } else {
                $error_message = "Registration failed. Please try again.";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Petal & Stem</title>
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
            max-width: 780px;
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
            padding: 1.5rem;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .auth-left::before {
            content: '🌺';
            position: absolute;
            font-size: 180px;
            opacity: 0.08;
            bottom: -40px;
            right: -40px;
        }

        .auth-left h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            margin-bottom: 0.5rem;
        }

        .auth-left p {
            font-size: 0.7rem;
            opacity: 0.85;
        }

        .auth-left ul {
            margin-top: 0.8rem;
            list-style: none;
        }

        .auth-left li {
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.7rem;
        }

        .auth-left li i {
            font-size: 0.8rem;
        }

        .auth-right {
            flex: 1.2;
            padding: 1.5rem;
        }

        .auth-right h3 {
            font-size: 1.4rem;
            color: #3d2a1f;
            margin-bottom: 0.2rem;
        }

        .subtitle {
            color: #7a5a48;
            margin-bottom: 1rem;
            font-size: 0.7rem;
        }

        /* Input with icon */
        .input-group {
            position: relative;
            margin-bottom: 0.8rem;
        }

        .input-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #c45c4a;
            font-size: 0.85rem;
            z-index: 1;
        }

        .input-group input {
            width: 100%;
            padding: 0.55rem 0.8rem 0.55rem 38px;
            border: 2px solid #f0e0d4;
            border-radius: 10px;
            font-size: 0.8rem;
            transition: 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .input-group input:focus {
            outline: none;
            border-color: #c45c4a;
            box-shadow: 0 0 0 2px rgba(196,92,74,0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.8rem;
        }

        .register-btn {
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
            margin-top: 0.3rem;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .register-btn:hover {
            background: linear-gradient(135deg, #a84a3a, #d47358);
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(196,92,74,0.3);
        }

        .login-link {
            text-align: center;
            margin-top: 0.8rem;
            color: #7a5a48;
            font-size: 0.7rem;
        }

        .login-link a {
            color: #c45c4a;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .terms {
            font-size: 0.65rem;
            text-align: center;
            margin-top: 0.5rem;
            color: #7a5a48;
        }

        .terms a {
            color: #c45c4a;
        }

        .home-link-container {
            text-align: center;
            margin-top: 0.8rem;
            padding-top: 0.5rem;
            border-top: 1px solid #f0e0d4;
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

        /* Checkbox styling */
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            cursor: pointer;
            font-size: 0.7rem;
            color: #5a3f2c;
        }

        .checkbox-label input {
            width: 14px;
            height: 14px;
            cursor: pointer;
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
            .form-row {
                grid-template-columns: 1fr;
            }
            body {
                padding: 0.5rem;
            }
        }

        .error-message {
            background: #fee;
            color: #c45c4a;
            padding: 0.5rem;
            border-radius: 8px;
            margin-bottom: 0.8rem;
            font-size: 0.7rem;
            display: <?php echo $error_message ? 'flex' : 'none'; ?>;
            align-items: center;
            gap: 0.4rem;
        }

        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 0.5rem;
            border-radius: 8px;
            margin-bottom: 0.8rem;
            font-size: 0.7rem;
            display: <?php echo $success_message ? 'flex' : 'none'; ?>;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-left">
            <h2>Join Our Floral Family</h2>
            <p>Create your account and unlock:</p>
            <ul>
                <li><i class="fas fa-check-circle"></i> Exclusive collections</li>
                <li><i class="fas fa-check-circle"></i> Birthday reminders</li>
                <li><i class="fas fa-check-circle"></i> 10% off first order</li>
                <li><i class="fas fa-check-circle"></i> Free delivery $50+</li>
            </ul>
        </div>
        <div class="auth-right">
            <h3>Create Account</h3>
            <p class="subtitle">Start your floral journey</p>
            
            <?php if($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            
            <?php if($success_message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="registerForm">
                <div class="form-row">
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="first_name" id="firstName" placeholder="First Name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="last_name" id="lastName" placeholder="Last Name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="email" placeholder="Email Address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                
                <div class="input-group">
                    <i class="fas fa-phone"></i>
                    <input type="tel" name="phone" id="phone" placeholder="Phone (optional)" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
                
                <div class="form-row">
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Password" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-check-circle"></i>
                        <input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirm Password" required>
                    </div>
                </div>
                
                <div class="input-group" style="margin-bottom: 0.5rem;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terms" id="termsCheckbox" <?php echo isset($_POST['terms']) ? 'checked' : ''; ?> required>
                        I agree to <a href="#" style="color: #c45c4a;">Terms & Privacy</a>
                    </label>
                </div>
                
                <button type="submit" class="register-btn">
                    <i class="fas fa-user-plus"></i> Create Free Account
                </button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Sign in instead</a>
            </div>
            
            <div class="terms">
                By signing up, you'll receive special offers
            </div>
            
            <div class="home-link-container">
                <a href="index.php" class="home-link">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const firstName = document.getElementById('firstName').value;
            const lastName = document.getElementById('lastName').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const termsChecked = document.getElementById('termsCheckbox').checked;
            
            if (!firstName || !lastName || !email || !password) {
                e.preventDefault();
                showError('Please fill in all required fields');
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                showError('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                showError('Password must be at least 6 characters');
                return false;
            }
            
            if (!termsChecked) {
                e.preventDefault();
                showError('Please agree to the Terms of Service');
                return false;
            }
            
            return true;
        });
        
        function showError(message) {
            let errorDiv = document.querySelector('.error-message');
            if (!errorDiv || errorDiv.innerHTML === '') {
                errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                const form = document.getElementById('registerForm');
                form.insertBefore(errorDiv, form.firstChild);
            }
            errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
            errorDiv.style.display = 'flex';
            
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>