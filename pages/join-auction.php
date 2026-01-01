<?php 
require_once '../config/session.php';
require_once '../includes/auction_room_functions.php';

requireLogin();

$current_user = getCurrentUser();
$room_code = $_GET['code'] ?? $_POST['room_code'] ?? '';
$room = null;
$error = '';
$success = false;

// Predefined teams (display name => code)
$teams = [
    'Mumbai Indians' => 'mi',
    'Chennai Super Kings' => 'csk',
    'Royal Challengers Bangalore' => 'rcb',
    'Kolkata Knight Riders' => 'kkr',
    'Lucknow Super Giants' => 'lsg',
    'Gujarat Titans' => 'gt',
    'Rajasthan Royals' => 'rr',
    'Punjab Kings' => 'pbks',
    'Delhi Capitals' => 'dc',
    'Sunrisers Hyderabad' => 'srh'
];

if ($room_code) {
    $room = getRoomByCode($room_code);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'join') {
    $room_code = $_POST['room_code'];
    $team_name = $_POST['team_name'] ?? '';
    
    if ($team_name) {
        $result = joinAuctionRoom($room_code, $current_user['user_id'], $team_name);
        
        if ($result['success']) {
            header('Location: auction-room.php?room_id=' . $result['room_id']);
            exit();
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Please provide a team name';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Auction - IPL Auction</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2.0">
    <style>
        .join-container {
            max-width: 600px;
            margin: 3rem auto;
            padding: 0 2rem;
        }
        .join-card {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(30, 41, 59, 0.95) 100%);
            padding: 3rem;
            border-radius: 20px;
            color: white;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .join-card h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2.5rem;
            background: linear-gradient(135deg, #34d399, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .join-card > p {
            color: #94a3b8;
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            color: #cbd5e1;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .form-group input {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            transition: all 0.3s;
        }
        .form-group select {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            color: #000000;
            font-size: 1rem;
            transition: all 0.3s;
        }
        /* Attempt to style option text color (may be limited by browser) */
        .form-group select option {
            color: #000000;
            background: #ffffff;
        }
        .form-group input:focus {
            outline: none;
            border-color: #34d399;
            background: rgba(255, 255, 255, 0.08);
        }
        .btn-join {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #34d399, #10b981);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-join:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(52, 211, 153, 0.3);
        }
        .btn-back {
            width: 100%;
            padding: 0.875rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 1rem;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        .room-info {
            background: rgba(52, 211, 153, 0.1);
            border: 2px solid #34d399;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .room-info h3 {
            margin: 0 0 1rem 0;
            color: #34d399;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            margin: 0.5rem 0;
            color: #cbd5e1;
        }
        .info-item strong {
            color: #e2e8f0;
        }
        .error-message {
            background: rgba(239, 68, 68, 0.2);
            border: 2px solid #ef4444;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .error-message p {
            margin: 0;
            color: #fca5a5;
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
                <li><a href="my-auctions.php">My Auctions</a></li>
                <li><a href="../user/dashboard.php">Dashboard</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="join-container">
        <div class="join-card">
            <h1>Join Auction</h1>
            <p>Enter the room code to join your friend's auction</p>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!$room_code || !$room): ?>
                <form method="GET">
                    <div class="form-group">
                        <label for="code">Room Code</label>
                        <input type="text" id="code" name="code" required placeholder="Enter 6-digit code" maxlength="6" style="text-align: center; font-size: 1.5rem;">
                    </div>
                    <button type="submit" class="btn-join">Find Room</button>
                    <a href="my-auctions.php" class="btn-back">Back to My Auctions</a>
                </form>
            <?php elseif ($room): ?>
                <div class="room-info">
                    <h3>🎯 Room Details</h3>
                    <div class="info-item">
                        <span>Room Name:</span>
                        <strong><?php echo htmlspecialchars($room['room_name']); ?></strong>
                    </div>
                    <div class="info-item">
                        <span>Host:</span>
                        <strong><?php echo htmlspecialchars($room['host_name'] ?: $room['host_username']); ?></strong>
                    </div>
                    <div class="info-item">
                        <span>Participants:</span>
                        <strong><?php echo $room['participants_count']; ?> / <?php echo $room['max_participants']; ?></strong>
                    </div>
                    <div class="info-item">
                        <span>Budget Per Team:</span>
                        <strong>₹<?php echo number_format($room['total_budget_per_team'] / 10000000, 0); ?> Cr</strong>
                    </div>
                    <div class="info-item">
                        <span>Status:</span>
                        <strong style="color: <?php echo $room['status'] == 'waiting' ? '#fbbf24' : ($room['status'] == 'in_progress' ? '#34d399' : '#94a3b8'); ?>">
                            <?php echo ucfirst($room['status']); ?>
                        </strong>
                    </div>
                </div>
                
                <?php if ($room['participants_count'] >= $room['max_participants']): ?>
                    <div class="error-message">
                        <p>⚠️ This room is full. Please ask the host to create a new room.</p>
                    </div>
                    <a href="my-auctions.php" class="btn-back">Back to My Auctions</a>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="join">
                        <input type="hidden" name="room_code" value="<?php echo htmlspecialchars($room_code); ?>">
                        
                        <div class="form-group">
                            <label for="team_name">Select Your Team</label>
                            <select id="team_name" name="team_name" required style="padding:0.75rem; border-radius:8px; background: rgba(255,255,255,0.04); color: white; width:100%;">
                                <option value="">-- Choose a team --</option>
                                <?php foreach ($teams as $display => $code): ?>
                                    <option value="<?php echo htmlspecialchars($display); ?>"><?php echo htmlspecialchars($display); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn-join">Join Auction Room</button>
                        <a href="my-auctions.php" class="btn-back">Cancel</a>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
