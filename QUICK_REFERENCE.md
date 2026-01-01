# IPL Auction - Quick Reference

## 🚀 Quick Start Commands

### Start XAMPP Services
```
1. Open XAMPP Control Panel
2. Click "Start" next to Apache
3. Click "Start" next to MySQL
```

### Setup Database (One-time)
```
1. Open: http://localhost/phpmyadmin
2. Create database: ipl_auction
3. Import: database/schema.sql
```

### Access Application
```
Homepage: http://localhost/ipl_auction/
```

## 🔐 Demo Login
```
Username: admin
Password: admin123
```

## 📍 Important URLs

| Page | URL |
|------|-----|
| Homepage | http://localhost/ipl_auction/ |
| Players | http://localhost/ipl_auction/pages/players.php |
| Teams | http://localhost/ipl_auction/pages/teams.php |
| Auction | http://localhost/ipl_auction/pages/auction.php |
| Updates | http://localhost/ipl_auction/pages/updates.php |
| Login | http://localhost/ipl_auction/auth/login.php |
| Register | http://localhost/ipl_auction/auth/register.php |
| Add Player | http://localhost/ipl_auction/admin/add_player.php |

## 🎮 Auction Workflow

1. **Login** → Use admin/admin123
2. **Go to Auction** → Click "Auction" in menu
3. **Start** → Click "Start Auction"
4. **Select Group** → Choose A, B, C, or D
5. **Bid** → Select team, click +10L or +50L
6. **Finalize** → Click "Sold!" or "Pass"
7. **Repeat** → Select next group

## 💰 Budget Info

- Total per team: ₹120 Crores
- Bid Increment (Dynamic):
  - Below ₹3 Cr: +10L, +50L, +1Cr
  - Above ₹3 Cr: +20L, +1Cr, +2Cr
- Bid Timer: 15 seconds (+ Wait button for 10s extra)

## 📊 Player Groups

| Group | Type | Base Price |
|-------|------|------------|
| A | Premium | 15-20 Cr |
| B | Star | 8-12 Cr |
| C | Mid-tier | 5-8 Cr |
| D | Budget | 2-3 Cr |

## 🏏 Sample Data

- **Teams**: 8 (MI, CSK, RCB, KKR, DC, RR, PBKS, SRH)
- **Players**: 20+ with stats
- **Groups**: 5 players per group
- **Budget**: ₹120 Cr per team

## 🔧 Troubleshooting

### Database Error
```
Check: XAMPP MySQL is running
Verify: Database name is 'ipl_auction'
Fix: Re-import database/schema.sql
```

### Can't Login
```
Use: admin / admin123
Or: Register new account
Check: Database has users table
```

### No Players Showing
```
Check: schema.sql imported correctly
Verify: Players table has data
Solution: Re-import database
```

## 📁 Key Files

### Config
- `config/database.php` - Database connection
- `config/session.php` - Session handling

### Functions
- `includes/auction_functions.php` - Auction logic
- `includes/player_functions.php` - Player operations
- `includes/team_functions.php` - Team management

### Pages
- `pages/auction.php` - Main auction interface
- `pages/players.php` - Player listing
- `pages/teams.php` - Team management

### Database
- `database/schema.sql` - Complete database structure

## 🎨 Customization

### Add Player
```
URL: http://localhost/ipl_auction/admin/add_player.php
Login required: Yes
```

### Change Budget
```
File: database/schema.sql
Line: total_budget DECIMAL(12, 2) DEFAULT 12000000000.00
Note: 120 Cr = 12,000,000,000 paise
```

### Add Team
```
Database: ipl_auction
Table: teams
Budget: 12000000000 (120 Cr)
```

## 📞 Support

Check these files for help:
- `README.md` - Full documentation
- `SETUP.md` - Quick setup guide
- `PROJECT_OVERVIEW.md` - Project details

## ✅ Pre-flight Checklist

Before starting auction:
- [ ] XAMPP Apache running
- [ ] XAMPP MySQL running
- [ ] Database 'ipl_auction' exists
- [ ] Schema imported successfully
- [ ] Can access homepage
- [ ] Can login with admin/admin123
- [ ] Players visible in Players page
- [ ] Teams visible in Teams page

## 🎯 Common Tasks

### Reset Auction
```sql
UPDATE auction_session SET 
    current_player_id = NULL,
    current_bidder_team_id = NULL,
    is_active = FALSE;

UPDATE players SET is_sold = FALSE, current_team_id = NULL, sold_price = NULL;
UPDATE teams SET remaining_budget = total_budget, players_count = 0;
DELETE FROM team_players;
DELETE FROM bids;
```

### View All Unsold Players
```sql
SELECT player_name, player_type, auction_group, base_price 
FROM players 
WHERE is_sold = FALSE 
ORDER BY auction_group, base_price DESC;
```

### Check Team Budgets
```sql
SELECT team_name, 
       remaining_budget / 10000000 as budget_crores,
       players_count 
FROM teams 
ORDER BY remaining_budget DESC;
```

---

**Need Help?** Check README.md for detailed instructions!

**Happy Auctioning! 🏆**
