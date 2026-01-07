<?php
require_once __DIR__ . '/config/database.php';

$conn = getDBConnection();
if (!$conn) {
    die('Could not connect to DB');
}

$db = 'ipl_auction';

$sql = "SHOW TABLES FROM `$db`";
$res = $conn->query($sql);
if (!$res) {
    die('Query failed: ' . $conn->error);
}

echo "<h2>Tables in `$db`</h2>";
echo "<ul>";
while ($row = $res->fetch_array(MYSQLI_NUM)) {
    echo "<li>" . htmlspecialchars($row[0]) . "</li>";
}
echo "</ul>";

closeDBConnection($conn);
?>