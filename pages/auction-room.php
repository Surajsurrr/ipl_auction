<?php 
require_once '../config/session.php';
require_once '../includes/auction_room_functions.php';
require_once '../includes/player_functions.php';

requireLogin();

$current_user = getCurrentUser();
$room_id = $_GET['room_id'] ?? 0;

// Handle AJAX request for participant players
if (isset($_GET['action']) && $_GET['action'] == 'get_participant_players') {
    header('Content-Type: application/json');
    $participant_id = $_GET['participant_id'] ?? 0;
    
    error_log("Fetching players for participant_id: " . $participant_id);
    
    $players = getParticipantPlayers($participant_id);
    
    error_log("Found " . count($players) . " players");
    
    echo json_encode([
        'success' => true,
        'players' => $players,
        'count' => count($players),
        'participant_id' => $participant_id
    ]);
    exit();
}

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
        // Automatically select first player
        getNextPlayerForRoom($room_id, null);
        header('Location: auction-room.php?room_id=' . $room_id);
        exit();
    } elseif ($_POST['action'] == 'next_player') {
        // Allow server-side next player selection (triggered by client POST)
        $group = $_POST['group'] ?? null;
        getNextPlayerForRoom($room_id, $group);
        header('Location: auction-room.php?room_id=' . $room_id);
        exit();
    } elseif ($_POST['action'] == 'bid') {
        // Auto-increment based on current bid
        $current_bid_cr = $room['current_bid'] / 10000000;
        if ($current_bid_cr >= 3) {
            $increment = 2000000; // 20L for bids above 3 Cr
        } else {
            $increment = 1000000; // 10L for bids below 3 Cr
        }
        $new_bid = $room['current_bid'] + $increment;
        $bid_result = placeBidInRoom($room_id, $participant_id, $new_bid);
        if (!$bid_result['success']) {
            $_SESSION['bid_error'] = $bid_result['message'];
        }
        // Reset timer on new bid
        resetBidTimer($room_id);
        header('Location: auction-room.php?room_id=' . $room_id);
        exit();
    } elseif ($_POST['action'] == 'quit_bid') {
        // Release participant from bidding war
        releaseFromBiddingWar($room_id, $participant_id);
        header('Location: auction-room.php?room_id=' . $room_id);
        exit();
    } elseif ($_POST['action'] == 'wait') {
        // Extend timer by 10 seconds
        extendBidTimer($room_id, 10);
        header('Location: auction-room.php?room_id=' . $room_id);
        exit();
    } elseif ($_POST['action'] == 'finalize') {
        // Any participant can trigger finalization when timer expires
        // Get current player info before finalizing
        $sale_player = getPlayerById($room['current_player_id']);
        $sale_bidder = null;
        if ($room['current_bidder_id']) {
            $sale_bidder = getParticipantById($room['current_bidder_id']);
        }
        
        finalizePlayerInRoom($room_id);
        
        // Store sale notification in session
        $_SESSION['sale_notification'] = [
            'player_name' => $sale_player['player_name'],
            'player_type' => $sale_player['player_type'],
            'base_price' => $sale_player['base_price'],
            'sold_price' => $room['current_bid'],
            'team_name' => $sale_bidder ? $sale_bidder['team_name'] : null,
            'is_sold' => $room['current_bidder_id'] ? true : false
        ];
        
        header('Location: auction-room.php?room_id=' . $room_id);
        exit();
    }
}

// Reload room data
$room = getRoomById($room_id);

// Check for sale notification
$sale_notification = null;
if (isset($_SESSION['sale_notification'])) {
    $sale_notification = $_SESSION['sale_notification'];
    unset($_SESSION['sale_notification']);
}

$current_player = null;
if ($room['current_player_id']) {
    $current_player = getPlayerById($room['current_player_id']);
}

