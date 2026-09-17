<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db.php';

$error = '';

if (isset($_POST['login'])) {

    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {

        $sql = "SELECT id, fullname, username, registration_number, is_eligible, password, role
                FROM users
                WHERE LOWER(TRIM(username)) = ?";

        $params = array($username);
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && !empty($user['password'])) {

                //  Verify hashed password (supports password_hash)
                if (password_verify($password, $user['password'])) {

                    $_SESSION['user_id']  = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['fullname'] = $user['fullname'];
                    $_SESSION['registration_number'] = $user['registration_number'];
                    $_SESSION['is_eligible'] = (bool) $user['is_eligible'];
                    // clean role for consistent comparison
                    $_SESSION['role']     = strtolower(trim($user['role']));

                    // allow admin and admin_limited to go to admin dashboard
                    if (in_array($_SESSION['role'], ['admin','admin_limited'])) {
                        header("Location: admin.php");
                    } else {
                        header("Location: dashboard.php");
                    }

                    exit;

                } else {
                    $error = 'Incorrect password';
                }

            } else {
                $error = 'Email not found. <a href="register.php">Register here</a>';
            }
        } catch (PDOException $e) {
            $error = 'Database error. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="form-container">

<h2>Login</h2>

<?php 
if (isset($_GET['registered'])) {
    echo "<div style='background:#d4edda; color:#155724; padding:12px; border-radius:4px; margin-bottom:15px;'>
    ✓ Registration successful! Please login.
    </div>";
}

if ($error) {
    echo "<div style='background:#f8d7da; color:#721c24; padding:12px; border-radius:4px; margin-bottom:15px;'>
     " . $error . "
    </div>";
}
?>

<form method="POST">
    <input type="email" name="username" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button name="login">Login</button>
</form>

<p>Don't have account? <a href="register.php">Register here</a></p>

</div>

</body>
</html>