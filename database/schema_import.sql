-- Compatible schema copy for import into a fresh database
-- Modified TIMESTAMP columns to be NULLABLE to avoid default value issues

DROP DATABASE IF EXISTS ipl_auction_new;
CREATE DATABASE ipl_auction_new CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ipl_auction_new;

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
    total_budget DECIMAL(15, 2) DEFAULT 12000000000.00,
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
    started_at TIMESTAMP NULL DEFAULT NULL,
    ended_at TIMESTAMP NULL DEFAULT NULL,
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

-- Sample data
INSERT INTO users (username, email, password, full_name) VALUES
('admin', 'admin@iplauction.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User');

INSERT INTO teams (team_name, team_logo, owner_user_id) VALUES
('Mumbai Indians', 'mi_logo.png', 1),
('Chennai Super Kings', 'csk_logo.png', NULL);

-- Minimal players sample
INSERT INTO players (player_name, player_type, player_role, base_price, auction_group, previous_team, nationality, age) VALUES
('Sample Player', 'Indian', 'Batsman', 1000000, 'C', 'MI', 'India', 25);

-- Multiplayer Auction System Tables
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

CREATE TABLE room_used_players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    player_id INT NOT NULL,
    is_sold BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (room_id) REFERENCES auction_rooms(room_id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(player_id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_player (room_id, player_id)
);
