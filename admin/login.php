<?php
session_start();
require_once '../config/database.php';

$error_message = '';

// Redirect if already logged in
if(isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Update last login time function
function updateLastLogin($user_id, $conn) {
    $update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $user_id);
    $update_stmt->execute();
    $update_stmt->close();
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if(empty($email) || empty($password)) {
        $error_message = "Please fill in all fields";
    } else {
        // Get user with role information
        $sql = "SELECT id, first_name, last_name, email, password, is_admin, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($row = $result->fetch_assoc()) {
            // Check if user is admin
            if($row['is_admin'] == 1) {
                if(password_verify($password, $row['password'])) {
                    // Set session variables
                    $_SESSION['admin_id'] = $row['id'];
                    $_SESSION['admin_name'] = $row['first_name'] . ' ' . $row['last_name'];
                    $_SESSION['admin_email'] = $row['email'];
                    $_SESSION['is_admin'] = true;
                    $_SESSION['admin_role'] = $row['role'] ?? 'admin';
                    
                    // Update last login time
                    updateLastLogin($row['id'], $conn);
                    
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error_message = "Invalid password. Please try again.";
                }
            } else {
                $error_message = "You do not have administrator privileges.";
            }
        } else {
            $error_message = "No account found with this email address.";
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
    <title>Admin Login | Ray & Roses</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        /* Decorative Flowers */
        body::before {
            content: '🌸';
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 60px;
            opacity: 0.1;
            pointer-events: none;
        }

        body::after {
            content: '🌺';
            position: absolute;
            bottom: 20px;
            right: 20px;
            font-size: 60px;
            opacity: 0.1;
            pointer-events: none;
        }

        .login-container {
            max-width: 450px;
            width: 100%;
            background: white;
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header i {
            font-size: 3rem;
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin-bottom: 1rem;
        }

        .login-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: #2d2a24;
        }

        .login-header p {
            color: #7b6b5c;
            margin-top: 0.5rem;
            font-size: 0.9rem;
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
            font-size: 1rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 45px;
            border: 2px solid #f0e0d4;
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #c45c4a;
            box-shadow: 0 0 0 3px rgba(196,92,74,0.1);
        }

        .login-btn {
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(196,92,74,0.3);
        }

        .error-message {
            background: #fee;
            color: #f44336;
            padding: 0.8rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            border-left: 4px solid #f44336;
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-link a {
            color: #c45c4a;
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-link a:hover {
            color: #a84a3a;
            transform: translateX(-3px);
        }

        .info-box {
            background: #fef6ef;
            padding: 0.8rem;
            border-radius: 12px;
            margin-top: 1rem;
            text-align: center;
            font-size: 0.8rem;
            color: #c45c4a;
            border-left: 4px solid #c45c4a;
        }

        .info-box i {
            margin-right: 0.3rem;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            body {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-crown"></i>
            <h1>Admin Login</h1>
            <p>Access the Ray & Roses administration panel</p>
        </div>
        
        <?php if($error_message): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="admin@rayandroses.com" value="admin@rayandroses.com" required>
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Login to Admin Panel
            </button>
        </form>
        
        <div class="back-link">
            <a href="../index.php"><i class="fas fa-home"></i> Back to Website</a>
        </div>
        
        <div class="info-box">
            <i class="fas fa-info-circle"></i> Contact the system administrator for login credentials
        </div>
    </div>
</body>
</html>