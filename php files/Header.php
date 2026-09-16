<?php
if (!isset($_SESSION['user_id'])) return;
?>

<link rel="stylesheet" href="style.css">
<div class="header">
    <?php if ($_SESSION['role'] == 'user'): ?>
        <a href="dashboard.php">Dashboard</a>
        <a href="results.php">Results</a>
    <?php elseif ($_SESSION['role'] == 'admin'): ?>
        <a href="admin.php">Admin Panel</a>
    <?php endif; ?>
    <a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username']); ?>)</a>
</div>

