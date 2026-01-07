<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'ipl_auction';
$port = 3307;
$mysqli = new mysqli($host, $user, $pass, $db, $port);
if ($mysqli->connect_error) {
    fwrite(STDERR, "Connect error: " . $mysqli->connect_error . "\n");
    exit(1);
}
$t = date('Ymd_His');
$out = __DIR__ . "/recovered_players_{$t}.sql";
$fh = fopen($out, 'w');
if (!$fh) { fwrite(STDERR, "Cannot open $out for writing\n"); exit(1); }
$ddl = "CREATE TABLE IF NOT EXISTS `players` (\n  `player_id` INT AUTO_INCREMENT PRIMARY KEY,\n  `player_name` VARCHAR(100) NOT NULL,\n  `player_image` VARCHAR(255),\n  `player_type` ENUM('Indian','Indian Uncapped','Overseas','Overseas Uncapped') NOT NULL,\n  `player_role` ENUM('Batsman','Bowler','All-Rounder','Wicket-Keeper') NOT NULL,\n  `base_price` DECIMAL(15,2) NOT NULL,\n  `auction_group` ENUM('Marquee','A','B','C','D') NOT NULL,\n  `previous_team` VARCHAR(100),\n  `nationality` VARCHAR(50) NOT NULL,\n  `age` INT,\n  `is_sold` BOOLEAN DEFAULT FALSE,\n  `current_team_id` INT,\n  `sold_price` DECIMAL(15,2),\n  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n) ENGINE=InnoDB;\n\n";
fwrite($fh, $ddl);
$query = "SELECT player_id, player_name, player_image, player_type, player_role, base_price, auction_group, previous_team, nationality, age, is_sold, current_team_id, sold_price, created_at FROM players_tmp";
if (!($res = $mysqli->query($query))) {
    fwrite(STDERR, "Query failed: " . $mysqli->error . "\n");
    fclose($fh);
    exit(1);
}
$cols = ['player_id','player_name','player_image','player_type','player_role','base_price','auction_group','previous_team','nationality','age','is_sold','current_team_id','sold_price','created_at'];
while ($row = $res->fetch_assoc()) {
    $vals = [];
    foreach ($cols as $c) {
        if ($row[$c] === null) { $vals[] = 'NULL'; continue; }
        if (is_numeric($row[$c]) && $c !== 'player_name' && $c !== 'player_image' && $c !== 'player_type' && $c !== 'player_role' && $c !== 'auction_group' && $c !== 'previous_team' && $c !== 'nationality' && $c !== 'created_at') {
            $vals[] = $row[$c];
        } else {
            $vals[] = "'" . $mysqli->real_escape_string($row[$c]) . "'";
        }
    }
    $line = "INSERT INTO `players` (`player_id`,`player_name`,`player_image`,`player_type`,`player_role`,`base_price`,`auction_group`,`previous_team`,`nationality`,`age`,`is_sold`,`current_team_id`,`sold_price`,`created_at`) VALUES (" . implode(',', $vals) . ");\n";
    fwrite($fh, $line);
}
fclose($fh);
echo "Wrote $out\n";
