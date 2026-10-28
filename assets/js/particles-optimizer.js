/**
 * Particles.js Performance Optimizer
 * Optimizes particles.js configurations based on device capabilities
 */

class ParticlesOptimizer {
    constructor() {
        this.isMobile = window.innerWidth <= 768;
        this.isLowEnd = navigator.hardwareConcurrency <= 2;
        this.isReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.particlesConfig = null;
        
        this.init();
    }
    
    init() {
        // Wait for particles.js to be available
        if (typeof particlesJS !== 'undefined') {
            this.optimizeParticles();
        } else {
            // Wait for particles.js to load
            const checkParticles = setInterval(() => {
                if (typeof particlesJS !== 'undefined') {
                    clearInterval(checkParticles);
                    this.optimizeParticles();
                }
            }, 100);
            
            // Stop checking after 5 seconds
            setTimeout(() => clearInterval(checkParticles), 5000);
        }
    }
    
    getOptimizedConfig() {
        // Disable particles on mobile, low-end devices, or if user prefers reduced motion
        if (this.isMobile || this.isLowEnd || this.isReducedMotion) {
            return null;
        }
        
        // Optimized configuration for desktop
        return {
            "particles": {
                "number": {
                    "value": 30, // Reduced from typical 80-100
                    "density": {
                        "enable": true,
                        "value_area": 1000
                    }
                },
                "color": {
                    "value": ["#4cc9f0", "#7209b7", "#4361ee"]
                },
                "shape": {
                    "type": "circle",
                    "stroke": {
                        "width": 0,
                        "color": "#000000"
                    }
                },
                "opacity": {
                    "value": 0.3, // Reduced opacity
                    "random": true,
                    "anim": {
                        "enable": false // Disable opacity animation for performance
                    }
                },
                "size": {
                    "value": 3,
                    "random": true,
                    "anim": {
                        "enable": false // Disable size animation for performance
                    }
                },
                "line_linked": {
                    "enable": true,
                    "distance": 150,
                    "color": "#4cc9f0",
                    "opacity": 0.2, // Reduced opacity
                    "width": 1
                },
                "move": {
                    "enable": true,
                    "speed": 1, // Reduced speed
                    "direction": "none",
                    "random": false,
                    "straight": false,
                    "out_mode": "out",
                    "bounce": false,
                    "attract": {
                        "enable": false // Disable attraction for performance
                    }
                }
            },
            "interactivity": {
                "detect_on": "window",
                "events": {
                    "onhover": {
                        "enable": false // Disable hover for performance
                    },
                    "onclick": {
                        "enable": false // Disable click for performance
                    },
                    "resize": true
                }
            },
            "retina_detect": false // Disable retina detection for performance
        };
    }
    
    optimizeParticles() {
        const config = this.getOptimizedConfig();
        
        if (!config) {
            // Hide particles container if optimization disables particles
            const particlesContainer = document.getElementById('particles-js');
            if (particlesContainer) {
                particlesContainer.style.display = 'none';
            }
            return;
        }
        
        // Initialize optimized particles
        const particlesContainer = document.getElementById('particles-js');
        if (particlesContainer) {
            particlesJS('particles-js', config);
            
            // Monitor performance and adjust if needed
            this.monitorPerformance();
        }
    }
    
    monitorPerformance() {
        let frameCount = 0;
        let lastTime = performance.now();
        
        const checkPerformance = () => {
            frameCount++;
            const currentTime = performance.now();
            
            // Check FPS every 60 frames (approximately 1 second at 60fps)
            if (frameCount >= 60) {
                const fps = 1000 / ((currentTime - lastTime) / frameCount);
                
                // If FPS drops below 30, reduce particles
                if (fps < 30 && window.pJSDom && window.pJSDom[0]) {
                    this.reduceParticles();
                }
                
                frameCount = 0;
                lastTime = currentTime;
            }
            
            requestAnimationFrame(checkPerformance);
        };
        
        // Start monitoring after a delay
        setTimeout(() => {
            requestAnimationFrame(checkPerformance);
        }, 2000);
    }
    
    reduceParticles() {
        if (window.pJSDom && window.pJSDom[0] && window.pJSDom[0].pJS) {
            const pJS = window.pJSDom[0].pJS;
            
            // Reduce particle count
            if (pJS.particles.number.value > 15) {
                pJS.particles.number.value = Math.max(15, pJS.particles.number.value - 10);
                
                // Remove excess particles
                const excessParticles = pJS.particles.array.length - pJS.particles.number.value;
                if (excessParticles > 0) {
                    pJS.particles.array.splice(-excessParticles, excessParticles);
                }
                
                console.log('Particles reduced for performance:', pJS.particles.number.value);
            }
        }
    }
    
    // Method to completely disable particles if needed
    disableParticles() {
        const particlesContainer = document.getElementById('particles-js');
        if (particlesContainer) {
            particlesContainer.style.display = 'none';
        }
        
        if (window.pJSDom && window.pJSDom[0]) {
            window.pJSDom[0].pJS.fn.vendors.destroypJS();
            window.pJSDom = [];
        }
    }
    
    // Method to re-enable particles with optimized settings
    enableParticles() {
        const particlesContainer = document.getElementById('particles-js');
        if (particlesContainer) {
            particlesContainer.style.display = 'block';
            this.optimizeParticles();
        }
    }
}

// Initialize particles optimizer
const particlesOptimizer = new ParticlesOptimizer();

// Export for global use
window.ParticlesOptimizer = ParticlesOptimizer;
window.particlesOptimizer = particlesOptimizer;

// Handle visibility changes to pause/resume particles
document.addEventListener('visibilitychange', () => {
    if (window.pJSDom && window.pJSDom[0] && window.pJSDom[0].pJS) {
        const pJS = window.pJSDom[0].pJS;
        
        if (document.hidden) {
            // Pause particles when tab is not visible
            pJS.particles.move.enable = false;
        } else {
            // Resume particles when tab becomes visible
            pJS.particles.move.enable = true;
        }
    }
}); 