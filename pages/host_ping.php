<?php
require_once '../config/session.php';
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

// Ensure host_last_ping column exists
$check = $conn->query("SHOW COLUMNS FROM auction_rooms LIKE 'host_last_ping'");
if (!$check || $check->num_rows == 0) {
    $conn->query("ALTER TABLE auction_rooms ADD COLUMN host_last_ping INT DEFAULT NULL");
}

$room_id_safe = $conn->real_escape_string($room_id);
$now = time();
$conn->query("UPDATE auction_rooms SET host_last_ping = $now WHERE room_id = $room_id_safe");

closeDBConnection($conn);
echo json_encode(['success' => true, 'ts' => $now]);
exit();
