<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

// Get user by ID
function getUserById($user_id) {
    $conn = getDBConnection();
    $user_id = $conn->real_escape_string($user_id);
    
    $sql = "SELECT user_id, username, email, full_name, phone, bio, favorite_team, 
            profile_image, city, country, date_of_birth, created_at, updated_at 
            FROM users WHERE user_id = $user_id";
    
    $result = $conn->query($sql);
    $user = $result ? $result->fetch_assoc() : null;
    
    closeDBConnection($conn);
    return $user;
}

// Update user profile
function updateUserProfile($user_id, $data) {
    $conn = getDBConnection();
    $user_id = $conn->real_escape_string($user_id);
    
    $updates = [];
    
    if (isset($data['full_name'])) {
        $full_name = $conn->real_escape_string($data['full_name']);
        $updates[] = "full_name = '$full_name'";
    }
    
    if (isset($data['email'])) {
        $email = $conn->real_escape_string($data['email']);
        // Check if email is already taken by another user
        $check_sql = "SELECT user_id FROM users WHERE email = '$email' AND user_id != $user_id";
        $check_result = $conn->query($check_sql);
        if ($check_result->num_rows > 0) {
            closeDBConnection($conn);
            return ['success' => false, 'message' => 'Email already in use'];
        }
        $updates[] = "email = '$email'";
    }
    
    if (isset($data['phone'])) {
        $phone = $conn->real_escape_string($data['phone']);
        $updates[] = "phone = '$phone'";
    }
    
    if (isset($data['bio'])) {
        $bio = $conn->real_escape_string($data['bio']);
        $updates[] = "bio = '$bio'";
    }
    
    if (isset($data['favorite_team'])) {
        $favorite_team = $conn->real_escape_string($data['favorite_team']);
        $updates[] = "favorite_team = '$favorite_team'";
    }
    
    if (isset($data['city'])) {
        $city = $conn->real_escape_string($data['city']);
        $updates[] = "city = '$city'";
    }
    
    if (isset($data['country'])) {
        $country = $conn->real_escape_string($data['country']);
        $updates[] = "country = '$country'";
    }
    
    if (isset($data['date_of_birth'])) {
        $dob = $conn->real_escape_string($data['date_of_birth']);
        $updates[] = "date_of_birth = '$dob'";
    }
    
    if (isset($data['profile_image'])) {
        $profile_image = $conn->real_escape_string($data['profile_image']);
        $updates[] = "profile_image = '$profile_image'";
    }
    
    if (empty($updates)) {
        closeDBConnection($conn);
        return ['success' => false, 'message' => 'No data to update'];
    }
    
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE user_id = $user_id";
    
    $result = $conn->query($sql);
    closeDBConnection($conn);
    
    if ($result) {
        // Update session data
        if (isset($data['full_name'])) {
            $_SESSION['full_name'] = $data['full_name'];
        }
        return ['success' => true, 'message' => 'Profile updated successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to update profile'];
    }
}

// Change user password
function changePassword($user_id, $current_password, $new_password) {
    $conn = getDBConnection();
    $user_id = $conn->real_escape_string($user_id);
    
    // Get current password hash
    $sql = "SELECT password FROM users WHERE user_id = $user_id";
    $result = $conn->query($sql);
    
    if (!$result || $result->num_rows == 0) {
        closeDBConnection($conn);
        return ['success' => false, 'message' => 'User not found'];
    }
    
    $user = $result->fetch_assoc();
    
    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        closeDBConnection($conn);
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    // Update password
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password = '$new_password_hash' WHERE user_id = $user_id";
    
    $result = $conn->query($sql);
    closeDBConnection($conn);
    
    if ($result) {
        return ['success' => true, 'message' => 'Password changed successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to change password'];
    }
}

// Get user statistics
function getUserStats($user_id) {
    $conn = getDBConnection();
    $user_id = $conn->real_escape_string($user_id);
    
    $stats = [];
    
    // Get teams owned by user
    $sql = "SELECT COUNT(*) as count FROM teams WHERE owner_user_id = $user_id";
    $result = $conn->query($sql);
    $stats['teams_owned'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Get total bids placed (if tracking user bids)
    // Note: Current schema doesn't have user_id in bids table
    // This is a placeholder for future enhancement
    $stats['total_bids'] = 0;
    
    closeDBConnection($conn);
    return $stats;
}
?>
