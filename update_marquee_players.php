<?php
/**
 * Update Marquee Players Script
 * Assigns specific players to the 'Marquee' auction group
 * 
 * Marquee Players (10 players):
 * 1. Virat Kohli
 * 2. Rohit Sharma
 * 3. KL Rahul
 * 4. Shreyas Iyer
 * 5. Suryakumar Yadav
 * 6. Jasprit Bumrah
 * 7. Rishabh Pant
 * 8. M S Dhoni
 * 9. Hardik Pandya
 * 10. Shubman Gill
 */

require_once __DIR__ . '/config/database.php';

echo "Starting marquee players update...\n\n";

$conn = getDBConnection();

// First, modify the auction_group column to include 'Marquee'
echo "Step 1: Updating database schema to support 'Marquee' group...\n";
$alter_sql = "ALTER TABLE players MODIFY auction_group ENUM('Marquee', 'A', 'B', 'C', 'D') NOT NULL";
if ($conn->query($alter_sql)) {
    echo "✓ Schema updated successfully\n\n";
} else {
    echo "✗ Schema update failed: " . $conn->error . "\n\n";
}

// Update auction_session table as well
$alter_session_sql = "ALTER TABLE auction_session MODIFY current_group ENUM('Marquee', 'A', 'B', 'C', 'D')";
if ($conn->query($alter_session_sql)) {
    echo "✓ Auction session table updated successfully\n\n";
} else {
    echo "✗ Auction session update failed: " . $conn->error . "\n\n";
}

// Define marquee players
$marquee_players = [
    'Virat Kohli',
    'virat kohli',  // Handle case sensitivity
    'Rohit Sharma',
    'KL Rahul',
    'Shreyas Iyer',
    'Suryakumar Yadav',
    'Jasprit Bumrah',
    'Rishabh Pant',
    'M S Dhoni',
    'Hardik Pandya',
    'Shubman Gill'
];

echo "Step 2: Assigning players to 'Marquee' group...\n";

$updated_count = 0;
$not_found = [];

foreach ($marquee_players as $player_name) {
    $escaped_name = $conn->real_escape_string($player_name);
    
    // Update player to Marquee group
    $sql = "UPDATE players SET auction_group = 'Marquee' WHERE player_name = '$escaped_name'";
    
    if ($conn->query($sql)) {
        if ($conn->affected_rows > 0) {
            echo "✓ $player_name → Marquee\n";
            $updated_count++;
        } else {
            // Try case-insensitive search
            $sql_ci = "UPDATE players SET auction_group = 'Marquee' WHERE LOWER(player_name) = LOWER('$escaped_name')";
            if ($conn->query($sql_ci) && $conn->affected_rows > 0) {
                echo "✓ $player_name → Marquee (case-insensitive match)\n";
                $updated_count++;
            } else {
                $not_found[] = $player_name;
            }
        }
    } else {
        echo "✗ Error updating $player_name: " . $conn->error . "\n";
    }
}

echo "\n--- Summary ---\n";
echo "Total marquee players updated: $updated_count\n";

if (!empty($not_found)) {
    echo "\nPlayers not found in database:\n";
    foreach ($not_found as $player) {
        echo "  - $player\n";
    }
}

// Get count of players in Marquee group
echo "\n--- Marquee Group Statistics ---\n";
$summary_sql = "SELECT COUNT(*) as count FROM players WHERE auction_group = 'Marquee'";
$result = $conn->query($summary_sql);

if ($result) {
    $row = $result->fetch_assoc();
    echo "Total players in Marquee group: " . $row['count'] . "\n";
    
    // List all marquee players
    echo "\nMarquee Players:\n";
    $list_sql = "SELECT player_name, player_type, player_role, base_price FROM players WHERE auction_group = 'Marquee' ORDER BY player_name";
    $list_result = $conn->query($list_sql);
    
    if ($list_result) {
        $index = 1;
        while ($player = $list_result->fetch_assoc()) {
            $price = number_format($player['base_price'] / 10000000, 2);
            echo "$index. {$player['player_name']} ({$player['player_role']}) - ₹{$price} Cr\n";
            $index++;
        }
    }
}

// Show distribution across all groups
echo "\n--- All Groups Distribution ---\n";
$dist_sql = "SELECT auction_group, COUNT(*) as count FROM players GROUP BY auction_group ORDER BY FIELD(auction_group, 'Marquee', 'A', 'B', 'C', 'D')";
$dist_result = $conn->query($dist_sql);

if ($dist_result) {
    while ($row = $dist_result->fetch_assoc()) {
        echo "Group {$row['auction_group']}: {$row['count']} players\n";
    }
}

closeDBConnection($conn);

echo "\nMarquee players update completed!\n";
?>
