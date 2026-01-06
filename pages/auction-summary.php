<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auction_room_functions.php';

// Helper: resolve a team logo path by matching known name fragments (falls back to null)
function getTeamLogoPath($team_name) {
    $team_logos = [
        'Chennai' => '../assets/images/teams/csk.png',
        'Delhi' => '../assets/images/teams/dc.png',
        'Mumbai' => '../assets/images/teams/mi.png',
        'Kolkata' => '../assets/images/teams/kkr.png',
        'Gujarat' => '../assets/images/teams/gt.png',
        'Royal' => '../assets/images/teams/rcb.png',
        'Rajasthan' => '../assets/images/teams/rr.png',
        'Sunrisers' => '../assets/images/teams/srh.png',
        'Lucknow' => '../assets/images/teams/lsg.png',
        'Punjab' => '../assets/images/teams/pbks.png'
    ];

    foreach ($team_logos as $key => $path) {
        if (stripos($team_name, $key) !== false) return $path;
    }

    return null;
}

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
$status = strtolower(trim($room['status'] ?? ''));
error_log("Auction Summary - Room ID: $room_id, Status: '$status', Raw Status: '{$room['status']}'");

if ($status !== 'completed' && $status !== 'finished') {
    echo "Auction is not finished yet. The summary will be available after the auction ends.<br>";
    echo "Current status: " . htmlspecialchars($room['status']);
    exit;
}

// Fetch participants (teams) using helper
$participants = getRoomParticipants($room_id);

