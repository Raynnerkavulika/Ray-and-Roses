<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include __DIR__ . '/config/database.php';

// Include header
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - Ray & Roses</title>
    <style>
        .track-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 5%;
        }
        
        .track-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .track-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #2d2a24;
            margin-bottom: 1rem;
        }
        
        .track-header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .search-box {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .search-form {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .search-input-group {
            flex: 1;
            min-width: 200px;
        }
        
        .search-input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2d2a24;
        }
        
        .search-input-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #e0d4c8;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
        }
        
        .search-input-group input:focus {
            outline: none;
            border-color: #c45c4a;
        }
        
        .search-btn {
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            align-self: flex-end;
        }
        
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(196,92,74,0.3);
        }
        
        .order-details {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            display: none;
        }
        
        .order-details.show {
            display: block;
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
        
        .order-header {
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .order-status {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .status-pending { background: #ffc107; color: #856404; }
        .status-processing { background: #17a2b8; color: white; }
        .status-shipped { background: #007bff; color: white; }
        .status-delivered { background: #28a745; color: white; }
        .status-cancelled { background: #dc3545; color: white; }
        
        .order-info {
            padding: 1.5rem;
            border-bottom: 1px solid #f0e0d4;
        }
        
        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        
        .info-item {
            margin-bottom: 0.5rem;
        }
        
        .info-label {
            font-weight: 600;
            color: #2d2a24;
            margin-bottom: 0.3rem;
        }
        
        .info-value {
            color: #666;
        }
        
        .timeline {
            padding: 2rem;
            background: #fef6ef;
        }
        
        .timeline-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d2a24;
            margin-bottom: 1.5rem;
        }
        
        .timeline-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            flex-wrap: wrap;
        }
        
        .timeline-steps::before {
            content: '';
            position: absolute;
            top: 30px;
            left: 0;
            right: 0;
            height: 3px;
            background: #e0d4c8;
            z-index: 1;
        }
        
        .timeline-step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 2;
            min-width: 120px;
            margin-bottom: 1rem;
        }
        
        .step-icon {
            width: 60px;
            height: 60px;
            background: white;
            border: 3px solid #e0d4c8;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            position: relative;
            background: white;
        }
        
        .step-icon i {
            font-size: 1.5rem;
            color: #999;
        }
        
        .timeline-step.completed .step-icon {
            border-color: #28a745;
            background: #28a745;
        }
        
        .timeline-step.completed .step-icon i {
            color: white;
        }
        
        .timeline-step.active .step-icon {
            border-color: #c45c4a;
            background: #c45c4a;
        }
        
        .timeline-step.active .step-icon i {
            color: white;
        }
        
        .step-label {
            font-weight: 600;
            color: #2d2a24;
            margin-bottom: 0.3rem;
        }
        
        .step-date {
            font-size: 0.8rem;
            color: #999;
        }
        
        .products-section {
            padding: 1.5rem;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .products-table th,
        .products-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f0e0d4;
        }
        
        .products-table th {
            background: #f9f3ef;
            font-weight: 600;
            color: #2d2a24;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .order-summary {
            padding: 1.5rem;
            background: #fef6ef;
            text-align: right;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: none;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }
        
        .recent-orders {
            margin-top: 2rem;
            display: none;
        }
        
        .recent-orders.show {
            display: block;
        }
        
        .recent-orders h3 {
            margin-bottom: 1rem;
            color: #2d2a24;
        }
        
        .orders-list {
            display: grid;
            gap: 1rem;
        }
        
        .order-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            cursor: pointer;
            transition: 0.3s;
            border: 1px solid #e0d4c8;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .order-card:hover {
            border-color: #c45c4a;
            transform: translateX(5px);
        }
        
        @media (max-width: 768px) {
            .track-header h1 {
                font-size: 1.8rem;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-btn {
                align-self: stretch;
            }
            
            .timeline-steps {
                flex-direction: column;
                gap: 1rem;
            }
            
            .timeline-steps::before {
                display: none;
            }
            
            .timeline-step {
                display: flex;
                align-items: center;
                gap: 1rem;
                text-align: left;
            }
            
            .step-icon {
                margin: 0;
            }
            
            .products-table {
                font-size: 0.85rem;
            }
            
            .products-table th,
            .products-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>

<div class="track-container">
    <div class="track-header">
        <h1>📦 Track Your Order</h1>
        <p>Enter your order number and email to track your delivery status</p>
    </div>
    
    <div class="search-box">
        <form id="trackForm" class="search-form" method="POST" action="">
            <div class="search-input-group">
                <label>Order Number *</label>
                <input type="text" id="orderNumber" name="order_number" placeholder="e.g., RR-12345" required>
            </div>
            <div class="search-input-group">
                <label>Email Address *</label>
                <input type="email" id="email" name="email" placeholder="your@email.com" required>
            </div>
            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i> Track Order
            </button>
        </form>
    </div>
    
    <div id="alertMessage"></div>
    
    <?php
    // Check if database connection exists
    if (!isset($conn) || $conn === null) {
        echo '<div class="alert error">Database connection error. Please try again later.</div>';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_number']) && isset($_POST['email'])) {
        $order_number = mysqli_real_escape_string($conn, $_POST['order_number']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        // Query to get order details with first_name and last_name
        $query = "SELECT o.*, u.email, u.first_name, u.last_name,
                  CONCAT(u.first_name, ' ', u.last_name) as full_name 
                  FROM orders o 
                  JOIN users u ON o.user_id = u.id 
                  WHERE o.order_number = '$order_number' AND u.email = '$email'";
        
        $result = mysqli_query($conn, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $order = mysqli_fetch_assoc($result);
            
            // Get order items
            $items_query = "SELECT oi.*, p.name as product_name, p.image 
                           FROM order_items oi 
                           JOIN products p ON oi.product_id = p.id 
                           WHERE oi.order_id = '{$order['id']}'";
            $items_result = mysqli_query($conn, $items_query);
            $order_items = [];
            if ($items_result) {
                while ($item = mysqli_fetch_assoc($items_result)) {
                    $order_items[] = $item;
                }
            }
            
            // Get tracking updates
            $tracking_query = "SELECT * FROM order_tracking 
                              WHERE order_id = '{$order['id']}' 
                              ORDER BY created_at ASC";
            $tracking_result = mysqli_query($conn, $tracking_query);
            $tracking_updates = [];
            if ($tracking_result) {
                while ($track = mysqli_fetch_assoc($tracking_result)) {
                    $tracking_updates[$track['status']] = $track;
                }
            }
            ?>
            
            <div id="orderDetails" class="order-details show">
                <div class="order-header">
                    <div>
                        <strong>Order #: </strong><?php echo htmlspecialchars($order['order_number']); ?>
                    </div>
                    <div>
                        <span class="order-status status-<?php echo $order['status']; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="order-info">
                    <div class="order-info-grid">
                        <div class="info-item">
                            <div class="info-label">Customer Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Order Date</div>
                            <div class="info-value"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Delivery Address</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['delivery_address'] ?? 'Not specified'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Payment Method</div>
                            <div class="info-value"><?php echo ucfirst($order['payment_method'] ?? 'Not specified'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Tracking Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['tracking_number'] ?? 'Not assigned yet'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['email']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="timeline">
                    <div class="timeline-title">Order Progress</div>
                    <div class="timeline-steps">
                        <?php
                        $timeline_steps = [
                            'pending' => ['icon' => 'fa-shopping-cart', 'label' => 'Order Placed'],
                            'confirmed' => ['icon' => 'fa-check-circle', 'label' => 'Order Confirmed'],
                            'processing' => ['icon' => 'fa-seedling', 'label' => 'Preparing'],
                            'shipped' => ['icon' => 'fa-truck', 'label' => 'Shipped'],
                            'delivered' => ['icon' => 'fa-home', 'label' => 'Delivered']
                        ];
                        
                        $current_status = $order['status'];
                        $status_order = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
                        $current_index = array_search($current_status, $status_order);
                        
                        foreach ($timeline_steps as $key => $step):
                            $step_index = array_search($key, $status_order);
                            $is_completed = $step_index <= $current_index;
                            $is_active = $step_index == $current_index;
                            ?>
                            <div class="timeline-step <?php echo $is_completed ? 'completed' : ($is_active ? 'active' : ''); ?>">
                                <div class="step-icon">
                                    <i class="fas <?php echo $step['icon']; ?>"></i>
                                </div>
                                <div class="step-label"><?php echo $step['label']; ?></div>
                                <div class="step-date">
                                    <?php 
                                    if (isset($tracking_updates[$key]) && isset($tracking_updates[$key]['created_at'])) {
                                        echo date('M j, g:i A', strtotime($tracking_updates[$key]['created_at']));
                                    } else {
                                        echo 'Pending';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="products-section">
                    <?php if(count($order_items) > 0): ?>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtotal = 0;
                            foreach ($order_items as $item): 
                                $item_total = $item['quantity'] * $item['price'];
                                $subtotal += $item_total;
                            ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <img src="<?php echo htmlspecialchars($item['image'] ?: 'images/placeholder.jpg'); ?>" class="product-image" onerror="this.src='https://via.placeholder.com/60?text=Flower'">
                                        <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>KES <?php echo number_format($item['price']); ?></td>
                                <td>KES <?php echo number_format($item_total); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <p>No products found for this order.</p>
                    <?php endif; ?>
                </div>
                
                <div class="order-summary">
                    <strong>Total Amount: </strong> 
                    <span style="font-size: 1.3rem; color: #c45c4a;">
                        KES <?php echo number_format($order['total_amount']); ?>
                    </span>
                </div>
            </div>
            
        <?php } else { ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i> Order not found. Please check your order number and email.
            </div>
        <?php } 
    } ?>
    
    <!-- Recent Orders for Logged In Users -->
    <?php if(isset($_SESSION['user_id']) && isset($conn) && $conn !== null): 
        // Fetch recent orders for logged in user
        $user_id = $_SESSION['user_id'];
        $recent_query = "SELECT o.*, u.email, CONCAT(u.first_name, ' ', u.last_name) as customer_name 
                        FROM orders o 
                        JOIN users u ON o.user_id = u.id 
                        WHERE o.user_id = '$user_id' 
                        ORDER BY o.created_at DESC 
                        LIMIT 5";
        $recent_result = mysqli_query($conn, $recent_query);
        
        if($recent_result && mysqli_num_rows($recent_result) > 0):
    ?>
    <div id="recentOrders" class="recent-orders show">
        <h3><i class="fas fa-history"></i> Your Recent Orders</h3>
        <div class="orders-list">
            <?php while($recent_order = mysqli_fetch_assoc($recent_result)): ?>
                <div class="order-card" onclick="fillAndTrack('<?php echo htmlspecialchars($recent_order['order_number']); ?>', '<?php echo htmlspecialchars($recent_order['email']); ?>')">
                    <div>
                        <strong><?php echo htmlspecialchars($recent_order['order_number']); ?></strong><br>
                        <small><?php echo date('M j, Y', strtotime($recent_order['created_at'])); ?></small>
                    </div>
                    <div>
                        <span class="order-status status-<?php echo $recent_order['status']; ?>" style="font-size: 0.8rem;">
                            <?php echo ucfirst($recent_order['status']); ?>
                        </span>
                    </div>
                    <div>
                        <strong>KES <?php echo number_format($recent_order['total_amount']); ?></strong>
                    </div>
                    <i class="fas fa-chevron-right" style="color: #c45c4a;"></i>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; endif; ?>
</div>

<script>
    function fillAndTrack(orderNumber, email) {
        document.getElementById('orderNumber').value = orderNumber;
        document.getElementById('email').value = email;
        document.getElementById('trackForm').submit();
    }
    
    // Auto-hide alert after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if(alert) {
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }
    }, 100);
    
    // Pre-fill from URL parameters if any
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.has('order')) {
        document.getElementById('orderNumber').value = urlParams.get('order');
        if(urlParams.has('email')) {
            document.getElementById('email').value = urlParams.get('email');
        }
    }
</script>

<?php include 'footer.php'; ?>

</body>
</html>