// Get time remaining for bid timer
$time_remaining = 45;
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

        /* Sale flashcard */
        .sale-flash {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem 0;
        }
        .sale-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.03), rgba(255,255,255,0.02));
            border: 1px solid rgba(255,255,255,0.06);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.6);
            max-width: 420px;
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
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .btn-bid-player {
            padding: 1.5rem;
            background: linear-gradient(135deg, #34d399, #10b981);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(52, 211, 153, 0.4);
        }
        .btn-bid-player:hover { 
            background: linear-gradient(135deg, #10b981, #059669);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 211, 153, 0.6);
        }
        
        .btn-not-interested {
            padding: 1.5rem;
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 2px solid #ef4444;
            border-radius: 12px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-not-interested:hover { 
            background: rgba(239, 68, 68, 0.3);
            transform: translateY(-2px);
        }
        
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
        
        /* Clickable participant */
        .participant-item {
            cursor: pointer;
            transition: all 0.2s;
        }
        .participant-item:hover {
            transform: translateX(5px);
            background: rgba(96, 165, 250, 0.15) !important;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }
        .modal-content {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            margin: 2% auto;
            padding: 0;
            border: 2px solid #60a5fa;
            border-radius: 15px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }
        .modal-header {
            background: linear-gradient(135deg, rgba(96, 165, 250, 0.2), rgba(167, 139, 250, 0.2));
            padding: 1.5rem 2rem;
            border-bottom: 2px solid rgba(96, 165, 250, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .modal-header h2 {
            margin: 0;
            color: white;
            font-size: 1.5rem;
        }
        .close {
            color: #94a3b8;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s;
            line-height: 1;
        }
        .close:hover { color: #ef4444; }
        .modal-body {
            padding: 2rem;
        }
        .budget-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .budget-card {
            background: rgba(96, 165, 250, 0.1);
            padding: 1.25rem;
            border-radius: 10px;
            border: 1px solid rgba(96, 165, 250, 0.3);
            text-align: center;
        }
        .budget-label {
            color: #94a3b8;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .budget-value {
            color: #60a5fa;
            font-size: 1.75rem;
            font-weight: 800;
        }
        .players-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }
        .player-card-modal {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.25rem;
            border-radius: 10px;
            border: 1px solid rgba(96, 165, 250, 0.2);
            transition: all 0.2s;
        }
        .player-card-modal:hover {
            border-color: #60a5fa;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(96, 165, 250, 0.3);
        }
        .player-card-modal h4 {
            margin: 0 0 0.75rem 0;
            color: white;
            font-size: 1.1rem;
        }
        .player-detail {
            display: flex;
            justify-content: space-between;
            margin: 0.4rem 0;
            font-size: 0.875rem;
        }
        .player-detail-label {
            color: #94a3b8;
        }
        .player-detail-value {
            color: #60a5fa;
            font-weight: 600;
        }
        
        /* Sale Notification Modal */
        .sale-notification {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            animation: fadeIn 0.3s;
        }
        .sale-notification-content {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 3rem;
            border-radius: 20px;
            text-align: center;
            max-width: 600px;
            width: 90%;
            animation: scaleIn 0.5s;
        }
        .sale-notification-content.sold {
            border: 4px solid #34d399;
            box-shadow: 0 0 50px rgba(52, 211, 153, 0.5);
        }
        .sale-notification-content.unsold {
            border: 4px solid #ef4444;
            box-shadow: 0 0 50px rgba(239, 68, 68, 0.5);
        }
        .sale-status {
            font-size: 4rem;
            font-weight: 900;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .sale-status.sold {
            color: #34d399;
            text-shadow: 0 0 20px rgba(52, 211, 153, 0.8);
        }
        .sale-status.unsold {
            color: #ef4444;
            text-shadow: 0 0 20px rgba(239, 68, 68, 0.8);
        }
        .sale-player-name {
            font-size: 2.5rem;
            color: white;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        .sale-details {
            font-size: 1.25rem;
            color: #94a3b8;
            margin: 0.5rem 0;
        }
        .sale-price {
            font-size: 2rem;
            color: #fbbf24;
            font-weight: 800;
            margin: 1rem 0;
        }
        .sale-team {
            font-size: 1.75rem;
            color: #60a5fa;
            font-weight: 700;
            margin-top: 1.5rem;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        @keyframes scaleIn {
            from { transform: scale(0.5); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
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
                    <div class="participant-item <?php echo $p['is_host'] ? 'host' : ''; ?>" onclick="showParticipantDetails(<?php echo $p['participant_id']; ?>)" title="Click to view squad details">
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
                        <h2>🔄 Loading Next Player...</h2>
                        <p style="color: #94a3b8; margin-top: 1rem;">The system is automatically selecting the next player for auction</p>
                        <form id="autoNextForm" method="POST" style="display:none;">
                            <input type="hidden" name="action" value="next_player">
                        </form>
                        <script>
                            document.getElementById('autoNextForm').submit();
                        </script>
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
                        </div>
                        
                        <!-- Bid Timer -->
                        <div class="bid-timer">
                            <div class="timer-label">Time Remaining</div>
                            <div class="timer-value" id="timer"><?php echo $time_remaining; ?></div>
                        </div>

                        <?php
                        // Check if user is in bidding war or if they can join
                        $player1_id = $room['bidding_war_player1_id'];
                        $player2_id = $room['bidding_war_player2_id'];
                        $is_in_war = ($participant_id == $player1_id || $participant_id == $player2_id);
                        $war_locked = ($player1_id && $player2_id);
                        $can_bid = !$war_locked || $is_in_war;
                        
                        // Display bid error if any
                        if (isset($_SESSION['bid_error'])):
                        ?>
                            <div style="background: rgba(239, 68, 68, 0.1); padding: 0.75rem; border-radius: 8px; border: 1px solid #ef4444; margin-bottom: 1rem;">
                                <div style="color: #fca5a5; font-size: 0.875rem; text-align: center;">⚠️ <?php echo htmlspecialchars($_SESSION['bid_error']); ?></div>
                            </div>
                        <?php 
                            unset($_SESSION['bid_error']);
                        endif; 
                        ?>

                        <div class="bid-buttons">
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="action" value="bid">
                                <button type="submit" class="btn-bid-player" <?php echo !$can_bid ? 'disabled' : ''; ?> style="<?php echo !$can_bid ? 'opacity: 0.5; cursor: not-allowed;' : ''; ?>">
                                    🏏 Bid for <?php echo htmlspecialchars($current_player['player_name']); ?>
                                </button>
                            </form>
                            <?php if ($is_in_war): ?>
                                <form method="POST" style="flex: 1;">
                                    <input type="hidden" name="action" value="quit_bid">
                                    <button type="submit" class="btn-not-interested">
                                        ❌ Quit Bidding
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($war_locked && !$is_in_war): ?>
                            <div style="text-align: center; margin-top: 1rem; padding: 1rem; background: rgba(239, 68, 68, 0.1); border-radius: 10px; color: #fca5a5;">
                                <small>🔒 Bidding is locked between two teams. Wait for one to quit.</small>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; margin-top: 1rem; padding: 1rem; background: rgba(251, 191, 36, 0.1); border-radius: 10px; color: #fbbf24;">
                                <small>⏱️ Player will be automatically sold/unsold when timer reaches 0</small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Current Bidder Panel -->
            <div class="activity-panel">
                <h3>Bidding War</h3>
                <?php if ($current_player): ?>
                    <?php 
                    $player1_id = $room['bidding_war_player1_id'];
                    $player2_id = $room['bidding_war_player2_id'];
                    $player1_bid = $room['bidding_war_player1_bid'];
                    $player2_bid = $room['bidding_war_player2_bid'];
                    
                    $player1 = $player1_id ? array_filter($participants, fn($p) => $p['participant_id'] == $player1_id) : null;
                    $player1 = $player1 ? reset($player1) : null;
                    
                    $player2 = $player2_id ? array_filter($participants, fn($p) => $p['participant_id'] == $player2_id) : null;
                    $player2 = $player2 ? reset($player2) : null;
                    ?>
                    
                    <?php if ($player1 && $player2): ?>
                        <!-- Locked bidding war between two players -->
                        <div style="background: rgba(239, 68, 68, 0.1); padding: 0.75rem; border-radius: 8px; border: 2px solid #ef4444; margin-bottom: 1rem; text-align: center;">
                            <div style="color: #fca5a5; font-size: 0.875rem; font-weight: 700;">🔒 LOCKED BATTLE</div>
                        </div>
                        
                        <!-- Player 1 -->
                        <div style="background: <?php echo $room['current_bidder_id'] == $player1_id ? 'rgba(96, 165, 250, 0.15)' : 'rgba(255, 255, 255, 0.05)'; ?>; padding: 1.25rem; border-radius: 10px; border: 2px solid <?php echo $room['current_bidder_id'] == $player1_id ? '#60a5fa' : 'transparent'; ?>; margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <div>
                                    <?php if ($room['current_bidder_id'] == $player1_id): ?>
                                        <span style="font-size: 1.25rem;">👑</span>
                                    <?php endif; ?>
                                    <span style="color: white; font-size: 1.1rem; font-weight: 700;"><?php echo htmlspecialchars($player1['team_name']); ?></span>
                                </div>
                            </div>
                            <div style="color: #94a3b8; font-size: 0.75rem; margin-bottom: 0.25rem;">Current Bid</div>
                            <div style="color: #34d399; font-size: 1.5rem; font-weight: 800;">₹<?php echo number_format($player1_bid / 10000000, 2); ?> Cr</div>
                            <div style="color: #94a3b8; font-size: 0.75rem; margin-top: 0.5rem;">Budget: ₹<?php echo number_format($player1['remaining_budget'] / 10000000, 2); ?> Cr</div>
                        </div>
                        
                        <div style="text-align: center; margin: 1rem 0; color: #64748b; font-weight: 700;">⚔️ VS ⚔️</div>
                        
                        <!-- Player 2 -->
                        <div style="background: <?php echo $room['current_bidder_id'] == $player2_id ? 'rgba(96, 165, 250, 0.15)' : 'rgba(255, 255, 255, 0.05)'; ?>; padding: 1.25rem; border-radius: 10px; border: 2px solid <?php echo $room['current_bidder_id'] == $player2_id ? '#60a5fa' : 'transparent'; ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <div>
                                    <?php if ($room['current_bidder_id'] == $player2_id): ?>
                                        <span style="font-size: 1.25rem;">👑</span>
                                    <?php endif; ?>
                                    <span style="color: white; font-size: 1.1rem; font-weight: 700;"><?php echo htmlspecialchars($player2['team_name']); ?></span>
                                </div>
                            </div>
                            <div style="color: #94a3b8; font-size: 0.75rem; margin-bottom: 0.25rem;">Current Bid</div>
                            <div style="color: #34d399; font-size: 1.5rem; font-weight: 800;">₹<?php echo number_format($player2_bid / 10000000, 2); ?> Cr</div>
                            <div style="color: #94a3b8; font-size: 0.75rem; margin-top: 0.5rem;">Budget: ₹<?php echo number_format($player2['remaining_budget'] / 10000000, 2); ?> Cr</div>
                        </div>
                        
                    <?php elseif ($player1): ?>
                        <!-- Single bidder -->
                        <div style="background: rgba(96, 165, 250, 0.1); padding: 1.5rem; border-radius: 12px; border: 2px solid #60a5fa;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                                <span style="font-size: 2rem;">👑</span>
                                <div>
                                    <div style="color: #60a5fa; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em;">Leading Bid</div>
                                    <div style="color: white; font-size: 1.5rem; font-weight: 700; margin-top: 0.25rem;"><?php echo htmlspecialchars($player1['team_name']); ?></div>
                                </div>
                            </div>
                            <div style="border-top: 1px solid rgba(96, 165, 250, 0.2); padding-top: 1rem; margin-top: 1rem;">
                                <div style="color: #94a3b8; font-size: 0.875rem; margin-bottom: 0.5rem;">Bid Amount</div>
                                <div style="color: #34d399; font-size: 1.75rem; font-weight: 800;">₹<?php echo number_format($player1_bid / 10000000, 2); ?> Cr</div>
                            </div>
                            <div style="border-top: 1px solid rgba(96, 165, 250, 0.2); padding-top: 1rem; margin-top: 1rem;">
                                <div style="color: #94a3b8; font-size: 0.875rem; margin-bottom: 0.5rem;">Remaining Budget</div>
                                <div style="color: #fbbf24; font-size: 1.25rem; font-weight: 700;">₹<?php echo number_format($player1['remaining_budget'] / 10000000, 2); ?> Cr</div>
                            </div>
                        </div>
                        <div style="background: rgba(251, 191, 36, 0.1); padding: 1rem; border-radius: 8px; margin-top: 1rem; text-align: center;">
                            <div style="color: #fbbf24; font-size: 0.875rem;">⏳ Waiting for second bidder...</div>
                        </div>
                    <?php else: ?>
                        <div style="background: rgba(255, 255, 255, 0.05); padding: 2rem; border-radius: 12px; text-align: center;">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
                            <div style="color: #94a3b8; font-size: 1.1rem;">No bids yet</div>
                            <div style="color: #64748b; font-size: 0.875rem; margin-top: 0.5rem;">Waiting for first bid...</div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="background: rgba(255, 255, 255, 0.05); padding: 2rem; border-radius: 12px; text-align: center;">
                        <div style="color: #64748b; font-size: 0.875rem;">No active player</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Sale notification data
        const saleNotification = <?php echo $sale_notification ? json_encode($sale_notification) : 'null'; ?>;
        
        // Show sale notification if exists
        if (saleNotification) {
            showSaleNotification(saleNotification);
        }
        
        function showSaleNotification(data) {
            const isSold = data.is_sold;
            const status = isSold ? 'SOLD' : 'UNSOLD';
            const statusClass = isSold ? 'sold' : 'unsold';
            
            let detailsHTML = '';
            if (isSold) {
                detailsHTML = `
                    <div class="sale-team">🎯 ${data.team_name}</div>
                    <div class="sale-price">₹${(data.sold_price / 10000000).toFixed(2)} Cr</div>
                    <div class="sale-details">Base Price: ₹${(data.base_price / 10000000).toFixed(2)} Cr</div>
                `;
            } else {
                detailsHTML = `
                    <div class="sale-details">Base Price: ₹${(data.base_price / 10000000).toFixed(2)} Cr</div>
                    <div class="sale-details" style="margin-top: 1rem; color: #64748b;">No bids received</div>
                `;
            }
            
            const notificationHTML = `
                <div class="sale-notification" id="saleNotification">
                    <div class="sale-notification-content ${statusClass}">
                        <div class="sale-status ${statusClass}">${status}!</div>
                        <div class="sale-player-name">🏏 ${data.player_name}</div>
                        <div class="sale-details">${data.player_type}</div>
                        ${detailsHTML}
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', notificationHTML);
            
            // Play sound using multiple methods for better compatibility
            playSaleSound(status, isSold);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const notification = document.getElementById('saleNotification');
                if (notification) {
                    notification.style.animation = 'fadeOut 0.3s';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 4000);
            
            // Click to dismiss
            document.getElementById('saleNotification').onclick = function() {
                this.style.animation = 'fadeOut 0.3s';
                setTimeout(() => this.remove(), 300);
            };
        }
        
        // Play sale sound with multiple methods
        function playSaleSound(text, isSold) {
            console.log('Playing sound:', text);
            
            // Method 1: Web Speech API (text-to-speech)
            if ('speechSynthesis' in window) {
                // Cancel any ongoing speech
                window.speechSynthesis.cancel();
                
                // Wait a bit for cancellation
                setTimeout(() => {
                    const utterance = new SpeechSynthesisUtterance(text);
                    utterance.rate = 0.8;
                    utterance.pitch = 1.3;
                    utterance.volume = 1.0;
                    utterance.lang = 'en-US';
                    
                    utterance.onerror = function(event) {
                        console.error('Speech synthesis error:', event);
                        playBeepSound(isSold);
                    };
                    
                    utterance.onstart = function() {
                        console.log('Speech started');
                    };
                    
                    window.speechSynthesis.speak(utterance);
                }, 100);
            } else {
                console.log('Speech synthesis not supported, playing beep');
                playBeepSound(isSold);
            }
            
            // Also play beep as backup
            setTimeout(() => playBeepSound(isSold), 200);
        }
        
        // Fallback beep sound using Web Audio API
        function playBeepSound(isSold) {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                // Different frequency for sold vs unsold
                oscillator.frequency.value = isSold ? 800 : 400;
                oscillator.type = 'sine';
                
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.5);
                
                console.log('Beep sound played');
            } catch (e) {
                console.error('Error playing beep:', e);
            }
        }
        
        // Participant details modal - MUST BE IN GLOBAL SCOPE
        const participantsData = <?php echo json_encode($participants); ?>;
        const totalBudget = <?php echo $room['total_budget_per_team']; ?>;
        
        console.log('Participants data loaded:', participantsData);
        console.log('Total budget:', totalBudget);
        
        function showParticipantDetails(participantId) {
            console.log('showParticipantDetails called with ID:', participantId);
            const participant = participantsData.find(p => p.participant_id == participantId);
            console.log('Found participant:', participant);
            
            if (!participant) {
                console.error('Participant not found!');
                return;
            }
            
            // Fetch player details via AJAX
            const url = '?action=get_participant_players&participant_id=' + participantId + '&room_id=<?php echo $room_id; ?>';
            console.log('Fetching from URL:', url);
            
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Received data:', data);
                    displayParticipantModal(participant, data.players);
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    alert('Error loading player data: ' + error.message);
                });
        }
        
        function displayParticipantModal(participant, players) {
            const spentAmount = totalBudget - participant.remaining_budget;
            
            let playersHTML = '';
            if (players && players.length > 0) {
                playersHTML = '<div class="players-list">';
                players.forEach(player => {
                    playersHTML += `
                        <div class="player-card-modal">
                            <h4>🏏 ${player.player_name}</h4>
                            <div class="player-detail">
                                <span class="player-detail-label">Type:</span>
                                <span class="player-detail-value">${player.player_type}</span>
                            </div>
                            <div class="player-detail">
                                <span class="player-detail-label">Base Price:</span>
                                <span class="player-detail-value">₹${(player.base_price / 10000000).toFixed(2)} Cr</span>
                            </div>
                            <div class="player-detail">
                                <span class="player-detail-label">Bought For:</span>
                                <span class="player-detail-value" style="color: #34d399; font-size: 1.1rem;">₹${(player.sold_price / 10000000).toFixed(2)} Cr</span>
                            </div>
                        </div>
                    `;
                });
                playersHTML += '</div>';
            } else {
                playersHTML = '<div style="text-align: center; padding: 3rem; color: #94a3b8;"><div style="font-size: 3rem; margin-bottom: 1rem;">🏏</div><p style="font-size: 1.1rem;">No players bought yet</p><p style="font-size: 0.875rem; margin-top: 0.5rem;">Start bidding to build your squad!</p></div>';
            }
            
            const modalHTML = `
                <div id="participantModal" class="modal" style="display: block;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>🎯 ${participant.team_name}'s Squad</h2>
                            <span class="close" onclick="closeModal()">&times;</span>
                        </div>
                        <div class="modal-body">
                            <div class="budget-summary">
                                <div class="budget-card" style="border-color: #34d399; background: rgba(52, 211, 153, 0.1);">
                                    <div class="budget-label">💰 Total Budget</div>
                                    <div class="budget-value" style="color: #34d399;">₹${(totalBudget / 10000000).toFixed(2)} Cr</div>
                                </div>
                                <div class="budget-card" style="border-color: #ef4444; background: rgba(239, 68, 68, 0.1);">
                                    <div class="budget-label">💸 Spent</div>
                                    <div class="budget-value" style="color: #ef4444;">₹${(spentAmount / 10000000).toFixed(2)} Cr</div>
                                </div>
                                <div class="budget-card" style="border-color: #fbbf24; background: rgba(251, 191, 36, 0.1);">
                                    <div class="budget-label">💵 Remaining</div>
                                    <div class="budget-value" style="color: #fbbf24;">₹${(participant.remaining_budget / 10000000).toFixed(2)} Cr</div>
                                </div>
                                <div class="budget-card" style="border-color: #60a5fa; background: rgba(96, 165, 250, 0.1);">
                                    <div class="budget-label">🏏 Players</div>
                                    <div class="budget-value" style="color: #60a5fa;">${players ? players.length : 0}</div>
                                </div>
                            </div>
                            <h3 style="color: white; margin-bottom: 1.5rem; border-bottom: 2px solid rgba(96, 165, 250, 0.3); padding-bottom: 0.5rem;">
                                Squad Players ${players && players.length > 0 ? '(' + players.length + ')' : ''}
                            </h3>
                            ${playersHTML}
                        </div>
                    </div>
                </div>
            `;
            
            const existingModal = document.getElementById('participantModal');
            if (existingModal) existingModal.remove();
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            document.getElementById('participantModal').onclick = function(event) {
                if (event.target.id === 'participantModal') {
                    closeModal();
                }
            };
        }
        
        function closeModal() {
            const modal = document.getElementById('participantModal');
            if (modal) modal.remove();
        }
        
        // Bid Timer - Debug info
        console.log('Page loaded');
        console.log('Time remaining from PHP:', <?php echo $time_remaining; ?>);
        console.log('Current player ID:', <?php echo $room['current_player_id'] ?? 'null'; ?>);
        console.log('Room status:', '<?php echo $room['status']; ?>');
        
        let timeRemaining = <?php echo $time_remaining; ?>;
        let timerInterval = null;
        
        function startTimer() {
            const timerElement = document.getElementById('timer');
            if (!timerElement) {
                console.error('Timer element not found!');
                return;
            }
            
            console.log('Starting timer with ' + timeRemaining + ' seconds');
            
            // Clear any existing interval
            if (timerInterval) {
                clearInterval(timerInterval);
            }
            
            timerInterval = setInterval(function() {
                timeRemaining--;
                console.log('Timer tick:', timeRemaining);
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
                    console.log('Timer expired, auto-finalizing...');
                    autoFinalizeSale();
                }
            }, 1000);
        }
        
        function autoFinalizeSale() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'finalize';
            
            form.appendChild(actionInput);
            document.body.appendChild(form);
            form.submit();
        }
        
        // Start timer when page loads
        window.addEventListener('load', function() {
            console.log('Window loaded event fired');
            const timerElement = document.getElementById('timer');
            if (timerElement) {
                console.log('Timer element found, starting timer...');
                startTimer();
            } else {
                console.log('Timer element NOT found');
            }
        });
        
        // Auto-refresh for synchronization
        <?php if ($current_player && $room['status'] == 'active'): ?>
        setInterval(function() {
            if (timeRemaining > 0) return;
            window.location.reload();
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>
