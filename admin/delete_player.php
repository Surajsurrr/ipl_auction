<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$player_id = $_GET['id'] ?? 0;

if ($player_id > 0) {
    $conn = getDBConnection();
    $player_id_clean = $conn->real_escape_string($player_id);
    
    // First, delete related player stats
    $conn->query("DELETE FROM player_stats WHERE player_id = $player_id_clean");
    
    // Then delete the player
    $sql = "DELETE FROM players WHERE player_id = $player_id_clean";
    
    if ($conn->query($sql)) {
        $_SESSION['delete_success'] = "Player deleted successfully!";
    } else {
        $_SESSION['delete_error'] = "Error deleting player: " . $conn->error;
    }
    
    closeDBConnection($conn);
}

header('Location: dashboard.php');
exit();
?>
