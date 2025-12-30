<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams - IPL Auction</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    require_once '../config/session.php';
    require_once '../includes/team_functions.php';
    require_once '../includes/player_functions.php';
    
    $teams = getAllTeams();
    $selected_team_id = isset($_GET['team_id']) ? $_GET['team_id'] : null;
    $team_players = [];
    $team_stats = null;
    
    if ($selected_team_id) {
        $team_players = getTeamPlayers($selected_team_id);
        $team_stats = getTeamStatistics($selected_team_id);
    }
    ?>
    
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">🏏 IPL Auction</a>
            <ul class="nav-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="players.php">Players</a></li>
                <li><a href="teams.php">Teams</a></li>
                <li><a href="auction.php">Auction</a></li>
                <li><a href="updates.php">Updates</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="../auth/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="../auth/login.php" class="nav-button">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>All Teams</h2>
            </div>

            <div class="grid grid-4">
                <?php foreach ($teams as $team): ?>
                    <div class="team-card">
                        <h3 class="team-name"><?php echo htmlspecialchars($team['team_name']); ?></h3>
                        <div class="team-budget">
                            <div class="budget-label">Remaining Budget</div>
                            <div class="budget-value"><?php echo formatCurrency($team['remaining_budget']); ?></div>
                        </div>
                        <p><strong>Players:</strong> <?php echo $team['total_players']; ?></p>
                        <p><strong>Spent:</strong> <?php echo formatCurrency($team['total_budget'] - $team['remaining_budget']); ?></p>
                        <a href="teams.php?team_id=<?php echo $team['team_id']; ?>" class="btn btn-primary" style="margin-top: 1rem; width: 100%;">View Squad</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($selected_team_id): ?>
            <?php $selected_team = getTeamById($selected_team_id); ?>
            <div class="card">
                <div class="card-header">
                    <h2><?php echo htmlspecialchars($selected_team['team_name']); ?> Squad</h2>
                </div>

                <!-- Team Statistics -->
                <?php if ($team_stats && $team_stats['indian_players'] > 0): ?>
                    <div class="grid grid-4" style="margin-bottom: 2rem;">
                        <div class="team-card">
                            <h3><?php echo $team_stats['indian_players']; ?></h3>
                            <p>Indian Players</p>
                        </div>
                        <div class="team-card">
                            <h3><?php echo $team_stats['overseas_players']; ?></h3>
                            <p>Overseas Players</p>
                        </div>
                        <div class="team-card">
                            <h3><?php echo $team_stats['batsmen'] + $team_stats['allrounders']; ?></h3>
                            <p>Batsmen</p>
                        </div>
                        <div class="team-card">
                            <h3><?php echo $team_stats['bowlers']; ?></h3>
                            <p>Bowlers</p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Players List -->
                <?php if (!empty($team_players)): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Player Name</th>
                                <th>Type</th>
                                <th>Role</th>
                                <th>Nationality</th>
                                <th>Purchase Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($team_players as $player): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($player['player_name']); ?></strong></td>
                                    <td>
                                        <span class="player-type type-<?php echo strtolower(str_replace(' ', '-', $player['player_type'])); ?>">
                                            <?php echo $player['player_type']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $player['player_role']; ?></td>
                                    <td><?php echo $player['nationality']; ?></td>
                                    <td><strong><?php echo formatCurrency($player['purchased_price']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; color: #999;">
                        <h3>No players bought yet</h3>
                        <p>This team hasn't made any purchases in the auction</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
