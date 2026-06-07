<div class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-crown"></i> Admin Panel</h2>
        <p>Petal & Stem</p>
    </div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li><a href="products.php"><i class="fas fa-box"></i> <span>Products</span></a></li>
        <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> <span>Orders</span></a></li>
        <li><a href="users.php"><i class="fas fa-users"></i> <span>Customers</span></a></li>
        
        <?php 
        // Only show Manage Admins to Super Admin
        $user_role = $_SESSION['admin_role'] ?? 'admin';
        if($user_role == 'super_admin'): 
        ?>
        <li><a href="manage-admins.php"><i class="fas fa-users-cog"></i> <span>Manage Admins</span></a></li>
        <?php endif; ?>
        
        <li><a href="profile.php"><i class="fas fa-user-circle"></i> <span>My Profile</span></a></li>
        <li><a href="../index.php" target="_blank"><i class="fas fa-store"></i> <span>View Store</span></a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>