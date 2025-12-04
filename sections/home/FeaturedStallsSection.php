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
            'class' => 'btn-browse'
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
            'href' => 'stall-detail.php?id=' . ($stall['id'] ?? ''),
            'class' => 'stall-card-action'
        ]);

        // Determine image source - use image field (which maps from logo_path)
        $imageHtml = '';
        $imagePath = $stall['image'] ?? null;
        
        // Debug: log what we're getting
        error_log("Featured Stall: " . ($stall['name'] ?? 'Unknown') . " | Image Path: " . var_export($imagePath, true));
        
        if (!empty($imagePath)) {
            // Prepend BASE_URL to the logo path from database
            $fullImagePath = BASE_URL . $imagePath;
            error_log("Full Image Path: " . $fullImagePath);
            $imageHtml = '<img src="' . Helpers::escape($fullImagePath) . '" alt="' . Helpers::escape($stall['name']) . '" class="stall-image" style="width: 100%; height: 100%; object-fit: cover; background: #f0f0f0;">';
        } else {
            // No logo available - show a neutral placeholder
            error_log("No image path for stall: " . ($stall['name'] ?? 'Unknown'));
            $imageHtml = '<div style="width: 100%; height: 100%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;"><i class="fas fa-store" style="font-size: 3rem;"></i></div>';
        }

        return '
        <article class="stall-card">
            <div class="stall-card-header">
                ' . $imageHtml . '
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

    
}
