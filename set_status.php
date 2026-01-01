<?php
require_once 'config/database.php';

$room_id = $_GET['room_id'] ?? 1;

$conn = getDBConnection();

// Set status to in_progress
$sql = "UPDATE auction_rooms SET status = 'in_progress' WHERE room_id = $room_id";
$conn->query($sql);

echo "Status set to 'in_progress' for room $room_id<br>";
echo "<a href='pages/auction-room.php?room_id=$room_id'>Back to Auction Room</a>";

closeDBConnection($conn);
?>
