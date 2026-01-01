<?php
require_once 'config/database.php';

$conn = getDBConnection();

// Check if columns exist
$check_sql = "SHOW COLUMNS FROM auction_rooms LIKE 'bidding_war_player1_id'";
$result = $conn->query($check_sql);

if ($result->num_rows == 0) {
    // Add the columns
    $sql = "ALTER TABLE auction_rooms 
            ADD COLUMN bidding_war_player1_id INT DEFAULT NULL,
            ADD COLUMN bidding_war_player2_id INT DEFAULT NULL,
            ADD COLUMN bidding_war_player1_bid BIGINT DEFAULT NULL,
            ADD COLUMN bidding_war_player2_bid BIGINT DEFAULT NULL";
    
    if ($conn->query($sql)) {
        echo "✓ Added bidding war tracking columns to auction_rooms table<br>";
    } else {
        echo "✗ Error adding columns: " . $conn->error . "<br>";
    }
} else {
    echo "✓ Bidding war columns already exist<br>";
}

closeDBConnection($conn);
echo "<br>Migration complete!";
?>
