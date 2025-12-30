<?php
/**
 * Update Auction Groups Script
 * Updates all players' auction_group based on their base_price
 * 
 * Group Classification:
 * - Group A: > 2 Crore (> 20,000,000)
 * - Group B: 1-2 Crore (10,000,000 - 20,000,000)
 * - Group C: < 1 Crore (< 10,000,000)
 */

require_once __DIR__ . '/config/database.php';

echo "Starting auction group update...\n\n";

$conn = getDBConnection();

// Group A: > 2 Crore
$sql_a = "UPDATE players SET auction_group = 'A' WHERE base_price > 20000000";
$result_a = $conn->query($sql_a);
$count_a = $conn->affected_rows;
echo "Group A (> 2 Crore): $count_a players updated\n";

// Group B: 1-2 Crore
$sql_b = "UPDATE players SET auction_group = 'B' WHERE base_price >= 10000000 AND base_price <= 20000000";
$result_b = $conn->query($sql_b);
$count_b = $conn->affected_rows;
echo "Group B (1-2 Crore): $count_b players updated\n";

// Group C: < 1 Crore
$sql_c = "UPDATE players SET auction_group = 'C' WHERE base_price < 10000000";
$result_c = $conn->query($sql_c);
$count_c = $conn->affected_rows;
echo "Group C (< 1 Crore): $count_c players updated\n";

echo "\n--- Summary by Group ---\n";

// Get count of players in each group
$summary_sql = "SELECT auction_group, COUNT(*) as count, 
                MIN(base_price) as min_price, 
                MAX(base_price) as max_price 
                FROM players 
                GROUP BY auction_group 
                ORDER BY auction_group";

$result = $conn->query($summary_sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $group = $row['auction_group'];
        $count = $row['count'];
        $min_price = number_format($row['min_price'] / 10000000, 2);
        $max_price = number_format($row['max_price'] / 10000000, 2);
        
        echo "Group $group: $count players (₹{$min_price} Cr - ₹{$max_price} Cr)\n";
    }
}

closeDBConnection($conn);

echo "\nAuction groups updated successfully!\n";
?>
