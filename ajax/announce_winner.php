<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auction_room_functions.php';

header('Content-Type: application/json');

// Ensure logged in
$user = getCurrentUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$room_id = isset($input['room_id']) ? intval($input['room_id']) : 0;
$winner_pid = isset($input['participant_id']) ? intval($input['participant_id']) : 0;

if (!$room_id || !$winner_pid) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$room = getRoomById($room_id);
if (!$room) {
    echo json_encode(['success' => false, 'error' => 'Room not found']);
    exit;
}

// Only host can announce
if ((int)$room['created_by'] !== (int)$user['user_id']) {
    echo json_encode(['success' => false, 'error' => 'Only the host can announce the winner']);
    exit;
}

// Load votes file
$votesFile = __DIR__ . '/../data/votes/votes_room_' . $room_id . '.json';
$votes = ['by_user' => []];
if (file_exists($votesFile)) {
    $content = file_get_contents($votesFile);
    $votes = json_decode($content, true) ?: ['by_user' => []];
}

$participants = getRoomParticipants($room_id);
$participantCount = count($participants);

// Count how many participants/users have submitted a vote
$votersCount = count($votes['by_user'] ?? []);
if ($votersCount < $participantCount) {
    echo json_encode(['success' => false, 'error' => 'Not all participants have voted yet']);
    exit;
}

// Determine winner by votes (most votes)
$counts = [];
foreach (($votes['by_user'] ?? []) as $uid => $pid) {
    $pid = (string)$pid;
    if (!isset($counts[$pid])) $counts[$pid] = 0;
    $counts[$pid]++;
}

arsort($counts);
$top = array_keys($counts);
$winningPid = intval($top[0] ?? 0);

if ($winningPid !== $winner_pid) {
    // mismatch between requested and computed winner - prevent accidental override
    echo json_encode(['success' => false, 'error' => 'Winner mismatch (computed vs requested)']);
    exit;
}

// Map participant id to team name
$winnerParticipant = getParticipantById($winningPid);
if (!$winnerParticipant) {
    echo json_encode(['success' => false, 'error' => 'Winning participant not found']);
    exit;
}

$winnerTeamName = $winnerParticipant['team_name'];

// Increment championships for team (use team_name match)
$conn = getDBConnection();
$team_name_esc = $conn->real_escape_string($winnerTeamName);

// Ensure championships column exists by attempting to add if missing (safe to run)
$conn->query("ALTER TABLE teams ADD COLUMN IF NOT EXISTS championships INT DEFAULT 0");

$update_sql = "UPDATE teams SET championships = IFNULL(championships,0) + 1 WHERE team_name = '$team_name_esc'";
if ($conn->query($update_sql)) {
    // Optionally record announcement in room (status or note)
    $note_sql = "UPDATE auction_rooms SET status = 'finished', ended_at = NOW() WHERE room_id = " . intval($room_id);
    $conn->query($note_sql);
    // write broadcast announcement file so clients can pick it up
    $annDir = __DIR__ . '/../data/announcements';
    if (!is_dir($annDir)) @mkdir($annDir, 0755, true);
    $announceData = [
        'winner_participant_id' => $winningPid,
        'winner_team' => $winnerTeamName,
        'timestamp' => time()
    ];
    @file_put_contents($annDir . '/announcement_room_' . $room_id . '.json', json_encode($announceData));
    closeDBConnection($conn);
    echo json_encode(['success' => true, 'winner_team' => $winnerTeamName, 'winner_participant_id' => $winningPid]);
    exit;
} else {
    $err = $conn->error;
    closeDBConnection($conn);
    echo json_encode(['success' => false, 'error' => 'DB update failed: ' . $err]);
    exit;
}

?>
