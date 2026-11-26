<?php
/**
 * Database Configuration
 * Supports both local development (XAMPP default) and production environment variables (Vercel).
 */

// Parse DATABASE_URL or MYSQL_URL if provided (e.g. mysql://user:pass@host:port/dbname)
$databaseUrl = getenv('DATABASE_URL') ?: (getenv('MYSQL_URL') ?: ($_ENV['DATABASE_URL'] ?? ($_ENV['MYSQL_URL'] ?? '')));
$parsedHost = null;
$parsedPort = null;
$parsedUser = null;
$parsedPass = null;
$parsedName = null;

if (!empty($databaseUrl)) {
    $dbParts = parse_url($databaseUrl);
    if ($dbParts) {
        $parsedHost = $dbParts['host'] ?? null;
        $parsedPort = isset($dbParts['port']) ? (string)$dbParts['port'] : null;
        $parsedUser = $dbParts['user'] ?? null;
        $parsedPass = $dbParts['pass'] ?? null;
        $parsedName = isset($dbParts['path']) ? ltrim($dbParts['path'], '/') : null;
    }
}

// Resolve database configuration with fallbacks for local XAMPP
define('DB_HOST', getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? ($parsedHost ?: 'localhost')));
define('DB_PORT', (int)(getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? ($parsedPort ?: (getenv('DB_HOST') ? '3306' : '3307')))));
define('DB_USER', getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? ($parsedUser ?: 'root')));
define('DB_PASS', getenv('DB_PASS') ?: (getenv('DB_PASSWORD') ?: ($_ENV['DB_PASS'] ?? ($_ENV['DB_PASSWORD'] ?? ($parsedPass !== null ? $parsedPass : '')))));
define('DB_NAME', getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? ($parsedName ?: 'ipl_auction')));

// Optional temporary test database name for local tests (ignored if production env vars are present)
if (!getenv('DB_HOST') && !getenv('DATABASE_URL')) {
    define('DB_NAME_TEMP_TEST', 'ipl_auction_new');
} else {
    define('DB_NAME_TEMP_TEST', '');
}

/**
 * Get active MySQL connection
 */
function getDBConnection() {
    $dbToUse = (defined('DB_NAME_TEMP_TEST') && DB_NAME_TEMP_TEST) ? DB_NAME_TEMP_TEST : DB_NAME;
    
    // Silence raw errors and catch cleanly
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, $dbToUse, DB_PORT);

    if ($conn->connect_error) {
        error_log("Database connection error: " . $conn->connect_error);
        die("Database connection failed. Please check your database settings or environment variables.");
    }

    $conn->set_charset("utf8mb4");
    return $conn;
}

/**
 * Close database connection
 */
function closeDBConnection($conn) {
    if ($conn && $conn instanceof mysqli) {
        @$conn->close();
    }
}

/**
 * Execute query
 */
function executeQuery($sql) {
    $conn = getDBConnection();
    $result = $conn->query($sql);
    closeDBConnection($conn);
    return $result;
}

/**
 * Fetch a single row as associative array
 */
function getSingleRow($sql) {
    $conn = getDBConnection();
    $result = $conn->query($sql);
    $row = $result ? $result->fetch_assoc() : null;
    closeDBConnection($conn);
    return $row;
}

/**
 * Fetch all rows as an array of associative arrays
 */
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
