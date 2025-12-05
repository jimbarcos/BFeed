<?php
/**
 * BuzzarFeed - Admin Log Service
 * 
 * Handles logging of admin activities
 * 
 * @package BuzzarFeed\Services
 * @version 1.0
 */

namespace BuzzarFeed\Services;

use BuzzarFeed\Utils\Database;
use BuzzarFeed\Utils\Session;

class AdminLogService
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Log an admin action
     * 
     * @param int $adminId Admin user ID performing the action
     * @param string $entity Entity type (application, review, user, etc.)
     * @param int|null $entityId ID of the entity being acted upon
     * @param string $action Action performed (approve, decline, delete, convert, etc.)
     * @param string|null $details Additional details about the action
     * @return bool Success status
     */
    public function logAction(
        int $adminId,
        string $entity,
        ?int $entityId,
        string $action,
        ?string $details = null
    ): bool {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        
        return $this->db->execute(
            "INSERT INTO admin_logs (admin_id, entity, entity_id, action, details, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [$adminId, $entity, $entityId, $action, $details, $ipAddress]
        );
    }
    
    /**
     * Log application approval
     */
    public function logApplicationApproval(int $adminId, int $applicationId, string $stallName, ?string $notes = null): bool
    {
        $details = "Approved stall application: {$stallName}";
        if ($notes) {
            $details .= " | Review Notes: {$notes}";
        }
        
        return $this->logAction($adminId, 'application', $applicationId, 'approve', $details);
    }
    
    /**
     * Log application decline
     */
    public function logApplicationDecline(int $adminId, int $applicationId, string $stallName, ?string $notes = null): bool
    {
        $details = "Declined stall application: {$stallName}";
        if ($notes) {
            $details .= " | Review Notes: {$notes}";
        }
        
        return $this->logAction($adminId, 'application', $applicationId, 'decline', $details);
    }
    
    /**
     * Log application archive
     */
    public function logApplicationArchive(int $adminId, int $applicationId, string $stallName): bool
    {
        $details = "Archived stall application: {$stallName}";
        return $this->logAction($adminId, 'application', $applicationId, 'archive', $details);
    }
    
    /**
     * Log review deletion
     */
    public function logReviewDeletion(int $adminId, int $reviewId, string $stallName, ?string $reason = null): bool
    {
        $details = "Deleted review for stall: {$stallName}";
        if ($reason) {
            $details .= " | Reason: {$reason}";
        }
        
        return $this->logAction($adminId, 'review', $reviewId, 'delete', $details);
    }
    
    /**
     * Log user to admin conversion
     */
    public function logUserConversion(int $adminId, int $convertedUserId, string $userName, string $userEmail): bool
    {
        $details = "Converted user to admin: {$userName} ({$userEmail})";
        return $this->logAction($adminId, 'user', $convertedUserId, 'convert_to_admin', $details);
    }
    
    /**
     * Get all admin logs with pagination
     * 
     * @param int $limit Number of logs per page
     * @param int $offset Starting position
     * @return array Array of log entries
     */
    public function getAllLogs(int $limit = 50, int $offset = 0): array
    {
        return $this->db->query(
            "SELECT 
                al.log_id,
                al.entity,
                al.entity_id,
                al.action,
                al.details,
                al.ip_address,
                al.created_at,
                u.user_id,
                u.name as admin_name,
                u.email as admin_email
             FROM admin_logs al
             JOIN users u ON al.admin_id = u.user_id
             ORDER BY al.created_at DESC
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }
    
    /**
     * Get logs by admin user
     */
    public function getLogsByAdmin(int $adminId, int $limit = 50, int $offset = 0): array
    {
        return $this->db->query(
            "SELECT 
                al.log_id,
                al.entity,
                al.entity_id,
                al.action,
                al.details,
                al.ip_address,
                al.created_at,
                u.user_id,
                u.name as admin_name,
                u.email as admin_email
             FROM admin_logs al
             JOIN users u ON al.admin_id = u.user_id
             WHERE al.admin_id = ?
             ORDER BY al.created_at DESC
             LIMIT ? OFFSET ?",
            [$adminId, $limit, $offset]
        );
    }
    
    /**
     * Get logs by entity type
     */
    public function getLogsByEntity(string $entity, int $limit = 50, int $offset = 0): array
    {
        return $this->db->query(
            "SELECT 
                al.log_id,
                al.entity,
                al.entity_id,
                al.action,
                al.details,
                al.ip_address,
                al.created_at,
                u.user_id,
                u.name as admin_name,
                u.email as admin_email
             FROM admin_logs al
             JOIN users u ON al.admin_id = u.user_id
             WHERE al.entity = ?
             ORDER BY al.created_at DESC
             LIMIT ? OFFSET ?",
            [$entity, $limit, $offset]
        );
    }
    
    /**
     * Get total count of logs
     */
    public function getTotalLogsCount(): int
    {
        $result = $this->db->querySingle("SELECT COUNT(*) as count FROM admin_logs");
        return $result['count'] ?? 0;
    }
    
    /**
     * Get logs count by entity
     */
    public function getLogsCountByEntity(string $entity): int
    {
        $result = $this->db->querySingle(
            "SELECT COUNT(*) as count FROM admin_logs WHERE entity = ?",
            [$entity]
        );
        return $result['count'] ?? 0;
    }
}
