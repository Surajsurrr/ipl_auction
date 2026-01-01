<?php
require_once '../config/session.php';
require_once '../includes/auction_room_functions.php';

// This endpoint handles beacon requests for auto-pause
// Beacon API doesn't work with session redirects, so we keep it simple

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = $_POST['room_id'] ?? 0;
    
    error_log("Pause beacon received for room_id: " . $room_id);
    
    if ($room_id > 0) {
        pauseAuctionRoom($room_id);
        error_log("Room $room_id paused successfully");
        echo json_encode(['success' => true]);
    } else {
        error_log("Invalid room_id: " . $room_id);
        echo json_encode(['success' => false, 'error' => 'Invalid room_id']);
    }
} else {
    error_log("Pause beacon: Invalid request method");
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
