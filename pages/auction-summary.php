<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auction_room_functions.php';

// Get room id
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
if (!$room_id) {
    echo "Invalid room id";
    exit;
}

// Use existing helpers from includes/auction_room_functions.php
$room = getRoomById($room_id);
if (!$room) {
    echo "Auction room not found";
    exit;
}

// Only show summary after auction ends
if ($room['status'] !== 'completed' && $room['status'] !== 'finished') {
    echo "Auction is not finished yet. The summary will be available after the auction ends.";
    exit;
}

// Fetch participants (teams) using helper
$participants = getRoomParticipants($room_id);

// Fetch assignments grouped by participant using mysqli
$conn = getDBConnection();
$room_id_esc = $conn->real_escape_string($room_id);
 $sql = "SELECT rpa.participant_id, rpa.player_id, rpa.sold_price, p.player_name, p.team as original_team, p.group_name
     FROM room_player_assignments rpa
     LEFT JOIN players p ON p.player_id = rpa.player_id
     WHERE rpa.room_id = $room_id_esc
     ORDER BY rpa.participant_id, rpa.sold_price DESC";
$result = $conn->query($sql);
$assignments = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
}
closeDBConnection($conn);

$byParticipant = [];
foreach ($assignments as $a) {
    $pid = $a['participant_id'];
    if (!isset($byParticipant[$pid])) $byParticipant[$pid] = [];
    $byParticipant[$pid][] = $a;
}

// Fallback: if a participant has 0 assignments from the batch query,
// try fetching via getParticipantPlayers() to ensure we don't miss any rows
foreach ($participants as $p) {
    $pid = $p['participant_id'];
    if (empty($byParticipant[$pid])) {
        $fetched = getParticipantPlayers($pid);
        if (!empty($fetched)) {
            $byParticipant[$pid] = [];
            foreach ($fetched as $row) {
                $byParticipant[$pid][] = [
                    'participant_id' => $pid,
                    'player_id' => $row['player_id'] ?? null,
                    'sold_price' => $row['sold_price'] ?? null,
                    'player_name' => $row['player_name'] ?? ($row['player_name'] ?? ''),
                    'group_name' => $row['group_name'] ?? ($row['auction_group'] ?? '')
                ];
            }
        }
    }
}

