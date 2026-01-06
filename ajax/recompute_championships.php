<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$user = getCurrentUser();
if (!$user || intval($user['user_id']) !== 1) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$annDir = __DIR__ . '/../data/announcements';
$counts = [];
if (is_dir($annDir)) {
    $files = glob($annDir . '/announcement_room_*.json');
    foreach ($files as $f) {
        $c = @file_get_contents($f);
        $d = json_decode($c, true);
        if (!$d) continue;
        $team = $d['winner_team'] ?? null;
        if (!$team) continue;
        if (!isset($counts[$team])) $counts[$team] = 0;
        $counts[$team]++;
    }
}

$conn = getDBConnection();
// Ensure championships column exists
$conn->query("ALTER TABLE teams ADD COLUMN IF NOT EXISTS championships INT DEFAULT 0");

// Reset all to zero first
$conn->query("UPDATE teams SET championships = 0");

foreach ($counts as $team => $num) {
    $teamEsc = $conn->real_escape_string($team);
    $conn->query("UPDATE teams SET championships = $num WHERE team_name = '$teamEsc'");
}

closeDBConnection($conn);

echo json_encode(['success' => true, 'counts' => $counts]);
exit;
