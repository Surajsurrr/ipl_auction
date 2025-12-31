<?php
/**
 * Update Auction Groups Script
 * Updates all players' auction_group based on their base_price
 * 
 * Group Classification:
 * - Group Marquee: Special marquee players (manually assigned)
 * - Group A: >= 200 Lakh (>= 20,000,000)
 * - Group B: 100-200 Lakh (10,000,000 to < 20,000,000)
 * - Group C: < 100 Lakh (< 10,000,000)
 */

require_once __DIR__ . '/config/database.php';

echo "Starting auction group update...\n\n";

$conn = getDBConnection();

// Marquee players (do not update these)
$marquee_players = [
    'Virat Kohli', 'virat kohli', 'Rohit Sharma', 'KL Rahul', 'Shreyas Iyer',
    'Suryakumar Yadav', 'Jasprit Bumrah', 'Rishabh Pant', 'M S Dhoni', 'Hardik Pandya',
    'Shubman Gill'
];

// Build exclusion condition for marquee players
$marquee_condition = "player_name NOT IN ('" . implode("','", array_map(function($name) use ($conn) {
    return $conn->real_escape_string($name);
}, $marquee_players)) . "')";

// Group A: >= 200 Lakh (exclude marquee players)
$sql_a = "UPDATE players SET auction_group = 'A' WHERE base_price >= 20000000 AND auction_group != 'Marquee' AND $marquee_condition";
$result_a = $conn->query($sql_a);
$count_a = $conn->affected_rows;
echo "Group A (>= ₹200 Lakh): $count_a players updated\n";

// Group B: 100-200 Lakh
$sql_b = "UPDATE players SET auction_group = 'B' WHERE base_price >= 10000000 AND base_price < 20000000 AND auction_group != 'Marquee'";
$result_b = $conn->query($sql_b);
$count_b = $conn->affected_rows;
echo "Group B (₹100-200 Lakh): $count_b players updated\n";

// Group C: < 100 Lakh
$sql_c = "UPDATE players SET auction_group = 'C' WHERE base_price < 10000000 AND auction_group != 'Marquee'";
$result_c = $conn->query($sql_c);
$count_c = $conn->affected_rows;
echo "Group C (< ₹100 Lakh): $count_c players updated\n";

echo "\n--- Summary by Group ---\n";

// Get count of players in each group
$summary_sql = "SELECT auction_group, COUNT(*) as count, 
                MIN(base_price) as min_price, 
                MAX(base_price) as max_price 
                FROM players 
                GROUP BY auction_group 
                ORDER BY FIELD(auction_group, 'Marquee', 'A', 'B', 'C', 'D')";

$result = $conn->query($summary_sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $group = $row['auction_group'];
        $count = $row['count'];
        $min_price = number_format($row['min_price'] / 10000000, 2);
        $max_price = number_format($row['max_price'] / 10000000, 2);
        
        if ($group == 'Marquee') {
            echo "Group $group: $count players (Marquee Players)\n";
        } else {
            echo "Group $group: $count players (₹{$min_price} Cr - ₹{$max_price} Cr)\n";
        }
    }
}

closeDBConnection($conn);

echo "\nAuction groups updated successfully!\n";
?>