// Map team names to CSS classes so each team card can use its brand colors
$teamClassMap = [
    'Royal Challengers Bangalore' => 'team-rcb',
    'Chennai Super Kings' => 'team-csk',
    'Mumbai Indians' => 'team-mi',
    'Kolkata Knight Riders' => 'team-kkr',
    'Lucknow Super Giants' => 'team-lsg',
    'Gujarat Titans' => 'team-gt',
    'Rajasthan Royals' => 'team-rr',
    'Punjab Kings' => 'team-pbks',
    'Delhi Capitals' => 'team-dc',
    'Sunrisers Hyderabad' => 'team-srh'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auction Summary - <?php echo htmlspecialchars($room['room_name'] ?? 'Room'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .summary-wrapper {
            min-height: 100vh;
            padding-bottom: 3rem;
        }

        .summary-container { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 2rem;
        }
        
        .summary-hero {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 25px;
            padding: 3rem;
            margin-bottom: 3rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .summary-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(255, 70, 85, 0.1) 0%, rgba(30, 58, 138, 0.1) 100%);
            border-radius: 50%;
            filter: blur(60px);
        }

        .summary-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
            position: relative;
            z-index: 1;
        }
        
        .summary-title-section h1 { 
            margin: 0;
            font-size: 2.8rem; 
            background: linear-gradient(135deg, #ff4655 0%, #1e3a8a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            line-height: 1.2;
        }
        
        .summary-sub { 
            color: #64748b;
            font-size: 1.1rem;
            margin-top: 0.5rem;
        }

        .summary-controls { 
            display: flex; 
            gap: 1rem; 
            align-items: center;
            flex-wrap: wrap;
        }
        
        .back-link {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 0.9rem 1.8rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-link:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 6px 25px rgba(59, 130, 246, 0.6);
        }
        
        .csv-btn { 
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 0.9rem 1.8rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .csv-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 6px 25px rgba(16, 185, 129, 0.6);
        }

        .teams-grid {
            display: grid;
            gap: 2rem;
            grid-template-columns: repeat(auto-fill, minmax(550px, 1fr));
        }

        .team-card { 
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            color: #0f172a;
        }

        .team-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, #ff4655 0%, #1e3a8a 100%);
        }

        /* Team-specific accents */
        .team-card.team-rcb::before { background: linear-gradient(135deg, #ff4655 0%, #6b21a8 100%); }
        .team-card.team-csk::before { background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); }
        .team-card.team-mi::before { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); }
        .team-card.team-kkr::before { background: linear-gradient(135deg, #6b21a8 0%, #f59e0b 100%); }
        .team-card.team-lsg::before { background: linear-gradient(135deg, #fb923c 0%, #f97316 100%); }
        .team-card.team-gt::before { background: linear-gradient(135deg, #06b6d4 0%, #059669 100%); }
        .team-card.team-rr::before { background: linear-gradient(135deg, #ec4899 0%, #7c3aed 100%); }
        .team-card.team-pbks::before { background: linear-gradient(135deg, #ef4444 0%, #7c3aed 100%); }
        .team-card.team-dc::before { background: linear-gradient(135deg, #1e3a8a 0%, #ef4444 100%); }
        .team-card.team-srh::before { background: linear-gradient(135deg, #ff7a18 0%, #ff4655 100%); }

        /* Team-specific purse badge colors */
        .team-card.team-rcb .purse-badge { background: linear-gradient(135deg, #ff4655 0%, #6b21a8 100%); box-shadow: 0 6px 20px rgba(107,33,168,0.18); }
        .team-card.team-csk .purse-badge { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); box-shadow: 0 6px 20px rgba(245,158,11,0.12); color:#0f172a; }
        .team-card.team-mi .purse-badge { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); box-shadow: 0 6px 20px rgba(29,78,216,0.14); }
        .team-card.team-kkr .purse-badge { background: linear-gradient(135deg, #6b21a8 0%, #f59e0b 100%); box-shadow: 0 6px 20px rgba(107,33,168,0.12); }
        .team-card.team-lsg .purse-badge { background: linear-gradient(135deg, #fb923c 0%, #f97316 100%); box-shadow: 0 6px 20px rgba(249,115,22,0.12); }
        .team-card.team-gt .purse-badge { background: linear-gradient(135deg, #06b6d4 0%, #059669 100%); box-shadow: 0 6px 20px rgba(6,182,212,0.12); }
        .team-card.team-rr .purse-badge { background: linear-gradient(135deg, #ec4899 0%, #7c3aed 100%); box-shadow: 0 6px 20px rgba(124,58,237,0.12); }
        .team-card.team-pbks .purse-badge { background: linear-gradient(135deg, #ef4444 0%, #7c3aed 100%); box-shadow: 0 6px 20px rgba(239,68,68,0.12); }
        .team-card.team-dc .purse-badge { background: linear-gradient(135deg, #1e3a8a 0%, #ef4444 100%); box-shadow: 0 6px 20px rgba(30,58,138,0.12); }
        .team-card.team-srh .purse-badge { background: linear-gradient(135deg, #ff7a18 0%, #ff4655 100%); box-shadow: 0 6px 20px rgba(255,122,24,0.12); }

        /* Team card full backgrounds + contrast tweaks */
        .team-card.team-rcb { background: linear-gradient(135deg, #ff4655 0%, #6b21a8 100%); color: #0f172a; border: 1px solid rgba(255,255,255,0.06); }
        .team-card.team-rcb .player-item { background: rgba(255,255,255,0.9); border-color: rgba(15,23,42,0.06); }
        .team-card.team-rcb .player-name, .team-card.team-rcb .team-id { color: #0f172a; }
        .team-card.team-rcb * { color: #0f172a !important; }

        .team-card.team-csk { background: linear-gradient(135deg, #fff7cc 0%, #fff3b0 100%); color: #0f172a; border: 1px solid rgba(34,34,34,0.04); }
        .team-card.team-csk .player-item { background: rgba(15,23,42,0.03); border-color: rgba(15,23,42,0.04); }
        .team-card.team-csk .player-name, .team-card.team-csk .team-id { color: #0f172a; }

        .team-card.team-mi { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #0f172a; border: 1px solid rgba(29,78,216,0.06); }
        .team-card.team-mi .player-item { background: rgba(255,255,255,0.6); }

        .team-card.team-kkr { background: linear-gradient(135deg, #efe6ff 0%, #fef3c7 100%); color: #0f172a; }
        .team-card.team-lsg { background: linear-gradient(135deg, #fff3e0 0%, #fff1e0 100%); color: #0f172a; }
        .team-card.team-gt { background: linear-gradient(135deg, #e6fffb 0%, #d1fae5 100%); color: #0f172a; }
        .team-card.team-rr { background: linear-gradient(135deg, #fff0f6 0%, #f3e8ff 100%); color: #0f172a; }
        .team-card.team-pbks { background: linear-gradient(135deg, #fff5f5 0%, #fff0f0 100%); color: #0f172a; }
        .team-card.team-dc { background: linear-gradient(135deg, #eaf2ff 0%, #eef2ff 100%); color: #0f172a; }
        .team-card.team-srh { background: linear-gradient(135deg, #fff7f0 0%, #fff3f0 100%); color: #0f172a; }

        .team-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.2);
        }
        
        .team-header { 
            display: flex; 
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid rgba(255, 70, 85, 0.1);
        }
        
        .team-info h3 { 
            font-size: 1.6rem; 
            color: #0f172a;
            margin: 0 0 0.5rem 0;
            font-weight: 700;
        }
        
        .team-id { 
            color: #94a3b8;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .team-stats {
            text-align: right;
        }

        .purse-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            display: inline-block;
            margin-bottom: 0.5rem;
        }

        .players-count {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .players-count i {
            color: #ff4655;
        }
        
        .player-list { 
            display: grid;
            gap: 0.75rem;
        }
        
        .player-item { 
            background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 1rem 1.25rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            border: 1px solid rgba(226, 232, 240, 0.5);
        }

        .player-item:hover {
            transform: translateX(5px);
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .player-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .player-info {
            flex: 1;
        }

        .player-name {
            font-weight: 700;
            color: #0f172a;
            font-size: 1.05rem;
            margin-bottom: 0.25rem;
        }

        .player-group {
            color: #64748b;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .player-group i {
            color: #ff4655;
            font-size: 0.75rem;
        }

        .player-price {
            text-align: right;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            font-size: 1.2rem;
            white-space: nowrap;
        }
        
        .no-players { 
            color: #94a3b8;
            padding: 2rem;
            text-align: center;
            font-style: italic;
            background: rgba(148, 163, 184, 0.1);
            border-radius: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }

        .empty-state i {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: #64748b;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .summary-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .summary-title-section h1 {
                font-size: 2rem;
            }

            .teams-grid {
                grid-template-columns: 1fr;
            }

            .summary-hero {
                padding: 2rem;
            }

            .summary-container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
<?php if (file_exists(__DIR__ . '/../includes/header.php')) include __DIR__ . '/../includes/header.php'; ?>

<div class="summary-wrapper">
    <div class="summary-container">
        <!-- Hero Section -->
        <div class="summary-hero">
            <div class="summary-header">
                <div class="summary-title-section">
                    <h1><i class="fas fa-trophy"></i> Auction Summary</h1>
                    <div class="summary-sub">
                        <strong><?php echo htmlspecialchars($room['room_name'] ?? 'IPL Auction'); ?></strong> - Final squads and remaining purses
                    </div>
                </div>
                <div class="summary-controls">
                    <a class="back-link" href="my-auctions.php">
                        <i class="fas fa-arrow-left"></i> Back to My Auctions
                    </a>
                    <a class="csv-btn" href="auction-summary.php?room_id=<?php echo $room_id; ?>&export=csv">
                        <i class="fas fa-download"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- Teams Grid -->
        <?php if (count($participants) === 0): ?>
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <p>No participants found for this auction.</p>
            </div>
        <?php else: ?>
            <div class="teams-grid">
                <?php foreach ($participants as $p): ?>
                    <?php $teamClass = isset($teamClassMap[$p['team_name']]) ? $teamClassMap[$p['team_name']] : 'team-default'; ?>
                    <div class="team-card <?php echo $teamClass; ?>">
                        <div class="team-header">
                            <div class="team-info">
                                <h3><i class="fas fa-shield-alt"></i> <?php echo htmlspecialchars($p['team_name'] ?: 'Team'); ?></h3>
                                <div class="team-id">Participant #<?php echo (int)$p['participant_id']; ?></div>
                            </div>
                            <div class="team-stats">
                                <div class="purse-badge">
                                    <i class="fas fa-wallet"></i> ₹<?php echo number_format((float)$p['remaining_budget'] / 10000000, 2); ?> Cr
                                </div>
                                <div class="players-count">
                                    <i class="fas fa-users"></i> <?php echo (isset($p['players_bought']) ? (int)$p['players_bought'] : (isset($byParticipant[$p['participant_id']]) ? count($byParticipant[$p['participant_id']]) : 0)); ?> Players
                                </div>
                            </div>
                        </div>

                        <div class="player-list">
                            <?php if (!empty($byParticipant[$p['participant_id']])): ?>
                                <?php foreach ($byParticipant[$p['participant_id']] as $pl): ?>
                                    <div class="player-item">
                                        <div class="player-details">
                                            <div class="player-info">
                                                <div class="player-name"><?php echo htmlspecialchars($pl['player_name']); ?></div>
                                                <div class="player-group">
                                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($pl['group_name'] ?? 'N/A'); ?>
                                                </div>
                                            </div>
                                            <div class="player-price">
                                                ₹<?php echo number_format((float)$pl['sold_price'] / 10000000, 2); ?> Cr
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-players">
                                    <i class="fas fa-info-circle"></i> No players bought
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (file_exists(__DIR__ . '/../includes/footer.php')) include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

