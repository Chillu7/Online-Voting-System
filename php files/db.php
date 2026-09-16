<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PostgreSQL connection config
$db_host = getenv('DB_HOST') ?: '';
$db_port = getenv('DB_PORT') ?: '5432';
$db_name = getenv('DB_NAME') ?: 'postgres';
$db_user = getenv('DB_USER') ?: ''; // Added database username (change 'postgres' if needed)
$db_pass = getenv('DB_PASS') ?: '';

try {
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";
    
    // Create the PDO connection instance (this was missing in your original code)
    $conn = new PDO($dsn, $db_user, $db_pass);
    
    // Set PDO attributes
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>