<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in as admin
if(!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// Get current admin role
$user_role = $_SESSION['admin_role'] ?? 'admin';
$is_super_admin = ($user_role == 'super_admin');

// Handle user deletion
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    // Check if user has orders
    $check_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE user_id = $delete_id");
    $has_orders = $check_orders->fetch_assoc()['count'] > 0;
    
    if(!$has_orders) {
        $delete_sql = "DELETE FROM users WHERE id = ? AND (is_admin = 0 OR is_admin IS NULL)";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $delete_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        header("Location: users.php?msg=deleted");
    } else {
        header("Location: users.php?msg=has_orders");
    }
    exit();
}

// Handle user status toggle (ban/unban)
if(isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $user_id = $_GET['toggle_status'];
    $conn->query("UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE id = $user_id AND (is_admin = 0 OR is_admin IS NULL)");
    header("Location: users.php");
    exit();
}

// Handle make admin (only for super admin)
if($is_super_admin && isset($_GET['make_admin']) && is_numeric($_GET['make_admin'])) {
    $user_id = $_GET['make_admin'];
    
    // Check if user is not already admin
    $check_sql = "SELECT is_admin, email, first_name, last_name FROM users WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $user = $check_result->fetch_assoc();
    
    if($user && ($user['is_admin'] == 0 || $user['is_admin'] == NULL)) {
        $update_sql = "UPDATE users SET is_admin = 1, role = 'admin', created_by = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $_SESSION['admin_id'], $user_id);
        
        if($update_stmt->execute()) {
            header("Location: users.php?msg=make_admin_success");
        } else {
            header("Location: users.php?msg=make_admin_failed");
        }
        $update_stmt->close();
    } else {
        header("Location: users.php?msg=already_admin");
    }
    $check_stmt->close();
    exit();
}

// Get filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query - EXCLUDE admin users properly
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
          (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE user_id = u.id AND status != 'cancelled') as total_spent
          FROM users u 
          WHERE (u.is_admin = 0 OR u.is_admin IS NULL OR u.is_admin = '')";

// Also exclude the currently logged-in admin
if(isset($_SESSION['admin_id'])) {
    $query .= " AND u.id != " . intval($_SESSION['admin_id']);
}

if($search) {
    $query .= " AND (u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.email LIKE '%$search%' OR u.phone LIKE '%$search%')";
}
if($status_filter) {
    $query .= " AND u.status = '$status_filter'";
}

$query .= " ORDER BY u.created_at DESC";
$users = $conn->query($query);

// Get statistics - EXCLUDE admin users
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE (is_admin = 0 OR is_admin IS NULL OR is_admin = '') AND id != " . intval($_SESSION['admin_id']))->fetch_assoc()['count'];
$active_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE (is_admin = 0 OR is_admin IS NULL OR is_admin = '') AND id != " . intval($_SESSION['admin_id']) . " AND (status = 'active' OR status IS NULL)")->fetch_assoc()['count'];
$inactive_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE (is_admin = 0 OR is_admin IS NULL OR is_admin = '') AND id != " . intval($_SESSION['admin_id']) . " AND status = 'inactive'")->fetch_assoc()['count'];
$users_with_orders = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM orders WHERE user_id IN (SELECT id FROM users WHERE is_admin = 0 OR is_admin IS NULL)")->fetch_assoc()['count'];
$total_spent_all = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status != 'cancelled'")->fetch_assoc()['total'];

