<?php
require_once 'config/database.php';
require_once 'includes/team_functions.php';

$team_icons = [
    'Mumbai' => 'logo',
    'Chennai' => 'logo', 
    'Royal' => 'logo',
    'Kolkata' => 'logo',
    'Delhi' => 'logo',
    'Punjab' => 'logo',
    'Rajasthan' => 'logo',
    'Sunrisers' => 'logo',
    'Gujarat' => 'logo',
    'Lucknow' => 'logo'
];

$team_logos = [
    'Chennai' => '../assets/images/teams/csk.png',
    'Delhi' => '../assets/images/teams/dc.png',
    'Mumbai' => '../assets/images/teams/mi.png',
    'Kolkata' => '../assets/images/teams/kkr.png',
    'Gujarat' => '../assets/images/teams/gt.png',
    'Royal' => '../assets/images/teams/rcb.png',
    'Rajasthan' => '../assets/images/teams/rr.png',
    'Sunrisers' => '../assets/images/teams/srh.png',
    'Lucknow' => '../assets/images/teams/lsg.png',
    'Punjab' => '../assets/images/teams/pbks.png'
];

$teams = getAllTeams();

foreach ($teams as $team) {
    $icon = '🏏';
    $has_logo = false;
    $logo_path = '';
    
    foreach ($team_icons as $key => $emoji) {
        if (stripos($team['team_name'], $key) !== false) {
            if ($emoji === 'logo' && isset($team_logos[$key])) {
                $has_logo = true;
                $logo_path = $team_logos[$key];
            } else {
                $icon = $emoji;
            }
            break;
        }
    }
    
    echo "Team: " . $team['team_name'] . "\n";
    echo "Has Logo: " . ($has_logo ? 'YES' : 'NO') . "\n";
    echo "Logo Path: " . $logo_path . "\n";
    echo "---\n";
}
