<?php
/**
 * Database Configuration
 * Supports both local development (XAMPP default) and production environment variables (Vercel).
 * Optimized with connection pooling and request-level connection reuse for high performance.
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
 * Get active MySQL connection with connection pooling & reuse
 */
function getDBConnection() {
    static $cachedConn = null;

    // Reuse open, healthy connection across multiple queries in the same request
    if ($cachedConn instanceof mysqli && @$cachedConn->ping()) {
        return $cachedConn;
    }

    $dbToUse = (defined('DB_NAME_TEMP_TEST') && DB_NAME_TEMP_TEST) ? DB_NAME_TEMP_TEST : DB_NAME;
    
    $conn = mysqli_init();
    if (!$conn) {
        error_log("Database initialization error");
        die("Database connection failed. Please check your database settings or environment variables.");
    }

    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 6);

    // If connecting to a remote cloud host (like TiDB Cloud), enable SSL/TLS and persistent pooling
    $isRemote = (DB_HOST !== 'localhost' && DB_HOST !== '127.0.0.1' && DB_HOST !== '::1');
    $hostToUse = $isRemote ? ('p:' . DB_HOST) : DB_HOST;

    if ($isRemote) {
        if (file_exists('/etc/ssl/certs/ca-certificates.crt')) {
            $conn->ssl_set(NULL, NULL, '/etc/ssl/certs/ca-certificates.crt', NULL, NULL);
        }
        $connected = @$conn->real_connect($hostToUse, DB_USER, DB_PASS, $dbToUse, DB_PORT, NULL, MYSQLI_CLIENT_SSL);
        
        // Fallback to non-persistent connection if p: is unsupported on some drivers
        if (!$connected) {
            $conn = mysqli_init();
            $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 6);
            if (file_exists('/etc/ssl/certs/ca-certificates.crt')) {
                $conn->ssl_set(NULL, NULL, '/etc/ssl/certs/ca-certificates.crt', NULL, NULL);
            }
            $connected = @$conn->real_connect(DB_HOST, DB_USER, DB_PASS, $dbToUse, DB_PORT, NULL, MYSQLI_CLIENT_SSL);
        }
    } else {
        $connected = @$conn->real_connect(DB_HOST, DB_USER, DB_PASS, $dbToUse, DB_PORT);
    }

    if (!$connected || $conn->connect_error) {
        $errorMsg = $conn->connect_error ?: mysqli_connect_error();
        error_log("Database connection error: " . $errorMsg);
        die("Database connection failed: " . htmlspecialchars($errorMsg));
    }

    $conn->set_charset("utf8mb4");
    $cachedConn = $conn;
    return $conn;
}

/**
 * Close database connection
 * Note: Keeps connection cached for other queries in the same request to avoid repeated TLS handshakes.
 */
function closeDBConnection($conn) {
    // Intentionally kept alive for request-level reuse. PHP closes automatically on script termination.
}

/**
 * Execute query
 */
function executeQuery($sql) {
    $conn = getDBConnection();
    $result = $conn->query($sql);
    return $result;
}

/**
 * Fetch a single row as associative array
 */
function getSingleRow($sql) {
    $conn = getDBConnection();
    $result = $conn->query($sql);
    $row = $result ? $result->fetch_assoc() : null;
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
    return $rows;
}
?>
