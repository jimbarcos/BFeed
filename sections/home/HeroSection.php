<?php
/**
 * BuzzarFeed - Hero Section Component
 *
 * Reusable hero section for homepage
 * Following ISO 9241: Reusability and Modularity
 *
 * @package BuzzarFeed\Sections\Home
 * @version 1.0
 */

namespace BuzzarFeed\Sections\Home;

use BuzzarFeed\Components\BaseComponent;
use BuzzarFeed\Components\Common\Button;
use BuzzarFeed\Utils\Helpers;

class HeroSection extends BaseComponent {

    /**
     * @var string Hero title
     */
    protected string $title = '';

    /**
     * @var string Hero description
     */
    protected string $description = '';

    /**
     * @var string|null CTA button text
     */
    protected ?string $ctaText = null;

    /**
     * @var string|null CTA button link
     */
    protected ?string $ctaLink = null;

    /**
     * Initialize component
     */
    protected function initialize(): void {
        parent::initialize();

        $this->title = $this->prop('title', 'Discover the Flavors<br>of <span class="highlight-orange">BGC Night Market</span>');
        $this->description = $this->prop('description', 'Taste. Try. Savor. Explore top stalls, menus, and honest reviews from fellow food lovers — at BGC Night Market.');
        $this->ctaText = $this->prop('ctaText', 'Discover Stalls');
        $this->ctaLink = $this->prop('ctaLink', '#featured-stalls');
    }

    /**
     * Render the section
     *
     * @return string Rendered HTML
     */
    public function render(): string {
        $button = new Button([
            'text' => $this->ctaText,
            'href' => $this->ctaLink,
            'variant' => Button::VARIANT_PRIMARY,
            'class' => 'btn-discover',
            'icon' => 'fas fa-utensils'
        ]);

        return '
        <section class="hero-section">
            <div class="container hero-container">
                <div class="hero-content">
                    <h1 class="hero-title">' . $this->title . '</h1>
                    <p class="hero-description">' . Helpers::escape($this->description) . '</p>
                    ' . $button->render() . '
                </div>
                <div class="hero-image">
                    <div class="polaroid-container">
                        <div class="polaroid polaroid-1">
                            <div class="polaroid-img"></div>
                        </div>
                        <div class="polaroid polaroid-2">
                            <div class="polaroid-img"></div>
                        </div>
                        <div class="polaroid polaroid-3">
                            <div class="polaroid-img"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>';
    }
}
