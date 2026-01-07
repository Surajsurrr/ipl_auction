<?php
/**
 * Database Initialization Script
 * This script creates all necessary tables for the IPL Auction application
 */

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ipl_auction';
$db_port = '3307';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>IPL Auction Database Initialization</h2>";
echo "<hr>";

// Read the schema file
$schema_file = __DIR__ . '/database/schema.sql';

if (!file_exists($schema_file)) {
    die("Schema file not found: " . $schema_file);
}

$schema_sql = file_get_contents($schema_file);

// Split the SQL file into individual statements
// This is a simple approach - may need refinement for complex SQL
$statements = array_filter(
    array_map('trim', explode(';', $schema_sql)),
    function($stmt) {
        return !empty($stmt) && substr($stmt, 0, 2) !== '--';
    }
);

$success_count = 0;
$error_count = 0;
$errors = [];

echo "<p>Running " . count($statements) . " SQL statements...</p>";
echo "<ul>";

foreach ($statements as $statement) {
    $statement = trim($statement);
    
    if (empty($statement)) {
        continue;
    }
    
    // Execute the statement
    if ($conn->query($statement) === TRUE) {
        $success_count++;
        echo "<li style='color: green;'>✓ Statement executed successfully</li>";
    } else {
        $error_count++;
        $error_msg = $conn->error;
        $errors[] = $error_msg;
        echo "<li style='color: red;'>✗ Error: " . htmlspecialchars($error_msg) . "</li>";
    }
}

echo "</ul>";
echo "<hr>";
echo "<h3>Summary</h3>";
echo "<p><strong>Successful statements:</strong> " . $success_count . "</p>";
echo "<p><strong>Failed statements:</strong> " . $error_count . "</p>";

if ($error_count > 0) {
    echo "<h3>Errors:</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: green;'><strong>✓ Database initialization completed successfully!</strong></p>";
    echo "<p><a href='admin/dashboard.php'>Go to Admin Dashboard</a></p>";
}

$conn->close();
?>
