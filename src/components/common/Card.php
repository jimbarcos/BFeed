<?php
/**
 * BuzzarFeed - Card Component
 * 
 * Reusable card component for various content types
 * Following ISO 9241: Reusability and Flexibility
 * 
 * @package BuzzarFeed\Components\Common
 * @version 1.0
 */

namespace BuzzarFeed\Components\Common;

use BuzzarFeed\Components\BaseComponent;
use BuzzarFeed\Utils\Helpers;

class Card extends BaseComponent {
    
    /**
     * Card variants
     */
    const VARIANT_STALL = 'stall';
    const VARIANT_REVIEW = 'review';
    const VARIANT_JOIN = 'join';
    const VARIANT_DEFAULT = 'default';
    
    /**
     * @var string Card variant
     */
    protected string $variant = self::VARIANT_DEFAULT;
    
    /**
     * @var string|null Card title
     */
    protected ?string $title = null;
    
    /**
     * @var string|null Card subtitle
     */
    protected ?string $subtitle = null;
    
    /**
     * @var string|null Card content
     */
    protected ?string $content = null;
    
    /**
     * @var string|null Card image URL
     */
    protected ?string $image = null;
    
    /**
     * @var string|null Card footer content
     */
    protected ?string $footer = null;
    
    /**
     * @var array Card actions (buttons, links)
     */
    protected array $actions = [];
    
    /**
     * @var bool Whether card has hover effect
     */
    protected bool $hoverable = false;
    
    /**
     * Initialize component
     */
    protected function initialize(): void {
        parent::initialize();
        
        $this->variant = $this->prop('variant', self::VARIANT_DEFAULT);
        $this->title = $this->prop('title');
        $this->subtitle = $this->prop('subtitle');
        $this->content = $this->prop('content');
        $this->image = $this->prop('image');
        $this->footer = $this->prop('footer');
        $this->actions = $this->prop('actions', []);
        $this->hoverable = $this->prop('hoverable', false);
    }
    
    /**
     * Render the card
     * 
     * @return string Rendered HTML
     */
    public function render(): string {
        $classes = $this->buildClasses();
        $attributes = !empty($this->id) ? ' id="' . Helpers::escape($this->id) . '"' : '';
        
        $html = '<article class="' . Helpers::escape($classes) . '"' . $attributes . '>';
        
        // Render image
        if ($this->image) {
            $html .= $this->renderImage();
        }
        
        // Render card body
        $html .= '<div class="card-body">';
        
        if ($this->title) {
            $html .= '<h3 class="card-title">' . Helpers::escape($this->title) . '</h3>';
        }
        
        if ($this->subtitle) {
            $html .= '<p class="card-subtitle">' . Helpers::escape($this->subtitle) . '</p>';
        }
        
        if ($this->content) {
            $html .= '<div class="card-content">' . $this->content . '</div>';
        }
        
        $html .= '</div>';
        
        // Render footer
        if ($this->footer || !empty($this->actions)) {
            $html .= '<div class="card-footer">';
            
            if ($this->footer) {
                $html .= $this->footer;
            }
            
            if (!empty($this->actions)) {
                $html .= $this->renderActions();
            }
            
            $html .= '</div>';
        }
        
        $html .= '</article>';
        
        return $html;
    }
    
    /**
     * Render card image
     * 
     * @return string Rendered HTML
     */
    protected function renderImage(): string {
        return '<div class="card-image">' .
               '<img src="' . Helpers::escape($this->image) . '" alt="' . 
               Helpers::escape($this->title ?? 'Card image') . '">' .
               '</div>';
    }
    
    /**
     * Render card actions
     * 
     * @return string Rendered HTML
     */
    protected function renderActions(): string {
        $html = '<div class="card-actions">';
        
        foreach ($this->actions as $action) {
            if ($action instanceof BaseComponent) {
                $html .= $action->render();
            } else {
                $html .= $action;
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Build card CSS classes
     * 
     * @return string CSS classes
     */
    protected function buildClasses(): string {
        $classes = ['card'];
        
        // Add variant class
        if ($this->variant !== self::VARIANT_DEFAULT) {
            $classes[] = 'card-' . $this->variant;
        }
        
        // Add hoverable class
        if ($this->hoverable) {
            $classes[] = 'card-hoverable';
        }
        
        // Add custom classes
        if (!empty($this->classes)) {
            $classes[] = $this->classes;
        }
        
        return implode(' ', $classes);
    }
}
