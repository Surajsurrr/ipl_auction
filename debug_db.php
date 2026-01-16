<?php
require __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
$conn = getDBConnection();
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 1;

$queries = [
    "room" => "SELECT room_id,status,current_player_id,current_auction_group,current_bid FROM auction_rooms WHERE room_id = $room_id",
    "players_count" => "SELECT COUNT(*) AS players FROM players",
    "players_sample" => "SELECT player_id,player_name,auction_group,is_sold,base_price FROM players LIMIT 10",
    "used_count" => "SELECT COUNT(*) AS used FROM room_used_players WHERE room_id = $room_id",
    "used_sample" => "SELECT player_id,is_sold FROM room_used_players WHERE room_id = $room_id LIMIT 20",
    "unsold_by_group" => "SELECT auction_group, COUNT(*) AS cnt FROM players WHERE player_id NOT IN (SELECT player_id FROM room_player_assignments WHERE room_id = $room_id) GROUP BY auction_group"
];

echo "<html><head><meta charset=\"utf-8\"><title>Debug DB - room_id=$room_id</title></head><body style=\"font-family:Segoe UI,Arial,sans-serif;\">";
echo "<h2>Debug DB - room_id=$room_id</h2>";
echo "<p>Use ?room_id=ID to change room.</p>";
echo "<pre style=\"background:#0b1220;color:#d6e6ff;padding:12px;border-radius:6px;\">";

foreach ($queries as $key => $sql) {
    echo "--- $key ---\n";
    $res = $conn->query($sql);
    if ($res) {
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        if (count($rows) === 0) {
            echo "(no rows)\n";
        } else {
            foreach ($rows as $r) {
                print_r($r);
                echo "\n";
            }
        }
    } else {
        echo "ERROR: " . $conn->error . "\n";
    }
    echo "\n";
}

echo "</pre>";

closeDBConnection($conn);

echo "</body></html>";

?>