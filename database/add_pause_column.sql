-- Add pause functionality column
ALTER TABLE auction_rooms 
ADD COLUMN IF NOT EXISTS paused_time_remaining INT DEFAULT 0 
COMMENT 'Timer seconds remaining when auction was paused';
