<?php
/**
 * BuzzarFeed - Review Report Service
 * 
 * Handles reporting and moderation of reviews
 * 
 * @package BuzzarFeed\Services
 * @version 1.0
 */

namespace BuzzarFeed\Services;

use BuzzarFeed\Utils\Database;
use BuzzarFeed\Utils\Session;
use BuzzarFeed\Utils\EmailService;

class ReviewReportService
{
    private Database $db;
    private AdminLogService $logService;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logService = new AdminLogService();
    }
    
    /**
     * Report a review
     * 
     * @param int $reviewId Review being reported
     * @param int $reporterId User reporting the review
     * @param string $reason Reason for report
     * @param string|null $customReason Custom description if reason is 'other'
     * @return bool Success status
     * @throws \Exception
     */
    public function reportReview(
        int $reviewId,
        int $reporterId,
        string $reason,
        ?string $customReason = null
    ): bool {
        // Check if review exists
        $review = $this->db->querySingle(
            "SELECT r.*, u.name as reviewer_name, s.name as stall_name
             FROM reviews r
             JOIN users u ON r.user_id = u.user_id
             JOIN food_stalls s ON r.stall_id = s.stall_id
             WHERE r.review_id = ?",
            [$reviewId]
        );
        
        if (!$review) {
            throw new \Exception('Review not found');
        }
        
        // Check if user is trying to report their own review
        if ($review['user_id'] == $reporterId) {
            throw new \Exception('You cannot report your own review');
        }
        
        // Check if user has an existing report for this review
        $existingReport = $this->db->querySingle(
            "SELECT report_id, status FROM review_reports 
             WHERE review_id = ? AND reporter_id = ?",
            [$reviewId, $reporterId]
        );
        
        if ($existingReport) {
            // If there's already a pending report, don't allow duplicate
            if ($existingReport['status'] === 'pending') {
                throw new \Exception('PENDING_REPORT');
            }
            
            // If the previous report was dismissed or reviewed, update it back to pending
            return $this->db->execute(
                "UPDATE review_reports 
                 SET status = 'pending',
                     report_reason = ?,
                     custom_reason = ?,
                     reviewed_by = NULL,
                     review_notes = NULL,
                     reviewed_at = NULL,
                     created_at = NOW()
                 WHERE report_id = ?",
                [$reason, $customReason, $existingReport['report_id']]
            );
        }
        
        // Create a new report if no existing report found
        return $this->db->execute(
            "INSERT INTO review_reports 
             (review_id, reporter_id, report_reason, custom_reason, status, created_at)
             VALUES (?, ?, ?, ?, 'pending', NOW())",
            [$reviewId, $reporterId, $reason, $customReason]
        );
    }
    
    /**
     * Get all pending review reports with details
     * 
     * @return array Array of pending reports grouped by review
     */
    public function getPendingReports(): array
    {
        // Get all pending reports grouped by review_id
        $groupedReports = $this->db->query(
            "SELECT 
                r.review_id,
                r.rating,
                r.title as review_title,
                r.comment as review_comment,
                r.is_hidden,
                r.created_at as review_created_at,
                reviewer.user_id as reviewer_id,
                reviewer.name as reviewer_name,
                reviewer.email as reviewer_email,
                s.stall_id,
                s.name as stall_name,
                COUNT(DISTINCT rr.report_id) as total_reports,
                MIN(rr.created_at) as first_report_date
             FROM reviews r
             JOIN review_reports rr ON r.review_id = rr.review_id
             JOIN users reviewer ON r.user_id = reviewer.user_id
             JOIN food_stalls s ON r.stall_id = s.stall_id
             WHERE rr.status = 'pending'
             GROUP BY r.review_id
             ORDER BY total_reports DESC, first_report_date DESC"
        );
        
        // For each review, get all individual reports
        $result = [];
        foreach ($groupedReports as $review) {
            $reports = $this->db->query(
                "SELECT 
                    rr.report_id,
                    rr.report_reason,
                    rr.custom_reason,
                    rr.created_at,
                    reporter.name as reporter_name,
                    reporter.email as reporter_email
                 FROM review_reports rr
                 JOIN users reporter ON rr.reporter_id = reporter.user_id
                 WHERE rr.review_id = ? AND rr.status = 'pending'
                 ORDER BY rr.created_at ASC",
                [$review['review_id']]
            );
            
            $review['reports'] = $reports;
            $result[] = $review;
        }
        
        return $result;
    }
    
    /**
     * Delete a review and mark reports as reviewed
     * 
     * @param int $reviewId Review to delete
     * @param int $adminId Admin performing the action
     * @param string $reason Reason for deletion
     * @return bool Success status
     * @throws \Exception
     */
    public function deleteReview(int $reviewId, int $adminId, string $reason): bool
    {
        // Get review details before deletion
        $review = $this->db->querySingle(
            "SELECT r.*, u.name as reviewer_name, u.email as reviewer_email, s.name as stall_name
             FROM reviews r
             JOIN users u ON r.user_id = u.user_id
             JOIN food_stalls s ON r.stall_id = s.stall_id
             WHERE r.review_id = ?",
            [$reviewId]
        );
        
        if (!$review) {
            throw new \Exception('Review not found');
        }
        
        try {
            $this->db->execute("START TRANSACTION");
            
            // Mark all pending reports for this review as reviewed
            $this->db->execute(
                "UPDATE review_reports 
                 SET status = 'reviewed', 
                     reviewed_by = ?, 
                     review_notes = ?,
                     reviewed_at = NOW()
                 WHERE review_id = ? AND status = 'pending'",
                [$adminId, $reason, $reviewId]
            );
            
            // Create moderation record before deletion
            $this->db->execute(
                "INSERT INTO review_moderations 
                 (review_id, moderator_id, reason, is_hidden, moderated_at, created_at)
                 VALUES (?, ?, ?, 1, NOW(), NOW())",
                [$reviewId, $adminId, $reason]
            );
            
            // Delete review reactions first (foreign key constraint)
            $this->db->execute(
                "DELETE FROM review_reactions WHERE review_id = ?",
                [$reviewId]
            );
            
            // Delete the review
            $this->db->execute(
                "DELETE FROM reviews WHERE review_id = ?",
                [$reviewId]
            );
            
            $this->db->execute("COMMIT");
            
            // Log admin action
            $this->logService->logReviewDeletion(
                $adminId,
                $reviewId,
                $review['stall_name'],
                $reason
            );
            
            // Send notification email to review owner
            $this->sendDeletedNotification($review, $reason);
            
            return true;
            
        } catch (\Exception $e) {
            $this->db->execute("ROLLBACK");
            throw $e;
        }
    }
    
    /**
     * Dismiss reports without deleting the review
     * 
     * @param int $reviewId Review ID
     * @param int $adminId Admin performing the action
     * @param string $notes Notes for dismissal
     * @return bool Success status
     */
    public function dismissReports(int $reviewId, int $adminId, string $notes): bool
    {
        // Get review details for logging
        $review = $this->db->querySingle(
            "SELECT r.review_id, s.name as stall_name
             FROM reviews r
             JOIN food_stalls s ON r.stall_id = s.stall_id
             WHERE r.review_id = ?",
            [$reviewId]
        );
        
        $result = $this->db->execute(
            "UPDATE review_reports 
             SET status = 'dismissed', 
                 reviewed_by = ?, 
                 review_notes = ?,
                 reviewed_at = NOW()
             WHERE review_id = ? AND status = 'pending'",
            [$adminId, $notes, $reviewId]
        );
        
        if ($result && $review) {
            // Log the dismissal action
            $this->logService->logAction(
                $adminId,
                'review',
                $reviewId,
                'dismiss_reports',
                "Dismissed reports for review on {$review['stall_name']}: {$notes}"
            );
        }
        
        return $result;
    }
    
    /**
     * Send notification to user about deleted review
     */
    private function sendDeletedNotification(array $review, string $reason): void
    {
        try {
            $emailService = EmailService::getInstance();
            
            $subject = "Your Review Has Been Removed - BuzzarFeed";
            
            $body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #DD452A;'>Review Moderation Notice</h2>
                    <p>Dear {$review['reviewer_name']},</p>
                    <p>We are writing to inform you that your review for <strong>{$review['stall_name']}</strong> has been removed by our moderation team.</p>
                    
                    <div style='background: #f5f5f5; padding: 15px; margin: 20px 0; border-left: 4px solid #DD452A;'>
                        <strong>Review Details:</strong><br>
                        <strong>Stall:</strong> {$review['stall_name']}<br>
                        <strong>Your Rating:</strong> {$review['rating']}/5 stars<br>
                    </div>
                    
                    <div style='background: #fff3cd; padding: 15px; margin: 20px 0; border-left: 4px solid #ff9800;'>
                        <strong>Reason for Removal:</strong><br>
                        {$reason}
                    </div>
                    
                    <p>We take content moderation seriously to maintain a respectful and helpful community for all users. Your review violated our community guidelines.</p>
                    
                    <p>If you believe this action was taken in error, please contact our support team at support@buzzarfeed.com.</p>
                    
                    <p>Thank you for your understanding.</p>
                    <p><strong>The BuzzarFeed Team</strong></p>
                </div>
            ";
            
            $emailService->sendCustomEmail(
                $review['reviewer_email'],
                $review['reviewer_name'],
                $subject,
                $body
            );
            
        } catch (\Exception $e) {
            error_log("Failed to send review deleted notification: " . $e->getMessage());
        }
    }
    
    /**
     * Get report statistics
     */
    public function getReportStats(): array
    {
        $pending = $this->db->querySingle(
            "SELECT COUNT(*) as count FROM review_reports WHERE status = 'pending'"
        );
        
        $hiddenReviews = $this->db->querySingle(
            "SELECT COUNT(*) as count FROM reviews WHERE is_hidden = 1"
        );
        
        return [
            'pending_reports' => $pending['count'] ?? 0,
            'hidden_reviews' => $hiddenReviews['count'] ?? 0
        ];
    }
}
