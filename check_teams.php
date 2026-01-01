<?php
require_once 'config/database.php';
require_once 'includes/team_functions.php';

$teams = getAllTeams();
foreach($teams as $team) {
    echo $team['team_name'] . "\n";
}
