document.addEventListener('DOMContentLoaded', function() {
         // Apply keyword style and hover effects to dynamically added elements
         const applyKeywordStyles = function() {
             const keywordSpans = document.querySelectorAll('#keywordsList span:not(.popular-label)');
             keywordSpans.forEach(span => {
                 // Add the keyword-tag class if not already present
                 if (!span.classList.contains('keyword-tag')) {
                     span.classList.add('keyword-tag');
                     
                     // Add click handler if not already added
                     if (!span._hasClickHandler) {
                         span._hasClickHandler = true;
                         span.addEventListener('click', function() {
                             const searchBar = document.getElementById('search-bar');
                             if (searchBar) {
                                 searchBar.value = this.textContent.trim();
                                 searchBar.focus();
                             }
                         });
                     }
                 }
             });
         };
         
         // Run initially
         setTimeout(applyKeywordStyles, 500);
         
         // Run periodically to catch dynamically added keywords
         setInterval(applyKeywordStyles, 2000);
         
         // Initialize particles for search background
         if (typeof particlesJS !== 'undefined') {
            particlesJS('search-particles', {
                "particles": {
                    "number": {
                        "value": 40,
                        "density": {
                            "enable": true,
                            "value_area": 800
                        }
                    },
                    "color": {
                        "value": "#4cc9f0"
                    },
                    "shape": {
                        "type": "circle"
                    },
                    "opacity": {
                        "value": 0.3,
                        "random": true
                    },
                    "size": {
                        "value": 2,
                        "random": true
                    },
                    "line_linked": {
                        "enable": true,
                        "distance": 150,
                        "color": "#4cc9f0",
                        "opacity": 0.2,
                        "width": 1
                    },
                    "move": {
                        "enable": true,
                        "speed": 1,
                        "direction": "none",
                        "random": true,
                        "straight": false,
                        "out_mode": "out",
                        "bounce": false
                    }
                },
                "interactivity": {
                    "detect_on": "canvas",
                    "events": {
                        "onhover": {
                            "enable": true,
                            "mode": "grab"
                        },
                        "onclick": {
                            "enable": false
                        },
                        "resize": true
                    },
                    "modes": {
                        "grab": {
                            "distance": 140,
                            "line_linked": {
                                "opacity": 0.5
                            }
                        }
                    }
                },
                "retina_detect": true
            });
        }
        
        // Add hover animation to search input
        const searchBar = document.getElementById('search-bar');
        if (searchBar) {
            searchBar.addEventListener('focus', function() {
                document.querySelector('.futuristic-search-bar').classList.add('focused');
            });
            
            searchBar.addEventListener('blur', function() {
                document.querySelector('.futuristic-search-bar').classList.remove('focused');
            });
        }
        
        // Add ripple effect to search button
        const searchButton = document.getElementById('search-bttn');
        if (searchButton) {
            searchButton.addEventListener('click', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const ripple = document.createElement('span');
                ripple.className = 'ripple-effect';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        }
    });

// ----

document.addEventListener('DOMContentLoaded', function() {
        // Project filter functionality
        const filterButtons = document.querySelectorAll('.filter-btn');
        const projectItems = document.querySelectorAll('.project-item');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Get filter value
                const filterValue = this.getAttribute('data-filter');
                
                // Filter projects
                projectItems.forEach(item => {
                    if (filterValue === 'all' || item.getAttribute('data-category') === filterValue) {
                        item.style.display = 'block';
                        setTimeout(() => {
                            item.style.opacity = '1';
                            item.style.transform = 'translateY(0)';
                        }, 100);
                    } else {
                        item.style.opacity = '0';
                        item.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            item.style.display = 'none';
                        }, 300);
                    }
                });
            });
        });
        
        // Add hover effect to cards
        const cards = document.querySelectorAll('.futuristic-card');
        
        cards.forEach(card => {
            card.addEventListener('mousemove', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                // Calculate rotation based on mouse position
                const rotateY = ((x / rect.width) - 0.5) * 5; // -2.5 to 2.5 degrees
                const rotateX = ((y / rect.height) - 0.5) * -5; // 2.5 to -2.5 degrees
                
                // Apply subtle 3D rotation
                this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-10px)`;
            });
            
            card.addEventListener('mouseleave', function() {
                // Reset transformation
                this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateY(0)';
            });
        });
    });

// ----

document.addEventListener('DOMContentLoaded', function() {
                    // Fetch faculty data from the same endpoint used in Faculty_Page.php
                    fetch('src/model/load_faculty.php')
                        .then(response => response.json())
                        .then(data => {
                            if (!data || data.length === 0) {
                                document.getElementById('randomFacultyList').innerHTML = '<p class="text-center text-light">No faculty members found.</p>';
                                return;
                            }
                            
                            // Shuffle the array to get random faculty
                            const shuffledFaculty = shuffleArray([...data]);
                            
                            // Take only the first 4 entries (or fewer if less than 4 are available)
                            const selectedFaculty = shuffledFaculty.slice(0, 4);
                            
                            // Clear loading spinner
                            document.getElementById('randomFacultyList').innerHTML = '';
                            
                            // Generate HTML for each faculty card with staggered animation delay
                            selectedFaculty.forEach((faculty, index) => {
                                const profileImage = faculty.profile_image ? faculty.profile_image : 'assets/resources/imgPlaceholder.png';
                                
                                // Create specialty display (use first field of research if available)
                                let specialty = '';
                                if (faculty.interested_fields_of_research && faculty.interested_fields_of_research.length > 0) {
                                    specialty = faculty.interested_fields_of_research[0];
                                } else {
                                    specialty = 'Research Faculty';
                                }
                                
                                // Calculate animation delay based on index
                                const animDelay = 100 + (index * 150);
                                
                                // Create faculty card
                                const facultyCard = `
                                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="${animDelay}">
                                    <div class="neo-faculty-card" data-faculty-id="${faculty._id}" style="cursor: pointer;">
                                        <div class="card-border"></div>
                                        <div class="faculty-img-wrapper">
                            <div class="faculty-img-container" id="img-container-${index}">
                                                <img src="${profileImage}" alt="${faculty.name}" class="img-fluid" onload="handleImageLoad(${index})" onerror="handleImageError(${index})">
                                                <div class="img-overlay"></div>
                            </div>
                                            <div class="faculty-specialty-badge">
                                                <span>${specialty}</span>
                                </div>
                            </div>
                            <div class="faculty-info">
                                                <h4 class="faculty-name">${faculty.name}</h4>
                                                <p class="faculty-position">Faculty Member</p>
                                    <div class="faculty-quote">
                                                    <q>${faculty.bio ? (faculty.bio.length > 100 ? faculty.bio.substring(0, 100) + '...' : faculty.bio) : 'Research faculty at UIU.'}</q>
                                    </div>
                            </div>
                            </div>
                        </div>
                                `;
                                
                                document.getElementById('randomFacultyList').insertAdjacentHTML('beforeend', facultyCard);
                            });
                            
                            // Simple card interactions
                            setTimeout(() => {
                                const facultyCards = document.querySelectorAll('.neo-faculty-card');
                                facultyCards.forEach(card => {
                                    // Add click handler to redirect to faculty profile
                                    card.addEventListener('click', function(e) {
                                        const facultyId = this.getAttribute('data-faculty-id');
                                        if (facultyId) {
                                            window.location.href = `Faculty_Profile.php?id=${facultyId}`;
                                        }
                                    });
                                    
                                    // Add hover effect for better UX
                                    card.addEventListener('mouseenter', function() {
                                        this.style.cursor = 'pointer';
                                    });
                                });
                            }, 500);
                        })
                        .catch(error => {
                            document.getElementById('randomFacultyList').innerHTML = `<p class="text-danger">Failed to load faculty data. Please try again later.</p>`;
                            console.error('Error loading faculty data:', error);
                        });
                    
                    // Function to shuffle an array (Fisher-Yates algorithm)
                    function shuffleArray(array) {
                        for (let i = array.length - 1; i > 0; i--) {
                            const j = Math.floor(Math.random() * (i + 1));
                            [array[i], array[j]] = [array[j], array[i]];
                        }
                        return array;
                    }
                    
                    // Image loading handlers
                    window.handleImageLoad = function(index) {
                        const container = document.getElementById(`img-container-${index}`);
                        if (container) {
                            container.classList.add('loaded');
                        }
                    };
                    
                    window.handleImageError = function(index) {
                        const container = document.getElementById(`img-container-${index}`);
                        if (container) {
                            const img = container.querySelector('img');
                            if (img) {
                                img.src = 'assets/resources/imgPlaceholder.png';
                                img.onload = () => container.classList.add('loaded');
                            }
                        }
                    };
                });

// ----

document.addEventListener('DOMContentLoaded', function() {
    // Add parallax effect to event cards
    const eventCards = document.querySelectorAll('.neo-event-card');
    
    eventCards.forEach(card => {
        card.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // Calculate rotation values based on mouse position
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            // Limit the rotation angles to prevent extreme 3D transforms
            const deltaX = Math.min(Math.max((x - centerX) / 25, -5), 5);
            const deltaY = Math.min(Math.max((y - centerY) / 25, -5), 5);
            
            // Apply 3D rotation with limited values to avoid overlapping
            this.style.transform = `perspective(1000px) rotateX(${-deltaY}deg) rotateY(${deltaX}deg) translateY(-5px)`;
            
            // Move glow to follow cursor
            const glow = this.querySelector('.card-glow');
            if (glow) {
                glow.style.background = `radial-gradient(circle at ${x}px ${y}px, rgba(247, 37, 133, 0.3), transparent 60%)`;
                glow.style.opacity = '1';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            // Reset transforms and effects - ensure it properly resets to default state
            this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
            
            const glow = this.querySelector('.card-glow');
            if (glow) {
                glow.style.background = 'radial-gradient(circle at center, rgba(247, 37, 133, 0.2), transparent 70%)';
                glow.style.opacity = '0';
            }
        });
        
        // Add entry animation with a more controlled approach
        const delay = Array.from(eventCards).indexOf(card) * 100;
        card.style.animation = `card-float 0.8s ease-out ${delay}ms forwards`;
        card.style.opacity = '0';
        
        // Reset animation after it completes to prevent lingering effects
        setTimeout(() => {
            card.style.opacity = '1';
            // Ensure position is stable after animation
            if (!card.matches(':hover')) {
                card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
            }
        }, delay + 1000);
    });
});

// ----

document.addEventListener('DOMContentLoaded', () => {
            const sections = Array.from(document.querySelectorAll('.search-section, .neo-hero-section, .for-you-section, .featured-projects, .faculty-spotlight, .events-section, #faq-section, #footer-section'));
            const scrollDots = Array.from(document.querySelectorAll('.scroll-dot'));
            
            let isScrolling = false;
            let currentSectionIndex = 0;
            let scrollTimeout = null;
            let lastScrollTime = 0;
            let wheelAccumulator = 0;
            let isHoveringBulletinBoard = false;
            
            // Debounce settings
            const SCROLL_THRESHOLD = 50; // Minimum wheel delta to trigger scroll
            const SCROLL_DEBOUNCE = 150; // ms to wait before allowing another scroll
            const SCROLL_LOCK_TIME = 1000; // ms to lock scrolling during animation
            
            function setActiveDot(idx) {
                scrollDots.forEach((dot, i) => dot.classList.toggle('active', i === idx));
                currentSectionIndex = idx;
            }

            function scrollToSection(idx) {
                if (idx < 0 || idx >= sections.length || idx === currentSectionIndex) return;
                
                isScrolling = true;
                currentSectionIndex = idx;
                
                // Clear any existing timeout
                if (scrollTimeout) {
                    clearTimeout(scrollTimeout);
                }
                
                // Scroll to section
                sections[idx].scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
                
                setActiveDot(idx);
                
                // Lock scrolling for animation duration
                scrollTimeout = setTimeout(() => {
                    isScrolling = false;
                    wheelAccumulator = 0; // Reset accumulator
                }, SCROLL_LOCK_TIME);
            }

            // Improved section detection using viewport center
            function getCurrentSectionIndex() {
                const viewportCenter = window.innerHeight / 2;
                let closestIndex = 0;
                let closestDistance = Infinity;
                
                sections.forEach((section, index) => {
                    const rect = section.getBoundingClientRect();
                    const sectionCenter = rect.top + (rect.height / 2);
                    const distance = Math.abs(sectionCenter - viewportCenter);
                    
                    if (distance < closestDistance) {
                        closestDistance = distance;
                        closestIndex = index;
                    }
                });
                
                return closestIndex;
            }

            // Enhanced wheel event handler with accumulation and debouncing
            function handleWheelScroll(e) {
                // Ignore if modifier keys are pressed
                if (e.ctrlKey || e.altKey || e.shiftKey) return;
                
                // Allow normal scrolling if hovering over bulletin board
                if (isHoveringBulletinBoard) {
                    return; // Don't prevent default, allow normal scrolling
                }
                
                // Ignore if currently scrolling
                if (isScrolling) {
                    e.preventDefault();
                    return;
                }
                
                const now = Date.now();
                
                // Debounce rapid scroll events
                if (now - lastScrollTime < SCROLL_DEBOUNCE) {
                    e.preventDefault();
                    return;
                }
                
                // Accumulate wheel delta for better sensitivity control
                wheelAccumulator += e.deltaY;
                
                // Only trigger scroll if accumulated delta exceeds threshold
                if (Math.abs(wheelAccumulator) < SCROLL_THRESHOLD) {
                    e.preventDefault();
                    return;
                }
                
                // Determine scroll direction
                const direction = wheelAccumulator > 0 ? 1 : -1;
                const targetIndex = currentSectionIndex + direction;
                
                // Check if target section exists
                if (targetIndex >= 0 && targetIndex < sections.length) {
                    e.preventDefault();
                    lastScrollTime = now;
                    wheelAccumulator = 0; // Reset accumulator
                    scrollToSection(targetIndex);
                } else {
                    // Reset accumulator if we can't scroll further
                    wheelAccumulator = 0;
                }
            }

            // Dot click navigation
            scrollDots.forEach((dot, i) => {
                dot.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (!isScrolling) {
                        scrollToSection(i);
                    }
                });
            });

            // Enhanced IntersectionObserver for better section detection
            const observerOptions = {
                root: null,
                rootMargin: '-20% 0px -20% 0px', // Only trigger when section is well within viewport
                threshold: [0, 0.25, 0.5, 0.75, 1.0]
            };
            
            const observer = new IntersectionObserver((entries) => {
                if (isScrolling) return; // Don't update during programmatic scrolling
                
                let mostVisibleSection = null;
                let maxVisibility = 0;
                
                entries.forEach((entry) => {
                    if (entry.isIntersecting && entry.intersectionRatio > maxVisibility) {
                        maxVisibility = entry.intersectionRatio;
                        mostVisibleSection = entry.target;
                    }
                });
                
                if (mostVisibleSection) {
                    const idx = sections.indexOf(mostVisibleSection);
                    if (idx !== -1 && idx !== currentSectionIndex) {
                        setActiveDot(idx);
                    }
                }
            }, observerOptions);

            // Observe all sections
            sections.forEach((section) => observer.observe(section));

            // Add wheel event listener
            window.addEventListener('wheel', handleWheelScroll, { passive: false });

            // Bulletin board hover detection to disable scroll snapping
            const bulletinBoard = document.querySelector('.bulletin-board-container');
            if (bulletinBoard) {
                bulletinBoard.addEventListener('mouseenter', () => {
                    isHoveringBulletinBoard = true;
                    // Also disable CSS scroll snap temporarily
                    document.documentElement.style.scrollSnapType = 'none';
                });
                
                bulletinBoard.addEventListener('mouseleave', () => {
                    isHoveringBulletinBoard = false;
                    // Re-enable CSS scroll snap
                    document.documentElement.style.scrollSnapType = 'y mandatory';
                });
            }
            
            // Handle keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (isScrolling) return;
                
                let targetIndex = -1;
                
                switch(e.key) {
                    case 'ArrowDown':
                    case 'PageDown':
                        targetIndex = currentSectionIndex + 1;
                        break;
                    case 'ArrowUp':
                    case 'PageUp':
                        targetIndex = currentSectionIndex - 1;
                        break;
                    case 'Home':
                        targetIndex = 0;
                        break;
                    case 'End':
                        targetIndex = sections.length - 1;
                        break;
                }
                
                if (targetIndex >= 0 && targetIndex < sections.length && targetIndex !== currentSectionIndex) {
                    e.preventDefault();
                    scrollToSection(targetIndex);
                }
            });
            
            // Handle browser back/forward navigation
            window.addEventListener('popstate', () => {
                if (!isScrolling) {
                    const hash = window.location.hash;
                    if (hash) {
                        const targetSection = document.querySelector(hash);
                        if (targetSection) {
                            const idx = sections.indexOf(targetSection);
                            if (idx !== -1) {
                                scrollToSection(idx);
                            }
                        }
                    }
                }
            });
            
            // Initialize - detect current section on load
            setTimeout(() => {
                const initialIndex = getCurrentSectionIndex();
                setActiveDot(initialIndex);
            }, 100);
            
            // Handle window resize - recalculate current section
            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    if (!isScrolling) {
                        const newIndex = getCurrentSectionIndex();
                        setActiveDot(newIndex);
                    }
                }, 250);
            });
        });

// ----

document.addEventListener('DOMContentLoaded', function() {
            const badgeLink = document.querySelector('.neo-badge-link');
            if (badgeLink) {
                badgeLink.addEventListener('click', function(e) {
                    window.location.href = 'Faculty_Page.php';
                });
            }
        });

// ----

document.addEventListener('DOMContentLoaded', function() {
      // Add animation to FAQ accordion items
      const setupFaqAnimations = () => {
          const accordionItems = document.querySelectorAll('.neo-accordion .accordion-item');
          
          accordionItems.forEach((item, index) => {
              // Add staggered animation
              const delay = 100 + (index * 50);
              item.style.animation = `slide-in 0.6s ease-out ${delay}ms forwards`;
              item.style.opacity = '0';
              
              // Add event listeners for button interactions
              const button = item.querySelector('.accordion-button');
              
              button.addEventListener('click', function() {
                  // Remove active glow from all items
                  accordionItems.forEach(i => i.classList.remove('active-glow'));
                  
                  // Add active glow to clicked item if it's expanded
                  if (this.classList.contains('collapsed')) {
                      setTimeout(() => {
                          item.classList.add('active-glow');
                      }, 100);
                  }
              });
          });
      };
      
      // Set up observer to run the animation when fetching is complete
      const observer = new MutationObserver((mutations) => {
          mutations.forEach((mutation) => {
              if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                  // Check if FAQs have been loaded
                  if (document.querySelectorAll('.neo-accordion .accordion-item').length > 0) {
                      setupFaqAnimations();
                      observer.disconnect(); // Stop observing once FAQs are loaded
                  }
              }
          });
      });
      
      // Start observing the accordion containers
      observer.observe(document.getElementById('faqAccordionLeft'), { childList: true });
      observer.observe(document.getElementById('faqAccordionRight'), { childList: true });
      
      // Create interactive particle background effect
      const createParticleEffect = () => {
          const faqSection = document.getElementById('faq-section');
          if (!faqSection) return;
          
          // Create canvas for particles
          const canvas = document.createElement('canvas');
          canvas.classList.add('faq-particles-canvas');
          canvas.style.position = 'absolute';
          canvas.style.top = '0';
          canvas.style.left = '0';
          canvas.style.width = '100%';
          canvas.style.height = '100%';
          canvas.style.pointerEvents = 'none';
          canvas.style.zIndex = '1';
          canvas.style.opacity = '0.4';
          
          faqSection.insertBefore(canvas, faqSection.firstChild);
          
          // Set canvas size
          const resizeCanvas = () => {
              canvas.width = faqSection.offsetWidth;
              canvas.height = faqSection.offsetHeight;
          };
          
          resizeCanvas();
          window.addEventListener('resize', resizeCanvas);
          
          // Create particles
          const ctx = canvas.getContext('2d');
          const particles = [];
          
          class Particle {
              constructor() {
                  this.x = Math.random() * canvas.width;
                  this.y = Math.random() * canvas.height;
                  this.size = Math.random() * 2 + 0.5;
                  this.speedX = Math.random() * 0.5 - 0.25;
                  this.speedY = Math.random() * 0.5 - 0.25;
                  this.color = Math.random() > 0.5 ? 
                      `rgba(76, 201, 240, ${Math.random() * 0.5 + 0.2})` : 
                      `rgba(247, 37, 133, ${Math.random() * 0.5 + 0.2})`;
              }
              
              update() {
                  this.x += this.speedX;
                  this.y += this.speedY;
                  
                  if (this.x < 0 || this.x > canvas.width) this.speedX *= -1;
                  if (this.y < 0 || this.y > canvas.height) this.speedY *= -1;
              }
              
              draw() {
                  ctx.fillStyle = this.color;
                  ctx.beginPath();
                  ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                  ctx.fill();
              }
          }
          
          const initParticles = () => {
              for (let i = 0; i < 50; i++) {
                  particles.push(new Particle());
              }
          };
          
          const animateParticles = () => {
              ctx.clearRect(0, 0, canvas.width, canvas.height);
              
              for (let i = 0; i < particles.length; i++) {
                  particles[i].update();
                  particles[i].draw();
                  
                  // Connect particles with lines
                  for (let j = i; j < particles.length; j++) {
                      const dx = particles[i].x - particles[j].x;
                      const dy = particles[i].y - particles[j].y;
                      const distance = Math.sqrt(dx * dx + dy * dy);
                      
                      if (distance < 100) {
                          ctx.beginPath();
                          ctx.strokeStyle = `rgba(114, 9, 183, ${0.1 * (1 - distance / 100)})`;
                          ctx.lineWidth = 0.2;
                          ctx.moveTo(particles[i].x, particles[i].y);
                          ctx.lineTo(particles[j].x, particles[j].y);
                          ctx.stroke();
                      }
                  }
              }
              
              requestAnimationFrame(animateParticles);
          };
          
          initParticles();
          animateParticles();
      };
      
      // Initialize the particle effect
      createParticleEffect();
  });

// ----

document.addEventListener('DOMContentLoaded', function() {
      const searchBar = document.getElementById('search-bar');
      const keywordsList = document.getElementById('keywordsList');
      
      // Fetch and display the frequently searched keywords
      fetch('src/model/fetch_keywords.php')
          .then(response => response.json())
          .then(data => {
              // Save the popular label before clearing
              const popularLabel = keywordsList.querySelector('.popular-searches');
              keywordsList.innerHTML = '';
              
              // Re-add popular label
              if (popularLabel) {
                  keywordsList.appendChild(popularLabel);
              }
              
              data.forEach(keyword => {
                  const span = document.createElement('span');
                  span.textContent = keyword.name;
                  span.classList.add('keyword-tag');
                  span.addEventListener('click', () => {
                      searchBar.value = keyword.name;
                      handleSearch(keyword.name);
                  });
                  keywordsList.appendChild(span);
              });
          });

      // Handle search input - redirect to Research_page.php with search parameter
      searchBar.addEventListener('keypress', function(event) {
          if (event.key === 'Enter') {
              const searchString = searchBar.value.trim();
              if (searchString) {
                  // Redirect to Research_page.php with search parameter
                  const searchUrl = `Research_page.php?search=${encodeURIComponent(searchString)}`;
                  window.location.href = searchUrl;
              }
          }
      });

      // Fetch and display the FAQs
      fetch('src/model/fetch_faqs.php')
          .then(response => response.json())
          .then(data => {
              const faqAccordionLeft = document.getElementById('faqAccordionLeft');
              const faqAccordionRight = document.getElementById('faqAccordionRight');
              faqAccordionLeft.innerHTML = '';
              faqAccordionRight.innerHTML = '';
              data.forEach((faq, index) => {
                  const accordionItem = document.createElement('div');
                  accordionItem.className = 'accordion-item m-4';
                  accordionItem.innerHTML = `
                      <h2 class="accordion-header" id="heading${index}">
                          <button class="accordion-button ${index !== 0 ? 'collapsed' : ''}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${index}" aria-expanded="${index === 0}" aria-controls="collapse${index}">
                              ${faq.question}
                          </button>
                      </h2>
                      <div id="collapse${index}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" aria-labelledby="heading${index}">
                          <div class="accordion-body">
                              ${faq.answer}
                          </div>
                      </div>
                  `;
                  if (index % 2 === 0) {
                      faqAccordionLeft.appendChild(accordionItem);
                  } else {
                      faqAccordionRight.appendChild(accordionItem);
                  }
              });
          });

      // Fetch and apply the background image
      fetch('src/model/fetch_image.php?name=uiurp_homepage_background')
          .then(response => response.json())
          .then(data => {
              const searchSection = document.getElementById('search');
              if (data.url) {
                  searchSection.style.backgroundImage = `url('${data.url}')`;
                  searchSection.style.backgroundSize = 'cover';
                  searchSection.style.backgroundPosition = 'center';
              }
                });

            // Initialize AOS
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: false,
                mirror: true,
                offset: 50
            });
            
            // Initialize stats counter
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const targetCount = parseInt(stat.getAttribute('data-count'), 10);
                let count = 0;
                const increment = Math.ceil(targetCount / 50);
                const interval = setInterval(() => {
                    count += increment;
                    if (count >= targetCount) {
                        count = targetCount;
                        clearInterval(interval);
                    }
                    stat.textContent = count;
                }, 30);
          });
  });

// ----

// Preloader
        window.addEventListener('load', function() {
            document.querySelector('.preloader').classList.add('loaded');
            
            // Initialize particles.js for hero section
            if (typeof particlesJS !== 'undefined') {
                particlesJS('particles-js', {
                    "particles": {
                        "number": {
                            "value": 50,
                            "density": {
                                "enable": true,
                                "value_area": 800
                            }
                        },
                        "color": {
                            "value": ["#4cc9f0", "#7209b7", "#4361ee", "#f72585"]
                        },
                        "shape": {
                            "type": "circle"
                        },
                        "opacity": {
                            "value": 0.5,
                            "random": true,
                            "anim": {
                                "enable": true,
                                "speed": 1,
                                "opacity_min": 0.1,
                                "sync": false
                            }
                        },
                        "size": {
                            "value": 3,
                            "random": true,
                            "anim": {
                                "enable": true,
                                "speed": 2,
                                "size_min": 0.1,
                                "sync": false
                            }
                        },
                        "line_linked": {
                            "enable": true,
                            "distance": 150,
                            "color": "#4cc9f0",
                            "opacity": 0.2,
                            "width": 1
                        },
                        "move": {
                            "enable": true,
                            "speed": 1,
                            "direction": "none",
                            "random": true,
                            "straight": false,
                            "out_mode": "out",
                            "bounce": false,
                            "attract": {
                                "enable": true,
                                "rotateX": 600,
                                "rotateY": 1200
                            }
                        }
                    },
                    "interactivity": {
                        "detect_on": "canvas",
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
                                "distance": 140,
                                "line_linked": {
                                    "opacity": 0.5
                                }
                            },
                            "push": {
                                "particles_nb": 3
                            }
                        }
                    },
                    "retina_detect": true
                });
            }
            
            // Initialize stat counters
            const statCounters = document.querySelectorAll('.stat-value[data-counter]');
            statCounters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-counter'));
                let count = 0;
                const suffix = counter.nextElementSibling?.classList.contains('counter-suffix') ? counter.nextElementSibling.textContent : '';
                const decimal = target % 1 !== 0;
                const increment = decimal ? target / 50 : Math.ceil(target / 50);
                const duration = 2000; // 2 seconds
                const interval = duration / 50;
                
                const counterAnimation = setInterval(() => {
                    count += increment;
                    if (count >= target) {
                        count = target;
                        clearInterval(counterAnimation);
                    }
                    counter.textContent = decimal ? count.toFixed(1) : Math.floor(count);
                }, interval);
            });
            
            // Add mousemove parallax effect to hero visual
            const heroVisual = document.querySelector('.neo-hero-visual');
            if (heroVisual) {
                document.addEventListener('mousemove', (e) => {
                    const moveX = (e.clientX - window.innerWidth / 2) * 0.01;
                    const moveY = (e.clientY - window.innerHeight / 2) * 0.01;
                    
                    const mainVisual = document.querySelector('.main-visual');
                    if (mainVisual) {
                        mainVisual.style.transform = `translate(calc(-50% + ${moveX * 2}px), calc(-50% + ${moveY * 2}px))`;
                    }
                    
                    const floatElements = document.querySelectorAll('.float-element');
                    floatElements.forEach((element, index) => {
                        const factor = (index + 1) * 0.4;
                        element.style.transform = `translate(${moveX * factor}px, ${moveY * factor}px)`;
                    });
                });
            }
            
            // Smooth scroll from search section to hero section
            const scrollToHero = document.getElementById('scrollToHero');
            if (scrollToHero) {
                scrollToHero.addEventListener('click', function() {
                    const heroSection = document.getElementById('hero-section');
                    if (heroSection) {
                        heroSection.scrollIntoView({ 
                            behavior: 'smooth'
                        });
                    }
                });
                
                // Make the entire scroll-down-container clickable
                const scrollDownContainer = document.querySelector('.scroll-down-container');
                if (scrollDownContainer) {
                    scrollDownContainer.addEventListener('click', function() {
                        const heroSection = document.getElementById('hero-section');
                        if (heroSection) {
                            heroSection.scrollIntoView({ 
                                behavior: 'smooth' 
                            });
                        }
                    });
                }
            }
        });

        // Initialize AOS animations
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof AOS !== 'undefined') {
                AOS.init({
                    duration: 1000,
                    easing: 'ease-in-out',
                    once: true,
                    mirror: false
                });
            }
            
            // Initialize stats counter
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const targetCount = parseInt(stat.getAttribute('data-count'), 10);
                let count = 0;
                const increment = Math.ceil(targetCount / 50);
                const interval = setInterval(() => {
                    count += increment;
                    if (count >= targetCount) {
                        count = targetCount;
                        clearInterval(interval);
                    }
                    stat.textContent = count;
                }, 30);
            });
            
            // Initialize research impact chart
            if (typeof Chart !== 'undefined') {
                const ctx = document.getElementById('researchImpactChart');
                if (ctx) {
                    // Set Chart.js default options for futuristic look
                    Chart.defaults.font.family = "'Inter', 'Poppins', sans-serif";
                    Chart.defaults.color = "rgba(255, 255, 255, 0.8)";
                    
                    // Create advanced gradients with multiple color stops
                    const gradientPublication = ctx.getContext('2d').createLinearGradient(0, 0, 0, 600);
                    gradientPublication.addColorStop(0, 'rgba(67, 97, 238, 0.95)');
                    gradientPublication.addColorStop(0.5, 'rgba(67, 97, 238, 0.5)');
                    gradientPublication.addColorStop(1, 'rgba(67, 97, 238, 0.02)');
                    
                    const gradientCitation = ctx.getContext('2d').createLinearGradient(0, 0, 0, 600);
                    gradientCitation.addColorStop(0, 'rgba(114, 9, 183, 0.95)');
                    gradientCitation.addColorStop(0.5, 'rgba(114, 9, 183, 0.5)');
                    gradientCitation.addColorStop(1, 'rgba(114, 9, 183, 0.02)');
                    
                    const gradientFunding = ctx.getContext('2d').createLinearGradient(0, 0, 0, 600);
                    gradientFunding.addColorStop(0, 'rgba(76, 201, 240, 0.95)');
                    gradientFunding.addColorStop(0.5, 'rgba(76, 201, 240, 0.5)');
                    gradientFunding.addColorStop(1, 'rgba(76, 201, 240, 0.02)');
                    
                    // Glowing effects for points
                    const createPointStyles = (color) => {
                        return {
                            pointRadius: 6,
                            pointBackgroundColor: color,
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 8,
                            pointHoverBackgroundColor: color,
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 3,
                            pointShadowBlur: 10,
                            pointShadowColor: color
                        };
                    };
                    
                    // Dataset definitions with animation delays
                    const researchChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: ['2019', '2020', '2021', '2022', '2023'],
                            datasets: [
                                {
                                    label: 'Publications',
                                    data: [35, 45, 60, 75, 85],
                                    backgroundColor: gradientPublication,
                                    borderColor: 'rgba(67, 97, 238, 1)',
                                    borderWidth: 3,
                                    fill: true,
                                    tension: 0.4,
                                    ...createPointStyles('#4361ee'),
                                    pointStyle: 'circle',
                                    cubicInterpolationMode: 'monotone',
                                    spanGaps: true
                                },
                                {
                                    label: 'Citations',
                                    data: [150, 210, 280, 350, 420],
                                    backgroundColor: gradientCitation,
                                    borderColor: 'rgba(114, 9, 183, 1)',
                                    borderWidth: 3,
                                    fill: true,
                                    tension: 0.4,
                                    ...createPointStyles('#7209b7'),
                                    pointStyle: 'rectRounded',
                                    cubicInterpolationMode: 'monotone',
                                    spanGaps: true
                                },
                                {
                                    label: 'Research Funding ($M)',
                                    data: [2.5, 5.0, 7.5, 10.0, 12.5],
                                    backgroundColor: gradientFunding,
                                    borderColor: 'rgba(76, 201, 240, 1)',
                                    borderWidth: 3,
                                    fill: true,
                                    tension: 0.4,
                                    ...createPointStyles('#4cc9f0'),
                                    pointStyle: 'star',
                                    cubicInterpolationMode: 'monotone',
                                    spanGaps: true
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    enabled: true,
                                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                    titleFont: {
                                        size: 14,
                                        weight: 'bold',
                                        family: "'Inter', sans-serif"
                                    },
                                    bodyFont: {
                                        size: 13,
                                        family: "'Inter', sans-serif"
                                    },
                                    borderColor: 'rgba(255, 255, 255, 0.2)',
                                    borderWidth: 1,
                                    displayColors: true,
                                    boxPadding: 8,
                                    cornerRadius: 8,
                                    padding: 12,
                                    usePointStyle: true,
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            if (context.parsed.y !== null) {
                                                if (context.dataset.label === 'Research Funding ($M)') {
                                                    label += '$' + context.parsed.y + 'M';
                                                } else {
                                                    label += context.parsed.y;
                                                }
                                            }
                                            return label;
                                        },
                                        labelTextColor: function(context) {
                                            return context.dataset.borderColor;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        color: 'rgba(255, 255, 255, 0.05)',
                                        borderDash: [5, 5],
                                        drawBorder: false,
                                        tickLength: 0
                                    },
                                    ticks: {
                                        font: {
                                            size: 12,
                                            weight: '500'
                                        },
                                        padding: 10,
                                        color: 'rgba(255, 255, 255, 0.7)'
                                    },
                                    border: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(255, 255, 255, 0.05)',
                                        borderDash: [5, 5],
                                        drawBorder: false
                                    },
                                    ticks: {
                                        font: {
                                            size: 12,
                                            weight: '500'
                                        },
                                        padding: 10,
                                        color: 'rgba(255, 255, 255, 0.7)',
                                        callback: function(value, index, values) {
                                            if (Math.floor(value) === value)
                                                return value;
                                        }
                                    },
                                    border: {
                                        display: false
                                    }
                                }
                            },
                            elements: {
                                line: {
                                    borderWidth: 3,
                                    borderCapStyle: 'rounded'
                                },
                                point: {
                                    hitRadius: 10,
                                    hoverRadius: 10
                                }
                            },
                            animation: {
                                duration: 2500,
                                easing: 'easeOutCirc',
                                delay: (context) => {
                                    return context.datasetIndex * 300 + context.dataIndex * 100;
                                }
                            },
                            layout: {
                                padding: {
                                    top: 20,
                                    right: 20,
                                    bottom: 20,
                                    left: 20
                                }
                            }
                        }
                    });
                }
            }
            
            // Initialize all counter animations
            const counterValues = document.querySelectorAll('.counter-value');
            counterValues.forEach(counter => {
                const targetCount = parseFloat(counter.getAttribute('data-count'));
                let count = 0;
                const suffix = counter.nextElementSibling?.classList.contains('counter-suffix') ? counter.nextElementSibling.textContent : '';
                const decimal = targetCount % 1 !== 0;
                const increment = decimal ? targetCount / 50 : Math.ceil(targetCount / 50);
                const duration = 2000; // 2 seconds
                const interval = duration / 50;
                
                const counterAnimation = setInterval(() => {
                    count += increment;
                    if (count >= targetCount) {
                        count = targetCount;
                        clearInterval(counterAnimation);
                    }
                    counter.textContent = decimal ? count.toFixed(1) : Math.floor(count);
                }, interval);
            });
            
            // Chart view controls functionality
            const chartControls = document.querySelectorAll('.chart-control-btn');
            if (chartControls.length && researchChart) {
                chartControls.forEach(btn => {
                    btn.addEventListener('click', () => {
                        // Remove active class from all buttons
                        chartControls.forEach(b => b.classList.remove('active'));
                        
                        // Add active class to clicked button
                        btn.classList.add('active');
                        
                        // Get the view type
                        const viewType = btn.getAttribute('data-view');
                        
                        // Update chart based on view type
                        updateChartView(viewType);
                    });
                });
                
                // Function to update chart view
                function updateChartView(viewType) {
                    // Show all datasets by default
                    researchChart.data.datasets.forEach((dataset, index) => {
                        researchChart.setDatasetVisibility(index, true);
                    });
                    
                    // Apply specific view settings
                    if (viewType === 'publications') {
                        researchChart.setDatasetVisibility(1, false); // Hide citations
                        researchChart.setDatasetVisibility(2, false); // Hide funding
                    } else if (viewType === 'citations') {
                        researchChart.setDatasetVisibility(0, false); // Hide publications
                        researchChart.setDatasetVisibility(2, false); // Hide funding
                    } else if (viewType === 'funding') {
                        researchChart.setDatasetVisibility(0, false); // Hide publications
                        researchChart.setDatasetVisibility(1, false); // Hide citations
                    }
                    
                    // Apply animation
                    researchChart.update('active');
                    
                    // Add special visual effects
                    const chartContainer = document.querySelector('.impact-chart-container');
                    chartContainer.classList.add('updating');
                    setTimeout(() => {
                        chartContainer.classList.remove('updating');
                    }, 700);
                }
                
                // Add hover interaction to improve chart user experience
                const chartCanvas = document.getElementById('researchImpactChart');
                if (chartCanvas) {
                    chartCanvas.addEventListener('mousemove', (e) => {
                        const rect = chartCanvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
                        // Create ripple effect on hover
                        const glowEffect = document.querySelector('.chart-glow-effect');
                        if (glowEffect) {
                            glowEffect.style.background = `radial-gradient(circle at ${x}px ${y}px, rgba(76, 201, 240, 0.1) 0%, rgba(15, 23, 42, 0) 70%)`;
                        }
                    });
                }
            }
        });

// ----

document.addEventListener('DOMContentLoaded', function() {
            const searchBar = document.getElementById('search-bar');
            const searchButton = document.getElementById('search-bttn');
            
            // Function to perform search
            function performSearch() {
                const searchQuery = searchBar.value.trim();
                if (searchQuery) {
                    // Redirect to Research_page.php with search parameter
                    const searchUrl = `Research_page.php?search=${encodeURIComponent(searchQuery)}`;
                    window.location.href = searchUrl;
                }
            }
            
            // Handle search button click
            if (searchButton) {
                searchButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    performSearch();
                });
            }
            
            // Handle Enter key press in search input
            if (searchBar) {
                searchBar.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        performSearch();
                    }
                });
                
                // Add real-time search suggestions (optional enhancement)
                searchBar.addEventListener('input', function() {
                    const query = this.value.trim();
                    if (query.length >= 2) {
                        // Show search suggestions or highlight popular searches
                        highlightMatchingKeywords(query);
                    } else {
                        // Reset keyword highlights
                        resetKeywordHighlights();
                    }
                });
            }
            
            // Function to highlight matching keywords
            function highlightMatchingKeywords(query) {
                const keywords = document.querySelectorAll('.keyword-tag');
                keywords.forEach(keyword => {
                    const keywordText = keyword.textContent.toLowerCase();
                    if (keywordText.includes(query.toLowerCase())) {
                        keyword.classList.add('highlighted');
                    } else {
                        keyword.classList.remove('highlighted');
                    }
                });
            }
            
            // Function to reset keyword highlights
            function resetKeywordHighlights() {
                const keywords = document.querySelectorAll('.keyword-tag');
                keywords.forEach(keyword => {
                    keyword.classList.remove('highlighted');
                });
            }
            
            // Make keyword tags clickable
            const keywordTags = document.querySelectorAll('.keyword-tag');
            keywordTags.forEach(tag => {
                tag.addEventListener('click', function() {
                    const keyword = this.textContent.trim();
                    searchBar.value = keyword;
                    performSearch();
                });
                
                // Make them focusable and accessible
                tag.setAttribute('tabindex', '0');
                tag.setAttribute('role', 'button');
                tag.setAttribute('aria-label', `Search for ${this.textContent}`);
                
                // Handle keyboard navigation
                tag.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        const keyword = this.textContent.trim();
                        searchBar.value = keyword;
                        performSearch();
                    }
                });
    });
});
