<?php
session_start();
require_once 'config/database.php';
require_once 'config/check_auth.php'; // Ensure user is logged in

// Get user info
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Get user data from database
$user_sql = "SELECT id, first_name, last_name, email, phone, created_at FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Get wishlist count
$wishlist_count = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;

// Handle profile update
$profile_message = '';
$profile_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $profile_error = "Please fill in all required fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profile_error = "Please enter a valid email address";
    } else {
        $update_sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            $_SESSION['user_email'] = $email;
            $profile_message = "Profile updated successfully!";
            // Refresh user data
            $user_data['first_name'] = $first_name;
            $user_data['last_name'] = $last_name;
            $user_data['email'] = $email;
            $user_data['phone'] = $phone;
        } else {
            $profile_error = "Failed to update profile. Please try again.";
        }
        $update_stmt->close();
    }
}

// Handle password change
$password_message = '';
$password_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Verify current password
    $pass_sql = "SELECT password FROM users WHERE id = ?";
    $pass_stmt = $conn->prepare($pass_sql);
    $pass_stmt->bind_param("i", $user_id);
    $pass_stmt->execute();
    $pass_result = $pass_stmt->get_result();
    $user_pass = $pass_result->fetch_assoc();
    
    if (!password_verify($current_password, $user_pass['password'])) {
        $password_error = "Current password is incorrect";
    } elseif (strlen($new_password) < 6) {
        $password_error = "New password must be at least 6 characters";
    } elseif ($new_password !== $confirm_password) {
        $password_error = "New passwords do not match";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_pass_sql = "UPDATE users SET password = ? WHERE id = ?";
        $update_pass_stmt = $conn->prepare($update_pass_sql);
        $update_pass_stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($update_pass_stmt->execute()) {
            $password_message = "Password changed successfully!";
        } else {
            $password_error = "Failed to change password. Please try again.";
        }
        $update_pass_stmt->close();
    }
    $pass_stmt->close();
}

// Fetch real orders from database
$orders_sql = "SELECT id, order_number, created_at, total_amount, status FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$orders_stmt = $conn->prepare($orders_sql);
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
$orders = [];

if($orders_result && $orders_result->num_rows > 0) {
    while($row = $orders_result->fetch_assoc()) {
        // Get item count for this order
        $item_count_sql = "SELECT COUNT(*) as count FROM order_items WHERE order_id = ?";
        $item_count_stmt = $conn->prepare($item_count_sql);
        $item_count_stmt->bind_param("i", $row['id']);
        $item_count_stmt->execute();
        $item_count_result = $item_count_stmt->get_result();
        $item_count = $item_count_result->fetch_assoc();
        
        $orders[] = [
            'id' => $row['order_number'],
            'order_id' => $row['id'],
            'date' => $row['created_at'],
            'items' => $item_count['count'],
            'total' => $row['total_amount'],
            'status' => ucfirst($row['status'])
        ];
        $item_count_stmt->close();
    }
}
$orders_stmt->close();

// Include header
include 'header.php';
?>

