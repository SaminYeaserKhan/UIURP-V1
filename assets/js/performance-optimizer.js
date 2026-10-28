/**
 * Performance Optimization Module
 * Handles throttling, debouncing, lazy loading, and performance monitoring
 */

class PerformanceOptimizer {
    constructor() {
        this.scrollListeners = new Map();
        this.resizeListeners = new Map();
        this.intersectionObservers = new Map();
        this.animationFrameId = null;
        this.isScrolling = false;
        this.scrollTimeout = null;
        
        // Performance monitoring
        this.performanceMetrics = {
            scrollEvents: 0,
            animationFrames: 0,
            domQueries: 0
        };
        
        this.init();
    }
    
    init() {
        this.setupScrollOptimization();
        this.setupLazyLoading();
        this.setupAnimationOptimization();
        this.setupMemoryOptimization();
    }
    
    /**
     * Optimized scroll handling with throttling and passive listeners
     */
    setupScrollOptimization() {
        let ticking = false;
        
        const optimizedScrollHandler = () => {
            if (!ticking) {
                requestAnimationFrame(() => {
                    this.handleScrollEvents();
                    ticking = false;
                    this.performanceMetrics.animationFrames++;
                });
                ticking = true;
            }
            this.performanceMetrics.scrollEvents++;
        };
        
        // Replace all scroll listeners with optimized version
        window.addEventListener('scroll', optimizedScrollHandler, { 
            passive: true,
            capture: false 
        });
        
        // Debounced scroll end detection
        window.addEventListener('scroll', () => {
            clearTimeout(this.scrollTimeout);
            this.isScrolling = true;
            
            this.scrollTimeout = setTimeout(() => {
                this.isScrolling = false;
                this.onScrollEnd();
            }, 150);
        }, { passive: true });
    }
    
    /**
     * Handle all scroll-related updates in a single RAF
     */
    handleScrollEvents() {
        const scrollY = window.pageYOffset;
        const windowHeight = window.innerHeight;
        
        // Update all registered scroll listeners efficiently
        this.scrollListeners.forEach((callback, id) => {
            try {
                callback(scrollY, windowHeight);
            } catch (error) {
                console.warn(`Scroll listener ${id} error:`, error);
            }
        });
    }
    
    /**
     * Register a scroll listener with automatic optimization
     */
    addScrollListener(id, callback) {
        this.scrollListeners.set(id, callback);
    }
    
    /**
     * Remove scroll listener
     */
    removeScrollListener(id) {
        this.scrollListeners.delete(id);
    }
    
