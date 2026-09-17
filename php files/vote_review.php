<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: login.php');
    exit;
}

$cid = filter_input(INPUT_POST, 'cid', FILTER_VALIDATE_INT);
$position = trim($_POST['position'] ?? '');
if (!$cid || $position === '') {
    header('Location: dashboard.php?error=' . urlencode('Invalid vote selection.'));
    exit;
}

$stmt = $conn->prepare('SELECT id, name, position, biography, manifesto FROM candidates WHERE id = ? AND position = ?');
$stmt->execute([$cid, $position]);
$candidate = $stmt->fetch();
if (!$candidate) {
    header('Location: dashboard.php?error=' . urlencode('Candidate not found.'));
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Vote</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include __DIR__ . '/Header.php'; ?>
<div class="form-container">
    <h2>Review your choice</h2>
    <img class="review-photo" src="image.php?id=<?php echo (int) $candidate['id']; ?>" alt="Candidate photo">
    <p><strong>Position:</strong> <?php echo htmlspecialchars($candidate['position']); ?></p>
    <p><strong>Candidate:</strong> <?php echo htmlspecialchars($candidate['name']); ?></p>
    <p>Confirming will record one vote for this position.</p>
    <form method="POST" action="vote.php">
        <input type="hidden" name="cid" value="<?php echo (int) $candidate['id']; ?>">
        <input type="hidden" name="position" value="<?php echo htmlspecialchars($candidate['position'], ENT_QUOTES); ?>">
        <button type="submit">Confirm Vote</button>
    </form>
    <p><a href="dashboard.php">Go back and change choice</a></p>
</div>
</body>
</html>
