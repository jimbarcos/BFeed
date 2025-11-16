<?php
/**
 * BuzzarFeed - Base Component Abstract Class
 * 
 * Base class for all UI components
 * Following ISO 9241: Extensibility, Reusability, and Maintainability
 * 
 * @package BuzzarFeed\Components
 * @version 1.0
 */

namespace BuzzarFeed\Components;

use BuzzarFeed\Utils\Helpers;

abstract class BaseComponent {
    
    /**
     * @var array Component properties
     */
    protected array $props = [];
    
    /**
     * @var array Component attributes (HTML attributes)
     */
    protected array $attributes = [];
    
    /**
     * @var string Component CSS classes
     */
    protected string $classes = '';
    
    /**
     * @var string Component ID
     */
    protected string $id = '';
    
    /**
     * Constructor
     * 
     * @param array $props Component properties
     */
    public function __construct(array $props = []) {
        $this->props = $props;
        $this->initialize();
    }
    
    /**
     * Initialize component (override in child classes)
     * 
     * @return void
     */
    protected function initialize(): void {
        // Extract common properties
        $this->id = $this->props['id'] ?? '';
        $this->classes = $this->props['class'] ?? '';
        $this->attributes = $this->props['attributes'] ?? [];
    }
    
    /**
     * Render the component (must be implemented by child classes)
     * 
     * @return string Rendered HTML
     */
    abstract public function render(): string;
    
    /**
     * Get property value
     * 
     * @param string $key Property key
     * @param mixed $default Default value
     * @return mixed Property value or default
     */
    protected function prop(string $key, $default = null) {
        return $this->props[$key] ?? $default;
    }
    
    /**
     * Build HTML attributes string
     * 
     * @return string HTML attributes
     */
    protected function buildAttributes(): string {
        $attrs = [];
        
        if (!empty($this->id)) {
            $attrs[] = 'id="' . Helpers::escape($this->id) . '"';
        }
        
        if (!empty($this->classes)) {
            $attrs[] = 'class="' . Helpers::escape($this->classes) . '"';
        }
        
        foreach ($this->attributes as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $attrs[] = Helpers::escape($key);
                }
            } else {
                $attrs[] = Helpers::escape($key) . '="' . Helpers::escape($value) . '"';
            }
        }
        
        return implode(' ', $attrs);
    }
    
    /**
     * Merge CSS classes
     * 
     * @param string ...$classes Classes to merge
     * @return string Merged classes
     */
    protected function mergeClasses(string ...$classes): string {
        return implode(' ', array_filter($classes));
    }
    
    /**
     * Render component to string
     * 
     * @return string Rendered HTML
     */
    public function __toString(): string {
        return $this->render();
    }
    
    /**
     * Static factory method for easier instantiation
     * 
     * @param array $props Component properties
     * @return static Component instance
     */
    public static function make(array $props = []): static {
        return new static($props);
    }
    
    /**
     * Render and echo component
     * 
     * @return void
     */
    public function display(): void {
        echo $this->render();
    }
}
