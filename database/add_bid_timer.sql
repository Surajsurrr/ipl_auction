-- Add bid timer column to auction_rooms table
ALTER TABLE auction_rooms 
ADD COLUMN bid_timer_expires_at TIMESTAMP NULL AFTER current_bidder_id;
