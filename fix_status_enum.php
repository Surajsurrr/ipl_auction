<?php
require_once 'config/database.php';

$conn = getDBConnection();

echo "Adding 'paused' to status ENUM...\n";

$result = $conn->query("ALTER TABLE auction_rooms MODIFY COLUMN status ENUM('waiting', 'in_progress', 'paused', 'completed') DEFAULT 'waiting'");

if ($result) {
    echo "✓ ENUM updated successfully!\n\n";
    
    // Now set the status to paused
    $conn->query("UPDATE auction_rooms SET status = 'paused', paused_time_remaining = 25 WHERE room_id = 1");
    echo "✓ Status set to 'paused'\n\n";
    
    // Verify
    $result = $conn->query("SELECT status, paused_time_remaining FROM auction_rooms WHERE room_id = 1");
    $row = $result->fetch_assoc();
    echo "Verified:\n";
    echo "  Status: [" . $row['status'] . "]\n";
    echo "  Paused time: " . $row['paused_time_remaining'] . " seconds\n";
} else {
    echo "✗ Error: " . $conn->error . "\n";
}

closeDBConnection($conn);
