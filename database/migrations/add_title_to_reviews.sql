-- Migration: Add title column to reviews table
-- Date: 2025-11-15

-- Add title column if it doesn't exist
ALTER TABLE reviews 
ADD COLUMN IF NOT EXISTS title VARCHAR(255) AFTER rating;

-- Update existing reviews to have a default title
UPDATE reviews 
SET title = 'Review' 
WHERE title IS NULL OR title = '';
