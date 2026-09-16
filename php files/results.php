<?php
session_start();
include 'db.php';

// Ensure user is logged in and is user
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

include __DIR__ . '/Header.php';
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="results-container">
<h1>🗳️ Election Results</h1>

<?php
try {
    // Fetch distinct positions
    $stmt_positions = $conn->prepare('SELECT DISTINCT position FROM candidates ORDER BY position');
    $stmt_positions->execute();
    $positions = $stmt_positions->fetchAll(PDO::FETCH_ASSOC);

    if ($positions) {
        $has_results = false;

        foreach ($positions as $p) {
            $position = $p['position'] ?? '';
            if (!$position) continue;

            echo "<div class='position-title'>" . htmlspecialchars($position) . "</div>";

            // Fetch candidates and votes for this position
            $stmt_cands = $conn->prepare('
                SELECT c.id, c.name, c.photo, c.position, COUNT(v.id) AS total_votes
                FROM candidates c
                LEFT JOIN votes v ON c.id = v.candidate_id
                WHERE c.position = ?
                GROUP BY c.id, c.name, c.photo, c.position
                ORDER BY total_votes DESC, c.name ASC
            ');
            $stmt_cands->execute([$position]);
            $candidates = $stmt_cands->fetchAll(PDO::FETCH_ASSOC);

            if ($candidates) {
                $has_results = true;
                foreach ($candidates as $c) {
                    $c_name = htmlspecialchars($c['name'] ?? '');
                    $c_photo = htmlspecialchars($c['photo'] ?? '');
                    $c_position = htmlspecialchars($c['position'] ?? '');
                    $c_votes = intval($c['total_votes'] ?? 0);

                    echo "<div class='candidate-card'>
                            <img src='{$c_photo}' alt='{$c_name}'>
                            <div class='candidate-info'>
                                <div><b>{$c_name}</b></div>
                                <div>Position: {$c_position}</div>
                            </div>
                            <div class='vote-count'>{$c_votes} vote" . ($c_votes != 1 ? 's' : '') . "</div>
                          </div>";
                }
            } else {
                echo "<p style='text-align:center;'>No candidates for this position yet.</p>";
            }
        }

        if (!$has_results) {
            echo "<p style='text-align:center; margin-top:20px;'>No voting data available yet.</p>";
        }
    } else {
        echo "<p style='text-align:center; margin-top:20px;'>No positions found. Please contact administrator.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='text-align:center; color:red;'>Error loading results: " . $e->getMessage() . "</p>";
}
?>
</div>
</body>
</html>
