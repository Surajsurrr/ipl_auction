<?php
require_once '../config/session.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$group = isset($_GET['group']) ? trim($_GET['group']) : '';

if ($room_id <= 0 || $group == '') {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit();
}

$conn = getDBConnection();
$room_id_safe = $conn->real_escape_string($room_id);
$group_safe = $conn->real_escape_string($group);

if (strtolower($group_safe) === 'accelerated') {
    // Accelerated: previously used but unsold players (include Marquee as user requested)
    $sql = "SELECT p.player_id, p.player_name, p.auction_group, p.base_price, rup.is_sold
            FROM players p
            JOIN room_used_players rup ON p.player_id = rup.player_id
            WHERE rup.room_id = $room_id_safe
              AND rup.is_sold = 0
              AND p.player_id NOT IN (
                  SELECT player_id FROM room_player_assignments WHERE room_id = $room_id_safe
              )
            ORDER BY p.auction_group, p.player_name";
} else {
    // Normal group: players not yet used in this room and belonging to the group
    $sql = "SELECT p.player_id, p.player_name, p.auction_group, p.base_price, 0 as is_sold
            FROM players p
            WHERE p.player_id NOT IN (
                SELECT player_id FROM room_used_players WHERE room_id = $room_id_safe
            )
            AND p.auction_group = '$group_safe'
            ORDER BY p.player_name";
}

$result = $conn->query($sql);
$players = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['base_price'] = intval($row['base_price']);
        $players[] = $row;
    }
}

closeDBConnection($conn);
echo json_encode(['success' => true, 'group' => $group, 'count' => count($players), 'players' => $players]);
exit();
