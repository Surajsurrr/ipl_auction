<?php
require_once 'config/database.php';

$conn = getDBConnection();

// Check and add columns for last sale display
$cols = [
    "last_sold_player_id INT NULL",
    "last_sold_price BIGINT NULL",
    "last_sold_participant_id INT NULL",
    "last_sold_at TIMESTAMP NULL",
    "last_sale_show_until TIMESTAMP NULL"
];

foreach ($cols as $colDef) {
    preg_match('/^([a-z_]+)/', $colDef, $m);
    $col = $m[1];
    $check = $conn->query("SHOW COLUMNS FROM auction_rooms LIKE '$col'");
    if ($check && $check->num_rows == 0) {
        echo "Adding column $col...\n";
        $alter = "ALTER TABLE auction_rooms ADD COLUMN $colDef AFTER bid_timer_expires_at";
        if ($conn->query($alter)) {
            echo "  ✓ $col added\n";
        } else {
            echo "  ✗ failed to add $col: " . $conn->error . "\n";
        }
    } else {
        echo "Column $col already exists\n";
    }
}

closeDBConnection($conn);
echo "Done.\n";

?>
