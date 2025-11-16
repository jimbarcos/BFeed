<?php
/**
 * BuzzarFeed - Input Component
 * 
 * Reusable form input component
 * Following ISO 9241: Reusability and Accessibility
 * 
 * @package BuzzarFeed\Components\Common
 * @version 1.0
 */

namespace BuzzarFeed\Components\Common;

use BuzzarFeed\Components\BaseComponent;
use BuzzarFeed\Utils\Helpers;

class Input extends BaseComponent {
    
    /**
     * Input types
     */
    const TYPE_TEXT = 'text';
    const TYPE_EMAIL = 'email';
    const TYPE_PASSWORD = 'password';
    const TYPE_NUMBER = 'number';
    const TYPE_TEL = 'tel';
    const TYPE_URL = 'url';
    const TYPE_DATE = 'date';
    const TYPE_TEXTAREA = 'textarea';
    
    /**
     * @var string Input type
     */
    protected string $type = self::TYPE_TEXT;
    
    /**
     * @var string Input name
     */
    protected string $name = '';
    
    /**
     * @var string Input value
     */
    protected string $value = '';
    
    /**
     * @var string Input placeholder
     */
    protected string $placeholder = '';
    
    /**
     * @var string|null Input label
     */
    protected ?string $label = null;
    
    /**
     * @var bool Whether input is required
     */
    protected bool $required = false;
    
    /**
     * @var bool Whether input is disabled
     */
    protected bool $disabled = false;
    
    /**
     * @var string|null Error message
     */
    protected ?string $error = null;
    
    /**
     * @var string|null Help text
     */
    protected ?string $help = null;
    
    /**
     * @var bool Whether to show password toggle
     */
    protected bool $showPasswordToggle = false;
    
    /**
     * Initialize component
     */
    protected function initialize(): void {
        parent::initialize();
        
        $this->type = $this->prop('type', self::TYPE_TEXT);
        $this->name = $this->prop('name', '');
        $this->value = $this->prop('value', '');
        $this->placeholder = $this->prop('placeholder', '');
        $this->label = $this->prop('label');
        $this->required = $this->prop('required', false);
        $this->disabled = $this->prop('disabled', false);
        $this->error = $this->prop('error');
        $this->help = $this->prop('help');
        
        // Accept both 'showToggle' and 'showPasswordToggle' for backward compatibility
        $this->showPasswordToggle = $this->prop('showPasswordToggle', $this->prop('showToggle', false));
        
        // Auto-generate ID if not provided and showPasswordToggle is true
        if ($this->showPasswordToggle && empty($this->id)) {
            $this->id = $this->name;
        }
    }
    
    /**
     * Render the input
     * 
     * @return string Rendered HTML
     */
    public function render(): string {
        $html = '<div class="form-group">';
        
        // Render label
        if ($this->label) {
            $html .= $this->renderLabel();
        }
        
        // Render input wrapper
        if ($this->type === self::TYPE_PASSWORD && $this->showPasswordToggle) {
            $html .= '<div class="password-wrapper">';
        }
        
        // Render input
        $html .= $this->type === self::TYPE_TEXTAREA 
            ? $this->renderTextarea() 
            : $this->renderInput();
        
        // Render password toggle
        if ($this->type === self::TYPE_PASSWORD && $this->showPasswordToggle) {
            $html .= $this->renderPasswordToggle();
            $html .= '</div>';
        }
        
        // Render error message
        if ($this->error) {
            $html .= '<span class="form-error">' . Helpers::escape($this->error) . '</span>';
        }
        
        // Render help text
        if ($this->help) {
            $html .= '<span class="form-help">' . Helpers::escape($this->help) . '</span>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render input element
     * 
     * @return string Rendered HTML
     */
    protected function renderInput(): string {
        $classes = 'form-input';
        if ($this->error) {
            $classes .= ' form-input-error';
        }
        if (!empty($this->classes)) {
            $classes .= ' ' . $this->classes;
        }
        
        $attributes = [];
        $attributes[] = 'type="' . Helpers::escape($this->type) . '"';
        $attributes[] = 'name="' . Helpers::escape($this->name) . '"';
        $attributes[] = 'class="' . Helpers::escape($classes) . '"';
        
        if (!empty($this->id)) {
            $attributes[] = 'id="' . Helpers::escape($this->id) . '"';
        }
        
        if (!empty($this->value)) {
            $attributes[] = 'value="' . Helpers::escape($this->value) . '"';
        }
        
        if (!empty($this->placeholder)) {
            $attributes[] = 'placeholder="' . Helpers::escape($this->placeholder) . '"';
        }
        
        if ($this->required) {
            $attributes[] = 'required';
        }
        
        if ($this->disabled) {
            $attributes[] = 'disabled';
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
        
        return "<input {$attrs}>";
    }
    
    /**
     * Render textarea element
     * 
     * @return string Rendered HTML
     */
    protected function renderTextarea(): string {
        $classes = 'form-input';
        if ($this->error) {
            $classes .= ' form-input-error';
        }
        if (!empty($this->classes)) {
            $classes .= ' ' . $this->classes;
        }
        
        $attributes = [];
        $attributes[] = 'name="' . Helpers::escape($this->name) . '"';
        $attributes[] = 'class="' . Helpers::escape($classes) . '"';
        
        if (!empty($this->id)) {
            $attributes[] = 'id="' . Helpers::escape($this->id) . '"';
        }
        
        if (!empty($this->placeholder)) {
            $attributes[] = 'placeholder="' . Helpers::escape($this->placeholder) . '"';
        }
        
        if ($this->required) {
            $attributes[] = 'required';
        }
        
        if ($this->disabled) {
            $attributes[] = 'disabled';
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
        
        return "<textarea {$attrs}>" . Helpers::escape($this->value) . "</textarea>";
    }
    
    /**
     * Render label element
     * 
     * @return string Rendered HTML
     */
    protected function renderLabel(): string {
        $for = !empty($this->id) ? ' for="' . Helpers::escape($this->id) . '"' : '';
        return '<label class="form-label"' . $for . '>' . Helpers::escape($this->label) . '</label>';
    }
    
    /**
     * Render password toggle button
     * 
     * @return string Rendered HTML
     */
    protected function renderPasswordToggle(): string {
        $inputId = $this->id ?: $this->name;
        return '<button type="button" class="password-toggle" onclick="togglePassword(\'' . 
               Helpers::escape($inputId) . '\')"><i class="fas fa-eye-slash" id="' . 
               Helpers::escape($inputId) . '-icon"></i></button>';
    }
}
