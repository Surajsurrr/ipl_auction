# Quick Setup Guide

## Step-by-Step Instructions

### 1. Database Setup
```
1. Start XAMPP (Apache + MySQL)
2. Open browser: http://localhost/phpmyadmin
3. Click "New" to create database
4. Database name: ipl_auction
5. Click "Import" tab
6. Choose file: ipl_auction/database/schema.sql
7. Click "Go"
```

### 2. Access Application
```
Open browser: http://localhost/ipl_auction/
```

### 3. Login
```
Username: admin
Password: admin123
```

### 4. Start Auction
```
1. Click "Auction" in menu
2. Click "Start Auction" button
3. Select a group (A, B, C, or D)
4. Place bids by selecting team and clicking +10L or +50L
5. Click "Sold!" to finalize or "Pass" to skip
6. Select next group and repeat
```

## Common URLs

- Homepage: http://localhost/ipl_auction/
- Players: http://localhost/ipl_auction/pages/players.php
- Teams: http://localhost/ipl_auction/pages/teams.php
- Auction: http://localhost/ipl_auction/pages/auction.php
- Updates: http://localhost/ipl_auction/pages/updates.php
- Login: http://localhost/ipl_auction/auth/login.php
- Register: http://localhost/ipl_auction/auth/register.php

## Database Info

- Database: ipl_auction
- Tables: 8 (users, teams, players, player_stats, auction_session, bids, team_players, ipl_updates)
- Sample Teams: 8 IPL teams
- Sample Players: 20 players across all groups
- Default Budget: ₹120 Crores per team

## Need Help?

Check README.md for detailed documentation!
