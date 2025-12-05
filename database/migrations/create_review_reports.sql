-- ============================================================================
-- Review Reports Migration
-- ============================================================================
-- Creates table for user-reported reviews that need moderation
-- Date: December 4, 2025
-- ============================================================================

USE if0_40016301_db_buzzarfeed;

-- Review Reports Table (for user-flagged content)
CREATE TABLE IF NOT EXISTS review_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    reporter_id INT NOT NULL,
    report_reason ENUM('vulgar', 'inappropriate', 'spam', 'harassment', 'misleading', 'other') NOT NULL,
    custom_reason TEXT,
    status ENUM('pending', 'reviewed', 'dismissed') DEFAULT 'pending',
    reviewed_by INT,
    review_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP,
    
    FOREIGN KEY (review_id) REFERENCES reviews(review_id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (reporter_id) REFERENCES users(user_id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    
    INDEX idx_review_id (review_id),
    INDEX idx_reporter_id (reporter_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    
    UNIQUE KEY unique_user_review_report (reporter_id, review_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- END OF MIGRATION
-- ============================================================================
