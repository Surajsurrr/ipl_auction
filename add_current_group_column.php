<?php
require_once 'config/database.php';

$conn = getDBConnection();

// Add current_auction_group column if it doesn't exist
$check = $conn->query("SHOW COLUMNS FROM auction_rooms LIKE 'current_auction_group'");
if ($check && $check->num_rows == 0) {
    echo "Adding current_auction_group column...\n";
    $sql = "ALTER TABLE auction_rooms ADD COLUMN current_auction_group VARCHAR(20) DEFAULT 'Marquee' AFTER status";
    if ($conn->query($sql)) {
        echo "✓ current_auction_group column added\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
} else {
    echo "✓ current_auction_group column already exists\n";
}

closeDBConnection($conn);
echo "Done!\n";
?>
