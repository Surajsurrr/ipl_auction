<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Player - IPL Auction</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2.0">
</head>
<body>
    <?php 
    session_start();
    
    // Check if admin is logged in
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        header('Location: login.php');
        exit();
    }
    
    require_once '../config/database.php';
    require_once '../includes/player_functions.php';
    
    $success = '';
    $error = '';
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $player_data = [
            'player_name' => $_POST['player_name'],
            'player_type' => $_POST['player_type'],
            'player_role' => $_POST['player_role'],
            'base_price' => $_POST['base_price'], // Already in rupees
            'nationality' => $_POST['nationality'],
            'age' => $_POST['age'],
            'previous_team' => $_POST['previous_team']
        ];
        
        $player_id = addPlayer($player_data);
        
        if ($player_id) {
            $success = 'Player added successfully!';
        } else {
            $error = 'Failed to add player. Please try again.';
        }
    }
    ?>
    
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
            <a href="../index.php" class="logo">🏏 IPL Auction</a>
            <ul class="nav-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="../pages/players.php">Players</a></li>
                <li><a href="../pages/teams.php">Teams</a></li>
                <li><a href="../pages/auction.php">Auction</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card" style="max-width: 600px; margin: 2rem auto;">
            <div class="card-header">
                <h2>Add New Player</h2>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="player_name">Player Name *</label>
                    <input type="text" id="player_name" name="player_name" class="form-control" required>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="player_type">Player Type *</label>
                        <select id="player_type" name="player_type" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="Indian">Indian</option>
                            <option value="Indian Uncapped">Indian Uncapped</option>
                            <option value="Overseas">Overseas</option>
                            <option value="Overseas Uncapped">Overseas Uncapped</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="player_role">Player Role *</label>
                        <select id="player_role" name="player_role" class="form-control" required>
                            <option value="">Select Role</option>
                            <option value="Batsman">Batsman</option>
                            <option value="Bowler">Bowler</option>
                            <option value="All-Rounder">All-Rounder</option>
                            <option value="Wicket-Keeper">Wicket-Keeper</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="nationality">Nationality *</label>
                        <input type="text" id="nationality" name="nationality" class="form-control" required placeholder="e.g., India, Australia">
                    </div>

                    <div class="form-group">
                        <label for="age">Age *</label>
                        <input type="number" id="age" name="age" class="form-control" required min="18" max="45">
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="auction_group">Auction Group *</label>
                        <select id="auction_group" name="auction_group" class="form-control" required>
                            <option value="">Select Group</option>
                            <option value="A">Group A (Premium)</option>
                            <option value="B">Group B (Star)</option>
                            <option value="C">Group C (Mid-tier)</option>
                            <option value="D">Group D (Budget)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="base_price">Base Price (Crores) *</label>
                        <input type="number" id="base_price" name="base_price" class="form-control" required step="0.1" min="0.2" placeholder="e.g., 2.0">
                    </div>
                </div>

                <div class="form-group">
                    <label for="previous_team">Previous Team</label>
                    <input type="text" id="previous_team" name="previous_team" class="form-control" placeholder="e.g., MI, CSK, RCB">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Add Player</button>
            </form>

            <div style="margin-top: 2rem;">
                <a href="../pages/players.php" class="btn btn-warning" style="width: 100%; text-align: center;">View All Players</a>
            </div>
        </div>

        <!-- Helper Guide -->
        <div class="card" style="max-width: 600px; margin: 2rem auto; background: #f8f9fa;">
            <h3>Group Guidelines</h3>
            <ul style="line-height: 2;">
                <li><strong>Group A:</strong> Premium players (15-20 Cr base price)</li>
                <li><strong>Group B:</strong> Star players (8-12 Cr base price)</li>
                <li><strong>Group C:</strong> Mid-tier players (5-8 Cr base price)</li>
                <li><strong>Group D:</strong> Budget/Uncapped (2-3 Cr base price)</li>
            </ul>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
