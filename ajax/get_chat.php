<?php
require_once __DIR__ . '/../config/session.php';

$user = getCurrentUser();
session_write_close();

header('Content-Type: application/json');

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

if (!$room_id) {
    echo json_encode(['success' => false, 'error' => 'Missing room_id']);
    exit;
}

$chatFile = __DIR__ . '/../data/chat/chat_room_' . $room_id . '.json';
$messages = [];

if (file_exists($chatFile)) {
    $content = @file_get_contents($chatFile);
    $messages = json_decode($content, true) ?: [];
}

echo json_encode([
    'success' => true,
    'messages' => $messages
]);
exit;
