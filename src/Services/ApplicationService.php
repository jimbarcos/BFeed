<?php
/**
 * BuzzarFeed - Application Service
 * 
 * Handles business logic for stall application management
 * Following ISO 9241 principles: Modularity, Reusability, Separation of Concerns
 * 
 * @package BuzzarFeed\Services
 * @version 1.0
 */

namespace BuzzarFeed\Services;

use BuzzarFeed\Utils\Database;
use BuzzarFeed\Utils\Session;
use BuzzarFeed\Utils\EmailService;

class ApplicationService
{
    private Database $db;
    private AdminLogService $logService;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logService = new AdminLogService();
    }
    
    /**
     * Get application by ID with user details
     * 
     * @param int $applicationId
     * @return array|null
     */
    public function getApplicationById(int $applicationId): ?array
    {
        return $this->db->querySingle(
            "SELECT a.*, u.name, u.email 
             FROM applications a 
             JOIN users u ON a.user_id = u.user_id 
             WHERE a.application_id = ?",
            [$applicationId]
        );
    }
    
    /**
     * Get all pending applications
     * 
     * @return array
     */
    public function getPendingApplications(): array
    {
        return $this->db->query(
            "SELECT a.*, u.name as applicant_name, u.email as applicant_email 
             FROM applications a 
             JOIN users u ON a.user_id = u.user_id 
             WHERE a.current_status_id = 1 
             ORDER BY a.created_at DESC"
        );
    }

    /**
     * Approve an application and create the food stall
     * 
     * Steps:
     *  1. Validate and fetch the application.
     *  2. Start DB transaction.
     *  3. Create the food stall and location.
     *  4. Update application + user timestamp.
     *  5. Commit transaction.
     *  6. Send approval email (outside transaction).
     * 
     * @param int $applicationId
     * @param string $reviewNotes
     * @return bool
     * @throws \Exception
     */
    public function approveApplication(int $applicationId, string $reviewNotes = ''): bool
    {
        $application = $this->getApplicationById($applicationId);
        
        if (!$application) {
            throw new \Exception('Application not found');
        }
        
        try {
            // Start transaction
            $this->db->execute("START TRANSACTION");
            
            // Create food stall
            $stallId = $this->createFoodStall($application);
            
            // Create stall location
            if (!empty($application['location'])) {
                $this->createStallLocation($stallId, $application);
            }
            
            // Update application status to approved (2)
            $this->updateApplicationStatus($applicationId, 2);
            
            // Update user timestamp
            $this->updateUserTimestamp($application['user_id']);
            
            // Commit transaction
            $this->db->execute("COMMIT");
            
            // Log admin action
            $adminId = Session::get('user_id');
            if ($adminId) {
                $this->logService->logApplicationApproval(
                    $adminId,
                    $applicationId,
                    $application['stall_name'],
                    $reviewNotes
                );
            }
            
            // Send approval email
            $this->sendApprovalEmail($application, $reviewNotes);
            
            return true;
        } catch (\Exception $e) {
            $this->db->execute("ROLLBACK");
            throw $e;
        }
    }

    /**
     * Decline an application and clean up files
     * 
     * @param int $applicationId
     * @param string $reviewNotes
     * @return bool
     * @throws \Exception
     */
    public function declineApplication(int $applicationId, string $reviewNotes = ''): bool
    {
        $application = $this->getApplicationById($applicationId);
        
        if (!$application) {
            throw new \Exception('Application not found');
        }
        
        try {
            // Delete uploaded files
            $this->deleteApplicationFiles($application);
            
            // Delete application from database
            $this->db->execute(
                "DELETE FROM applications WHERE application_id = ?",
                [$applicationId]
            );
            
            // Log admin action
            $adminId = Session::get('user_id');
            if ($adminId) {
                $this->logService->logApplicationDecline(
                    $adminId,
                    $applicationId,
                    $application['stall_name'],
                    $reviewNotes
                );
            }
            
            // Send decline email
            $this->sendDeclineEmail($application, $reviewNotes);
            
            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Archive/hide an application
     * 
     * @param int $applicationId
     * @return bool
     */
    public function archiveApplication(int $applicationId): bool
    {
        $application = $this->getApplicationById($applicationId);
        
        $this->updateApplicationStatus($applicationId, 3);
        
        // Log admin action
        $adminId = Session::get('user_id');
        if ($adminId && $application) {
            $this->logService->logApplicationArchive(
                $adminId,
                $applicationId,
                $application['stall_name']
            );
        }
        
        return true;
    }

    /**
     * Create food stall from application data
     * 
     * @param array $application
     * @return int Stall ID
     */
    private function createFoodStall(array $application): int
    {
        $query = "INSERT INTO food_stalls 
            (owner_id, name, description, logo_path, food_categories, is_active, created_at) 
            VALUES (?, ?, ?, ?, ?, 1, NOW())";
        
        $this->db->execute($query, [
            $application['user_id'],
            $application['stall_name'],
            $application['stall_description'],
            $application['stall_logo_path'],
            $application['food_categories']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Create stall location from application data
     * 
     * @param int $stallId
     * @param array $application
     * @return void
     */
    private function createStallLocation(int $stallId, array $application): void
    {
        $query = "INSERT INTO stall_locations 
            (stall_id, address, latitude, longitude, created_at) 
            VALUES (?, ?, ?, ?, NOW())";
        
        $this->db->execute($query, [
            $stallId,
            $application['location'],
            $application['map_x'] ?? null,
            $application['map_y'] ?? null
        ]);
    }

    /**
     * Update application status
     * 
     * @param int $applicationId
     * @param int $statusId
     * @return void
     */
    private function updateApplicationStatus(int $applicationId, int $statusId): void
    {
        $this->db->execute(
            "UPDATE applications SET current_status_id = ? WHERE application_id = ?",
            [$statusId, $applicationId]
        );
    }
    
    /**
     * Update user timestamp
     * 
     * @param int $userId
     * @return void
     */
    private function updateUserTimestamp(int $userId): void
    {
        $this->db->execute(
            "UPDATE users SET updated_at = NOW() WHERE user_id = ?",
            [$userId]
        );
    }
    
    /**
     * Delete application files
     * 
     * @param array $application
     * @return void
     */
    private function deleteApplicationFiles(array $application): void
    {
        $uploadDir = __DIR__ . '/../../';
        $filesToDelete = [
            $application['bir_registration_path'],
            $application['business_permit_path'],
            $application['dti_sec_path'],
            $application['stall_logo_path']
        ];
        
        foreach ($filesToDelete as $filePath) {
            if ($filePath && file_exists($uploadDir . $filePath)) {
                unlink($uploadDir . $filePath);
            }
        }
        
        // Delete the directory if empty
        if (!empty($application['bir_registration_path'])) {
            $appDir = dirname($uploadDir . $application['bir_registration_path']);
            if (is_dir($appDir) && count(scandir($appDir)) == 2) {
                rmdir($appDir);
            }
        }
    }

    /**
     * Send approval email to applicant
     * 
     * @param array $application
     * @param string $reviewNotes
     * @return void
     */
    private function sendApprovalEmail(array $application, string $reviewNotes): void
    {
        try {
            $emailService = EmailService::getInstance();
            $emailService->sendApplicationApprovalEmail(
                $application['email'],
                $application['name'],
                $application['stall_name'],
                $reviewNotes
            );
            error_log("Application approval email sent to: {$application['email']}");
        } catch (\Exception $e) {
            error_log("Failed to send approval email: " . $e->getMessage());
            // Don't throw exception - email failure shouldn't stop the approval process
        }
    }
    
    /**
     * Send decline email to applicant
     * 
     * @param array $application
     * @param string $reviewNotes
     * @return void
     */
    private function sendDeclineEmail(array $application, string $reviewNotes): void
    {
        try {
            $emailService = EmailService::getInstance();
            $emailService->sendApplicationDeclineEmail(
                $application['email'],
                $application['name'],
                $application['stall_name'],
                $reviewNotes
            );
            error_log("Application decline email sent to: {$application['email']}");
        } catch (\Exception $e) {
            error_log("Failed to send decline email: " . $e->getMessage());
            // Don't throw exception - email failure shouldn't stop the decline process
        }
    }
}