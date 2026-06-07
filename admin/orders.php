<?php
session_start();
require_once '../config/database.php';

// Add this to the top of ALL admin pages (dashboard.php, products.php, orders.php, users.php, etc.)
if(!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// Handle order status update
if(isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    
    $update_sql = "UPDATE orders SET status = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_status, $order_id);
    
    if($update_stmt->execute()) {
        $success_message = "Order status updated successfully!";
    } else {
        $error_message = "Failed to update order status.";
    }
    $update_stmt->close();
}

// Handle order deletion
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_sql = "DELETE FROM orders WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $delete_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    header("Location: orders.php?msg=deleted");
    exit();
}

// Get filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Build query
$query = "SELECT o.*, u.first_name, u.last_name, u.email 
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.id 
          WHERE 1=1";

if($search) {
    $query .= " AND (o.order_number LIKE '%$search%' OR u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.email LIKE '%$search%')";
}
if($status_filter) {
    $query .= " AND o.status = '$status_filter'";
}
if($date_filter) {
    $query .= " AND DATE(o.created_at) = '$date_filter'";
}

$query .= " ORDER BY o.created_at DESC";
$orders = $conn->query($query);

// Get statistics
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch_assoc()['count'];
$processing_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'processing'")->fetch_assoc()['count'];
$shipped_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'shipped'")->fetch_assoc()['count'];
$delivered_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'delivered'")->fetch_assoc()['count'];
$cancelled_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE status != 'cancelled'")->fetch_assoc()['revenue'];

