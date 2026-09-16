<?php
include 'db.php';

$error = '';
$success = '';

if (isset($_POST['register'])) {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($fullname) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Check if email already exists
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->execute([$username]);

        if ($check_stmt->fetch(PDO::FETCH_ASSOC)) {
            $error = 'Email already registered. Please use a different email or <a href="login.php">login here</a>';
        } else {
            // Register new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users(fullname, username, password, role) VALUES(?, ?, ?, ?)";
            $params = array($fullname, $username, $hashed_password, 'user');
            try {
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $success = 'Registration successful! Redirecting to login...';
                header("Location: login.php?registered=1");
                exit;
            } catch (PDOException $e) {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
    <h2>Register</h2>
    <?php 
    if ($error) {
        echo "<div style='background:#f8d7da; color:#721c24; padding:12px; border-radius:4px; margin-bottom:15px;'>❌ " . $error . "</div>";
    }
    if ($success) {
        echo "<div style='background:#d4edda; color:#155724; padding:12px; border-radius:4px; margin-bottom:15px;'>✓ " . $success . "</div>";
    }
    ?>
    <form method="POST">
        <input type="text" name="fullname" placeholder="Full Name" required>
        <input type="email" name="username" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button name="register">Register</button>
    </form>
    <p>Already have account? <a href="login.php">Login here</a></p>
</div>
</body>
</html>
