<?php
require_once 'config/database.php';

// Clean up all demo auction data
$conn = getDBConnection();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Cleanup Demo Data</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f0f0f0; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: #10b981; font-weight: bold; padding: 10px; background: #d1fae5; border-radius: 5px; margin: 10px 0; }
        .error { color: #ef4444; font-weight: bold; padding: 10px; background: #fee2e2; border-radius: 5px; margin: 10px 0; }
        .info { color: #3b82f6; padding: 10px; background: #dbeafe; border-radius: 5px; margin: 10px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; margin-top: 20px; }
        .btn:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🧹 Cleanup Demo Data</h1>
        <p>This script will remove all demo auction rooms and reset team data.</p>
";

// Start transaction
$conn->begin_transaction();

try {
    // 1. Get count of auctions to delete
    $result = $conn->query("SELECT COUNT(*) as count FROM auction_rooms");
    $count = $result->fetch_assoc()['count'];
    echo "<div class='info'>Found $count auction room(s) to delete</div>";
    
    // 2. Delete all participants
    $result = $conn->query("DELETE FROM participants");
    echo "<div class='success'>✓ Deleted all participants</div>";
    
    // 3. Reset all players (mark as unsold and remove participant assignments)
    $result = $conn->query("UPDATE players SET is_sold = 0, participant_id = NULL, sold_price = NULL");
    $affected = $conn->affected_rows;
    echo "<div class='success'>✓ Reset $affected players to unsold status</div>";
    
    // 4. Delete all auction rooms
    $result = $conn->query("DELETE FROM auction_rooms");
    echo "<div class='success'>✓ Deleted all auction rooms</div>";
    
    // 5. Reset team championship counts
    $result = $conn->query("UPDATE teams SET championships = 0 WHERE championships > 0");
    $affected = $conn->affected_rows;
    echo "<div class='success'>✓ Reset championship count for $affected team(s)</div>";
    
    // 6. Clean up announcement files
    $annDir = __DIR__ . '/data/announcements';
    if (is_dir($annDir)) {
        $files = glob($annDir . '/*.json');
        $fileCount = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $fileCount++;
            }
        }
        echo "<div class='success'>✓ Deleted $fileCount announcement file(s)</div>";
    }
    
    // 7. Clean up chat files
    $chatDir = __DIR__ . '/data/chat';
    if (is_dir($chatDir)) {
        $files = glob($chatDir . '/*.json');
        $fileCount = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $fileCount++;
            }
        }
        echo "<div class='success'>✓ Deleted $fileCount chat file(s)</div>";
    }
    
    // Commit transaction
    $conn->commit();
    
    echo "<div class='success' style='font-size: 1.2em; margin-top: 30px;'>
        ✅ All demo data has been successfully cleaned up!<br>
        The system is now ready for real auctions.
    </div>";
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo "<div class='error'>❌ Error during cleanup: " . $e->getMessage() . "</div>";
    echo "<div class='error'>All changes have been rolled back.</div>";
}

closeDBConnection($conn);

echo "
        <a href='index.php' class='btn'>← Back to Home</a>
        <a href='pages/my-auctions.php' class='btn' style='background: #10b981;'>View My Auctions</a>
    </div>
</body>
</html>";
?>
