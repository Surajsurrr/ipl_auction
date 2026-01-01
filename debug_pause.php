<?php
require_once 'config/database.php';

$conn = getDBConnection();

// Check if paused_time_remaining column exists
$result = $conn->query("SHOW COLUMNS FROM auction_rooms LIKE 'paused_time_remaining'");
if ($result->num_rows == 0) {
    echo "ERROR: Column 'paused_time_remaining' does not exist!<br>";
    echo "Adding column...<br>";
    $conn->query("ALTER TABLE auction_rooms ADD COLUMN paused_time_remaining INT DEFAULT 0");
    echo "Column added.<br>";
} else {
    echo "Column 'paused_time_remaining' exists.<br>";
}

// Check current room status
$result = $conn->query("SELECT room_id, status, paused_time_remaining, bid_timer_expires_at FROM auction_rooms WHERE room_id = 1");
if ($row = $result->fetch_assoc()) {
    echo "<pre>";
    print_r($row);
    echo "</pre>";
}

// Test pause update
echo "<br>Testing pause update...<br>";
$remaining = 30;
$update_sql = "UPDATE auction_rooms 
               SET status = 'paused',
                   paused_time_remaining = $remaining
               WHERE room_id = 1";
$success = $conn->query($update_sql);
echo "Update result: " . ($success ? "SUCCESS" : "FAILED - " . $conn->error) . "<br>";
echo "Rows affected: " . $conn->affected_rows . "<br>";

// Check status after update
$result = $conn->query("SELECT room_id, status, paused_time_remaining FROM auction_rooms WHERE room_id = 1");
if ($row = $result->fetch_assoc()) {
    echo "<br>After update:<br>";
    echo "<pre>";
    print_r($row);
    echo "</pre>";
}

closeDBConnection($conn);
