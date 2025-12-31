<?php
require_once __DIR__ . '/config/database.php';

$conn = getDBConnection();

echo "Sample players from each group:\n\n";

// Group A samples
echo "=== GROUP A (should be >= 200 Lakh) ===\n";
$result = $conn->query("SELECT player_name, base_price, auction_group FROM players WHERE auction_group = 'A' LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $lakhs = $row['base_price'] / 100000;
    echo "{$row['player_name']}: {$lakhs} Lakh (Group {$row['auction_group']})\n";
}

echo "\n=== GROUP B (should be 100-200 Lakh) ===\n";
$result = $conn->query("SELECT player_name, base_price, auction_group FROM players WHERE auction_group = 'B' LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $lakhs = $row['base_price'] / 100000;
    echo "{$row['player_name']}: {$lakhs} Lakh (Group {$row['auction_group']})\n";
}

echo "\n=== GROUP C (should be < 100 Lakh) ===\n";
$result = $conn->query("SELECT player_name, base_price, auction_group FROM players WHERE auction_group = 'C' LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $lakhs = $row['base_price'] / 100000;
    echo "{$row['player_name']}: {$lakhs} Lakh (Group {$row['auction_group']})\n";
}

echo "\n=== PRICE DISTRIBUTION ===\n";
$result = $conn->query("SELECT MIN(base_price) as min, MAX(base_price) as max, COUNT(*) as count, auction_group FROM players GROUP BY auction_group ORDER BY auction_group");
while ($row = $result->fetch_assoc()) {
    $minLakhs = $row['min'] / 100000;
    $maxLakhs = $row['max'] / 100000;
    echo "Group {$row['auction_group']}: {$row['count']} players, Range: {$minLakhs} - {$maxLakhs} Lakh\n";
}

closeDBConnection($conn);
?>
