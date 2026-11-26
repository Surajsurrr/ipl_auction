<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auction_room_functions.php';
require_once __DIR__ . '/../includes/player_functions.php';

header('Content-Type: application/json');

// Ensure user is logged in
$user = getCurrentUser();
session_write_close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
$participant_id = isset($_POST['participant_id']) ? intval($_POST['participant_id']) : 0;

if (!$room_id || !$participant_id) {
    echo json_encode(['success' => false, 'message' => 'Missing room or participant information']);
    exit;
}

$room = getRoomById($room_id);
if (!$room) {
    echo json_encode(['success' => false, 'message' => 'Room not found']);
    exit;
}

if ($room['status'] === 'paused') {
    echo json_encode(['success' => false, 'message' => 'Auction is currently paused']);
    exit;
}

if (empty($room['current_player_id'])) {
    echo json_encode(['success' => false, 'message' => 'No active player for auction']);
    exit;
}

$current_player = getPlayerById($room['current_player_id']);
if (!$current_player) {
    echo json_encode(['success' => false, 'message' => 'Player not found']);
    exit;
}

// First bid is base price; subsequent bids increment by 10L or 20L
if (!$room['current_bidder_id'] && $room['current_bid'] == $current_player['base_price']) {
    $new_bid = $current_player['base_price'];
} else {
    $current_bid_cr = $room['current_bid'] / 10000000;
    $increment = ($current_bid_cr >= 3) ? 2000000 : 1000000;
    $new_bid = $room['current_bid'] + $increment;
}

$bid_result = placeBidInRoom($room_id, $participant_id, $new_bid);
if (!$bid_result['success']) {
    echo json_encode(['success' => false, 'message' => $bid_result['message']]);
    exit;
}

// Reset timer on new bid
resetBidTimer($room_id);

echo json_encode([
    'success' => true,
    'new_bid' => $new_bid,
    'new_bid_cr' => number_format($new_bid / 10000000, 2),
    'current_bidder_id' => $participant_id
]);
exit;
