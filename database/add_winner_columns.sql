-- Add columns to record announced winner for auction_rooms
ALTER TABLE auction_rooms
ADD COLUMN IF NOT EXISTS winner_participant_id INT NULL,
ADD COLUMN IF NOT EXISTS winner_team VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS winner_announced_at TIMESTAMP NULL;
