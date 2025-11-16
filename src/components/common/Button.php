<?php
/**
 * BuzzarFeed - Button Component
 * 
 * Reusable button component with variants
 * Following ISO 9241: Reusability and Consistency
 * 
 * @package BuzzarFeed\Components\Common
 * @version 1.0
 */

namespace BuzzarFeed\Components\Common;

use BuzzarFeed\Components\BaseComponent;
use BuzzarFeed\Utils\Helpers;

class Button extends BaseComponent {
    
    /**
     * Button variants
     */
    const VARIANT_PRIMARY = 'primary';
    const VARIANT_SECONDARY = 'secondary';
    const VARIANT_SUCCESS = 'success';
    const VARIANT_OUTLINE = 'outline';
    
    /**
     * Button sizes
     */
    const SIZE_SMALL = 'sm';
    const SIZE_MEDIUM = 'md';
    const SIZE_LARGE = 'lg';
    
    /**
     * @var string Button text
     */
    protected string $text = '';
    
    /**
     * @var string Button variant
     */
    protected string $variant = self::VARIANT_PRIMARY;
    
    /**
     * @var string Button size
     */
    protected string $size = self::SIZE_MEDIUM;
    
    /**
     * @var string|null Button icon
     */
    protected ?string $icon = null;
    
    /**
     * @var string|null Button href for link buttons
     */
    protected ?string $href = null;
    
    /**
     * @var string Button type (button, submit, reset)
     */
    protected string $type = 'button';
    
    /**
     * @var bool Whether button is disabled
     */
    protected bool $disabled = false;
    
    /**
     * @var bool Whether button is full width
     */
    protected bool $fullWidth = false;
    
    /**
     * Initialize component
     */
    protected function initialize(): void {
        parent::initialize();
        
        $this->text = $this->prop('text', '');
        $this->variant = $this->prop('variant', self::VARIANT_PRIMARY);
        $this->size = $this->prop('size', self::SIZE_MEDIUM);
        $this->icon = $this->prop('icon');
        $this->href = $this->prop('href');
        $this->type = $this->prop('type', 'button');
        $this->disabled = $this->prop('disabled', false);
        $this->fullWidth = $this->prop('fullWidth', false);
    }
    
    /**
     * Render the button
     * 
     * @return string Rendered HTML
     */
    public function render(): string {
        $classes = $this->buildClasses();
        $tag = $this->href ? 'a' : 'button';
        
        $attributes = [];
        
        if (!empty($this->id)) {
            $attributes[] = 'id="' . Helpers::escape($this->id) . '"';
        }
        
        $attributes[] = 'class="' . Helpers::escape($classes) . '"';
        
        if ($tag === 'a') {
            $attributes[] = 'href="' . Helpers::escape($this->href) . '"';
        } else {
            $attributes[] = 'type="' . Helpers::escape($this->type) . '"';
            if ($this->disabled) {
                $attributes[] = 'disabled';
            }
        }
        
        // Add custom attributes
        foreach ($this->attributes as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $attributes[] = Helpers::escape($key);
                }
            } else {
                $attributes[] = Helpers::escape($key) . '="' . Helpers::escape($value) . '"';
            }
        }
        
        $attrs = implode(' ', $attributes);
        
        $iconHtml = '';
        if ($this->icon) {
            $iconHtml = '<i class="' . Helpers::escape($this->icon) . '"></i> ';
        }
        
        return "<{$tag} {$attrs}>{$iconHtml}" . Helpers::escape($this->text) . "</{$tag}>";
    }
    
    /**
     * Build button CSS classes
     * 
     * @return string CSS classes
     */
    protected function buildClasses(): string {
        $classes = ['btn'];
        
        // Add variant class
        $classes[] = 'btn-' . $this->variant;
        
        // Add size class
        if ($this->size !== self::SIZE_MEDIUM) {
            $classes[] = 'btn-' . $this->size;
        }
        
        // Add full width class
        if ($this->fullWidth) {
            $classes[] = 'btn-full';
        }
        
        // Add custom classes
        if (!empty($this->classes)) {
            $classes[] = $this->classes;
        }
        
        return implode(' ', $classes);
    }
}
