<?php
session_start();
require_once 'config/database.php';

$error_message = '';
$success_message = '';
$token_valid = false;
$email = '';

// Get token from URL
$token = $_GET['token'] ?? '';

if(empty($token)) {
    header("Location: forgot_password.php");
    exit();
}

// Verify token
$check_sql = "SELECT email, expires_at FROM password_resets WHERE token = ? AND used = 0";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $token);
$check_stmt->execute();
$result = $check_stmt->get_result();

if($row = $result->fetch_assoc()) {
    $expires_at = strtotime($row['expires_at']);
    $current_time = time();
    
    if($current_time < $expires_at) {
        $token_valid = true;
        $email = $row['email'];
    } else {
        $error_message = "This reset link has expired. Please request a new one.";
        // Delete expired token
        $delete_sql = "DELETE FROM password_resets WHERE token = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("s", $token);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
} else {
    $error_message = "Invalid reset link. Please request a new password reset.";
}
$check_stmt->close();

// Handle password reset
if($_SERVER["REQUEST_METHOD"] == "POST" && $token_valid) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if(empty($new_password) || empty($confirm_password)) {
        $error_message = "Please fill in all fields";
    } elseif(strlen($new_password) < 6) {
        $error_message = "Password must be at least 6 characters";
    } elseif($new_password !== $confirm_password) {
        $error_message = "Passwords do not match";
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET password = ? WHERE email = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ss", $hashed_password, $email);
        
        if($update_stmt->execute()) {
            // Mark token as used
            $mark_sql = "UPDATE password_resets SET used = 1 WHERE token = ?";
            $mark_stmt = $conn->prepare($mark_sql);
            $mark_stmt->bind_param("s", $token);
            $mark_stmt->execute();
            $mark_stmt->close();
            
            $success_message = "Password reset successfully! You can now login with your new password.";
            
            // Redirect after 3 seconds
            echo '<meta http-equiv="refresh" content="3;url=login.php">';
        } else {
            $error_message = "Failed to reset password. Please try again.";
        }
        $update_stmt->close();
    }
}

include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Ray & Roses</title>
    <style>
        .reset-container {
            max-width: 500px;
            margin: 3rem auto;
            padding: 0 5%;
        }
        
        .reset-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .reset-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .reset-header i {
            font-size: 3rem;
            color: #c45c4a;
            margin-bottom: 1rem;
        }
        
        .reset-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: #2d2a24;
        }
        
        .reset-header p {
            color: #7b6b5c;
            margin-top: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2d2a24;
            font-weight: 500;
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
        }
        
        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 45px;
            border: 2px solid #f0e0d4;
            border-radius: 12px;
            font-size: 1rem;
            transition: 0.3s;
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
            padding: 1rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(196,92,74,0.3);
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            border-left: 4px solid #4caf50;
        }
        
        .alert-error {
            background: #fee;
            color: #f44336;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            border-left: 4px solid #f44336;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-link a {
            color: #c45c4a;
            text-decoration: none;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .password-requirements {
            font-size: 0.75rem;
            color: #7b6b5c;
            margin-top: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .reset-card {
                padding: 1.5rem;
            }
            
            .reset-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

<div class="reset-container">
    <div class="reset-card">
        <div class="reset-header">
            <i class="fas fa-lock"></i>
            <h1>Reset Password</h1>
            <p>Create a new password for your account</p>
        </div>
        
        <?php if($error_message): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if($success_message): ?>
        <div class="alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if($token_valid && !$success_message): ?>
        <form method="POST">
            <div class="form-group">
                <label>New Password</label>
                <div class="input-group">
                    <i class="fas fa-key"></i>
                    <input type="password" name="new_password" required placeholder="Enter new password">
                </div>
                <div class="password-requirements">
                    <i class="fas fa-info-circle"></i> Password must be at least 6 characters
                </div>
            </div>
            
            <div class="form-group">
                <label>Confirm Password</label>
                <div class="input-group">
                    <i class="fas fa-check-circle"></i>
                    <input type="password" name="confirm_password" required placeholder="Confirm new password">
                </div>
            </div>
            
            <button type="submit" class="btn-reset">
                <i class="fas fa-save"></i> Reset Password
            </button>
        </form>
        <?php endif; ?>
        
        <?php if(!$token_valid && !$success_message): ?>
        <div class="back-link">
            <a href="forgot_password.php"><i class="fas fa-paper-plane"></i> Request New Reset Link</a>
        </div>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>