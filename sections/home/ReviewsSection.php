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
        $readMoreButton = new Button([
            'text' => 'Read more',
            'href' => '#',
            'variant' => Button::VARIANT_OUTLINE,
            'class' => 'review-readmore',
            'icon' => 'fas fa-arrow-right'
        ]);

        return '
        <article class="review-card">
            <div class="reviewer-avatar"></div>
            <div class="review-content">
                <h4 class="reviewer-name">' . Helpers::escape($review['reviewer'] ?? '') . '</h4>
                <p class="review-text">"' . Helpers::escape($review['text'] ?? '') . '"</p>
                <div class="review-footer">
                    <div class="review-rating">
                        <span class="rating-value">' . Helpers::escape($review['rating'] ?? '4.9') . '</span>
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
