-- Add map coordinates to applications table
-- Migration: add_map_coordinates_to_applications
-- Date: 2025-11-13

ALTER TABLE applications 
ADD COLUMN map_x DECIMAL(5,2) NULL COMMENT 'Map pin X coordinate (percentage)' AFTER stall_logo_path,
ADD COLUMN map_y DECIMAL(5,2) NULL COMMENT 'Map pin Y coordinate (percentage)' AFTER map_x;
