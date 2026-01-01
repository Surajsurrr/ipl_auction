<?php
require_once 'config/database.php';

$conn = getDBConnection();

echo "<h2>Setting up Pause Feature</h2>";

// Add paused_time_remaining column
$sql = "ALTER TABLE auction_rooms 
        ADD COLUMN IF NOT EXISTS paused_time_remaining INT DEFAULT 0 
        COMMENT 'Timer seconds remaining when auction was paused'";

if ($conn->query($sql)) {
    echo "<p style='color: green;'>✓ Column added successfully</p>";
} else {
    echo "<p style='color: orange;'>Note: " . $conn->error . "</p>";
}

closeDBConnection($conn);

echo "<p><a href='pages/my-auctions.php'>Go to My Auctions</a></p>";
?>