// Get single order details for modal
$order_details = null;
if(isset($_GET['view']) && is_numeric($_GET['view'])) {
    $view_id = $_GET['view'];
    $detail_sql = "SELECT o.*, u.first_name, u.last_name, u.email, u.phone 
                   FROM orders o 
                   LEFT JOIN users u ON o.user_id = u.id 
                   WHERE o.id = ?";
    $detail_stmt = $conn->prepare($detail_sql);
    $detail_stmt->bind_param("i", $view_id);
    $detail_stmt->execute();
    $order_details = $detail_stmt->get_result()->fetch_assoc();
    
    // Get order items
    $items_sql = "SELECT oi.*, p.name as product_name 
                  FROM order_items oi 
                  LEFT JOIN products p ON oi.product_id = p.id 
                  WHERE oi.order_id = ?";
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param("i", $view_id);
    $items_stmt->execute();
    $order_items = $items_stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders | Ray & Roses Admin</title>
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
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: white;
            padding: 1rem;
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
        .stat-card.pending .number { color: #ff9800; }
        .stat-card.processing .number { color: #2196f3; }
        .stat-card.delivered .number { color: #4caf50; }
        .stat-card.cancelled .number { color: #f44336; }
        .stat-card.revenue .number { color: #4caf50; }

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

        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3e0;
            color: #ff9800;
        }

        .status-processing {
            background: #e3f2fd;
            color: #2196f3;
        }

        .status-shipped {
            background: #e8eaf6;
            color: #3f51b5;
        }

        .status-delivered {
            background: #e8f5e9;
            color: #4caf50;
        }

        .status-cancelled {
            background: #fee;
            color: #f44336;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
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
            max-width: 800px;
            width: 90%;
            max-height: 85vh;
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

        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #fefaf7;
            border-radius: 16px;
        }

        .order-info-item {
            display: flex;
            flex-direction: column;
        }

        .order-info-label {
            font-size: 0.7rem;
            color: #7b6b5c;
            text-transform: uppercase;
        }

        .order-info-value {
            font-weight: 600;
            margin-top: 0.3rem;
            color: #2d2a24;
        }

        .items-table {
            width: 100%;
            margin: 1rem 0;
        }

        .items-table th {
            background: #fef6ef;
            padding: 0.8rem;
            text-align: left;
            color: #2d2a24;
        }

        .items-table td {
            padding: 0.8rem;
            border-bottom: 1px solid #f0e0d4;
        }

        .status-form {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #f0e0d4;
        }

        .status-form h3 {
            color: #2d2a24;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 70px;
            }
            .order-info-grid {
                grid-template-columns: 1fr;
            }
            .sidebar {
                width: 70px;
            }
            .sidebar-header h2,
            .sidebar-header p,
            .sidebar-menu li a span {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-shopping-bag"></i> Orders Management</h1>
                <div>
                    <span class="stat-card revenue" style="display: inline-block; padding: 0.5rem 1rem; margin: 0;">
                        <i class="fas fa-tag"></i> Total Revenue: KES <?php echo number_format($total_revenue, 2); ?>
                    </span>
                </div>
            </div>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> Order deleted successfully!</div>
            <?php endif; ?>
            
            <?php if(isset($success_message)): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <!-- Stats Row -->
            <div class="stats-row">
                <div class="stat-card total"><div class="number"><?php echo $total_orders; ?></div><div class="label">Total Orders</div></div>
                <div class="stat-card pending"><div class="number"><?php echo $pending_orders; ?></div><div class="label">Pending</div></div>
                <div class="stat-card processing"><div class="number"><?php echo $processing_orders; ?></div><div class="label">Processing</div></div>
                <div class="stat-card"><div class="number"><?php echo $shipped_orders; ?></div><div class="label">Shipped</div></div>
                <div class="stat-card delivered"><div class="number"><?php echo $delivered_orders; ?></div><div class="label">Delivered</div></div>
                <div class="stat-card cancelled"><div class="number"><?php echo $cancelled_orders; ?></div><div class="label">Cancelled</div></div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <form method="GET" action="" style="display: flex; gap: 0.8rem; flex-wrap: wrap; align-items: center;">
                    <input type="text" name="search" class="filter-input" placeholder="Search by order # or customer..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="status" class="filter-select">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <input type="date" name="date" class="filter-input" value="<?php echo $date_filter; ?>">
                    <button type="submit" class="btn-icon" style="background: linear-gradient(135deg, #c45c4a, #e8876e); color: white; width: auto; padding: 0 1rem;"><i class="fas fa-search"></i> Filter</button>
                    <a href="orders.php" class="btn-icon" style="background: #7b6b5c; color: white; width: auto; padding: 0 1rem; text-decoration: none;"><i class="fas fa-undo-alt"></i> Reset</a>
                </form>
            </div>
            
            <!-- Orders Table -->
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($orders && $orders->num_rows > 0): ?>
                            <?php while($order = $orders->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $order['order_number']; ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                    <br><small style="color: #7b6b5c;"><?php echo htmlspecialchars($order['email']); ?></small>
                                 </td>
                                <td><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                                <td class="price">KES <?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>-</td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                 </td>
                                <td class="actions">
                                    <a href="?view=<?php echo $order['id']; ?>" class="btn-icon" title="View Details"><i class="fas fa-eye"></i></a>
                                    <a href="?delete=<?php echo $order['id']; ?>" class="btn-icon" title="Delete" onclick="return confirm('Delete this order?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 3rem;">
                                    <i class="fas fa-shopping-bag" style="font-size: 3rem; color: #e2cbb8;"></i>
                                    <p style="margin-top: 1rem; color: #7b6b5c;">No orders found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Order Details Modal -->
    <?php if($order_details): ?>
    <div id="orderModal" class="modal show">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-receipt"></i> Order Details</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            
            <div class="order-info-grid">
                <div class="order-info-item">
                    <span class="order-info-label">Order Number</span>
                    <span class="order-info-value"><?php echo $order_details['order_number']; ?></span>
                </div>
                <div class="order-info-item">
                    <span class="order-info-label">Date Placed</span>
                    <span class="order-info-value"><?php echo date('F d, Y h:i A', strtotime($order_details['created_at'])); ?></span>
                </div>
                <div class="order-info-item">
                    <span class="order-info-label">Customer Name</span>
                    <span class="order-info-value"><?php echo htmlspecialchars($order_details['first_name'] . ' ' . $order_details['last_name']); ?></span>
                </div>
                <div class="order-info-item">
                    <span class="order-info-label">Email</span>
                    <span class="order-info-value"><?php echo htmlspecialchars($order_details['email']); ?></span>
                </div>
                <div class="order-info-item">
                    <span class="order-info-label">Phone</span>
                    <span class="order-info-value"><?php echo htmlspecialchars($order_details['phone'] ?? 'N/A'); ?></span>
                </div>
                <div class="order-info-item">
                    <span class="order-info-label">Payment Method</span>
                    <span class="order-info-value"><?php echo ucfirst($order_details['payment_method'] ?? 'M-Pesa'); ?></span>
                </div>
            </div>
            
            <h3 style="color: #2d2a24; margin-bottom: 1rem;">Order Items</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(isset($order_items) && $order_items->num_rows > 0): ?>
                        <?php while($item = $order_items->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name'] ?? 'Product'); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>KES <?php echo number_format($item['price'], 2); ?></td>
                            <td>KES <?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No items found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #fef6ef;">
                        <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                        <td><strong>KES <?php echo number_format($order_details['total_amount'], 2); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="status-form">
                <h3>Update Order Status</h3>
                <form method="POST" style="display: flex; gap: 1rem; align-items: center; margin-top: 0.5rem; flex-wrap: wrap;">
                    <input type="hidden" name="order_id" value="<?php echo $order_details['id']; ?>">
                    <select name="status" class="filter-select">
                        <option value="pending" <?php echo $order_details['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $order_details['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $order_details['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $order_details['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $order_details['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <button type="submit" name="update_status" class="btn-icon" style="background: linear-gradient(135deg, #c45c4a, #e8876e); color: white; width: auto; padding: 0.5rem 1rem;">Update Status</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        function closeModal() {
            document.getElementById('orderModal').classList.remove('show');
            window.location.href = 'orders.php';
        }
        
        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if(e.key === 'Escape') {
                const modal = document.getElementById('orderModal');
                if(modal && modal.classList.contains('show')) {
                    closeModal();
                }
            }
        });
    </script>
</body>
</html>