<style>
    /* Account Page Styles */
    .account-container {
        max-width: 1400px;
        margin: 2rem auto;
        padding: 0 5%;
    }

    .account-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .account-header h1 {
        font-family: 'Playfair Display', serif;
        font-size: 2.5rem;
        color: #3d2a1f;
        margin-bottom: 0.5rem;
    }

    .account-header p {
        color: #7b6b5c;
    }

    .account-grid {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 2rem;
    }

    /* Sidebar */
    .account-sidebar {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        height: fit-content;
        position: sticky;
        top: 100px;
    }

    .user-avatar {
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .avatar-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #c45c4a, #e8876e);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }

    .avatar-icon i {
        font-size: 3rem;
        color: white;
    }

    .user-avatar h3 {
        color: #3d2a1f;
        margin-bottom: 0.3rem;
    }

    .user-avatar p {
        color: #7b6b5c;
        font-size: 0.9rem;
    }

    .sidebar-menu {
        list-style: none;
        margin-top: 1rem;
    }

    .sidebar-menu li {
        margin-bottom: 0.5rem;
    }

    .sidebar-menu a {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        padding: 0.8rem;
        color: #5a3f2c;
        text-decoration: none;
        border-radius: 12px;
        transition: 0.3s;
    }

    .sidebar-menu a:hover,
    .sidebar-menu a.active {
        background: #fef6ef;
        color: #c45c4a;
    }

    .sidebar-menu a i {
        width: 24px;
    }

    .wishlist-badge {
        background: #c45c4a;
        color: white;
        border-radius: 50%;
        padding: 0.2rem 0.5rem;
        font-size: 0.7rem;
        margin-left: auto;
    }

    /* Main Content */
    .account-main {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }

    .section-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        color: #3d2a1f;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #f0e0d4;
    }

    /* Profile Form */
    .profile-form {
        max-width: 600px;
    }

    .form-group {
        margin-bottom: 1.2rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #3d2a1f;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .form-group input {
        width: 100%;
        padding: 0.8rem 1rem;
        border: 2px solid #f0e0d4;
        border-radius: 12px;
        font-size: 0.95rem;
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

    .save-btn {
        background: #c45c4a;
        color: white;
        border: none;
        padding: 0.8rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
    }

    .save-btn:hover {
        background: #a84a3a;
        transform: translateY(-2px);
    }

    /* Orders Table */
    .orders-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .track-order-top-btn {
        background: linear-gradient(135deg, #c45c4a, #e8876e);
        color: white;
        padding: 10px 20px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: 0.3s;
    }

    .track-order-top-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(196,92,74,0.3);
        color: white;
    }

    .orders-table {
        width: 100%;
        overflow-x: auto;
    }

    .orders-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .orders-table th {
        background: #fef6ef;
        padding: 1rem;
        text-align: left;
        color: #3d2a1f;
        font-weight: 600;
    }

    .orders-table td {
        padding: 1rem;
        border-bottom: 1px solid #f0e0d4;
    }

    .status-badge {
        display: inline-block;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
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

    .track-order-btn {
        background: #c45c4a;
        color: white;
        padding: 5px 12px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: 0.3s;
    }

    .track-order-btn:hover {
        background: #a84a3a;
        transform: translateY(-2px);
        color: white;
    }

    .reorder-btn {
        background: transparent;
        border: 1px solid #c45c4a;
        padding: 5px 12px;
        border-radius: 8px;
        color: #c45c4a;
        cursor: pointer;
        transition: 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .reorder-btn:hover {
        background: #c45c4a;
        color: white;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    /* Wishlist Preview */
    .wishlist-preview {
        text-align: center;
        padding: 2rem;
    }

    .wishlist-stats {
        margin-bottom: 1.5rem;
    }

    .wishlist-stats .count {
        font-size: 2.5rem;
        font-weight: 700;
        color: #c45c4a;
    }

    .view-wishlist-btn {
        display: inline-block;
        background: #c45c4a;
        color: white;
        padding: 0.8rem 2rem;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        transition: 0.3s;
    }

    .view-wishlist-btn:hover {
        background: #a84a3a;
        transform: translateY(-2px);
    }

    .empty-wishlist-preview {
        text-align: center;
        color: #7b6b5c;
        padding: 2rem;
    }

    /* Messages */
    .success-message {
        background: #e8f5e9;
        color: #2e7d32;
        padding: 0.8rem;
        border-radius: 10px;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }

    .error-message {
        background: #fee;
        color: #c45c4a;
        padding: 0.8rem;
        border-radius: 10px;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }

    /* Quick Track Modal */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 9998;
    }
    
    .modal-content {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        border-radius: 20px;
        width: 90%;
        max-width: 400px;
        z-index: 9999;
        overflow: hidden;
    }
    
    .modal-header {
        background: linear-gradient(135deg, #c45c4a, #e8876e);
        color: white;
        padding: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
    }
    
    .modal-body {
        padding: 1.5rem;
    }
    
    .modal-body input {
        width: 100%;
        padding: 0.8rem;
        margin-bottom: 1rem;
        border: 1px solid #e0d4c8;
        border-radius: 8px;
    }
    
    .track-submit {
        width: 100%;
        background: #c45c4a;
        color: white;
        border: none;
        padding: 0.8rem;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .account-grid {
            grid-template-columns: 1fr;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .account-sidebar {
            position: static;
        }
        
        .orders-header {
            flex-direction: column;
            align-items: stretch;
        }
        
        .track-order-top-btn {
            text-align: center;
            justify-content: center;
        }
        
        .action-buttons {
            flex-direction: column;
        }
    }
</style>

<div class="account-container">
    <div class="account-header">
        <h1><i class="fas fa-user-circle"></i> My Account</h1>
        <p>Manage your profile, orders, and preferences</p>
    </div>

    <div class="account-grid">
        <!-- Sidebar -->
        <div class="account-sidebar">
            <div class="user-avatar">
                <div class="avatar-icon">
                    <i class="fas fa-user"></i>
                </div>
                <h3><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h3>
                <p><?php echo htmlspecialchars($user_data['email']); ?></p>
                <p><small>Member since <?php echo date('M Y', strtotime($user_data['created_at'])); ?></small></p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="#" class="active" data-tab="profile"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="#" data-tab="orders"><i class="fas fa-shopping-bag"></i> Orders</a></li>
                <li><a href="#" data-tab="security"><i class="fas fa-lock"></i> Security</a></li>
                <li>
                    <a href="#" data-tab="wishlist">
                        <i class="fas fa-heart"></i> Wishlist
                        <?php if($wishlist_count > 0): ?>
                        <span class="wishlist-badge"><?php echo $wishlist_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #f0e0d4;">
                    <a href="#" onclick="showQuickTrack(event)">
                        <i class="fas fa-map-marker-alt"></i> Quick Track Order
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="account-main">
            <!-- Profile Tab -->
            <div id="profile-tab" class="tab-content">
                <h2 class="section-title">Profile Information</h2>
                
                <?php if($profile_message): ?>
                <div class="success-message"><?php echo htmlspecialchars($profile_message); ?></div>
                <?php endif; ?>
                
                <?php if($profile_error): ?>
                <div class="error-message"><?php echo htmlspecialchars($profile_error); ?></div>
                <?php endif; ?>
                
                <form method="POST" class="profile-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($user_data['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($user_data['last_name']); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                    </div>
                    <button type="submit" name="update_profile" class="save-btn">Save Changes</button>
                </form>
            </div>

            <!-- Orders Tab -->
            <div id="orders-tab" class="tab-content" style="display: none;">
                <div class="orders-header">
                    <h2 class="section-title" style="margin-bottom: 0;">Order History</h2>
                    
                    <!-- Track Order Button at top of orders section -->
                    <a href="track-order.php" class="track-order-top-btn">
                        <i class="fas fa-map-marker-alt"></i> Track Order by Number
                    </a>
                </div>
                
                <?php if(empty($orders)): ?>
                <p style="text-align: center; color: #7b6b5c; padding: 2rem;">
                    <i class="fas fa-box-open" style="font-size: 3rem; display: block; margin-bottom: 1rem; color: #e2cbb8;"></i>
                    No orders yet. Start shopping!
                </p>
                <?php else: ?>
                <div class="orders-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $order): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($order['id']); ?></strong></td>
                                <td><?php echo date('M d, Y', strtotime($order['date'])); ?></td>
                                <td><?php echo $order['items']; ?> item(s)</td>
                                <td>KES <?php echo number_format($order['total'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <!-- Track This Order Button -->
                                        <a href="track-order.php?order=<?php echo urlencode($order['id']); ?>&email=<?php echo urlencode($user_email); ?>" class="track-order-btn">
                                            <i class="fas fa-map-marker-alt"></i> Track
                                        </a>
                                        <button class="reorder-btn" onclick="reorder('<?php echo $order['id']; ?>')">
                                            <i class="fas fa-redo"></i> Reorder
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Security Tab -->
            <div id="security-tab" class="tab-content" style="display: none;">
                <h2 class="section-title">Change Password</h2>
                
                <?php if($password_message): ?>
                <div class="success-message"><?php echo htmlspecialchars($password_message); ?></div>
                <?php endif; ?>
                
                <?php if($password_error): ?>
                <div class="error-message"><?php echo htmlspecialchars($password_error); ?></div>
                <?php endif; ?>
                
                <form method="POST" class="profile-form">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password (min 6 characters)</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="save-btn">Update Password</button>
                </form>
            </div>

            <!-- Wishlist Tab -->
            <div id="wishlist-tab" class="tab-content" style="display: none;">
                <h2 class="section-title"><i class="fas fa-heart"></i> My Wishlist</h2>
                
                <?php if($wishlist_count > 0): ?>
                <div class="wishlist-preview">
                    <div class="wishlist-stats">
                        <div class="count"><?php echo $wishlist_count; ?></div>
                        <p>item(s) saved in your wishlist</p>
                    </div>
                    <a href="wishlist.php" class="view-wishlist-btn">
                        View Full Wishlist →
                    </a>
                </div>
                <?php else: ?>
                <div class="empty-wishlist-preview">
                    <i class="fas fa-heart" style="font-size: 3rem; color: #e2cbb8; margin-bottom: 1rem; display: block;"></i>
                    <p>Your wishlist is empty.</p>
                    <p style="margin-top: 0.5rem;">
                        <a href="shop.php" style="color: #c45c4a;">Browse our collection and save your favorite flowers →</a>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Tab switching
document.querySelectorAll('.sidebar-menu a[data-tab]').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        
        // Remove active class from all links
        document.querySelectorAll('.sidebar-menu a').forEach(a => a.classList.remove('active'));
        link.classList.add('active');
        
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
        
        // Show selected tab
        const tabName = link.getAttribute('data-tab');
        document.getElementById(`${tabName}-tab`).style.display = 'block';
    });
});

// Quick Track Order Function
function showQuickTrack(e) {
    e.preventDefault();
    
    // Create modal
    let modal = document.getElementById('quickTrackModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'quickTrackModal';
        modal.innerHTML = `
            <div class="modal-overlay" onclick="closeQuickTrack()"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-map-marker-alt"></i> Track Your Order</h3>
                    <button onclick="closeQuickTrack()" class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Enter your order number to track status</p>
                    <form id="quickTrackForm">
                        <input type="text" id="quickOrderNumber" placeholder="Order Number" required>
                        <input type="email" id="quickEmail" placeholder="Email Address" value="<?php echo $user_email; ?>" required>
                        <button type="submit" class="track-submit">Track Order</button>
                    </form>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        document.getElementById('quickTrackForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const orderNum = document.getElementById('quickOrderNumber').value;
            const email = document.getElementById('quickEmail').value;
            if(orderNum && email) {
                window.location.href = `track-order.php?order=${encodeURIComponent(orderNum)}&email=${encodeURIComponent(email)}`;
            }
        });
    }
    
    modal.style.display = 'block';
}

function closeQuickTrack() {
    const modal = document.getElementById('quickTrackModal');
    if(modal) modal.style.display = 'none';
}

// Reorder function - Add items from previous order to cart
function reorder(orderNumber) {
    showToast('Adding items to cart...');
    
    fetch('reorder.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'order_number=' + encodeURIComponent(orderNumber)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.items.length > 0) {
            let cart = JSON.parse(localStorage.getItem('flowerCart')) || [];
            
            data.items.forEach(item => {
                const existing = cart.find(cartItem => cartItem.id == item.id);
                if (existing) {
                    existing.quantity += item.quantity;
                } else {
                    cart.push({
                        id: item.id,
                        name: item.name,
                        price: item.price,
                        quantity: item.quantity,
                        image: item.image || 'https://via.placeholder.com/80'
                    });
                }
            });
            
            localStorage.setItem('flowerCart', JSON.stringify(cart));
            updateCartCount();
            showToast('Items added to cart! Redirecting...');
            
            setTimeout(() => {
                window.location.href = 'cart.php';
            }, 1500);
        } else {
            showToast('Unable to reorder. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error processing reorder.');
    });
}

// Show toast notification
function showToast(message) {
    let toast = document.getElementById('toastMsg');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toastMsg';
        toast.style.cssText = `
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            background: #2d2a24;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            z-index: 3000;
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
        `;
        document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.style.opacity = '1';
    setTimeout(() => {
        toast.style.opacity = '0';
    }, 2000);
}

// Update cart count from localStorage
function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('flowerCart')) || [];
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(el => {
        if (el) el.textContent = totalItems;
    });
}

updateCartCount();
</script>

<?php include 'footer.php'; ?>