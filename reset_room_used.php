<?php
require_once __DIR__ . '/config/database.php';

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 1;
$conn = getDBConnection();

function getCounts($conn, $room_id) {
    $out = [];
    $r = $conn->query("SELECT COUNT(*) AS total_players FROM players");
    $out['total_players'] = $r ? intval($r->fetch_assoc()['total_players']) : 0;
    $r = $conn->query("SELECT COUNT(*) AS used FROM room_used_players WHERE room_id = $room_id");
    $out['used'] = $r ? intval($r->fetch_assoc()['used']) : 0;
    $r = $conn->query("SELECT COUNT(*) AS assigned FROM room_player_assignments WHERE room_id = $room_id");
    $out['assigned'] = $r ? intval($r->fetch_assoc()['assigned']) : 0;
    return $out;
}

$counts = getCounts($conn, $room_id);
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    // Perform deletion (non-reversible) -- for testing only
    $del_sql = "DELETE FROM room_used_players WHERE room_id = $room_id";
    if ($conn->query($del_sql)) {
        $message = "Deleted room_used_players entries for room_id=$room_id";
    } else {
        $message = "Error deleting entries: " . $conn->error;
    }
    $counts = getCounts($conn, $room_id);
}

closeDBConnection($conn);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Reset room_used_players - room <?php echo htmlspecialchars($room_id); ?></title>
</head>
<body style="font-family:Segoe UI,Arial,sans-serif; padding:20px;">
<h2>Reset room_used_players (room <?php echo htmlspecialchars($room_id); ?>)</h2>
<p>Total players: <strong><?php echo $counts['total_players']; ?></strong></p>
<p>room_used_players (this room): <strong><?php echo $counts['used']; ?></strong></p>
<p>room_player_assignments (this room): <strong><?php echo $counts['assigned']; ?></strong></p>
<?php if ($message): ?>
    <p style="color:green;"><strong><?php echo htmlspecialchars($message); ?></strong></p>
<?php endif; ?>

<?php if ($counts['used'] > 0): ?>
    <form method="POST">
        <p style="color:#b33;">Warning: this will permanently delete the used-player records for this room. This is safe for testing but not reversible.</p>
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" style="padding:8px 12px; background:#c62828; color:white; border:none; border-radius:6px;">Confirm: Clear used players for room <?php echo htmlspecialchars($room_id); ?></button>
    </form>
<?php else: ?>
    <p>No used players to clear.</p>
<?php endif; ?>

<p style="margin-top:20px;">To reset a different room, add <code>?room_id=ID</code> to the URL.</p>
</body>
</html>