CREATE DATABASE IF NOT EXISTS flower_shop;
USE flower_shop;

CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    verification_code VARCHAR(64),
    remember_token VARCHAR(64) NULL,
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_remember_token (remember_token)
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    full_description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0,
    sku VARCHAR(50) UNIQUE,
    image VARCHAR(500),
    status ENUM('active', 'inactive') DEFAULT 'active',
    featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_status (status)
);

-- Product images table
CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0,
    shipping_cost DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    shipping_address TEXT,
    billing_address TEXT,
    tracking_number VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_order_number (order_number)
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Admin users table (separate from regular users)
ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN role ENUM('user', 'admin', 'super_admin') DEFAULT 'user';

-- Insert default admin (password: admin123)
INSERT INTO users (first_name, last_name, email, password, is_admin, role) 
VALUES ('Admin', 'User', 'admin@petalandstem.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'super_admin');

-- Product reviews table
CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add admin columns to users table
ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN role ENUM('user', 'admin', 'super_admin') DEFAULT 'user';

-- Insert default admin user (password: admin123)
INSERT INTO users (first_name, last_name, email, password, is_admin, role) 
VALUES ('Admin', 'User', 'admin@petalandstem.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'super_admin')
ON DUPLICATE KEY UPDATE is_admin = 1, role = 'super_admin';

UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE email = 'admin@petalandstem.com';

ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active';

ALTER TABLE products ADD COLUMN original_price DECIMAL(10,2) NULL;
ALTER TABLE products ADD COLUMN discount_percent INT DEFAULT 0;

-- Add role and permissions columns if not exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('super_admin', 'admin', 'user') DEFAULT 'user';
ALTER TABLE users ADD COLUMN IF NOT EXISTS permissions TEXT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS created_by INT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL;

-- Create product_reviews table
CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    comment TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_status (status)
);

-- Add missing columns to orders table
ALTER TABLE orders ADD COLUMN first_name VARCHAR(100) AFTER user_id;
ALTER TABLE orders ADD COLUMN last_name VARCHAR(100) AFTER first_name;
ALTER TABLE orders ADD COLUMN email VARCHAR(100) AFTER last_name;
ALTER TABLE orders ADD COLUMN phone VARCHAR(20) AFTER email;
ALTER TABLE orders ADD COLUMN address VARCHAR(255) AFTER phone;
ALTER TABLE orders ADD COLUMN city VARCHAR(100) AFTER address;
ALTER TABLE orders ADD COLUMN postal_code VARCHAR(20) AFTER city;

-- Add subtotal column to order_items table if it doesn't exist
ALTER TABLE order_items ADD COLUMN IF NOT EXISTS subtotal DECIMAL(10,2) AFTER price;

-- Create order_tracking table for status history
CREATE TABLE IF NOT EXISTS order_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Create order_items table if not exists
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE IF NOT EXISTS order_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Create password_resets table
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email)
);

-- Check and add only missing columns
-- First, check if mpesa_checkout_id column exists (if not, add it)
ALTER TABLE orders ADD COLUMN IF NOT EXISTS mpesa_checkout_id VARCHAR(100) NULL;

-- Check if payment_message column exists (if not, add it)
ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_message TEXT NULL;

-- Create mpesa_transactions table (if it doesn't exist)
CREATE TABLE IF NOT EXISTS mpesa_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL,
    checkout_request_id VARCHAR(100) NOT NULL,
    mpesa_receipt_number VARCHAR(50),
    amount DECIMAL(10,2),
    transaction_date VARCHAR(20),
    result_code INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_number (order_number)
);

CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);