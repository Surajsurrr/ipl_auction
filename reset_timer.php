<?php
require_once 'config/database.php';

$room_id = $_GET['room_id'] ?? 1;

$conn = getDBConnection();
$sql = "UPDATE auction_rooms SET bid_timer_expires_at = DATE_ADD(NOW(), INTERVAL 45 SECOND) WHERE room_id = $room_id";

if ($conn->query($sql)) {
    echo "Timer reset to 45 seconds for room $room_id<br>";
    echo "<a href='pages/auction-room.php?room_id=$room_id'>Back to Auction Room</a>";
} else {
    echo "Error: " . $conn->error;
}

closeDBConnection($conn);
?>
