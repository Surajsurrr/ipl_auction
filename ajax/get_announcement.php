<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
if (!$room_id) {
    echo json_encode(['success' => false, 'error' => 'Missing room_id']);
    exit;
}

$file = __DIR__ . '/../data/announcements/announcement_room_' . $room_id . '.json';
if (!file_exists($file)) {
    echo json_encode(['success' => true, 'has' => false]);
    exit;
}

$content = @file_get_contents($file);
$data = json_decode($content, true) ?: null;
if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Malformed announcement']);
    exit;
}

echo json_encode(['success' => true, 'has' => true, 'announcement' => $data]);
exit;
