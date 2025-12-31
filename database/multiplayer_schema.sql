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

-- Room Player Assignments (tracks which players are sold to which participant)
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

-- Room Used Players (tracks which players have been auctioned in each room)
CREATE TABLE room_used_players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    player_id INT NOT NULL,
    is_sold BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (room_id) REFERENCES auction_rooms(room_id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(player_id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_player (room_id, player_id)
);
