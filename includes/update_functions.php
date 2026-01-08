<?php
require_once __DIR__ . '/../config/database.php';

// Get all IPL updates
function getAllUpdates($limit = null) {
    $sql = "SELECT * FROM ipl_updates ORDER BY created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    
    return getAllRows($sql);
}

// Get featured updates
function getFeaturedUpdates() {
    $sql = "SELECT * FROM ipl_updates WHERE is_featured = TRUE ORDER BY created_at DESC LIMIT 5";
    $updates = getAllRows($sql);

    // If DB doesn't have featured updates yet, return sensible defaults
    if (empty($updates)) {
        $now = date('Y-m-d H:i:s');
        return [
            [
                'update_id' => 0,
                'title' => 'Multiplayer Auction Rules',
                'content' => "Basic rules: Each team gets ₹120 crores budget; squad size 18-25; max 8 overseas players. Bidding starts at base price with intelligent increments and a 15s countdown.",
                'category' => 'Rules',
                'is_featured' => 1,
                'image_url' => '',
                'created_at' => $now
            ],
            [
                'update_id' => 0,
                'title' => 'Welcome to IPL Auction 2026',
                'content' => "Welcome! Create private rooms, invite friends with a 6-digit code, and enjoy live auctions with leaderboard and smart timers.",
                'category' => 'General',
                'is_featured' => 1,
                'image_url' => '',
                'created_at' => $now
            ],
            [
                'update_id' => 0,
                'title' => 'Player Pool & Groups',
                'content' => "Players are organized into groups: Marquee, A, B, C. Use the Add Player admin to assign Marquee players manually.",
                'category' => 'Players',
                'is_featured' => 1,
                'image_url' => '',
                'created_at' => $now
            ]
        ];
    }

    return $updates;
}

// Get update by ID
function getUpdateById($update_id) {
    $conn = getDBConnection();
    $update_id = $conn->real_escape_string($update_id);
    
    $sql = "SELECT * FROM ipl_updates WHERE update_id = $update_id";
    $update = getSingleRow($sql);
    
    closeDBConnection($conn);
    return $update;
}

// Add new update
function addUpdate($data) {
    $conn = getDBConnection();
    
    $title = $conn->real_escape_string($data['title']);
    $content = $conn->real_escape_string($data['content']);
    $category = $conn->real_escape_string($data['category']);
    $is_featured = isset($data['is_featured']) ? 1 : 0;
    $image_url = $conn->real_escape_string($data['image_url'] ?? '');
    
    $sql = "INSERT INTO ipl_updates (title, content, category, is_featured, image_url) 
            VALUES ('$title', '$content', '$category', $is_featured, '$image_url')";
    
    $result = $conn->query($sql);
    closeDBConnection($conn);
    
    return $result;
}
?>
