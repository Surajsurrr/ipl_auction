<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Auction - IPL Auction</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    require_once '../config/session.php';
    require_once '../includes/auction_functions.php';
    require_once '../includes/player_functions.php';
    require_once '../includes/team_functions.php';
    
    $session = getOrCreateSession();
    $current_player = null;
    $current_team = null;
    $teams = getAllTeams();
    
    if ($session && $session['current_player_id']) {
        $current_player = getPlayerById($session['current_player_id']);
        if ($session['current_bidder_team_id']) {
            $current_team = getTeamById($session['current_bidder_team_id']);
        }
    }
    
    $auction_stats = getAuctionStatistics();
    
    // Handle auction actions
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isLoggedIn()) {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'start':
                    startAuction($session['session_id']);
                    header('Location: auction.php');
                    exit();
                    
                case 'next_player':
                    $group = $_POST['group'];
                    $player = getRandomPlayerFromGroup($group);
                    if ($player) {
                        setCurrentPlayer($session['session_id'], $player['player_id'], $group);
                    }
                    header('Location: auction.php');
                    exit();
                    
                case 'place_bid':
                    $team_id = $_POST['team_id'];
                    $increment = $_POST['increment'] ?? 1000000;
                    $current_bid = $session['current_bid'];
                    $new_bid = $current_bid + $increment;
                    placeBid($session['session_id'], $team_id, $current_player['player_id'], $new_bid);
                    header('Location: auction.php');
                    exit();
                    
                case 'finalize':
                    finalizePlayerSale($session['session_id']);
                    header('Location: auction.php');
                    exit();
                    
                case 'pass':
                    passPlayer($session['session_id']);
                    header('Location: auction.php');
                    exit();
            }
        }
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
        <!-- Auction Statistics -->
        <div class="grid grid-4">
            <div class="team-card">
                <h3><?php echo $auction_stats['sold_players']; ?></h3>
                <p>Players Sold</p>
            </div>
            <div class="team-card">
                <h3><?php echo $auction_stats['unsold_players']; ?></h3>
                <p>Players Remaining</p>
            </div>
            <div class="team-card">
                <h3><?php echo formatCurrency($auction_stats['total_money_spent'] ?? 0); ?></h3>
                <p>Total Spent</p>
            </div>
            <div class="team-card">
                <h3><?php echo formatCurrency($auction_stats['avg_price'] ?? 0); ?></h3>
                <p>Average Price</p>
            </div>
        </div>

        <div class="auction-container" id="auction-container">
            <?php if (!$session['is_active']): ?>
                <!-- Start Auction -->
                <div style="text-align: center; padding: 3rem;">
                    <h2>Auction Not Started</h2>
                    <p style="margin: 1rem 0;">Click below to begin the IPL Auction 2026</p>
                    <?php if (isLoggedIn()): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="start">
                            <button type="submit" class="btn btn-success" style="font-size: 1.2rem; padding: 1rem 2rem;">
                                🏏 Start Auction
                            </button>
                        </form>
                    <?php else: ?>
                        <p style="color: #999;">Please login to start the auction</p>
                        <a href="../auth/login.php" class="btn btn-primary">Login</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php if ($current_player): ?>
                    <!-- Current Player on Auction -->
                    <div class="current-player">
                        <h2 id="current-player-name"><?php echo htmlspecialchars($current_player['player_name']); ?></h2>
                        <p style="font-size: 1.2rem; margin: 0.5rem 0;">
                            <?php echo $current_player['player_type']; ?> | <?php echo $current_player['player_role']; ?>
                        </p>
                        <p><?php echo $current_player['nationality']; ?>, <?php echo $current_player['age']; ?> years</p>
                        <p><strong>Group <?php echo $current_player['auction_group']; ?></strong> | Base Price: <?php echo formatCurrency($current_player['base_price']); ?></p>
                        
                        <div class="current-bid">
                            Current Bid: <span id="current-bid-amount" data-value="<?php echo $session['current_bid']; ?>"><?php echo formatCurrency($session['current_bid']); ?></span>
                        </div>
                        
                        <?php if ($current_team): ?>
                            <p style="font-size: 1.1rem;">
                                Highest Bidder: <strong id="current-bidder"><?php echo htmlspecialchars($current_team['team_name']); ?></strong>
                            </p>
                        <?php else: ?>
                            <p style="font-size: 1.1rem;" id="current-bidder">No bids yet</p>
                        <?php endif; ?>
                    </div>

                    <!-- Bidding Controls -->
                    <?php if (isLoggedIn()): ?>
                        <div class="card">
                            <h3>Place Your Bid</h3>
                            <div class="grid grid-4" style="margin-top: 1rem;">
                                <?php foreach ($teams as $team): ?>
                                    <div class="team-card">
                                        <h4><?php echo htmlspecialchars($team['team_name']); ?></h4>
                                        <p style="font-size: 0.9rem;">Budget: <?php echo formatCurrency($team['remaining_budget']); ?></p>
                                        <form method="POST" style="margin-top: 0.5rem;">
                                            <input type="hidden" name="action" value="place_bid">
                                            <input type="hidden" name="team_id" value="<?php echo $team['team_id']; ?>">
                                            <button type="submit" name="increment" value="1000000" class="btn btn-primary" style="width: 100%; margin-bottom: 0.5rem;">+10 L</button>
                                            <button type="submit" name="increment" value="5000000" class="btn btn-primary" style="width: 100%;">+50 L</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Auction Control -->
                        <div class="bid-controls">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="finalize">
                                <button type="submit" class="btn btn-success" onclick="return confirmAction('Finalize this sale?')">
                                    ✅ Sold!
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="pass">
                                <button type="submit" class="btn btn-danger" onclick="return confirmAction('Pass on this player?')">
                                    ❌ Pass
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- No Current Player - Select Next -->
                    <div style="text-align: center; padding: 3rem;">
                        <h2>Select Next Player</h2>
                        <p style="margin: 1rem 0;">Choose a group to randomly select the next player</p>
                        
                        <?php if (isLoggedIn()): ?>
                            <div style="max-width: 600px; margin: 2rem auto;">
                                <?php 
                                $groups = [
                                    'A' => '> ₹2 Crore',
                                    'B' => '₹1-2 Crore',
                                    'C' => '< ₹1 Crore'
                                ];
                                foreach ($groups as $group => $description): 
                                ?>
                                    <form method="POST" style="display: inline-block; width: 30%; margin: 0.5rem;">
                                        <input type="hidden" name="action" value="next_player">
                                        <input type="hidden" name="group" value="<?php echo $group; ?>">
                                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1.5rem 1rem;">
                                            <div style="font-size: 1.5rem; font-weight: bold;">Group <?php echo $group; ?></div>
                                            <div style="font-size: 0.9rem; margin-top: 0.5rem;"><?php echo $description; ?></div>
                                        </button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: #999;">Please login to continue</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