// Attach logo path to participants for template rendering
foreach ($participants as $i => $part) {
    $participants[$i]['logo'] = getTeamLogoPath($part['team_name'] ?? '');
}

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

        /* Winner banner */
        .winner-banner {
            display: none;
            position: relative;
            margin-top: 1rem;
            padding: 1rem 1.25rem;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(6,182,212,0.12), rgba(59,130,246,0.06));
            border: 1px solid rgba(16,185,129,0.06);
            box-shadow: 0 8px 30px rgba(2,6,23,0.04);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .winner-banner img { width:48px;height:48px;border-radius:8px; }
        .winner-banner .winner-text { font-size:1.25rem; font-weight:800; color:#0f172a; }
        .winner-banner .winner-sub { color:#475569; font-weight:600; }

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

        .vote-btn {
            background: linear-gradient(135deg,#06b6d4,#3b82f6);
            color: white;
            border: none;
            padding: 0.5rem 0.9rem;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            margin-left: 8px;
        }
        .vote-btn.voted { background: linear-gradient(135deg,#f59e0b,#f97316); }
        .vote-count { font-weight:700; color:#0f172a; margin-left:8px; }
        
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
    <style>
        /* Chat sidebar styles */
        .chat-toggle {
            position: fixed;
            left: 16px;
            top: 220px;
            z-index: 1200;
            background: linear-gradient(135deg,#06b6d4,#3b82f6);
            color: white;
            border: none;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(2,6,23,0.5);
            cursor: pointer;
            font-size: 18px;
        }

        .chat-sidebar {
            position: fixed;
            left: 16px;
            top: 60px;
            bottom: 20px;
            width: 340px;
            /* keep a fixed width so it never expands to full width */
            max-width: 340px;
            /* slightly transparent so page to the right remains visible */
            background: rgba(11,18,32,0.88);
            backdrop-filter: blur(6px);
            color: white;
            border-radius: 12px;
            /* smaller shadow so it doesn't visually dominate */
            box-shadow: 0 8px 30px rgba(2,6,23,0.45);
            display: none;
            z-index: 1200;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .chat-header { padding: 12px 14px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.04); }
        .chat-title { font-weight:700; }
        .chat-close { background:transparent; color: #cbd5e1; border:none; cursor:pointer; font-size:16px; }

        .chat-messages { padding: 12px; overflow-y: auto; flex:1; }
        .chat-message { margin-bottom: 10px; display:flex; gap:8px; align-items:flex-start; }
        .chat-avatar { width:36px; height:36px; border-radius:8px; background:#111827; flex:0 0 36px; display:inline-block; overflow:hidden; }
        .chat-avatar img { width:100%; height:100%; object-fit:cover; }
        .chat-bubble { background: rgba(255,255,255,0.04); padding:8px 10px; border-radius:8px; max-width:78%; }
        .chat-meta { font-size:12px; color:#9ca3af; margin-bottom:4px; }

        .chat-form { display:flex; gap:8px; padding:10px; border-top:1px solid rgba(255,255,255,0.03); }
        .chat-form input { flex:1; padding:10px 12px; border-radius:8px; border:1px solid rgba(255,255,255,0.04); background: rgba(255,255,255,0.02); color:white; }
        .chat-form button { padding:10px 12px; border-radius:8px; border:none; background:linear-gradient(135deg,#06b6d4,#3b82f6); color:white; cursor:pointer; }
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
                <div id="winnerBanner" class="winner-banner" role="status" aria-live="polite" style="display:none;">
                    <img id="winnerLogo" src="" alt="winner logo" />
                    <div>
                        <div class="winner-text" id="winnerText">The winner is <span id="winnerTeamName"></span></div>
                        <div class="winner-sub">Congratulations to the winning team!</div>
                    </div>
                </div>
                <div class="summary-controls">
                    <a class="back-link" href="my-auctions.php">
                        <i class="fas fa-arrow-left"></i> Back to My Auctions
                    </a>
                    <button id="startVoting" class="csv-btn" style="display:inline-flex;align-items:center;gap:0.5rem;">
                        <i class="fas fa-vote-yea"></i> Vote for the Best
                    </button>
                    <div id="votingStatus" style="margin-left:8px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px;"></div>
                    <?php $currentUser = getCurrentUser(); $isHost = $currentUser && ((int)$currentUser['user_id'] === (int)$room['created_by']); ?>
                    <?php if ($isHost): ?>
                        <button id="announceWinner" class="csv-btn" style="background:linear-gradient(135deg,#f59e0b,#f97316); display:inline-flex;align-items:center;gap:0.5rem; margin-left:8px;" disabled>
                            <i class="fas fa-bullhorn"></i> Announce Winner
                        </button>
                        <div id="announceError" style="color:#ff6b6b;font-weight:700;margin-left:8px;display:inline-block;"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Teams Grid -->
        <!-- Chat sidebar toggle + container -->
        <div id="chatSidebar" class="chat-sidebar">
            <div class="chat-header">
                <div class="chat-title">Live Chat</div>
                <button id="chatClose" class="chat-close" title="Close chat">✕</button>
            </div>
            <div id="chatMessages" class="chat-messages" aria-live="polite"></div>
            <form id="chatForm" class="chat-form">
                <input type="text" id="chatInput" placeholder="Write a message..." autocomplete="off" />
                <button type="submit">Send</button>
            </form>
        </div>

        <button id="chatToggle" class="chat-toggle" title="Open chat">💬</button>

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
                                <h3>
                                    <?php if (!empty($p['logo'])): ?>
                                        <img src="<?php echo htmlspecialchars($p['logo']); ?>" alt="<?php echo htmlspecialchars($p['team_name']); ?> logo" style="width:36px;height:36px;vertical-align:middle;margin-right:8px;border-radius:6px;">
                                    <?php else: ?>
                                        <i class="fas fa-shield-alt"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($p['team_name'] ?: 'Team'); ?>
                                </h3>
                                <div style="display:flex; align-items:center; gap:12px; margin-top:6px;">
                                    <div class="team-id">Participant #<?php echo (int)$p['participant_id']; ?></div>
                                    <div>
                                        <button class="vote-btn" data-pid="<?php echo (int)$p['participant_id']; ?>">Vote</button>
                                        <span class="vote-count" data-pid="<?php echo (int)$p['participant_id']; ?>">0</span>
                                    </div>
                                </div>
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
    <script>
        // Chat client for summary page
        const currentUser = <?php echo json_encode(getCurrentUser() ?: null); ?>;
        const participants = <?php echo json_encode($participants); ?>;
        const isHost = <?php echo json_encode($isHost ?? false); ?>;
        const roomStatus = <?php echo json_encode($room['status'] ?? ''); ?>;
        const roomId = <?php echo json_encode($room_id); ?>;

        // Find participant entry for current user if any
        let myParticipant = null;
        if (currentUser) {
            myParticipant = participants.find(p => p.user_id == currentUser.user_id) || null;
        }

        const chatToggle = document.getElementById('chatToggle');
        const chatSidebar = document.getElementById('chatSidebar');
        const chatClose = document.getElementById('chatClose');
        const chatForm = document.getElementById('chatForm');
        const chatInput = document.getElementById('chatInput');
        const chatMessages = document.getElementById('chatMessages');

        function openChat() { chatSidebar.style.display = 'flex'; chatToggle.style.display = 'none'; loadMessages(true); }
        function closeChat() { chatSidebar.style.display = 'none'; chatToggle.style.display = 'block'; }

        chatToggle.addEventListener('click', openChat);
        chatClose.addEventListener('click', closeChat);

        async function loadMessages(scrollToBottom) {
            try {
                const res = await fetch('../ajax/chat.php?room_id=' + encodeURIComponent(roomId));
                const data = await res.json();
                if (!data.success) return;
                renderMessages(data.messages || [], scrollToBottom);
            } catch (e) {
                console.error('Chat load error', e);
            }
        }

        function renderMessages(messages, scrollToBottom) {
            chatMessages.innerHTML = '';
            messages.forEach(m => {
                const row = document.createElement('div');
                row.className = 'chat-message';

                const avatar = document.createElement('div');
                avatar.className = 'chat-avatar';
                // try to find participant logo
                let logo = null;
                if (m.participant_id) {
                    const p = participants.find(pp => pp.participant_id == m.participant_id);
                    if (p && p.logo) logo = p.logo;
                }
                if (logo) {
                    avatar.innerHTML = `<img src="${logo}" alt="logo">`;
                } else {
                    avatar.innerHTML = '<span style="display:block;width:100%;height:100%;background:#111827;border-radius:6px;"></span>';
                }

                const bubble = document.createElement('div');
                bubble.className = 'chat-bubble';
                bubble.innerHTML = `<div class="chat-meta"><strong>${escapeHtml(m.username)}</strong> <span style="margin-left:6px;color:#7c8ea3;font-size:11px;">${new Date(m.timestamp*1000).toLocaleTimeString()}</span></div><div>${escapeHtml(m.text)}</div>`;

                row.appendChild(avatar);
                row.appendChild(bubble);
                chatMessages.appendChild(row);
            });
            if (scrollToBottom) chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Show inline winner announcement and use speech synthesis
        function showAnnouncement(teamName, logo) {
            const banner = document.getElementById('winnerBanner');
            const tEl = document.getElementById('winnerTeamName');
            const lEl = document.getElementById('winnerLogo');
            if (tEl) tEl.textContent = teamName;
            if (lEl) {
                if (logo) { lEl.src = logo; lEl.style.display = 'block'; } else { lEl.style.display = 'none'; }
            }
            if (banner) banner.style.display = 'flex';

            try {
                const utter = new SpeechSynthesisUtterance('The winner is ' + teamName);
                window.speechSynthesis.cancel();
                window.speechSynthesis.speak(utter);
            } catch (e) { console.warn('TTS not available', e); }
        }

        function escapeHtml(s) { if (!s) return ''; return String(s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c]; }); }

        // Polling
        let pollInterval = null;
        function startPolling() { if (pollInterval) clearInterval(pollInterval); pollInterval = setInterval(()=>loadMessages(false), 2500); }

        // Poll for announcements every 3 seconds
        let announcementPoll = null;
        function startAnnouncementPolling() {
            if (announcementPoll) clearInterval(announcementPoll);
            announcementPoll = setInterval(checkAnnouncement, 3000);
        }

        async function checkAnnouncement() {
            try {
                const res = await fetch('../ajax/get_announcement.php?room_id=' + encodeURIComponent(roomId));
                const data = await res.json();
                if (!data.success) return;
                if (data.has && data.announcement) {
                    const ann = data.announcement;
                    // avoid re-showing if already visible
                    const banner = document.getElementById('winnerBanner');
                    if (banner && banner.style.display === 'flex') return;
                    const winning = participants.find(p => String(p.participant_id) === String(ann.winner_participant_id));
                    const teamName = ann.winner_team || (winning ? winning.team_name : 'Winner');
                    const logo = winning ? (winning.logo || '') : '';
                    showAnnouncement(teamName, logo);
                    // disable announce button for host
                    const announceBtn = document.getElementById('announceWinner'); if (announceBtn) { announceBtn.disabled = true; announceBtn.style.opacity = 0.6; }
                }
            } catch (e) { console.error('Announcement poll error', e); }
        }

        chatForm.addEventListener('submit', async function(e){
            e.preventDefault();
            const text = chatInput.value.trim();
            if (!text) return;
            const payload = { room_id: roomId, text: text };
            if (myParticipant) { payload.participant_id = myParticipant.participant_id; payload.team_name = myParticipant.team_name; }
            try {
                const res = await fetch('../ajax/chat.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const data = await res.json();
                if (data.success) {
                    chatInput.value = '';
                    loadMessages(true);
                } else {
                    alert('Could not send message: ' + (data.error || 'unknown'));
                }
            } catch (err) { console.error(err); alert('Send failed'); }
        });

        // Start polling when chat is open
        // Open chat automatically for convenience on desktop
        window.addEventListener('load', function(){ 
            startPolling(); 
            startAnnouncementPolling();
            // Check for announcement immediately on page load
            checkAnnouncement();
        });

        // ----------------------- Voting functionality -----------------------
        const startVotingBtn = document.getElementById('startVoting');
        const votingStatus = document.getElementById('votingStatus');

        startVotingBtn && startVotingBtn.addEventListener('click', function(){
            // reveal voting controls (they are already visible) and fetch current votes
            fetchVotes(true);
            // optionally scroll to teams
            const el = document.querySelector('.teams-grid'); if (el) el.scrollIntoView({behavior:'smooth'});
        });

        async function fetchVotes(scrollToWinner) {
            try {
                const res = await fetch('../ajax/vote.php?room_id=' + encodeURIComponent(roomId));
                const data = await res.json();
                if (!data.success) return;
                const counts = data.counts || {};
                const userVote = data.user_vote || null;

                // update counts and button states
                document.querySelectorAll('.vote-count').forEach(el => {
                    const pid = el.getAttribute('data-pid');
                    const c = counts[pid] || 0;
                    el.textContent = c + ' votes';
                });

                document.querySelectorAll('.vote-btn').forEach(btn => {
                    const pid = btn.getAttribute('data-pid');
                    if (String(pid) === String(userVote)) {
                        btn.classList.add('voted');
                        btn.textContent = 'Voted';
                    } else {
                        btn.classList.remove('voted');
                        btn.textContent = 'Vote';
                    }
                });

                // compute winner
                let winnerPid = null; let winnerCount = 0;
                for (const [k,v] of Object.entries(counts)) {
                    if (v > winnerCount) { winnerCount = v; winnerPid = k; }
                }

                // enable announce button for host only when all participants have voted
                try {
                    const announceBtn = document.getElementById('announceWinner');
                    if (announceBtn) {
                        const totalVotes = Object.values(counts).reduce((s,n) => s + (parseInt(n)||0), 0);
                        if (isHost && totalVotes >= participants.length) {
                            announceBtn.disabled = false;
                        } else {
                            announceBtn.disabled = true;
                        }
                    }
                } catch (e) { console.error(e); }

                // Render vote summary for all participants next to the main button
                let summaryHtml = '';
                participants.forEach(p => {
                    const pid = String(p.participant_id);
                    const c = counts[pid] || 0;
                    const logo = p.logo ? p.logo : '';
                    summaryHtml += `<span style="display:inline-flex;align-items:center;gap:8px;margin-left:10px;">${logo ? `<img src='${logo}' style='width:22px;height:22px;border-radius:4px;'>` : ''}<strong style='font-weight:700;color:#0f172a;'>${escapeHtml(p.team_name)}</strong> <small style='color:#334155;margin-left:6px;'>• ${c} votes</small></span>`;
                });
                votingStatus.innerHTML = summaryHtml || 'No votes yet';

                // highlight current winner if requested
                    if (winnerPid && scrollToWinner) {
                    const card = [...document.querySelectorAll('.team-card')].find(c => c.querySelector('.vote-btn') && c.querySelector('.vote-btn').getAttribute('data-pid') === String(winnerPid));
                    if (card) {
                        card.style.transition = 'box-shadow 0.3s, transform 0.3s';
                        card.style.transform = 'translateY(-6px)';
                        setTimeout(()=>{ card.style.transform = ''; }, 1500);
                    }
                }

                // If the room is already finished, reveal the winner banner inline
                try {
                    if ((roomStatus === 'finished' || roomStatus === 'completed') && winnerPid) {
                        const winning = participants.find(p => String(p.participant_id) === String(winnerPid));
                        if (winning) showAnnouncement(winning.team_name, winning.logo || '');
                    }
                } catch (e) { console.error(e); }

            } catch (e) { console.error('Vote fetch error', e); }
        }

        // Attach click handlers to vote buttons
        document.querySelectorAll('.vote-btn').forEach(btn => {
            btn.addEventListener('click', async function(){
                if (!currentUser) return alert('Please log in to vote');
                const pid = btn.getAttribute('data-pid');
                try {
                    const res = await fetch('../ajax/vote.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ room_id: roomId, participant_id: pid }) });
                    const data = await res.json();
                    if (data.success) {
                        fetchVotes(true);
                    } else {
                        alert('Vote failed: ' + (data.error || 'unknown'));
                    }
                } catch (err) { console.error(err); alert('Vote request failed'); }
            });
        });

        // Announce winner (host only)
        const announceBtnEl = document.getElementById('announceWinner');
        if (announceBtnEl) {
            announceBtnEl.addEventListener('click', async function(){
                // Inline confirmation: require two clicks within 5s to confirm
                if (!announceBtnEl.dataset.confirm) {
                    announceBtnEl.dataset.confirm = '1';
                    const original = announceBtnEl.innerHTML;
                    announceBtnEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Click again to confirm';
                    announceBtnEl.style.filter = 'brightness(0.95)';
                    setTimeout(()=>{ delete announceBtnEl.dataset.confirm; announceBtnEl.innerHTML = original; announceBtnEl.style.filter = ''; }, 5000);
                    return;
                }
                // fetch latest counts to determine winner
                try {
                    const res = await fetch('../ajax/vote.php?room_id=' + encodeURIComponent(roomId));
                    const data = await res.json();
                    if (!data.success) { document.getElementById('announceError').textContent = 'Could not fetch votes'; return; }
                    const counts = data.counts || {};
                    // find top
                    let winnerPid = null; let winnerCount = 0;
                    for (const [k,v] of Object.entries(counts)) {
                        if (v > winnerCount) { winnerCount = v; winnerPid = k; }
                    }
                    if (!winnerPid) { document.getElementById('announceError').textContent = 'No votes yet'; return; }
                    // call announce endpoint
                    const post = await fetch('../ajax/announce_winner.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ room_id: roomId, participant_id: winnerPid }) });
                    const out = await post.json();
                    if (out.success) {
                        // find logo for winner
                        const winning = participants.find(p => String(p.participant_id) === String(out.winner_participant_id));
                        const logo = winning ? (winning.logo || '') : '';
                        showAnnouncement(out.winner_team, logo);
                        // disable announce button after success
                        announceBtnEl.disabled = true;
                        announceBtnEl.style.opacity = 0.6;
                        document.getElementById('announceError').textContent = '';
                    } else {
                        document.getElementById('announceError').textContent = 'Announce failed: ' + (out.error || 'unknown');
                    }
                } catch (e) { console.error(e); document.getElementById('announceError').textContent = 'Announce failed'; }
            });
        }

        // initial votes load
        fetchVotes(false);

    </script>
</body>
</html>

