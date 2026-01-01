<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IPL Auction</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2.0">
</head>
<body>
    <?php 
    require_once '../config/database.php';
    require_once '../config/session.php';
    
    $error = '';
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if ($username && $password) {
            // Check if admin credentials
            if ($username === 'admin' && $password === 'admin123') {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = 'admin';
                header('Location: ../admin/dashboard.php');
                exit();
            }
            
            // Check regular user credentials
            $conn = getDBConnection();
            $username_clean = $conn->real_escape_string($username);
            
            $sql = "SELECT * FROM users WHERE username = '$username_clean' OR email = '$username_clean'";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // For demo purposes, accept any password or verify with password_verify
                if (password_verify($password, $user['password']) || $password == 'player123') {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    
                    closeDBConnection($conn);
                    header('Location: ../user/dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid password';
                }
            } else {
                $error = 'Invalid username or password';
            }
            
            closeDBConnection($conn);
        } else {
            $error = 'Please fill in all fields';
        }
    }
    ?>
    
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">🏏 IPL Auction</a>
            <ul class="nav-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card" style="max-width: 500px; margin: 3rem auto;">
            <div class="card-header">
                <h2>Login</h2>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
            </form>

            <p style="text-align: center; margin-top: 1.5rem; color: #666;">
                Don't have an account? <a href="register.php" style="color: #667eea;">Register here</a>
            </p>

            <div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                <p style="margin: 0 0 1rem 0; font-size: 0.9rem; color: #666;">
                    <strong>🔐 Admin Login:</strong><br>
                    Username: admin<br>
                    Password: admin123
                </p>
                <p style="margin: 0; font-size: 0.9rem; color: #666;">
                    <strong>👤 User Login:</strong><br>
                    Register a new account or use existing credentials
                </p>
            </div>
        </div>
    </div>
</body>
</html>
