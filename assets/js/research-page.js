
  document.addEventListener('DOMContentLoaded', function() {
        // Initialize theme FIRST before other components
        initializeTheme();
        
        // Initialize enhanced particles
        initializeEnhancedParticles();
        
        // Initialize enhanced AOS
        initializeEnhancedAOS();
        
        // Initialize scroll animations
        initializeScrollAnimations();
        
        // Initialize enhanced search functionality
        initializeEnhancedSearch();
        
        // Initialize ripple effects
        initializeRippleEffects();
        
        // Initialize performance optimizations
        initializePerformanceOptimizations();
    });

    function initializeEnhancedParticles() {
        // Get current theme
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        
        // Theme-specific particle configurations
        const particleConfig = {
            dark: {
                colors: ["#2563eb", "#0ea5e9", "#06b6d4", "#3b82f6"],
                strokeColor: "rgba(37, 99, 235, 0.3)",
                lineColor: "#2563eb",
                opacity: 0.3,
                lineOpacity: 0.2
            },
            light: {
                colors: ["#1e40af", "#0369a1", "#0891b2", "#1d4ed8"],
                strokeColor: "rgba(30, 64, 175, 0.2)",
                lineColor: "#1e40af",
                opacity: 0.15,
                lineOpacity: 0.1
            }
        };
        
        const config = particleConfig[currentTheme];
        
        particlesJS('particles-js', {
            "particles": {
                "number": {
                    "value": 60,
                    "density": {
                        "enable": true,
                        "value_area": 1000
                    }
                },
                "color": {
                    "value": config.colors
                },
                "shape": {
                    "type": "circle",
                    "stroke": {
                        "width": 1,
                        "color": config.strokeColor
                    }
                },
                "opacity": {
                    "value": config.opacity,
                    "random": true,
                    "anim": {
                        "enable": true,
                        "speed": 1,
                        "opacity_min": config.opacity * 0.3,
                        "sync": false
                    }
                },
                "size": {
                    "value": 4,
                    "random": true,
                    "anim": {
                        "enable": true,
                        "speed": 2,
                        "size_min": 1,
                        "sync": false
                    }
                },
                "line_linked": {
                    "enable": true,
                    "distance": 150,
                    "color": config.lineColor,
                    "opacity": config.lineOpacity,
                    "width": 1.5,
                    "shadow": {
                        "enable": true,
                        "color": config.lineColor,
                        "blur": 5
                    }
                },
                "move": {
                    "enable": true,
                    "speed": 1.5,
                    "direction": "none",
                    "random": true,
                    "straight": false,
                    "out_mode": "bounce",
                    "bounce": true,
                    "attract": {
                        "enable": true,
                        "rotateX": 300,
                        "rotateY": 600
                    }
                }
            },
            "interactivity": {
                "detect_on": "window",
                "events": {
                    "onhover": {
                        "enable": true,
                        "mode": "grab"
                    },
                    "onclick": {
                        "enable": true,
                        "mode": "push"
                    },
                    "resize": true
                },
                "modes": {
                    "grab": {
                        "distance": 200,
                        "line_linked": {
                            "opacity": config.lineOpacity * 3
                        }
                    },
                    "bubble": {
                        "distance": 250,
                        "size": 8,
                        "duration": 2,
                        "opacity": config.opacity * 2,
                        "speed": 3
                    },
                    "repulse": {
                        "distance": 200,
                        "duration": 0.4
                    },
                    "push": {
                        "particles_nb": 4
                    },
                    "remove": {
                        "particles_nb": 2
                    }
                }
            },
            "retina_detect": true
        });
    }

    function initializeEnhancedAOS() {
        // Use performance optimizer for AOS if available
        if (window.performanceOptimizer) {
            window.performanceOptimizer.optimizeAOS();
        } else {
            // Fallback optimized AOS configuration
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
        }
    }

    function initializeScrollAnimations() {
        // Initialize hero background scroll effects
        initializeHeroScrollEffects();
        
        // Intersection Observer for fade-in-up animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        // Observe all fade-in-up elements
        document.querySelectorAll('.fade-in-up').forEach(el => {
            observer.observe(el);
        });

        // Use performance optimizer for parallax if available
        if (window.performanceOptimizer) {
            window.performanceOptimizer.addScrollListener('parallax', (scrollY) => {
                // Only update parallax on desktop for performance
                if (window.innerWidth > 768) {
                    document.querySelectorAll('.floating-orb').forEach((orb, index) => {
                        const speed = 0.3 + (index * 0.1);
                        orb.style.transform = `translateY(${scrollY * speed}px)`;
                    });
                }
            });
        } else {
            // Fallback parallax with throttling
        let parallaxTicking = false;
        
        function updateParallax() {
                if (window.innerWidth > 768) { // Only on desktop
            const scrolled = window.pageYOffset;
            
            document.querySelectorAll('.floating-orb').forEach((orb, index) => {
                const speed = 0.3 + (index * 0.1);
                orb.style.transform = `translateY(${scrolled * speed}px)`;
            });
                }
            parallaxTicking = false;
        }

        window.addEventListener('scroll', function() {
            if (!parallaxTicking) {
                requestAnimationFrame(updateParallax);
                parallaxTicking = true;
            }
        }, { passive: true });
        }
    }

    function initializeHeroScrollEffects() {
        const heroSection = document.getElementById('hero-section');
        if (!heroSection) return;

        function updateHeroBackground(scrollY) {
            const heroHeight = heroSection.offsetHeight;
            const scrollProgress = Math.min(scrollY / (heroHeight * 0.8), 1);

            // Remove existing scroll classes
            heroSection.classList.remove('scrolled-small', 'scrolled-medium', 'scrolled-large');

            // Apply scroll classes based on scroll progress
            if (scrollProgress > 0.6) {
                heroSection.classList.add('scrolled-large');
            } else if (scrollProgress > 0.4) {
                heroSection.classList.add('scrolled-medium');
            } else if (scrollProgress > 0.2) {
                heroSection.classList.add('scrolled-small');
            }

            // Additional smooth transformations (only on desktop)
            if (window.innerWidth > 768) {
            const layers = heroSection.querySelectorAll('.bg-layer-1, .bg-layer-2, .bg-layer-3');
            layers.forEach((layer, index) => {
                const layerSpeed = (index + 1) * 0.1;
                const translateY = scrollY * layerSpeed;
                const scale = 1 - (scrollProgress * 0.3 * (index + 1) / layers.length);
                
                if (!heroSection.classList.contains('scrolled-small', 'scrolled-medium', 'scrolled-large')) {
                    layer.style.transform = `translateY(${translateY}px) scale(${scale})`;
                }
            });
            }
        }

        // Use performance optimizer if available
        if (window.performanceOptimizer) {
            window.performanceOptimizer.addScrollListener('hero', updateHeroBackground);
        } else {
            // Fallback with throttling
            let scrollTicking = false;

        function handleScroll() {
            if (!scrollTicking) {
                    requestAnimationFrame(() => {
                        updateHeroBackground(window.pageYOffset);
                        scrollTicking = false;
                    });
                scrollTicking = true;
            }
        }

        window.addEventListener('scroll', handleScroll, { passive: true });
        }
        
        // Initialize on load
        updateHeroBackground(window.pageYOffset);
    }
      
    function initializeEnhancedSearch() {
      const projectsList = document.getElementById('projectsList');
      const searchBar = document.getElementById('search-bar');
      const searchButton = document.getElementById('search-bttn');
      const toggleButton = document.getElementById('toggle-projects');
      const toggleSortButton = document.getElementById('toggle-sort');
      const toggleRecommendedButton = document.getElementById('toggle-recommended');
      const loader = document.getElementById('loader');
      const exploreBtn = document.querySelector('.hero-actions .btn-primary');
      
      let showAllProjects = false;
      let sortByClicks = false;
      let showRecommended = (document.body.getAttribute("data-show-recommended") === "true");
      let searchTimeout;

        // Explore Projects button functionality
        if (exploreBtn) {
            exploreBtn.addEventListener('click', function() {
                // Add animation
                this.style.transform = 'scale(0.95)';
      setTimeout(() => {
                    this.style.transform = '';
                }, 150);
                
                // Scroll to projects with smooth animation
                projectsList.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            });
        }

        // Enhanced search with debouncing
        if (searchBar) {
            searchBar.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                
                if (query.length > 2) {
                    searchTimeout = setTimeout(() => {
                        performSearch(query);
      }, 300);
                } else if (query.length === 0) {
                    loadProjects('src/model/fetch_projects.php?limit=15');
                }
            });
        }

        // Enhanced search button with animation
        if (searchButton) {
            searchButton.addEventListener('click', function() {
                const query = searchBar.value.trim();
                if (query) {
                    // Add pulse animation
                    this.style.animation = 'pulse 0.6s ease-in-out';
                  setTimeout(() => {
                        this.style.animation = '';
                    }, 600);
                    
                    performSearch(query);
                }
            });
        }

        // Enhanced enter key support
        if (searchBar) {
            searchBar.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const query = this.value.trim();
                    if (query) {
                        performSearch(query);
                    }
              }
          });
      }

        // Enhanced toggle functionality
        if (toggleButton) {
            toggleButton.addEventListener('click', function() {
                showRecommended = false;
                showAllProjects = !showAllProjects;
                
                // Update button text with animation
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.innerHTML = showAllProjects ? 
                        '<i class="fas fa-filter me-2"></i>Show Public Projects Only' : 
                        '<i class="fas fa-filter me-2"></i>Show All Projects';
                    this.style.transform = 'scale(1)';
                }, 150);
                
                // Reset other buttons and highlight this one
                resetButtonStates();
                this.style.background = 'linear-gradient(135deg, var(--modern-blue), var(--modern-purple))';
                this.style.color = 'white';
                
                // Clear search input and reload default projects
                if (searchBar) {
                    searchBar.value = '';
                }
                
                const endpoint = 'src/model/fetch_projects.php?limit=50';
                loadProjects(endpoint, false);
            });
        }

        // Helper function to reset button states
        function resetButtonStates() {
            [toggleRecommendedButton, toggleSortButton, toggleButton].forEach(btn => {
                if (btn) {
                    btn.style.background = '';
                    btn.style.color = '';
                }
            });
        }

        // Function to load personalized projects
        function loadPersonalizedProjects() {
            showLoader();
            
            fetch('src/model/fetch_recommended_projects.php?limit=50')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    setTimeout(() => {
                        if (data.projects) {
                            displayProjects(data.projects, data.is_personalized);
                        } else {
                            displayProjects(data, false);
                        }
                        hideLoader();
                        
                        // Smooth scroll to results
                        projectsList.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'start' 
                        });
                    }, 300);
                })
                .catch(error => {
                    console.error('Load personalized projects error:', error);
                    hideLoader();
                    // Fallback to regular projects
                    loadProjects('src/model/fetch_projects.php?limit=50', false);
                });
        }

        // Recommended toggle functionality
        if (toggleRecommendedButton) {
            toggleRecommendedButton.addEventListener('click', function() {
                showRecommended = true;
                sortByClicks = false;
                
                // Update button text with animation
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
                
                // Reset other buttons
                resetButtonStates();
                this.style.background = 'linear-gradient(135deg, var(--modern-blue), var(--modern-purple))';
                this.style.color = 'white';
                
                // Clear search input and load recommended projects
                if (searchBar) {
                    searchBar.value = '';
                }
                
                loadPersonalizedProjects();
            });
        }

        // Sort toggle functionality
        if (toggleSortButton) {
            toggleSortButton.addEventListener('click', function() {
                showRecommended = false;
                sortByClicks = !sortByClicks;
                
                // Update button text with animation
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.innerHTML = sortByClicks ? 
                        '<i class="fas fa-sort me-2"></i>Sort by Views' : 
                        '<i class="fas fa-sort me-2"></i>Sort by Newest';
                    this.style.transform = 'scale(1)';
                }, 150);
                
                // Reset other buttons and highlight this one
                resetButtonStates();
                this.style.background = 'linear-gradient(135deg, var(--modern-blue), var(--modern-purple))';
                this.style.color = 'white';
                
                // Clear search input and reload projects with new sorting
                if (searchBar) {
                    searchBar.value = '';
                }
                
                const endpoint = sortByClicks ? 'src/model/fetch_projects_by_clicks.php' : 'src/model/fetch_projects.php?limit=50';
                loadProjects(endpoint, false);
            });
        }
      
      function showLoader() {
          loader.style.display = 'flex';
          projectsList.innerHTML = '';
            
            // Add skeleton loading
            const skeletonCount = 6;
            for (let i = 0; i < skeletonCount; i++) {
                const skeleton = createSkeletonCard();
                skeleton.style.animationDelay = `${i * 0.1}s`;
                projectsList.appendChild(skeleton);
            }
      }
      
      function hideLoader() {
          loader.style.display = 'none';
      }
      
        function createSkeletonCard() {
            const card = document.createElement('div');
            card.className = 'skeleton-card fade-in-up';
            card.innerHTML = `
                <div class="skeleton-item" style="height: 200px; margin-bottom: 1rem;"></div>
                <div class="skeleton-item skeleton-title"></div>
                <div class="skeleton-item skeleton-text"></div>
                <div class="skeleton-item skeleton-text short"></div>
                <div class="skeleton-item skeleton-text"></div>
            `;
            return card;
        }

        function performSearch(query) {
          const searchEndpoint = 'src/model/search_projects.php';
          
          // Reset recommendation state when searching
          showRecommended = false;
          resetButtonStates();
          
          // Update URL with search parameter
          const newUrl = new URL(window.location);
          if (query && query.trim()) {
              newUrl.searchParams.set('search', query.trim());
          } else {
              newUrl.searchParams.delete('search');
          }
          window.history.replaceState({}, '', newUrl);
              
          showLoader();
            
          fetch(searchEndpoint, {
              method: 'POST',
              headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ searchString: query })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
              .then(data => {
                setTimeout(() => {
                  displayProjects(data, false);
                  hideLoader();
                  
                    // Smooth scroll to results
                    projectsList.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start' 
                    });
                }, 300); // Small delay for better UX
            })
            .catch(error => {
                console.error('Search error:', error);
                hideLoader();
                showErrorMessage('Search failed. Please try again.');
            });
        }

        function loadProjects(url, sortByClicks = false) {
            // Clear search parameter from URL when loading default projects
            const newUrl = new URL(window.location);
            newUrl.searchParams.delete('search');
            window.history.replaceState({}, '', newUrl);
            
            showLoader();
            
            // Use the clicks-based endpoint if sortByClicks is true
            const finalUrl = sortByClicks ? 'src/model/fetch_projects_by_clicks.php' : url;
            
            fetch(finalUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                  setTimeout(() => {
                        displayProjects(data, false);
                        hideLoader();
                  }, 300);
              })
              .catch(error => {
                    console.error('Load projects error:', error);
                  hideLoader();
                    showErrorMessage('Failed to load projects. Please try again.');
                });
        }

        function showErrorMessage(message) {
              projectsList.innerHTML = `
                <div class="no-results fade-in-up">
                    <div class="no-results-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                  </div>
                    <p class="no-results-text">${message}</p>
                </div>
            `;
        }

        function displayProjects(projects, isPersonalized = false) {
            projectsList.innerHTML = '';
            
            if (projects && projects.length > 0) {
                const uniqueProjects = removeDuplicateProjects(projects);
                
                uniqueProjects.forEach((project, index) => {
                    const projectCard = createProjectCard(project, index, isPersonalized);
                    projectsList.appendChild(projectCard);
                });
                
                // Reinitialize animations
                  setTimeout(() => {
                    AOS.refresh();
                    initializeCardAnimations();
                }, 100);
                
            } else {
                const message = showRecommended && isPersonalized === false ? 
                    "Keep interacting with projects to get personalized recommendations!" :
                    "No projects found. Try different keywords or browse all available projects.";
                    
                projectsList.innerHTML = `
                    <div class="no-results fade-in-up">
                        <div class="no-results-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <p class="no-results-text">${message}</p>
                    </div>
                `;
            }
        }

        function removeDuplicateProjects(projects) {
            const uniqueProjects = [];
            const projectIds = new Set();
            
            projects.forEach(project => {
                const id = project._id && project._id.$oid ? project._id.$oid : project._id;
                if (!projectIds.has(id)) {
                    projectIds.add(id);
                    uniqueProjects.push(project);
                }
            });
            
            return uniqueProjects;
        }

        function createProjectCard(project, index, isPersonalized = false) {
            const card = document.createElement('div');
            card.className = 'project-card fade-in-up';
            card.setAttribute('data-aos', 'fade-up');
            card.setAttribute('data-aos-delay', Math.min(index * 100, 600));
            
            // Handle project ID format properly
            let projectId = '';
            if (project._id) {
                if (typeof project._id === 'object' && project._id.$oid) {
                    projectId = project._id.$oid;
                } else {
                    projectId = project._id;
                }
            }
            
            // Format project data
            const formattedDate = formatDate(project.createdAt);
            const authorsList = formatAuthors(project.members);
            const supervisorName = formatSupervisor(project.supervisor);
            const imageSrc = getProjectImage(project.coverImage);
            const badgeInfo = getProjectBadge(project.privacy);
            
            // Add tracking metadata including personalization info
            const trackingData = {
                title: project.title,
                category: project.category || 'Research',
                field: project.field || 'Research',
                keywords: project.keywords || [],
                tags: [project.category || 'Research', project.field || 'Research'].filter(Boolean),
                relevance_score: project.relevance_score || 0,
                is_recommended: project.is_recommended || false,
                is_personalized: isPersonalized
            };
            
            card.innerHTML = `
                <a href="Project_details.php?id=${projectId}" class="text-decoration-none" 
                   data-item-type="project" 
                   data-item-id="${projectId}"
                   data-tracking-metadata='${JSON.stringify(trackingData)}'></a>
                <div class="card-image">
                    <img src="${imageSrc}" alt="${project.title}" loading="lazy" onerror="this.src='assets/resources/research_picture/pub_1.jpg'">
                </div>
                <div class="card-content">
                    <div class="project-badge ${badgeInfo.class}">${badgeInfo.text}</div>
                    <h3 class="card-title">${project.title}</h3>
                    <p class="card-description">${truncateText(project.abstract || project.description || 'No description available', 120)}</p>
                    <div class="project-meta">
                        <div class="meta-item">
                            <i class="meta-icon far fa-calendar-alt"></i>
                            <span>${formattedDate}</span>
                        </div>
                        <div class="meta-item">
                            <i class="meta-icon fas fa-chalkboard-teacher"></i>
                            <span>Supervisor: ${supervisorName}</span>
                        </div>
                        <div class="meta-item">
                            <i class="meta-icon fas fa-users"></i>
                            <span>${authorsList}</span>
                        </div>
                        <div class="meta-item">
                            <i class="meta-icon fas fa-graduation-cap"></i>
                            <span>${project.field || 'Research'}</span>
                        </div>
                    </div>
                </div>
            `;
            
            return card;
        }

        // Utility functions
      function formatDate(dateInput) {
          if (!dateInput) return 'Not specified';
          
          try {
                let dateValue = dateInput;
                
                if (typeof dateInput === 'object' && dateInput.$date) {
                          dateValue = dateInput.$date;
                }
                
              const date = new Date(dateValue);
              
              if (isNaN(date.getTime())) {
                  return 'Date not available';
              }
              
              return date.toLocaleDateString('en-US', { 
                  year: 'numeric', 
                  month: 'long', 
                  day: 'numeric' 
              });
          } catch (error) {
                console.error('Date formatting error:', error);
              return 'Date not available';
          }
      }

        function formatAuthors(members) {
            if (!members || !members.length) return 'No authors listed';
            
            return members
                .map(member => member.name || member.role || 'Author')
                .join(', ');
        }

        function formatSupervisor(supervisor) {
            if (!supervisor) return 'Not specified';
            
            if (typeof supervisor === 'string') return supervisor;
            if (typeof supervisor === 'object') {
                return supervisor.name || supervisor.userId || 'Not specified';
            }
            
            return 'Not specified';
        }

        function getProjectImage(coverImage) {
            const defaultImages = [
                  'assets/resources/research_picture/pub_1.jpg',
                  'assets/resources/research_picture/pub_2.jpg',
                  'assets/resources/research_picture/pub_3.jpg',
                  'assets/resources/research_picture/pub_4.jpg',
                  'assets/resources/research_picture/pub_5.jpg',
                  'assets/resources/research_picture/pub_6.jpg',
                  'assets/resources/research_picture/pub_7.jpg',
                  'assets/resources/research_picture/pub_8.jpeg',
                  'assets/resources/research_picture/pub_9.jpeg',
                  'assets/resources/research_picture/pub_10.jpeg'
              ];

            if (coverImage && coverImage.url && coverImage.url.trim()) {
                return coverImage.url;
            }
            
            return defaultImages[Math.floor(Math.random() * defaultImages.length)];
        }

        function getProjectBadge(privacy) {
            if (typeof privacy === 'undefined') {
                return { class: 'badge-secondary', text: 'Unknown' };
            }
            
            return privacy === 0 ? 
                { class: 'badge-public', text: 'Public' } : 
                { class: 'private', text: 'Private' };
        }

        function truncateText(text, maxLength) {
            if (!text || text.length <= maxLength) return text || '';
            return text.substring(0, maxLength) + '...';
        }

        // Initialize button states based on default sorting
        function initializeButtonStates() {
            if (showRecommended && toggleRecommendedButton) {
                toggleRecommendedButton.style.background = 'linear-gradient(135deg, var(--modern-blue), var(--modern-purple))';
                toggleRecommendedButton.style.color = 'white';
            }
        }

        // Initialize with URL search parameter or default projects
        const urlParams = new URLSearchParams(window.location.search);
        const urlSearchQuery = urlParams.get('search');
        
        if (urlSearchQuery && urlSearchQuery.trim()) {
            // Auto-search if URL has search parameter
            setTimeout(() => {
                performSearch(urlSearchQuery.trim());
            }, 100);
        } else {
            // Load default projects based on user state
            if (showRecommended) {
                setTimeout(() => {
                    loadPersonalizedProjects();
                    initializeButtonStates();
                }, 100);
            } else {
                setTimeout(() => {
                    loadProjects('src/model/fetch_projects.php?limit=50', false);
                }, 100);
            }
        }
    }

    function initializeCardAnimations() {
        // Enhanced hover effects for project cards
        document.querySelectorAll('.project-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                // Add dynamic glow effect
                this.style.boxShadow = `
                    0 25px 50px rgba(0, 0, 0, 0.3),
                    0 0 40px rgba(37, 99, 235, 0.4),
                    inset 0 1px 0 rgba(255, 255, 255, 0.1)
                `;
            });

            card.addEventListener('mouseleave', function() {
                this.style.boxShadow = '';
            });

            // Add click animation
            card.addEventListener('click', function(e) {
                // Create ripple effect
                const rect = this.getBoundingClientRect();
                const ripple = document.createElement('div');
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.position = 'absolute';
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.style.background = 'rgba(37, 99, 235, 0.3)';
                ripple.style.borderRadius = '50%';
                ripple.style.pointerEvents = 'none';
                ripple.style.transform = 'scale(0)';
                ripple.style.animation = 'ripple-animation 0.6s ease-out';
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    }

    function initializeRippleEffects() {
        // Add ripple effect CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple-animation {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
    }

    function initializePerformanceOptimizations() {
        // Lazy loading for images
        if ('IntersectionObserver' in window) {
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
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }

        // Preload critical resources
        const criticalResources = [
            'assets/resources/research_picture/pub_1.jpg',
            'assets/resources/research_picture/pub_2.jpg',
            'assets/resources/research_picture/pub_3.jpg'
        ];

        criticalResources.forEach(resource => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'image';
            link.href = resource;
            document.head.appendChild(link);
        });

        // Optimize scroll performance
        let scrollTimeout;
        window.addEventListener('scroll', function() {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                // Cleanup any expired animations
                document.querySelectorAll('.fade-in-up.visible').forEach(el => {
                    el.style.willChange = 'auto';
                });
            }, 150);
        }, { passive: true });
    }

    // Initialize enhanced interactions on page load
    window.addEventListener('load', function() {
        // Add entrance animation to hero
        document.querySelector('.hero-section').style.opacity = '0';
        document.querySelector('.hero-section').style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            document.querySelector('.hero-section').style.transition = 'all 1s ease-out';
            document.querySelector('.hero-section').style.opacity = '1';
            document.querySelector('.hero-section').style.transform = 'translateY(0)';
        }, 100);

        // Initialize card animations after a short delay
        setTimeout(initializeCardAnimations, 500);
    });

    // Theme Management System
    function initializeTheme() {
        // Get saved theme from localStorage or default to dark
        const savedTheme = localStorage.getItem('theme') || 'dark';
        
        // Apply theme to document
        document.documentElement.setAttribute('data-theme', savedTheme);
        
        // Listen for theme changes from navbar
        document.addEventListener('themeChanged', function(e) {
            // Reinitialize particles with new theme
            initializeEnhancedParticles();
            
            // Add transition effect
            document.body.style.transition = 'all 0.4s ease';
            setTimeout(() => {
                document.body.style.transition = '';
            }, 400);
        });
    }

    // Listen for theme changes from other pages/tabs
    window.addEventListener('storage', function(e) {
        if (e.key === 'theme') {
            const newTheme = e.newValue || 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            // Reinitialize particles with new theme
            initializeEnhancedParticles();
        }
    });
  