<?php
/**
 * BuzzarFeed - User Service
 * 
 * Handles user account management business logic
 * Following ISO 9241 principles: Modularity, Reusability, Separation of Concerns
 * 
 * @package BuzzarFeed\Services
 * @version 1.0
 */

namespace BuzzarFeed\Services;

use BuzzarFeed\Utils\Database;
use BuzzarFeed\Utils\Helpers;

class UserService
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get user by ID with type information
     * 
     * @param int $userId
     * @return array|null
     */
    public function getUserById(int $userId): ?array
    {
        return $this->db->querySingle(
            "SELECT u.*, ut.type_name, ut.description as type_description
             FROM users u
             INNER JOIN user_types ut ON u.user_type_id = ut.user_type_id
             WHERE u.user_id = ?",
            [$userId]
        );
    }
    
    /**
     * Update user profile
     * 
     * @param int $userId
     * @param string $name
     * @return bool
     * @throws \Exception
     */
    public function updateProfile(int $userId, string $name): bool
    {
        if (empty($name)) {
            throw new \Exception("Name is required");
        }
        
        $this->db->execute(
            "UPDATE users SET name = ?, updated_at = NOW() WHERE user_id = ?",
            [$name, $userId]
        );
        
        return true;
    }
    
    /**
     * Change user password
     * 
     * @param int $userId
     * @param string $currentPassword
     * @param string $newPassword
     * @param string $hashedPassword Current hashed password from DB
     * @return bool
     * @throws \Exception
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword, string $hashedPassword): bool
    {
        // Verify current password
        if (!Helpers::verifyPassword($currentPassword, $hashedPassword)) {
            throw new \Exception("Current password is incorrect");
        }
        
        // Validate new password
        $passwordValidation = Helpers::validatePassword($newPassword);
        if (!$passwordValidation['valid']) {
            throw new \Exception($passwordValidation['message']);
        }
        
        // Update password
        $newHashedPassword = Helpers::hashPassword($newPassword);
        $this->db->execute(
            "UPDATE users SET hashed_password = ?, updated_at = NOW() WHERE user_id = ?",
            [$newHashedPassword, $userId]
        );
        
        return true;
    }
    
    /**
     * Delete user account
     * 
     * @param int $userId
     * @param string $password
     * @param string $hashedPassword Current hashed password from DB
     * @return bool
     * @throws \Exception
     */
    public function deleteAccount(int $userId, string $password, string $hashedPassword): bool
    {
        // Verify password
        if (!Helpers::verifyPassword($password, $hashedPassword)) {
            throw new \Exception("Password is incorrect");
        }
        
        // Check for associated stalls
        $stalls = $this->db->query(
            "SELECT stall_id FROM food_stalls WHERE owner_id = ?",
            [$userId]
        );
        
        if (!empty($stalls)) {
            throw new \Exception("Cannot delete account with active stalls. Please remove your stalls first.");
        }
        
        // Delete user
        $this->db->execute("DELETE FROM users WHERE user_id = ?", [$userId]);
        
        return true;
    }
    
    /**
     * Upload and update profile image
     * 
     * @param int $userId
     * @param array $file $_FILES array element
     * @return string Path to uploaded image
     * @throws \Exception
     */
    public function updateProfileImage(int $userId, array $file): string
    {
        // Validate file
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \Exception("No file uploaded");
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new \Exception("Only JPG and PNG images are allowed");
        }
        
        if ($file['size'] > 2 * 1024 * 1024) { // 2MB
            throw new \Exception("Image must be less than 2MB");
        }
        
        // Create upload directory
        $uploadDir = __DIR__ . '/../../uploads/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $userId . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new \Exception("Failed to upload image");
        }
        
        // Update database
        $relativePath = 'uploads/profiles/' . $filename;
        $this->db->execute(
            "UPDATE users SET profile_image = ?, updated_at = NOW() WHERE user_id = ?",
            [$relativePath, $userId]
        );
        
        return $relativePath;
    }
}
