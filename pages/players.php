<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Players - IPL Auction</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2.0">
</head>
<body>
    <?php 
    require_once '../config/session.php';
    require_once '../includes/player_functions.php';
    
    // Get filter parameters
    $filters = [];
    if (isset($_GET['type'])) $filters['player_type'] = $_GET['type'];
    if (isset($_GET['group'])) $filters['auction_group'] = $_GET['group'];
    if (isset($_GET['sold'])) $filters['is_sold'] = $_GET['sold'] == '1';
    
    $players = getAllPlayers($filters);
    ?>
    
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">🏏 IPL Auction</a>
            <ul class="nav-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="players.php">Players</a></li>
                <li><a href="teams.php">Teams</a></li>
                <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                    <li><a href="../admin/dashboard.php" class="nav-button" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);">⚙️ Admin Panel</a></li>
                <?php elseif (isLoggedIn()): ?>
                    <li><a href="my-auctions.php">My Auctions</a></li>
                    <li><a href="../user/dashboard.php">Dashboard</a></li>
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
                <h2>Player Pool</h2>
            </div>

            <!-- Filters -->
            <div class="grid grid-4" style="margin-bottom: 2rem;">
                <div class="form-group">
                    <label>Player Type</label>
                    <select id="filter-type" class="form-control">
                        <option value="">All Types</option>
                        <option value="Indian" <?php echo (isset($_GET['type']) && $_GET['type'] == 'Indian') ? 'selected' : ''; ?>>Indian</option>
                        <option value="Indian Uncapped" <?php echo (isset($_GET['type']) && $_GET['type'] == 'Indian Uncapped') ? 'selected' : ''; ?>>Indian Uncapped</option>
                        <option value="Overseas" <?php echo (isset($_GET['type']) && $_GET['type'] == 'Overseas') ? 'selected' : ''; ?>>Overseas</option>
                        <option value="Overseas Uncapped" <?php echo (isset($_GET['type']) && $_GET['type'] == 'Overseas Uncapped') ? 'selected' : ''; ?>>Overseas Uncapped</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Group</label>
                    <select id="filter-group" class="form-control">
                        <option value="">All Groups</option>
                        <option value="Marquee" <?php echo (isset($_GET['group']) && $_GET['group'] == 'Marquee') ? 'selected' : ''; ?>>Marquee (Marquee Players)</option>
                        <option value="A" <?php echo (isset($_GET['group']) && $_GET['group'] == 'A') ? 'selected' : ''; ?>>Group A (>= ₹200 Lakh)</option>
                        <option value="B" <?php echo (isset($_GET['group']) && $_GET['group'] == 'B') ? 'selected' : ''; ?>>Group B (₹100-200 Lakh)</option>
                        <option value="C" <?php echo (isset($_GET['group']) && $_GET['group'] == 'C') ? 'selected' : ''; ?>>Group C (< ₹100 Lakh)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="filter-sold" class="form-control">
                        <option value="">All Players</option>
                        <option value="0" <?php echo (isset($_GET['sold']) && $_GET['sold'] == '0') ? 'selected' : ''; ?>>Unsold</option>
                        <option value="1" <?php echo (isset($_GET['sold']) && $_GET['sold'] == '1') ? 'selected' : ''; ?>>Sold</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button onclick="filterPlayers()" class="btn btn-primary" style="width: 100%;">Apply Filters</button>
                </div>
            </div>

            <!-- Players Grid -->
            <div class="grid grid-3">
                <?php foreach ($players as $player): ?>
                    <div class="player-card">
                        <div class="player-header">
                            <div>
                                <div class="player-name"><?php echo htmlspecialchars($player['player_name']); ?></div>
                                <p style="color: #999; font-size: 0.9rem; margin-top: 0.3rem;">
                                    <?php echo $player['player_role']; ?> | <?php echo $player['nationality']; ?>
                                </p>
                            </div>
                            <span class="player-type type-<?php echo strtolower(str_replace(' ', '-', $player['player_type'])); ?>">
                                <?php echo $player['player_type']; ?>
                            </span>
                        </div>

                        <div class="player-info">
                            <p><strong>Age:</strong> <?php echo $player['age']; ?> years</p>
                            <p><strong>Group:</strong> <?php 
                                echo $player['auction_group'];
                                $group_desc = ['Marquee' => ' (Marquee)', 'A' => ' (>= ₹200 L)', 'B' => ' (₹100-200 L)', 'C' => ' (< ₹100 L)'];
                                echo isset($group_desc[$player['auction_group']]) ? $group_desc[$player['auction_group']] : '';
                            ?></p>
                            <p><strong>Base Price:</strong> <?php echo formatCurrency($player['base_price']); ?></p>
                            <?php if ($player['previous_team']): ?>
                                <p><strong>Previous Team:</strong> <?php echo $player['previous_team']; ?></p>
                            <?php endif; ?>
                            <?php if ($player['is_sold']): ?>
                                <p><strong>Sold Price:</strong> <span style="color: #28a745; font-weight: bold;"><?php echo formatCurrency($player['sold_price']); ?></span></p>
                            <?php endif; ?>
                        </div>

                        <?php if ($player['matches_played'] > 0): ?>
                            <div class="player-stats">
                                <h4 style="margin-bottom: 0.5rem;">Career Stats</h4>
                                <div class="stats-grid">
                                    <div class="stat-item">
                                        <span class="stat-label">Matches:</span>
                                        <span class="stat-value"><?php echo $player['matches_played']; ?></span>
                                    </div>
                                    <?php if ($player['runs_scored'] > 0): ?>
                                        <div class="stat-item">
                                            <span class="stat-label">Runs:</span>
                                            <span class="stat-value"><?php echo $player['runs_scored']; ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label">Average:</span>
                                            <span class="stat-value"><?php echo $player['batting_average']; ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label">Strike Rate:</span>
                                            <span class="stat-value"><?php echo $player['strike_rate']; ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($player['wickets_taken'] > 0): ?>
                                        <div class="stat-item">
                                            <span class="stat-label">Wickets:</span>
                                            <span class="stat-value"><?php echo $player['wickets_taken']; ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label">Economy:</span>
                                            <span class="stat-value"><?php echo $player['economy_rate']; ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top: 1rem;">
                            <?php if ($player['is_sold']): ?>
                                <span class="badge badge-success">SOLD</span>
                            <?php else: ?>
                                <span class="badge badge-warning">AVAILABLE</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($players)): ?>
                <div style="text-align: center; padding: 3rem; color: #999;">
                    <h3>No players found</h3>
                    <p>Try adjusting your filters</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
