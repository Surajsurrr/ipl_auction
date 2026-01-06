<?php
require_once 'config/database.php';

$conn = getDBConnection();

// Check table structure
$result = $conn->query("DESCRIBE teams");
echo "Teams table structure:\n";
echo "=====================\n";
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n\nTeam data:\n";
echo "==========\n";
$result = $conn->query("SELECT * FROM teams ORDER BY team_name");
while($row = $result->fetch_assoc()) {
    echo $row['team_name'] . "\n";
    print_r($row);
    echo "\n";
}

closeDBConnection($conn);

