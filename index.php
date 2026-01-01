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
                    <?php 
                        $is_auction_rules = stripos($update['title'], 'auction rules') !== false;
                        $is_welcome = stripos($update['title'], 'welcome') !== false;
                        $is_player_pool = stripos($update['title'], 'player pool') !== false;
                        $is_clickable = $is_auction_rules || $is_welcome || $is_player_pool;
                        $onclick_function = '';
                        if ($is_auction_rules) {
                            $onclick_function = 'showMultiplayerRules()';
                        } elseif ($is_welcome) {
                            $onclick_function = 'showAuctionDetails()';
                        } elseif ($is_player_pool) {
                            $onclick_function = 'showPlayerDashboard()';
                        }
                    ?>
                    <div class="player-card" style="cursor: <?php echo $is_clickable ? 'pointer' : 'default'; ?>;" onclick="<?php echo $onclick_function; ?>">
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

        <!-- Modal for Multiplayer Auction Rules -->
        <div id="rulesModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
            <div style="background: white; max-width: 800px; margin: 50px auto; padding: 2rem; border-radius: 12px; position: relative;">
                <button onclick="closeRulesModal()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 2rem; cursor: pointer; color: #666;">&times;</button>
                <h2 style="color: #3b82f6; margin-bottom: 1.5rem;">🎮 Multiplayer Auction Rules</h2>
                
                <div style="line-height: 1.8; color: #333;">
                    <h3 style="color: #1f2937; margin-top: 1.5rem;">📋 Basic Setup</h3>
                    <ul style="margin-left: 1.5rem;">
                        <li><strong>Budget:</strong> Each team gets ₹120 crores to build their squad</li>
                        <li><strong>Squad Size:</strong> Minimum 18 and maximum 25 players per team</li>
                        <li><strong>Auction Code:</strong> Room creator gets a unique 6-digit code to share with friends</li>
                        <li><strong>Teams:</strong> Multiple teams can join using the auction code</li>
                    </ul>

                    <h3 style="color: #1f2937; margin-top: 1.5rem;">👥 Player Categories</h3>
                    <ul style="margin-left: 1.5rem;">
                        <li><strong>Indian Players:</strong> Capped and Uncapped</li>
                        <li><strong>Overseas Players:</strong> Capped and Uncapped</li>
                        <li><strong>Maximum 8 overseas players</strong> per squad</li>
                        <li><strong>Player Groups:</strong> A (₹2 Cr+), B (₹1-2 Cr), C (₹50L-1 Cr), D (<₹50L)</li>
                    </ul>

                    <h3 style="color: #1f2937; margin-top: 1.5rem;">⚡ Bidding Process</h3>
                    <ul style="margin-left: 1.5rem;">
                        <li><strong>Base Price:</strong> Bidding starts at the player's base price</li>
                        <li><strong>Bid Increment:</strong> 
                            <ul style="margin-left: 1rem; margin-top: 0.5rem;">
                                <li>Below ₹3 Cr: ₹10 lakhs minimum increment per bid</li>
                                <li>Above ₹3 Cr: ₹20 lakhs minimum increment per bid</li>
                            </ul>
                        </li>
                        <li><strong>Bid Timer:</strong> 15 seconds for each bid decision</li>
                        <li><strong>Wait Button:</strong> Click "Wait" to extend timer by 10 seconds if you need time to think</li>
                        <li><strong>Real-time Updates:</strong> All teams see bids instantly</li>
                        <li><strong>Winning:</strong> Highest bidder when timer runs out gets the player</li>
                        <li><strong>Unsold Players:</strong> Can be brought back in later rounds</li>
                    </ul>

                    <h3 style="color: #1f2937; margin-top: 1.5rem;">🎯 Auction Flow</h3>
                    <ul style="margin-left: 1.5rem;">
                        <li>Players are randomly selected from groups A, B, C, and D</li>
                        <li>Teams take turns or bid simultaneously (based on room settings)</li>
                        <li>Budget is automatically deducted after successful bid</li>
                        <li>Squad composition rules are enforced automatically</li>
                        <li>Live leaderboard shows team progress</li>
                    </ul>

                    <h3 style="color: #1f2937; margin-top: 1.5rem;">🏆 Winning Strategy</h3>
                    <ul style="margin-left: 1.5rem;">
                        <li>Balance your squad between stars and budget players</li>
                        <li>Don't exhaust your budget on marquee players</li>
                        <li>Keep track of overseas player limit (8 max)</li>
                        <li>Ensure you have enough budget to complete minimum 18 players</li>
                        <li>Monitor other teams' budgets and needs</li>
                    </ul>

                    <h3 style="color: #1f2937; margin-top: 1.5rem;">📱 How to Join</h3>
                    <ul style="margin-left: 1.5rem;">
                        <li><strong>Create:</strong> Click "Create Auction Room" to start a new auction</li>
                        <li><strong>Share:</strong> Share the 6-digit code with friends</li>
                        <li><strong>Join:</strong> Friends click "Join with Code" and enter the code</li>
                        <li><strong>Start:</strong> Creator starts the auction when all teams are ready</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Modal for Auction Details -->
        <div id="auctionDetailsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
            <div style="background: white; max-width: 900px; margin: 50px auto; padding: 2rem; border-radius: 12px; position: relative;">
                <button onclick="closeAuctionDetailsModal()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 2rem; cursor: pointer; color: #666;">&times;</button>
                <h2 style="color: #3b82f6; margin-bottom: 1.5rem;">🏆 IPL Auction 2026 - Live Updates</h2>
                
                <div style="line-height: 1.8; color: #333;">
                    <h3 style="color: #1f2937; margin-top: 1.5rem;">🚀 What's New in 2026</h3>
                    <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); padding: 1.5rem; border-radius: 10px; margin-bottom: 1.5rem; border-left: 4px solid #3b82f6;">
                        <ul style="margin: 0; padding-left: 1.5rem;">
                            <li><strong>Multiplayer Auctions:</strong> Create private rooms and compete with friends in real-time</li>
                            <li><strong>Smart Timer System:</strong> 15-second countdown with extendable wait option</li>
                            <li><strong>Dynamic Bidding:</strong> Intelligent bid increments that adapt to price levels</li>
                            <li><strong>682 Real Players:</strong> Complete IPL 2025 auction player database</li>
                            <li><strong>Live Leaderboards:</strong> Track your team's progress against competitors</li>
                        </ul>
                    </div>

                    <h3 style="color: #1f2937; margin-top: 1.5rem;">🎮 Active Auctions</h3>
                    <?php
                    // Get active auction rooms
                    $active_rooms_sql = "SELECT ar.*, 
                        (SELECT COUNT(*) FROM room_participants WHERE room_id = ar.room_id) as participants_count,
                        (SELECT team_name FROM room_participants WHERE room_id = ar.room_id AND is_host = 1) as host_team
                        FROM auction_rooms ar 
                        WHERE ar.status IN ('waiting', 'in_progress') 
                        ORDER BY ar.created_at DESC LIMIT 5";
                    $conn = getDBConnection();
                    $active_rooms_result = $conn->query($active_rooms_sql);
                    
                    if ($active_rooms_result && $active_rooms_result->num_rows > 0):
                    ?>
                        <div style="background: #f9fafb; padding: 1.5rem; border-radius: 10px; margin-bottom: 1.5rem;">
                            <?php while ($room = $active_rooms_result->fetch_assoc()): ?>
                                <div style="background: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #e5e7eb;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <h4 style="margin: 0 0 0.5rem 0; color: #1f2937;">🎮 <?php echo htmlspecialchars($room['room_name']); ?></h4>
                                            <p style="margin: 0; font-size: 0.875rem; color: #6b7280;">
                                                Host: <?php echo htmlspecialchars($room['host_team']); ?> | 
                                                Players: <?php echo $room['participants_count']; ?>/<?php echo $room['max_participants']; ?> | 
                                                Code: <strong style="color: #3b82f6;"><?php echo $room['room_code']; ?></strong>
                                            </p>
                                        </div>
                                        <span style="padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 600; 
                                            <?php echo $room['status'] == 'in_progress' ? 'background: #dcfce7; color: #166534;' : 'background: #fef3c7; color: #92400e;'; ?>">
                                            <?php echo $room['status'] == 'in_progress' ? '⚡ Live' : '⏳ Waiting'; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php 
                    else:
                    ?>
                        <div style="background: #f9fafb; padding: 1.5rem; border-radius: 10px; margin-bottom: 1.5rem; text-align: center; color: #6b7280;">
                            <p style="margin: 0;">🔍 No active auctions right now. Be the first to create one!</p>
                        </div>
                    <?php 
                    endif;
                    closeDBConnection($conn);
                    ?>

                    <h3 style="color: #1f2937; margin-top: 1.5rem;">📊 Platform Statistics</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                        <div style="background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%); padding: 1.5rem; border-radius: 10px; text-align: center;">
                            <div style="font-size: 2rem; font-weight: bold; color: #6b21a8;"><?php echo $players_count; ?></div>
                            <div style="color: #6b7280; font-size: 0.875rem; margin-top: 0.25rem;">Total Players</div>
                        </div>
                        <div style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); padding: 1.5rem; border-radius: 10px; text-align: center;">
                            <div style="font-size: 2rem; font-weight: bold; color: #1e40af;"><?php echo $teams_count; ?></div>
                            <div style="color: #6b7280; font-size: 0.875rem; margin-top: 0.25rem;">Registered Teams</div>
                        </div>
                        <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 1.5rem; border-radius: 10px; text-align: center;">
                            <div style="font-size: 2rem; font-weight: bold; color: #92400e;">₹120 Cr</div>
                            <div style="color: #6b7280; font-size: 0.875rem; margin-top: 0.25rem;">Budget Per Team</div>
                        </div>
                        <div style="background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); padding: 1.5rem; border-radius: 10px; text-align: center;">
                            <div style="font-size: 2rem; font-weight: bold; color: #166534;">4 Groups</div>
                            <div style="color: #6b7280; font-size: 0.875rem; margin-top: 0.25rem;">Player Categories</div>
                        </div>
                    </div>

                    <h3 style="color: #1f2937; margin-top: 1.5rem;">✨ Key Features</h3>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                        <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; border-left: 3px solid #3b82f6;">
                            <div style="font-weight: 600; color: #1f2937; margin-bottom: 0.25rem;">💰 Smart Budget Management</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Real-time budget tracking and validation</div>
                        </div>
                        <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; border-left: 3px solid #10b981;">
                            <div style="font-weight: 600; color: #1f2937; margin-bottom: 0.25rem;">⚡ Live Bidding</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Instant updates across all participants</div>
                        </div>
                        <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; border-left: 3px solid #f59e0b;">
                            <div style="font-weight: 600; color: #1f2937; margin-bottom: 0.25rem;">🎯 Strategic Groups</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Players divided into A, B, C, D tiers</div>
                        </div>
                        <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; border-left: 3px solid #8b5cf6;">
                            <div style="font-weight: 600; color: #1f2937; margin-bottom: 0.25rem;">📈 Analytics Dashboard</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Track your team's composition and spending</div>
                        </div>
                    </div>

                    <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 1.5rem; border-radius: 10px; margin-top: 1.5rem; text-align: center;">
                        <h4 style="margin: 0 0 0.5rem 0;">🚀 Ready to Start?</h4>
                        <p style="margin: 0 0 1rem 0; opacity: 0.9;">Create your own auction room or join an existing one with friends!</p>
                        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                            <?php if (isLoggedIn()): ?>
                                <a href="pages/create-auction.php" style="padding: 0.75rem 1.5rem; background: white; color: #3b82f6; text-decoration: none; border-radius: 8px; font-weight: 600;">Create Auction</a>
                                <a href="pages/join-auction.php" style="padding: 0.75rem 1.5rem; background: rgba(255,255,255,0.2); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; border: 2px solid white;">Join Auction</a>
                            <?php else: ?>
                                <a href="auth/login.php" style="padding: 0.75rem 1.5rem; background: white; color: #3b82f6; text-decoration: none; border-radius: 8px; font-weight: 600;">Login to Start</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Player Dashboard -->
        <div id="playerDashboardModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
            <div style="background: white; max-width: 1200px; margin: 50px auto; padding: 2rem; border-radius: 12px; position: relative;">
                <button onclick="closePlayerDashboard()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 2rem; cursor: pointer; color: #666;">&times;</button>
                <h2 style="color: #3b82f6; margin-bottom: 1.5rem;">🏏 Player Pool - Groupwise Distribution</h2>
                
                <?php
                // Get players grouped by auction group
                $conn = getDBConnection();
                $groups = ['Marquee', 'A', 'B', 'C', 'D'];
                ?>
                
                <div style="margin-bottom: 1rem;">
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center;">
                        <?php foreach ($groups as $group): ?>
                            <button onclick="showGroup('<?php echo $group; ?>')" id="btn-<?php echo $group; ?>" 
                                style="padding: 0.75rem 1.5rem; border: 2px solid #3b82f6; background: white; color: #3b82f6; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s;"
                                onmouseover="this.style.background='#3b82f6'; this.style.color='white';" 
                                onmouseout="if(!this.classList.contains('active')) { this.style.background='white'; this.style.color='#3b82f6'; }">
                                Group <?php echo $group; ?>
                            </button>
                        <?php endforeach; ?>
                        <button onclick="showGroup('all')" id="btn-all" 
                            style="padding: 0.75rem 1.5rem; border: 2px solid #10b981; background: #10b981; color: white; border-radius: 8px; cursor: pointer; font-weight: 600;"
                            class="active">
                            All Players
                        </button>
                    </div>
                </div>
                
                <?php foreach ($groups as $group): ?>
                    <?php
                    $group_sql = "SELECT player_name, player_role, player_type, base_price, age, auction_group 
                                  FROM players 
                                  WHERE auction_group = '" . $conn->real_escape_string($group) . "'
                                  ORDER BY base_price DESC";
                    $group_result = $conn->query($group_sql);
                    $player_count = $group_result ? $group_result->num_rows : 0;
                    
                    // Get price range for group
                    $price_ranges = [
                        'Marquee' => '₹20+ Cr',
                        'A' => '₹2+ Cr',
                        'B' => '₹1-2 Cr',
                        'C' => '₹50L-1Cr',
                        'D' => '<₹50L'
                    ];
                    
                    $group_colors = [
                        'Marquee' => ['bg' => '#fef3c7', 'text' => '#92400e', 'icon' => '⭐'],
                        'A' => ['bg' => '#dbeafe', 'text' => '#1e40af', 'icon' => '💎'],
                        'B' => ['bg' => '#e9d5ff', 'text' => '#6b21a8', 'icon' => '💰'],
                        'C' => ['bg' => '#dcfce7', 'text' => '#166534', 'icon' => '🎯'],
                        'D' => ['bg' => '#f3f4f6', 'text' => '#374151', 'icon' => '🏆']
                    ];
                    ?>
                    
                    <div id="group-<?php echo $group; ?>" class="group-section" style="display: block;">
                        <div style="background: <?php echo $group_colors[$group]['bg']; ?>; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem; border-left: 4px solid <?php echo $group_colors[$group]['text']; ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <h3 style="margin: 0; color: <?php echo $group_colors[$group]['text']; ?>;">
                                    <?php echo $group_colors[$group]['icon']; ?> Group <?php echo $group; ?> - <?php echo $price_ranges[$group]; ?>
                                </h3>
                                <span style="padding: 0.5rem 1rem; background: white; border-radius: 6px; font-weight: 600; color: <?php echo $group_colors[$group]['text']; ?>;">
                                    <?php echo $player_count; ?> Players
                                </span>
                            </div>
                            
                            <?php if ($group_result && $player_count > 0): ?>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                                    <?php while ($player = $group_result->fetch_assoc()): ?>
                                        <div style="background: white; padding: 1rem; border-radius: 8px; border: 1px solid rgba(0,0,0,0.1);">
                                            <div style="font-weight: 600; color: #1f2937; margin-bottom: 0.5rem; font-size: 1rem;">
                                                🏏 <?php echo htmlspecialchars($player['player_name']); ?>
                                            </div>
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; font-size: 0.875rem; color: #6b7280;">
                                                <div><strong>Role:</strong> <?php echo htmlspecialchars($player['player_role']); ?></div>
                                                <div><strong>Type:</strong> <?php echo htmlspecialchars($player['player_type']); ?></div>
                                                <div><strong>Age:</strong> <?php echo $player['age']; ?> yrs</div>
                                                <div style="color: <?php echo $group_colors[$group]['text']; ?>; font-weight: 600;">
                                                    <strong>Base:</strong> ₹<?php echo number_format($player['base_price'] / 10000000, 2); ?> Cr
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p style="margin: 1rem 0 0 0; color: #6b7280; text-align: center;">No players in this group</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php closeDBConnection($conn); ?>
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
                    <h3>🎯 Automated Auction</h3>
                    <p>Random player selection from groups A, B, C, D</p>
                </div>
                <div class="team-card">
                    <h3>⚡ Real-time Bidding</h3>
                    <p>Live auction updates and team management</p>
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
                <h3>multiplayer option</h3>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        function showMultiplayerRules() {
            document.getElementById('rulesModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeRulesModal() {
            document.getElementById('rulesModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function showAuctionDetails() {
            document.getElementById('auctionDetailsModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeAuctionDetailsModal() {
            document.getElementById('auctionDetailsModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function showPlayerDashboard() {
            document.getElementById('playerDashboardModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
            showGroup('all'); // Show all groups by default
        }

        function closePlayerDashboard() {
            document.getElementById('playerDashboardModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function showGroup(groupName) {
            // Get all group sections
            const sections = document.querySelectorAll('.group-section');
            const buttons = document.querySelectorAll('[id^="btn-"]');
            
            // Remove active class from all buttons
            buttons.forEach(btn => {
                btn.classList.remove('active');
                btn.style.background = 'white';
                btn.style.color = '#3b82f6';
            });
            
            if (groupName === 'all') {
                // Show all groups
                sections.forEach(section => section.style.display = 'block');
                document.getElementById('btn-all').classList.add('active');
                document.getElementById('btn-all').style.background = '#10b981';
                document.getElementById('btn-all').style.color = 'white';
            } else {
                // Hide all groups first
                sections.forEach(section => section.style.display = 'none');
                // Show only selected group
                document.getElementById('group-' + groupName).style.display = 'block';
                // Highlight selected button
                const selectedBtn = document.getElementById('btn-' + groupName);
                selectedBtn.classList.add('active');
                selectedBtn.style.background = '#3b82f6';
                selectedBtn.style.color = 'white';
            }
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            const rulesModal = document.getElementById('rulesModal');
            const detailsModal = document.getElementById('auctionDetailsModal');
            const playerModal = document.getElementById('playerDashboardModal');
            
            if (event.target === rulesModal) {
                closeRulesModal();
            }
            if (event.target === detailsModal) {
                closeAuctionDetailsModal();
            }
            if (event.target === playerModal) {
                closePlayerDashboard();
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeRulesModal();
                closeAuctionDetailsModal();
                closePlayerDashboard();
            }
        });
    </script>
</body>
</html>
