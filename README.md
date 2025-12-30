# IPL Auction 2026 - Virtual Auction Platform

A complete web-based IPL auction system built with PHP and MySQL for conducting virtual cricket auctions among friends.

## 🏏 Features

### Core Functionality
- **Automated Auction System**: Random player selection from groups A, B, C, D
- **Budget Management**: Each team gets ₹120 Crores
- **Player Classification**: 
  - Indian Players
  - Indian Uncapped Players
  - Overseas Players
  - Overseas Uncapped Players
- **Detailed Player Stats**: Batting and bowling statistics, previous teams, records
- **Real-time Bidding**: Live auction with incremental bidding
- **Team Management**: Track team budgets, player rosters, and squad composition
- **IPL Updates**: News and announcements section

### Technical Features
- User authentication (Login/Register)
- Responsive design
- Interactive UI with JavaScript
- MySQL database backend
- Session management
- Real-time auction updates

## 📁 Project Structure

```
ipl_auction/
├── assets/
│   ├── css/
│   │   └── style.css          # Main stylesheet
│   └── js/
│       └── script.js          # JavaScript functionality
├── auth/
│   ├── login.php              # Login page
│   ├── register.php           # Registration page
│   └── logout.php             # Logout handler
├── config/
│   ├── database.php           # Database connection
│   └── session.php            # Session management
├── database/
│   └── schema.sql             # Database schema
├── includes/
│   ├── auction_functions.php  # Auction logic
│   ├── player_functions.php   # Player operations
│   ├── team_functions.php     # Team management
│   └── update_functions.php   # Updates/News
├── pages/
│   ├── auction.php            # Live auction page
│   ├── players.php            # Player listing
│   ├── teams.php              # Team management
│   └── updates.php            # IPL updates
└── index.php                  # Homepage
```

## 🚀 Setup Instructions

### Prerequisites
- XAMPP (or similar PHP/MySQL environment)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser

### Step 1: Install XAMPP
1. Download and install XAMPP from https://www.apachefriends.org/
2. Start Apache and MySQL from XAMPP Control Panel

### Step 2: Setup Database
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named `ipl_auction`
3. Import the schema:
   - Click on the `ipl_auction` database
   - Go to "Import" tab
   - Choose file: `database/schema.sql`
   - Click "Go" to execute

The schema will create:
- 8 tables (users, teams, players, player_stats, auction_session, bids, team_players, ipl_updates)
- Sample data including 8 teams and 20 players
- An admin user (username: admin, password: admin123)

### Step 3: Configure Database Connection
The project is already configured for default XAMPP settings:
- Host: localhost
- Username: root
- Password: (empty)
- Database: ipl_auction

If your settings are different, edit `config/database.php`:
```php
define('DB_HOST', 'your_host');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'ipl_auction');
```

### Step 4: Access the Application
1. Open your web browser
2. Navigate to: `http://localhost/ipl_auction/`
3. The homepage will appear

## 🎮 How to Use

### First Time Setup
1. **Login**: Click "Login" and use the demo credentials:
   - Username: `admin`
   - Password: `admin123`

2. **Or Register**: Create a new account from the registration page

### Conducting an Auction

1. **Navigate to Auction Page**: Click "Auction" in the menu

2. **Start Auction**: Click "Start Auction" button

3. **Select Player Group**: Choose from Group A, B, C, or D
   - Group A: Premium players (base price 15-20 Cr)
   - Group B: Star players (base price 8-12 Cr)
   - Group C: Mid-tier players (base price 5-8 Cr)
   - Group D: Uncapped players (base price 2-3 Cr)

4. **Place Bids**: 
   - Select a team
   - Click "+10 L" (10 lakhs) or "+50 L" (50 lakhs) to bid
   - Minimum increment: ₹10 lakhs

5. **Finalize Sale**: 
   - Click "✅ Sold!" to assign player to highest bidder
   - Click "❌ Pass" if no bids or to skip

6. **Continue**: Select next group and repeat

### View Teams
- Go to "Teams" page
- Click "View Squad" on any team to see:
  - Players bought
  - Remaining budget
  - Team composition (Indian/Overseas, Batsmen/Bowlers)

### View Players
- Go to "Players" page
- Filter by:
  - Player Type (Indian/Overseas/Uncapped)
  - Group (A/B/C/D)
  - Status (Sold/Unsold)
- View detailed stats for each player

## 📊 Database Tables

### Key Tables

**players**: Player information and classification
- player_name, player_type, player_role
- base_price, auction_group
- nationality, age, previous_team

**teams**: Team details and budgets
- team_name, total_budget (120 Cr)
- remaining_budget, players_count

**auction_session**: Active auction state
- current_player_id, current_bid
- current_bidder_team_id, auction_status

**bids**: Bidding history
- player_id, team_id, bid_amount, bid_time

**player_stats**: Detailed player statistics
- matches, runs, average, strike_rate
- wickets, economy, centuries, etc.

## 🎨 Customization

### Adding More Players
1. Go to phpMyAdmin
2. Open the `players` table
3. Insert new records with player details
4. Add corresponding stats in `player_stats` table

### Adding More Teams
1. Open the `teams` table
2. Insert new team with:
   - team_name
   - total_budget: 12000000000 (120 Cr in paise)
   - remaining_budget: 12000000000

### Changing Budget
Edit `database/schema.sql` and update:
```sql
total_budget DECIMAL(12, 2) DEFAULT 12000000000.00
```
(120 crore = 12,000,000,000 paise)

### Modifying Bid Increments
Edit `pages/auction.php` and `assets/js/script.js`:
```php
<button type="submit" name="increment" value="1000000">+10 L</button>
<button type="submit" name="increment" value="5000000">+50 L</button>
```

## 🔧 Troubleshooting

### Database Connection Error
- Check XAMPP MySQL is running
- Verify database name is `ipl_auction`
- Check credentials in `config/database.php`

### Players Not Showing
- Ensure database schema is imported
- Check if sample data was inserted
- Verify in phpMyAdmin that `players` table has data

### Login Not Working
- Use demo credentials: admin / admin123
- Or register a new account
- Check `users` table in database

### Auction Not Starting
- Make sure you're logged in
- Check `auction_session` table exists
- Verify session is created in database

## 💡 Additional Features You Can Add

1. **Player Images**: Upload and display player photos
2. **Live Chat**: Real-time bidding chat
3. **Auction Timer**: Countdown for each player
4. **Email Notifications**: Alert teams about winning bids
5. **PDF Reports**: Generate team squad PDFs
6. **Analytics Dashboard**: Charts and statistics
7. **Multiple Auctions**: Support for different auction sessions
8. **Admin Panel**: Add/edit players and teams via UI

## 📝 Notes

- All prices are in Indian Rupees (Crores)
- 1 Crore = 10,000,000 (1 followed by 7 zeros)
- Budget per team: ₹120 Crores
- Minimum bid increment: ₹10 Lakhs (0.1 Crores)

## 🤝 Credits

Built with:
- PHP for backend logic
- MySQL for database
- HTML/CSS for frontend
- JavaScript for interactivity

## 📄 License

Free to use and modify for educational and personal projects.

---

**Enjoy your virtual IPL auction! 🏆**
