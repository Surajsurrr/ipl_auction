<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Auctions - IPL Auction</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2.0">
    <style>
        .auctions-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .page-header p {
            color: #94a3b8;
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 3rem;
        }
        .btn {
            padding: 1rem 2rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-create {
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            color: white;
        }
        .btn-join {
            background: linear-gradient(135deg, #34d399, #10b981);
            color: white;
        }
        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        .room-card {
            background: rgba(15, 23, 42, 0.95);
            padding: 1.5rem;
            border-radius: 15px;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.1);
            transition: border-color 0.3s;
        }
        .room-card:hover {
            border-color: #60a5fa;
        }
        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        .room-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: #e2e8f0;
        }
        .room-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-waiting {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
        }
        .status-in_progress {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }
        .status-completed {
            background: rgba(148, 163, 184, 0.2);
            color: #94a3b8;
        }
        .room-code {
            font-size: 1.5rem;
            font-weight: bold;
            letter-spacing: 0.2em;
            color: #60a5fa;
            text-align: center;
            padding: 0.75rem;
            background: rgba(96, 165, 250, 0.1);
            border-radius: 8px;
            margin: 1rem 0;
        }
        .room-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin: 1rem 0;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            color: #94a3b8;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .info-value {
            color: #e2e8f0;
            font-weight: 600;
            margin-top: 0.25rem;
        }
        .room-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .btn-enter {
            flex: 1;
            padding: 0.75rem;
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: block;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #94a3b8;
        }
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php 
    require_once '../config/session.php';
    require_once '../includes/auction_room_functions.php';
    
    requireLogin();
    
    $current_user = getCurrentUser();
    $rooms = getUserRooms($current_user['user_id']);
    ?>
    
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">🏏 IPL Auction</a>
            <ul class="nav-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="../pages/players.php">Players</a></li>
                <li><a href="../pages/teams.php">Teams</a></li>
                <li><a href="my-auctions.php" class="active">My Auctions</a></li>
                <li><a href="../user/dashboard.php">Dashboard</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="auctions-container">
        <div class="page-header">
            <h1>My Auction Rooms</h1>
            <p>Create your own auction or join your friends</p>
        </div>
        
        <div class="action-buttons">
            <a href="create-auction.php" class="btn btn-create">🎯 Create New Auction</a>
            <a href="join-auction.php" class="btn btn-join">🔗 Join with Code</a>
        </div>
        
        <?php if (empty($rooms)): ?>
            <div class="empty-state">
                <h3>No Auctions Yet</h3>
                <p>Create your first auction room or join one using a code!</p>
            </div>
        <?php else: ?>
            <div class="rooms-grid">
                <?php foreach ($rooms as $room): ?>
                    <div class="room-card">
                        <div class="room-header">
                            <div class="room-name"><?php echo htmlspecialchars($room['room_name']); ?></div>
                            <div class="room-status status-<?php echo $room['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $room['status'])); ?>
                            </div>
                        </div>
                        
                        <div class="room-code"><?php echo htmlspecialchars($room['room_code']); ?></div>
                        
                        <div class="room-info">
                            <div class="info-item">
                                <span class="info-label">Participants</span>
                                <span class="info-value"><?php echo $room['participants_count']; ?> / <?php echo $room['max_participants']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">My Team</span>
                                <span class="info-value"><?php echo htmlspecialchars($room['my_team_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Created</span>
                                <span class="info-value"><?php echo date('M d, Y', strtotime($room['created_at'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Budget</span>
                                <span class="info-value">₹<?php echo number_format($room['total_budget_per_team'] / 10000000, 0); ?> Cr</span>
                            </div>
                        </div>
                        
                        <div class="room-actions">
                            <a href="auction-room.php?room_id=<?php echo $room['room_id']; ?>" class="btn-enter">
                                <?php echo $room['status'] == 'waiting' ? 'Enter Waiting Room' : 'Enter Auction'; ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