    /**
     * Optimized lazy loading for images and content
     */
    setupLazyLoading() {
        // Lazy load images
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        imageObserver.unobserve(img);
                    }
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.01
        });
        
        // Observe all lazy images
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
        
        // Lazy load content sections
        const contentObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    element.classList.add('loaded');
                    
                    // Trigger any pending animations
                    if (element.dataset.animation) {
                        element.classList.add(element.dataset.animation);
                    }
                    
                    contentObserver.unobserve(element);
                }
            });
        }, {
            rootMargin: '100px 0px',
            threshold: 0.1
        });
        
        // Observe lazy content
        document.querySelectorAll('.lazy-content').forEach(el => {
            contentObserver.observe(el);
        });
        
        this.intersectionObservers.set('images', imageObserver);
        this.intersectionObservers.set('content', contentObserver);
    }
    
    /**
     * Optimize animations and transitions
     */
    setupAnimationOptimization() {
        // Reduce motion for users who prefer it
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.documentElement.style.setProperty('--transition', 'none');
            document.documentElement.style.setProperty('--transition-smooth', 'none');
            document.documentElement.style.setProperty('--transition-bounce', 'none');
        }
        
        // Optimize CSS animations
        this.optimizeAnimations();
        
        // Setup visibility-based animation control
        this.setupVisibilityBasedAnimations();
    }
    
    /**
     * Optimize CSS animations and transitions
     */
    optimizeAnimations() {
        const style = document.createElement('style');
        style.textContent = `
            /* Performance optimized animations */
            .optimized-animation {
                will-change: transform, opacity;
                transform: translateZ(0);
                backface-visibility: hidden;
                perspective: 1000px;
            }
            
            .optimized-animation.complete {
                will-change: auto;
            }
            
            /* Reduce heavy effects on mobile */
            @media (max-width: 768px) {
                .floating-orb,
                .cyber-grid,
                .particles-js-canvas-el {
                    display: none !important;
                }
                
                .card::before,
                .card::after {
                    display: none !important;
                }
                
                * {
                    animation-duration: 0.3s !important;
                    transition-duration: 0.3s !important;
                }
            }
            
            /* Optimize scroll-triggered animations */
            .scroll-optimized {
                transform: translate3d(0, 0, 0);
                will-change: transform;
            }
            
            .scroll-optimized.idle {
                will-change: auto;
            }
        `;
        document.head.appendChild(style);
    }
    
    /**
     * Control animations based on element visibility
     */
    setupVisibilityBasedAnimations() {
        const animationObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const element = entry.target;
                
                if (entry.isIntersecting) {
                    element.classList.add('optimized-animation');
                    element.classList.remove('idle');
                } else {
                    element.classList.add('idle');
                    // Clean up completed animations
                    setTimeout(() => {
                        if (element.classList.contains('idle')) {
                            element.classList.add('complete');
                        }
                    }, 1000);
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0
        });
        
        // Observe animated elements
        document.querySelectorAll('.fade-in-up, .card, .project-card, [data-aos]').forEach(el => {
            animationObserver.observe(el);
        });
        
        this.intersectionObservers.set('animations', animationObserver);
    }
    
    /**
     * Memory optimization and cleanup
     */
    setupMemoryOptimization() {
        // Clean up unused event listeners
        this.onScrollEnd = this.debounce(() => {
            // Clean up will-change properties
            document.querySelectorAll('.scroll-optimized').forEach(el => {
                el.classList.add('idle');
            });
            
            // Force garbage collection hint
            if (window.gc && typeof window.gc === 'function') {
                window.gc();
            }
        }, 500);
        
        // Monitor memory usage
        if ('memory' in performance) {
            setInterval(() => {
                const memory = performance.memory;
                if (memory.usedJSHeapSize > memory.jsHeapSizeLimit * 0.9) {
                    console.warn('High memory usage detected, cleaning up...');
                    this.cleanup();
                }
            }, 30000); // Check every 30 seconds
        }
    }
    
    /**
     * Debounce utility
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    /**
     * Throttle utility
     */
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    /**
     * Optimize AOS configuration
     */
    optimizeAOS() {
        if (typeof AOS !== 'undefined') {
            // Disable AOS on mobile for better performance
            const isMobile = window.innerWidth <= 768;
            
            AOS.init({
                duration: isMobile ? 300 : 600,
                once: true, // Only animate once for better performance
                mirror: false, // Disable mirror for better performance
                offset: 50,
                easing: 'ease-out',
                anchorPlacement: 'top-bottom',
                disable: isMobile ? 'mobile' : false,
                debounceDelay: 50,
                throttleDelay: 100
            });
            
            // Replace AOS scroll handler with optimized version
            this.addScrollListener('aos', this.throttle(() => {
                if (!isMobile) {
                    AOS.refresh();
                }
            }, 100));
        }
    }
    
    /**
     * Optimize particles.js
     */
    optimizeParticles() {
        if (typeof particlesJS !== 'undefined') {
            const isMobile = window.innerWidth <= 768;
            const isLowEnd = navigator.hardwareConcurrency <= 2;
            
            if (isMobile || isLowEnd) {
                // Disable particles on mobile/low-end devices
                const particlesContainer = document.getElementById('particles-js');
                if (particlesContainer) {
                    particlesContainer.style.display = 'none';
                }
                return;
            }
            
            // Optimize particles configuration
            const optimizedConfig = {
                particles: {
                    number: { value: 30 }, // Reduced from default
                    size: { value: 3 },
                    move: {
                        enable: true,
                        speed: 1, // Reduced speed
                        out_mode: "out"
                    },
                    opacity: {
                        value: 0.3 // Reduced opacity
                    }
                },
                interactivity: {
                    detect_on: "window",
                    events: {
                        onhover: { enable: false }, // Disable hover for performance
                        onclick: { enable: false }, // Disable click for performance
                        resize: true
                    }
                },
                retina_detect: false // Disable retina detection for performance
            };
            
            // Apply optimized config
            if (window.pJSDom && window.pJSDom[0]) {
                window.pJSDom[0].pJS.particles.number.value = 30;
                window.pJSDom[0].pJS.particles.move.speed = 1;
                window.pJSDom[0].pJS.interactivity.events.onhover.enable = false;
                window.pJSDom[0].pJS.interactivity.events.onclick.enable = false;
            }
        }
    }
    
    /**
     * Clean up resources
     */
    cleanup() {
        // Clear all observers
        this.intersectionObservers.forEach(observer => {
            observer.disconnect();
        });
        
        // Clear listeners
        this.scrollListeners.clear();
        this.resizeListeners.clear();
        
        // Cancel animation frames
        if (this.animationFrameId) {
            cancelAnimationFrame(this.animationFrameId);
        }
        
        // Clear timeouts
        if (this.scrollTimeout) {
            clearTimeout(this.scrollTimeout);
        }
    }
    
    /**
     * Get performance metrics
     */
    getMetrics() {
        return {
            ...this.performanceMetrics,
            memoryUsage: performance.memory ? {
                used: Math.round(performance.memory.usedJSHeapSize / 1024 / 1024),
                total: Math.round(performance.memory.totalJSHeapSize / 1024 / 1024),
                limit: Math.round(performance.memory.jsHeapSizeLimit / 1024 / 1024)
            } : null
        };
    }
}

// Initialize performance optimizer
const performanceOptimizer = new PerformanceOptimizer();

// Export for global use
window.PerformanceOptimizer = PerformanceOptimizer;
window.performanceOptimizer = performanceOptimizer;

// Auto-optimize common libraries
document.addEventListener('DOMContentLoaded', () => {
    // Optimize AOS if present
    performanceOptimizer.optimizeAOS();
    
    // Optimize particles if present
    setTimeout(() => {
        performanceOptimizer.optimizeParticles();
    }, 1000);
}); 