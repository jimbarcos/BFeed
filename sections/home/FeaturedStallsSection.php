<?php
/**
 * BuzzarFeed - Featured Stalls Section Component
 *
 * Reusable featured stalls section
 * Following ISO 9241: Reusability and Modularity
 *
 * @package BuzzarFeed\Sections\Home
 * @version 1.0
 */

namespace BuzzarFeed\Sections\Home;

use BuzzarFeed\Components\BaseComponent;
use BuzzarFeed\Components\Common\Button;
use BuzzarFeed\Components\Common\Card;
use BuzzarFeed\Utils\Helpers;

class FeaturedStallsSection extends BaseComponent {

    /**
     * @var array Featured stalls data
     */
    protected array $stalls = [];

    /**
     * @var string Section title
     */
    protected string $title = '';

    /**
     * @var string Section location
     */
    protected string $location = '';

    /**
     * Initialize component
     */
    protected function initialize(): void {
        parent::initialize();

        $this->stalls = $this->prop('stalls', []);
        $this->title = $this->prop('title', '<span class="highlight-green">Explore</span> featured<br>stalls');
        $this->location = $this->prop('location', 'Terra 28th, 28th St. corner 7th Ave. BGC');
    }

    /**
     * Render the section
     *
     * @return string Rendered HTML
     */
    public function render(): string {
        $browseButton = new Button([
            'text' => 'Browse',
            'href' => 'stalls.php',
            'variant' => Button::VARIANT_SECONDARY,
            'class' => 'btn-browse',
            'icon' => 'fas fa-arrow-up-right-from-square'
        ]);

        $html = '
        <section id="featured-stalls" class="featured-stalls">
            <div class="container">
                <div class="section-header-stalls">
                    <h2 class="section-title">' . $this->title . '</h2>
                    <p class="section-subtitle">
                        <i class="fas fa-map-marker-alt"></i> ' . Helpers::escape($this->location) . '
                    </p>
                    ' . $browseButton->render() . '
                </div>
                <div class="stalls-grid">';

        // Render stall cards
        if (empty($this->stalls)) {
            // Default placeholder stalls
            $html .= $this->renderPlaceholderStalls();
        } else {
            foreach ($this->stalls as $stall) {
                $html .= $this->renderStallCard($stall);
            }
        }

        $html .= '
                </div>
            </div>
        </section>';

        return $html;
    }

    /**
     * Render a stall card
     *
     * @param array $stall Stall data
     * @return string Rendered HTML
     */
    protected function renderStallCard(array $stall): string {
        $viewButton = new Button([
            'text' => 'View details',
            'href' => 'stall-details.php?id=' . ($stall['id'] ?? ''),
            'class' => 'stall-card-action'
        ]);

        return '
        <article class="stall-card">
            <div class="stall-card-header">
                <div class="stall-image-placeholder"></div>
            </div>
            <div class="stall-card-overlay">
                <h3 class="stall-label-name">' . Helpers::escape($stall['name'] ?? '') . '</h3>
                <p class="stall-label-hours">
                    <i class="far fa-clock"></i> ' . Helpers::escape($stall['hours'] ?? '') . '
                </p>
                <p class="stall-label-description">
                    ' . Helpers::escape($stall['description'] ?? '') . '
                </p>
            </div>
            ' . $viewButton->render() . '
        </article>';
    }

    /**
     * Render placeholder stalls
     *
     * @return string Rendered HTML
     */
    protected function renderPlaceholderStalls(): string {
        $placeholders = [
            [
                'id' => 1,
                'name' => 'Kape Kuripot',
                'hours' => 'Monday to Saturday 09:00 to 18:00',
                'description' => 'Lorem ipsum dolor sit amet. Est magnam possimus in odio quis sed assumenda odio quis impedit.'
            ],
            [
                'id' => 2,
                'name' => 'Kape Kuripot',
                'hours' => 'Monday to Saturday 09:00 to 18:00',
                'description' => 'Lorem ipsum dolor sit amet. Est magnam possimus in odio quis sed assumenda odio quis impedit.'
            ]
        ];

        $html = '';
        foreach ($placeholders as $stall) {
            $html .= $this->renderStallCard($stall);
        }

        return $html;
    }
}
