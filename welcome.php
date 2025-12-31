<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to IPL Auction System</title>
    <link rel="stylesheet" href="assets/css/style.css?v=2.0">
    <style>
        .welcome-container {
            max-width: 1000px;
            margin: 4rem auto;
            padding: 2rem;
        }
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }
        .dashboard-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 25px;
            padding: 3rem;
            box-shadow: var(--shadow-xl);
            transition: all 0.3s ease;
            text-align: center;
            border: 3px solid transparent;
        }
        .dashboard-card:hover {
            transform: translateY(-10px);
            border-color: rgba(255, 70, 85, 0.3);
        }
        .dashboard-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .dashboard-title {
            background: var(--gradient-ipl);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        .dashboard-desc {
            color: #64748b;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <div style="text-align: center; margin-bottom: 3rem;">
            <h1 style="font-size: 3.5rem; background: var(--gradient-ipl); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 1rem;">
                🏏 IPL Auction 2026
            </h1>
            <p style="font-size: 1.3rem; color: #64748b;">
                Complete Auction Management System
            </p>
        </div>

        <div class="dashboard-cards">
            <!-- Player Dashboard -->
            <div class="dashboard-card">
                <div class="dashboard-icon">🎯</div>
                <h2 class="dashboard-title">Player Dashboard</h2>
                <p class="dashboard-desc">
                    Public auction platform for viewing players, teams, and live auction
                </p>
                <a href="index.php" class="btn btn-primary" style="width: 100%; padding: 1.2rem;">
                    View Player Dashboard
                </a>
                <div style="margin-top: 1.5rem; padding: 1rem; background: #f8fafc; border-radius: 12px;">
                    <p style="margin: 0; font-size: 0.9rem; color: #64748b;">
                        <strong>Features:</strong><br>
                        ✓ Browse 628 Players<br>
                        ✓ View Team Rosters<br>
                        ✓ Live Auction Updates<br>
                        ✓ Player Statistics
                    </p>
                </div>
            </div>

            <!-- Admin Dashboard -->
            <div class="dashboard-card" style="border: 3px solid rgba(251, 191, 36, 0.3);">
                <div class="dashboard-icon">⚙️</div>
                <h2 class="dashboard-title" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);">
                    Admin Dashboard
                </h2>
                <p class="dashboard-desc">
                    Complete control panel for managing players, teams, and auction settings
                </p>
                <a href="admin/login.php" class="btn btn-warning" style="width: 100%; padding: 1.2rem; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #0f172a;">
                    Access Admin Panel
                </a>
                <div style="margin-top: 1.5rem; padding: 1rem; background: #fef3c7; border-radius: 12px;">
                    <p style="margin: 0; font-size: 0.9rem; color: #78350f;">
                        <strong>Admin Features:</strong><br>
                        🔐 Secure Login Required<br>
                        ✏️ Edit Player Details<br>
                        ➕ Add New Players<br>
                        🗑️ Delete Players<br>
                        📊 Full Management Access
                    </p>
                </div>
            </div>
        </div>

        <div style="margin-top: 4rem; text-align: center; padding: 2rem; background: rgba(255, 255, 255, 0.98); border-radius: 20px; box-shadow: var(--shadow-lg);">
            <h3 style="color: #0f172a; margin-bottom: 1rem;">Quick Stats</h3>
            <div class="grid grid-4" style="margin-top: 1.5rem;">
                <div>
                    <div style="font-size: 2rem; font-weight: 800; color: #ff4655;">628</div>
                    <div style="color: #64748b;">Total Players</div>
                </div>
                <div>
                    <div style="font-size: 2rem; font-weight: 800; color: #3b82f6;">3</div>
                    <div style="color: #64748b;">Auction Groups</div>
                </div>
                <div>
                    <div style="font-size: 2rem; font-weight: 800; color: #10b981;">8</div>
                    <div style="color: #64748b;">IPL Teams</div>
                </div>
                <div>
                    <div style="font-size: 2rem; font-weight: 800; color: #fbbf24;">120 Cr</div>
                    <div style="color: #64748b;">Team Budget</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
