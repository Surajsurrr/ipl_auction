<?php
// Use the site's DB connection to update RCB championships to 0
require_once __DIR__ . '/../config/database.php';

// Use app connection helper so the same DB (including DB_NAME_TEMP_TEST) is targeted
$logFile = __DIR__ . '/../data/debug/set_rcb_champ_site.log';
function slog($m) { global $logFile; @file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $m . "\n", FILE_APPEND); }

try {
    $conn = getDBConnection();
} catch (Exception $e) {
    slog('getDBConnection failed: ' . $e->getMessage());
    // Browser-safe output
    echo 'DB connection failed: ' . htmlspecialchars($e->getMessage()) . "\n";
    exit(2);
}

slog('Connected using site DB connection');

$findSql = "SELECT team_id, team_name, championships FROM teams WHERE team_name LIKE '%Royal%' OR team_name LIKE '%Challengers%' OR team_name LIKE '%RCB%';";
$res = $conn->query($findSql);
if (!$res) {
    slog('Find query failed: ' . $conn->error);
    echo 'Find query failed: ' . htmlspecialchars($conn->error) . "\n";
    $conn->close();
    exit(3);
}

$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;

if (count($rows) === 0) {
    echo "No matching teams found.\n";
    slog('No matching teams found');
    $conn->close();
    exit(0);
}

echo "Found teams:\n";
slog('Found ' . count($rows) . ' matching teams');
foreach ($rows as $r) {
    echo " - [ID={$r['team_id']}] {$r['team_name']} (championships={$r['championships']})\n";
    slog('Found team: ID=' . $r['team_id'] . ' name=' . $r['team_name'] . ' championships=' . $r['championships']);
}

$ids = array_map(function($r){ return intval($r['team_id']); }, $rows);
$idList = implode(',', $ids);

$upd = "UPDATE teams SET championships = 0 WHERE team_id IN ($idList)";
if (!$conn->query($upd)) {
    slog('Update failed: ' . $conn->error);
    echo 'Update failed: ' . htmlspecialchars($conn->error) . "\n";
    $conn->close();
    exit(4);
}

echo "Update OK. Rows affected: " . $conn->affected_rows . "\n";
slog('Update OK. Rows affected: ' . $conn->affected_rows);

$ver = $conn->query("SELECT team_id, team_name, championships FROM teams WHERE team_id IN ($idList)");
while ($r = $ver->fetch_assoc()) {
    echo " - [ID={$r['team_id']}] {$r['team_name']} (championships={$r['championships']})\n";
    slog('Verify: ID=' . $r['team_id'] . ' championships=' . $r['championships']);
}

$conn->close();
echo "Done.\n";
slog('Script finished successfully');

?>
