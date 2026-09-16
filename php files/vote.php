<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

if (isset($_POST['cid']) && isset($_POST['position'])) {
    $user_id = $_SESSION['user_id'];
    $cid     = intval($_POST['cid']);
    $position = $_POST['position'];

    try {
        // Check if user already voted
        $stmt_check = $conn->prepare('SELECT id FROM votes WHERE user_id = ? AND position = ?');
        $stmt_check->execute([$user_id, $position]);
        $already_voted = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$already_voted) {
            // Insert vote
            $stmt_insert = $conn->prepare('
                INSERT INTO votes(user_id, candidate_id, position)
                VALUES(?, ?, ?)
            ');
            $stmt_insert->execute([$user_id, $cid, $position]);

            header("Location: dashboard.php?success=Your vote has been recorded successfully!");
            exit;
        } else {
            header("Location: dashboard.php?error=You have already voted for this position!");
            exit;
        }
    } catch (PDOException $e) {
        header("Location: dashboard.php?error=Error recording vote: " . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: dashboard.php?error=Invalid request!");
    exit;
}
