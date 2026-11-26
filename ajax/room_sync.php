<?php
require_once __DIR__ . '/../config/session.php';
// Release session lock immediately so other requests, bids, and page navigation are never blocked
session_write_close();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auction_room_functions.php';
require_once __DIR__ . '/../includes/player_functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$client_hash = isset($_GET['hash']) ? trim($_GET['hash']) : '';
$current_pid = isset($_GET['participant_id']) ? intval($_GET['participant_id']) : 0;
$is_host_req = !empty($_GET['is_host']);

if (!$room_id) {
    echo json_encode(['success' => false, 'error' => 'Missing room_id']);
    exit;
}

$conn = getDBConnection();
$room_id_safe = intval($room_id);

// Refresh host heartbeat directly in sync call (eliminates separate host_ping HTTP requests)
if ($is_host_req) {
    $now = time();
    $conn->query("UPDATE auction_rooms SET host_last_ping = $now WHERE room_id = $room_id_safe");
}

// 1. Fetch room row with unix timestamp for timer
$sql = "SELECT r.*, 
               UNIX_TIMESTAMP(r.bid_timer_expires_at) AS timer_expires_ts,
               TIMESTAMPDIFF(SECOND, NOW(), r.bid_timer_expires_at) AS seconds_remaining
        FROM auction_rooms r 
        WHERE r.room_id = $room_id_safe";
$res = $conn->query($sql);
if (!$res || $res->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Room not found']);
    exit;
}
$room = $res->fetch_assoc();

// 2. Fetch all participants to track waiting room entries, roster counts, and budget changes
$part_res = $conn->query("SELECT participant_id, user_id, team_name, remaining_budget, players_count, is_host FROM room_participants WHERE room_id = $room_id_safe ORDER BY participant_id ASC");
$participants = [];
$part_hash_tokens = [];
if ($part_res) {
    while ($p = $part_res->fetch_assoc()) {
        $p['logo'] = function_exists('getTeamLogoPath') ? getTeamLogoPath($p['team_name']) : null;
        $part_hash_tokens[] = $p['participant_id'] . ':' . $p['remaining_budget'] . ':' . ($p['players_count'] ?? 0);
        $participants[$p['participant_id']] = $p;
    }
}
$part_hash = implode('|', $part_hash_tokens);
$participant_count = count($participants);
$max_participants = intval($room['max_participants'] ?? 10);

// Check last sale broadcast file
$last_sale_file = __DIR__ . '/../data/announcements/last_sale_room_' . $room_id . '.json';
$last_sale_ts = file_exists($last_sale_file) ? filemtime($last_sale_file) : 0;

// Compute comprehensive state hash including status, bids, timers, sales, and participants
$state_hash = md5(
    ($room['status'] ?? '') . '_' .
    ($room['current_player_id'] ?? 'null') . '_' .
    ($room['current_bid'] ?? '0') . '_' .
    ($room['current_bidder_id'] ?? 'null') . '_' .
    ($room['bid_timer_expires_at'] ?? 'null') . '_' .
    ($room['paused_time_remaining'] ?? '0') . '_' .
    ($room['bidding_war_player1_id'] ?? 'null') . '_' .
    ($room['bidding_war_player1_bid'] ?? '0') . '_' .
    ($room['bidding_war_player2_id'] ?? 'null') . '_' .
    ($room['bidding_war_player2_bid'] ?? '0') . '_' .
    $last_sale_ts . '_' .
    $part_hash
);

// If client hash matches, return lightweight ping with status and participant count
if ($client_hash === $state_hash) {
    $seconds = max(0, intval($room['seconds_remaining'] ?? 0));
    if ($room['status'] === 'paused') {
        $seconds = intval($room['paused_time_remaining'] ?? 0);
    }
    echo json_encode([
        'success' => true,
        'changed' => false,
        'hash' => $state_hash,
        'status' => $room['status'],
        'time_remaining' => $seconds,
        'timer_expires_ts' => $room['timer_expires_ts'] ? (intval($room['timer_expires_ts']) * 1000) : null,
        'participant_count' => $participant_count,
        'max_participants' => $max_participants
    ]);
    exit;
}

// State has changed! Build full sync payload
$current_player = null;
if (!empty($room['current_player_id'])) {
    $pid = intval($room['current_player_id']);
    $p_res = $conn->query("SELECT * FROM players WHERE player_id = $pid");
    if ($p_res && $p_row = $p_res->fetch_assoc()) {
        $current_player = [
            'player_id' => $p_row['player_id'],
            'player_name' => $p_row['player_name'],
            'auction_group' => $p_row['auction_group'],
            'player_type' => $p_row['player_type'],
            'player_role' => $p_row['player_role'],
            'base_price' => $p_row['base_price'],
            'base_price_cr' => number_format($p_row['base_price'] / 10000000, 2),
            'age' => $p_row['age'],
            'nationality' => $p_row['nationality']
        ];
    }
}

