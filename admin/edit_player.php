<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';
require_once '../includes/player_functions.php';

$player_id = $_GET['id'] ?? 0;
$conn = getDBConnection();

// Get player data
$player_id_clean = $conn->real_escape_string($player_id);
$player = getPlayerById($player_id_clean);

if (!$player) {
    header('Location: dashboard.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $player_name = $conn->real_escape_string($_POST['player_name']);
    $player_type = $conn->real_escape_string($_POST['player_type']);
    $player_role = $conn->real_escape_string($_POST['player_role']);
    $base_price = $conn->real_escape_string($_POST['base_price']);
    $nationality = $conn->real_escape_string($_POST['nationality']);
    $age = $conn->real_escape_string($_POST['age']);
    $previous_team = $conn->real_escape_string($_POST['previous_team']);
    
    // Calculate auction group based on base price
    $auction_group = calculateAuctionGroup($base_price);
    
    $sql = "UPDATE players SET 
            player_name = '$player_name',
            player_type = '$player_type',
            player_role = '$player_role',
            base_price = $base_price,
            auction_group = '$auction_group',
            nationality = '$nationality',
            age = $age,
            previous_team = '$previous_team'
            WHERE player_id = $player_id_clean";
    
    if ($conn->query($sql)) {
        $success = "Player updated successfully!";
        // Refresh player data
        $player = getPlayerById($player_id_clean);
    } else {
        $error = "Error updating player: " . $conn->error;
    }
}

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Player - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2.0">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">🏏 IPL Admin</a>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="add_player.php">Add Player</a></li>
                <li><a href="../index.php">View Site</a></li>
                <li><a href="logout.php" class="nav-button">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card" style="max-width: 800px; margin: 2rem auto;">
            <div class="card-header">
                <h2>Edit Player</h2>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label>Player Name *</label>
                        <input type="text" name="player_name" class="form-control" required 
                               value="<?php echo htmlspecialchars($player['player_name']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Player Type *</label>
                        <select name="player_type" class="form-control" required>
                            <option value="Indian" <?php echo $player['player_type'] == 'Indian' ? 'selected' : ''; ?>>Indian</option>
                            <option value="Indian Uncapped" <?php echo $player['player_type'] == 'Indian Uncapped' ? 'selected' : ''; ?>>Indian Uncapped</option>
                            <option value="Overseas" <?php echo $player['player_type'] == 'Overseas' ? 'selected' : ''; ?>>Overseas</option>
                            <option value="Overseas Uncapped" <?php echo $player['player_type'] == 'Overseas Uncapped' ? 'selected' : ''; ?>>Overseas Uncapped</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Player Role *</label>
                        <select name="player_role" class="form-control" required>
                            <option value="Batsman" <?php echo $player['player_role'] == 'Batsman' ? 'selected' : ''; ?>>Batsman</option>
                            <option value="Bowler" <?php echo $player['player_role'] == 'Bowler' ? 'selected' : ''; ?>>Bowler</option>
                            <option value="All-Rounder" <?php echo $player['player_role'] == 'All-Rounder' ? 'selected' : ''; ?>>All-Rounder</option>
                            <option value="Wicket-Keeper" <?php echo $player['player_role'] == 'Wicket-Keeper' ? 'selected' : ''; ?>>Wicket-Keeper</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Base Price (in Rupees) *</label>
                        <input type="number" name="base_price" class="form-control" required 
                               value="<?php echo $player['base_price']; ?>" step="100000" min="3000000">
                        <small style="color: #64748b;">Enter price in rupees (e.g., 20000000 for ₹2 Cr)</small>
                    </div>

                    <div class="form-group">
                        <label>Nationality *</label>
                        <input type="text" name="nationality" class="form-control" required 
                               value="<?php echo htmlspecialchars($player['nationality']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Age *</label>
                        <input type="number" name="age" class="form-control" required 
                               value="<?php echo $player['age']; ?>" min="16" max="50">
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Previous Team</label>
                        <input type="text" name="previous_team" class="form-control" 
                               value="<?php echo htmlspecialchars($player['previous_team'] ?? ''); ?>" 
                               placeholder="e.g., CSK, MI, RCB">
                    </div>
                </div>

                <div style="background: #f8fafc; padding: 1rem; border-radius: 10px; margin: 1rem 0;">
                    <p style="margin: 0; color: #64748b;">
                        <strong>Current Group:</strong> Group <?php echo $player['auction_group']; ?> 
                        (<?php echo formatCurrency($player['base_price']); ?>)
                    </p>
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.85rem; color: #64748b;">
                        Group will be automatically updated based on base price:<br>
                        • Group A: ≥ ₹200 Lakh | Group B: ₹100-200 Lakh | Group C: < ₹100 Lakh
                    </p>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-success" style="flex: 1;">
                        Update Player
                    </button>
                    <a href="dashboard.php" class="btn btn-warning" style="flex: 1; text-align: center;">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
