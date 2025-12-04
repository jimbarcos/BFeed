<?php
/**
 * BuzzarFeed - Stall Service
 * 
 * Handles business logic for food stall management
 * Following ISO 9241 principles: Modularity, Reusability, Separation of Concerns
 * 
 * @package BuzzarFeed\Services
 * @version 1.0
 */

namespace BuzzarFeed\Services;

use BuzzarFeed\Utils\Database;

class StallService
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all active stalls with their details
     * 
     * @return array
     */
    public function getAllActiveStalls(): array
    {
        $query = "SELECT 
                    fs.stall_id,
                    fs.name,
                    fs.description,
                    fs.logo_path,
                    fs.food_categories,
                    fs.hours,
                    COALESCE(AVG(r.rating), 0) as average_rating,
                    COUNT(r.review_id) as total_reviews,
                    sl.address,
                    sl.latitude,
                    sl.longitude
                  FROM food_stalls fs
                  LEFT JOIN stall_locations sl ON fs.stall_id = sl.stall_id
                  LEFT JOIN reviews r ON fs.stall_id = r.stall_id
                  WHERE fs.is_active = 1
                  GROUP BY fs.stall_id, fs.name, fs.description, fs.logo_path, fs.food_categories, fs.hours, sl.address, sl.latitude, sl.longitude
                  ORDER BY fs.created_at DESC";
        
        $stalls = $this->db->query($query);
        
        // Process the results
        return array_map(function($stall) {
            return $this->formatStallData($stall);
        }, $stalls);
    }
    
    /**
     * Get stalls by category
     * 
     * @param string $category
     * @return array
     */
    public function getStallsByCategory(string $category): array
    {
        // Handle special cases for category names that might be stored differently
        $categoryVariations = [
            'Beverages' => ['Beverages', 'beverages', 'Beverage', 'beverage'],
            'Street Food' => ['Street Food', 'street_food', 'Streetfood', 'streetfood', 'Street food'],
            'Rice Meals' => ['Rice Meals', 'rice_meals', 'Rice meals', 'rice meals', 'RiceMeals'],
            'Fast Food' => ['Fast Food', 'fast_food', 'Fastfood', 'fastfood', 'Fast food'],
            'Snacks' => ['Snacks', 'snacks', 'Snack', 'snack'],
            'Pastries' => ['Pastries', 'pastries', 'Pastry', 'pastry'],
            'Others' => ['Others', 'others', 'Other', 'other']
        ];
        
        // Get possible variations for the category
        $searchCategories = $categoryVariations[$category] ?? [$category];
        
        // Build WHERE conditions for all variations
        $conditions = [];
        $params = [];
        foreach ($searchCategories as $variation) {
            $conditions[] = "JSON_CONTAINS(fs.food_categories, ?, '$')";
            $params[] = json_encode($variation);
        }
        
        $whereClause = '(' . implode(' OR ', $conditions) . ')';
        
        $query = "SELECT 
                    fs.stall_id,
                    fs.name,
                    fs.description,
                    fs.logo_path,
                    fs.food_categories,
                    fs.hours,
                    COALESCE(AVG(r.rating), 0) as average_rating,
                    COUNT(r.review_id) as total_reviews,
                    sl.address,
                    sl.latitude,
                    sl.longitude
                  FROM food_stalls fs
                  LEFT JOIN stall_locations sl ON fs.stall_id = sl.stall_id
                  LEFT JOIN reviews r ON fs.stall_id = r.stall_id
                  WHERE fs.is_active = 1
                  AND {$whereClause}
                  GROUP BY fs.stall_id, fs.name, fs.description, fs.logo_path, fs.food_categories, fs.hours, sl.address, sl.latitude, sl.longitude
                  ORDER BY fs.created_at DESC";
        
        $stalls = $this->db->query($query, $params);
        
        return array_map(function($stall) {
            return $this->formatStallData($stall);
        }, $stalls);
    }
    
    /**
     * Search stalls by name or description
     * 
     * @param string $searchTerm
     * @return array
     */
    public function searchStalls(string $searchTerm): array
    {
        $query = "SELECT 
                    fs.stall_id,
                    fs.name,
                    fs.description,
                    fs.logo_path,
                    fs.food_categories,
                    fs.hours,
                    COALESCE(AVG(r.rating), 0) as average_rating,
                    COUNT(r.review_id) as total_reviews,
                    sl.address,
                    sl.latitude,
                    sl.longitude
                  FROM food_stalls fs
                  LEFT JOIN stall_locations sl ON fs.stall_id = sl.stall_id
                  LEFT JOIN reviews r ON fs.stall_id = r.stall_id
                  WHERE fs.is_active = 1
                  AND (fs.name LIKE ? OR fs.description LIKE ?)
                  GROUP BY fs.stall_id, fs.name, fs.description, fs.logo_path, fs.food_categories, fs.hours, sl.address, sl.latitude, sl.longitude
                  ORDER BY fs.created_at DESC";
        
        $searchPattern = "%{$searchTerm}%";
        $stalls = $this->db->query($query, [$searchPattern, $searchPattern]);
        
        return array_map(function($stall) {
            return $this->formatStallData($stall);
        }, $stalls);
    }
    
    /**
     * Get stall by ID
     * 
     * @param int $stallId
     * @return array|null
     */
    public function getStallById(int $stallId): ?array
    {
        $query = "SELECT 
                    fs.stall_id,
                    fs.name,
                    fs.description,
                    fs.logo_path,
                    fs.food_categories,
                    fs.hours,
                    fs.average_rating,
                    fs.total_reviews,
                    sl.address,
                    sl.latitude,
                    sl.longitude,
                    u.name as owner_name,
                    u.email as owner_email
                  FROM food_stalls fs
                  LEFT JOIN stall_locations sl ON fs.stall_id = sl.stall_id
                  LEFT JOIN users u ON fs.owner_id = u.user_id
                  WHERE fs.stall_id = ? AND fs.is_active = 1";
        
        $stall = $this->db->querySingle($query, [$stallId]);
        
        return $stall ? $this->formatStallData($stall) : null;
    }
    
    /**
     * Get random stalls for featured section
     * 
     * @param int $limit
     * @return array
     */
    public function getRandomStalls(int $limit = 6): array
    {
        $query = "SELECT 
                    fs.stall_id,
                    fs.name,
                    fs.description,
                    fs.logo_path,
                    fs.food_categories,
                    fs.hours,
                    COALESCE(AVG(r.rating), 0) as average_rating,
                    COUNT(r.review_id) as total_reviews,
                    sl.address,
                    sl.latitude,
                    sl.longitude
                  FROM food_stalls fs
                  LEFT JOIN stall_locations sl ON fs.stall_id = sl.stall_id
                  LEFT JOIN reviews r ON fs.stall_id = r.stall_id
                  WHERE fs.is_active = 1
                  GROUP BY fs.stall_id, fs.name, fs.description, fs.logo_path, fs.food_categories, fs.hours, sl.address, sl.latitude, sl.longitude
                  ORDER BY RAND()
                  LIMIT ?";
        
        $stalls = $this->db->query($query, [$limit]);
        
        return array_map(function($stall) {
            return $this->formatStallData($stall);
        }, $stalls);
    }
    
    /**
     * Get all unique food categories from active stalls
     * 
     * @return array
     */
    public function getAllCategories(): array
    {
        $query = "SELECT DISTINCT food_categories 
                  FROM food_stalls 
                  WHERE is_active = 1 
                  AND food_categories IS NOT NULL";
        
        $results = $this->db->query($query);
        $categories = [];
        
        foreach ($results as $result) {
            if (!empty($result['food_categories'])) {
                $cats = json_decode($result['food_categories'], true);
                if (is_array($cats)) {
                    $categories = array_merge($categories, $cats);
                }
            }
        }
        
        return array_values(array_unique($categories));
    }
    
    /**
     * Format stall data for display
     * 
     * @param array $stall
     * @return array
     */
    private function formatStallData(array $stall): array
    {
        // Decode JSON categories
        $categories = !empty($stall['food_categories']) 
            ? json_decode($stall['food_categories'], true) 
            : [];
        
        // Ensure categories is an array
        if (!is_array($categories)) {
            $categories = [];
        }
        
        return [
            'id' => $stall['stall_id'],
            'name' => $stall['name'],
            'description' => $stall['description'] ?? '',
            'categories' => $categories,
            'rating' => (float)($stall['average_rating'] ?? 0),
            'reviews' => (int)($stall['total_reviews'] ?? 0),
            'hours' => $stall['hours'] ?? 'Hours not specified',
            'image' => $stall['logo_path'] ?? null,
            'address' => $stall['address'] ?? '',
            'latitude' => $stall['latitude'] ?? null,
            'longitude' => $stall['longitude'] ?? null,
            'owner_name' => $stall['owner_name'] ?? null,
            'owner_email' => $stall['owner_email'] ?? null,
        ];
    }
}
