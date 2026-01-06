<?php
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');

$user = getCurrentUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$room_id = isset($input['room_id']) ? intval($input['room_id']) : 0;
$message = isset($input['message']) ? trim($input['message']) : '';

if (!$room_id || !$message) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

// Load or create chat file
$chatDir = __DIR__ . '/../data/chat';
if (!is_dir($chatDir)) @mkdir($chatDir, 0755, true);

$chatFile = $chatDir . '/chat_room_' . $room_id . '.json';
$messages = [];
if (file_exists($chatFile)) {
    $content = file_get_contents($chatFile);
    $messages = json_decode($content, true) ?: [];
}

// Add new message
$messages[] = [
    'user_id' => $user['user_id'],
    'username' => $user['username'],
    'full_name' => $user['full_name'] ?? $user['username'],
    'message' => $message,
    'timestamp' => time()
];

// Keep only last 100 messages
if (count($messages) > 100) {
    $messages = array_slice($messages, -100);
}

file_put_contents($chatFile, json_encode($messages));

echo json_encode(['success' => true, 'message_count' => count($messages)]);
?>
