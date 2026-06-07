<?php
session_start();
require_once '../config/database.php';

// Add this to the top of ALL admin pages (dashboard.php, products.php, orders.php, users.php, etc.)
if(!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}
// Handle product deletion
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_sql = "DELETE FROM products WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $delete_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    header("Location: products.php?msg=deleted");
    exit();
}

// Handle bulk delete
if(isset($_POST['bulk_delete']) && isset($_POST['selected_products'])) {
    $selected_ids = $_POST['selected_products'];
    $ids_string = implode(',', array_map('intval', $selected_ids));
    $conn->query("DELETE FROM products WHERE id IN ($ids_string)");
    header("Location: products.php?msg=bulk_deleted");
    exit();
}

// Handle status toggle
if(isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $toggle_id = $_GET['toggle'];
    $toggle_sql = "UPDATE products SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?";
    $toggle_stmt = $conn->prepare($toggle_sql);
    $toggle_stmt->bind_param("i", $toggle_id);
    $toggle_stmt->execute();
    $toggle_stmt->close();
    header("Location: products.php");
    exit();
}

// Handle bulk status update
if(isset($_POST['bulk_status']) && isset($_POST['selected_products'])) {
    $selected_ids = $_POST['selected_products'];
    $new_status = $_POST['bulk_status_action'];
    $ids_string = implode(',', array_map('intval', $selected_ids));
    $conn->query("UPDATE products SET status = '$new_status' WHERE id IN ($ids_string)");
    header("Location: products.php?msg=bulk_updated");
    exit();
}

// Search and filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$stock_filter = isset($_GET['stock']) ? $_GET['stock'] : '';

// Build query
$query = "SELECT * FROM products WHERE 1=1";
if($search) {
    $query .= " AND (name LIKE '%$search%' OR description LIKE '%$search%' OR sku LIKE '%$search%')";
}
if($category_filter) {
    $query .= " AND category = '$category_filter'";
}
if($stock_filter == 'low') {
    $query .= " AND stock < 10 AND stock > 0";
} elseif($stock_filter == 'out') {
    $query .= " AND stock = 0";
} elseif($stock_filter == 'high') {
    $query .= " AND stock >= 10";
}

$query .= " ORDER BY created_at DESC";
$products = $conn->query($query);

// Get category counts for filter
$categories = $conn->query("SELECT category, COUNT(*) as count FROM products GROUP BY category");

