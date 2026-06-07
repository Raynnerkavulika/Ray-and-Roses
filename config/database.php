<?php
// config/database.php
$db_host = 'localhost';
$db_user = 'root'; // Change this to your database username
$db_password = ''; // Change this to your database password
$db_name = 'flower_shop';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");
?>