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
                <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                    <li><a href="admin/dashboard.php" class="nav-button" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);">⚙️ Admin Panel</a></li>
                <?php elseif (isLoggedIn()): ?>
                    <li><a href="pages/my-auctions.php">My Auctions</a></li>
                    <li><a href="user/dashboard.php">Dashboard</a></li>
                    <li><a href="auth/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="auth/login.php" class="nav-button">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="container">
        <div class="card" style="padding: 3rem 2rem; margin-bottom: 3rem;">
            <div class="card-header" style="text-align: center; border: none; padding-bottom: 1rem;">
                <h1 style="font-size: 3rem; margin: 0 0 0.5rem 0; color: #1f2937; font-weight: 800;">🏆 IPL Auction 2026</h1>
                <p style="font-size: 1.2rem; color: #6b7280; margin: 0 0 2rem 0; font-weight: 500;">Build Your Dream Cricket Team - Virtual Auction Platform</p>
            </div>
            
            <?php if (isLoggedIn()): ?>
                <!-- Multiplayer Section -->
                <div style="background: #f9fafb; padding: 2rem; border-radius: 12px; border: 2px solid #e5e7eb;">
                    <h2 style="margin: 0 0 0.5rem 0; font-size: 1.8rem; color: #3b82f6; font-weight: 700; text-align: center;">
                        🎮 Multiplayer Auctions Now Live!
                    </h2>
                    <p style="color: #6b7280; font-size: 1.05rem; margin-bottom: 1.5rem; text-align: center;">
                        Create your own auction room and compete with friends in real-time!
                    </p>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="pages/create-auction.php" style="padding: 1rem 2rem; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 1.05rem; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.3s; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(59, 130, 246, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.3)';">
                            <span style="font-size: 1.2rem;">🎯</span> Create Auction Room
                        </a>
                        <a href="pages/join-auction.php" style="padding: 1rem 2rem; background: white; color: #3b82f6; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 1.05rem; display: inline-flex; align-items: center; gap: 0.5rem; border: 2px solid #3b82f6; transition: all 0.3s;" onmouseover="this.style.background='#eff6ff'; this.style.transform='translateY(-2px)';" onmouseout="this.style.background='white'; this.style.transform='translateY(0)';">
                            <span style="font-size: 1.2rem;">🔗</span> Join with Code
                        </a>
                        <a href="pages/my-auctions.php" style="padding: 1rem 2rem; background: white; color: #3b82f6; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 1.05rem; display: inline-flex; align-items: center; gap: 0.5rem; border: 2px solid #3b82f6; transition: all 0.3s;" onmouseover="this.style.background='#eff6ff'; this.style.transform='translateY(-2px)';" onmouseout="this.style.background='white'; this.style.transform='translateY(0)';">
                            <span style="font-size: 1.2rem;">📋</span> My Auctions
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Not Logged In -->
                <div style="text-align: center; background: #f9fafb; padding: 2rem; border-radius: 12px; border: 2px solid #e5e7eb;">
                    <p style="color: #6b7280; margin: 0; font-size: 1.1rem; font-weight: 500;">
                        🎮 <a href="auth/login.php" style="color: #3b82f6; text-decoration: none; font-weight: 700; border-bottom: 2px solid #3b82f6;" onmouseover="this.style.color='#2563eb';" onmouseout="this.style.color='#3b82f6';">Login</a> to unlock multiplayer auctions with friends!
                    </p>
                </div>
            <?php endif; ?>
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
