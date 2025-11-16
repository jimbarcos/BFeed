<?php
/**
 * BuzzarFeed - Modal Component
 * 
 * Reusable modal/dialog component
 * Following ISO 9241: Reusability and Accessibility
 * 
 * @package BuzzarFeed\Components\Common
 * @version 1.0
 */

namespace BuzzarFeed\Components\Common;

use BuzzarFeed\Components\BaseComponent;
use BuzzarFeed\Utils\Helpers;

class Modal extends BaseComponent {
    
    /**
     * Modal sizes
     */
    const SIZE_SMALL = 'sm';
    const SIZE_MEDIUM = 'md';
    const SIZE_LARGE = 'lg';
    
    /**
     * @var string Modal title
     */
    protected string $title = '';
    
    /**
     * @var string Modal content
     */
    protected string $content = '';
    
    /**
     * @var string Modal size
     */
    protected string $size = self::SIZE_MEDIUM;
    
    /**
     * @var bool Whether modal is closable
     */
    protected bool $closable = true;
    
    /**
     * @var bool Whether to show header
     */
    protected bool $showHeader = true;
    
    /**
     * @var bool Whether to show footer
     */
    protected bool $showFooter = false;
    
    /**
     * @var string|null Footer content
     */
    protected ?string $footer = null;
    
    /**
     * @var array Modal actions (buttons)
     */
    protected array $actions = [];
    
    /**
     * Initialize component
     */
    protected function initialize(): void {
        parent::initialize();
        
        $this->title = $this->prop('title', '');
        $this->content = $this->prop('content', '');
        $this->size = $this->prop('size', self::SIZE_MEDIUM);
        $this->closable = $this->prop('closable', true);
        $this->showHeader = $this->prop('showHeader', true);
        $this->showFooter = $this->prop('showFooter', false);
        $this->footer = $this->prop('footer');
        $this->actions = $this->prop('actions', []);
    }
    
    /**
     * Render the modal
     * 
     * @return string Rendered HTML
     */
    public function render(): string {
        $modalId = $this->id ?: 'modal-' . uniqid();
        $classes = $this->buildClasses();
        
        $html = '<div class="' . Helpers::escape($classes) . '" id="' . 
                Helpers::escape($modalId) . '" role="dialog" aria-modal="true">';
        
        $html .= '<div class="modal-backdrop" onclick="closeModal(\'' . 
                 Helpers::escape($modalId) . '\')"></div>';
        
        $html .= '<div class="modal-content">';
        
        // Render header
        if ($this->showHeader) {
            $html .= $this->renderHeader($modalId);
        }
        
        // Render body
        $html .= '<div class="modal-body">';
        $html .= $this->content;
        $html .= '</div>';
        
        // Render footer
        if ($this->showFooter || !empty($this->actions)) {
            $html .= $this->renderFooter();
        }
        
        $html .= '</div></div>';
        
        return $html;
    }
    
    /**
     * Render modal header
     * 
     * @param string $modalId Modal ID
     * @return string Rendered HTML
     */
    protected function renderHeader(string $modalId): string {
        $html = '<div class="modal-header">';
        $html .= '<h3 class="modal-title">' . Helpers::escape($this->title) . '</h3>';
        
        if ($this->closable) {
            $html .= '<button type="button" class="modal-close" ' .
                     'onclick="closeModal(\'' . Helpers::escape($modalId) . '\')" ' .
                     'aria-label="Close">' .
                     '<i class="fas fa-times"></i>' .
                     '</button>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render modal footer
     * 
     * @return string Rendered HTML
     */
    protected function renderFooter(): string {
        $html = '<div class="modal-footer">';
        
        if ($this->footer) {
            $html .= $this->footer;
        }
        
        if (!empty($this->actions)) {
            foreach ($this->actions as $action) {
                if ($action instanceof BaseComponent) {
                    $html .= $action->render();
                } else {
                    $html .= $action;
                }
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Build modal CSS classes
     * 
     * @return string CSS classes
     */
    protected function buildClasses(): string {
        $classes = ['modal'];
        
        // Add size class
        if ($this->size !== self::SIZE_MEDIUM) {
            $classes[] = 'modal-' . $this->size;
        }
        
        // Add custom classes
        if (!empty($this->classes)) {
            $classes[] = $this->classes;
        }
        
        return implode(' ', $classes);
    }
}
