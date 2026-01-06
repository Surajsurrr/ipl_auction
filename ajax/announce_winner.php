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
// Make sure we only award championships once per room
$room_id_safe = intval($room_id);
$room_check_sql = "SELECT winner_participant_id FROM auction_rooms WHERE room_id = $room_id_safe";
$result = $conn->query($room_check_sql);
$room_row = $result ? $result->fetch_assoc() : null;
if ($room_row && !empty($room_row['winner_participant_id'])) {
    // already announced for this room
    if (intval($room_row['winner_participant_id']) === $winningPid) {
        // still write broadcast file (idempotent) and return success
        $annDir = __DIR__ . '/../data/announcements';
        if (!is_dir($annDir)) @mkdir($annDir, 0755, true);
        $announceData = [
            'winner_participant_id' => $winningPid,
            'winner_team' => $winnerTeamName,
            'timestamp' => time()
        ];
        @file_put_contents($annDir . '/announcement_room_' . $room_id . '.json', json_encode($announceData));
        closeDBConnection($conn);
        echo json_encode(['success' => true, 'winner_team' => $winnerTeamName, 'winner_participant_id' => $winningPid, 'already_announced' => true]);
        exit;
    } else {
        closeDBConnection($conn);
        echo json_encode(['success' => false, 'error' => 'A different winner was already recorded for this room']);
        exit;
    }
}

$team_name_esc = $conn->real_escape_string($winnerTeamName);
// Ensure championships column exists by attempting to add if missing (safe to run)
$conn->query("ALTER TABLE teams ADD COLUMN IF NOT EXISTS championships INT DEFAULT 0");

// Increment the team's championships and record winner in auction_rooms atomically
$conn->begin_transaction();
try {
    $update_sql = "UPDATE teams SET championships = IFNULL(championships,0) + 1 WHERE team_name = '$team_name_esc'";
    if (!$conn->query($update_sql)) throw new Exception($conn->error);

    $note_sql = "UPDATE auction_rooms SET status = 'finished', ended_at = NOW(), winner_participant_id = $winningPid, winner_team = '$team_name_esc', winner_announced_at = NOW() WHERE room_id = $room_id_safe";
    if (!$conn->query($note_sql)) throw new Exception($conn->error);

    $conn->commit();
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
} catch (Exception $e) {
    $conn->rollback();
    $err = $e->getMessage();
    closeDBConnection($conn);
    echo json_encode(['success' => false, 'error' => 'DB update failed: ' . $err]);
    exit;
}

?>
