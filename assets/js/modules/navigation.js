/**
 * BuzzarFeed - Navigation Module
 * 
 * Mobile menu and navigation functionality
 * Following ISO 9241: Modularity and Maintainability
 * 
 * @package BuzzarFeed
 * @version 2.0
 */

export class Navigation {
    constructor() {
        this.init();
    }
    
    init() {
        this.menuToggle = document.querySelector('.mobile-menu-toggle');
        this.navMenu = document.querySelector('.nav-menu');
        this.navLinks = document.querySelectorAll('.nav-link');
        
        if (!this.menuToggle || !this.navMenu) return;
        
        this.bindEvents();
    }
    
    bindEvents() {
        // Toggle menu on button click
        this.menuToggle.addEventListener('click', () => {
            this.toggleMenu();
        });
        
        // Close menu when clicking on a nav link
        this.navLinks.forEach(link => {
            link.addEventListener('click', () => {
                this.closeMenu();
            });
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', (event) => {
            if (!this.navMenu.contains(event.target) && 
                !this.menuToggle.contains(event.target)) {
                this.closeMenu();
            }
        });
    }
    
    toggleMenu() {
        this.navMenu.classList.toggle('active');
        this.menuToggle.classList.toggle('active');
        document.body.style.overflow = this.navMenu.classList.contains('active') ? 'hidden' : '';
    }
    
    closeMenu() {
        this.navMenu.classList.remove('active');
        this.menuToggle.classList.remove('active');
        document.body.style.overflow = '';
    }
}
