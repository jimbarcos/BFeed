-- ============================================================================
-- BuzzarFeed Database Schema
-- ============================================================================
-- Database for BGC Night Market Bazaar food stall discovery platform
-- Version: 1.0
-- Created: November 2025
-- ============================================================================

-- Drop database if exists (CAUTION: Use only in local development)
-- DROP DATABASE IF EXISTS if0_40016301_db_buzzarfeed;

-- Use InfinityFree database
USE if0_40016301_db_buzzarfeed;

-- ============================================================================
-- LOOKUP TABLES (Reference Data)
-- ============================================================================

-- User Types Table
CREATE TABLE IF NOT EXISTS user_types (
    user_type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_type_name (type_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Approval Status Table (for application reviews)
CREATE TABLE IF NOT EXISTS approval_statuses (
    status_id INT AUTO_INCREMENT PRIMARY KEY,
    status_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status_name (status_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu Category Table
CREATE TABLE IF NOT EXISTS menu_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- USER MANAGEMENT TABLES
-- ============================================================================

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    hashed_password VARCHAR(255) NOT NULL,
    user_type_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_type_id) REFERENCES user_types(user_type_id) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    
    INDEX idx_email (email),
    INDEX idx_user_type (user_type_id),
    INDEX idx_is_active (is_active),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Session Tokens Table (for "Remember Me" functionality)
CREATE TABLE IF NOT EXISTS session_tokens (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expiry DATETIME NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_token (token),
    INDEX idx_expiry (expiry)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password Reset Tokens Table
CREATE TABLE IF NOT EXISTS reset_tokens (
    reset_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expiry DATETIME NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_token (token),
    INDEX idx_expiry (expiry),
    INDEX idx_is_used (is_used)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- FOOD STALL TABLES
-- ============================================================================

-- Food Stalls Table
CREATE TABLE IF NOT EXISTS food_stalls (
    stall_id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    logo_path VARCHAR(255),
    hours VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (owner_id) REFERENCES users(user_id) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    
    INDEX idx_owner_id (owner_id),
    INDEX idx_name (name),
    INDEX idx_is_active (is_active),
    INDEX idx_is_featured (is_featured),
    INDEX idx_average_rating (average_rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stall Location Table
CREATE TABLE IF NOT EXISTS stall_locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    stall_id INT NOT NULL,
    address TEXT NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (stall_id) REFERENCES food_stalls(stall_id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    
    UNIQUE KEY unique_stall (stall_id),
    INDEX idx_latitude_longitude (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- STALL APPLICATION TABLES
-- ============================================================================

-- Applications Table (for stall owners to register their stalls)
CREATE TABLE IF NOT EXISTS applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    stall_name VARCHAR(255) NOT NULL,
    stall_description TEXT,
    location TEXT,
    food_categories JSON,
    bir_registration_path VARCHAR(255),
    business_permit_path VARCHAR(255),
    dti_sec_path VARCHAR(255),
    stall_logo_path VARCHAR(255),
    current_status_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (current_status_id) REFERENCES approval_statuses(status_id) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_current_status (current_status_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Application Reviews Table (admin reviews of applications)
CREATE TABLE IF NOT EXISTS application_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    status_id INT NOT NULL,
    notes TEXT,
    reviewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (application_id) REFERENCES applications(application_id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(user_id) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (status_id) REFERENCES approval_statuses(status_id) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    
    INDEX idx_application_id (application_id),
    INDEX idx_reviewer_id (reviewer_id),
    INDEX idx_status_id (status_id),
    INDEX idx_reviewed_at (reviewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- MENU TABLES
-- ============================================================================

-- Menu Items Table
CREATE TABLE IF NOT EXISTS menu_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    stall_id INT NOT NULL,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image_path VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (stall_id) REFERENCES food_stalls(stall_id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (category_id) REFERENCES menu_categories(category_id) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    
    INDEX idx_stall_id (stall_id),
    INDEX idx_category_id (category_id),
    INDEX idx_name (name),
    INDEX idx_is_available (is_available),
    INDEX idx_price (price)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- REVIEW TABLES
-- ============================================================================

-- Reviews Table
CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    stall_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    comment TEXT,
    is_anonymous BOOLEAN DEFAULT FALSE,
    is_hidden BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (stall_id) REFERENCES food_stalls(stall_id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    
    UNIQUE KEY unique_user_stall_review (user_id, stall_id),
    INDEX idx_stall_id (stall_id),
    INDEX idx_user_id (user_id),
    INDEX idx_rating (rating),
    INDEX idx_is_hidden (is_hidden),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Review Reactions Table (Likes/Dislikes)
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

-- Review Moderation Table
CREATE TABLE IF NOT EXISTS review_moderations (
    moderation_id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    moderator_id INT NOT NULL,
    reason TEXT NOT NULL,
    is_hidden BOOLEAN DEFAULT TRUE,
    moderated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (review_id) REFERENCES reviews(review_id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (moderator_id) REFERENCES users(user_id) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    
    INDEX idx_review_id (review_id),
    INDEX idx_moderator_id (moderator_id),
    INDEX idx_moderated_at (moderated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ADMIN LOGGING TABLE
-- ============================================================================

-- Admin Activity Log Table
CREATE TABLE IF NOT EXISTS admin_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    entity VARCHAR(100) NOT NULL,
    entity_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (admin_id) REFERENCES users(user_id) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    
    INDEX idx_admin_id (admin_id),
    INDEX idx_entity (entity),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- INSERT DEFAULT DATA
-- ============================================================================

-- Insert User Types (with duplicate check)
INSERT INTO user_types (type_name, description) 
SELECT * FROM (
    SELECT 'food_enthusiast' AS type_name, 'Regular user who can browse stalls and write reviews' AS description
    UNION ALL
    SELECT 'food_stall_owner', 'Stall owner who can manage their stall and respond to reviews'
    UNION ALL
    SELECT 'admin', 'Platform administrator with full access and moderation capabilities'
) AS tmp
WHERE NOT EXISTS (
    SELECT type_name FROM user_types WHERE type_name = tmp.type_name
) LIMIT 3;

-- Insert Approval Statuses (with duplicate check)
INSERT INTO approval_statuses (status_name, description)
SELECT * FROM (
    SELECT 'pending' AS status_name, 'Application submitted and awaiting review' AS description
    UNION ALL
    SELECT 'under_review', 'Application is currently being reviewed'
    UNION ALL
    SELECT 'approved', 'Application has been approved'
    UNION ALL
    SELECT 'rejected', 'Application has been rejected'
    UNION ALL
    SELECT 'requires_changes', 'Application needs modifications before approval'
) AS tmp
WHERE NOT EXISTS (
    SELECT status_name FROM approval_statuses WHERE status_name = tmp.status_name
) LIMIT 5;

-- Insert Menu Categories (with duplicate check)
INSERT INTO menu_categories (name, description, icon)
SELECT * FROM (
    SELECT 'Filipino' AS name, 'Traditional Filipino cuisine' AS description, 'fa-utensils' AS icon
    UNION ALL
    SELECT 'Japanese', 'Japanese food and sushi', 'fa-fish'
    UNION ALL
    SELECT 'Korean', 'Korean BBQ and dishes', 'fa-fire'
    UNION ALL
    SELECT 'Chinese', 'Chinese cuisine', 'fa-dragon'
    UNION ALL
    SELECT 'Western', 'Western-style food', 'fa-hamburger'
    UNION ALL
    SELECT 'Desserts', 'Sweet treats and desserts', 'fa-ice-cream'
    UNION ALL
    SELECT 'Beverages', 'Drinks and refreshments', 'fa-mug-hot'
    UNION ALL
    SELECT 'Street Food', 'Popular street food items', 'fa-hot-dog'
    UNION ALL
    SELECT 'Vegetarian', 'Vegetarian and vegan options', 'fa-leaf'
    UNION ALL
    SELECT 'Seafood', 'Fresh seafood dishes', 'fa-shrimp'
) AS tmp
WHERE NOT EXISTS (
    SELECT name FROM menu_categories WHERE name = tmp.name
) LIMIT 10;

-- ============================================================================
-- TRIGGERS (NOT SUPPORTED ON INFINITYFREE)
-- ============================================================================
-- InfinityFree free hosting does not support creating triggers
-- The functionality will be implemented in PHP code instead
-- See src/utils/ReviewHelper.php for trigger replacement logic
-- ============================================================================

-- The following triggers would normally handle automatic rating updates:
-- 1. update_stall_rating_after_insert - Updates ratings when review is added
-- 2. update_stall_rating_after_update - Updates ratings when review is modified
-- 3. update_stall_rating_after_delete - Updates ratings when review is deleted
-- 4. update_review_hidden_status - Updates review visibility when moderated
-- 5. update_application_status - Updates application status when reviewed

-- These are now handled in PHP application code for InfinityFree compatibility

/*
-- TRIGGER DEFINITIONS (For reference - use on local MySQL or production servers that support triggers)

DELIMITER //

CREATE TRIGGER update_stall_rating_after_insert
AFTER INSERT ON reviews
FOR EACH ROW
BEGIN
    UPDATE food_stalls 
    SET 
        average_rating = (
            SELECT AVG(rating) 
            FROM reviews 
            WHERE stall_id = NEW.stall_id AND is_hidden = FALSE
        ),
        total_reviews = (
            SELECT COUNT(*) 
            FROM reviews 
            WHERE stall_id = NEW.stall_id AND is_hidden = FALSE
        )
    WHERE stall_id = NEW.stall_id;
END//

CREATE TRIGGER update_stall_rating_after_update
AFTER UPDATE ON reviews
FOR EACH ROW
BEGIN
    UPDATE food_stalls 
    SET 
        average_rating = (
            SELECT AVG(rating) 
            FROM reviews 
            WHERE stall_id = NEW.stall_id AND is_hidden = FALSE
        ),
        total_reviews = (
            SELECT COUNT(*) 
            FROM reviews 
            WHERE stall_id = NEW.stall_id AND is_hidden = FALSE
        )
    WHERE stall_id = NEW.stall_id;
END//

CREATE TRIGGER update_stall_rating_after_delete
AFTER DELETE ON reviews
FOR EACH ROW
BEGIN
    UPDATE food_stalls 
    SET 
        average_rating = (
            SELECT COALESCE(AVG(rating), 0) 
            FROM reviews 
            WHERE stall_id = OLD.stall_id AND is_hidden = FALSE
        ),
        total_reviews = (
            SELECT COUNT(*) 
            FROM reviews 
            WHERE stall_id = OLD.stall_id AND is_hidden = FALSE
        )
    WHERE stall_id = OLD.stall_id;
END//

CREATE TRIGGER update_review_hidden_status
AFTER INSERT ON review_moderations
FOR EACH ROW
BEGIN
    UPDATE reviews 
    SET is_hidden = NEW.is_hidden 
    WHERE review_id = NEW.review_id;
END//

CREATE TRIGGER update_application_status
AFTER INSERT ON application_reviews
FOR EACH ROW
BEGIN
    UPDATE applications 
    SET current_status_id = NEW.status_id 
    WHERE application_id = NEW.application_id;
END//

DELIMITER ;
*/

-- ============================================================================
-- VIEWS (NOT SUPPORTED ON INFINITYFREE)
-- ============================================================================
-- InfinityFree free hosting does not support creating views
-- Use direct queries in PHP code instead
-- ============================================================================

-- View for active stalls with owner information (REFERENCE ONLY - DO NOT EXECUTE)
/*
SELECT 
    s.stall_id,
    s.name AS stall_name,
    s.description,
    s.logo_path,
    s.hours,
    s.average_rating,
    s.total_reviews,
    s.is_featured,
    u.name AS owner_name,
    u.email AS owner_email,
    l.address,
    l.latitude,
    l.longitude,
    s.created_at,
    s.updated_at
FROM food_stalls s
INNER JOIN users u ON s.owner_id = u.user_id
LEFT JOIN stall_locations l ON s.stall_id = l.stall_id
WHERE s.is_active = TRUE AND u.is_active = TRUE;
*/

-- View for pending applications (REFERENCE ONLY - DO NOT EXECUTE)
/*
SELECT 
    a.application_id,
    a.stall_name,
    a.stall_description,
    a.location,
    a.food_categories,
    u.name AS applicant_name,
    u.email AS applicant_email,
    ast.status_name AS current_status,
    a.created_at,
    a.updated_at
FROM applications a
INNER JOIN users u ON a.user_id = u.user_id
LEFT JOIN approval_statuses ast ON a.current_status_id = ast.status_id
WHERE ast.status_name IN ('pending', 'under_review');
*/

-- ============================================================================
-- INDEXES FOR PERFORMANCE (Additional indexes for common queries)
-- ============================================================================

CREATE INDEX idx_reviews_stall_rating ON reviews(stall_id, rating);
CREATE INDEX idx_reviews_user_created ON reviews(user_id, created_at DESC);
CREATE INDEX idx_stalls_featured_rating ON food_stalls(is_featured, average_rating DESC);

-- ============================================================================
-- COMMENTS
-- ============================================================================

-- Add table comments for documentation
ALTER TABLE users COMMENT = 'Stores all user accounts (enthusiasts, owners, admins)';
ALTER TABLE food_stalls COMMENT = 'Food stalls registered in the platform';
ALTER TABLE reviews COMMENT = 'User reviews for food stalls';
ALTER TABLE applications COMMENT = 'Stall registration applications';
ALTER TABLE menu_items COMMENT = 'Menu items offered by food stalls';

-- ============================================================================
-- END OF SCHEMA
-- ============================================================================
