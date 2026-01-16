<?php
require_once __DIR__ . '/includes/auction_room_functions.php';
require_once __DIR__ . '/config/session.php';
header('Content-Type: text/plain; charset=utf-8');
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 1;
try {
    _grf_debug("run_next_player called for room_id=$room_id");
} catch (Exception $e) { /* ignore */ }
$player = getNextPlayerForRoom($room_id, null);
if ($player) {
    echo "Selected player:\n";
    print_r($player);
} else {
    echo "No player returned by getNextPlayerForRoom().\n";
    echo "Check data/debug/getnext.log and server error logs.\n";
}

// Also show current auction_rooms row
$pdoConn = getDBConnection();
$res = $pdoConn->query("SELECT room_id,current_player_id,current_bid,current_auction_group,bid_timer_expires_at FROM auction_rooms WHERE room_id = $room_id");
if ($res) {
    echo "\nRoom row:\n";
    print_r($res->fetch_assoc());
} else {
    echo "Error fetching room row: " . $pdoConn->error . "\n";
}
closeDBConnection($pdoConn);
?>