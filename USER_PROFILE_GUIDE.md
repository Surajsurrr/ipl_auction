# User Profile System

## Overview
Users can now create and manage their own profiles with personalized information displayed in their dashboard.

## Features

### 1. User Dashboard (`/user/dashboard.php`)
- View profile information at a glance
- See user statistics (teams owned, bids placed)
- Display profile avatar or username initial
- Quick access to edit profile

### 2. Profile Management (`/user/edit-profile.php`)
- Edit personal information:
  - Full Name
  - Email
  - Phone
  - Date of Birth
  - City & Country
  - Favorite IPL Team
  - Bio/Description
- Change password securely
- Form validation

### 3. Profile Fields
- **username** - Unique username (set during registration)
- **email** - Email address
- **full_name** - Full display name
- **phone** - Phone number
- **bio** - Personal description
- **favorite_team** - Favorite IPL team selection
- **profile_image** - Profile picture URL (future enhancement)
- **city** - User's city
- **country** - User's country
- **date_of_birth** - Date of birth

## Database Changes

### New Fields Added to `users` Table:
```sql
phone VARCHAR(20)
bio TEXT
favorite_team VARCHAR(100)
profile_image VARCHAR(255)
city VARCHAR(100)
country VARCHAR(100)
date_of_birth DATE
updated_at TIMESTAMP
```

## File Structure

```
ipl_auction/
├── user/
│   ├── dashboard.php          # User dashboard page
│   └── edit-profile.php       # Profile editing page
├── includes/
│   └── user_functions.php     # User profile functions
└── database/
    └── add_profile_fields.sql # Database migration
```

## Usage

### For Users:

1. **Register/Login**
   - Create an account or login at `/auth/login.php`
   
2. **Access Dashboard**
   - Click "My Dashboard" in the navigation menu
   - Or navigate to `/user/dashboard.php`

3. **Edit Profile**
   - Click "Edit Profile" button on dashboard
   - Fill in your information
   - Click "Save Changes"

4. **Change Password**
   - Scroll to "Change Password" section on edit profile page
   - Enter current password and new password
   - Click "Change Password"

### For Developers:

#### Get User Profile:
```php
require_once 'includes/user_functions.php';

$user = getUserById($user_id);
// Returns array with all user fields
```

#### Update Profile:
```php
$data = [
    'full_name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '+91 1234567890',
    'city' => 'Mumbai',
    'country' => 'India',
    'favorite_team' => 'Mumbai Indians',
    'bio' => 'Cricket enthusiast'
];

$result = updateUserProfile($user_id, $data);
// Returns ['success' => true/false, 'message' => '...']
```

#### Change Password:
```php
$result = changePassword($user_id, $current_password, $new_password);
// Returns ['success' => true/false, 'message' => '...']
```

#### Get User Stats:
```php
$stats = getUserStats($user_id);
// Returns array with teams_owned, total_bids, etc.
```

## Navigation Integration

The navigation menu now shows:
- **Logged out users:** "Login" button
- **Logged in users:** "My Dashboard" + "Logout" links
- **Admin users:** "Admin Panel" link

## Security Features

- Password hashing using `password_hash()` and `password_verify()`
- SQL injection prevention with `real_escape_string()`
- Session-based authentication
- Email uniqueness validation
- Current password verification before password change

## Future Enhancements

1. **Profile Image Upload**
   - Allow users to upload profile pictures
   - Image resizing and optimization
   - Avatar generation

2. **Activity Feed**
   - Track user auction activity
   - Display recent bids and purchases
   - Team performance metrics

3. **Preferences**
   - Notification settings
   - Display preferences
   - Privacy settings

4. **Social Features**
   - Follow other users
   - Friend system
   - Public profile pages

5. **Badges & Achievements**
   - Award badges for milestones
   - Leaderboards
   - Activity streaks

## Installation

The profile fields have been automatically added to your database. If you need to reinstall:

```bash
# Run the migration script
php -r "require_once 'config/database.php'; $conn = getDBConnection(); $sql = file_get_contents('database/add_profile_fields.sql'); $conn->multi_query($sql); closeDBConnection($conn);"
```

Or use phpMyAdmin:
1. Open phpMyAdmin
2. Select `ipl_auction` database
3. Go to SQL tab
4. Copy and paste contents of `database/add_profile_fields.sql`
5. Click "Go"

## API Reference

### User Functions (`includes/user_functions.php`)

- `getUserById($user_id)` - Get user profile by ID
- `updateUserProfile($user_id, $data)` - Update user profile
- `changePassword($user_id, $current_password, $new_password)` - Change password
- `getUserStats($user_id)` - Get user statistics

### Session Functions (`config/session.php`)

- `isLoggedIn()` - Check if user is logged in
- `getCurrentUser()` - Get current user session data
- `requireLogin()` - Redirect to login if not authenticated
- `setFlashMessage($message, $type)` - Set flash message
- `getFlashMessage()` - Get and clear flash message

## Testing

To test the profile system:

1. Register a new user or use existing account
2. Login and navigate to "My Dashboard"
3. Click "Edit Profile"
4. Fill in profile information
5. Save changes
6. Verify data appears on dashboard
7. Test password change functionality

## Support

For issues or questions about the profile system, check:
- Database connection in `config/database.php`
- Session configuration in `config/session.php`
- User functions in `includes/user_functions.php`
