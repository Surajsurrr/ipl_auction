<?php
require_once '../config/session.php';

header('Content-Type: application/json');

$room_id = $_GET['room_id'] ?? 0;
$last_check = $_GET['last_check'] ?? 0;

$eventsDir = __DIR__ . '/../data/events';
$eventFile = $eventsDir . '/events_room_' . $room_id . '.json';

if (!file_exists($eventFile)) {
    echo json_encode(['success' => true, 'events' => []]);
    exit;
}

$events = json_decode(file_get_contents($eventFile), true);
if (!$events) {
    echo json_encode(['success' => true, 'events' => []]);
    exit;
}

// Filter events newer than last_check
$newEvents = array_filter($events, function($event) use ($last_check) {
    return $event['timestamp'] > $last_check;
});

// Re-index array
$newEvents = array_values($newEvents);

echo json_encode([
    'success' => true,
    'events' => $newEvents
]);
?>
