<?php 
require_once '../config/session.php';
require_once '../includes/auction_room_functions.php';
require_once '../includes/player_functions.php';

requireLogin();

$current_user = getCurrentUser();
$room_id = $_GET['room_id'] ?? 0;

$room = getRoomById($room_id);
if (!$room) {
    header('Location: my-auctions.php');
    exit();
}

$userCheck = isUserInRoom($room_id, $current_user['user_id']);
if (!$userCheck['in_room']) {
    header('Location: join-auction.php?code=' . $room['room_code']);
    exit();
}

$participants = getRoomParticipants($room_id);
$is_host = $room['created_by'] == $current_user['user_id'];
$participant_id = $userCheck['participant_id'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'start' && $is_host) {
        startAuctionRoom($room_id, $current_user['user_id']);
        header('Location: auction-room.php?room_id=' . $room_id);
        exit();
    } elseif ($_POST['action'] == 'next_player' && $is_host) {
        $group = $_POST['group'] ?? null;
        getNextPlayerForRoom($room_id, $group);
        header('Location: auction-room.php?room_id=' . $room_id);
        exit();
    } elseif ($_POST['action'] == 'bid') {
        $increment = floatval($_POST['increment'] ?? 1000000);
        $new_bid = $room['current_bid'] + $increment;
        placeBidInRoom($room_id, $participant_id, $new_bid);
        // Reset timer on new bid
        resetBidTimer($room_id);
        header('Location: auction-room.php?room_id=' . $room_id);
        exit();
    } elseif ($_POST['action'] == 'wait') {
        // Extend timer by 10 seconds
        extendBidTimer($room_id, 10);
        header('Location: auction-room.php?room_id=' . $room_id);
        exit();
    } elseif ($_POST['action'] == 'finalize' && $is_host) {
        finalizePlayerInRoom($room_id);
        header('Location: auction-room.php?room_id=' . $room_id);
        exit();
    }
}

// Reload room data
$room = getRoomById($room_id);
$current_player = null;
if ($room['current_player_id']) {
    $current_player = getPlayerById($room['current_player_id']);
}

