-- Add food_categories column to food_stalls table
-- Migration: add_food_categories_to_food_stalls
-- Date: 2025-11-14

ALTER TABLE food_stalls 
ADD COLUMN food_categories JSON NULL COMMENT 'Food categories offered by the stall' AFTER logo_path;
