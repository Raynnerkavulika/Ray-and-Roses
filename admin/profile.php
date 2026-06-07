<?php
session_start();
require_once '../config/database.php';

// Add this to the top of ALL admin pages (dashboard.php, products.php, orders.php, users.php, etc.)
if(!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$error_message = '';
$success_message = '';
$password_error = '';
$password_success = '';

// Get admin data
$admin_sql = "SELECT id, first_name, last_name, email, phone, created_at, role, status FROM users WHERE id = ?";
$admin_stmt = $conn->prepare($admin_sql);
$admin_stmt->bind_param("i", $admin_id);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();
$admin = $admin_result->fetch_assoc();

// Handle profile update
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if(empty($first_name) || empty($last_name) || empty($email)) {
        $error_message = "Please fill in all required fields";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address";
    } else {
        $update_sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $admin_id);
        
        if($update_stmt->execute()) {
            $_SESSION['admin_name'] = $first_name . ' ' . $last_name;
            $_SESSION['admin_email'] = $email;
            $success_message = "Profile updated successfully!";
            // Refresh admin data
            $admin['first_name'] = $first_name;
            $admin['last_name'] = $last_name;
            $admin['email'] = $email;
            $admin['phone'] = $phone;
        } else {
            $error_message = "Failed to update profile";
        }
        $update_stmt->close();
    }
}

// Handle password change
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Verify current password
    $pass_sql = "SELECT password FROM users WHERE id = ?";
    $pass_stmt = $conn->prepare($pass_sql);
    $pass_stmt->bind_param("i", $admin_id);
    $pass_stmt->execute();
    $pass_result = $pass_stmt->get_result();
    $admin_pass = $pass_result->fetch_assoc();
    
    if(!password_verify($current_password, $admin_pass['password'])) {
        $password_error = "Current password is incorrect";
    } elseif(strlen($new_password) < 6) {
        $password_error = "New password must be at least 6 characters";
    } elseif($new_password !== $confirm_password) {
        $password_error = "New passwords do not match";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_pass_sql = "UPDATE users SET password = ? WHERE id = ?";
        $update_pass_stmt = $conn->prepare($update_pass_sql);
        $update_pass_stmt->bind_param("si", $hashed_password, $admin_id);
        
        if($update_pass_stmt->execute()) {
            $password_success = "Password changed successfully!";
        } else {
            $password_error = "Failed to change password";
        }
        $update_pass_stmt->close();
    }
    $pass_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile | Ray & Roses Admin</title>
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
            background: #fefaf7;
            color: #2d2a24;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar - Ray & Roses Colors */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #d4bca8;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(212, 188, 168, 0.2);
            text-align: center;
        }

        .sidebar-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .sidebar-header p {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 0.3rem;
            color: #d4bca8;
        }

        .sidebar-menu {
            list-style: none;
            padding: 1rem 0;
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem 1.5rem;
            color: #d4bca8;
            text-decoration: none;
            transition: 0.3s;
            border-radius: 10px;
            margin: 0.3rem 1rem;
        }

        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background: rgba(196, 92, 74, 0.2);
            color: #c45c4a;
        }

        .sidebar-menu li a i {
            width: 24px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 1rem;
        }

        /* Top Bar */
        .top-bar {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .top-bar h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d2a24;
        }

        .top-bar h1 i {
            color: #c45c4a;
        }

        /* Profile Container */
        .profile-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 1.5rem;
        }

        /* Profile Sidebar */
        .profile-sidebar {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            height: fit-content;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .profile-avatar i {
            font-size: 3rem;
            color: white;
        }

        .profile-sidebar h3 {
            margin-bottom: 0.3rem;
            color: #2d2a24;
            font-family: 'Playfair Display', serif;
        }

        .profile-sidebar p {
            color: #7b6b5c;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .role-badge {
            display: inline-block;
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            color: white;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-bottom: 1rem;
        }

        .profile-stats {
            text-align: left;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #f0e0d4;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
        }

        .stat-label {
            color: #7b6b5c;
        }

        .stat-value {
            font-weight: 600;
            color: #2d2a24;
        }

        /* Profile Main Content */
        .profile-main {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .section-title {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0e0d4;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #2d2a24;
            font-family: 'Playfair Display', serif;
        }

        .section-title i {
            color: #c45c4a;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2d2a24;
        }

        .form-group label .required {
            color: #c45c4a;
        }

        .form-group input {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 2px solid #f0e0d4;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            transition: 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #c45c4a;
            box-shadow: 0 0 0 3px rgba(196,92,74,0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(196,92,74,0.4);
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 0.8rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            border-left: 4px solid #4caf50;
        }

        .alert-error {
            background: #fee;
            color: #f44336;
            padding: 0.8rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            border-left: 4px solid #f44336;
        }

        small {
            color: #7b6b5c;
            font-size: 0.7rem;
            display: block;
            margin-top: 0.3rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            .sidebar-header h2,
            .sidebar-header p,
            .sidebar-menu li a span {
                display: none;
            }
            .main-content {
                margin-left: 70px;
            }
            .profile-container {
                grid-template-columns: 1fr;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-user-circle"></i> My Profile</h1>
                <div>
                    <span class="role-badge">
                        <i class="fas fa-shield-alt"></i> <?php echo ucfirst($admin['role'] ?? 'Admin'); ?>
                    </span>
                </div>
            </div>
            
            <div class="profile-container">
                <!-- Profile Sidebar -->
                <div class="profile-sidebar">
                    <div class="profile-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></h3>
                    <p><?php echo htmlspecialchars($admin['email']); ?></p>
                    <span class="role-badge">
                        <?php echo ucfirst($admin['role'] ?? 'Administrator'); ?>
                    </span>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-label">Member Since</span>
                            <span class="stat-value"><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Account Status</span>
                            <span class="stat-value" style="color: #4caf50;">✓ Active</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">User ID</span>
                            <span class="stat-value">#<?php echo $admin['id']; ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Main Content -->
                <div class="profile-main">
                    <!-- Edit Profile Form -->
                    <form method="POST">
                        <div class="section-title">
                            <i class="fas fa-edit"></i> Edit Profile Information
                        </div>
                        
                        <?php if($success_message): ?>
                        <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if($error_message): ?>
                        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name <span class="required">*</span></label>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($admin['first_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Last Name <span class="required">*</span></label>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($admin['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Email Address <span class="required">*</span></label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>" placeholder="Optional">
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                    
                    <!-- Change Password Form -->
                    <form method="POST" style="margin-top: 2rem;">
                        <div class="section-title">
                            <i class="fas fa-lock"></i> Change Password
                        </div>
                        
                        <?php if(isset($password_success) && $password_success): ?>
                        <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $password_success; ?></div>
                        <?php endif; ?>
                        
                        <?php if(isset($password_error) && $password_error): ?>
                        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $password_error; ?></div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>Current Password <span class="required">*</span></label>
                            <input type="password" name="current_password" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>New Password <span class="required">*</span></label>
                                <input type="password" name="new_password" required>
                                <small>Minimum 6 characters</small>
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password <span class="required">*</span></label>
                                <input type="password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn-primary">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>