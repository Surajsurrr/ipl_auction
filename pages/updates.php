<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPL Updates - IPL Auction</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2.0">
</head>
<body>
    <?php 
    require_once '../config/session.php';
    require_once '../includes/update_functions.php';
    
    $updates = getAllUpdates();
    ?>
    
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">🏏 IPL Auction</a>
            <ul class="nav-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="players.php">Players</a></li>
                <li><a href="teams.php">Teams</a></li>
                <li><a href="auction.php">Auction</a></li>
                <li><a href="updates.php">Updates</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="../auth/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="../auth/login.php" class="nav-button">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>IPL Updates & News</h2>
            </div>

            <?php if (!empty($updates)): ?>
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <?php foreach ($updates as $update): ?>
                        <div class="player-card">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <div>
                                    <h3 style="color: #667eea; margin-bottom: 0.5rem;">
                                        <?php echo htmlspecialchars($update['title']); ?>
                                    </h3>
                                    <span class="badge badge-info"><?php echo $update['category']; ?></span>
                                    <?php if ($update['is_featured']): ?>
                                        <span class="badge badge-warning" style="margin-left: 0.5rem;">Featured</span>
                                    <?php endif; ?>
                                </div>
                                <span style="color: #999; font-size: 0.9rem;">
                                    <?php echo date('M d, Y', strtotime($update['created_at'])); ?>
                                </span>
                            </div>
                            <p style="color: #666; line-height: 1.8;">
                                <?php echo nl2br(htmlspecialchars($update['content'])); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: #999;">
                    <h3>No updates available</h3>
                    <p>Check back later for the latest IPL news and announcements</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
