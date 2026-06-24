<?php
session_start();
require_once '../config/database.php';

// Check admin authentication
if(!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// Get user role
$user_role = $_SESSION['admin_role'] ?? 'admin';
$is_super_admin = ($user_role == 'super_admin');
$admin_name = $_SESSION['admin_name'];
$admin_email = $_SESSION['admin_email'];

// Get stats with error handling
$stats = [
    'products' => 0,
    'orders' => 0,
    'revenue' => 0,
    'users' => 0,
    'low_stock' => 0,
    'pending_orders' => 0,
    'today_orders' => 0,
    'month_revenue' => 0
];

// Total products
$products_result = $conn->query("SELECT COUNT(*) as count FROM products");
if($products_result) {
    $stats['products'] = $products_result->fetch_assoc()['count'];
}

// Total orders
$orders_result = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE status != 'cancelled'");
if($orders_result) {
    $order_data = $orders_result->fetch_assoc();
    $stats['orders'] = $order_data['count'];
    $stats['revenue'] = $order_data['revenue'];
}

// Pending orders
$pending_result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
if($pending_result) {
    $stats['pending_orders'] = $pending_result->fetch_assoc()['count'];
}

// Today's orders
$today_result = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE DATE(created_at) = CURDATE()");
if($today_result) {
    $today_data = $today_result->fetch_assoc();
    $stats['today_orders'] = $today_data['count'];
    $stats['today_revenue'] = $today_data['revenue'];
}

// This month revenue
$month_result = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
if($month_result) {
    $stats['month_revenue'] = $month_result->fetch_assoc()['revenue'];
}

// Total users (non-admin)
$users_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE COALESCE(is_admin, 0) = 0");
if($users_result) {
    $stats['users'] = $users_result->fetch_assoc()['count'];
}

// Total admins (for super admin view)
$admins_count = 0;
if($is_super_admin) {
    $admins_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
    if($admins_result) {
        $admins_count = $admins_result->fetch_assoc()['count'];
    }
}

// Low stock products
$low_stock_result = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock < 10 AND status = 'active'");
if($low_stock_result) {
    $stats['low_stock'] = $low_stock_result->fetch_assoc()['count'];
}

// Get low stock products list
$low_stock_products = $conn->query("SELECT name, stock, id FROM products WHERE stock < 10 AND status = 'active' ORDER BY stock ASC LIMIT 5");

// Recent orders
$recent_orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");

// Recent products
$recent_products = $conn->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 5");

// Get top selling products
$top_products = $conn->query("
    SELECT p.name, COUNT(oi.id) as sales_count, SUM(oi.quantity) as total_sold
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    GROUP BY oi.product_id
    ORDER BY total_sold DESC
    LIMIT 5
");

// Get recent customers
$recent_customers = $conn->query("SELECT id, first_name, last_name, email, created_at FROM users WHERE COALESCE(is_admin, 0) = 0 ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Ray & Roses</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .admin-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-badge {
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            color: white;
        }

        .role-badge {
            background: #e8f5e9;
            color: #4caf50;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(196,92,74,0.15);
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

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .stat-info h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d2a24;
        }

        .stat-info p {
            color: #7b6b5c;
            font-size: 0.85rem;
        }

        .stat-trend {
            font-size: 0.7rem;
            margin-top: 0.3rem;
        }

        .trend-up {
            color: #4caf50;
        }

        .trend-down {
            color: #f44336;
        }

        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .chart-card h3 {
            margin-bottom: 1rem;
            font-size: 1.1rem;
            color: #2d2a24;
        }

        .chart-card h3 i {
            color: #c45c4a;
        }

        canvas {
            max-height: 250px;
        }

        /* Recent Sections */
        .recent-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 1.5rem;
        }

        .recent-orders, .recent-products, .low-stock-section, .top-products-section, .recent-customers {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0e0d4;
        }

        .section-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d2a24;
        }

        .section-header h2 i {
            color: #c45c4a;
        }

        .section-header a {
            color: #c45c4a;
            text-decoration: none;
            font-size: 0.8rem;
            transition: 0.3s;
        }

        .section-header a:hover {
            color: #a84a3a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #f0e0d4;
        }

        th {
            background: #fef6ef;
            font-weight: 600;
            color: #2d2a24;
        }

        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-active, .status-delivered {
            background: #e8f5e9;
            color: #4caf50;
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

        .status-inactive, .status-cancelled {
            background: #fee;
            color: #f44336;
        }

        .btn-small {
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.75rem;
            transition: 0.3s;
            display: inline-block;
        }

        .btn-small:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(196,92,74,0.3);
        }

        .stock-badge {
            font-weight: 600;
        }

        .stock-critical {
            color: #f44336;
        }

        .stock-low {
            color: #ff9800;
        }

        .customer-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
            margin-right: 0.5rem;
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
            .recent-section {
                grid-template-columns: 1fr;
            }
            .charts-section {
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
                <h1><i class="fas fa-chart-line"></i> Dashboard</h1>
                <div class="admin-info">
                    <span class="admin-badge"><i class="fas fa-shield-alt"></i> <?php echo $is_super_admin ? 'Super Admin' : 'Admin'; ?></span>
                    <span> Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
                    <?php if($is_super_admin): ?>
                    <span class="role-badge"><i class="fas fa-crown"></i> Full Access</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-seedling"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['products']; ?></h3>
                        <p>Total Products</p>
                        <div class="stat-trend"><i class="fas fa-chart-line"></i> In catalog</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['orders']; ?></h3>
                        <p>Total Orders</p>
                        <div class="stat-trend"><?php echo $stats['pending_orders']; ?> pending</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-tag"></i></div>
                    <div class="stat-info">
                        <h3>KES <?php echo number_format($stats['revenue'], 2); ?></h3>
                        <p>Total Revenue</p>
                        <div class="stat-trend trend-up"><i class="fas fa-arrow-up"></i> KES <?php echo number_format($stats['month_revenue'], 2); ?> this month</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['users']; ?></h3>
                        <p>Happy Customers</p>
                        <div class="stat-trend"><i class="fas fa-heart" style="color: #c45c4a;"></i> Growing community</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['low_stock']; ?></h3>
                        <p>Low Stock Items</p>
                        <div class="stat-trend trend-down"><i class="fas fa-arrow-down"></i> Needs attention</div>
                    </div>
                </div>
                <?php if($is_super_admin): ?>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $admins_count; ?></h3>
                        <p>Administrators</p>
                        <div class="stat-trend"><i class="fas fa-users-cog"></i> Manage team</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="charts-section">
                <div class="chart-card">
                    <h3><i class="fas fa-chart-line"></i> Sales Overview</h3>
                    <canvas id="salesChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3><i class="fas fa-chart-pie"></i> Order Status Distribution</h3>
                    <canvas id="orderChart"></canvas>
                </div>
            </div>
            
            <div class="recent-section">
                <div class="recent-orders">
                    <div class="section-header">
                        <h2><i class="fas fa-clock"></i> Recent Orders</h2>
                        <a href="orders.php">View All →</a>
                    </div>
                    <?php if($recent_orders && $recent_orders->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = $recent_orders->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $order['order_number']; ?></strong></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>KES <?php echo number_format($order['total_amount'], 2); ?></td>
                                <td><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                <td><a href="orders.php?view=<?php echo $order['id']; ?>" class="btn-small">View</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; padding: 2rem; color: #d4bca8;">No orders yet</p>
                    <?php endif; ?>
                </div>
                
                <div class="top-products-section">
                    <div class="section-header">
                        <h2><i class="fas fa-fire"></i> Top Selling Products</h2>
                        <a href="products.php">Manage →</a>
                    </div>
                    <?php if($top_products && $top_products->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($product = $top_products->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><strong><?php echo $product['total_sold']; ?></strong> units</td>
                                <td>KES <?php echo number_format($product['total_sold'] * 45, 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; padding: 2rem; color: #d4bca8;">No sales data yet</p>
                    <?php endif; ?>
                </div>
                
                <div class="low-stock-section">
                    <div class="section-header">
                        <h2><i class="fas fa-exclamation-triangle"></i> Low Stock Alert</h2>
                        <a href="products.php">Update Stock →</a>
                    </div>
                    <?php if($low_stock_products && $low_stock_products->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($product = $low_stock_products->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><span class="stock-badge <?php echo $product['stock'] <= 3 ? 'stock-critical' : 'stock-low'; ?>"><?php echo $product['stock']; ?> left</span></td>
                                <td><span class="status-badge status-pending">Critical</span></td>
                                <td><a href="product-edit.php?id=<?php echo $product['id']; ?>" class="btn-small">Restock</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; padding: 2rem; color: #4caf50;"><i class="fas fa-check-circle"></i> All products have sufficient stock</p>
                    <?php endif; ?>
                </div>
                
                <div class="recent-customers">
                    <div class="section-header">
                        <h2><i class="fas fa-user-plus"></i> New Customers</h2>
                        <a href="users.php">View All →</a>
                    </div>
                    <?php if($recent_customers && $recent_customers->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($customer = $recent_customers->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="customer-avatar"><?php echo strtoupper(substr($customer['first_name'], 0, 1)); ?></span>
                                    <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                                 </td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; padding: 2rem; color: #d4bca8;">No customers yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sales Chart with Ray & Roses colors
        const ctx1 = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Sales (KES)',
                    data: [<?php 
                        for($i = 1; $i <= 12; $i++) {
                            $month_sales = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE MONTH(created_at) = $i AND YEAR(created_at) = YEAR(CURDATE())");
                            $month_data = $month_sales->fetch_assoc();
                            echo $month_data['revenue'] . ($i < 12 ? ',' : '');
                        }
                    ?>],
                    borderColor: '#c45c4a',
                    backgroundColor: 'rgba(196, 92, 74, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Order Status Chart with Ray & Roses inspired colors
        const ctx2 = document.getElementById('orderChart').getContext('2d');
        
        <?php
        $status_counts = [];
        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        foreach($statuses as $status) {
            $count_result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = '$status'");
            $status_counts[$status] = $count_result->fetch_assoc()['count'];
        }
        ?>
        
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'],
                datasets: [{
                    data: [<?php echo $status_counts['pending']; ?>, <?php echo $status_counts['processing']; ?>, <?php echo $status_counts['shipped']; ?>, <?php echo $status_counts['delivered']; ?>, <?php echo $status_counts['cancelled']; ?>],
                    backgroundColor: ['#ff9800', '#2196f3', '#9c27b0', '#4caf50', '#f44336'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>