<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '3307');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ipl_auction');

// Create connection
// Temporary test DB name. Set to empty string to use the original DB.
define('DB_NAME_TEMP_TEST', 'ipl_auction_new');

function getDBConnection() {
    $dbToUse = (defined('DB_NAME_TEMP_TEST') && DB_NAME_TEMP_TEST) ? DB_NAME_TEMP_TEST : DB_NAME;
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, $dbToUse, DB_PORT);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

// Close connection
function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}

// Function to execute query
function executeQuery($sql) {
    $conn = getDBConnection();
    $result = $conn->query($sql);
    closeDBConnection($conn);
    return $result;
}

// Function to get single row
function getSingleRow($sql) {
    $conn = getDBConnection();
    $result = $conn->query($sql);
    $row = $result ? $result->fetch_assoc() : null;
    closeDBConnection($conn);
    return $row;
}

// Function to get all rows
function getAllRows($sql) {
    $conn = getDBConnection();
    $result = $conn->query($sql);
    $rows = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    closeDBConnection($conn);
    return $rows;
}
?>
