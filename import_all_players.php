<?php
/**
 * Import all 682 players into the database
 */

require_once __DIR__ . '/config/database.php';

echo "Starting player import...\n\n";

$conn = getDBConnection();

// First, delete all existing players
echo "Clearing existing players...\n";
$sql = "DELETE FROM players";
if ($conn->query($sql)) {
    echo "✓ Existing players cleared\n\n";
} else {
    echo "✗ Error clearing players: " . $conn->error . "\n";
    exit;
}

// Reset auto increment
$conn->query("ALTER TABLE players AUTO_INCREMENT = 1");

// Read and execute the SQL file
$sqlFile = __DIR__ . '/database/all_682_players.sql';
echo "Reading SQL file: $sqlFile\n";

if (!file_exists($sqlFile)) {
    echo "✗ SQL file not found!\n";
    exit;
}

$sqlContent = file_get_contents($sqlFile);
$statements = explode(";\n", $sqlContent);

$successCount = 0;
$errorCount = 0;

echo "Importing players...\n";

foreach ($statements as $statement) {
    $statement = trim($statement);
    
    // Skip empty statements and comments
    if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, 'DELETE FROM') === 0) {
        continue;
    }
    
    if ($conn->query($statement)) {
        $successCount++;
        if ($successCount % 100 == 0) {
            echo "  Imported $successCount players...\n";
        }
    } else {
        $errorCount++;
        if ($errorCount <= 5) {
            echo "  Error: " . $conn->error . "\n";
        }
    }
}

echo "\n";
echo "=================================\n";
echo "Import Complete!\n";
echo "=================================\n";
echo "✓ Successfully imported: $successCount players\n";

if ($errorCount > 0) {
    echo "✗ Errors: $errorCount\n";
}

// Get final statistics
echo "\nFinal Statistics:\n";
echo "---------------------------------\n";

$result = $conn->query("SELECT COUNT(*) as total FROM players");
$row = $result->fetch_assoc();
echo "Total players in database: " . $row['total'] . "\n";

$result = $conn->query("SELECT auction_group, COUNT(*) as count FROM players GROUP BY auction_group ORDER BY auction_group");
echo "\nPlayers by group:\n";
while ($row = $result->fetch_assoc()) {
    $desc = '';
    if ($row['auction_group'] == 'A') $desc = ' (> ₹2 Cr)';
    if ($row['auction_group'] == 'B') $desc = ' (₹1-2 Cr)';
    if ($row['auction_group'] == 'C') $desc = ' (< ₹1 Cr)';
    echo "  Group {$row['auction_group']}$desc: {$row['count']} players\n";
}

$result = $conn->query("SELECT player_type, COUNT(*) as count FROM players GROUP BY player_type ORDER BY player_type");
echo "\nPlayers by type:\n";
while ($row = $result->fetch_assoc()) {
    echo "  {$row['player_type']}: {$row['count']} players\n";
}

closeDBConnection($conn);

echo "\n✓ All players imported successfully!\n";
?>
