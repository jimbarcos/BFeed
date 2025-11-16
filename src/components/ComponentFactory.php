<?php
/**
 * BuzzarFeed - Component Factory
 * 
 * Factory pattern for creating components
 * Following ISO 9241: Extensibility and Reusability
 * 
 * @package BuzzarFeed\Components
 * @version 1.0
 */

namespace BuzzarFeed\Components;

class ComponentFactory {
    
    /**
     * @var array Component registry
     */
    private static array $registry = [];
    
    /**
     * Register a component
     * 
     * @param string $name Component name
     * @param string $class Component class
     * @return void
     */
    public static function register(string $name, string $class): void {
        self::$registry[$name] = $class;
    }
    
    /**
     * Create a component instance
     * 
     * @param string $name Component name
     * @param array $props Component properties
     * @return BaseComponent Component instance
     * @throws \Exception If component not found
     */
    public static function create(string $name, array $props = []): BaseComponent {
        if (!isset(self::$registry[$name])) {
            throw new \Exception("Component '{$name}' not found");
        }
        
        $class = self::$registry[$name];
        return new $class($props);
    }
    
    /**
     * Check if component is registered
     * 
     * @param string $name Component name
     * @return bool True if registered
     */
    public static function has(string $name): bool {
        return isset(self::$registry[$name]);
    }
    
    /**
     * Get all registered components
     * 
     * @return array Component registry
     */
    public static function all(): array {
        return self::$registry;
    }
}

// Auto-register common components
ComponentFactory::register('button', 'BuzzarFeed\\Components\\Common\\Button');
ComponentFactory::register('input', 'BuzzarFeed\\Components\\Common\\Input');
ComponentFactory::register('card', 'BuzzarFeed\\Components\\Common\\Card');
ComponentFactory::register('modal', 'BuzzarFeed\\Components\\Common\\Modal');
