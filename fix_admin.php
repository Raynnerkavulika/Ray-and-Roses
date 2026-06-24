<?php

// Database configuration - adjust these to match your environment
$db_host = 'localhost';
$db_name = 'flower_shop';      // <-- CHANGE THIS
$db_user = 'root';      // <-- CHANGE THIS
$db_pass = '';      // <-- CHANGE THIS

// Alternatively, you can include a config file if you have one:
// require_once 'config/database.php';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $email = 'altoid@gmail.com';
    $plainPassword = '1234';
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update existing user to admin
        $sql = "UPDATE users 
                SET password = :password,
                    is_admin = 1,
                    role = 'admin',
                    status = 'active',
                    is_verified = 1,
                    verification_code = NULL,
                    updated_at = NOW()
                WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':password' => $hashedPassword,
            ':email'    => $email
        ]);
        echo "✅ Updated existing user (ID: {$existing['id']}) to admin.\n";
    } else {
        // Insert new admin user
        $sql = "INSERT INTO users 
                (first_name, last_name, email, password, is_admin, role, status, is_verified, created_at, updated_at)
                VALUES 
                ('Admin', 'User', :email, :password, 1, 'admin', 'active', 1, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':email'    => $email,
            ':password' => $hashedPassword
        ]);
        $newId = $pdo->lastInsertId();
        echo "✅ Inserted new admin user with ID: $newId\n";
    }

    echo "Admin user '$email' is now set with password '$plainPassword'.\n";
    echo "You can log in with these credentials.\n";

} catch (PDOException $e) {
    die("❌ Database error: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}