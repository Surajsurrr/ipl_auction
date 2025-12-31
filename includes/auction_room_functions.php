<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

// Generate unique room code
function generateRoomCode() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

// Create auction room
function createAuctionRoom($user_id, $room_name, $max_participants = 10, $budget_per_team = 12000000000) {
    $conn = getDBConnection();
    
    // Generate unique code
    do {
        $room_code = generateRoomCode();
        $check_sql = "SELECT room_id FROM auction_rooms WHERE room_code = '$room_code'";
        $result = $conn->query($check_sql);
    } while ($result && $result->num_rows > 0);
    
    $room_name = $conn->real_escape_string($room_name);
    $user_id = $conn->real_escape_string($user_id);
    
    $sql = "INSERT INTO auction_rooms (room_code, room_name, created_by, max_participants, total_budget_per_team) 
            VALUES ('$room_code', '$room_name', $user_id, $max_participants, $budget_per_team)";
    
    if ($conn->query($sql)) {
        $room_id = $conn->insert_id;
        closeDBConnection($conn);
        return ['success' => true, 'room_id' => $room_id, 'room_code' => $room_code];
    } else {
        closeDBConnection($conn);
        return ['success' => false, 'message' => 'Failed to create room'];
    }
}

// Join auction room
function joinAuctionRoom($room_code, $user_id, $team_name) {
    $conn = getDBConnection();
    $room_code = $conn->real_escape_string($room_code);
    $user_id = $conn->real_escape_string($user_id);
    $team_name = $conn->real_escape_string($team_name);
    
    // Check if room exists
    $sql = "SELECT * FROM auction_rooms WHERE room_code = '$room_code'";
    $result = $conn->query($sql);
    
    if (!$result || $result->num_rows == 0) {
        closeDBConnection($conn);
        return ['success' => false, 'message' => 'Invalid room code'];
    }
    
    $room = $result->fetch_assoc();
    
    // Check if room is not full
    $count_sql = "SELECT COUNT(*) as count FROM room_participants WHERE room_id = {$room['room_id']}";
    $count_result = $conn->query($count_sql);
    $count = $count_result->fetch_assoc()['count'];
    
    if ($count >= $room['max_participants']) {
        closeDBConnection($conn);
        return ['success' => false, 'message' => 'Room is full'];
    }
    
    // Check if already joined
    $check_sql = "SELECT * FROM room_participants WHERE room_id = {$room['room_id']} AND user_id = $user_id";
    $check_result = $conn->query($check_sql);
    
    if ($check_result && $check_result->num_rows > 0) {
        closeDBConnection($conn);
        return ['success' => true, 'room_id' => $room['room_id'], 'already_joined' => true];
    }
    
    // Check if user is the creator
    $is_host = ($room['created_by'] == $user_id) ? 1 : 0;
    
    // Join room
    $insert_sql = "INSERT INTO room_participants (room_id, user_id, team_name, remaining_budget, is_host) 
                   VALUES ({$room['room_id']}, $user_id, '$team_name', {$room['total_budget_per_team']}, $is_host)";
    
    if ($conn->query($insert_sql)) {
        closeDBConnection($conn);
        return ['success' => true, 'room_id' => $room['room_id']];
    } else {
        closeDBConnection($conn);
        return ['success' => false, 'message' => 'Failed to join room'];
    }
}

// Get room by code
function getRoomByCode($room_code) {
    $conn = getDBConnection();
    $room_code = $conn->real_escape_string($room_code);
    
    $sql = "SELECT ar.*, u.username as host_username, u.full_name as host_name,
            (SELECT COUNT(*) FROM room_participants WHERE room_id = ar.room_id) as participants_count
            FROM auction_rooms ar
            JOIN users u ON ar.created_by = u.user_id
            WHERE ar.room_code = '$room_code'";
    
    $result = $conn->query($sql);
    $room = $result ? $result->fetch_assoc() : null;
    
    closeDBConnection($conn);
    return $room;
}