// Current bidder info
$current_bidder = null;
if (!empty($room['current_bidder_id']) && isset($participants[$room['current_bidder_id']])) {
    $b = $participants[$room['current_bidder_id']];
    $current_bidder = [
        'participant_id' => $b['participant_id'],
        'team_name' => $b['team_name'],
        'logo' => $b['logo'],
        'remaining_budget' => $b['remaining_budget'],
        'remaining_budget_cr' => number_format($b['remaining_budget'] / 10000000, 2)
    ];
}

// Bidding war details
$p1_id = $room['bidding_war_player1_id'] ?? null;
$p2_id = $room['bidding_war_player2_id'] ?? null;
$player1 = ($p1_id && isset($participants[$p1_id])) ? $participants[$p1_id] : null;
$player2 = ($p2_id && isset($participants[$p2_id])) ? $participants[$p2_id] : null;

$bidding_war = [
    'player1' => $player1 ? [
        'participant_id' => $player1['participant_id'],
        'team_name' => $player1['team_name'],
        'logo' => $player1['logo'],
        'current_bid' => $room['bidding_war_player1_bid'],
        'current_bid_cr' => number_format(($room['bidding_war_player1_bid'] ?? 0) / 10000000, 2),
        'remaining_budget_cr' => number_format($player1['remaining_budget'] / 10000000, 2),
        'is_leading' => ($room['current_bidder_id'] == $player1['participant_id'])
    ] : null,
    'player2' => $player2 ? [
        'participant_id' => $player2['participant_id'],
        'team_name' => $player2['team_name'],
        'logo' => $player2['logo'],
        'current_bid' => $room['bidding_war_player2_bid'],
        'current_bid_cr' => number_format(($room['bidding_war_player2_bid'] ?? 0) / 10000000, 2),
        'remaining_budget_cr' => number_format($player2['remaining_budget'] / 10000000, 2),
        'is_leading' => ($room['current_bidder_id'] == $player2['participant_id'])
    ] : null,
    'is_locked' => ($player1 && $player2) ? true : false
];

// Next bid calculation
$current_bid = floatval($room['current_bid'] ?? 0);
$next_bid = $current_bid;
$increment = 1000000;
if (!$room['current_bidder_id'] && $current_player && $current_bid == $current_player['base_price']) {
    $next_bid = $current_player['base_price'];
} else {
    $current_bid_cr = $current_bid / 10000000;
    $increment = ($current_bid_cr >= 3) ? 2000000 : 1000000;
    $next_bid = $current_bid + $increment;
}

// Can the requesting participant bid?
$can_bid = true;
$cannot_bid_reason = '';
if ($room['status'] === 'paused') {
    $can_bid = false;
    $cannot_bid_reason = 'Auction is paused';
} elseif ($bidding_war['is_locked'] && $current_pid != $p1_id && $current_pid != $p2_id) {
    $can_bid = false;
    $cannot_bid_reason = 'Bidding locked between two teams';
} elseif ($current_pid && $room['current_bidder_id'] == $current_pid) {
    $can_bid = false;
    $cannot_bid_reason = 'Waiting for other teams to bid';
}

// Last sale notification
$last_sale = null;
if (file_exists($last_sale_file)) {
    $last_sale = json_decode(@file_get_contents($last_sale_file), true);
}

$seconds = max(0, intval($room['seconds_remaining'] ?? 0));
if ($room['status'] === 'paused') {
    $seconds = intval($room['paused_time_remaining'] ?? 0);
}

echo json_encode([
    'success' => true,
    'changed' => true,
    'hash' => $state_hash,
    'status' => $room['status'],
    'participant_count' => $participant_count,
    'max_participants' => $max_participants,
    'current_player' => $current_player,
    'current_bid' => $current_bid,
    'current_bid_cr' => number_format($current_bid / 10000000, 2),
    'next_bid' => $next_bid,
    'next_bid_cr' => number_format($next_bid / 10000000, 2),
    'increment_cr' => ($increment / 10000000) . ' Cr',
    'current_bidder' => $current_bidder,
    'bidding_war' => $bidding_war,
    'can_bid' => $can_bid,
    'cannot_bid_reason' => $cannot_bid_reason,
    'is_in_war' => ($current_pid == $p1_id || $current_pid == $p2_id),
    'time_remaining' => $seconds,
    'timer_expires_ts' => $room['timer_expires_ts'] ? (intval($room['timer_expires_ts']) * 1000) : null,
    'last_sale' => $last_sale,
    'participants' => array_values($participants)
]);
exit;
