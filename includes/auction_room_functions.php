<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

// Allowed teams for participants
function getAllowedTeams() {
    return [
        'Mumbai Indians',
        'Chennai Super Kings',
        'Royal Challengers Bangalore',
        'Kolkata Knight Riders',
        'Lucknow Super Giants',
        'Gujarat Titans',
        'Rajasthan Royals',
        'Punjab Kings',
        'Delhi Capitals',
        'Sunrisers Hyderabad'
    ];
}

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
    $team_name = trim($team_name);

    // Validate team name against allowed list
    $allowed = getAllowedTeams();
    if (!in_array($team_name, $allowed)) {
        closeDBConnection($conn);
        return ['success' => false, 'message' => 'Invalid team selected'];
    }

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

// Get participant by id
function getParticipantById($participant_id) {
    $conn = getDBConnection();
    $pid = $conn->real_escape_string($participant_id);
    $sql = "SELECT rp.*, u.username, u.full_name FROM room_participants rp JOIN users u ON rp.user_id = u.user_id WHERE rp.participant_id = $pid";
    $result = $conn->query($sql);
    $row = $result ? $result->fetch_assoc() : null;
    closeDBConnection($conn);
    return $row;
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

// Get next player for room (follows group order: Marquee -> A -> B -> C)
function getNextPlayerForRoom($room_id, $group = null) {
    $conn = getDBConnection();
    $room_id = $conn->real_escape_string($room_id);
    
    // Get current auction group
    $room_sql = "SELECT current_auction_group FROM auction_rooms WHERE room_id = $room_id";
    $room_result = $conn->query($room_sql);
    $room_data = $room_result ? $room_result->fetch_assoc() : null;
    $current_group = $room_data['current_auction_group'] ?? 'Marquee';
    
    // Define group order
    $group_order = ['Marquee', 'A', 'B', 'C'];
    
    // Try to get a player from current group
    $sql = "SELECT p.* FROM players p
            WHERE p.player_id NOT IN (
                SELECT player_id FROM room_used_players WHERE room_id = $room_id
            )
            AND p.auction_group = '$current_group'
            ORDER BY RAND() LIMIT 1";
    
    $result = $conn->query($sql);
    $player = $result ? $result->fetch_assoc() : null;
    
    // If no player found in current group, move to next group
    if (!$player) {
        $current_index = array_search($current_group, $group_order);
        $next_index = $current_index + 1;
        
        // Try next groups in order
        while ($next_index < count($group_order) && !$player) {
            $next_group = $group_order[$next_index];
            
            $sql = "SELECT p.* FROM players p
                    WHERE p.player_id NOT IN (
                        SELECT player_id FROM room_used_players WHERE room_id = $room_id
                    )
                    AND p.auction_group = '$next_group'
                    ORDER BY RAND() LIMIT 1";
            
            $result = $conn->query($sql);
            $player = $result ? $result->fetch_assoc() : null;
            
            if ($player) {
                $current_group = $next_group;
                break;
            }
            $next_index++;
        }
    }
    
    if ($player) {
        // Mark as used
        $insert_sql = "INSERT INTO room_used_players (room_id, player_id) VALUES ($room_id, {$player['player_id']})";
        $conn->query($insert_sql);
        
        // Set as current player and initialize timer, update current group
        $update_sql = "UPDATE auction_rooms 
                       SET current_player_id = {$player['player_id']}, 
                           current_bid = {$player['base_price']}, 
                           current_bidder_id = NULL,
                           bid_timer_expires_at = DATE_ADD(NOW(), INTERVAL 45 SECOND),
                           current_auction_group = '$current_group'
                       WHERE room_id = $room_id";
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
    $room_sql = "SELECT current_player_id, current_bid, bidding_war_player1_id, bidding_war_player2_id FROM auction_rooms WHERE room_id = $room_id";
    $room_result = $conn->query($room_sql);
    $room = $room_result->fetch_assoc();
    
    if ($bid_amount <= $room['current_bid']) {
        closeDBConnection($conn);
        return ['success' => false, 'message' => 'Bid must be higher than current bid'];
    }
    
    // Check bidding war lock
    $player1 = $room['bidding_war_player1_id'];
    $player2 = $room['bidding_war_player2_id'];
    
    // If both players are locked, only they can bid
    if ($player1 && $player2) {
        if ($participant_id != $player1 && $participant_id != $player2) {
            closeDBConnection($conn);
            return ['success' => false, 'message' => 'Bidding locked between two players'];
        }
    }
    
    // Update bidding war tracking
    if (!$player1) {
        // First bidder
        $player1 = $participant_id;
        $player1_bid = $bid_amount;
        $update_war = "UPDATE auction_rooms SET bidding_war_player1_id = $player1, bidding_war_player1_bid = $player1_bid WHERE room_id = $room_id";
        $conn->query($update_war);
    } elseif (!$player2 && $participant_id != $player1) {
        // Second bidder (different from first) - lock established
        $player2 = $participant_id;
        $player2_bid = $bid_amount;
        $update_war = "UPDATE auction_rooms SET bidding_war_player2_id = $player2, bidding_war_player2_bid = $player2_bid WHERE room_id = $room_id";
        $conn->query($update_war);
    } elseif ($participant_id == $player1) {
        // Player 1 bidding again
        $update_war = "UPDATE auction_rooms SET bidding_war_player1_bid = $bid_amount WHERE room_id = $room_id";
        $conn->query($update_war);
    } elseif ($participant_id == $player2) {
        // Player 2 bidding again
        $update_war = "UPDATE auction_rooms SET bidding_war_player2_bid = $bid_amount WHERE room_id = $room_id";
        $conn->query($update_war);
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
        // Player SOLD - Assign player to the highest bidder
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
    } else {
        // Player UNSOLD - No bids received, mark as unsold
        $mark_sql = "UPDATE room_used_players SET is_sold = FALSE 
                     WHERE room_id = $room_id AND player_id = {$room['current_player_id']}";
        $conn->query($mark_sql);
    }

    // Clear current player and reset timer and bidding war
    $clear_sql = "UPDATE auction_rooms 
                  SET current_player_id = NULL, 
                      current_bid = NULL, 
                      current_bidder_id = NULL,
                      bid_timer_expires_at = NULL,
                      bidding_war_player1_id = NULL,
                      bidding_war_player2_id = NULL,
                      bidding_war_player1_bid = NULL,
                      bidding_war_player2_bid = NULL
                  WHERE room_id = $room_id";
    $conn->query($clear_sql);

    closeDBConnection($conn);

    // Automatically get next player
    getNextPlayerForRoom($room_id, null);

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

// Reset bid timer (when new bid is placed)
function resetBidTimer($room_id) {
    $conn = getDBConnection();
    $room_id = $conn->real_escape_string($room_id);
    
    $sql = "UPDATE auction_rooms SET bid_timer_expires_at = DATE_ADD(NOW(), INTERVAL 45 SECOND) 
            WHERE room_id = $room_id";
    $conn->query($sql);
    
    closeDBConnection($conn);
}

// Extend bid timer (when wait button is clicked)
function extendBidTimer($room_id, $seconds = 10) {
    $conn = getDBConnection();
    $room_id = $conn->real_escape_string($room_id);
    $seconds = intval($seconds);
    
    $sql = "UPDATE auction_rooms 
            SET bid_timer_expires_at = DATE_ADD(IFNULL(bid_timer_expires_at, NOW()), INTERVAL $seconds SECOND) 
            WHERE room_id = $room_id";
    $conn->query($sql);
    
    closeDBConnection($conn);
}

// Release player from bidding war
function releaseFromBiddingWar($room_id, $participant_id) {
    $conn = getDBConnection();
    $room_id = $conn->real_escape_string($room_id);
    $participant_id = $conn->real_escape_string($participant_id);
    
    // Get current bidding war state
    $sql = "SELECT bidding_war_player1_id, bidding_war_player2_id FROM auction_rooms WHERE room_id = $room_id";
    $result = $conn->query($sql);
    $room = $result->fetch_assoc();
    
    if ($participant_id == $room['bidding_war_player1_id']) {
        // Remove player 1
        $update_sql = "UPDATE auction_rooms 
                       SET bidding_war_player1_id = NULL, 
                           bidding_war_player1_bid = NULL 
                       WHERE room_id = $room_id";
        $conn->query($update_sql);
    } elseif ($participant_id == $room['bidding_war_player2_id']) {
        // Remove player 2
        $update_sql = "UPDATE auction_rooms 
                       SET bidding_war_player2_id = NULL, 
                           bidding_war_player2_bid = NULL 
                       WHERE room_id = $room_id";
        $conn->query($update_sql);
    }
    
    closeDBConnection($conn);
    return ['success' => true];
}

// Get remaining time for current bid
function getBidTimeRemaining($room_id) {
    $conn = getDBConnection();
    $room_id = $conn->real_escape_string($room_id);
    
    $sql = "SELECT TIMESTAMPDIFF(SECOND, NOW(), bid_timer_expires_at) as seconds_remaining 
            FROM auction_rooms WHERE room_id = $room_id";
    $result = $conn->query($sql);
    
    if ($result && $row = $result->fetch_assoc()) {
        $seconds = max(0, intval($row['seconds_remaining']));
        closeDBConnection($conn);
        return $seconds;
    }
    
    closeDBConnection($conn);
    return 45; // Default 45 seconds
}

// Get players bought by a participant
function getParticipantPlayers($participant_id) {
    $conn = getDBConnection();
    $participant_id = $conn->real_escape_string($participant_id);
    
    $sql = "SELECT p.*, rpa.sold_price 
            FROM room_player_assignments rpa
            JOIN players p ON rpa.player_id = p.player_id
            WHERE rpa.participant_id = $participant_id
            ORDER BY rpa.sold_at DESC";
    
    $result = $conn->query($sql);
    $players = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $players[] = $row;
        }
    }
    
    closeDBConnection($conn);
    return $players;
}

// Pause auction room - save current timer state
function pauseAuctionRoom($room_id) {
    $conn = getDBConnection();
    $room_id = $conn->real_escape_string($room_id);
    
    error_log("Pausing auction room: $room_id");
    
    // Get remaining time before pausing
    $sql = "SELECT TIMESTAMPDIFF(SECOND, NOW(), bid_timer_expires_at) as seconds_remaining 
            FROM auction_rooms WHERE room_id = $room_id";
    $result = $conn->query($sql);
    $remaining = 45;
    
    if ($result && $row = $result->fetch_assoc()) {
        $remaining = max(0, intval($row['seconds_remaining']));
        error_log("Remaining seconds: $remaining");
    }
    
    // Save the remaining time and set status to paused
    $update_sql = "UPDATE auction_rooms 
                   SET status = 'paused',
                       paused_time_remaining = $remaining
                   WHERE room_id = $room_id";
    
    $success = $conn->query($update_sql);
    error_log("Pause update SQL result: " . ($success ? "SUCCESS" : "FAILED - " . $conn->error));
    error_log("Rows affected: " . $conn->affected_rows);
    
    closeDBConnection($conn);
}

// Resume auction room - restore timer from where it left off
function resumeAuctionRoom($room_id) {
    $conn = getDBConnection();
    $room_id = $conn->real_escape_string($room_id);
    
    // Get the saved time remaining
    $sql = "SELECT paused_time_remaining FROM auction_rooms WHERE room_id = $room_id";
    $result = $conn->query($sql);
    $remaining = 45;
    
    if ($result && $row = $result->fetch_assoc()) {
        $remaining = $row['paused_time_remaining'] ?? 45;
    }
    
    // Resume with the saved time
    $update_sql = "UPDATE auction_rooms 
                   SET status = 'in_progress',
                       bid_timer_expires_at = DATE_ADD(NOW(), INTERVAL $remaining SECOND)
                   WHERE room_id = $room_id";
    
    $conn->query($update_sql);
    closeDBConnection($conn);
}
