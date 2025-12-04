<?php
/**
 * BuzzarFeed - Review Service
 * 
 * Handles business logic for review management
 * Following ISO 9241 principles: Modularity, Reusability, Separation of Concerns
 * 
 * @package BuzzarFeed\Services
 * @version 1.0
 */

namespace BuzzarFeed\Services;

use BuzzarFeed\Utils\Database;

class ReviewService
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get recent reviews for homepage
     * 
     * @param int $limit
     * @return array
     */
    public function getRecentReviews(int $limit = 6): array
    {
        $query = "SELECT 
                    r.review_id,
                    r.rating,
                    r.comment,
                    r.created_at,
                    u.name as reviewer_name,
                    fs.name as stall_name,
                    fs.stall_id,
                    fs.logo_path as stall_logo
                  FROM reviews r
                  INNER JOIN users u ON r.user_id = u.user_id
                  INNER JOIN food_stalls fs ON r.stall_id = fs.stall_id
                  WHERE fs.is_active = 1
                  ORDER BY r.created_at DESC
                  LIMIT ?";
        
        $reviews = $this->db->query($query, [$limit]);
        
        return array_map(function($review) {
            return $this->formatReviewData($review);
        }, $reviews);
    }
    
    /**
     * Get reviews for a specific stall
     * 
     * @param int $stallId
     * @return array
     */
    public function getStallReviews(int $stallId): array
    {
        $query = "SELECT 
                    r.review_id,
                    r.rating,
                    r.comment,
                    r.created_at,
                    u.name as reviewer_name
                  FROM reviews r
                  INNER JOIN users u ON r.user_id = u.user_id
                  WHERE r.stall_id = ?
                  ORDER BY r.created_at DESC";
        
        $reviews = $this->db->query($query, [$stallId]);
        
        return array_map(function($review) {
            return $this->formatReviewData($review);
        }, $reviews);
    }
    
    /**
     * Format review data for display
     * 
     * @param array $review
     * @return array
     */
    private function formatReviewData(array $review): array
    {
        return [
            'id' => $review['review_id'],
            'reviewer' => $review['reviewer_name'],
            'text' => $review['comment'] ?? '',
            'rating' => number_format((float)$review['rating'], 1),
            'stall_name' => $review['stall_name'] ?? '',
            'stall_id' => $review['stall_id'] ?? null,
            'stall_logo' => $review['stall_logo'] ?? null,
            'created_at' => $review['created_at'] ?? '',
        ];
    }
}
