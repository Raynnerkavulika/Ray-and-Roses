<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in as admin
if(!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// Get current admin info
$current_admin_id = $_SESSION['admin_id'];
$current_admin_role = $_SESSION['admin_role'] ?? 'admin';

// Check if current user is super admin
$check_sql = "SELECT role FROM users WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $current_admin_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$current_user = $check_result->fetch_assoc();

// Only super admin can access this page
if($current_user['role'] !== 'super_admin') {
    header("Location: dashboard.php");
    exit();
}

$error_message = '';
$success_message = '';

// Handle adding new admin
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_admin'])) {
    $email = trim($_POST['email'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'admin';
    
    // Validation
    if(empty($email) || empty($first_name) || empty($last_name) || empty($password)) {
        $error_message = "Please fill in all required fields";
    } elseif(strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters";
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
            $error_message = "Email already registered!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_sql = "INSERT INTO users (first_name, last_name, email, password, is_admin, role, created_by, created_at) 
                          VALUES (?, ?, ?, ?, 1, ?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssssssi", $first_name, $last_name, $email, $hashed_password, $role, $current_admin_id);
            
            if($insert_stmt->execute()) {
                $success_message = "Admin user added successfully!";
                $_POST = array();
            } else {
                $error_message = "Failed to add admin. Please try again.";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

// Handle removing admin
if(isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $remove_id = $_GET['remove'];
    
    // Prevent removing self
    if($remove_id == $current_admin_id) {
        $error_message = "You cannot remove yourself!";
    } else {
        $check_sql = "SELECT role FROM users WHERE id = ? AND is_admin = 1";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $remove_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if($check_result->num_rows > 0) {
            $update_sql = "UPDATE users SET is_admin = 0, role = 'user' WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $remove_id);
            
            if($update_stmt->execute()) {
                $success_message = "Admin privileges removed successfully!";
            } else {
                $error_message = "Failed to remove admin privileges.";
            }
            $update_stmt->close();
        } else {
            $error_message = "User is not an admin.";
        }
        $check_stmt->close();
    }
}

// Handle updating admin role
if(isset($_POST['update_role']) && isset($_POST['user_id']) && is_numeric($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];
    
    if($user_id == $current_admin_id && $new_role != 'super_admin') {
        $error_message = "You cannot change your own super admin role!";
    } else {
        $update_sql = "UPDATE users SET role = ? WHERE id = ? AND is_admin = 1";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $new_role, $user_id);
        
        if($update_stmt->execute()) {
            $success_message = "Admin role updated successfully!";
        } else {
            $error_message = "Failed to update role.";
        }
        $update_stmt->close();
    }
}

// Get all admin users
$admins_sql = "SELECT id, first_name, last_name, email, role, created_at, created_by 
               FROM users 
               WHERE is_admin = 1 
               ORDER BY 
                   CASE WHEN role = 'super_admin' THEN 0 ELSE 1 END,
                   created_at ASC";
$admins = $conn->query($admins_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins | Ray & Roses Admin</title>
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

        /* Form Container */
        .form-container {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .form-title {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0e0d4;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #2d2a24;
            font-family: 'Playfair Display', serif;
        }

        .form-title i {
            color: #c45c4a;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 2px solid #f0e0d4;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            transition: 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #c45c4a;
            box-shadow: 0 0 0 3px rgba(196,92,74,0.1);
        }

        .form-group small {
            color: #7b6b5c;
            font-size: 0.7rem;
            display: block;
            margin-top: 0.3rem;
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

        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.8rem;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .btn-warning {
            background: #ff9800;
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.8rem;
            transition: 0.3s;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-warning:hover {
            background: #e68900;
            transform: translateY(-1px);
        }

        /* Alerts */
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

        /* Table Container */
        .table-container {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .table-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0e0d4;
            color: #2d2a24;
            font-family: 'Playfair Display', serif;
        }

        .table-title i {
            color: #c45c4a;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #fef6ef;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #2d2a24;
            border-bottom: 2px solid #f0e0d4;
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid #f0e0d4;
        }

        .data-table tr:hover {
            background: #fefaf7;
        }

        /* Role Badges */
        .role-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .role-super-admin {
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            color: white;
        }

        .role-admin {
            background: #e3f2fd;
            color: #1976d2;
        }

        /* You Badge */
        .you-badge {
            background: #e8f5e9;
            color: #4caf50;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            font-size: 0.7rem;
            margin-left: 0.5rem;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* Info Box */
        .info-box {
            background: #fef6ef;
            padding: 1rem;
            border-radius: 16px;
            margin-top: 1rem;
            border-left: 4px solid #c45c4a;
        }

        .info-box ul {
            margin-left: 1.5rem;
            margin-top: 0.5rem;
            color: #7b6b5c;
        }

        .info-box li {
            margin-bottom: 0.3rem;
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
            .form-grid {
                grid-template-columns: 1fr;
            }
            .data-table {
                font-size: 0.85rem;
            }
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-users-cog"></i> Manage Administrators</h1>
                <div>
                    <span class="role-badge role-super-admin">
                        <i class="fas fa-crown"></i> Super Admin
                    </span>
                </div>
            </div>
            
            <?php if($error_message): ?>
            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if($success_message): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <!-- Add New Admin Form -->
            <div class="form-container">
                <div class="form-title">
                    <i class="fas fa-user-plus"></i> Add New Administrator
                </div>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>First Name <span class="required">*</span></label>
                            <input type="text" name="first_name" required value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Last Name <span class="required">*</span></label>
                            <input type="text" name="last_name" required value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Email Address <span class="required">*</span></label>
                            <input type="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Password <span class="required">*</span></label>
                            <input type="password" name="password" required>
                            <small>Minimum 6 characters</small>
                        </div>
                        <div class="form-group">
                            <label>Role <span class="required">*</span></label>
                            <select name="role">
                                <option value="admin">Admin</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                            <small>Super Admin can manage other admins</small>
                        </div>
                    </div>
                    <button type="submit" name="add_admin" class="btn-primary">
                        <i class="fas fa-plus"></i> Add Administrator
                    </button>
                </form>
            </div>
            
            <!-- Admins List Table -->
            <div class="table-container">
                <div class="table-title">
                    <i class="fas fa-list"></i> Administrator List
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Added By</th>
                            <th>Added On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($admins && $admins->num_rows > 0): ?>
                            <?php while($admin = $admins->fetch_assoc()): 
                                $is_current_user = ($admin['id'] == $current_admin_id);
                                $can_remove = !$is_current_user;
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></strong>
                                    <?php if($is_current_user): ?>
                                        <span class="you-badge"><i class="fas fa-user-check"></i> You</span>
                                    <?php endif; ?>
                                 </td>
                                <td><?php echo htmlspecialchars($admin['email']); ?> </td>
                                <td>
                                    <span class="role-badge <?php echo $admin['role'] == 'super_admin' ? 'role-super-admin' : 'role-admin'; ?>">
                                        <?php echo $admin['role'] == 'super_admin' ? '<i class="fas fa-crown"></i> Super Admin' : '<i class="fas fa-user-shield"></i> Admin'; ?>
                                    </span>
                                 </td>
                                <td>
                                    <?php 
                                    if($admin['created_by']) {
                                        $creator_sql = "SELECT first_name, last_name FROM users WHERE id = ?";
                                        $creator_stmt = $conn->prepare($creator_sql);
                                        $creator_stmt->bind_param("i", $admin['created_by']);
                                        $creator_stmt->execute();
                                        $creator_result = $creator_stmt->get_result();
                                        if($creator = $creator_result->fetch_assoc()) {
                                            echo htmlspecialchars($creator['first_name'] . ' ' . $creator['last_name']);
                                        } else {
                                            echo '<span style="color: #7b6b5c;">System</span>';
                                        }
                                        $creator_stmt->close();
                                    } else {
                                        echo '<span style="color: #7b6b5c;">System</span>';
                                    }
                                    ?>
                                 </td>
                                <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?> </td>
                                <td class="actions">
                                    <?php if(!$is_current_user && $admin['role'] != 'super_admin'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $admin['id']; ?>">
                                        <input type="hidden" name="role" value="super_admin">
                                        <button type="submit" name="update_role" class="btn-warning" title="Make Super Admin">
                                            <i class="fas fa-crown"></i> Make Super Admin
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <?php if($can_remove): ?>
                                    <a href="?remove=<?php echo $admin['id']; ?>" class="btn-danger" onclick="return confirm('Remove admin privileges from <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>?')">
                                        <i class="fas fa-trash"></i> Remove
                                    </a>
                                    <?php else: ?>
                                    <span style="color: #7b6b5c; font-size: 0.7rem;"><i class="fas fa-lock"></i> Cannot remove self</span>
                                    <?php endif; ?>
                                 </td>
                             </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: #7b6b5c;">
                                    <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                                    No administrators found
                                 </td>
                             </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Info Box -->
            <div class="info-box">
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <i class="fas fa-info-circle" style="font-size: 1.5rem; color: #c45c4a;"></i>
                    <div>
                        <strong style="color: #2d2a24;">📋 Administrator Management Notes:</strong>
                        <ul>
                            <li>Super Admins have full access including managing other admins</li>
                            <li>Regular Admins can manage products, orders, and customers but cannot manage other admins</li>
                            <li>The original Super Admin account cannot be removed by anyone</li>
                            <li>You cannot remove your own admin privileges</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>