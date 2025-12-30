# Auction Group Classification System

## Overview
The IPL Auction system divides players into three groups (A, B, and C) based on their base price. This classification helps organize the auction process and allows teams to strategically plan their bidding.

## Group Classification

### Group A - Premium Players
- **Base Price Range:** > ₹2 Crore (> 20,000,000)
- **Description:** Elite players with the highest base prices
- **Typical Players:** International stars, proven match-winners, established captains

### Group B - Mid-Range Players
- **Base Price Range:** ₹1 Crore - ₹2 Crore (10,000,000 - 20,000,000)
- **Description:** Experienced players with proven track records
- **Typical Players:** Reliable performers, senior players, specialists

### Group C - Budget Players
- **Base Price Range:** < ₹1 Crore (< 10,000,000)
- **Description:** Young talent, uncapped players, and budget options
- **Typical Players:** Emerging talents, uncapped players, bench strength

## Automatic Group Assignment

The system automatically assigns players to groups based on their base price:

```php
// Group assignment logic in includes/player_functions.php
function calculateAuctionGroup($base_price) {
    if ($base_price > 20000000) {
        return 'A';  // > 2 Crore
    } elseif ($base_price >= 10000000) {
        return 'B';  // 1-2 Crore
    } else {
        return 'C';  // < 1 Crore
    }
}
```

## Implementation Details

### Database Schema
- **Table:** `players`
- **Column:** `auction_group` ENUM('A', 'B', 'C', 'D')
- **Note:** Group 'D' is reserved for future use

### Files Modified
1. **includes/player_functions.php**
   - Added `calculateAuctionGroup()` function
   - Modified `addPlayer()` to auto-calculate groups

2. **pages/auction.php**
   - Updated group selection buttons with descriptions
   - Shows only Groups A, B, and C

3. **pages/players.php**
   - Updated filter dropdown with group descriptions
   - Displays group information with price ranges

4. **update_auction_groups.php** (NEW)
   - Script to update all existing players' groups
   - Provides summary statistics by group

## Usage

### For Administrators
1. When adding new players, the system automatically assigns the correct group based on base price
2. Run `update_auction_groups.php` to update all existing players:
   ```bash
   php update_auction_groups.php
   ```

### During Auction
1. Select a group (A, B, or C) to bring a random player from that group
2. The current player's group is displayed prominently
3. Teams can filter players by group in the Players page

## Auction Strategy Tips

### Group A
- High-risk, high-reward bidding
- Compete for marquee players
- Reserve budget for key targets

### Group B
- Balanced approach
- Build core team strength
- Good value for experienced players

### Group C
- Volume strategy
- Find hidden gems
- Build squad depth within budget

## Future Enhancements
- Group D can be used for special categories (e.g., returning players, special drafts)
- Dynamic group adjustment based on auction dynamics
- Historical group performance analytics
