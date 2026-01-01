<?php
require_once 'config/database.php';

$conn = getDBConnection();

// Manually set status to paused
$conn->query("UPDATE auction_rooms SET status = 'paused', paused_time_remaining = 25 WHERE room_id = 1");
echo "Status set to paused\n";

// Verify
$result = $conn->query("SELECT status, paused_time_remaining FROM auction_rooms WHERE room_id = 1");
$row = $result->fetch_assoc();
echo "Verified - Status: [" . $row['status'] . "], Paused time: " . $row['paused_time_remaining'] . "\n";

closeDBConnection($conn);
