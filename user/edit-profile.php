<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - IPL Auction</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2.0">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .profile-card {
            background: rgba(15, 23, 42, 0.95);
            padding: 2rem;
            border-radius: 15px;
            color: white;
        }
        .profile-card h2 {
            color: #60a5fa;
            margin-top: 0;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            color: #94a3b8;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #60a5fa;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        .btn-primary {
            flex: 1;
            padding: 1rem;
            background: #60a5fa;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-primary:hover {
            background: #3b82f6;
        }
        .btn-secondary {
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .section-divider {
            border: none;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin: 2rem 0;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
            border: 1px solid #4ade80;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid #f87171;
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
    
    $message = '';
    $message_type = '';
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['action']) && $_POST['action'] == 'update_profile') {
            $data = [
                'full_name' => $_POST['full_name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'bio' => $_POST['bio'] ?? '',
                'favorite_team' => $_POST['favorite_team'] ?? '',
                'city' => $_POST['city'] ?? '',
                'country' => $_POST['country'] ?? '',
                'date_of_birth' => $_POST['date_of_birth'] ?? ''
            ];
            
            $result = updateUserProfile($current_user['user_id'], $data);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            
            // Refresh user data
            $user = getUserById($current_user['user_id']);
        } elseif (isset($_POST['action']) && $_POST['action'] == 'change_password') {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if ($new_password !== $confirm_password) {
                $message = 'New passwords do not match';
                $message_type = 'error';
            } else {
                $result = changePassword($current_user['user_id'], $current_password, $new_password);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
            }
        }
    }
    ?>
    
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">🏏 IPL Auction</a>
            <ul class="nav-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="../pages/players.php">Players</a></li>
                <li><a href="../pages/teams.php">Teams</a></li>
                <li><a href="../pages/my-auctions.php">My Auctions</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="profile-container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Profile Information Form -->
        <div class="profile-card">
            <h2>Edit Profile Information</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="+91 1234567890">
                    </div>
                    
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($user['date_of_birth']); ?>">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city']); ?>" placeholder="Mumbai">
                    </div>
                    
                    <div class="form-group">
                        <label for="country">Country</label>
                        <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($user['country']); ?>" placeholder="India">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="favorite_team">Favorite IPL Team</label>
                    <select id="favorite_team" name="favorite_team">
                        <option value="">Select a team</option>
                        <option value="Chennai Super Kings" <?php echo $user['favorite_team'] == 'Chennai Super Kings' ? 'selected' : ''; ?>>Chennai Super Kings</option>
                        <option value="Delhi Capitals" <?php echo $user['favorite_team'] == 'Delhi Capitals' ? 'selected' : ''; ?>>Delhi Capitals</option>
                        <option value="Gujarat Titans" <?php echo $user['favorite_team'] == 'Gujarat Titans' ? 'selected' : ''; ?>>Gujarat Titans</option>
                        <option value="Kolkata Knight Riders" <?php echo $user['favorite_team'] == 'Kolkata Knight Riders' ? 'selected' : ''; ?>>Kolkata Knight Riders</option>
                        <option value="Lucknow Super Giants" <?php echo $user['favorite_team'] == 'Lucknow Super Giants' ? 'selected' : ''; ?>>Lucknow Super Giants</option>
                        <option value="Mumbai Indians" <?php echo $user['favorite_team'] == 'Mumbai Indians' ? 'selected' : ''; ?>>Mumbai Indians</option>
                        <option value="Punjab Kings" <?php echo $user['favorite_team'] == 'Punjab Kings' ? 'selected' : ''; ?>>Punjab Kings</option>
                        <option value="Rajasthan Royals" <?php echo $user['favorite_team'] == 'Rajasthan Royals' ? 'selected' : ''; ?>>Rajasthan Royals</option>
                        <option value="Royal Challengers Bangalore" <?php echo $user['favorite_team'] == 'Royal Challengers Bangalore' ? 'selected' : ''; ?>>Royal Challengers Bangalore</option>
                        <option value="Sunrisers Hyderabad" <?php echo $user['favorite_team'] == 'Sunrisers Hyderabad' ? 'selected' : ''; ?>>Sunrisers Hyderabad</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio']); ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Save Changes</button>
                    <a href="dashboard.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Change Password Section -->
        <div class="profile-card" style="margin-top: 2rem;">
            <h2>Change Password</h2>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label for="current_password">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="new_password">New Password *</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
