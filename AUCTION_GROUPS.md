# Auction Group Classification System

## Overview
The IPL Auction system divides players into four groups (Marquee, A, B, and C) based on their base price and special status. This classification helps organize the auction process and allows teams to strategically plan their bidding.

## Group Classification

### Group Marquee - Elite Marquee Players
- **Player Count:** 9 players
- **Description:** The most prestigious and sought-after players in IPL history
- **Players List:**
  1. Virat Kohli
  2. Rohit Sharma
  3. KL Rahul
  4. Shreyas Iyer
  5. Suryakumar Yadav
  6. Jasprit Bumrah
  7. Rishabh Pant
  8. M S Dhoni
  9. Hardik Pandya

### Group A - Premium Players
- **Base Price Range:** >= ₹200 Lakh (>= 20,000,000)
- **Description:** Elite players with the highest base prices (excluding Marquee players)
- **Current Count:** ~439 players
- **Typical Players:** International stars, proven match-winners, established captains

### Group B - Mid-Range Players
- **Base Price Range:** ₹100-200 Lakh (10,000,000 to < 20,000,000)
- **Description:** Experienced players with proven track records
- **Current Count:** 40 players
- **Typical Players:** Reliable performers, senior players, specialists

### Group C - Budget Players
- **Base Price Range:** < ₹100 Lakh (< 10,000,000)
- **Description:** Young talent, uncapped players, and budget options
- **Current Count:** 140 players
- **Typical Players:** Emerging talents, uncapped players, bench strength

## Automatic Group Assignment

The system automatically assigns players to groups based on their base price and player name:

```php
// Group assignment logic in includes/player_functions.php
function calculateAuctionGroup($base_price, $player_name = '') {
    // Check if player is a marquee player
    if ($player_name && isMarqueePlayer($player_name)) {
        return 'Marquee';
    }
    
    if ($base_price >= 20000000) {
        return 'A';  // >= 200 Lakh
    } elseif ($base_price >= 10000000) {
        return 'B';  // 100-200 Lakh
    } else {
        return 'C';  // < 100 Lakh
    }
}
```

## Implementation Details

### Database Schema
- **Table:** `players`
- **Column:** `auction_group` ENUM('Marquee', 'A', 'B', 'C', 'D')
- **Note:** Group 'D' is reserved for future use

### Current Player Distribution (as of Jan 1, 2026)
- **Total Players:** 628
- **Group Marquee:** 9 players (1.4%)
- **Group A:** ~439 players (69.9%)
- **Group B:** 40 players (6.4%)
- **Group C:** 140 players (22.3%)

### Files Modified
1. **includes/player_functions.php**
   - Added `getMarqueePlayers()` function to list marquee players
   - Added `isMarqueePlayer()` function to check marquee status
   - Updated `calculateAuctionGroup()` function to handle marquee players
   - Modified `addPlayer()` to auto-calculate groups

2. **pages/auction.php**
   - Updated group selection buttons to include Marquee group
   - Shows Marquee, A, B, and C groups with descriptions

3. **pages/players.php**
   - Updated filter dropdown with Marquee group option
   - Displays group information with price ranges

4. **update_auction_groups.php**
   - Script to update all existing players' groups
   - Preserves Marquee group assignments
   - Provides summary statistics by group

5. **update_marquee_players.php** (NEW)
   - Script to assign specific players to Marquee group
   - Updates database schema to support Marquee group
   - Lists all marquee players with statistics

6. **database/schema.sql**
   - Updated `auction_group` ENUM to include 'Marquee'
   - Updated `current_group` in auction_session table

## Usage

### For Administrators
1. When adding new players, the system automatically assigns the correct group based on base price and player name
2. Run `update_marquee_players.php` to set up the Marquee group (first time only):
   ```bash
   php update_marquee_players.php
   ```
3. Run `update_auction_groups.php` to update all existing players' groups:
   ```bash
   php update_auction_groups.php
   ```

### During Auction
1. Select a group (Marquee, A, B, or C) to bring a random player from that group
2. The current player's group is displayed prominently
3. Teams can filter players by group in the Players page
4. Start with Marquee players for maximum excitement!

## Auction Strategy Tips

### Group Marquee
- Highest-stakes bidding for legendary players
- Expect fierce competition and record-breaking bids
- These players can transform a team's fortunes
- Budget wisely - don't exhaust resources on one player

### Group A
- High-risk, high-reward bidding
- Compete for premium players
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
