<?php
// One-off script to set Royal Challengers Bangalore championships to 0
// Run from CLI: C:\xampp\php\php.exe tools\set_rcb_champ.php

require_once __DIR__ . '/../config/database.php';

$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$db   = DB_NAME;
$port = DB_PORT;

// Log helper
$logFile = __DIR__ . '/../data/debug/set_rcb_champ.log';
function logm($msg) { global $logFile; @file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND); }

$mysqli = new mysqli($host, $user, $pass, $db, $port);
if ($mysqli->connect_error) {
    logm("DB connect failed: " . $mysqli->connect_error);
    // also write to STDERR for CLI
    fwrite(STDERR, "DB connect failed: " . $mysqli->connect_error . PHP_EOL);
    exit(2);
}

logm("Connected to DB at $host:$port, DB={$db}");

// Find likely RCB rows
$sqlFind = "SELECT team_id, team_name, championships FROM teams WHERE team_name LIKE '%Royal%' OR team_name LIKE '%Challengers%' OR team_name LIKE '%RCB%';";
$res = $mysqli->query($sqlFind);
if (!$res) {
    logm("Find query failed: " . $mysqli->error);
    fwrite(STDERR, "Find query failed: " . $mysqli->error . PHP_EOL);
    $mysqli->close();
    exit(3);
}

$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;

if (count($rows) === 0) {
    $msg = "No matching teams found. Aborting.";
    echo $msg . "\n";
    logm($msg);
    $mysqli->close();
    exit(0);
}

echo "Found teams:\n";
logm("Found " . count($rows) . " matching teams");
foreach ($rows as $r) {
    echo " - [ID={$r['team_id']}] {$r['team_name']} (championships={$r['championships']})\n";
}

// Build safe WHERE clause using IDs
$ids = array_map(function($r){ return intval($r['team_id']); }, $rows);
$idList = implode(',', $ids);

$sqlUpdate = "UPDATE teams SET championships = 0 WHERE team_id IN ($idList);";
if (!$mysqli->query($sqlUpdate)) {
    fwrite(STDERR, "Update failed: " . $mysqli->error . PHP_EOL);
    $mysqli->close();
    exit(4);
}

echo "Update OK. Rows affected: " . $mysqli->affected_rows . "\n";
logm("Update OK. Rows affected: " . $mysqli->affected_rows);

$sqlVerify = "SELECT team_id, team_name, championships FROM teams WHERE team_id IN ($idList);";
$vres = $mysqli->query($sqlVerify);
while ($r = $vres->fetch_assoc()) {
    echo " - [ID={$r['team_id']}] {$r['team_name']} (championships={$r['championships']})\n";
}
$mysqli->close();
logm("Script completed successfully");
echo "Done.\n";


?>