// Get total stats
$total_products = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
$active_products = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 'active'")->fetch_assoc()['total'];
$inactive_products = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 'inactive'")->fetch_assoc()['total'];
$low_stock_count = $conn->query("SELECT COUNT(*) as total FROM products WHERE stock < 10 AND stock > 0")->fetch_assoc()['total'];
$out_stock_count = $conn->query("SELECT COUNT(*) as total FROM products WHERE stock = 0")->fetch_assoc()['total'];
$on_discount_count = $conn->query("SELECT COUNT(*) as total FROM products WHERE original_price IS NOT NULL AND original_price > price AND price > 0")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products | Ray & Roses Admin</title>
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

        .btn-primary {
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: 0.3s;
            font-weight: 600;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(196,92,74,0.4);
        }

        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-mini-card {
            background: white;
            padding: 1rem;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }

        .stat-mini-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(196,92,74,0.1);
        }

        .stat-mini-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .stat-mini-info h4 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2d2a24;
        }

        .stat-mini-info p {
            font-size: 0.7rem;
            color: #7b6b5c;
        }

        /* Filter Bar */
        .filter-bar {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: space-between;
            align-items: center;
        }

        .filter-group {
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .filter-input {
            padding: 0.5rem 1rem;
            border: 2px solid #f0e0d4;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            transition: 0.3s;
        }

        .filter-input:focus {
            outline: none;
            border-color: #c45c4a;
            box-shadow: 0 0 0 3px rgba(196,92,74,0.1);
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 2px solid #f0e0d4;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
        }

        .filter-select:focus {
            outline: none;
            border-color: #c45c4a;
        }

        .btn-secondary {
            background: #7b6b5c;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-secondary:hover {
            background: #5a3f2c;
            transform: translateY(-2px);
        }

        /* Bulk Actions */
        .bulk-actions {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            display: none;
        }

        .bulk-actions.show {
            display: flex;
            flex-wrap: wrap;
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

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 12px;
        }

        .product-placeholder {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #fef6ef, #f0e0d4);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #c45c4a;
        }

        .category-badge {
            background: #fef6ef;
            color: #c45c4a;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
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

        .stock-badge {
            font-weight: 600;
        }

        .stock-high {
            color: #4caf50;
        }

        .stock-low {
            color: #ff9800;
        }

        .stock-out {
            color: #f44336;
        }

        .discount-badge {
            background: #c45c4a;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
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
        }

        .btn-icon:hover {
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            color: white;
            transform: translateY(-2px);
        }

        .checkbox-select {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #c45c4a;
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
            .filter-bar {
                flex-direction: column;
            }
            .bulk-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-seedling"></i> Products Management</h1>
                <a href="product-add.php" class="btn-primary"><i class="fas fa-plus"></i> Add New Product</a>
            </div>
            
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i> 
                    <?php 
                        if($_GET['msg'] == 'added') echo "Product added successfully!";
                        elseif($_GET['msg'] == 'updated') echo "Product updated successfully!";
                        elseif($_GET['msg'] == 'deleted') echo "Product deleted successfully!";
                        elseif($_GET['msg'] == 'bulk_deleted') echo "Products deleted successfully!";
                        elseif($_GET['msg'] == 'bulk_updated') echo "Products status updated successfully!";
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- Stats Row -->
            <div class="stats-row">
                <div class="stat-mini-card">
                    <div class="stat-mini-icon"><i class="fas fa-seedling"></i></div>
                    <div class="stat-mini-info">
                        <h4><?php echo $total_products; ?></h4>
                        <p>Total Products</p>
                    </div>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-mini-info">
                        <h4><?php echo $active_products; ?></h4>
                        <p>Active</p>
                    </div>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-icon"><i class="fas fa-ban"></i></div>
                    <div class="stat-mini-info">
                        <h4><?php echo $inactive_products; ?></h4>
                        <p>Inactive</p>
                    </div>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-mini-info">
                        <h4><?php echo $low_stock_count; ?></h4>
                        <p>Low Stock</p>
                    </div>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-icon"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-mini-info">
                        <h4><?php echo $out_stock_count; ?></h4>
                        <p>Out of Stock</p>
                    </div>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-icon"><i class="fas fa-tag"></i></div>
                    <div class="stat-mini-info">
                        <h4><?php echo $on_discount_count; ?></h4>
                        <p>On Discount</p>
                    </div>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <form method="GET" action="" style="display: flex; gap: 0.8rem; flex-wrap: wrap;">
                    <input type="text" name="search" class="filter-input" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="category" class="filter-select">
                        <option value="">All Categories</option>
                        <?php while($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['category']; ?>" <?php echo $category_filter == $cat['category'] ? 'selected' : ''; ?>>
                            <?php echo ucfirst($cat['category']); ?> (<?php echo $cat['count']; ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <select name="stock" class="filter-select">
                        <option value="">All Stock</option>
                        <option value="high" <?php echo $stock_filter == 'high' ? 'selected' : ''; ?>>High Stock (10+)</option>
                        <option value="low" <?php echo $stock_filter == 'low' ? 'selected' : ''; ?>>Low Stock (&lt;10)</option>
                        <option value="out" <?php echo $stock_filter == 'out' ? 'selected' : ''; ?>>Out of Stock (0)</option>
                    </select>
                    <button type="submit" class="btn-primary" style="padding: 0.5rem 1rem;"><i class="fas fa-search"></i> Filter</button>
                    <a href="products.php" class="btn-secondary">Clear</a>
                </form>
            </div>
            
            <!-- Bulk Actions -->
            <form method="POST" id="bulkForm">
                <div class="bulk-actions" id="bulkActions">
                    <span><i class="fas fa-check-square"></i> <span id="selectedCount">0</span> items selected</span>
                    <select name="bulk_status_action" class="filter-select">
                        <option value="active">Set Active</option>
                        <option value="inactive">Set Inactive</option>
                    </select>
                    <button type="submit" name="bulk_status" class="btn-primary">Apply Status</button>
                    <button type="submit" name="bulk_delete" class="btn-secondary" onclick="return confirm('Delete selected products?')">Delete Selected</button>
                </div>
            
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" class="checkbox-select"></th>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Discount</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($products && $products->num_rows > 0): ?>
                                <?php while($product = $products->fetch_assoc()): 
                                    $has_discount = ($product['original_price'] && $product['original_price'] > $product['price'] && $product['price'] > 0);
                                    $discount_percent = 0;
                                    if($has_discount) {
                                        $discount_percent = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100);
                                    }
                                ?>
                                <tr>
                                    <td><input type="checkbox" name="selected_products[]" value="<?php echo $product['id']; ?>" class="product-checkbox checkbox-select"></td>
                                    <td>
                                        <?php if($product['image']): ?>
                                        <img src="<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                        <?php else: ?>
                                        <div class="product-placeholder"><i class="fas fa-image"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        <br>
                                        <small style="color: #7b6b5c;"><?php echo substr(htmlspecialchars($product['description']), 0, 50); ?>...</small>
                                     </td>
                                    <td><code style="color: #c45c4a;"><?php echo $product['sku']; ?></code></td>
                                    <td><span class="category-badge"><?php echo ucfirst($product['category']); ?></span></td>
                                    <td class="price">
                                        <?php if($has_discount): ?>
                                            <span style="font-size: 0.7rem; color: #999; text-decoration: line-through; display: block;">KES <?php echo number_format($product['original_price'], 2); ?></span>
                                            <span style="font-weight: 700; color: #c45c4a;">KES <?php echo number_format($product['price'], 2); ?></span>
                                        <?php else: ?>
                                            <span style="font-weight: 700;">KES <?php echo number_format($product['price'], 2); ?></span>
                                        <?php endif; ?>
                                     </td>
                                    <td>
                                        <?php if($has_discount): ?>
                                            <span class="discount-badge">-<?php echo $discount_percent; ?>% OFF</span>
                                        <?php else: ?>
                                            <span style="color: #7b6b5c;">-</span>
                                        <?php endif; ?>
                                     </td>
                                    <td>
                                        <span class="stock-badge <?php echo $product['stock'] > 10 ? 'stock-high' : ($product['stock'] > 0 ? 'stock-low' : 'stock-out'); ?>">
                                            <i class="fas <?php echo $product['stock'] > 0 ? 'fa-boxes' : 'fa-box-open'; ?>"></i> <?php echo $product['stock']; ?> left
                                        </span>
                                     </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $product['status']; ?>">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                     </td>
                                    <td class="actions">
                                        <a href="product-edit.php?id=<?php echo $product['id']; ?>" class="btn-icon" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="?toggle=<?php echo $product['id']; ?>" class="btn-icon" title="Toggle Status"><i class="fas fa-power-off"></i></a>
                                        <a href="?delete=<?php echo $product['id']; ?>" class="btn-icon" title="Delete" onclick="return confirm('Are you sure you want to delete <?php echo addslashes($product['name']); ?>?')"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 3rem;">
                                        <i class="fas fa-seedling" style="font-size: 3rem; color: #e2cbb8;"></i>
                                        <p style="margin-top: 1rem; color: #7b6b5c;">No products found</p>
                                        <a href="product-add.php" class="btn-primary" style="margin-top: 1rem; display: inline-block;">Add Your First Product</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Select All functionality
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.product-checkbox');
        const bulkActions = document.getElementById('bulkActions');
        const selectedCountSpan = document.getElementById('selectedCount');

        function updateBulkActions() {
            const checked = document.querySelectorAll('.product-checkbox:checked');
            const count = checked.length;
            selectedCountSpan.textContent = count;
            
            if(count > 0) {
                bulkActions.classList.add('show');
            } else {
                bulkActions.classList.remove('show');
            }
        }

        if(selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => {
                    cb.checked = selectAll.checked;
                });
                updateBulkActions();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkActions);
        });

        updateBulkActions();
    </script>
</body>
</html>