// Get time remaining for bid timer
$time_remaining = 15;
if ($current_player && $room['status'] == 'active') {
    $time_remaining = getBidTimeRemaining($room_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auction Room - IPL Auction</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2.0">
    <style>
        body { background: #0f172a; }
        .auction-room { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        
        /* Room Header */
        .room-header {
            background: linear-gradient(135deg, rgba(96, 165, 250, 0.2), rgba(167, 139, 250, 0.2));
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 2px solid rgba(96, 165, 250, 0.3);
        }
        .room-title h1 { margin: 0; color: white; font-size: 2rem; }
        .room-code-display {
            background: rgba(0, 0, 0, 0.3);
            padding: 1rem 2rem;
            border-radius: 10px;
        }
        .room-code-display .label { color: #94a3b8; font-size: 0.875rem; }
        .room-code-display .code {
            color: #60a5fa;
            font-size: 1.5rem;
            font-weight: bold;
            letter-spacing: 0.3em;
        }
        
        /* Main Layout */
        .auction-layout { display: grid; grid-template-columns: 300px 1fr 300px; gap: 2rem; }
        
        /* Participants Panel */
        .participants-panel {
            background: rgba(15, 23, 42, 0.95);
            padding: 1.5rem;
            border-radius: 15px;
            height: fit-content;
        }
        .participants-panel h3 { color: #60a5fa; margin-top: 0; }
        .participant-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            border-left: 3px solid #60a5fa;
        }
        .participant-item.host { border-left-color: #fbbf24; }
        .participant-name { color: white; font-weight: 600; }
        .participant-budget { color: #94a3b8; font-size: 0.875rem; margin-top: 0.5rem; }
        
        /* Auction Area */
        .auction-area {
            background: rgba(15, 23, 42, 0.95);
            padding: 2rem;
            border-radius: 15px;
        }
        
        /* Waiting State */
        .waiting-state { text-align: center; padding: 3rem; color: white; }
        .waiting-state h2 { color: #fbbf24; margin-bottom: 1rem; }
        .waiting-state .participant-count {
            font-size: 3rem;
            font-weight: bold;
            color: #60a5fa;
            margin: 2rem 0;
        }
        .btn-start {
            padding: 1rem 3rem;
            background: linear-gradient(135deg, #34d399, #10b981);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
        }
        
        /* Player Display */
        .current-player {
            background: linear-gradient(135deg, rgba(96, 165, 250, 0.1), rgba(167, 139, 250, 0.1));
            padding: 2rem;
            border-radius: 15px;
            border: 2px solid rgba(96, 165, 250, 0.3);
            margin-bottom: 2rem;
        }
        .player-header { display: flex; justify-content: space-between; align-items: start; }
        .player-name { font-size: 2rem; font-weight: bold; color: white; }
        .player-group {
            padding: 0.5rem 1rem;
            background: rgba(96, 165, 250, 0.2);
            border-radius: 8px;
            color: #60a5fa;
            font-weight: 600;
        }
        .player-details {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .player-detail { color: #cbd5e1; }
        .player-detail .label { color: #94a3b8; font-size: 0.875rem; }
        .player-detail .value { font-size: 1.25rem; font-weight: 600; margin-top: 0.5rem; }
        
        /* Bidding Controls */
        .bidding-controls { margin-top: 2rem; }
        .current-bid {
            text-align: center;
            margin-bottom: 2rem;
        }
        .current-bid .label { color: #94a3b8; }
        .current-bid .amount {
            font-size: 3rem;
            font-weight: bold;
            color: #34d399;
            margin: 0.5rem 0;
        }
        .current-bid .bidder { color: #60a5fa; font-size: 1.1rem; }
        
        .bid-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .btn-bid {
            padding: 1.5rem;
            background: rgba(96, 165, 250, 0.2);
            color: white;
            border: 2px solid #60a5fa;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-bid:hover { background: rgba(96, 165, 250, 0.3); transform: scale(1.05); }
        
        .action-buttons { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .btn-sold {
            padding: 1rem;
            background: linear-gradient(135deg, #34d399, #10b981);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-pass {
            padding: 1rem;
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 2px solid #ef4444;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }
        
        /* Group Selection */
        .group-selection {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .btn-group {
            padding: 1.5rem 1rem;
            background: rgba(96, 165, 250, 0.1);
            color: white;
            border: 2px solid rgba(96, 165, 250, 0.3);
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
        }
        .btn-group:hover { border-color: #60a5fa; background: rgba(96, 165, 250, 0.2); }
        
        /* Activity Feed */
        .activity-panel {
            background: rgba(15, 23, 42, 0.95);
            padding: 1.5rem;
            border-radius: 15px;
            height: 600px;
            overflow-y: auto;
        }
        .activity-panel h3 { color: #60a5fa; margin-top: 0; }
        .activity-item {
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
            color: #cbd5e1;
        }
        .activity-item .time { color: #64748b; font-size: 0.75rem; }
        
        /* Timer */
        .bid-timer {
            text-align: center;
            margin: 1.5rem 0;
            padding: 1rem;
            background: rgba(96, 165, 250, 0.1);
            border-radius: 10px;
            border: 2px solid rgba(96, 165, 250, 0.3);
        }
        .timer-label { color: #94a3b8; font-size: 0.875rem; margin-bottom: 0.5rem; }
        .timer-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #60a5fa;
            font-family: monospace;
        }
        .timer-value.warning { color: #fbbf24; }
        .timer-value.danger { color: #ef4444; }
        .btn-wait {
            margin-top: 1rem;
            padding: 0.75rem 1.5rem;
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
            border: 2px solid #fbbf24;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-wait:hover { background: rgba(251, 191, 36, 0.3); transform: scale(1.05); }
    </style>
</head>
<body>
    
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">🏏 IPL Auction</a>
            <ul class="nav-menu">
                <li><a href="my-auctions.php">← Back to My Auctions</a></li>
            </ul>
        </div>
    </nav>

    <div class="auction-room">
        <div class="room-header">
            <div class="room-title">
                <h1><?php echo htmlspecialchars($room['room_name']); ?></h1>
                <p style="color: #94a3b8; margin: 0.5rem 0 0 0;">
                    Host: <?php echo htmlspecialchars($room['host_name'] ?: $room['host_username']); ?>
                    <?php if ($is_host): ?><span style="color: #fbbf24;"> (You)</span><?php endif; ?>
                </p>
            </div>
            <div class="room-code-display">
                <div class="label">Room Code</div>
                <div class="code"><?php echo htmlspecialchars($room['room_code']); ?></div>
            </div>
        </div>

        <div class="auction-layout">
            <!-- Participants Panel -->
            <div class="participants-panel">
                <h3>Participants (<?php echo count($participants); ?>/<?php echo $room['max_participants']; ?>)</h3>
                <?php foreach ($participants as $p): ?>
                    <div class="participant-item <?php echo $p['is_host'] ? 'host' : ''; ?>">
                        <div class="participant-name">
                            <?php echo htmlspecialchars($p['team_name']); ?>
                            <?php if ($p['is_host']): ?><span style="color: #fbbf24;"> 👑</span><?php endif; ?>
                            <?php if ($p['user_id'] == $current_user['user_id']): ?><span style="color: #34d399;"> (You)</span><?php endif; ?>
                        </div>
                        <div class="participant-budget">
                            💰 ₹<?php echo number_format($p['remaining_budget'] / 10000000, 2); ?> Cr
                            <br>
                            🏏 <?php echo $p['players_bought']; ?> players
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Main Auction Area -->
            <div class="auction-area">
                <?php if ($room['status'] == 'waiting'): ?>
                    <div class="waiting-state">
                        <h2>⏳ Waiting for Auction to Start</h2>
                        <div class="participant-count">
                            <?php echo count($participants); ?> / <?php echo $room['max_participants']; ?>
                        </div>
                        <p style="color: #94a3b8;">Players have joined</p>
                        
                        <?php if ($is_host): ?>
                            <form method="POST" style="margin-top: 2rem;">
                                <input type="hidden" name="action" value="start">
                                <button type="submit" class="btn-start">Start Auction</button>
                            </form>
                        <?php else: ?>
                            <p style="margin-top: 2rem; color: #fbbf24;">Waiting for host to start...</p>
                        <?php endif; ?>
                    </div>
                    
                <?php elseif (!$current_player): ?>
                    <div class="waiting-state">
                        <h2>Select Player Group</h2>
                        <?php if ($is_host): ?>
                            <div class="group-selection">
                                <form method="POST">
                                    <input type="hidden" name="action" value="next_player">
                                    <input type="hidden" name="group" value="Marquee">
                                    <button type="submit" class="btn-group">
                                        <div style="font-size: 1.5rem;">⭐</div>
                                        <div style="font-weight: 600;">Marquee</div>
                                        <div style="font-size: 0.875rem; color: #94a3b8;">Special</div>
                                    </button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="action" value="next_player">
                                    <input type="hidden" name="group" value="A">
                                    <button type="submit" class="btn-group">
                                        <div style="font-size: 1.5rem;">💎</div>
                                        <div style="font-weight: 600;">Group A</div>
                                        <div style="font-size: 0.875rem; color: #94a3b8;">Premium</div>
                                    </button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="action" value="next_player">
                                    <input type="hidden" name="group" value="B">
                                    <button type="submit" class="btn-group">
                                        <div style="font-size: 1.5rem;">💰</div>
                                        <div style="font-weight: 600;">Group B</div>
                                        <div style="font-size: 0.875rem; color: #94a3b8;">Mid-tier</div>
                                    </button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="action" value="next_player">
                                    <input type="hidden" name="group" value="C">
                                    <button type="submit" class="btn-group">
                                        <div style="font-size: 1.5rem;">🎯</div>
                                        <div style="font-weight: 600;">Group C</div>
                                        <div style="font-size: 0.875rem; color: #94a3b8;">Budget</div>
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <p style="color: #94a3b8;">Waiting for host to select next player...</p>
                        <?php endif; ?>
                    </div>
                    
                <?php else: ?>
                    <!-- Current Player Display -->
                    <div class="current-player">
                        <div class="player-header">
                            <div class="player-name"><?php echo htmlspecialchars($current_player['player_name']); ?></div>
                            <div class="player-group">Group <?php echo $current_player['auction_group']; ?></div>
                        </div>
                        <div class="player-details">
                            <div class="player-detail">
                                <div class="label">Type</div>
                                <div class="value"><?php echo $current_player['player_type']; ?></div>
                            </div>
                            <div class="player-detail">
                                <div class="label">Role</div>
                                <div class="value"><?php echo $current_player['player_role']; ?></div>
                            </div>
                            <div class="player-detail">
                                <div class="label">Base Price</div>
                                <div class="value">₹<?php echo number_format($current_player['base_price'] / 10000000, 2); ?> Cr</div>
                            </div>
                            <div class="player-detail">
                                <div class="label">Age</div>
                                <div class="value"><?php echo $current_player['age']; ?> years</div>
                            </div>
                        </div>
                    </div>

                    <!-- Bidding Controls -->
                    <div class="bidding-controls">
                        <div class="current-bid">
                            <div class="label">Current Bid</div>
                            <div class="amount">₹<?php echo number_format($room['current_bid'] / 10000000, 2); ?> Cr</div>
                            <?php if ($room['current_bidder_id']): ?>
                                <?php 
                                $bidder = array_filter($participants, fn($p) => $p['participant_id'] == $room['current_bidder_id']);
                                $bidder = reset($bidder);
                                ?>
                                <div class="bidder">Current Bidder: <?php echo htmlspecialchars($bidder['team_name']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Bid Timer -->
                        <div class="bid-timer">
                            <div class="timer-label">Time Remaining</div>
                            <div class="timer-value" id="timer"><?php echo $time_remaining; ?></div>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="wait">
                                <button type="submit" class="btn-wait" id="waitBtn">⏸️ Wait</button>
                            </form>
                        </div>

                        <div class="bid-buttons">
                            <?php 
                            // Dynamic increment based on current bid
                            $current_bid_cr = $room['current_bid'] / 10000000;
                            if ($current_bid_cr >= 3) {
                                // Above 3 Cr: increment by 20L
                                $increment1 = 2000000;  // 20L
                                $increment2 = 10000000; // 1 Cr
                                $increment3 = 20000000; // 2 Cr
                                $label1 = '+20 L';
                                $label2 = '+1 Cr';
                                $label3 = '+2 Cr';
                            } else {
                                // Below 3 Cr: increment by 10L
                                $increment1 = 1000000;  // 10L
                                $increment2 = 5000000;  // 50L
                                $increment3 = 10000000; // 1 Cr
                                $label1 = '+10 L';
                                $label2 = '+50 L';
                                $label3 = '+1 Cr';
                            }
                            ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="bid">
                                <input type="hidden" name="increment" value="<?php echo $increment1; ?>">
                                <button type="submit" class="btn-bid"><?php echo $label1; ?></button>
                            </form>
                            <form method="POST">
                                <input type="hidden" name="action" value="bid">
                                <input type="hidden" name="increment" value="<?php echo $increment2; ?>">
                                <button type="submit" class="btn-bid"><?php echo $label2; ?></button>
                            </form>
                            <form method="POST">
                                <input type="hidden" name="action" value="bid">
                                <input type="hidden" name="increment" value="<?php echo $increment3; ?>">
                                <button type="submit" class="btn-bid"><?php echo $label3; ?></button>
                            </form>
                        </div>

                        <?php if ($is_host): ?>
                            <div class="action-buttons">
                                <form method="POST">
                                    <input type="hidden" name="action" value="finalize">
                                    <button type="submit" class="btn-sold">SOLD!</button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="action" value="finalize">
                                    <button type="submit" class="btn-pass">PASS</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Activity Feed -->
            <div class="activity-panel">
                <h3>Activity Feed</h3>
                <div class="activity-item">
                    <div>Room created</div>
                    <div class="time"><?php echo date('H:i', strtotime($room['created_at'])); ?></div>
                </div>
                <?php if ($room['started_at']): ?>
                    <div class="activity-item">
                        <div>Auction started!</div>
                        <div class="time"><?php echo date('H:i', strtotime($room['started_at'])); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Bid Timer
        let timeRemaining = <?php echo $time_remaining; ?>;
        let timerInterval;
        
        function startTimer() {
            const timerElement = document.getElementById('timer');
            if (!timerElement) return;
            
            timerInterval = setInterval(function() {
                timeRemaining--;
                timerElement.textContent = timeRemaining;
                
                // Change color based on time
                if (timeRemaining <= 5) {
                    timerElement.className = 'timer-value danger';
                } else if (timeRemaining <= 10) {
                    timerElement.className = 'timer-value warning';
                } else {
                    timerElement.className = 'timer-value';
                }
                
                if (timeRemaining <= 0) {
                    clearInterval(timerInterval);
                    // Auto-refresh when timer expires
                    window.location.reload();
                }
            }, 1000);
        }
        
        // Check if we're in active bidding
        <?php if ($current_player && $room['status'] == 'active'): ?>
            startTimer();
        <?php endif; ?>
        
        // Auto-refresh every 30 seconds (increased from 3 to not interfere with timer)
        setTimeout(function() {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
