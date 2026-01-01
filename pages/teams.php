<?php 
require_once '../config/session.php';
require_once '../includes/team_functions.php';
require_once '../includes/player_functions.php';

$teams = getAllTeams();
$selected_team_id = isset($_GET['team_id']) ? $_GET['team_id'] : null;
$team_players = [];
$team_stats = null;

if ($selected_team_id) {
    $team_players = getTeamPlayers($selected_team_id);
    $team_stats = getTeamStatistics($selected_team_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams - IPL Auction</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2.0">
    <style>
        .teams-hero {
            text-align: center;
            padding: 3rem 1rem 2rem;
            color: white;
        }
        
        .teams-hero h1 {
            font-size: 3.5rem;
            margin: 0 0 0.5rem 0;
            font-weight: 800;
            text-shadow: 3px 3px 6px rgba(0,0,0,0.3);
            animation: fadeInDown 0.8s ease;
        }
        
        .teams-hero p {
            font-size: 1.3rem;
            opacity: 0.95;
            margin: 0;
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .teams-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            padding: 2rem 0;
        }
        
        .team-showcase-card {
            background: white;
            border-radius: 25px;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 15px 40px rgba(0,0,0,0.25);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            animation: scaleIn 0.6s ease;
        }
        
        .team-showcase-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .team-showcase-card:hover {
            transform: translateY(-15px) scale(1.03);
            box-shadow: 0 25px 50px rgba(0,0,0,0.35);
        }
        
        .team-icon-large {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 4rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5);
            transition: all 0.3s;
        }
        
        .team-icon-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5);
            transition: all 0.3s;
            background: white;
            overflow: hidden;
            padding: 15px;
        }
        
        .team-icon-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
        }
        
        .team-showcase-card:hover .team-icon-large,
        .team-showcase-card:hover .team-icon-image {
            transform: rotate(360deg) scale(1.1);
        }
        
        .team-name-showcase {
            font-size: 1.8rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 1rem 0;
            letter-spacing: -0.5px;
        }
        
        .trophy-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid #f3f4f6;
        }
        
        .trophy-display {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1rem;
            color: #6b7280;
            margin-bottom: 0.75rem;
        }
        
        .trophy-count {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0.5rem 0;
        }
        
        .trophy-icons {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .trophy-icon {
            font-size: 2rem;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .no-trophy-text {
            color: #9ca3af;
            font-size: 0.875rem;
            font-style: italic;
            margin-top: 0.5rem;
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
                <li><a href="players.php">Players</a></li>
                <li><a href="teams.php">Teams</a></li>
                <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                    <li><a href="../admin/dashboard.php" class="nav-button" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);">⚙️ Admin Panel</a></li>
                <?php elseif (isLoggedIn()): ?>
                    <li><a href="my-auctions.php">My Auctions</a></li>
                    <li><a href="../user/dashboard.php">Dashboard</a></li>
                    <li><a href="../auth/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="../auth/login.php" class="nav-button">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="teams-hero">
        <h1>🏆 IPL Teams</h1>
    </div>

    <div class="container">
        <div class="teams-grid">
            <?php 
            $team_icons = [
                'Mumbai' => 'logo',
                'Chennai' => 'logo', 
                'Rajasthan' => 'logo',
                'Royal' => 'logo',
                'Kolkata' => 'logo',
                'Delhi' => 'logo',
                'Punjab' => 'logo',
                'Sunrisers' => 'logo',
                'Gujarat' => 'logo',
                'Lucknow' => 'logo'
            ];
            
            $team_logos = [
                'Chennai' => '../assets/images/teams/csk.png',
                'Delhi' => '../assets/images/teams/dc.png',
                'Mumbai' => '../assets/images/teams/mi.png',
                'Kolkata' => '../assets/images/teams/kkr.png',
                'Gujarat' => '../assets/images/teams/gt.png',
                'Rajasthan' => '../assets/images/teams/rr.png',
                'Royal' => '../assets/images/teams/rcb.png',
                'Sunrisers' => '../assets/images/teams/srh.png',
                'Lucknow' => '../assets/images/teams/lsg.png',
                'Punjab' => '../assets/images/teams/pbks.png'
            ];
            
            $team_colors = [
                'Chennai' => '#FDB913',
                'Delhi' => '#0078BC',
                'Mumbai' => '#004BA0',
                'Kolkata' => '#3A225D',
                'Gujarat' => '#1C2833',
                'Rajasthan' => '#EA1A85',
                'Royal' => '#D71920',
                'Sunrisers' => '#FF822A',
                'Lucknow' => '#1C4587',
                'Punjab' => '#ED1B24'
            ];
            
            $index = 0;
            foreach ($teams as $team): 
                $icon = '🏏';
                $has_logo = false;
                $logo_path = '';
                $card_color = '#FFFFFF';
                
                foreach ($team_icons as $key => $emoji) {
                    if (stripos($team['team_name'], $key) !== false) {
                        if ($emoji === 'logo' && isset($team_logos[$key])) {
                            $has_logo = true;
                            $logo_path = $team_logos[$key];
                        } else {
                            $icon = $emoji;
                        }
                        if (isset($team_colors[$key])) {
                            $card_color = $team_colors[$key];
                        }
                        break;
                    }
                }
                
                $championships = isset($team['championships']) ? intval($team['championships']) : 0;
                $index++;
            ?>
                <div class="team-showcase-card" style="animation-delay: <?php echo $index * 0.1; ?>s; background: <?php echo $card_color; ?>;">
                    <?php if ($has_logo): ?>
                        <div class="team-icon-image">
                            <img src="<?php echo $logo_path; ?>?v=2.0" alt="<?php echo htmlspecialchars($team['team_name']); ?>" />
                        </div>
                    <?php else: ?>
                        <div class="team-icon-large"><?php echo $icon; ?></div>
                    <?php endif; ?>
                    <h3 class="team-name-showcase"><?php echo htmlspecialchars($team['team_name']); ?></h3>
                    
                    <?php if ($championships > 0): ?>
                        <div class="trophy-section">
                            <div class="trophy-display">
                                <span style="font-size: 1.5rem;">🏆</span>
                                <span style="font-weight: 600; color: #1f2937;">Championships</span>
                            </div>
                            <div class="trophy-count"><?php echo $championships; ?></div>
                            <div class="trophy-icons">
                                <?php for ($i = 0; $i < min($championships, 5); $i++): ?>
                                    <span class="trophy-icon" style="animation-delay: <?php echo $i * 0.2; ?>s;">🏆</span>
                                <?php endfor; ?>
                                <?php if ($championships > 5): ?>
                                    <span style="color: #6b7280; font-weight: 600; font-size: 1.2rem;">+<?php echo $championships - 5; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
