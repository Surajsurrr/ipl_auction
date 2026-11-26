<?php
require_once '../config/session.php';
session_write_close();

require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit();
}

$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
if ($room_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid room_id']);
    exit();
}

$conn = getDBConnection();
$room_id_safe = intval($room_id);
$now = time();

$res = $conn->query("UPDATE auction_rooms SET host_last_ping = $now WHERE room_id = $room_id_safe");
if (!$res && $conn->errno == 1054) {
    @$conn->query("ALTER TABLE auction_rooms ADD COLUMN host_last_ping INT DEFAULT NULL");
    @$conn->query("UPDATE auction_rooms SET host_last_ping = $now WHERE room_id = $room_id_safe");
}

echo json_encode(['success' => true, 'ts' => $now]);
exit();
