<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auction_room_functions.php';
require_once __DIR__ . '/../includes/player_functions.php';

header('Content-Type: application/json');

$user = getCurrentUser();
session_write_close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';

if (!$room_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Missing room or action information']);
    exit;
}

$room = getRoomById($room_id);
if (!$room) {
    echo json_encode(['success' => false, 'message' => 'Room not found']);
    exit;
}

$is_host = ($room['created_by'] == $user['user_id']);
$userCheck = isUserInRoom($room_id, $user['user_id']);
$participant_id = $userCheck['participant_id'] ?? 0;

switch ($action) {
    case 'start':
        if (!$is_host) {
            echo json_encode(['success' => false, 'message' => 'Only the host can start the auction']);
            exit;
        }
        startAuctionRoom($room_id, $user['user_id']);
        getNextPlayerForRoom($room_id, null);
        echo json_encode(['success' => true]);
        exit;

    case 'pause':
        if (!$is_host) {
            echo json_encode(['success' => false, 'message' => 'Only the host can pause the auction']);
            exit;
        }
        pauseAuctionRoom($room_id);
        echo json_encode(['success' => true]);
        exit;

    case 'resume':
        if (!$is_host) {
            echo json_encode(['success' => false, 'message' => 'Only the host can resume the auction']);
            exit;
        }
        resumeAuctionRoom($room_id);
        echo json_encode(['success' => true]);
        exit;

    case 'next_player':
        $group = $_POST['group'] ?? null;
        getNextPlayerForRoom($room_id, $group);
        echo json_encode(['success' => true]);
        exit;

    case 'quit_bid':
        if ($participant_id) {
            releaseFromBiddingWar($room_id, $participant_id);
        }
        echo json_encode(['success' => true]);
        exit;

    case 'wait':
        extendBidTimer($room_id, 10);
        echo json_encode(['success' => true]);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}
