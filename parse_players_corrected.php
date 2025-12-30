<?php
// Parse IPL 2025 CSV and generate SQL with correct grouping
$csvFile = __DIR__ . '/1731674068078_TATA IPL 2025- Auction List -15.11.24.csv';
$outputFile = __DIR__ . '/database/players_corrected.sql';

$file = fopen($csvFile, 'r');
$players = [];
$header = fgetcsv($file);

echo "CSV Headers:\n";
print_r($header);
echo "\n\n";

$validPlayers = 0;
$skippedRows = 0;

while (($row = fgetcsv($file)) !== false) {
    // Skip rows with insufficient data
    if (count($row) < 22) {
        $skippedRows++;
        continue;
    }
    
    // Extract columns - based on CSV structure
    $firstName = isset($row[3]) ? trim($row[3]) : '';
    $surname = isset($row[4]) ? trim($row[4]) : '';
    $country = isset($row[5]) ? trim($row[5]) : '';
    $age = isset($row[8]) ? trim($row[8]) : '';
    $specialism = isset($row[9]) ? trim($row[9]) : '';
    $cappedUncapped = isset($row[20]) ? trim($row[20]) : '';
    $priceLakhs = isset($row[21]) ? trim($row[21]) : '';
    
    // Skip if any essential field is empty
    if (empty($firstName) || empty($surname) || empty($country) || 
        empty($age) || empty($specialism) || empty($priceLakhs)) {
        $skippedRows++;
        continue;
    }
    
    // Skip header rows or invalid data
    if ($firstName == 'First Name' || !is_numeric($age) || !is_numeric($priceLakhs)) {
        $skippedRows++;
        continue;
    }
    
    $playerName = trim($firstName . ' ' . $surname);
    
    // Convert price from lakhs to rupees
    $basePrice = intval($priceLakhs) * 100000;
    
    // Determine auction group based on price in lakhs
    if (intval($priceLakhs) == 200) {
        $auctionGroup = 'A';  // Exactly 200 lakhs
    } elseif (intval($priceLakhs) > 100 && intval($priceLakhs) < 200) {
        $auctionGroup = 'B';  // Between 100-200 lakhs
    } else {
        $auctionGroup = 'C';  // Less than 100 lakhs
    }
    
    // Determine player type
    $isIndian = (strtolower($country) == 'india');
    $isCapped = (strtoupper($cappedUncapped) == 'C');
    
    if ($isIndian && !$isCapped) {
        $playerType = 'Indian Uncapped';
    } elseif ($isIndian && $isCapped) {
        $playerType = 'Indian';
    } elseif (!$isIndian && !$isCapped) {
        $playerType = 'Overseas Uncapped';
    } else {
        $playerType = 'Overseas';
    }
    
    // Map specialism to role
    $specialism = strtolower($specialism);
    if (strpos($specialism, 'bat') !== false) {
        $role = 'Batsman';
    } elseif (strpos($specialism, 'bowl') !== false) {
        $role = 'Bowler';
    } elseif (strpos($specialism, 'all') !== false) {
        $role = 'All-Rounder';
    } elseif (strpos($specialism, 'wk') !== false || strpos($specialism, 'keeper') !== false) {
        $role = 'Wicket-Keeper';
    } else {
        $role = 'All-Rounder';
    }
    
    $players[] = [
        'name' => $playerName,
        'type' => $playerType,
        'role' => $role,
        'base_price' => $basePrice,
        'group' => $auctionGroup,
        'nationality' => $country,
        'age' => intval($age)
    ];
    
    $validPlayers++;
}

fclose($file);

// Sort players by group then name
usort($players, function($a, $b) {
    if ($a['group'] == $b['group']) {
        return strcmp($a['name'], $b['name']);
    }
    return strcmp($a['group'], $b['group']);
});

// Count by group
$groupCounts = ['A' => 0, 'B' => 0, 'C' => 0];
foreach ($players as $p) {
    $groupCounts[$p['group']]++;
}

// Generate SQL
$sql = "-- IPL 2025 Real Players from Official Auction List\n";
$sql .= "-- Total: " . count($players) . " players\n";
$sql .= "-- Group A (200 Lakhs): " . $groupCounts['A'] . " players\n";
$sql .= "-- Group B (100-200 Lakhs): " . $groupCounts['B'] . " players\n";
$sql .= "-- Group C (<100 Lakhs): " . $groupCounts['C'] . " players\n";
$sql .= "-- Skipped rows: $skippedRows (incomplete data)\n\n";

foreach ($players as $player) {
    $name = addslashes($player['name']);
    $type = $player['type'];
    $role = $player['role'];
    $basePrice = $player['base_price'];
    $group = $player['group'];
    $nationality = addslashes($player['nationality']);
    $age = $player['age'];
    
    $sql .= "INSERT INTO players (player_name, player_type, player_role, base_price, auction_group, nationality, age) VALUES ";
    $sql .= "('$name', '$type', '$role', $basePrice, '$group', '$nationality', $age);\n";
}

file_put_contents($outputFile, $sql);

echo "Successfully parsed $validPlayers players!\n";
echo "Skipped $skippedRows rows (incomplete data)\n";
echo "Group A (200 Lakhs): " . $groupCounts['A'] . " players\n";
echo "Group B (100-200 Lakhs): " . $groupCounts['B'] . " players\n";
echo "Group C (<100 Lakhs): " . $groupCounts['C'] . " players\n";
echo "SQL file generated: database/players_corrected.sql\n";
?>
