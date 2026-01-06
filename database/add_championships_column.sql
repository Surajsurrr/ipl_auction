-- Add championships column to teams table
ALTER TABLE teams
ADD COLUMN IF NOT EXISTS championships INT DEFAULT 0;
