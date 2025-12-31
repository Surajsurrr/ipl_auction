<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - IPL Auction</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2.0">
</head>
<body>
    <?php 
    require_once '../config/database.php';
    require_once '../config/session.php';
    
    $error = '';
    $success = '';
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $full_name = $_POST['full_name'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if ($username && $email && $password && $confirm_password) {
            if ($password !== $confirm_password) {
                $error = 'Passwords do not match';
            } else {
                $conn = getDBConnection();
                $username = $conn->real_escape_string($username);
                $email = $conn->real_escape_string($email);
                $full_name = $conn->real_escape_string($full_name);
                
                // Check if username or email exists
                $sql = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
                $result = $conn->query($sql);
                
                if ($result->num_rows > 0) {
                    $error = 'Username or email already exists';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $sql = "INSERT INTO users (username, email, full_name, password) 
                            VALUES ('$username', '$email', '$full_name', '$hashed_password')";
                    
                    if ($conn->query($sql)) {
                        $success = 'Registration successful! You can now login.';
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                }
                
                closeDBConnection($conn);
            }
        } else {
            $error = 'Please fill in all required fields';
        }
    }
    ?>
    
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">🏏 IPL Auction</a>
            <ul class="nav-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card" style="max-width: 500px; margin: 3rem auto;">
            <div class="card-header">
                <h2>Register</h2>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control">
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Register</button>
            </form>

            <p style="text-align: center; margin-top: 1.5rem; color: #666;">
                Already have an account? <a href="login.php" style="color: #667eea;">Login here</a>
            </p>
        </div>
    </div>
</body>
</html>
