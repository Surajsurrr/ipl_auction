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
    return getAllRows($sql);
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
