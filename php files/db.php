<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PostgreSQL connection config
$db_host = getenv('DB_HOST');
$db_port = getenv('DB_PORT') ?: '5432';
$db_name = getenv('DB_NAME') ?: 'postgres';
$db_user = getenv('DB_USER'); // Added database username (change 'postgres' if needed)
$db_pass = getenv('DB_PASS');

try {
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";
    
    // Create the PDO connection instance (this was missing in your original code)
    $conn = new PDO($dsn, $db_user, $db_pass);
    
    // Set PDO attributes
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Keep the app usable with databases created from the earlier schema.
    $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS registration_number VARCHAR(50)");
    $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_eligible BOOLEAN NOT NULL DEFAULT FALSE");
    $conn->exec("ALTER TABLE candidates ADD COLUMN IF NOT EXISTS photo_data BYTEA");
    $conn->exec("ALTER TABLE candidates ADD COLUMN IF NOT EXISTS photo_mime VARCHAR(100)");
    $conn->exec("ALTER TABLE candidates ADD COLUMN IF NOT EXISTS biography TEXT");
    $conn->exec("ALTER TABLE candidates ADD COLUMN IF NOT EXISTS manifesto TEXT");
    $conn->exec("CREATE TABLE IF NOT EXISTS election_settings (
        id SMALLINT PRIMARY KEY DEFAULT 1 CHECK (id = 1),
        institution_name VARCHAR(150) NOT NULL DEFAULT 'DIT',
        election_title VARCHAR(200) NOT NULL DEFAULT 'Student Online Voting System',
        starts_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ends_at TIMESTAMP NOT NULL DEFAULT (CURRENT_TIMESTAMP + INTERVAL '30 days'),
        results_visible BOOLEAN NOT NULL DEFAULT TRUE,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $conn->exec("INSERT INTO election_settings (id) VALUES (1) ON CONFLICT (id) DO NOTHING");

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>