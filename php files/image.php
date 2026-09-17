<?php
include 'db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit;
}

$stmt = $conn->prepare('SELECT photo_data, photo_mime, photo FROM candidates WHERE id = ?');
$stmt->execute([$id]);
$candidate = $stmt->fetch();

if (!$candidate) {
    http_response_code(404);
    exit;
}

header('Cache-Control: public, max-age=86400');
if (!empty($candidate['photo_data'])) {
    header('Content-Type: ' . ($candidate['photo_mime'] ?: 'image/jpeg'));
    echo is_resource($candidate['photo_data']) ? stream_get_contents($candidate['photo_data']) : $candidate['photo_data'];
    exit;
}

$legacy_path = __DIR__ . '/' . ltrim((string) $candidate['photo'], '/');
if (is_file($legacy_path)) {
    $mime = mime_content_type($legacy_path) ?: 'image/jpeg';
    header('Content-Type: ' . $mime);
    readfile($legacy_path);
    exit;
}

http_response_code(404);