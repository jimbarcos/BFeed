<?php
/**
 * BuzzarFeed - Footer Component
 * 
 * Reusable footer component for all pages
 * Includes site links, social media, and copyright information
 * 
 * @package BuzzarFeed
 * @version 1.0
 * @author BuzzarFeed Development Team
 */

$currentYear = date('Y');
?>

<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <!-- About Section -->
            <div class="footer-about">
                <a href="index.php" class="footer-logo">
                    <img src="<?= IMAGES_URL ?>/Logo-Footer.png" alt="BuzzarFeed" class="footer-logo-img" style="height:60px; display:block;">
                </a>
                <p class="footer-description">
                    BuzzarFeed is your go-to platform for discovering the best food stalls 
                    at BGC Night Market Bazaar. Explore menus, read reviews, and connect 
                    with fellow food enthusiasts in the vibrant Manila food scene.
                </p>
            </div>
            
            <!-- Discover Section -->
            <div class="footer-section">
                <h4>Discover</h4>
                <ul class="footer-links">
                    <li>
                        <a href="stalls.php" class="footer-link">
                            All Stalls
                        </a>
                    </li>
                    <li>
                        <a href="map.php" class="footer-link">
                            Bazaar Map
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Account Section -->
            <div class="footer-section">
                <h4>Account</h4>
                <ul class="footer-links">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li>
                            <a href="my-account.php" class="footer-link">
                                My Profile
                            </a>
                        </li>
                        <li>
                            <a href="my-reviews.php" class="footer-link">
                                My Reviews
                            </a>
                        </li>
                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'owner'): ?>
                            <li>
                                <a href="my-stall.php" class="footer-link">
                                    My Stall
                                </a>
                            </li>
                        <?php endif; ?>
                        <li>
                            <a href="logout.php" class="footer-link">
                                Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li>
                            <a href="login.php" class="footer-link">
                                Log In
                            </a>
                        </li>
                        <li>
                            <a href="signup.php" class="footer-link">
                                Sign Up
                            </a>
                        </li>
                        <li>
                            <a href="signup.php?type=owner" class="footer-link">
                                Register Stall
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <p class="footer-copyright">
                &copy; <?php echo $currentYear; ?> BuzzarFeed. All rights reserved. 
                <a href="privacy.php" class="footer-link">Privacy Policy</a> | 
                <a href="terms.php" class="footer-link">Terms of Service</a>
            </p>
            
            <!-- Social Media Links -->
            <div class="footer-social">
                <a href="https://facebook.com/buzzarfeed" target="_blank" rel="noopener noreferrer" 
                   class="social-link" aria-label="Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://instagram.com/buzzarfeed" target="_blank" rel="noopener noreferrer" 
                   class="social-link" aria-label="Instagram">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="https://twitter.com/buzzarfeed" target="_blank" rel="noopener noreferrer" 
                   class="social-link" aria-label="Twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="mailto:info@buzzarfeed.com" class="social-link" aria-label="Email">
                    <i class="fas fa-envelope"></i>
                </a>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button (injected by JavaScript) -->
<style>
/* Back to Top Button Styles */
.back-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #4A8B4F;
    color: white;
    border: none;
    cursor: pointer;
    font-size: 1.25rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px);
    transition: all 0.3s ease;
    z-index: 999;
}

.back-to-top.visible {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.back-to-top:hover {
    background-color: #3d7441;
    transform: translateY(-5px);
}

.back-to-top:active {
    transform: translateY(0);
}

/* Loading Overlay Styles (for future AJAX requests) */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top-color: #4A8B4F;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Toast Notification Styles (for future use) */
.toast {
    position: fixed;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%) translateY(100px);
    background-color: #333;
    color: white;
    padding: 16px 24px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 9999;
    max-width: 90%;
    width: auto;
}

.toast.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

.toast-success {
    background-color: #4A8B4F;
}

.toast-error {
    background-color: #E8663E;
}

.toast-info {
    background-color: #2C2C2C;
}

@media (max-width: 768px) {
    .back-to-top {
        bottom: 20px;
        right: 20px;
        width: 45px;
        height: 45px;
        font-size: 1.125rem;
    }
}
</style>
