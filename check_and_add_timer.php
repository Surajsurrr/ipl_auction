<?php
require_once 'config/database.php';

$conn = getDBConnection();

// Check if bid_timer_expires_at column exists
$check_sql = "SHOW COLUMNS FROM auction_rooms LIKE 'bid_timer_expires_at'";
$result = $conn->query($check_sql);

if ($result->num_rows == 0) {
    echo "Adding bid_timer_expires_at column...\n";
    $alter_sql = "ALTER TABLE auction_rooms ADD COLUMN bid_timer_expires_at TIMESTAMP NULL AFTER current_bidder_id";
    if ($conn->query($alter_sql)) {
        echo "✓ Successfully added bid_timer_expires_at column\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
} else {
    echo "✓ bid_timer_expires_at column already exists\n";
}

closeDBConnection($conn);
echo "\nDone! The timer feature is ready.\n";
?>
