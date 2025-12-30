<?php
// Parse all 682 players from IPL 2025 Auction CSV

$csvFile = 'C:\\xampp\\htdocs\\ipl_auction\\1731674068078_TATA IPL 2025- Auction List -15.11.24.csv';
$players = [];

if (($handle = fopen($csvFile, "r")) !== FALSE) {
    $lineNum = 0;
    
    while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
        $lineNum++;
        
        // Skip header rows and empty rows
        if ($lineNum <= 4 || empty($data[3]) || empty($data[4])) {
            continue;
        }
        
        $firstName = trim($data[3]);
        $surname = trim($data[4]);
        $country = trim($data[5]);
        $age = trim($data[8]);
        $role = trim($data[9]);
        $cappedStatus = trim($data[20]); // C/U/A column
        $basePrice = trim($data[21]); // Price in Lakhs
        
        // Skip if essential data missing
        if (empty($firstName) || empty($surname)) {
            continue;
        }
        
        $playerName = $firstName . ' ' . $surname;
        
        // Determine player type based on country and capped status
        $playerType = '';
        if ($country === 'India') {
            if ($cappedStatus === 'Uncapped') {
                $playerType = 'Indian Uncapped';
            } else {
                $playerType = 'Indian';
            }
        } else if ($cappedStatus === 'Associate') {
            $playerType = 'Overseas';
        } else {
            if ($cappedStatus === 'Uncapped') {
                $playerType = 'Overseas Uncapped';
            } else {
                $playerType = 'Overseas';
            }
        }
        
        // Determine role
        $playerRole = 'All-Rounder';
        $roleUpper = strtoupper($role);
        if (strpos($roleUpper, 'BATTER') !== false || strpos($roleUpper, 'BATSMAN') !== false) {
            $playerRole = 'Batsman';
        } elseif (strpos($roleUpper, 'BOWLER') !== false) {
            $playerRole = 'Bowler';
        } elseif (strpos($roleUpper, 'WICKETKEEPER') !== false || strpos($roleUpper, 'WICKET-KEEPER') !== false) {
            $playerRole = 'Wicket-Keeper';
        } elseif (strpos($roleUpper, 'ALL-ROUNDER') !== false || strpos($roleUpper, 'ALLROUNDER') !== false) {
            $playerRole = 'All-Rounder';
        }
        
        // Convert price from Lakhs to base price in rupees (Lakh * 100,000)
        $basePriceRupees = 20000000; // Default 2 Cr
        if (is_numeric($basePrice)) {
            $basePriceRupees = intval($basePrice) * 100000;
        }
        
        // Determine auction group based on base price
        $auctionGroup = 'D';
        if ($basePriceRupees >= 20000000) { // 2 Cr or more
            $auctionGroup = 'A';
        } elseif ($basePriceRupees >= 12500000) { // 1.25 Cr - 2 Cr
            $auctionGroup = 'B';
        } elseif ($basePriceRupees >= 7500000) { // 75L - 1.25 Cr
            $auctionGroup = 'C';
        }
        
        $players[] = [
            'name' => addslashes($playerName),
            'type' => $playerType,
            'role' => $playerRole,
            'base_price' => $basePriceRupees,
            'group' => $auctionGroup,
            'nationality' => $country === 'India' ? 'India' : addslashes($country),
            'age' => is_numeric($age) ? intval($age) : 25
        ];
    }
    
    fclose($handle);
}

echo "-- IPL 2025 Complete Player List (" . count($players) . " players)\n";
echo "-- Parsed from official TATA IPL 2025 Auction List\n\n";
echo "INSERT INTO players (player_name, player_type, player_role, base_price, auction_group, nationality, age) VALUES\n";

foreach ($players as $index => $player) {
    $comma = ($index < count($players) - 1) ? ',' : ';';
    echo "('{$player['name']}', '{$player['type']}', '{$player['role']}', {$player['base_price']}, '{$player['group']}', '{$player['nationality']}', {$player['age']}){$comma}\n";
}

echo "\n-- Total Players: " . count($players) . "\n";

// Group statistics
$groupCounts = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
foreach ($players as $player) {
    $groupCounts[$player['group']]++;
}

echo "-- Group A (Premium 2Cr+): {$groupCounts['A']} players\n";
echo "-- Group B (Star 1.25-2Cr): {$groupCounts['B']} players\n";
echo "-- Group C (Mid-tier 75L-1.25Cr): {$groupCounts['C']} players\n";
echo "-- Group D (Budget <75L): {$groupCounts['D']} players\n";
?>
