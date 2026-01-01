<?php
require_once 'config/database.php';

$conn = getDBConnection();

echo "<h2>Fixing Room Status</h2>";

// Set status to in_progress for rooms with current player but no status
$sql = "UPDATE auction_rooms 
        SET status = 'in_progress' 
        WHERE (status IS NULL OR status = '') 
        AND current_player_id IS NOT NULL";

if ($conn->query($sql)) {
    echo "<p style='color: green;'>✓ Room status fixed</p>";
    echo "<p>Affected rooms: " . $conn->affected_rows . "</p>";
} else {
    echo "<p style='color: red;'>Error: " . $conn->error . "</p>";
}

closeDBConnection($conn);

echo "<p><a href='pages/auction-room.php?room_id=1'>Back to Auction Room</a></p>";
?>
