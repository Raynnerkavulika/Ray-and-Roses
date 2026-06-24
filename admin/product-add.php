<?php
session_start();
require_once '../config/database.php';

// Admin access check
if(!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

$error_message = '';
$success_message = '';

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/products/';
if(!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate SKU automatically
function generateSKU($name, $category) {
    $prefix = strtoupper(substr($name, 0, 3));
    $cat_prefix = strtoupper(substr($category, 0, 2));
    $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return $prefix . $cat_prefix . '-' . $random;
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
            $image = $upload_result['success'];
        } else {
            $error_message = $upload_result['error'];
        }
    }
    
    // Validation
    if(empty($name) || empty($description) || $price <= 0 || empty($category)) {
        $error_message = "Please fill in all required fields";
    } elseif(empty($image)) {
        $error_message = "Please provide a product image (either upload or enter URL)";
    } else {
        // Generate SKU
        $sku = generateSKU($name, $category);
        
        // Ensure SKU is unique (prevent duplicate key errors)
        $check_stmt = $conn->prepare("SELECT id FROM products WHERE sku = ?");
        $check_stmt->bind_param("s", $sku);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            // Regenerate with a new random number
            $sku = generateSKU($name, $category);
        }
        $check_stmt->close();
        
        // Prepare and execute insert
        $insert_sql = "INSERT INTO products (name, description, full_description, price, original_price, category, stock, image, sku, status, featured, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        
        // Correct bind types: 
        // s = string, d = double, i = integer
        // order: name(s), description(s), full_description(s), price(d), original_price(d), 
        //        category(s), stock(i), image(s), sku(s), status(s), featured(i)
        $insert_stmt->bind_param("sssddsisssi", 
            $name, $description, $full_description, $price, $original_price, 
            $category, $stock, $image, $sku, $status, $featured
        );
        
        if($insert_stmt->execute()) {
            $product_id = $conn->insert_id;
            $success_message = "Product added successfully!";
            echo '<meta http-equiv="refresh" content="2;url=products.php?msg=added">';
        } else {
            $error_message = "Failed to add product. Please try again.";
        }
        $insert_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product | Ray & Roses Admin</title>
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
                <h1><i class="fas fa-seedling"></i> Add New Product</h1>
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
                                <input type="text" name="name" id="productName" required placeholder="e.g., Wild Rose Bouquet">
                                <div class="char-counter" id="nameCounter">0/100 characters</div>
                            </div>
                            
                            <div class="form-group">
                                <label>Short Description <span class="required">*</span></label>
                                <textarea name="description" id="shortDesc" rows="3" required placeholder="Brief description for product listing..."></textarea>
                                <div class="char-counter" id="shortDescCounter">0/200 characters</div>
                            </div>
                            
                            <div class="form-group">
                                <label>Full Description</label>
                                <textarea name="full_description" id="fullDesc" rows="6" placeholder="Detailed product description for product page..."></textarea>
                                <div class="char-counter" id="fullDescCounter">0/1000 characters</div>
                            </div>
                            
                            <div class="form-group">
                                <label>Category <span class="required">*</span></label>
                                <select name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="bouquet">🌸 Bouquet</option>
                                    <option value="luxury">💎 Luxury</option>
                                    <option value="seasonal">🍂 Seasonal</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="form-section">
                            <h3><i class="fas fa-chart-line"></i> Pricing & Inventory</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Price (KES) <span class="required">*</span></label>
                                    <input type="number" step="0.01" name="price" id="price" required placeholder="0.00">
                                </div>
                                <div class="form-group">
                                    <label>Stock Quantity <span class="required">*</span></label>
                                    <input type="number" name="stock" id="stock" value="0" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Original Price (for discount display)</label>
                                <input type="number" step="0.01" name="original_price" id="originalPrice" placeholder="Leave empty for no discount">
                                <small style="color: #7b6b5c; display: block; margin-top: 0.3rem;">
                                    <i class="fas fa-info-circle"></i> If set higher than regular price, a discount badge will appear on the product
                                </small>
                                <div class="discount-hint" id="discountHint">
                                    <i class="fas fa-tag"></i> Discount will be: <span id="discountPercent">0</span>% off
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
                                    </div>
                                    
                                    <!-- URL Tab -->
                                    <div id="urlTab" class="upload-tab" style="display: none;">
                                        <input type="url" name="image" id="imageUrl" class="form-group" placeholder="https://example.com/image.jpg" style="width: 100%;">
                                        <small style="color: #7b6b5c; display: block; margin-top: 0.5rem;">
                                            <i class="fas fa-info-circle"></i> Enter a valid image URL from Unsplash or other sources
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="image-preview" id="imagePreview" style="display: none;">
                                <div class="preview-box">
                                    <img id="previewImg" src="" alt="Preview">
                                </div>
                                <button type="button" class="btn-danger" style="padding: 0.3rem 0.8rem; font-size: 0.8rem;" onclick="clearImage()">Remove Image</button>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status">
                                        <option value="active">✓ Active</option>
                                        <option value="inactive">✗ Inactive</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="checkbox-group">
                                        <input type="checkbox" name="featured" id="featured" value="1">
                                        <label for="featured" style="margin-bottom: 0;">⭐ Featured Product</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Preview Information</label>
                                <div class="preview-box-info">
                                    <p><i class="fas fa-tag"></i> <strong>Estimated SKU:</strong> <span id="skuPreview" style="color: #c45c4a;">Auto-generated</span></p>
                                    <p><i class="fas fa-coins"></i> <strong>Total Value:</strong> <span id="totalValuePreview">KES 0.00</span></p>
                                    <p><i class="fas fa-percent"></i> <strong>Discount Preview:</strong> <span id="discountPreview">No discount</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="reset" class="btn-danger" onclick="return confirm('Clear all form fields?')">
                            <i class="fas fa-undo-alt"></i> Reset
                        </button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Save Product
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
            } else {
                uploadTab.style.display = 'none';
                urlTab.style.display = 'block';
                tabs[0].classList.remove('active');
                tabs[1].classList.add('active');
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
        
        function clearImage() {
            document.getElementById('product_image').value = '';
            document.getElementById('imageUrl').value = '';
            imagePreview.style.display = 'none';
            previewImg.src = '';
        }
        
        // SKU Preview
        const productName = document.getElementById('productName');
        const categorySelect = document.querySelector('select[name="category"]');
        const skuPreview = document.getElementById('skuPreview');
        
        function updateSKUPreview() {
            const name = productName.value;
            const category = categorySelect.value;
            if(name && category) {
                const prefix = name.substring(0, 3).toUpperCase();
                const catPrefix = category.substring(0, 2).toUpperCase();
                const random = Math.floor(Math.random() * 9999).toString().padStart(4, '0');
                skuPreview.textContent = `${prefix}${catPrefix}-${random}`;
            } else {
                skuPreview.textContent = 'Auto-generated';
            }
        }
        
        productName.addEventListener('input', updateSKUPreview);
        categorySelect.addEventListener('change', updateSKUPreview);
        
        // Total value preview
        const priceInput = document.getElementById('price');
        const stockInput = document.getElementById('stock');
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
            const category = categorySelect.value;
            const fileInput = document.getElementById('product_image');
            const urlInput = document.getElementById('imageUrl');
            
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
                categorySelect.focus();
                return false;
            }
            
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
            
            return true;
        });
        
        // Auto-generate preview on load
        updateTotalValue();
    </script>
</body>
</html>