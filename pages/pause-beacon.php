<?php
require_once '../config/session.php';
require_once '../includes/auction_room_functions.php';

// This endpoint handles beacon requests for auto-pause
// Beacon API doesn't work with session redirects, so we keep it simple

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = $_POST['room_id'] ?? 0;
    
    error_log("Pause beacon received for room_id: " . $room_id);
    
    if ($room_id > 0) {
        // Check recent host heartbeat before pausing
        $conn = getDBConnection();
        $room_id_safe = $conn->real_escape_string($room_id);

        // Ensure host_last_ping column exists
        $check = $conn->query("SHOW COLUMNS FROM auction_rooms LIKE 'host_last_ping'");
        if (!$check || $check->num_rows == 0) {
            $conn->query("ALTER TABLE auction_rooms ADD COLUMN host_last_ping INT DEFAULT NULL");
        }

        $result = $conn->query("SELECT host_last_ping FROM auction_rooms WHERE room_id = $room_id_safe");
        $row = $result ? $result->fetch_assoc() : null;
        $host_last = $row && isset($row['host_last_ping']) ? intval($row['host_last_ping']) : 0;
        $now = time();
        $threshold = 6; // seconds

        if ($host_last > 0 && ($now - $host_last) <= $threshold) {
            error_log("Skipping pause for room $room_id because host ping was recent (" . ($now - $host_last) . "s)");
            echo json_encode(['success' => true, 'skipped' => true, 'reason' => 'recent_host_ping']);
            closeDBConnection($conn);
            exit();
        }

        closeDBConnection($conn);

        // No recent host ping - proceed to pause
        pauseAuctionRoom($room_id);
        error_log("Room $room_id paused successfully");
        echo json_encode(['success' => true, 'skipped' => false]);
    } else {
        error_log("Invalid room_id: " . $room_id);
        echo json_encode(['success' => false, 'error' => 'Invalid room_id']);
    }
} else {
    error_log("Pause beacon: Invalid request method");
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
