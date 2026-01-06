<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

// Basic simple chat storage using JSON files per room
$method = $_SERVER['REQUEST_METHOD'];
$room_id = isset($_REQUEST['room_id']) ? intval($_REQUEST['room_id']) : 0;
if (!$room_id) {
    echo json_encode(['success' => false, 'error' => 'Missing room_id']);
    exit;
}

$chatDir = __DIR__ . '/../data/chat';
if (!is_dir($chatDir)) mkdir($chatDir, 0777, true);
$chatFile = $chatDir . '/chat_room_' . $room_id . '.json';

// Ensure file exists
if (!file_exists($chatFile)) file_put_contents($chatFile, json_encode([]));

if ($method === 'GET') {
    // return messages
    $raw = file_get_contents($chatFile);
    $messages = json_decode($raw, true) ?: [];
    echo json_encode(['success' => true, 'messages' => $messages]);
    exit;
}

if ($method === 'POST') {
    // require login for posting
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $text = trim($input['text'] ?? '');
    if ($text === '') {
        echo json_encode(['success' => false, 'error' => 'Empty message']);
        exit;
    }

    $user = getCurrentUser();
    $participant_id = isset($input['participant_id']) ? intval($input['participant_id']) : null;
    $team_name = $input['team_name'] ?? null;

    // Build message
    $msg = [
        'id' => time() . rand(1000,9999),
        'timestamp' => time(),
        'user_id' => $user['user_id'],
        'username' => $user['full_name'] ?? $user['username'],
        'participant_id' => $participant_id,
        'team_name' => $team_name,
        'text' => htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
    ];

    // Append to file safely
    $attempts = 0;
    do {
        $fp = fopen($chatFile, 'c+');
        if (!$fp) break;
        if (flock($fp, LOCK_EX)) {
            $contents = stream_get_contents($fp);
            $messages = json_decode($contents, true) ?: [];
            $messages[] = $msg;
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($messages));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            echo json_encode(['success' => true, 'message' => $msg]);
            exit;
        }
        if ($fp) fclose($fp);
        usleep(10000);
    } while (++$attempts < 5);

    echo json_encode(['success' => false, 'error' => 'Could not write message']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unsupported method']);
exit;
