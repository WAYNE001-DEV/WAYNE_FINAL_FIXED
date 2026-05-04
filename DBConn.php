<?php
/**
 * DBConn.php
 * Database connection file for PASTIMES
 * Uses MySQLi improved connection
 */

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Change to your MySQL username
define('DB_PASS', '');            // Change to your MySQL password
define('DB_NAME', 'ClothingStore');

/**
 * Creates and returns a MySQLi database connection
 * @return mysqli
 */
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Set character set to UTF-8
    $conn->set_charset("utf8mb4");

    return $conn;
}

// Create a global connection instance
$conn = getConnection();
?>
