<?php
require_once __DIR__ . '/../includes/auction_room_functions.php';

$room_id = isset($argv[1]) ? intval($argv[1]) : 1;
echo "Testing pauseAuctionRoom for room_id={$room_id}\n";
try {
    pauseAuctionRoom($room_id);
} catch (Exception $e) {
    echo "Exception while pausing: " . $e->getMessage() . "\n";
}

$conn = getDBConnection();
$rid = $conn->real_escape_string($room_id);
$res = $conn->query("SELECT room_id, status, paused_time_remaining, bid_timer_expires_at, current_player_id, current_bid FROM auction_rooms WHERE room_id = $rid");
if ($res) {
    $row = $res->fetch_assoc();
    echo "Resulting row:\n";
    print_r($row);
} else {
    echo "Failed to fetch room: " . $conn->error . "\n";
}
closeDBConnection($conn);

echo "Done.\n";

