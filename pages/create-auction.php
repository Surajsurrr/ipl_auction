<?php 
require_once '../config/session.php';
require_once '../includes/auction_room_functions.php';

requireLogin();

$current_user = getCurrentUser();
$room_created = false;
$room_code = '';
$room_id = '';
$error = '';

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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_name = $_POST['room_name'] ?? '';
    $max_participants = intval($_POST['max_participants'] ?? 10);
    $budget = floatval($_POST['budget'] ?? 120) * 10000000; // Convert to paise
    
    if ($room_name) {
        $result = createAuctionRoom($current_user['user_id'], $room_name, $max_participants, $budget);
        
        if ($result['success']) {
            $room_created = true;
            $room_code = $result['room_code'];
            $room_id = $result['room_id'];
            
            // Auto-join the creator
            $team_name = $_POST['team_name'] ?? ($current_user['username'] . "'s Team");
            joinAuctionRoom($room_code, $current_user['user_id'], $team_name);
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Please provide a room name';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Auction Room - IPL Auction</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2.0">
    <style>
        .create-room-container {
            max-width: 600px;
            margin: 3rem auto;
            padding: 0 2rem;
        }
        .room-card {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(30, 41, 59, 0.95) 100%);
            padding: 3rem;
            border-radius: 20px;
            color: white;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .room-card h1 {
            margin: 0 0 1rem 0;
            font-size: 2.5rem;
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .room-card p {
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
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-size: 1rem;
            transition: all 0.3s;
        }
        /* Set select option text to black */
        .form-group select option {
            color: #000000;
            background: #ffffff;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #60a5fa;
            background: rgba(255, 255, 255, 0.08);
        }
        .form-group small {
            color: #94a3b8;
            font-size: 0.875rem;
            display: block;
            margin-top: 0.5rem;
        }
        .btn-create {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(96, 165, 250, 0.3);
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
        .success-message {
            background: rgba(34, 197, 94, 0.2);
            border: 2px solid #22c55e;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .room-code-display {
            text-align: center;
            margin: 2rem 0;
        }
        .room-code {
            font-size: 3rem;
            font-weight: bold;
            letter-spacing: 0.5rem;
            color: #60a5fa;
            background: rgba(96, 165, 250, 0.1);
            padding: 1rem;
            border-radius: 10px;
            display: inline-block;
        }
        .share-info {
            background: rgba(96, 165, 250, 0.1);
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 2rem;
        }
        .share-info h3 {
            margin: 0 0 1rem 0;
            color: #60a5fa;
        }
        .share-info p {
            margin: 0.5rem 0;
            color: #cbd5e1;
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

    <div class="create-room-container">
        <?php if ($room_created): ?>
            <div class="room-card">
                <div class="success-message">
                    <h2 style="margin: 0 0 0.5rem 0; color: #22c55e;">🎉 Auction Room Created!</h2>
                    <p style="margin: 0; color: #86efac;">Your auction room has been created successfully.</p>
                </div>
                
                <div class="room-code-display">
                    <h3 style="color: #cbd5e1; margin-bottom: 1rem;">Room Code</h3>
                    <div class="room-code"><?php echo htmlspecialchars($room_code); ?></div>
                </div>
                
                <div class="share-info">
                    <h3>📢 Share with Friends</h3>
                    <p>Share this code with your friends so they can join your auction!</p>
                    <p><strong>Room Code:</strong> <?php echo htmlspecialchars($room_code); ?></p>
                    <p><strong>Join URL:</strong> <?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/ipl_auction/pages/join-auction.php?code=' . $room_code; ?></p>
                </div>
                
                <a href="auction-room.php?room_id=<?php echo $room_id; ?>" class="btn-create" style="margin-top: 2rem;">
                    Enter Auction Room →
                </a>
                <a href="my-auctions.php" class="btn-back">View All My Auctions</a>
            </div>
        <?php else: ?>
            <div class="room-card">
                <h1>Create Auction Room</h1>
                <p>Set up your own private auction and invite friends to compete!</p>
                
                <?php if ($error): ?>
                    <div style="background: rgba(239, 68, 68, 0.2); border: 2px solid #ef4444; padding: 1rem; border-radius: 10px; margin-bottom: 2rem;">
                        <p style="margin: 0; color: #fca5a5;"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="room_name">Auction Room Name *</label>
                        <input type="text" id="room_name" name="room_name" required placeholder="e.g., New Year Auction 2026">
                        <small>Give your auction a memorable name</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="team_name">Select Your Team *</label>
                        <select id="team_name" name="team_name" required>
                            <option value="">-- Choose a team --</option>
                            <?php foreach ($teams as $display => $code): ?>
                                <option value="<?php echo htmlspecialchars($display); ?>"><?php echo htmlspecialchars($display); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small>Choose one of the 10 official teams for your entry.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_participants">Maximum Players</label>
                        <select id="max_participants" name="max_participants">
                            <option value="2">2 Players</option>
                            <option value="4">4 Players</option>
                            <option value="6">6 Players</option>
                            <option value="8">8 Players</option>
                            <option value="10" selected>10 Players</option>
                        </select>
                        <small>How many people can join this auction?</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="budget">Budget Per Team (Crores)</label>
                        <input type="number" id="budget" name="budget" value="120" min="50" max="500" step="10">
                        <small>Default: ₹120 Crores per team</small>
                    </div>
                    
                    <button type="submit" class="btn-create">Create Auction Room</button>
                    <a href="my-auctions.php" class="btn-back">Cancel</a>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
