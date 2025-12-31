<?php
require_once __DIR__ . '/../config/database.php';

// Get all teams
function getAllTeams() {
    $sql = "SELECT t.*, COUNT(tp.player_id) as total_players 
            FROM teams t 
            LEFT JOIN team_players tp ON t.team_id = tp.team_id 
            GROUP BY t.team_id 
            ORDER BY t.team_name";
    
    return getAllRows($sql);
}

// Get team by ID
function getTeamById($team_id) {
    $conn = getDBConnection();
    $team_id = $conn->real_escape_string($team_id);
    
    $sql = "SELECT * FROM teams WHERE team_id = $team_id";
    $team = getSingleRow($sql);
    
    closeDBConnection($conn);
    return $team;
}

// Get team players
function getTeamPlayers($team_id) {
    $conn = getDBConnection();
    $team_id = $conn->real_escape_string($team_id);
    
    $sql = "SELECT p.*, tp.purchased_price 
            FROM players p 
            INNER JOIN team_players tp ON p.player_id = tp.player_id 
            WHERE tp.team_id = $team_id 
            ORDER BY tp.purchased_price DESC";
    
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

// Update team budget
function updateTeamBudget($team_id, $amount_spent) {
    $conn = getDBConnection();
    $team_id = $conn->real_escape_string($team_id);
    $amount_spent = $conn->real_escape_string($amount_spent);
    
    $sql = "UPDATE teams SET 
            remaining_budget = remaining_budget - $amount_spent,
            players_count = players_count + 1 
            WHERE team_id = $team_id";
    
    $result = $conn->query($sql);
    closeDBConnection($conn);
    
    return $result;
}

// Add player to team
function addPlayerToTeam($team_id, $player_id, $price) {
    $conn = getDBConnection();
    $team_id = $conn->real_escape_string($team_id);
    $player_id = $conn->real_escape_string($player_id);
    $price = $conn->real_escape_string($price);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Add to team_players
        $sql = "INSERT INTO team_players (team_id, player_id, purchased_price) 
                VALUES ($team_id, $player_id, $price)";
        $conn->query($sql);
        
        // Update player
        $sql = "UPDATE players SET is_sold = TRUE, current_team_id = $team_id, sold_price = $price 
                WHERE player_id = $player_id";
        $conn->query($sql);
        
        // Update team budget
        updateTeamBudget($team_id, $price);
        
        $conn->commit();
        closeDBConnection($conn);
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        closeDBConnection($conn);
        return false;
    }
}

// Get team statistics
function getTeamStatistics($team_id) {
    $conn = getDBConnection();
    $team_id = $conn->real_escape_string($team_id);
    
    $sql = "SELECT 
            COUNT(CASE WHEN p.player_type LIKE 'Indian%' THEN 1 END) as indian_players,
            COUNT(CASE WHEN p.player_type LIKE 'Overseas%' THEN 1 END) as overseas_players,
            COUNT(CASE WHEN p.player_type LIKE '%Uncapped' THEN 1 END) as uncapped_players,
            COUNT(CASE WHEN p.player_role = 'Batsman' THEN 1 END) as batsmen,
            COUNT(CASE WHEN p.player_role = 'Bowler' THEN 1 END) as bowlers,
            COUNT(CASE WHEN p.player_role = 'All-Rounder' THEN 1 END) as allrounders,
            COUNT(CASE WHEN p.player_role = 'Wicket-Keeper' THEN 1 END) as wicketkeepers,
            SUM(tp.purchased_price) as total_spent
            FROM team_players tp
            INNER JOIN players p ON tp.player_id = p.player_id
            WHERE tp.team_id = $team_id";
    
    $stats = getSingleRow($sql);
    closeDBConnection($conn);
    
    return $stats;
}

// Get total teams count
function getTotalTeamsCount() {
    $sql = "SELECT COUNT(*) as count FROM teams";
    $result = getSingleRow($sql);
    return $result['count'] ?? 0;
}
?>
