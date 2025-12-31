<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPL Auction 2026 - Virtual Auction Platform</title>
    <link rel="stylesheet" href="assets/css/style.css?v=2.0">
</head>
<body>
    <?php 
    require_once 'config/session.php';
    require_once 'includes/update_functions.php';
    require_once 'includes/team_functions.php';
    require_once 'includes/player_functions.php';
    
    $updates = getFeaturedUpdates();
    
    // Get real statistics
    $teams_count = getTotalTeamsCount();
    $players_count = getTotalPlayersCount();
    ?>
    
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">🏏 IPL Auction</a>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="pages/players.php">Players</a></li>
                <li><a href="pages/teams.php">Teams</a></li>
                <li><a href="pages/auction.php">Auction</a></li>
                <li><a href="pages/updates.php">Updates</a></li>
                <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                    <li><a href="admin/dashboard.php" class="nav-button" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);">⚙️ Admin Panel</a></li>
                <?php elseif (isLoggedIn()): ?>
                    <li><a href="user/dashboard.php">My Dashboard</a></li>
                    <li><a href="auth/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="auth/login.php" class="nav-button">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="container">
        <div class="hero">
            <h1>🏆 IPL Auction 2026</h1>
            <p>Build Your Dream Cricket Team - Virtual Auction Platform</p>
            <a href="pages/auction.php" class="cta-button">Start Auction</a>
        </div>

        <!-- Updates Section -->
        <div class="card">
            <div class="card-header">
                <h2>Latest Updates</h2>
            </div>
            <div class="grid grid-3">
                <?php foreach ($updates as $update): ?>
                    <div class="player-card">
                        <h3><?php echo htmlspecialchars($update['title']); ?></h3>
                        <p style="color: #666; margin-top: 0.5rem;">
                            <?php echo substr(htmlspecialchars($update['content']), 0, 150); ?>...
                        </p>
                        <span class="badge badge-info"><?php echo $update['category']; ?></span>
                        <p style="font-size: 0.85rem; color: #999; margin-top: 0.5rem;">
                            <?php echo date('M d, Y', strtotime($update['created_at'])); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center; margin-top: 2rem;">
                <a href="pages/updates.php" class="btn btn-primary">View All Updates</a>
            </div>
        </div>

        <!-- Features Section -->
        <div class="card">
            <div class="card-header">
                <h2>Features</h2>
            </div>
            <div class="grid grid-3">
                <div class="team-card">
                    <h3>💰 Budget Management</h3>
                    <p>Each team gets ₹120 Crores to build their squad</p>
                </div>
                <div class="team-card">
                    <h3>👥 Player Categories</h3>
                    <p>Indian, Overseas, Capped & Uncapped players</p>
                </div>
                <div class="team-card">
                    <h3>📊 Detailed Stats</h3>
                    <p>Complete player statistics and records</p>
                </div>
                <div class="team-card">
                    <h3>🎯 Automated Auction</h3>
                    <p>Random player selection from groups A, B, C, D</p>
                </div>
                <div class="team-card">
                    <h3>⚡ Real-time Bidding</h3>
                    <p>Live auction updates and team management</p>
                </div>
                <div class="team-card">
                    <h3>🏏 IPL Updates</h3>
                    <p>Latest news and auction announcements</p>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-4">
            <div class="team-card">
                <h3><?php echo $teams_count; ?></h3>
                <p>Teams</p>
            </div>
            <div class="team-card">
                <h3><?php echo $players_count; ?></h3>
                <p>Players</p>
            </div>
            <div class="team-card">
                <h3>120 Cr</h3>
                <p>Budget Per Team</p>
            </div>
            <div class="team-card">
                <h3>3</h3>
                <p>Player Groups</p>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
