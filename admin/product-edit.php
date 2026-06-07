<?php
session_start();
require_once '../config/database.php';

// Add this to the top of ALL admin pages (dashboard.php, products.php, orders.php, users.php, etc.)
if(!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// Get product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch product data
$product_sql = "SELECT * FROM products WHERE id = ?";
$product_stmt = $conn->prepare($product_sql);
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();
$product = $product_result->fetch_assoc();

if(!$product) {
    header("Location: products.php");
    exit();
}

$error_message = '';
$success_message = '';

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/products/';
if(!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Function to upload image
function uploadImage($file, $upload_dir) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if(!in_array($file['type'], $allowed_types)) {
        return ['error' => 'Only JPG, PNG, GIF, WEBP images are allowed'];
    }
    
    if($file['size'] > $max_size) {
        return ['error' => 'Image size must be less than 5MB'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = time() . '_' . rand(1000, 9999) . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    if(move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => 'uploads/products/' . $filename];
    } else {
        return ['error' => 'Failed to upload image'];
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $full_description = trim($_POST['full_description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $original_price = !empty($_POST['original_price']) ? floatval($_POST['original_price']) : null;
    $category = $_POST['category'] ?? '';
    $stock = intval($_POST['stock'] ?? 0);
    $image = $_POST['image'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Handle file upload
    if(isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $upload_result = uploadImage($_FILES['product_image'], $upload_dir);
        if(isset($upload_result['success'])) {
            // Delete old image if exists
            if($product['image'] && file_exists('../' . $product['image'])) {
                unlink('../' . $product['image']);
            }
            $image = $upload_result['success'];
        } else {
            $error_message = $upload_result['error'];
        }
    }
    
    // Validation
    if(empty($name) || empty($description) || $price <= 0 || empty($category)) {
        $error_message = "Please fill in all required fields";
    } elseif(empty($image) && empty($product['image'])) {
        $error_message = "Please provide a product image (either upload or enter URL)";
    } else {
        // If no new image uploaded, keep the existing one
        if(empty($image)) {
            $image = $product['image'];
        }
        
        $update_sql = "UPDATE products SET 
                      name = ?, description = ?, full_description = ?, 
                      price = ?, original_price = ?, category = ?, stock = ?, 
                      image = ?, status = ?, featured = ? 
                      WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssddissiii", 
            $name, $description, $full_description, $price, $original_price, 
            $category, $stock, $image, $status, $featured, $product_id
        );
        
        if($update_stmt->execute()) {
            $success_message = "Product updated successfully!";
            // Refresh product data
            $product['name'] = $name;
            $product['description'] = $description;
            $product['full_description'] = $full_description;
            $product['price'] = $price;
            $product['original_price'] = $original_price;
            $product['category'] = $category;
            $product['stock'] = $stock;
            $product['image'] = $image;
            $product['status'] = $status;
            $product['featured'] = $featured;
            echo '<meta http-equiv="refresh" content="2;url=products.php?msg=updated">';
        } else {
            $error_message = "Failed to update product. Please try again.";
        }
        $update_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product | Ray & Roses Admin</title>
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
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .top-bar h1 i {
            color: #c45c4a;
        }

        .product-id-badge {
            background: #fef6ef;
            color: #c45c4a;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .btn-secondary {
            background: #7b6b5c;
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

        .btn-secondary:hover {
            background: #5a3f2c;
            transform: translateY(-2px);
        }

        /* Form Container */
        .form-container {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
        }

        .form-section {
            background: #fefaf7;
            border-radius: 16px;
            padding: 1.5rem;
        }

        .form-section h3 {
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0e0d4;
            color: #2d2a24;
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
        }

        .form-section h3 i {
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

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 2px solid #f0e0d4;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            transition: 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #c45c4a;
            box-shadow: 0 0 0 3px rgba(196,92,74,0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* Discount Preview */
        .discount-hint {
            background: #e8f5e9;
            padding: 0.5rem;
            border-radius: 10px;
            margin-top: 0.5rem;
            font-size: 0.75rem;
            color: #2e7d32;
            display: none;
        }

        .discount-hint.show {
            display: block;
        }

        /* Image Upload Styles */
        .image-upload-container {
            border: 2px dashed #f0e0d4;
            border-radius: 16px;
            padding: 1rem;
            transition: 0.3s;
        }

        .image-upload-container:hover {
            border-color: #c45c4a;
        }

        .image-upload-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid #f0e0d4;
            padding-bottom: 0.5rem;
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 0.5rem 1rem;
            cursor: pointer;
            font-weight: 500;
            color: #7b6b5c;
            transition: 0.3s;
            border-radius: 10px;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            color: white;
        }

        .upload-area {
            border: 2px dashed #f0e0d4;
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
            background: #fefaf7;
        }

        .upload-area:hover {
            border-color: #c45c4a;
            background: #fef6ef;
        }

        .upload-area i {
            font-size: 2rem;
            color: #c45c4a;
            margin-bottom: 0.5rem;
        }

        .file-input {
            display: none;
        }

        .image-preview {
            margin-top: 1rem;
            text-align: center;
        }

        .preview-box {
            width: 100%;
            height: 200px;
            background: #fef6ef;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .preview-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }

        /* Stock Status */
        .stock-status {
            margin-top: 0.5rem;
            padding: 0.5rem;
            border-radius: 10px;
            font-size: 0.8rem;
        }

        .stock-high {
            background: #e8f5e9;
            color: #4caf50;
        }

        .stock-low {
            background: #fff3e0;
            color: #ff9800;
        }

        .stock-out {
            background: #fee;
            color: #f44336;
        }

        /* Checkbox */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .checkbox-group input {
            width: auto;
            accent-color: #c45c4a;
        }

        /* Alerts */
        .alert-error {
            background: #fee;
            color: #f44336;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            border-left: 4px solid #f44336;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            border-left: 4px solid #4caf50;
        }

        /* Form Actions */
        .form-actions {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #f0e0d4;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn-primary {
            background: linear-gradient(135deg, #c45c4a, #e8876e);
            color: white;
            padding: 0.8rem 2rem;
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
            padding: 0.8rem 2rem;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        /* Character Counter */
        .char-counter {
            font-size: 0.7rem;
            color: #7b6b5c;
            text-align: right;
            margin-top: 0.3rem;
        }

        /* Preview Box */
        .preview-box-info {
            background: #fef6ef;
            padding: 1rem;
            border-radius: 12px;
            margin-top: 0.5rem;
        }

        .preview-box-info p {
            font-size: 0.85rem;
            margin-bottom: 0.3rem;
        }

        .preview-box-info strong {
            color: #c45c4a;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .form-grid {
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
            .main-content {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="top-bar">
                <h1>
                    <i class="fas fa-edit"></i> Edit Product 
                    <span class="product-id-badge"><i class="fas fa-hashtag"></i> ID: <?php echo $product['id']; ?></span>
                    <span class="product-id-badge"><i class="fas fa-barcode"></i> SKU: <?php echo $product['sku']; ?></span>
                </h1>
                <a href="products.php" class="btn-secondary"><i class="fas fa-arrow-left"></i> Back to Products</a>
            </div>
            
            <?php if($error_message): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if($success_message): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" id="productForm" enctype="multipart/form-data">
                    <div class="form-grid">
                        <!-- Left Column -->
                        <div class="form-section">
                            <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                            
                            <div class="form-group">
                                <label>Product Name <span class="required">*</span></label>
                                <input type="text" name="name" id="productName" required value="<?php echo htmlspecialchars($product['name']); ?>" placeholder="e.g., Wild Rose Bouquet">
                                <div class="char-counter" id="nameCounter"><?php echo strlen($product['name']); ?>/100 characters</div>
                            </div>
                            
                            <div class="form-group">
                                <label>Short Description <span class="required">*</span></label>
                                <textarea name="description" id="shortDesc" rows="3" required placeholder="Brief description for product listing..."><?php echo htmlspecialchars($product['description']); ?></textarea>
                                <div class="char-counter" id="shortDescCounter"><?php echo strlen($product['description']); ?>/200 characters</div>
                            </div>
                            
                            <div class="form-group">
                                <label>Full Description</label>
                                <textarea name="full_description" id="fullDesc" rows="6" placeholder="Detailed product description for product page..."><?php echo htmlspecialchars($product['full_description'] ?? ''); ?></textarea>
                                <div class="char-counter" id="fullDescCounter"><?php echo strlen($product['full_description'] ?? ''); ?>/1000 characters</div>
                            </div>
                            
                            <div class="form-group">
                                <label>Category <span class="required">*</span></label>
                                <select name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="bouquet" <?php echo $product['category'] == 'bouquet' ? 'selected' : ''; ?>>🌸 Bouquet</option>
                                    <option value="luxury" <?php echo $product['category'] == 'luxury' ? 'selected' : ''; ?>>💎 Luxury</option>
                                    <option value="seasonal" <?php echo $product['category'] == 'seasonal' ? 'selected' : ''; ?>>🍂 Seasonal</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="form-section">
                            <h3><i class="fas fa-chart-line"></i> Pricing & Inventory</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Price (KES) <span class="required">*</span></label>
                                    <input type="number" step="0.01" name="price" id="price" required value="<?php echo $product['price']; ?>" placeholder="0.00">
                                </div>
                                <div class="form-group">
                                    <label>Stock Quantity <span class="required">*</span></label>
                                    <input type="number" name="stock" id="stock" required value="<?php echo $product['stock']; ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Original Price (for discount display)</label>
                                <input type="number" step="0.01" name="original_price" id="originalPrice" value="<?php echo $product['original_price']; ?>" placeholder="Leave empty for no discount">
                                <small style="color: #7b6b5c; display: block; margin-top: 0.3rem;">
                                    <i class="fas fa-info-circle"></i> If set higher than regular price, a discount badge will appear on the product
                                </small>
                                <div class="discount-hint" id="discountHint">
                                    <i class="fas fa-tag"></i> Discount will be: <span id="discountPercent">0</span>% off
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div id="stockStatusDisplay" class="stock-status <?php echo $product['stock'] > 10 ? 'stock-high' : ($product['stock'] > 0 ? 'stock-low' : 'stock-out'); ?>">
                                    <i class="fas <?php echo $product['stock'] > 0 ? ($product['stock'] > 10 ? 'fa-check-circle' : 'fa-exclamation-triangle') : 'fa-exclamation-circle'; ?>"></i>
                                    <?php 
                                    if($product['stock'] > 10) echo "✓ Good stock level";
                                    elseif($product['stock'] > 0) echo "⚠️ Low stock - Consider restocking soon";
                                    else echo "❌ Out of stock - Needs immediate attention";
                                    ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Product Image <span class="required">*</span></label>
                                <div class="image-upload-container">
                                    <div class="image-upload-tabs">
                                        <button type="button" class="tab-btn active" onclick="switchTab('upload')">📁 Upload File</button>
                                        <button type="button" class="tab-btn" onclick="switchTab('url')">🔗 Image URL</button>
                                    </div>
                                    
                                    <!-- File Upload Tab -->
                                    <div id="uploadTab" class="upload-tab">
                                        <div class="upload-area" onclick="document.getElementById('product_image').click()">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <p>Click to upload product image</p>
                                            <small style="color: #7b6b5c;">JPG, PNG, GIF, WEBP (Max 5MB)</small>
                                        </div>
                                        <input type="file" name="product_image" id="product_image" class="file-input" accept="image/*" onchange="previewFile(this)">
                                        <?php if($product['image'] && strpos($product['image'], 'uploads/') !== false): ?>
                                        <small style="color: #4caf50; display: block; margin-top: 0.5rem;">
                                            <i class="fas fa-check-circle"></i> Current image: <?php echo basename($product['image']); ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- URL Tab -->
                                    <div id="urlTab" class="upload-tab" style="display: none;">
                                        <input type="url" name="image" id="imageUrl" class="form-group" value="<?php echo htmlspecialchars($product['image'] ?? ''); ?>" placeholder="https://example.com/image.jpg" style="width: 100%;">
                                        <small style="color: #7b6b5c; display: block; margin-top: 0.5rem;">
                                            <i class="fas fa-info-circle"></i> Enter a valid image URL from Unsplash or other sources
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="image-preview" id="imagePreview" <?php echo !$product['image'] ? 'style="display: none;"' : ''; ?>>
                                <div class="preview-box">
                                    <img id="previewImg" src="<?php echo htmlspecialchars($product['image'] ?? ''); ?>" alt="Preview">
                                </div>
                                <button type="button" class="btn-danger" style="padding: 0.3rem 0.8rem; font-size: 0.8rem; margin-top: 0.5rem;" onclick="clearImage()">Remove Image</button>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status">
                                        <option value="active" <?php echo $product['status'] == 'active' ? 'selected' : ''; ?>>✓ Active</option>
                                        <option value="inactive" <?php echo $product['status'] == 'inactive' ? 'selected' : ''; ?>>✗ Inactive</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="checkbox-group">
                                        <input type="checkbox" name="featured" id="featured" value="1" <?php echo $product['featured'] ? 'checked' : ''; ?>>
                                        <label for="featured" style="margin-bottom: 0;">⭐ Featured Product</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Preview Information</label>
                                <div class="preview-box-info">
                                    <p><i class="fas fa-tag"></i> <strong>SKU:</strong> <span style="color: #c45c4a;"><?php echo $product['sku']; ?></span></p>
                                    <p><i class="fas fa-coins"></i> <strong>Total Inventory Value:</strong> <span id="totalValuePreview">KES <?php echo number_format($product['price'] * $product['stock'], 2); ?></span></p>
                                    <p><i class="fas fa-percent"></i> <strong>Discount Preview:</strong> <span id="discountPreview"><?php 
                                        if($product['original_price'] && $product['original_price'] > $product['price']) {
                                            $discount = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100);
                                            echo '<span style="color: #4caf50;">' . $discount . '% OFF</span>';
                                        } else {
                                            echo '<span style="color: #7b6b5c;">No discount</span>';
                                        }
                                    ?></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="products.php" class="btn-danger">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Character counters
        const nameInput = document.getElementById('productName');
        const shortDescInput = document.getElementById('shortDesc');
        const fullDescInput = document.getElementById('fullDesc');
        
        function updateCounter(input, counterId, max) {
            const counter = document.getElementById(counterId);
            const length = input.value.length;
            counter.textContent = `${length}/${max} characters`;
            if(length > max) {
                counter.style.color = '#f44336';
                input.value = input.value.substring(0, max);
                counter.textContent = `${max}/${max} characters`;
            } else if(length > max * 0.9) {
                counter.style.color = '#ff9800';
            } else {
                counter.style.color = '#7b6b5c';
            }
        }
        
        nameInput.addEventListener('input', () => updateCounter(nameInput, 'nameCounter', 100));
        shortDescInput.addEventListener('input', () => updateCounter(shortDescInput, 'shortDescCounter', 200));
        fullDescInput.addEventListener('input', () => updateCounter(fullDescInput, 'fullDescCounter', 1000));
        
        // Tab switching
        let currentTab = 'upload';
        
        function switchTab(tab) {
            currentTab = tab;
            const uploadTab = document.getElementById('uploadTab');
            const urlTab = document.getElementById('urlTab');
            const tabs = document.querySelectorAll('.tab-btn');
            
            if(tab === 'upload') {
                uploadTab.style.display = 'block';
                urlTab.style.display = 'none';
                tabs[0].classList.add('active');
                tabs[1].classList.remove('active');
                document.getElementById('imageUrl').value = '';
            } else {
                uploadTab.style.display = 'none';
                urlTab.style.display = 'block';
                tabs[0].classList.remove('active');
                tabs[1].classList.add('active');
                document.getElementById('product_image').value = '';
            }
        }
        
        // File preview
        function previewFile(input) {
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            
            if(input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                    document.getElementById('imageUrl').value = '';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // URL preview
        const imageUrl = document.getElementById('imageUrl');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        
        if(imageUrl) {
            imageUrl.addEventListener('input', function() {
                const url = this.value;
                if(url && (url.startsWith('http://') || url.startsWith('https://'))) {
                    previewImg.src = url;
                    imagePreview.style.display = 'block';
                    previewImg.onerror = function() {
                        previewImg.src = 'https://via.placeholder.com/300x200?text=Invalid+Image+URL';
                    };
                    document.getElementById('product_image').value = '';
                } else if(!url) {
                    imagePreview.style.display = 'none';
                }
            });
        }
        
        function clearImage() {
            document.getElementById('product_image').value = '';
            document.getElementById('imageUrl').value = '';
            imagePreview.style.display = 'none';
            previewImg.src = '';
        }
        
        // Stock status update
        const stockInput = document.getElementById('stock');
        const stockStatusDisplay = document.getElementById('stockStatusDisplay');
        
        function updateStockStatus() {
            const stock = parseInt(stockInput.value) || 0;
            if(stock > 10) {
                stockStatusDisplay.className = 'stock-status stock-high';
                stockStatusDisplay.innerHTML = '<i class="fas fa-check-circle"></i> ✓ Good stock level';
            } else if(stock > 0) {
                stockStatusDisplay.className = 'stock-status stock-low';
                stockStatusDisplay.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ⚠️ Low stock - Consider restocking soon';
            } else {
                stockStatusDisplay.className = 'stock-status stock-out';
                stockStatusDisplay.innerHTML = '<i class="fas fa-exclamation-circle"></i> ❌ Out of stock - Needs immediate attention';
            }
        }
        
        stockInput.addEventListener('input', updateStockStatus);
        
        // Total value preview
        const priceInput = document.getElementById('price');
        const totalValuePreview = document.getElementById('totalValuePreview');
        
        function updateTotalValue() {
            const price = parseFloat(priceInput.value) || 0;
            const stock = parseInt(stockInput.value) || 0;
            const total = price * stock;
            totalValuePreview.textContent = `KES ${total.toFixed(2)}`;
        }
        
        priceInput.addEventListener('input', updateTotalValue);
        stockInput.addEventListener('input', updateTotalValue);
        
        // Discount preview
        const originalPriceInput = document.getElementById('originalPrice');
        const discountHint = document.getElementById('discountHint');
        const discountPercentSpan = document.getElementById('discountPercent');
        const discountPreviewSpan = document.getElementById('discountPreview');
        
        function updateDiscountPreview() {
            const price = parseFloat(priceInput.value) || 0;
            const originalPrice = parseFloat(originalPriceInput.value) || 0;
            
            if(originalPrice > price && price > 0) {
                const discount = Math.round(((originalPrice - price) / originalPrice) * 100);
                discountPercentSpan.textContent = discount;
                discountHint.classList.add('show');
                discountPreviewSpan.innerHTML = `<span style="color: #4caf50;">${discount}% OFF (Original: KES ${originalPrice.toFixed(2)} → Sale: KES ${price.toFixed(2)})</span>`;
            } else {
                discountHint.classList.remove('show');
                discountPreviewSpan.innerHTML = '<span style="color: #7b6b5c;">No discount</span>';
            }
        }
        
        priceInput.addEventListener('input', updateDiscountPreview);
        originalPriceInput.addEventListener('input', updateDiscountPreview);
        
        // Form validation before submit
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const name = nameInput.value.trim();
            const price = parseFloat(priceInput.value);
            const category = document.querySelector('select[name="category"]').value;
            const hasExistingImage = <?php echo !empty($product['image']) ? 'true' : 'false'; ?>;
            
            if(!name) {
                e.preventDefault();
                alert('Please enter product name');
                nameInput.focus();
                return false;
            }
            
            if(price <= 0) {
                e.preventDefault();
                alert('Please enter a valid price greater than 0');
                priceInput.focus();
                return false;
            }
            
            if(!category) {
                e.preventDefault();
                alert('Please select a category');
                return false;
            }
            
            // Check if image is provided
            if(!hasExistingImage) {
                const fileInput = document.getElementById('product_image');
                const urlInput = document.getElementById('imageUrl');
                
                if(currentTab === 'upload') {
                    if(!fileInput.files || !fileInput.files[0]) {
                        e.preventDefault();
                        alert('Please upload a product image');
                        return false;
                    }
                } else {
                    if(!urlInput.value.trim()) {
                        e.preventDefault();
                        alert('Please enter an image URL');
                        return false;
                    }
                }
            }
            
            return true;
        });
        
        // Initialize discount preview on load
        updateDiscountPreview();
    </script>
</body>
</html>