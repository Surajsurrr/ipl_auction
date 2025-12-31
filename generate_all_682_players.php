<?php
// IPL 2025 Auction - Generate SQL for all 682 players

function cleanName($name) {
    return trim($name);
}

function determinePlayerType($country, $capStatus) {
    $country = trim($country);
    $capStatus = trim($capStatus);
    
    $isIndian = $country === "India";
    $isUncapped = $capStatus === "Uncapped";
    
    if ($isIndian && $isUncapped) {
        return "Indian Uncapped";
    } elseif ($isIndian) {
        return "Indian";
    } elseif ($isUncapped) {
        return "Overseas Uncapped";
    } else {
        return "Overseas";
    }
}

function mapRole($specialism) {
    $spec = strtoupper(trim($specialism));
    
    if (strpos($spec, "WICKETKEEPER") !== false || strpos($spec, "WICKET-KEEPER") !== false) {
        return "Wicket-Keeper";
    } elseif (strpos($spec, "BATTER") !== false || strpos($spec, "BATSMAN") !== false) {
        return "Batsman";
    } elseif (strpos($spec, "BOWLER") !== false) {
        return "Bowler";
    } elseif (strpos($spec, "ALL-ROUNDER") !== false) {
        return "All-Rounder";
    } else {
        return "All-Rounder";
    }
}

function determineAuctionGroup($basePrice) {
    if ($basePrice >= 20000000) { // >= 2 Crore
        return 'A';
    } elseif ($basePrice >= 10000000) { // 1 to < 2 Crore
        return 'B';
    } else { // < 1 Crore
        return 'C';
    }
}

function escapeSqlString($str) {
    return str_replace("'", "''", $str);
}

// Read CSV file
$csvPath = __DIR__ . '/1731674068078_TATA IPL 2025- Auction List -15.11.24.csv';
$outputPath = __DIR__ . '/database/all_682_players.sql';

$sqlStatements = [];
$playerCount = 0;
$groupCounts = ['A' => 0, 'B' => 0, 'C' => 0];

if (($handle = fopen($csvPath, "r")) !== FALSE) {
    $rowNum = 0;
    
    while (($row = fgetcsv($handle, 10000, ",")) !== FALSE) {
        $rowNum++;
        
        // Skip first 4 header rows
        if ($rowNum <= 4) {
            continue;
        }
        
        try {
            // Detect the CSV format by checking if column 2 is empty
            // Format 1: Has Set number in column 2, prices at columns 19/20
            // Format 2: Empty column 2, prices at columns 17/18
            $hasSetNumber = !empty(trim($row[2]));
            
            if ($hasSetNumber) {
                // Format 1: Original format
                $firstName = isset($row[3]) ? cleanName($row[3]) : "";
                $surname = isset($row[4]) ? cleanName($row[4]) : "";
                $country = isset($row[5]) ? trim($row[5]) : "India";
                $ageStr = isset($row[8]) ? trim($row[8]) : "25";
                $specialism = isset($row[9]) ? trim($row[9]) : "";
                $capStatus = isset($row[19]) ? trim($row[19]) : "Capped";
                $priceRsStr = isset($row[20]) ? trim($row[20]) : "";
            } else {
                // Format 2: Modified format (from row 575 onwards)
                $firstName = isset($row[3]) ? cleanName($row[3]) : "";
                $surname = isset($row[4]) ? cleanName($row[4]) : "";
                $country = isset($row[5]) ? trim($row[5]) : "India";
                $ageStr = isset($row[8]) ? trim($row[8]) : "25";
                $specialism = isset($row[9]) ? trim($row[9]) : "";
                $capStatus = isset($row[17]) ? trim($row[17]) : "Capped";
                $priceRsStr = isset($row[18]) ? trim($row[18]) : "";
            }
            
            // Skip empty rows
            if (empty($firstName) || empty($surname)) {
                continue;
            }
            
            // Player Name
            $playerName = escapeSqlString($firstName . " " . $surname);
            
            // Player Type
            $playerType = determinePlayerType($country, $capStatus);
            
            // Player Role
            $playerRole = mapRole($specialism);
            
            // Base Price
            if (!empty($priceRsStr) && is_numeric($priceRsStr)) {
                $basePrice = (int)(floatval($priceRsStr) * 100000);
            } else {
                $basePrice = 20000000;
            }
            
            // Auction Group
            $auctionGroup = determineAuctionGroup($basePrice);
            
            // Nationality
            $nationality = escapeSqlString(!empty($country) ? trim($country) : "India");
            
            // Age
            if (!empty($ageStr) && is_numeric($ageStr)) {
                $age = (int)$ageStr;
            } else {
                $age = 25;
            }
            
            // Generate SQL INSERT statement
            $sql = "INSERT INTO players (player_name, player_type, player_role, base_price, auction_group, nationality, age) VALUES ('$playerName', '$playerType', '$playerRole', $basePrice, '$auctionGroup', '$nationality', $age);";
            $sqlStatements[] = $sql;
            
            // Update counts
            $playerCount++;
            $groupCounts[$auctionGroup]++;
            
        } catch (Exception $e) {
            echo "Error processing row $rowNum: " . $e->getMessage() . "\n";
            continue;
        }
    }
    
    fclose($handle);
}

// Write to SQL file
$output = "-- IPL 2025 Auction - All Players\n";
$output .= "-- Total Players: $playerCount\n";
$output .= "-- Group A (>= 2 Cr): {$groupCounts['A']} players\n";
$output .= "-- Group B (1 to <2 Cr): {$groupCounts['B']} players\n";
$output .= "-- Group C (< 1 Cr): {$groupCounts['C']} players\n";
$output .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";

// First, delete all existing players
$output .= "DELETE FROM players;\n\n";

foreach ($sqlStatements as $sql) {
    $output .= $sql . "\n";
}

file_put_contents($outputPath, $output);

echo "✓ Successfully processed $playerCount players\n\n";
echo "Distribution across auction groups:\n";
echo "  Group A (>= 2 Cr):  {$groupCounts['A']} players\n";
echo "  Group B (1 to <2 Cr):  {$groupCounts['B']} players\n";
echo "  Group C (< 1 Cr):  {$groupCounts['C']} players\n\n";
echo "SQL file saved to: $outputPath\n";
?>
