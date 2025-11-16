/**
 * BuzzarFeed - Main Application (Modular)
 * 
 * Main entry point using ES6 modules
 * Following ISO 9241: Modularity and Maintainability
 * 
 * @package BuzzarFeed
 * @version 2.0
 */

import { Carousel } from './modules/carousel.js';
import { Navigation } from './modules/navigation.js';
import { ScrollManager } from './modules/scroll.js';
import { Utils } from './modules/utils.js';

class BuzzarFeedApp {
    constructor() {
        this.init();
    }
    
    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.onReady());
        } else {
            this.onReady();
        }
    }
    
    onReady() {
        // Initialize navigation
        this.navigation = new Navigation();
        
        // Initialize carousel
        const carouselElement = document.querySelector('.brands-carousel');
        if (carouselElement) {
            this.carousel = new Carousel('.brands-carousel', {
                autoPlay: true,
                autoPlayInterval: 5000
            });
        }
        
        // Initialize scroll manager
        this.scrollManager = new ScrollManager();
        
        // Log initialization
        this.logWelcome();
    }
    
    logWelcome() {
        console.log(
            '%c BuzzarFeed ', 
            'background: #4A8B4F; color: white; font-size: 16px; font-weight: bold; padding: 8px;'
        );
        console.log(
            '%c Welcome to BuzzarFeed! ', 
            'color: #E8663E; font-size: 14px;'
        );
        console.log('Discover the flavors of BGC Night Market 🍔🍕🍜');
    }
}

// Initialize application
const app = new BuzzarFeedApp();

// Export for testing and external access
export default app;
