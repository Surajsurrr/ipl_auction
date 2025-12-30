<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ipl_auction');

// Create connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
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
