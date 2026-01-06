<?php
require_once __DIR__ . '/../includes/auction_room_functions.php';
header('Content-Type: application/json');

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
if (!$room_id) {
    echo json_encode(['success' => false, 'error' => 'Missing room_id']);
    exit;
}

$room = getRoomById($room_id);
if (!$room) {
    echo json_encode(['success' => false, 'error' => 'Room not found']);
    exit;
}

echo json_encode(['success' => true, 'status' => $room['status']]);
exit;