// Get single user details for modal
$user_details = null;
if(isset($_GET['view']) && is_numeric($_GET['view'])) {
    $view_id = $_GET['view'];
    $detail_sql = "SELECT u.*, 
                   (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
                   (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE user_id = u.id AND status != 'cancelled') as total_spent
                   FROM users u WHERE u.id = ? AND (u.is_admin = 0 OR u.is_admin IS NULL)";
    $detail_stmt = $conn->prepare($detail_sql);
    $detail_stmt->bind_param("i", $view_id);
    $detail_stmt->execute();
    $user_details = $detail_stmt->get_result()->fetch_assoc();
    
    // Get user's recent orders
    if($user_details) {
        $user_orders = $conn->query("SELECT * FROM orders WHERE user_id = $view_id ORDER BY created_at DESC LIMIT 5");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers | Ray & Roses Admin</title>
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

        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: white;
            padding: 1.2rem;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #c45c4a, #e8876e);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(196,92,74,0.1);
        }

        .stat-card .number {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .stat-card .label {
            font-size: 0.8rem;
            color: #7b6b5c;
            margin-top: 0.3rem;
        }

        .stat-card.total .number { color: #c45c4a; }
        .stat-card.active .number { color: #4caf50; }
        .stat-card.inactive .number { color: #f44336; }
        .stat-card.orders .number { color: #ff9800; }
        .stat-card.spent .number { color: #c45c4a; }

        /* Filter Bar */
        .filter-bar {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }

        .filter-input, .filter-select {
            padding: 0.5rem 1rem;
            border: 2px solid #f0e0d4;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            transition: 0.3s;
        }

        .filter-input:focus, .filter-select:focus {
            outline: none;
            border-color: #c45c4a;
            box-shadow: 0 0 0 3px rgba(196,92,74,0.1);
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fef6ef;
            border-radius: 8px;
            color: #5a3f2c;
            text-decoration: none;
            transition: 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-icon:hover {
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            color: white;
            transform: translateY(-2px);
        }

        /* Make Admin Button */
        .btn-make-admin {
            background: linear-gradient(135deg, #4caf50, #45a049);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.75rem;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            white-space: nowrap;
        }

        .btn-make-admin:hover {
            background: linear-gradient(135deg, #45a049, #3d8b40);
            transform: translateY(-1px);
        }

        /* Super Admin Badge */
        .super-admin-badge {
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            color: white;
            padding: 0.2rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            margin-left: 0.5rem;
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

        .alert-warning {
            background: #fff3e0;
            color: #ff9800;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            border-left: 4px solid #ff9800;
        }

        .alert-error {
            background: #fee;
            color: #f44336;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            border-left: 4px solid #f44336;
        }

        /* Table */
        .table-responsive {
            background: white;
            border-radius: 20px;
            overflow-x: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
            vertical-align: middle;
        }

        .data-table tr:hover {
            background: #fefaf7;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-active {
            background: #e8f5e9;
            color: #4caf50;
        }

        .status-inactive {
            background: #fee;
            color: #f44336;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 700px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            padding: 2rem;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0e0d4;
        }

        .modal-header h2 {
            font-family: 'Playfair Display', serif;
            color: #2d2a24;
        }

        .modal-header h2 i {
            color: #c45c4a;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #7b6b5c;
            transition: 0.3s;
        }

        .close-modal:hover {
            color: #c45c4a;
        }

        .user-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #fefaf7;
            border-radius: 16px;
        }

        .user-info-item {
            display: flex;
            flex-direction: column;
        }

        .user-info-label {
            font-size: 0.7rem;
            color: #7b6b5c;
            text-transform: uppercase;
        }

        .user-info-value {
            font-weight: 600;
            margin-top: 0.3rem;
            color: #2d2a24;
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
            .user-info-grid {
                grid-template-columns: 1fr;
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
                <h1><i class="fas fa-users"></i> Customer Management</h1>
                <div>
                    <span class="stat-card spent" style="display: inline-block; padding: 0.5rem 1rem; margin: 0;">
                        <i class="fas fa-tag"></i> Total Spent: KES <?php echo number_format($total_spent_all, 2); ?>
                    </span>
                    <?php if($is_super_admin): ?>
                    <span class="super-admin-badge" style="margin-left: 1rem;">
                        <i class="fas fa-crown"></i> Super Admin
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if(isset($_GET['msg'])): ?>
                <?php if($_GET['msg'] == 'deleted'): ?>
                <div class="alert-success"><i class="fas fa-check-circle"></i> Customer deleted successfully!</div>
                <?php elseif($_GET['msg'] == 'has_orders'): ?>
                <div class="alert-warning"><i class="fas fa-exclamation-triangle"></i> Cannot delete customer with existing orders!</div>
                <?php elseif($_GET['msg'] == 'make_admin_success'): ?>
                <div class="alert-success"><i class="fas fa-check-circle"></i> Customer has been promoted to Admin successfully!</div>
                <?php elseif($_GET['msg'] == 'make_admin_failed'): ?>
                <div class="alert-error"><i class="fas fa-exclamation-circle"></i> Failed to promote customer to Admin!</div>
                <?php elseif($_GET['msg'] == 'already_admin'): ?>
                <div class="alert-warning"><i class="fas fa-exclamation-triangle"></i> This user is already an Admin!</div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Stats Row -->
            <div class="stats-row">
                <div class="stat-card total"><div class="number"><?php echo $total_users; ?></div><div class="label">Total Customers</div></div>
                <div class="stat-card active"><div class="number"><?php echo $active_users; ?></div><div class="label">Active</div></div>
                <div class="stat-card inactive"><div class="number"><?php echo $inactive_users; ?></div><div class="label">Inactive</div></div>
                <div class="stat-card orders"><div class="number"><?php echo $users_with_orders; ?></div><div class="label">Have Orders</div></div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <form method="GET" action="" style="display: flex; gap: 0.8rem; flex-wrap: wrap; align-items: center;">
                    <input type="text" name="search" class="filter-input" placeholder="Search by name, email, phone..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="status" class="filter-select">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                    <button type="submit" class="btn-icon" style="background: linear-gradient(135deg, #c45c4a, #e8876e); color: white; width: auto; padding: 0 1rem;"><i class="fas fa-search"></i> Filter</button>
                    <a href="users.php" class="btn-icon" style="background: #7b6b5c; color: white; width: auto; padding: 0 1rem; text-decoration: none;"><i class="fas fa-undo-alt"></i> Reset</a>
                </form>
            </div>
            
            <!-- Users Table -->
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Contact Info</th>
                            <th>Joined</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($users && $users->num_rows > 0): ?>
                            <?php while($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.8rem;">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                        </div>
                                    </div>
                                  </td>
                                <td>
                                    <i class="fas fa-envelope" style="color: #c45c4a;"></i> <?php echo htmlspecialchars($user['email']); ?>
                                    <br>
                                    <i class="fas fa-phone" style="color: #c45c4a;"></i> <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?>
                                  </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?> </td>
                                <td><?php echo $user['order_count']; ?> </td>
                                <td class="price">KES <?php echo number_format($user['total_spent'], 2); ?> </td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['status'] ?? 'active'; ?>">
                                        <?php echo ucfirst($user['status'] ?? 'Active'); ?>
                                    </span>
                                  </td>
                                <td class="actions">
                                    <a href="?view=<?php echo $user['id']; ?>" class="btn-icon" title="View Details"><i class="fas fa-eye"></i></a>
                                    <a href="?toggle_status=<?php echo $user['id']; ?>" class="btn-icon" title="<?php echo ($user['status'] ?? 'active') == 'active' ? 'Ban User' : 'Unban User'; ?>">
                                        <i class="fas <?php echo ($user['status'] ?? 'active') == 'active' ? 'fa-ban' : 'fa-check-circle'; ?>"></i>
                                    </a>
                                    <?php if($is_super_admin): ?>
                                    <a href="?make_admin=<?php echo $user['id']; ?>" class="btn-make-admin" title="Make this user an Admin" onclick="return confirm('Make <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> an administrator? They will gain access to the admin panel.')">
                                        <i class="fas fa-user-shield"></i> Make Admin
                                    </a>
                                    <?php else: ?>
                                    <span style="font-size: 0.7rem; color: #7b6b5c;" title="Only Super Admin can promote users">
                                        <i class="fas fa-lock"></i> Promote locked
                                    </span>
                                    <?php endif; ?>
                                    <a href="?delete=<?php echo $user['id']; ?>" class="btn-icon" title="Delete" onclick="return confirm('Delete this customer?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 3rem;">
                                    <i class="fas fa-users" style="font-size: 3rem; color: #e2cbb8;"></i>
                                    <p style="margin-top: 1rem; color: #7b6b5c;">No customers found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- User Details Modal -->
    <?php if($user_details): ?>
    <div id="userModal" class="modal show">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-circle"></i> Customer Details</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            
            <div class="user-info-grid">
                <div class="user-info-item">
                    <span class="user-info-label">Full Name</span>
                    <span class="user-info-value"><?php echo htmlspecialchars($user_details['first_name'] . ' ' . $user_details['last_name']); ?></span>
                </div>
                <div class="user-info-item">
                    <span class="user-info-label">Email Address</span>
                    <span class="user-info-value"><?php echo htmlspecialchars($user_details['email']); ?></span>
                </div>
                <div class="user-info-item">
                    <span class="user-info-label">Phone Number</span>
                    <span class="user-info-value"><?php echo htmlspecialchars($user_details['phone'] ?? 'Not provided'); ?></span>
                </div>
                <div class="user-info-item">
                    <span class="user-info-label">Member Since</span>
                    <span class="user-info-value"><?php echo date('F d, Y', strtotime($user_details['created_at'])); ?></span>
                </div>
                <div class="user-info-item">
                    <span class="user-info-label">Total Orders</span>
                    <span class="user-info-value"><?php echo $user_details['order_count']; ?> orders</span>
                </div>
                <div class="user-info-item">
                    <span class="user-info-label">Total Spent</span>
                    <span class="user-info-value">KES <?php echo number_format($user_details['total_spent'], 2); ?></span>
                </div>
                <div class="user-info-item">
                    <span class="user-info-label">Status</span>
                    <span class="user-info-value">
                        <span class="status-badge status-<?php echo $user_details['status'] ?? 'active'; ?>">
                            <?php echo ucfirst($user_details['status'] ?? 'Active'); ?>
                        </span>
                    </span>
                </div>
            </div>
            
            <?php if(isset($user_orders) && $user_orders->num_rows > 0): ?>
            <h3 style="color: #2d2a24; margin-bottom: 1rem;">Recent Orders</h3>
            <table class="data-table" style="margin-top: 0.5rem;">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($order = $user_orders->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $order['order_number']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                        <td>KES <?php echo number_format($order['total_amount'], 2); ?></td>
                        <td><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #7b6b5c; padding: 1rem;">No orders yet</p>
            <?php endif; ?>
            
            <?php if($is_super_admin): ?>
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #f0e0d4;">
                <a href="?make_admin=<?php echo $user_details['id']; ?>" class="btn-make-admin" onclick="return confirm('Make this user an administrator?')">
                    <i class="fas fa-user-shield"></i> Promote to Admin
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        function closeModal() {
            document.getElementById('userModal').classList.remove('show');
            window.location.href = 'users.php';
        }
        
        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if(e.key === 'Escape') {
                const modal = document.getElementById('userModal');
                if(modal && modal.classList.contains('show')) {
                    closeModal();
                }
            }
        });
    </script>
</body>
</html>