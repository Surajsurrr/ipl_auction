<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

// Calculate auction group based on base price
// Group A: > 2 Crore (20000000)
// Group B: 1-2 Crore (10000000 - 20000000)
// Group C: < 1 Crore (< 10000000)
function calculateAuctionGroup($base_price) {
    if ($base_price > 20000000) {
        return 'A';
    } elseif ($base_price >= 10000000) {
        return 'B';
    } else {
        return 'C';
    }
}

// Get all players with stats
function getAllPlayers($filters = []) {
    $conn = getDBConnection();
    
    $sql = "SELECT p.*, ps.* FROM players p 
            LEFT JOIN player_stats ps ON p.player_id = ps.player_id 
            WHERE 1=1";
    
    if (isset($filters['player_type']) && $filters['player_type'] != '') {
        $type = $conn->real_escape_string($filters['player_type']);
        $sql .= " AND p.player_type = '$type'";
    }
    
    if (isset($filters['auction_group']) && $filters['auction_group'] != '') {
        $group = $conn->real_escape_string($filters['auction_group']);
        $sql .= " AND p.auction_group = '$group'";
    }
    
    if (isset($filters['is_sold'])) {
        $sold = $filters['is_sold'] ? 1 : 0;
        $sql .= " AND p.is_sold = $sold";
    }
    
    $sql .= " ORDER BY p.auction_group, p.base_price DESC";
    
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

// Get player by ID
function getPlayerById($player_id) {
    $conn = getDBConnection();
    $player_id = $conn->real_escape_string($player_id);
    
    $sql = "SELECT p.*, ps.*, t.team_name 
            FROM players p 
            LEFT JOIN player_stats ps ON p.player_id = ps.player_id 
            LEFT JOIN teams t ON p.current_team_id = t.team_id
            WHERE p.player_id = $player_id";
    
    $result = $conn->query($sql);
    $player = $result ? $result->fetch_assoc() : null;
    
    closeDBConnection($conn);
    return $player;
}

// Get unsold players by group
function getUnsoldPlayersByGroup($group) {
    $conn = getDBConnection();
    $group = $conn->real_escape_string($group);
    
    $sql = "SELECT p.* FROM players p 
            WHERE p.auction_group = '$group' AND p.is_sold = FALSE 
            ORDER BY RAND()";
    
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

// Add new player
function addPlayer($data) {
    $conn = getDBConnection();
    
    $player_name = $conn->real_escape_string($data['player_name']);
    $player_type = $conn->real_escape_string($data['player_type']);
    $player_role = $conn->real_escape_string($data['player_role']);
    $base_price = $conn->real_escape_string($data['base_price']);
    // Auto-calculate auction group based on base price
    $auction_group = isset($data['auction_group']) && $data['auction_group'] != '' 
        ? $conn->real_escape_string($data['auction_group']) 
        : calculateAuctionGroup($base_price);
    $nationality = $conn->real_escape_string($data['nationality']);
    $age = $conn->real_escape_string($data['age']);
    $previous_team = $conn->real_escape_string($data['previous_team'] ?? '');
    
    $sql = "INSERT INTO players (player_name, player_type, player_role, base_price, auction_group, nationality, age, previous_team) 
            VALUES ('$player_name', '$player_type', '$player_role', $base_price, '$auction_group', '$nationality', $age, '$previous_team')";
    
    $result = $conn->query($sql);
    $player_id = $result ? $conn->insert_id : 0;
    
    // Add default stats
    if ($player_id > 0) {
        $sql = "INSERT INTO player_stats (player_id) VALUES ($player_id)";
        $conn->query($sql);
    }
    
    closeDBConnection($conn);
    return $player_id;
}

// Update player stats
function updatePlayerStats($player_id, $stats) {
    $conn = getDBConnection();
    $player_id = $conn->real_escape_string($player_id);
    
    $updates = [];
    foreach ($stats as $key => $value) {
        $key = $conn->real_escape_string($key);
        $value = $conn->real_escape_string($value);
        $updates[] = "$key = '$value'";
    }
    
    $sql = "UPDATE player_stats SET " . implode(', ', $updates) . " WHERE player_id = $player_id";
    $result = $conn->query($sql);
    
    closeDBConnection($conn);
    return $result;
}

// Format currency (in crores)
function formatCurrency($amount) {
    $crores = $amount / 10000000;
    return number_format($crores, 2) . ' Cr';
}
?>
