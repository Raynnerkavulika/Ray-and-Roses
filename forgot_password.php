<?php
session_start();
require_once 'config/database.php';

$error_message = '';
$success_message = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    
    if(empty($email)) {
        $error_message = "Please enter your email address";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address";
    } else {
        // Check if email exists
        $check_sql = "SELECT id, first_name, last_name, email FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if($user = $result->fetch_assoc()) {
            // Generate unique token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Save token to database (you need to create a password_resets table)
            $insert_sql = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sss", $email, $token, $expires);
            
            if($insert_stmt->execute()) {
                // Send reset email
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/flowersite/reset_password.php?token=" . $token;
                
                $to = $email;
                $subject = "Reset Your Password - Ray & Roses";
                $message = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #c45c4a, #e8876e); color: white; padding: 20px; text-align: center; }
                        .content { padding: 30px; background: #fefaf7; }
                        .btn { background: #c45c4a; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; }
                        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>🌺 Ray & Roses</h2>
                            <p>Password Reset Request</p>
                        </div>
                        <div class='content'>
                            <p>Hello <strong>" . htmlspecialchars($user['first_name']) . "</strong>,</p>
                            <p>We received a request to reset your password. Click the button below to create a new password:</p>
                            <p style='text-align: center; margin: 30px 0;'>
                                <a href='" . $reset_link . "' class='btn' style='color: white; text-decoration: none;'>Reset Password</a>
                            </p>
                            <p>This link will expire in 1 hour.</p>
                            <p>If you didn't request this, please ignore this email.</p>
                            <hr>
                            <p style='font-size: 12px; color: #666;'>If the button doesn't work, copy and paste this link:</p>
                            <p style='font-size: 12px; color: #666; word-break: break-all;'>" . $reset_link . "</p>
                        </div>
                        <div class='footer'>
                            <p>&copy; " . date('Y') . " Ray & Roses. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: noreply@rayandroses.com" . "\r\n";
                
                if(mail($to, $subject, $message, $headers)) {
                    $success_message = "Password reset link has been sent to your email address.";
                } else {
                    // For local development without mail server, show the link
                    $success_message = "Password reset link: <a href='" . $reset_link . "'>" . $reset_link . "</a>";
                }
            } else {
                $error_message = "Failed to process request. Please try again.";
            }
            $insert_stmt->close();
        } else {
            // Don't reveal that email doesn't exist (security)
            $success_message = "If an account exists with that email, you will receive a password reset link.";
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
    <title>Forgot Password - Ray & Roses</title>
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
        }

        .forgot-container {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
        }
        
        .forgot-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
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
        
        .forgot-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .forgot-header .icon-wrapper {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        
        .forgot-header .icon-wrapper i {
            font-size: 2rem;
            color: white;
        }
        
        .forgot-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: #2d2a24;
            margin-bottom: 0.5rem;
        }
        
        .forgot-header p {
            color: #7b6b5c;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2d2a24;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #c45c4a;
            z-index: 1;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 45px;
            border: 2px solid #f0e0d4;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: 0.3s;
            font-family: 'Inter', sans-serif;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #c45c4a;
            box-shadow: 0 0 0 3px rgba(196,92,74,0.1);
        }
        
        .btn-reset {
            width: 100%;
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            color: white;
            border: none;
            padding: 0.9rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(196,92,74,0.3);
            background: linear-gradient(135deg, #a84a3a, #d47358);
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #4caf50;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-success a {
            color: #2e7d32;
            font-weight: 600;
            word-break: break-all;
        }
        
        .alert-error {
            background: #fee;
            color: #c45c4a;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #f44336;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #f0e0d4;
        }
        
        .back-link a {
            color: #c45c4a;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-link a:hover {
            color: #a84a3a;
            text-decoration: underline;
        }
        
        .home-link-container {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .home-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #f5f0eb;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            color: #c45c4a;
            font-weight: 600;
            transition: 0.3s;
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
        }
        
        .home-link:hover {
            background: #c45c4a;
            color: white;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .forgot-card {
                padding: 1.5rem;
            }
            
            .forgot-header h1 {
                font-size: 1.5rem;
            }
            
            .forgot-header .icon-wrapper {
                width: 60px;
                height: 60px;
            }
            
            .forgot-header .icon-wrapper i {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-card">
            <div class="forgot-header">
                <div class="icon-wrapper">
                    <i class="fas fa-key"></i>
                </div>
                <h1>Forgot Password?</h1>
                <p>Enter your email address and we'll send you a link to reset your password.</p>
            </div>
            
            <?php if($error_message): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            
            <?php if($success_message): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" required placeholder="your@email.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn-reset">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </form>
            
            <div class="back-link">
                <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
            </div>
            
            <div class="home-link-container">
                <a href="index.php" class="home-link">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>