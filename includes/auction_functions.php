<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/team_functions.php';

// Get active auction session
function getActiveSession() {
    $sql = "SELECT * FROM auction_session WHERE is_active = TRUE LIMIT 1";
    return getSingleRow($sql);
}

// Get or create auction session
function getOrCreateSession() {
    $session = getActiveSession();
    if (!$session) {
        $sql = "SELECT * FROM auction_session ORDER BY session_id DESC LIMIT 1";
        $session = getSingleRow($sql);
    }
    return $session;
}

// Start auction
function startAuction($session_id) {
    $conn = getDBConnection();
    $session_id = $conn->real_escape_string($session_id);
    
    $sql = "UPDATE auction_session SET 
            is_active = TRUE, 
            auction_status = 'In Progress',
            started_at = NOW()
            WHERE session_id = $session_id";
    
    $result = $conn->query($sql);
    closeDBConnection($conn);
    
    return $result;
}

// Get random unsold player from group
function getRandomPlayerFromGroup($group) {
    $conn = getDBConnection();
    $group = $conn->real_escape_string($group);
    
    $sql = "SELECT * FROM players 
            WHERE auction_group = '$group' AND is_sold = FALSE 
            ORDER BY RAND() LIMIT 1";
    
    $player = getSingleRow($sql);
    closeDBConnection($conn);
    
    return $player;
}

// Set current player for auction
function setCurrentPlayer($session_id, $player_id, $group) {
    $conn = getDBConnection();
    $session_id = $conn->real_escape_string($session_id);
    $player_id = $conn->real_escape_string($player_id);
    $group = $conn->real_escape_string($group);
    
    // Get player base price
    $player = getPlayerById($player_id);
    $base_price = $player['base_price'];
    
    $sql = "UPDATE auction_session SET 
            current_player_id = $player_id,
            current_group = '$group',
            current_bid = $base_price,
            current_bidder_team_id = NULL
            WHERE session_id = $session_id";
    
    $result = $conn->query($sql);
    closeDBConnection($conn);
    
    return $result;
}

// Place bid
function placeBid($session_id, $team_id, $player_id, $bid_amount) {
    $conn = getDBConnection();
    
    $session_id = $conn->real_escape_string($session_id);
    $team_id = $conn->real_escape_string($team_id);
    $player_id = $conn->real_escape_string($player_id);
    $bid_amount = $conn->real_escape_string($bid_amount);
    
    // Check team budget
    $team = getTeamById($team_id);
    if ($team['remaining_budget'] < $bid_amount) {
        return ['success' => false, 'message' => 'Insufficient budget'];
    }
    
    // Get current bid
    $session = getActiveSession();
    $current_bid = $session['current_bid'];
    
    // Validate bid amount (minimum increment 10 lakhs)
    $min_increment = 1000000; // 10 lakhs
    if ($bid_amount <= $current_bid || $bid_amount < $current_bid + $min_increment) {
        return ['success' => false, 'message' => 'Bid must be at least ' . formatCurrency($current_bid + $min_increment)];
    }
    
    $conn->begin_transaction();
    
    try {
        // Record bid
        $sql = "INSERT INTO bids (session_id, player_id, team_id, bid_amount) 
                VALUES ($session_id, $player_id, $team_id, $bid_amount)";
        $conn->query($sql);
        
        // Update session
        $sql = "UPDATE auction_session SET 
                current_bid = $bid_amount,
                current_bidder_team_id = $team_id
                WHERE session_id = $session_id";
        $conn->query($sql);
        
        $conn->commit();
        closeDBConnection($conn);
        
        return ['success' => true, 'message' => 'Bid placed successfully'];
    } catch (Exception $e) {
        $conn->rollback();
        closeDBConnection($conn);
        return ['success' => false, 'message' => 'Error placing bid'];
    }
}

// Finalize player sale
function finalizePlayerSale($session_id) {
    $conn = getDBConnection();
    $session_id = $conn->real_escape_string($session_id);
    
    $session = getActiveSession();
    
    if (!$session || !$session['current_player_id'] || !$session['current_bidder_team_id']) {
        closeDBConnection($conn);
        return ['success' => false, 'message' => 'No active bid'];
    }
    
    $player_id = $session['current_player_id'];
    $team_id = $session['current_bidder_team_id'];
    $price = $session['current_bid'];
    
    // Add player to team
    $result = addPlayerToTeam($team_id, $player_id, $price);
    
    if ($result) {
        // Clear current player
        $sql = "UPDATE auction_session SET 
                current_player_id = NULL,
                current_bidder_team_id = NULL,
                current_bid = 0
                WHERE session_id = $session_id";
        $conn->query($sql);
        
        closeDBConnection($conn);
        return ['success' => true, 'message' => 'Player sold successfully'];
    }
    
    closeDBConnection($conn);
    return ['success' => false, 'message' => 'Error finalizing sale'];
}

// Pass on player (unsold)
function passPlayer($session_id) {
    $conn = getDBConnection();
    $session_id = $conn->real_escape_string($session_id);
    
    $sql = "UPDATE auction_session SET 
            current_player_id = NULL,
            current_bidder_team_id = NULL,
            current_bid = 0
            WHERE session_id = $session_id";
    
    $result = $conn->query($sql);
    closeDBConnection($conn);
    
    return $result;
}

// Get bid history for a player
function getPlayerBidHistory($player_id) {
    $conn = getDBConnection();
    $player_id = $conn->real_escape_string($player_id);
    
    $sql = "SELECT b.*, t.team_name 
            FROM bids b 
            INNER JOIN teams t ON b.team_id = t.team_id 
            WHERE b.player_id = $player_id 
            ORDER BY b.bid_time DESC";
    
    $result = $conn->query($sql);
    $bids = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $bids[] = $row;
        }
    }
    
    closeDBConnection($conn);
    return $bids;
}

// End auction
function endAuction($session_id) {
    $conn = getDBConnection();
    $session_id = $conn->real_escape_string($session_id);
    
    $sql = "UPDATE auction_session SET 
            is_active = FALSE,
            auction_status = 'Completed',
            ended_at = NOW()
            WHERE session_id = $session_id";
    
    $result = $conn->query($sql);
    closeDBConnection($conn);
    
    return $result;
}

// Get auction statistics
function getAuctionStatistics() {
    $sql = "SELECT 
            COUNT(CASE WHEN is_sold = TRUE THEN 1 END) as sold_players,
            COUNT(CASE WHEN is_sold = FALSE THEN 1 END) as unsold_players,
            SUM(CASE WHEN is_sold = TRUE THEN sold_price ELSE 0 END) as total_money_spent,
            AVG(CASE WHEN is_sold = TRUE THEN sold_price ELSE NULL END) as avg_price
            FROM players";
    
    return getSingleRow($sql);
}
?>