// Get room by ID
function getRoomById($room_id) {
    $conn = getDBConnection();
    $room_id = $conn->real_escape_string($room_id);
    
    $sql = "SELECT ar.*, u.username as host_username, u.full_name as host_name,
            (SELECT COUNT(*) FROM room_participants WHERE room_id = ar.room_id) as participants_count
            FROM auction_rooms ar
            JOIN users u ON ar.created_by = u.user_id
            WHERE ar.room_id = $room_id";
    
    $result = $conn->query($sql);
    $room = $result ? $result->fetch_assoc() : null;
    
    closeDBConnection($conn);
    return $room;
}

// Get room participants
function getRoomParticipants($room_id) {
    $conn = getDBConnection();
    $room_id = $conn->real_escape_string($room_id);
    
    $sql = "SELECT rp.*, u.username, u.full_name,
            (SELECT COUNT(*) FROM room_player_assignments WHERE participant_id = rp.participant_id) as players_bought
            FROM room_participants rp
            JOIN users u ON rp.user_id = u.user_id
            WHERE rp.room_id = $room_id
            ORDER BY rp.joined_at";
    
    $result = $conn->query($sql);
    $participants = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $participants[] = $row;
        }
    }
    
    closeDBConnection($conn);
    return $participants;
}

// Check if user is in room
function isUserInRoom($room_id, $user_id) {
    $conn = getDBConnection();
    $room_id = $conn->real_escape_string($room_id);
    $user_id = $conn->real_escape_string($user_id);
    
    $sql = "SELECT participant_id FROM room_participants WHERE room_id = $room_id AND user_id = $user_id";
    $result = $conn->query($sql);
    
    $inRoom = $result && $result->num_rows > 0;
    $participant_id = $inRoom ? $result->fetch_assoc()['participant_id'] : null;
    
    closeDBConnection($conn);
    return ['in_room' => $inRoom, 'participant_id' => $participant_id];
}

// Start auction room
function startAuctionRoom($room_id, $user_id) {
    $conn = getDBConnection();
    $room_id = $conn->real_escape_string($room_id);
    $user_id = $conn->real_escape_string($user_id);
    
    // Check if user is host
    $sql = "SELECT created_by FROM auction_rooms WHERE room_id = $room_id";
    $result = $conn->query($sql);
    
    if (!$result || $result->num_rows == 0) {
        closeDBConnection($conn);
        return ['success' => false, 'message' => 'Room not found'];
    }
    
    $room = $result->fetch_assoc();
    if ($room['created_by'] != $user_id) {
        closeDBConnection($conn);
        return ['success' => false, 'message' => 'Only host can start auction'];
    }
    
    // Update room status
    $update_sql = "UPDATE auction_rooms SET status = 'in_progress', started_at = NOW() WHERE room_id = $room_id";
    
    if ($conn->query($update_sql)) {
        closeDBConnection($conn);
        return ['success' => true];
    } else {
        closeDBConnection($conn);
        return ['success' => false, 'message' => 'Failed to start auction'];
    }
}

// Get next player for room
function getNextPlayerForRoom($room_id, $group = null) {
    $conn = getDBConnection();
    $room_id = $conn->real_escape_string($room_id);
    
    // Get players not yet used in this room
    $sql = "SELECT p.* FROM players p
            WHERE p.player_id NOT IN (
                SELECT player_id FROM room_used_players WHERE room_id = $room_id
            )";
    
    if ($group) {
        $group = $conn->real_escape_string($group);
        $sql .= " AND p.auction_group = '$group'";
    }
    
    $sql .= " ORDER BY RAND() LIMIT 1";
    
    $result = $conn->query($sql);
    $player = $result ? $result->fetch_assoc() : null;
    
    if ($player) {
        // Mark as used
        $insert_sql = "INSERT INTO room_used_players (room_id, player_id) VALUES ($room_id, {$player['player_id']})";
        $conn->query($insert_sql);
        
        // Set as current player
        $update_sql = "UPDATE auction_rooms SET current_player_id = {$player['player_id']}, current_bid = {$player['base_price']}, current_bidder_id = NULL WHERE room_id = $room_id";
        $conn->query($update_sql);
    }
    
    closeDBConnection($conn);
    return $player;
}

