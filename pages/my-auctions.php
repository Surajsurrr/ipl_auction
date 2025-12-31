<?php 
require_once '../config/session.php';
require_once '../includes/auction_room_functions.php';

requireLogin();

$current_user = getCurrentUser();
$rooms = getUserRooms($current_user['user_id']);
?>
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
            background: white;
            padding: 2.5rem 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            text-align: center;
        }
        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
        }
        .page-header p {
            color: #6b7280;
            font-size: 1.1rem;
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 2.5rem;
            flex-wrap: wrap;
        }
        .btn {
            padding: 1rem 2.5rem;
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            transition: all 0.3s;
            border: none;
        }
        .btn:hover {
            transform: translateY(-3px);
        }
        .btn-create {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        .btn-create:hover {
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }
        .btn-join {
            background: white;
            color: #10b981;
            border: 2px solid #10b981;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        .btn-join:hover {
            background: #f0fdf4;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        .room-card {
            background: white;
            padding: 1.8rem;
            border-radius: 15px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .room-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
            transform: translateY(-3px);
        }
        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1.2rem;
        }
        .room-name {
            font-size: 1.35rem;
            font-weight: 700;
            color: #1f2937;
        }
        .room-status {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .status-waiting {
            background: #fef3c7;
            color: #d97706;
        }
        .status-in_progress {
            background: #d1fae5;
            color: #059669;
        }
        .status-completed {
            background: #e5e7eb;
            color: #6b7280;
        }
        .room-code {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: 0.25em;
            color: #3b82f6;
            text-align: center;
            padding: 1rem;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border-radius: 12px;
            margin: 1.2rem 0;
            border: 2px solid #bfdbfe;
        }
        .room-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin: 1.2rem 0;
        }
        .info-item {
            background: #f9fafb;
            padding: 0.8rem;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .info-label {
            color: #6b7280;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        .info-value {
            color: #1f2937;
            font-weight: 700;
            font-size: 1.05rem;
        }
        .room-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }
        .btn-enter {
            flex: 1;
            padding: 1rem;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1.05rem;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: block;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        .btn-enter:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }
        .empty-state {
            background: white;
            text-align: center;
            padding: 5rem 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 2px dashed #e5e7eb;
        }
        .empty-state h3 {
            font-size: 1.8rem;
            margin-bottom: 0.8rem;
            color: #1f2937;
            font-weight: 700;
        }
        .empty-state p {
            color: #6b7280;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    
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
