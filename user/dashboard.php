<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - IPL Auction</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2.0">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .profile-info h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
        }
        .profile-info p {
            margin: 0;
            opacity: 0.9;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: rgba(15, 23, 42, 0.95);
            padding: 1.5rem;
            border-radius: 15px;
            color: white;
            text-align: center;
        }
        .stat-card h3 {
            margin: 0 0 1rem 0;
            font-size: 2.5rem;
            color: #60a5fa;
        }
        .stat-card p {
            margin: 0;
            opacity: 0.8;
        }
        .profile-section {
            background: rgba(15, 23, 42, 0.95);
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            color: white;
        }
        .profile-section h2 {
            margin-top: 0;
            color: #60a5fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .profile-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .detail-item {
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }
        .detail-item label {
            display: block;
            color: #94a3b8;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .detail-item .value {
            font-size: 1.1rem;
            color: white;
        }
        .empty-value {
            color: #64748b;
            font-style: italic;
        }
        .btn-edit {
            background: #60a5fa;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        .btn-edit:hover {
            background: #3b82f6;
        }
    </style>
</head>
<body>
    <?php 
    require_once '../config/session.php';
    require_once '../includes/user_functions.php';
    
    // Require login
    requireLogin();
    
    $current_user = getCurrentUser();
    $user = getUserById($current_user['user_id']);
    $stats = getUserStats($current_user['user_id']);
    
    // Get flash message
    $flash = getFlashMessage();
    ?>
    
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">🏏 IPL Auction</a>
            <ul class="nav-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="../pages/players.php">Players</a></li>
                <li><a href="../pages/teams.php">Teams</a></li>
                <li><a href="../pages/auction.php">Auction</a></li>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 2rem;">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="profile-avatar">
                <?php if ($user['profile_image']): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                <?php else: ?>
                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                <?php endif; ?>
            </div>
            <div class="profile-info" style="flex: 1;">
                <h1><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h1>
                <p>@<?php echo htmlspecialchars($user['username']); ?> • Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                <?php if ($user['city'] || $user['country']): ?>
                    <p>📍 <?php echo htmlspecialchars(($user['city'] ? $user['city'] . ', ' : '') . ($user['country'] ?: '')); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['teams_owned']; ?></h3>
                <p>Teams Owned</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $user['favorite_team'] ?: '—'; ?></h3>
                <p>Favorite Team</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['total_bids']; ?></h3>
                <p>Total Bids</p>
            </div>
            <div class="stat-card">
                <h3><?php echo date('M d, Y', strtotime($user['created_at'])); ?></h3>
                <p>Joined Date</p>
            </div>
        </div>

        <!-- Profile Details -->
        <div class="profile-section">
            <h2>
                Profile Information
                <a href="edit-profile.php" class="btn-edit">Edit Profile</a>
            </h2>
            <div class="profile-details">
                <div class="detail-item">
                    <label>Full Name</label>
                    <div class="value"><?php echo htmlspecialchars($user['full_name']) ?: '<span class="empty-value">Not set</span>'; ?></div>
                </div>
                <div class="detail-item">
                    <label>Email</label>
                    <div class="value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <div class="detail-item">
                    <label>Phone</label>
                    <div class="value"><?php echo htmlspecialchars($user['phone']) ?: '<span class="empty-value">Not set</span>'; ?></div>
                </div>
                <div class="detail-item">
                    <label>Date of Birth</label>
                    <div class="value"><?php echo $user['date_of_birth'] ? date('M d, Y', strtotime($user['date_of_birth'])) : '<span class="empty-value">Not set</span>'; ?></div>
                </div>
                <div class="detail-item">
                    <label>City</label>
                    <div class="value"><?php echo htmlspecialchars($user['city']) ?: '<span class="empty-value">Not set</span>'; ?></div>
                </div>
                <div class="detail-item">
                    <label>Country</label>
                    <div class="value"><?php echo htmlspecialchars($user['country']) ?: '<span class="empty-value">Not set</span>'; ?></div>
                </div>
            </div>
            
            <?php if ($user['bio']): ?>
                <div class="detail-item" style="margin-top: 1.5rem;">
                    <label>Bio</label>
                    <div class="value"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
