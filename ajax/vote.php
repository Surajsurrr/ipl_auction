<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// Accept JSON body or query params
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true);
$input = is_array($jsonInput) ? $jsonInput : $_REQUEST;

$room_id = isset($input['room_id']) ? intval($input['room_id']) : 0;
if (!$room_id) {
    echo json_encode(['success' => false, 'error' => 'Missing room_id']);
    exit;
}

$voteDir = __DIR__ . '/../data/votes';
if (!is_dir($voteDir)) mkdir($voteDir, 0777, true);
$voteFile = $voteDir . '/votes_room_' . $room_id . '.json';
if (!file_exists($voteFile)) file_put_contents($voteFile, json_encode(new stdClass()));

if ($method === 'GET') {
    $raw = file_get_contents($voteFile);
    $data = json_decode($raw, true) ?: [];
    // data mapping: user_id => participant_id
    $byUser = isset($data['by_user']) && is_array($data['by_user']) ? $data['by_user'] : [];
    $counts = [];
    foreach ($byUser as $uid => $pid) {
        if (!isset($counts[$pid])) $counts[$pid] = 0;
        $counts[$pid]++;
    }
    $currentUser = getCurrentUser();
    $user_vote = null;
    if ($currentUser && isset($byUser[$currentUser['user_id']])) $user_vote = $byUser[$currentUser['user_id']];

    echo json_encode(['success' => true, 'counts' => $counts, 'user_vote' => $user_vote]);
    exit;
}

if ($method === 'POST') {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }

    // Use previously-parsed $input (supports JSON body)
    $participant_id = isset($input['participant_id']) ? intval($input['participant_id']) : 0;
    if (!$participant_id) {
        echo json_encode(['success' => false, 'error' => 'Missing participant_id']);
        exit;
    }

    $user = getCurrentUser();
    $uid = $user['user_id'];

    // Read/Write with lock
    $attempts = 0;
    do {
        $fp = fopen($voteFile, 'c+');
        if (!$fp) break;
        if (flock($fp, LOCK_EX)) {
            $contents = stream_get_contents($fp);
            $json = json_decode($contents, true) ?: [];
            if (!isset($json['by_user']) || !is_array($json['by_user'])) $json['by_user'] = [];
            // set/overwrite vote
            $json['by_user'][(string)$uid] = $participant_id;
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($json));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            echo json_encode(['success' => true, 'participant_id' => $participant_id]);
            exit;
        }
        if ($fp) fclose($fp);
        usleep(10000);
    } while (++$attempts < 5);

    echo json_encode(['success' => false, 'error' => 'Could not write vote']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unsupported method']);
exit;
