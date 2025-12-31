<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';
require_once '../includes/player_functions.php';

// Handle search and filters
$search = $_GET['search'] ?? '';
$group_filter = $_GET['group'] ?? '';
$type_filter = $_GET['type'] ?? '';

$conn = getDBConnection();

// Build query
$sql = "SELECT * FROM players WHERE 1=1";

if (!empty($search)) {
    $search_clean = $conn->real_escape_string($search);
    $sql .= " AND player_name LIKE '%$search_clean%'";
}

if (!empty($group_filter)) {
    $group_clean = $conn->real_escape_string($group_filter);
    $sql .= " AND auction_group = '$group_clean'";
}

if (!empty($type_filter)) {
    $type_clean = $conn->real_escape_string($type_filter);
    $sql .= " AND player_type = '$type_clean'";
}

$sql .= " ORDER BY auction_group, player_name";

$result = $conn->query($sql);
$players = [];
while ($row = $result->fetch_assoc()) {
    $players[] = $row;
}

// Get statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM players")->fetch_assoc()['count'],
    'group_a' => $conn->query("SELECT COUNT(*) as count FROM players WHERE auction_group = 'A'")->fetch_assoc()['count'],
    'group_b' => $conn->query("SELECT COUNT(*) as count FROM players WHERE auction_group = 'B'")->fetch_assoc()['count'],
    'group_c' => $conn->query("SELECT COUNT(*) as count FROM players WHERE auction_group = 'C'")->fetch_assoc()['count'],
];

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - IPL Auction</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2.0">
    <style>
        .admin-header {
            background: rgba(15, 23, 42, 0.95);
            color: white;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            border-radius: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .table-wrapper {
            overflow-x: auto;
        }
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">🏏 IPL Admin</a>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="add_player.php">Add Player</a></li>
                <li><a href="../index.php">View Site</a></li>
                <li><a href="logout.php" class="nav-button">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="admin-header">
            <div>
                <h1>Player Management Dashboard</h1>
                <p style="opacity: 0.8;">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</p>
            </div>
            <a href="add_player.php" class="btn btn-success">+ Add New Player</a>
        </div>

        <?php if (isset($_SESSION['delete_success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['delete_success']); unset($_SESSION['delete_success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['delete_error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['delete_error']); unset($_SESSION['delete_error']); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="grid grid-4" style="margin-bottom: 2rem;">
            <div class="team-card">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Players</p>
            </div>
            <div class="team-card">
                <h3><?php echo $stats['group_a']; ?></h3>
                <p>Group A (≥₹200L)</p>
            </div>
            <div class="team-card">
                <h3><?php echo $stats['group_b']; ?></h3>
                <p>Group B (₹100-200L)</p>
            </div>
            <div class="team-card">
                <h3><?php echo $stats['group_c']; ?></h3>
                <p>Group C (<₹100L)</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>All Players</h2>
            </div>

            <!-- Search and Filters -->
            <form method="GET" style="margin-bottom: 2rem;">
                <div class="grid grid-4">
                    <div class="form-group">
                        <label>Search Player</label>
                        <input type="text" name="search" class="form-control" placeholder="Player name..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label>Group</label>
                        <select name="group" class="form-control">
                            <option value="">All Groups</option>
                            <option value="A" <?php echo $group_filter == 'A' ? 'selected' : ''; ?>>Group A</option>
                            <option value="B" <?php echo $group_filter == 'B' ? 'selected' : ''; ?>>Group B</option>
                            <option value="C" <?php echo $group_filter == 'C' ? 'selected' : ''; ?>>Group C</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Player Type</label>
                        <select name="type" class="form-control">
                            <option value="">All Types</option>
                            <option value="Indian" <?php echo $type_filter == 'Indian' ? 'selected' : ''; ?>>Indian</option>
                            <option value="Indian Uncapped" <?php echo $type_filter == 'Indian Uncapped' ? 'selected' : ''; ?>>Indian Uncapped</option>
                            <option value="Overseas" <?php echo $type_filter == 'Overseas' ? 'selected' : ''; ?>>Overseas</option>
                            <option value="Overseas Uncapped" <?php echo $type_filter == 'Overseas Uncapped' ? 'selected' : ''; ?>>Overseas Uncapped</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Filter</button>
                    </div>
                </div>
            </form>

            <!-- Players Table -->
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Player Name</th>
                            <th>Type</th>
                            <th>Role</th>
                            <th>Nationality</th>
                            <th>Age</th>
                            <th>Base Price</th>
                            <th>Group</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($players)): ?>
                            <tr>
                                <td colspan="10" style="text-align: center; padding: 2rem; color: #999;">
                                    No players found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($players as $player): ?>
                                <tr>
                                    <td><?php echo $player['player_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($player['player_name']); ?></strong></td>
                                    <td><?php echo $player['player_type']; ?></td>
                                    <td><?php echo $player['player_role']; ?></td>
                                    <td><?php echo $player['nationality']; ?></td>
                                    <td><?php echo $player['age']; ?></td>
                                    <td><?php echo formatCurrency($player['base_price']); ?></td>
                                    <td><span class="badge badge-info">Group <?php echo $player['auction_group']; ?></span></td>
                                    <td>
                                        <?php if ($player['is_sold']): ?>
                                            <span class="badge badge-success">SOLD</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">AVAILABLE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_player.php?id=<?php echo $player['player_id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                            <a href="delete_player.php?id=<?php echo $player['player_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($player['player_name']); ?>?')">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="text-align: center; margin-top: 2rem; color: #64748b;">
                Showing <?php echo count($players); ?> of <?php echo $stats['total']; ?> players
            </div>
        </div>
    </div>
</body>
</html>
