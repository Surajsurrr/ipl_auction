-- IPL Auction Database Schema
-- Created: December 30, 2025

-- Drop existing tables if they exist
DROP TABLE IF EXISTS bids;
DROP TABLE IF EXISTS team_players;
DROP TABLE IF EXISTS auction_session;
DROP TABLE IF EXISTS player_stats;
DROP TABLE IF EXISTS players;
DROP TABLE IF EXISTS teams;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS ipl_updates;

-- Users table for authentication
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    bio TEXT,
    favorite_team VARCHAR(100),
    profile_image VARCHAR(255),
    city VARCHAR(100),
    country VARCHAR(100),
    date_of_birth DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Teams table
CREATE TABLE teams (
    team_id INT AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(100) NOT NULL,
    team_logo VARCHAR(255),
    owner_user_id INT,
    total_budget DECIMAL(15, 2) DEFAULT 12000000000.00, -- 120 crore in paise
    remaining_budget DECIMAL(15, 2) DEFAULT 12000000000.00,
    players_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Players table
CREATE TABLE players (
    player_id INT AUTO_INCREMENT PRIMARY KEY,
    player_name VARCHAR(100) NOT NULL,
    player_image VARCHAR(255),
    player_type ENUM('Indian', 'Indian Uncapped', 'Overseas', 'Overseas Uncapped') NOT NULL,
    player_role ENUM('Batsman', 'Bowler', 'All-Rounder', 'Wicket-Keeper') NOT NULL,
    base_price DECIMAL(15, 2) NOT NULL,
    auction_group ENUM('Marquee', 'A', 'B', 'C', 'D') NOT NULL,
    previous_team VARCHAR(100),
    nationality VARCHAR(50) NOT NULL,
    age INT,
    is_sold BOOLEAN DEFAULT FALSE,
    current_team_id INT,
    sold_price DECIMAL(15, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (current_team_id) REFERENCES teams(team_id) ON DELETE SET NULL
);

-- Player statistics table
CREATE TABLE player_stats (
    stat_id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    matches_played INT DEFAULT 0,
    runs_scored INT DEFAULT 0,
    batting_average DECIMAL(5, 2) DEFAULT 0.00,
    strike_rate DECIMAL(5, 2) DEFAULT 0.00,
    centuries INT DEFAULT 0,
    half_centuries INT DEFAULT 0,
    wickets_taken INT DEFAULT 0,
    bowling_average DECIMAL(5, 2) DEFAULT 0.00,
    economy_rate DECIMAL(4, 2) DEFAULT 0.00,
    best_bowling VARCHAR(20),
    catches INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES players(player_id) ON DELETE CASCADE
);

-- Auction session table
CREATE TABLE auction_session (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    session_name VARCHAR(100) NOT NULL,
    current_player_id INT,
    current_group ENUM('Marquee', 'A', 'B', 'C', 'D'),
    is_active BOOLEAN DEFAULT FALSE,
    current_bid DECIMAL(15, 2),
    current_bidder_team_id INT,
    auction_status ENUM('Not Started', 'In Progress', 'Completed') DEFAULT 'Not Started',
    started_at TIMESTAMP,
    ended_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (current_player_id) REFERENCES players(player_id) ON DELETE SET NULL,
    FOREIGN KEY (current_bidder_team_id) REFERENCES teams(team_id) ON DELETE SET NULL
);

-- Bids history table
CREATE TABLE bids (
    bid_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    player_id INT NOT NULL,
    team_id INT NOT NULL,
    bid_amount DECIMAL(15, 2) NOT NULL,
    bid_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES auction_session(session_id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(player_id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE
);

-- Team players junction table
CREATE TABLE team_players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    player_id INT NOT NULL,
    purchased_price DECIMAL(15, 2) NOT NULL,
    purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(player_id) ON DELETE CASCADE,
    UNIQUE KEY unique_player (player_id)
);

-- IPL Updates table
CREATE TABLE ipl_updates (
    update_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image_url VARCHAR(255),
    category ENUM('News', 'Match', 'Player', 'Team', 'Announcement') DEFAULT 'News',
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample IPL updates
INSERT INTO ipl_updates (title, content, category, is_featured) VALUES
('Welcome to IPL Auction 2026', 'Get ready for the most exciting virtual IPL auction! Assemble your dream team with a budget of 120 crores.', 'Announcement', TRUE),
('Auction Rules', 'Each team gets 120 crore rupees. Players are divided into groups A, B, C, and D. Bid wisely and build your championship squad!', 'Announcement', TRUE),
('Player Pool Released', 'Complete list of players available for auction has been released. Check out the stats and plan your strategy!', 'News', FALSE);

-- Insert sample admin user (password: admin123)
INSERT INTO users (username, email, password, full_name) VALUES
('admin', 'admin@iplauction.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User');

-- Insert sample teams
INSERT INTO teams (team_name, team_logo, owner_user_id) VALUES
('Mumbai Indians', 'mi_logo.png', 1),
('Chennai Super Kings', 'csk_logo.png', NULL),
('Royal Challengers Bangalore', 'rcb_logo.png', NULL),
('Kolkata Knight Riders', 'kkr_logo.png', NULL),
('Delhi Capitals', 'dc_logo.png', NULL),
('Rajasthan Royals', 'rr_logo.png', NULL),
('Punjab Kings', 'pbks_logo.png', NULL),
('Sunrisers Hyderabad', 'srh_logo.png', NULL),
('Gujarat Titans', 'gt_logo.png', NULL),
('Lucknow Super Giants', 'lsg_logo.png', NULL);

-- IPL 2025 Real Auction Players from Official List
-- Total: 100 players across 4 groups
INSERT INTO players (player_name, player_type, player_role, base_price, auction_group, previous_team, nationality, age) VALUES
-- Group A - Premium Players (Base 2 Cr)
('Jos Buttler', 'Overseas', 'Wicket-Keeper', 20000000, 'A', 'RR', 'England', 34),
('Shreyas Iyer', 'Indian', 'Batsman', 20000000, 'A', 'KKR', 'India', 30),
('Rishabh Pant', 'Indian', 'Batsman', 20000000, 'A', 'DC', 'India', 27),
('Kagiso Rabada', 'Overseas', 'Bowler', 20000000, 'A', 'PBKS', 'South Africa', 29),
('Arshdeep Singh', 'Indian', 'Bowler', 20000000, 'A', 'PBKS', 'India', 26),
('Mitchell Starc', 'Overseas', 'Bowler', 20000000, 'A', 'KKR', 'Australia', 35),
('Yuzvendra Chahal', 'Indian', 'Bowler', 20000000, 'A', 'RR', 'India', 34),
('Liam Livingstone', 'Overseas', 'All-Rounder', 20000000, 'A', 'PBKS', 'England', 31),
('David Miller', 'Overseas', 'Batsman', 15000000, 'A', 'GT', 'South Africa', 35),
('KL Rahul', 'Indian', 'Wicket-Keeper', 20000000, 'A', 'LSG', 'India', 32),
('Mohammad Shami', 'Indian', 'Bowler', 20000000, 'A', 'GT', 'India', 34),
('Mohammad Siraj', 'Indian', 'Bowler', 20000000, 'A', 'RCB', 'India', 31),
('Harry Brook', 'Overseas', 'Batsman', 20000000, 'A', 'DC', 'England', 26),
('Devon Conway', 'Overseas', 'Batsman', 20000000, 'A', 'CSK', 'New Zealand', 33),
('Jake Fraser-Mcgurk', 'Overseas', 'Batsman', 20000000, 'A', 'DC', 'Australia', 22),
('Aiden Markram', 'Overseas', 'Batsman', 20000000, 'A', 'SRH', 'South Africa', 30),
('David Warner', 'Overseas', 'Batsman', 20000000, 'A', 'DC', 'Australia', 38),
('Ravichandran Ashwin', 'Indian', 'All-Rounder', 20000000, 'A', 'RR', 'India', 38),
('Venkatesh Iyer', 'Indian', 'All-Rounder', 20000000, 'A', 'KKR', 'India', 30),
('Mitchell Marsh', 'Overseas', 'All-Rounder', 20000000, 'A', 'DC', 'Australia', 33),
('Glenn Maxwell', 'Overseas', 'All-Rounder', 20000000, 'A', 'RCB', 'Australia', 36),
('Marcus Stoinis', 'Overseas', 'All-Rounder', 20000000, 'A', 'LSG', 'Australia', 35),
('Jonny Bairstow', 'Overseas', 'Wicket-Keeper', 20000000, 'A', 'PBKS', 'England', 35),
('Quinton De Kock', 'Overseas', 'Wicket-Keeper', 20000000, 'A', 'LSG', 'South Africa', 32),
('Rahmanullah Gurbaz', 'Overseas', 'Wicket-Keeper', 20000000, 'A', 'KKR', 'Afghanistan', 23),
('Ishan Kishan', 'Indian', 'Wicket-Keeper', 20000000, 'A', 'MI', 'India', 26),
('Phil Salt', 'Overseas', 'Wicket-Keeper', 20000000, 'A', 'KKR', 'England', 28),
('Trent Boult', 'Overseas', 'Bowler', 20000000, 'A', 'RR', 'New Zealand', 35),
('Josh Hazlewood', 'Overseas', 'Bowler', 20000000, 'A', 'RCB', 'Australia', 34),
('Avesh Khan', 'Indian', 'Bowler', 20000000, 'A', 'RR', 'India', 28),

-- Group B - Star Players (Base 1-2 Cr)
('Devdutt Padikkal', 'Indian', 'Batsman', 20000000, 'B', 'LSG', 'India', 24),
('Rahul Tripathi', 'Indian', 'Batsman', 7500000, 'B', 'SRH', 'India', 34),
('Harshal Patel', 'Indian', 'All-Rounder', 20000000, 'B', 'PBKS', 'India', 34),
('Rachin Ravindra', 'Overseas', 'All-Rounder', 15000000, 'B', 'CSK', 'New Zealand', 25),
('Jitesh Sharma', 'Indian', 'Wicket-Keeper', 10000000, 'B', 'PBKS', 'India', 31),
('Khaleel Ahmed', 'Indian', 'Bowler', 20000000, 'B', 'DC', 'India', 27),
('Prasidh Krishna', 'Indian', 'Bowler', 20000000, 'B', 'RR', 'India', 29),
('Natarajan T', 'Indian', 'Bowler', 20000000, 'B', 'SRH', 'India', 33),
('Anrich Nortje', 'Overseas', 'Bowler', 20000000, 'B', 'DC', 'South Africa', 31),
('Noor Ahmad', 'Overseas', 'Bowler', 20000000, 'B', 'GT', 'Afghanistan', 20),
('Rahul Chahar', 'Indian', 'Bowler', 10000000, 'B', 'PBKS', 'India', 25),
('Wanindu Hasaranga', 'Overseas', 'Bowler', 20000000, 'B', 'SRH', 'Sri Lanka', 27),
('Maheesh Theekshana', 'Overseas', 'Bowler', 20000000, 'B', 'CSK', 'Sri Lanka', 24),
('Adam Zampa', 'Overseas', 'Bowler', 20000000, 'B', 'RR', 'Australia', 32),
('Faf Du Plessis', 'Overseas', 'Batsman', 20000000, 'B', 'RCB', 'South Africa', 40),
('Glenn Phillips', 'Overseas', 'Batsman', 20000000, 'B', 'SRH', 'New Zealand', 28),
('Kane Williamson', 'Overseas', 'Batsman', 20000000, 'B', 'GT', 'New Zealand', 34),
('Sam Curran', 'Overseas', 'All-Rounder', 20000000, 'B', 'PBKS', 'England', 26),
('Daryl Mitchell', 'Overseas', 'All-Rounder', 20000000, 'B', 'CSK', 'New Zealand', 33),
('Krunal Pandya', 'Indian', 'All-Rounder', 20000000, 'B', 'LSG', 'India', 34),

-- Group C - Mid-tier Players (Base 50L-1Cr)
('Washington Sundar', 'Indian', 'All-Rounder', 20000000, 'C', 'SRH', 'India', 25),
('Shardul Thakur', 'Indian', 'All-Rounder', 20000000, 'C', 'CSK', 'India', 33),
('Deepak Chahar', 'Indian', 'Bowler', 20000000, 'C', 'CSK', 'India', 32),
('Lockie Ferguson', 'Overseas', 'Bowler', 20000000, 'C', 'RCB', 'New Zealand', 33),
('Bhuvneshwar Kumar', 'Indian', 'Bowler', 20000000, 'C', 'SRH', 'India', 35),
('Mukesh Kumar', 'Indian', 'Bowler', 20000000, 'C', 'DC', 'India', 31),
('Mujeeb Ur Rahman', 'Overseas', 'Bowler', 20000000, 'C', 'KKR', 'Afghanistan', 24),
('Adil Rashid', 'Overseas', 'Bowler', 20000000, 'C', 'SRH', 'England', 37),
('Moeen Ali', 'Overseas', 'All-Rounder', 20000000, 'C', 'CSK', 'England', 37),
('Tim David', 'Overseas', 'All-Rounder', 20000000, 'C', 'MI', 'Australia', 29),
('Will Jacks', 'Overseas', 'All-Rounder', 20000000, 'C', 'RCB', 'England', 26),
('Spencer Johnson', 'Overseas', 'Bowler', 20000000, 'C', 'GT', 'Australia', 29),
('Mustafizur Rahman', 'Overseas', 'Bowler', 20000000, 'C', 'CSK', 'Bangladesh', 29),
('Naveen Ul Haq', 'Overseas', 'Bowler', 20000000, 'C', 'LSG', 'Afghanistan', 25),
('Umesh Yadav', 'Indian', 'Bowler', 20000000, 'C', 'GT', 'India', 37),

-- Group D - Budget/Uncapped Players (Base <50L)
('Waqar Salamkheil', 'Overseas Uncapped', 'Bowler', 7500000, 'D', '', 'Afghanistan', 23),
('Virat Kohli', 'Indian', 'Batsman', 20000000, 'D', 'RCB', 'India', 37),
('MS Dhoni', 'Indian Uncapped', 'Wicket-Keeper', 20000000, 'D', 'CSK', 'India', 44),
('Ruturaj Gaikwad', 'Indian', 'Batsman', 20000000, 'D', 'CSK', 'India', 28),
('Matheesha Pathirana', 'Overseas', 'Bowler', 20000000, 'D', 'CSK', 'Sri Lanka', 23),
('Shivam Dube', 'Indian', 'All-Rounder', 20000000, 'D', 'CSK', 'India', 31),
('Ravindra Jadeja', 'Indian', 'All-Rounder', 20000000, 'D', 'CSK', 'India', 35),
('Jasprit Bumrah', 'Indian', 'Bowler', 20000000, 'D', 'MI', 'India', 32),
('Suryakumar Yadav', 'Indian', 'Batsman', 20000000, 'D', 'MI', 'India', 35),
('Rohit Sharma', 'Indian', 'Batsman', 20000000, 'D', 'MI', 'India', 38),
('Tilak Varma', 'Indian', 'Batsman', 20000000, 'D', 'MI', 'India', 22),
('Hardik Pandya', 'Indian', 'All-Rounder', 20000000, 'D', 'MI', 'India', 31),
('Rajat Patidar', 'Indian', 'Batsman', 20000000, 'D', 'RCB', 'India', 31),
('Axar Patel', 'Indian', 'All-Rounder', 20000000, 'D', 'DC', 'India', 32),
('Kuldeep Yadav', 'Indian', 'Bowler', 20000000, 'D', 'DC', 'India', 31),
('Tristan Stubbs', 'Overseas', 'Batsman', 20000000, 'D', 'DC', 'South Africa', 24),
('Sunil Narine', 'Overseas Uncapped', 'All-Rounder', 20000000, 'D', 'KKR', 'West Indies', 35),
('Andre Russell', 'Overseas Uncapped', 'All-Rounder', 20000000, 'D', 'KKR', 'West Indies', 36),
('Varun Chakravarthy', 'Indian', 'Bowler', 20000000, 'D', 'KKR', 'India', 33),
('Harshit Rana', 'Indian', 'All-Rounder', 20000000, 'D', 'KKR', 'India', 24),
('Heinrich Klaasen', 'Overseas', 'Batsman', 20000000, 'D', 'SRH', 'South Africa', 33),
('Sanju Samson', 'Indian', 'Wicket-Keeper', 20000000, 'D', 'RR', 'India', 32),
('Yashasvi Jaiswal', 'Indian', 'Batsman', 20000000, 'D', 'RR', 'India', 24),
('Dhruv Jurel', 'Indian', 'Wicket-Keeper', 20000000, 'D', 'RR', 'India', 23),
('Riyan Parag', 'Indian', 'Batsman', 20000000, 'D', 'RR', 'India', 24),
('Shimron Hetmyer', 'Overseas', 'Batsman', 20000000, 'D', 'RR', 'West Indies', 30),
('Sandeep Sharma', 'Indian Uncapped', 'Bowler', 10000000, 'D', 'RR', 'India', 31),
('Shubman Gill', 'Indian', 'Batsman', 20000000, 'D', 'GT', 'India', 25),
('Rashid Khan', 'Overseas', 'All-Rounder', 20000000, 'D', 'GT', 'Afghanistan', 25),
('Sai Sudharsan', 'Indian', 'Batsman', 20000000, 'D', 'GT', 'India', 24),
('Rahul Tewatia', 'Indian Uncapped', 'All-Rounder', 10000000, 'D', 'GT', 'India', 32),
('Shahrukh Khan', 'Indian Uncapped', 'All-Rounder', 10000000, 'D', 'PBKS', 'India', 28),
('Shashank Singh', 'Indian Uncapped', 'Batsman', 5000000, 'D', 'PBKS', 'India', 34),
('Prabsimran Singh', 'Indian Uncapped', 'Batsman', 3000000, 'D', 'PBKS', 'India', 25);

-- Create auction session
INSERT INTO auction_session (session_name, auction_status) VALUES
('IPL Auction 2026', 'Not Started');

-- Multiplayer Auction System Tables

-- Auction Rooms Table
CREATE TABLE auction_rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_code VARCHAR(8) UNIQUE NOT NULL,
    room_name VARCHAR(100) NOT NULL,
    created_by INT NOT NULL,
    max_participants INT DEFAULT 10,
    status ENUM('waiting', 'in_progress', 'completed') DEFAULT 'waiting',
    current_player_id INT,
    current_bid DECIMAL(15, 2),
    current_bidder_id INT,
    total_budget_per_team DECIMAL(15, 2) DEFAULT 12000000000.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Room Participants Table
CREATE TABLE room_participants (
    participant_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    team_name VARCHAR(100) NOT NULL,
    remaining_budget DECIMAL(15, 2) DEFAULT 12000000000.00,
    players_count INT DEFAULT 0,
    is_host BOOLEAN DEFAULT FALSE,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES auction_rooms(room_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_room (room_id, user_id)
);

-- Room Player Assignments
CREATE TABLE room_player_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    participant_id INT NOT NULL,
    player_id INT NOT NULL,
    sold_price DECIMAL(15, 2) NOT NULL,
    sold_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES auction_rooms(room_id) ON DELETE CASCADE,
    FOREIGN KEY (participant_id) REFERENCES room_participants(participant_id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(player_id) ON DELETE CASCADE
);

-- Room Bids History
CREATE TABLE room_bids (
    bid_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    player_id INT NOT NULL,
    participant_id INT NOT NULL,
    bid_amount DECIMAL(15, 2) NOT NULL,
    bid_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES auction_rooms(room_id) ON DELETE CASCADE,
    FOREIGN KEY (participant_id) REFERENCES room_participants(participant_id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(player_id) ON DELETE CASCADE
);

-- Room Used Players
CREATE TABLE room_used_players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    player_id INT NOT NULL,
    is_sold BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (room_id) REFERENCES auction_rooms(room_id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(player_id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_player (room_id, player_id)
);
