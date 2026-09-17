<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

include __DIR__ . '/Header.php';
$settings = $conn->query('SELECT * FROM election_settings WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
$now = new DateTimeImmutable();
$starts_at = new DateTimeImmutable($settings['starts_at']);
$ends_at = new DateTimeImmutable($settings['ends_at']);
$election_open = $now >= $starts_at && $now <= $ends_at;
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="welcome-box">
    <h2>🗳️ <?php echo htmlspecialchars($settings['institution_name']); ?> <?php echo htmlspecialchars($settings['election_title']); ?></h2>
    <p>Hi <strong><?php echo htmlspecialchars($_SESSION['fullname'] ?? ''); ?></strong>, please vote carefully and choose the best leaders for each position.</p>
    <p>Voting period: <?php echo htmlspecialchars($starts_at->format('d M Y H:i')); ?> - <?php echo htmlspecialchars($ends_at->format('d M Y H:i')); ?></p>
    <?php if (empty($_SESSION['is_eligible'])): ?><div class="voted-box">Your voter ID is awaiting admin verification. You cannot vote yet.</div><?php endif; ?>
    <?php if (!$election_open): ?><div class="voted-box">Voting is currently closed.</div><?php endif; ?>
    <?php
    if (!empty($_GET['success'])) {
        echo "<div style='background:#d4edda; color:#155724; padding:10px; border-radius:4px; margin-top:10px;'>✓ " . htmlspecialchars($_GET['success']) . "</div>";
    }
    if (!empty($_GET['error'])) {
        echo "<div style='background:#f8d7da; color:#721c24; padding:10px; border-radius:4px; margin-top:10px;'>❌ " . htmlspecialchars($_GET['error']) . "</div>";
    }
    ?>
</div>

<?php
$user_id = $_SESSION['user_id'];

try {
    // Fetch distinct positions
    $stmt = $conn->prepare('SELECT DISTINCT position FROM candidates ORDER BY position');
    $stmt->execute();
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($positions) {
        foreach ($positions as $p) {
            $position = $p['position'] ?? null;
            if (!$position) continue;

            echo "<div class='position-title'>Vote for " . htmlspecialchars($position) . "</div>";

            // Check if user already voted
            $stmt_vote = $conn->prepare('SELECT id FROM votes WHERE user_id = ? AND position = ?');
            $stmt_vote->execute([$user_id, $position]);
            $already_voted = $stmt_vote->fetch(PDO::FETCH_ASSOC);

            if ($already_voted) {
                echo "<div class='voted-box'>✅ You already voted for this position</div>";
                continue;
            }

            // Fetch candidates
            $stmt_cand = $conn->prepare('SELECT id, name, photo, position, biography, manifesto FROM candidates WHERE position = ? ORDER BY name');
            $stmt_cand->execute([$position]);
            $candidates = $stmt_cand->fetchAll(PDO::FETCH_ASSOC);

            echo "<div class='candidates-grid'>";
            if ($candidates) {
                foreach ($candidates as $c) {
                    $c_name = htmlspecialchars($c['name'] ?? '');
                    $c_position = htmlspecialchars($c['position'] ?? '');
                    $c_id = intval($c['id'] ?? 0);
                    $c_biography = htmlspecialchars($c['biography'] ?? '');
                    $c_manifesto = htmlspecialchars($c['manifesto'] ?? '');

                    echo "
                    <div class='candidate-card'>
                        <img src='image.php?id={$c_id}' alt='{$c_name}'>
                        <h3>{$c_name}</h3>
                        <p><small>Position: {$c_position}</small></p>
                        <p><strong>Biography:</strong> {$c_biography}</p>
                        <p><strong>Manifesto:</strong> {$c_manifesto}</p>
                        " . ($election_open && !empty($_SESSION['is_eligible']) ? "
                        <form method='POST' action='vote_review.php'>
                            <input type='hidden' name='cid' value='{$c_id}'>
                            <input type='hidden' name='position' value='{$c_position}'>
                            <button class='vote-btn' type='submit'>Review vote</button>
                        </form>" : "<div class='vote-closed'>Voting is closed</div>") . "
                    </div>";
                }
            } else {
                echo "<p style='text-align:center;'>No candidates available for this position.</p>";
            }
            echo "</div>";
        }
    } else {
        echo "<p style='text-align:center; color:#d32f2f;'>No positions available. Please contact the administrator.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red; text-align:center;'>Error loading dashboard: " . $e->getMessage() . "</p>";
}
?>
</body>
</html>
