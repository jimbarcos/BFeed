-- Migration: Add review_reactions table for like/dislike functionality
-- Date: 2025-11-15

-- Create review_reactions table
CREATE TABLE IF NOT EXISTS review_reactions (
    reaction_id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction_type ENUM('like', 'dislike') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (review_id) REFERENCES reviews(review_id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    
    UNIQUE KEY unique_user_review_reaction (user_id, review_id),
    INDEX idx_review_id (review_id),
    INDEX idx_reaction_type (reaction_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
