<?php
/**
 * BuzzarFeed - Reviews Section Component
 *
 * Reusable reviews section
 * Following ISO 9241: Reusability and Modularity
 *
 * @package BuzzarFeed\Sections\Home
 * @version 1.0
 */

namespace BuzzarFeed\Sections\Home;

use BuzzarFeed\Components\BaseComponent;
use BuzzarFeed\Components\Common\Button;
use BuzzarFeed\Utils\Helpers;

class ReviewsSection extends BaseComponent {

    /**
     * @var array Reviews data
     */
    protected array $reviews = [];

    /**
     * @var string Section title
     */
    protected string $title = '';

    /**
     * Initialize component
     */
    protected function initialize(): void {
        parent::initialize();

        $this->reviews = $this->prop('reviews', []);
        $this->title = $this->prop('title', 'See what foodies are <span class="highlight-green">raving</span> about...');
    }

    /**
     * Render the section
     *
     * @return string Rendered HTML
     */
    public function render(): string {
        $html = '
        <section class="reviews-section">
            <div class="container">
                <h2 class="section-title centered">' . $this->title . '</h2>
                <div class="reviews-grid">';

        // Render review cards
        if (empty($this->reviews)) {
            // Default placeholder reviews
            $html .= $this->renderPlaceholderReviews();
        } else {
            foreach ($this->reviews as $review) {
                $html .= $this->renderReviewCard($review);
            }
        }

        $html .= '
                </div>
            </div>
        </section>';

        return $html;
    }

    /**
     * Render a review card
     *
     * @param array $review Review data
     * @return string Rendered HTML
     */
    protected function renderReviewCard(array $review): string {
        $readMoreLink = '#';
        if (!empty($review['stall_id'])) {
            $readMoreLink = 'stall-detail.php?id=' . $review['stall_id'];
        }
        
        $readMoreButton = new Button([
            'text' => 'Read more',
            'href' => $readMoreLink,
            'variant' => Button::VARIANT_OUTLINE,
            'class' => 'review-readmore'
        ]);

        // Display stall logo in avatar circle
        $avatarHtml = '';
        $stallLogoPath = !empty($review['stall_logo']) ? BASE_URL . $review['stall_logo'] : null;
        
        if (!empty($stallLogoPath)) {
            $avatarHtml = '<div class="reviewer-avatar"><img src="' . Helpers::escape($stallLogoPath) . '" alt="' . Helpers::escape($review['stall_name'] ?? '') . '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;"></div>';
        } else {
            // Default placeholder icon if no logo
            $avatarHtml = '<div class="reviewer-avatar" style="background-color: rgba(255, 255, 255, 0.95); display: flex; align-items: center; justify-content: center;"><i class="fas fa-store" style="font-size: 1.8rem; color: #666;"></i></div>';
        }

        return '
        <article class="review-card">
            <div class="review-header-row">
                ' . $avatarHtml . '
                <div class="review-stall-info">
                    <h4 class="reviewer-name">' . Helpers::escape($review['reviewer'] ?? '') . '</h4>
                    ' . (!empty($review['stall_name']) ? '<p class="review-stall-name" title="' . Helpers::escape($review['stall_name']) . '"><i class="fas fa-store"></i> ' . Helpers::escape($review['stall_name']) . '</p>' : '') . '
                </div>
            </div>
            <div class="review-content">
                <p class="review-text">"' . Helpers::escape($review['text'] ?? '') . '"</p>
                <div class="review-footer">
                    <div class="review-rating">
                        <span class="rating-value">' . Helpers::escape($review['rating'] ?? '0.0') . '</span>
                        <img src="' . IMAGES_URL . 'star-rating.svg" alt="rating star" class="rating-star">
                    </div>
                    ' . $readMoreButton->render() . '
                </div>
            </div>
        </article>';
    }

    /**
     * Render placeholder reviews
     *
     * @return string Rendered HTML
     */
    protected function renderPlaceholderReviews(): string {
        $placeholders = [
            [
                'reviewer' => 'Sirap pochi•',
                'text' => 'Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua et dolore et labore sit ipsum.',
                'rating' => '4.9'
            ],
            [
                'reviewer' => 'H think homemade yung patty sa Yunie•',
                'text' => 'Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua et dolore et labore sit ipsum.',
                'rating' => '4.9'
            ],
            [
                'reviewer' => 'Sirap pochi•',
                'text' => 'Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua et dolore et labore sit ipsum.',
                'rating' => '4.9'
            ]
        ];

        $html = '';
        foreach ($placeholders as $review) {
            $html .= $this->renderReviewCard($review);
        }

        return $html;
    }
}