// Place bid in room
function placeBidInRoom($room_id, $participant_id, $bid_amount) {
    $conn = getDBConnection();
    $room_id = $conn->real_escape_string($room_id);
    $participant_id = $conn->real_escape_string($participant_id);
    $bid_amount = $conn->real_escape_string($bid_amount);
    
    // Get participant budget
    $sql = "SELECT remaining_budget FROM room_participants WHERE participant_id = $participant_id";
    $result = $conn->query($sql);
    
    if (!$result || $result->num_rows == 0) {
        closeDBConnection($conn);
        return ['success' => false, 'message' => 'Participant not found'];
    }
    
    $participant = $result->fetch_assoc();
    if ($participant['remaining_budget'] < $bid_amount) {
        closeDBConnection($conn);
        return ['success' => false, 'message' => 'Insufficient budget'];
    }
    
    // Get current room info
    $room_sql = "SELECT current_player_id, current_bid FROM auction_rooms WHERE room_id = $room_id";
    $room_result = $conn->query($room_sql);
    $room = $room_result->fetch_assoc();
    
    if ($bid_amount <= $room['current_bid']) {
        closeDBConnection($conn);
        return ['success' => false, 'message' => 'Bid must be higher than current bid'];
    }
    
    // Update room current bid
    $update_sql = "UPDATE auction_rooms SET current_bid = $bid_amount, current_bidder_id = $participant_id WHERE room_id = $room_id";
    $conn->query($update_sql);
    
    // Record bid
    $bid_sql = "INSERT INTO room_bids (room_id, player_id, participant_id, bid_amount) 
                VALUES ($room_id, {$room['current_player_id']}, $participant_id, $bid_amount)";
    $conn->query($bid_sql);
    
    closeDBConnection($conn);
    return ['success' => true];
}

// Finalize player sale in room
function finalizePlayerInRoom($room_id) {
    $conn = getDBConnection();
    $room_id = $conn->real_escape_string($room_id);
    
    // Get current room state
    $sql = "SELECT current_player_id, current_bid, current_bidder_id FROM auction_rooms WHERE room_id = $room_id";
    $result = $conn->query($sql);
    
    if (!$result || $result->num_rows == 0) {
        closeDBConnection($conn);
        return ['success' => false, 'message' => 'Room not found'];
    }
    
    $room = $result->fetch_assoc();
    
    if ($room['current_bidder_id']) {
        // Assign player to bidder
        $assign_sql = "INSERT INTO room_player_assignments (room_id, participant_id, player_id, sold_price) 
                       VALUES ($room_id, {$room['current_bidder_id']}, {$room['current_player_id']}, {$room['current_bid']})";
        $conn->query($assign_sql);
        
        // Update participant budget and count
        $update_sql = "UPDATE room_participants 
                       SET remaining_budget = remaining_budget - {$room['current_bid']},
                           players_count = players_count + 1
                       WHERE participant_id = {$room['current_bidder_id']}";
        $conn->query($update_sql);
        
        // Mark player as sold in room
        $mark_sql = "UPDATE room_used_players SET is_sold = TRUE 
                     WHERE room_id = $room_id AND player_id = {$room['current_player_id']}";
        $conn->query($mark_sql);
    }
    
    // Clear current player
    $clear_sql = "UPDATE auction_rooms SET current_player_id = NULL, current_bid = NULL, current_bidder_id = NULL WHERE room_id = $room_id";
    $conn->query($clear_sql);
    
    closeDBConnection($conn);
    return ['success' => true];
}

// Get user's rooms
function getUserRooms($user_id) {
    $conn = getDBConnection();
    $user_id = $conn->real_escape_string($user_id);
    
    $sql = "SELECT ar.*, 
            (SELECT COUNT(*) FROM room_participants WHERE room_id = ar.room_id) as participants_count,
            (SELECT team_name FROM room_participants WHERE room_id = ar.room_id AND user_id = $user_id) as my_team_name
            FROM auction_rooms ar
            WHERE ar.room_id IN (
                SELECT room_id FROM room_participants WHERE user_id = $user_id
            )
            ORDER BY ar.created_at DESC";
    
    $result = $conn->query($sql);
    $rooms = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
    }
    
    closeDBConnection($conn);
    return $rooms;
}
?>
