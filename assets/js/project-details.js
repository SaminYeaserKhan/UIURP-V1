        document.addEventListener('DOMContentLoaded', function() {
            // Initialize particles.js with more optimized settings
      particlesJS('particles-js', {
          "particles": {
              "number": {
                  "value": 100,
                  "density": {
                      "enable": true,
                      "value_area": 1200
                  }
              },
              "color": {
                  "value": ["#3a86ff", "#4cc9f0", "#7209b7", "#2cb2f5", "#4361ee"]
              },
              "shape": {
                  "type": ["circle", "edge"],
                  "stroke": {
                      "width": 0,
                      "color": "#000000"
                  },
                  "polygon": {
                      "nb_sides": 6
                  }
              },
              "opacity": {
                  "value": 0.2,
                  "random": true,
                  "anim": {
                      "enable": true,
                      "speed": 0.5,
                      "opacity_min": 0.1,
                      "sync": false
                  }
              },
              "size": {
                  "value": 3,
                  "random": true,
                  "anim": {
                      "enable": true,
                      "speed": 1,
                      "size_min": 1,
                      "sync": false
                  }
              },
              "line_linked": {
                  "enable": true,
                  "distance": 150,
                  "color": "#3a86ff",
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
                      "mode": "repulse"
                  },
                  "resize": true
              },
              "modes": {
                  "grab": {
                      "distance": 180,
                      "line_linked": {
                          "opacity": 0.5,
                          "color": "#4cc9f0"
                      }
                  },
                  "bubble": {
                      "distance": 200,
                      "size": 6,
                      "duration": 1.5,
                      "opacity": 0.6,
                      "speed": 3
                  },
                  "repulse": {
                      "distance": 200,
                      "duration": 1.5,
                      "speed": 2
                  },
                  "push": {
                      "particles_nb": 10
                  },
                  "remove": {
                      "particles_nb": 5
                  }
              }
          },
          "retina_detect": true
      });
      // Function to reinitialize particles if they stop
      function reinitializeParticlesIfNeeded() {
          if (window.pJSDom && window.pJSDom[0] && window.pJSDom[0].pJS) {
              const pJS = window.pJSDom[0].pJS;
              if (!pJS.particles.move.enable || pJS.particles.array.length === 0) {
                  console.log("Reinitializing particles...");
                  pJS.particles.move.enable = true;
                  
                  // First try to refresh existing particles
                  try {
                      pJS.fn.particlesRefresh();
                  } catch (error) {
                      console.error("Error refreshing particles:", error);
                      
                      // If refreshing fails, destroy and recreate
                      try {
                          window.pJSDom[0].pJS.fn.vendors.destroypJS();
                          window.pJSDom = [];
                          particlesJS('particles-js', /* same config as above */);
                      } catch (err) {
                          console.error("Failed to reinitialize particles:", err);
                      }
                  }
              }
          } else {
              // If pJSDom is missing, reinitialize
              particlesJS('particles-js', /* same config as above */);
          }
      }
      
      // Add scroll effect to particles for depth
      let lastScrollY = window.scrollY;
      window.addEventListener('scroll', function() {
          const canvas = document.querySelector('#particles-js canvas');
          if (canvas) {
              const scrollDifference = window.scrollY - lastScrollY;
              lastScrollY = window.scrollY;
              
              // Apply enhanced parallax effect to particles
              const particles = window.pJSDom[0].pJS.particles;
              particles.array.forEach(particle => {
                  particle.y -= scrollDifference * 0.05;
                  
                  // Add slight horizontal movement for more dynamic effect
                  if (Math.random() > 0.5) {
                      particle.x += Math.random() * 0.2 - 0.1;
                  }
              });
          }
      });
      
      
      // Create periodic wave effects through particles
      setInterval(() => {
          if (window.pJSDom && window.pJSDom[0] && window.pJSDom[0].pJS) {
              const particles = window.pJSDom[0].pJS.particles;
              
              // Only proceed if particles are moving
              if (!particles.move.enable) {
                  reinitializeParticlesIfNeeded();
                  return;
              }
              
              const centerX = window.innerWidth / 2;
              const centerY = window.innerHeight / 2;
              
              particles.array.forEach(particle => {
                  // Calculate distance from center
                  const dx = particle.x - centerX;
                  const dy = particle.y - centerY;
                  const distance = Math.sqrt(dx * dx + dy * dy);
                  
                  // Create a wave effect
                  const direction = distance > 0 ? (dx / distance) : 0;
                  
                  // Push particles slightly outward in a wave
                  particle.x += direction * 2;
                  
                  // Reset particles that go too far
                  if (particle.x > window.innerWidth) {
                      particle.x = 0;
                  } else if (particle.x < 0) {
                      particle.x = window.innerWidth;
                  }
              });
          } else {
              reinitializeParticlesIfNeeded();
          }
      }, 5000);
      
      // Ensure particles always stay active, check every 2 seconds
      setInterval(() => {
          reinitializeParticlesIfNeeded();
      }, 2000);
      
      // Make canvas and particles container un-clickable to prevent stopping animation
      const particlesContainer = document.getElementById('particles-js');
      if (particlesContainer) {
          particlesContainer.addEventListener('click', function(e) {
              e.stopPropagation();
              e.preventDefault();
              
              // Create burst effect without stopping the animation
              const burst = document.createElement('div');
              burst.classList.add('particle-burst');
              burst.style.left = e.pageX + 'px';
              burst.style.top = e.pageY + 'px';
              document.body.appendChild(burst);
              
              setTimeout(() => {
                  burst.remove();
              }, 1000);
              
              return false;
          }, true);
      }
      
      // Add global click handler for adding particles but prevent it from stopping animation
      document.addEventListener('click', function(e) {
          // Don't create particles for clicks on interactive elements
          if (e.target.closest('a, button, input, .search-box, .toggle-btn')) {
              return;
          }
          
          if (window.pJSDom && window.pJSDom[0] && window.pJSDom[0].pJS) {
              const pJS = window.pJSDom[0].pJS;
              
              // Use a safer method: create a new particle directly through pJS API
              try {
                  for (let i = 0; i < 8; i++) {
                      const posX = e.clientX + ((Math.random() - 0.5) * 20);
                      const posY = e.clientY + ((Math.random() - 0.5) * 20);
                      
                      // Use the particle creation method from particles.js
                      pJS.fn.modes.pushParticles(1, {x: posX, y: posY});
                  }
              } catch (error) {
                  console.error("Error adding particles on click:", error);
                  // If there's an error, ensure animation is still running
                  setTimeout(reinitializeParticlesIfNeeded, 200);
              }
          }
          
          // Don't stop propagation - just make sure particles keep running
          setTimeout(reinitializeParticlesIfNeeded, 500);
      });
      
      // Handle window focus/blur events to ensure particles keep moving
      window.addEventListener('blur', function() {
          // When window loses focus, set a timer to keep checking
          window.particleCheckInterval = setInterval(reinitializeParticlesIfNeeded, 1000);
      });
      
      window.addEventListener('focus', function() {
          // When window regains focus, clear the intensive check interval
          if (window.particleCheckInterval) {
              clearInterval(window.particleCheckInterval);
          }
          
          // Do a single check and reinitialize if needed
          reinitializeParticlesIfNeeded();
      });
      
      // Add fade-in effect for particles
      setTimeout(() => {
          document.body.classList.add('loaded');
      }, 300);

    });

    // Function to improve file layout after rendering to prevent download button clipping
    function improveFileLayout() {
        const resourcesContainer = document.getElementById('resources-container');
        if (!resourcesContainer) return;
        
        // Find all file list items
        const fileItems = resourcesContainer.querySelectorAll('.list-group-item');
        
        fileItems.forEach(item => {
            // Update the structure to use the new layout
            const fileInfo = item.querySelector('.file-info');
            const downloadBtn = item.querySelector('.download-btn');
            const fileName = item.querySelector('.file-name');
            
            if (fileInfo && downloadBtn && fileName) {
                // Remove existing classes and add new ones
                item.className = 'list-group-item';
                
                // Wrap file info and download button in a container
                const container = document.createElement('div');
                container.className = 'file-item-container';
                
                // Update file name structure for better text wrapping
                const icon = fileName.querySelector('i');
                const fileNameText = fileName.textContent.replace(/^\s*/, '').trim();
                
                if (icon) {
                    fileName.innerHTML = '';
                    fileName.appendChild(icon);
                    
                    const textSpan = document.createElement('span');
                    textSpan.className = 'file-name-text';
                    textSpan.textContent = fileNameText;
                    fileName.appendChild(textSpan);
                }
                
                // Move elements to the new container
                container.appendChild(fileInfo);
                container.appendChild(downloadBtn);
                
                // Clear the item and add the new container
                item.innerHTML = '';
                item.appendChild(container);
            }
        });
    }

    // Call the improvement function after a short delay to ensure DOM is ready
    setTimeout(() => {
        improveFileLayout();
    }, 1000);
    
    // Meeting functionality
    let memberScheduleData = [];
    
    // Global calendar functions (needed for onclick handlers)
    window.navigateCalendar = function(direction) {
        currentCalendarDate.setMonth(currentCalendarDate.getMonth() + direction);
        renderCalendar();
    };
    
    window.selectDate = function(dateString) {
        selectedDate = dateString;
        document.getElementById('meetingDate').value = dateString;
        renderCalendar(); // Re-render to show selection
        
        // Hide no date selected message and trigger time suggestions
        document.getElementById('noDateSelected').style.display = 'none';
        onDateChange();
    };
    
    // Initialize meeting functionality
    function initializeMeetingFeature() {
        console.log("Initializing meeting feature, checking access");
        
        // The section visibility is already controlled by the edit access check
        // We only need to add event listeners if the section is visible
        const projectMeetingsSection = document.getElementById('project-meetings-section');
        if (projectMeetingsSection && projectMeetingsSection.style.display !== 'none') {
            // Add event listeners
            document.getElementById('addMeetingBtn').addEventListener('click', openMeetingModal);
            document.getElementById('saveMeetingBtn').addEventListener('click', saveMeeting);
            
            // Load existing meetings
            loadProjectMeetings();
        }
        
        /* 
        // The code below was causing issues - we'll implement proper access later
        // For now always show meetings section to avoid disrupting functionality
        
        // Check if user has edit access to the project
        let hasEditAccess = false;
        
        try {
            console.log("Checking edit access, currentUserId:", currentUserId);
            console.log("Project data:", project);
            
            // Default to showing the section - safer approach
            hasEditAccess = true;
            
            // Add event listeners only if user has edit access
            document.getElementById('addMeetingBtn').addEventListener('click', openMeetingModal);
            document.getElementById('saveMeetingBtn').addEventListener('click', saveMeeting);
            
            // Load existing meetings
            loadProjectMeetings();
        } catch (error) {
            console.error('Error checking edit access:', error);
            // Show meetings section even on error for now
            const meetingsSection = document.getElementById('project-meetings-section');
            if (meetingsSection) {
                meetingsSection.style.display = 'block';
            }
            // Add event listeners
            document.getElementById('addMeetingBtn').addEventListener('click', openMeetingModal);
            document.getElementById('saveMeetingBtn').addEventListener('click', saveMeeting);
            
            // Load existing meetings
            loadProjectMeetings();
        }
        */
    }
    
    // Calendar state
    let currentCalendarDate = new Date();
    let selectedDate = null;
    
    // Open meeting modal
    function openMeetingModal() {
        const modal = new bootstrap.Modal(document.getElementById('meetingModal'));
        
        // Reset form and suggestions
        document.getElementById('meetingForm').reset();
        document.getElementById('availabilityResults').style.display = 'none';
        document.getElementById('timeSuggestionsContainer').style.display = 'none';
        document.getElementById('timeSuggestionsLoading').style.display = 'none';
        document.getElementById('memberSchedules').style.display = 'none';
        document.getElementById('noDateSelected').style.display = 'none'; // Hide "no date selected" message
        
        // Set calendar to current date and automatically select today
        currentCalendarDate = new Date();
        const today = new Date();
        selectedDate = today.toISOString().split('T')[0]; // Format as YYYY-MM-DD
        document.getElementById('meetingDate').value = selectedDate;
        
        // Render calendar with today highlighted
        renderCalendar();
        
        modal.show();
        
        // Automatically load suggestions for today
        if (memberScheduleData.length === 0) {
            // Load member schedules first, then generate suggestions for today
            loadMemberSchedules(() => {
                generateTimeSuggestionsForDate(selectedDate);
            });
        } else {
            // Generate suggestions for today immediately
            generateTimeSuggestionsForDate(selectedDate);
        }
    }
    
    // Render calendar
    function renderCalendar() {
        const calendarContainer = document.getElementById('meetingCalendar');
        const today = new Date();
        const year = currentCalendarDate.getFullYear();
        const month = currentCalendarDate.getMonth();
        
        // Create calendar HTML
        const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        
        let calendarHTML = `
            <div class="calendar-header">
                <button type="button" class="calendar-nav-btn" onclick="navigateCalendar(-1)">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <div class="calendar-month-year">
                    ${monthNames[month]} ${year}
                </div>
                <button type="button" class="calendar-nav-btn" onclick="navigateCalendar(1)">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
            <div class="calendar-grid">
        `;
        
        // Add day headers
        dayNames.forEach(day => {
            calendarHTML += `<div class="calendar-day-header">${day}</div>`;
        });
        
        // Get first day of month and number of days
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrevMonth = new Date(year, month, 0).getDate();
        
        // Add previous month's trailing days
        for (let i = firstDay - 1; i >= 0; i--) {
            const day = daysInPrevMonth - i;
            calendarHTML += `<div class="calendar-day other-month">${day}</div>`;
        }
        
        // Add current month's days
        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            const dateString = date.toISOString().split('T')[0];
            const isToday = date.toDateString() === today.toDateString();
            const isPast = date < today && !isToday;
            const isSelected = selectedDate && selectedDate === dateString;
            
            let classes = 'calendar-day';
            if (isPast) classes += ' disabled';
            if (isToday) classes += ' today';
            if (isSelected) classes += ' selected';
            
            const clickHandler = isPast ? '' : `onclick="selectDate('${dateString}')"`;
            
            calendarHTML += `<div class="${classes}" ${clickHandler}>${day}</div>`;
        }
        
        // Add next month's leading days
        const totalCells = Math.ceil((firstDay + daysInMonth) / 7) * 7;
        const remainingCells = totalCells - (firstDay + daysInMonth);
        for (let day = 1; day <= remainingCells; day++) {
            calendarHTML += `<div class="calendar-day other-month">${day}</div>`;
        }
        
        calendarHTML += '</div>';
        
        calendarContainer.innerHTML = calendarHTML;
    }
    

    
    // Handle date change event
    function onDateChange() {
        const selectedDate = document.getElementById('meetingDate').value;
        
        if (!selectedDate) {
            document.getElementById('timeSuggestionsContainer').style.display = 'none';
            return;
        }
        
        // Show loading
        document.getElementById('timeSuggestionsLoading').style.display = 'block';
        document.getElementById('timeSuggestionsContainer').style.display = 'none';
        
        // Generate suggestions for the selected date
        if (memberScheduleData.length > 0) {
            generateTimeSuggestionsForDate(selectedDate);
        } else {
            // Load schedules first, then generate suggestions
            loadMemberSchedules(() => {
                generateTimeSuggestionsForDate(selectedDate);
            });
        }
    }
    
    // Refresh time suggestions
    function refreshTimeSuggestions() {
        const selectedDate = document.getElementById('meetingDate').value;
        if (selectedDate) {
            onDateChange();
        }
    }
    
    // Manual time selection for testing
    function setManualTime() {
        const selectedDate = document.getElementById('meetingDate').value;
        const startTime = document.getElementById('manualStartTime').value;
        const endTime = document.getElementById('manualEndTime').value;
        
        // Validate inputs
        if (!selectedDate) {
            alert('Please select a date first');
            return;
        }
        
        if (!startTime || !endTime) {
            alert('Please enter both start and end times');
            return;
        }
        
        // Convert to Date objects for validation
        const startDateTime = new Date(`${selectedDate}T${startTime}`);
        const endDateTime = new Date(`${selectedDate}T${endTime}`);
        
        if (endDateTime <= startDateTime) {
            alert('End time must be after start time');
            return;
        }
        
        // Set the times in the hidden fields
        document.getElementById('meetingStartTime').value = startTime;
        document.getElementById('meetingEndTime').value = endTime;
        document.getElementById('meetingDate').value = selectedDate;
        
        // Clear any existing suggestion selections
        document.querySelectorAll('.suggestion-card').forEach(card => {
            card.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
        });
        
        // Calculate duration
        const durationMs = endDateTime - startDateTime;
        const durationHours = Math.floor(durationMs / (1000 * 60 * 60));
        const durationMinutes = Math.floor((durationMs % (1000 * 60 * 60)) / (1000 * 60));
        
        let durationText = '';
        if (durationHours > 0) {
            durationText += `${durationHours}h `;
        }
        if (durationMinutes > 0) {
            durationText += `${durationMinutes}min`;
        }
        
        // Enable save button and show confirmation
        document.getElementById('saveMeetingBtn').disabled = false;
        
        const availabilityResults = document.getElementById('availabilityResults');
        const formattedDate = new Date(selectedDate).toLocaleDateString('en-US', { 
            weekday: 'long', 
            month: 'long', 
            day: 'numeric' 
        });
        
        availabilityResults.innerHTML = `
            <i class="bi bi-tools me-2 text-warning"></i>
            <strong>Manual Time Set:</strong> ${startTime} - ${endTime} (${durationText}) on ${formattedDate}
            <br><small class="text-warning">⚠️ Testing mode - Team availability not checked</small>
        `;
        availabilityResults.className = 'alert alert-warning';
        availabilityResults.style.display = 'block';
        
        console.log('Manual time set:', {
            date: selectedDate,
            startTime: startTime,
            endTime: endTime,
            duration: durationText
        });
    }
    
    // Filter time suggestions by AM/PM
    function filterSuggestions(filter) {
        // Update button states
        document.querySelectorAll('#timeSuggestionsContainer .btn-group button').forEach(btn => {
            btn.classList.remove('active');
        });
        const buttonId = filter === 'all' ? 'filterAll' : 
                         filter === 'am' ? 'filterAM' : 
                         filter === 'pm' ? 'filterPM' : '';
        if (buttonId) {
            document.getElementById(buttonId).classList.add('active');
        }
        
        // Get all suggestion elements
        const suggestions = document.querySelectorAll('#timeSlots .suggestion-card');
        
        suggestions.forEach((suggestion, index) => {
            const timeDisplayElement = suggestion.querySelector('.time-display .card-title');
            const startTimeText = timeDisplayElement ? timeDisplayElement.textContent.trim() : '';
            
            // Extract start time from format like "08:00 - 09:00" (ignore the icon text)
            const timeMatch = startTimeText.match(/(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})/);
            const startTime = timeMatch ? timeMatch[1] : '';
            
            let showSuggestion = true;
            
            if (startTime && filter !== 'all') {
                const [hours, minutes] = startTime.split(':').map(Number);
                
                if (filter === 'am') {
                    // Show only AM suggestions (before 12:00)
                    showSuggestion = hours < 12;
                } else if (filter === 'pm') {
                    // Show only PM suggestions (12:00 and after)
                    showSuggestion = hours >= 12;
                }
            }
            
            suggestion.style.display = showSuggestion ? 'block' : 'none';
        });
        
        // Update count display
        const visibleSuggestions = document.querySelectorAll('#timeSlots .suggestion-card:not([style*="display: none"])').length;
        
        const availabilityResults = document.getElementById('availabilityResults');
        if (availabilityResults && availabilityResults.style.display !== 'none') {
            const filterText = filter === 'all' ? '' : ` (${filter.toUpperCase()} only)`;
            availabilityResults.innerHTML = availabilityResults.innerHTML.replace(
                /\d+ team meeting slots/,
                `${visibleSuggestions} team meeting slots`
            ).replace(/ \(AM only\)| \(PM only\)/g, '') + filterText;
        }
    }
    
    // Load member schedules using individual get_schedule.php calls
    function loadMemberSchedules(callback = null) {
        console.log('Starting to load member schedules for project:', projectId);
        
        // First, get the project details using GET request (not POST)
        fetch(`src/model/get_project.php?id=${projectId}`)
        .then(response => {
            console.log('Project fetch response status:', response.status);
            return response.json();
        })
        .then(projectData => {
            console.log('Project data received:', projectData);
            
            if (!projectData.success || !projectData.project) {
                throw new Error('Could not load project details: ' + (projectData.message || 'Unknown error'));
            }
            
            const project = projectData.project;
            const memberPromises = [];
            let memberCount = 0;
            
            console.log('Project loaded:', {
                title: project.title,
                members: project.members ? project.members.length : 0,
                supervisor: project.supervisor ? 'Yes' : 'No'
            });
            
            // Collect all team members
            if (project.members && Array.isArray(project.members)) {
                console.log('Processing', project.members.length, 'team members');
                
                project.members.forEach((member, index) => {
                    console.log(`Member ${index + 1}:`, member);
                    
                    if (member.userId) {
                        let userId = member.userId;
                        
                        // Handle different userId formats
                        if (typeof userId === 'object') {
                            if (userId.$oid) {
                                userId = userId.$oid;
                            } else if (userId.oid) {
                                userId = userId.oid;
                            } else {
                                userId = String(userId);
                            }
                        } else {
                            userId = String(userId);
                        }
                        
                        console.log(`  - Extracted userId: ${userId}`);
                        memberCount++;
                        
                        // Create promise to fetch this member's schedule
                        const schedulePromise = fetch('src/model/get_schedule.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ userId: userId })
                        })
                        .then(response => {
                            console.log(`Schedule fetch for ${member.name} (${userId}) - Status:`, response.status);
                            return response.json();
                        })
                        .then(scheduleData => {
                            console.log(`Schedule data for ${member.name}:`, scheduleData);
                            
                            return {
                                userId: userId,
                                name: member.name || 'Unknown',
                                role: member.role || 'Team Member',
                                type: 'student',
                                schedule: scheduleData.success && scheduleData.schedule ? scheduleData.schedule : []
                            };
                        })
                        .catch(error => {
                            console.error(`Error fetching schedule for ${member.name}:`, error);
                            return {
                                userId: userId,
                                name: member.name || 'Unknown',
                                role: member.role || 'Team Member',
                                type: 'student',
                                schedule: []
                            };
                        });
                        
                        memberPromises.push(schedulePromise);
                    } else {
                        console.warn(`Member ${index + 1} has no userId:`, member);
                    }
                });
            }
            
            // Add supervisor
            if (project.supervisor && project.supervisor.userId) {
                console.log('Processing supervisor:', project.supervisor);
                
                let supervisorId = project.supervisor.userId;
                
                // Handle different userId formats
                if (typeof supervisorId === 'object') {
                    if (supervisorId.$oid) {
                        supervisorId = supervisorId.$oid;
                    } else if (supervisorId.oid) {
                        supervisorId = supervisorId.oid;
                    } else {
                        supervisorId = String(supervisorId);
                    }
                } else {
                    supervisorId = String(supervisorId);
                }
                
                console.log(`  - Supervisor userId: ${supervisorId}`);
                memberCount++;
                
                const supervisorSchedulePromise = fetch('src/model/get_schedule.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ userId: supervisorId })
                })
                .then(response => {
                    console.log(`Schedule fetch for supervisor ${project.supervisor.name} (${supervisorId}) - Status:`, response.status);
                    return response.json();
                })
                .then(scheduleData => {
                    console.log(`Schedule data for supervisor ${project.supervisor.name}:`, scheduleData);
                    
                    return {
                        userId: supervisorId,
                        name: project.supervisor.name || 'Unknown',
                        role: 'Supervisor',
                        type: 'faculty',
                        schedule: scheduleData.success && scheduleData.schedule ? scheduleData.schedule : []
                    };
                })
                .catch(error => {
                    console.error(`Error fetching schedule for supervisor ${project.supervisor.name}:`, error);
                    return {
                        userId: supervisorId,
                        name: project.supervisor.name || 'Unknown',
                        role: 'Supervisor',
                        type: 'faculty',
                        schedule: []
                    };
                });
                
                memberPromises.push(supervisorSchedulePromise);
            }
            
            console.log(`Total members to fetch schedules for: ${memberCount}`);
            
            if (memberPromises.length === 0) {
                throw new Error('No team members or supervisors found in project');
            }
            
            // Wait for all schedule requests to complete
            return Promise.all(memberPromises);
        })
        .then(allMemberSchedules => {
            memberScheduleData = allMemberSchedules;
            console.log('Successfully loaded schedules for', memberScheduleData.length, 'members');
            
            // Log summary of loaded data
            allMemberSchedules.forEach(member => {
                console.log(`${member.name} (${member.role}): ${member.schedule.length} schedule items`);
            });
            
            if (callback) callback();
        })
        .catch(error => {
            console.error('Error loading member schedules:', error);
            document.getElementById('timeSuggestionsLoading').style.display = 'none';
            
            // Show detailed error message
            const availabilityResults = document.getElementById('availabilityResults');
            availabilityResults.innerHTML = `<i class="bi bi-x-circle me-2"></i>Error loading team schedules: ${error.message}`;
            availabilityResults.className = 'alert alert-danger';
            availabilityResults.style.display = 'block';
        });
    }
    
    // Load project meetings
    function loadProjectMeetings() {
        const meetingsLoading = document.getElementById('meetings-loading');
        const meetingsList = document.getElementById('meetings-list');
        const noMeetings = document.getElementById('no-meetings');
        
        meetingsLoading.style.display = 'block';
        meetingsList.style.display = 'none';
        noMeetings.style.display = 'none';
        
        // Construct proper URL for the API endpoint
        // If we're on a development server (port 3000), point to XAMPP on default port
        let getMeetingsUrl;
        if (window.location.port === '3000') {
            // Development server - point to XAMPP
            getMeetingsUrl = window.location.protocol + '//' + window.location.hostname + '/uiurp/src/model/get_project_meetings.php';
        } else {
            // Same server - use relative path with proper construction
            getMeetingsUrl = window.location.protocol + '//' + window.location.hostname + 
                            (window.location.port && window.location.port !== '80' && window.location.port !== '443' 
                             ? ':' + window.location.port : '') + 
                            window.location.pathname.replace(/\/[^\/]*$/, '') + '/src/model/get_project_meetings.php';
        }
        
        fetch(getMeetingsUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ projectId: projectId }),
            credentials: 'include'  // Include session cookies for cross-origin
        })
        .then(response => response.json())
        .then(data => {
            meetingsLoading.style.display = 'none';
            
            if (data.success && data.meetings && data.meetings.length > 0) {
                renderMeetings(data.meetings);
                meetingsList.style.display = 'block';
                noMeetings.style.display = 'none';
            } else {
                meetingsList.style.display = 'none';
                noMeetings.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error loading meetings:', error);
            meetingsLoading.style.display = 'none';
            meetingsList.style.display = 'none';
            noMeetings.style.display = 'block';
        });
    }
    
    // Render meetings
    function renderMeetings(meetings) {
        const meetingsList = document.getElementById('meetings-list');
        meetingsList.innerHTML = '';
        
        // Sort meetings by date, newest first
        const sortedMeetings = [...meetings].sort((a, b) => {
            // First compare dates
            const dateComparison = new Date(b.date) - new Date(a.date);
            
            // If same date, compare start times
            if (dateComparison === 0) {
                // Convert time strings to comparable values (assuming format like "09:00")
                const aTime = a.startTime.split(':').map(Number);
                const bTime = b.startTime.split(':').map(Number);
                
                // Compare hours first, then minutes
                if (bTime[0] !== aTime[0]) {
                    return bTime[0] - aTime[0];
                } else {
                    return bTime[1] - aTime[1];
                }
            }
            
            return dateComparison;
        });
        
        sortedMeetings.forEach(meeting => {
            const meetingCard = createMeetingCard(meeting);
            meetingsList.appendChild(meetingCard);
        });
    }
    
    // Create meeting card
    function createMeetingCard(meeting) {
        const card = document.createElement('div');
        card.className = 'meeting-card mb-3 p-3 border rounded';
        
        const meetingDate = new Date(meeting.date);
        const formattedDate = meetingDate.toLocaleDateString();
        const startTime = formatTime(meeting.startTime);
        const endTime = formatTime(meeting.endTime);
        
        // Determine status color
        let statusClass = 'bg-primary';
        const currentDate = new Date();
        const meetingDateTime = new Date(meeting.date + 'T' + meeting.startTime);
        
        if (meetingDateTime < currentDate) {
            statusClass = 'bg-secondary'; // Past meeting
        } else if (meeting.status === 'Cancelled') {
            statusClass = 'bg-danger';
        }
        
        card.innerHTML = `
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="mb-0">${meeting.title}</h6>
                <span class="badge ${statusClass}">${meeting.status}</span>
            </div>
            <div class="meeting-details">
                <div class="d-flex align-items-center text-muted mb-1">
                    <i class="bi bi-calendar3 me-2"></i>
                    <small>${formattedDate}</small>
                </div>
                <div class="d-flex align-items-center text-muted mb-1">
                    <i class="bi bi-clock me-2"></i>
                    <small>${startTime} - ${endTime}</small>
                </div>
                ${meeting.meetingLink ? `
                    <div class="d-flex align-items-center text-muted mb-1">
                        <i class="bi bi-link-45deg me-2"></i>
                        <small><a href="#" onclick="handleMeetingJoin('${meeting.meetingId}'); return false;" class="text-primary">Join Meeting (Time Restricted)</a></small>
                    </div>
                ` : ''}
                <div class="d-flex align-items-center text-muted">
                    <i class="bi bi-person me-2"></i>
                    <small>Organized by ${meeting.organizer}</small>
                </div>
            </div>
            ${meeting.description ? `
                <p class="text-muted small mt-2 mb-0">${meeting.description}</p>
            ` : ''}
        `;
        
        return card;
    }
    
    // Generate time suggestions for a specific date using team schedule analysis
    function generateTimeSuggestionsForDate(selectedDate) {
        const targetDate = new Date(selectedDate);
        const dayName = targetDate.toLocaleDateString('en-US', { weekday: 'long' });
        
        console.log('Generating suggestions for', selectedDate, '(', dayName, ') using team schedule analysis');
        
        // Construct proper URL for the API endpoint
        let suggestionsUrl;
        if (window.location.port === '3000') {
            // Development server - point to XAMPP
            suggestionsUrl = `http://localhost/uiurp/src/model/get_project_meeting_suggestions.php?projectId=${projectId}&date=${selectedDate}`;
        } else {
            // Same server - use relative path
            suggestionsUrl = `src/model/get_project_meeting_suggestions.php?projectId=${projectId}&date=${selectedDate}`;
        }
        
        // Fetch suggestions from our new backend API
        fetch(suggestionsUrl, {
            method: 'GET',
            credentials: 'include'  // Include session cookies for cross-origin
        })
            .then(response => response.json())
            .then(data => {
                console.log('Team schedule analysis result:', data);
                
                // Hide loading indicator
                document.getElementById('timeSuggestionsLoading').style.display = 'none';
                
                if (data.success) {
                    const suggestions = data.suggestions || [];
                    const memberConflicts = data.memberConflicts || {};
                    
                    // Debug: Log all suggestions received from API
                    console.log('Raw suggestions from API:', suggestions.map(s => `${s.startTime} - ${s.endTime}`));
                    console.log('Debug info:', data.debug);
                    
                    if (suggestions.length > 0) {
                        // Convert suggestions to our expected format
                        const formattedSuggestions = suggestions.map(suggestion => ({
                date: selectedDate,
                displayDate: targetDate.toLocaleDateString('en-US', { 
                    weekday: 'short', 
                    month: 'short', 
                    day: 'numeric' 
                }),
                            startTime: suggestion.startTime,
                            endTime: suggestion.endTime,
                            duration: suggestion.duration,
                            durationText: suggestion.durationText,
                            quality: suggestion.quality,
                            available: data.teamMembers.length, // Everyone is available in these slots
                            total: data.teamMembers.length,
                            conflicts: [], // No conflicts in suggested times
                            score: 1.0, // Perfect score since everyone is free
                            isCompletelyFree: true
                        }));
        
                        // Render the suggestions
                        renderTeamTimeSuggestions(formattedSuggestions, data.teamMembers, memberConflicts);
            document.getElementById('timeSuggestionsContainer').style.display = 'block';
            
                        // Show success message
            const availabilityResults = document.getElementById('availabilityResults');
                        availabilityResults.innerHTML = `<i class="bi bi-check-circle me-2 text-success"></i><strong>${suggestions.length} team meeting slots found when everyone is free!</strong>`;
                availabilityResults.className = 'alert alert-success';
                        availabilityResults.style.display = 'block';
                        
                        // Show team member schedules
                        displayTeamSchedules(data.teamMembers, memberConflicts, data.dayOfWeek);
                        
            } else {
                        // No suggestions found
                        document.getElementById('timeSuggestionsContainer').style.display = 'none';
                        
                        const availabilityResults = document.getElementById('availabilityResults');
                        availabilityResults.innerHTML = `<i class="bi bi-x-circle me-2 text-warning"></i><strong>No free time slots found on ${dayName} when everyone is available</strong><br><small>Consider selecting a different date or shorter meeting duration</small>`;
                availabilityResults.className = 'alert alert-warning';
            availabilityResults.style.display = 'block';
                        
                        // Still show team schedules to help understand conflicts
                        displayTeamSchedules(data.teamMembers, memberConflicts, data.dayOfWeek);
                    }
        } else {
                    // Error occurred
            document.getElementById('timeSuggestionsContainer').style.display = 'none';
            
            const availabilityResults = document.getElementById('availabilityResults');
                    availabilityResults.innerHTML = `<i class="bi bi-exclamation-triangle me-2 text-danger"></i><strong>Error loading team schedules:</strong> ${data.error}`;
            availabilityResults.className = 'alert alert-danger';
            availabilityResults.style.display = 'block';
        }
            })
            .catch(error => {
                console.error('Error fetching team schedule suggestions:', error);
                
                // Hide loading and show error
                document.getElementById('timeSuggestionsLoading').style.display = 'none';
                document.getElementById('timeSuggestionsContainer').style.display = 'none';
                
                const availabilityResults = document.getElementById('availabilityResults');
                availabilityResults.innerHTML = `<i class="bi bi-exclamation-triangle me-2 text-danger"></i><strong>Error loading team schedules.</strong> Please try again.`;
                availabilityResults.className = 'alert alert-danger';
                availabilityResults.style.display = 'block';
            });
    }
    
    // Render team time suggestions with enhanced formatting
    function renderTeamTimeSuggestions(suggestions, teamMembers, memberConflicts) {
        const timeSlots = document.getElementById('timeSlots');
        
        if (suggestions.length === 0) {
            timeSlots.innerHTML = `
                <div class="text-center py-4">
                    <i class="bi bi-clock-history text-muted mb-3" style="font-size: 2.5rem;"></i>
                    <h6 class="text-muted mb-2">No Available Time Slots</h6>
                    <p class="text-muted small mb-0">All team members are busy during this day. Try selecting a different date.</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        
        // Group suggestions by quality for better organization
        const groupedSuggestions = {
            long: suggestions.filter(s => s.quality === 'long'),
            medium: suggestions.filter(s => s.quality === 'medium'),
            short: suggestions.filter(s => s.quality === 'short')
        };
        
        // Show best suggestions first
        Object.entries(groupedSuggestions).forEach(([quality, items]) => {
            if (items.length === 0) return;
            
            items.forEach((suggestion, index) => {
                const priorityClass = suggestion.quality === 'long' ? 'border-success' : 
                                    suggestion.quality === 'medium' ? 'border-warning' : 'border-info';
                
                const priorityIcon = suggestion.quality === 'long' ? 'bi-star-fill text-success' : 
                                   suggestion.quality === 'medium' ? 'bi-star-half text-warning' : 'bi-star text-info';
                
                const qualityLabel = suggestion.quality === 'long' ? 'Optimal' :
                                   suggestion.quality === 'medium' ? 'Good' : 'Quick';
                
                const qualityColor = suggestion.quality === 'long' ? 'success' :
                                   suggestion.quality === 'medium' ? 'warning' : 'info';
                
                html += `
                    <div class="suggestion-card ${priorityClass}" onclick="selectTimeSuggestion('${suggestion.startTime}', '${suggestion.endTime}', '${suggestion.date}')">
                        <div class="card-header-section">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="time-display">
                                    <h6 class="card-title mb-1">
                                        <i class="bi ${priorityIcon} me-2"></i>
                                        ${suggestion.startTime} - ${suggestion.endTime}
                                    </h6>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar3 me-1"></i>${suggestion.displayDate}
                                    </small>
                                </div>
                                <div class="badges">
                                    <span class="badge bg-${qualityColor} bg-opacity-10 text-${qualityColor} mb-1">
                                        ${qualityLabel}
                                    </span>
                                    <br>
                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                        ${suggestion.durationText}
                                    </span>
                                </div>
                            </div>
                        </div>
                        

                        
                        <div class="availability-info">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center text-success">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    <span class="small fw-medium">
                                        Everyone Available
                                    </span>
                                </div>
                                <div class="d-flex align-items-center text-muted small">
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
        });
        
        timeSlots.innerHTML = html;
        
        // Reset filter to "All" when new suggestions are loaded
        setTimeout(() => {
            const filterAllBtn = document.getElementById('filterAll');
            if (filterAllBtn) {
                document.querySelectorAll('#timeSuggestionsContainer .btn-group button').forEach(btn => {
                    btn.classList.remove('active');
                });
                filterAllBtn.classList.add('active');
            }
        }, 100);
    }
    
    // Display team schedules and conflicts as sidebar
    function displayTeamSchedules(teamMembers, memberConflicts, dayOfWeek) {
        const memberSchedules = document.getElementById('memberSchedules');
        
        let html = `
            <div class="sidebar-header mb-3">
                <h6 class="text-primary mb-1">
                    <i class="bi bi-people-fill me-2"></i>Team Availability
                </h6>
                <small class="text-muted">Individual schedules for ${dayOfWeek}</small>
            </div>
            <div class="members-list">
        `;
        
        teamMembers.forEach(member => {
            const conflicts = memberConflicts[member.name] || [];
            const roleIcon = member.type === 'supervisor' ? 'bi-mortarboard-fill' : 'bi-person-circle';
            const roleColor = member.type === 'supervisor' ? 'text-primary' : 'text-info';
            
            html += `
                <div class="member-card mb-3">
                    <div class="member-header">
                        <div class="d-flex align-items-center mb-2">
                            <div class="member-avatar-small me-2">
                                <i class="bi ${roleIcon} ${roleColor}"></i>
                            </div>
                            <div class="member-info flex-grow-1">
                                <div class="member-name">${member.name}</div>
                                <span class="role-badge badge bg-${member.type === 'supervisor' ? 'primary' : 'info'} bg-opacity-15 text-${member.type === 'supervisor' ? 'primary' : 'info'}">
                                    ${member.role}
                                </span>
                            </div>
                            <div class="status-indicator">
                                ${conflicts.length === 0 ? 
                                    '<i class="bi bi-check-circle-fill text-success" title="Available"></i>' : 
                                    '<i class="bi bi-exclamation-circle-fill text-warning" title="Has conflicts"></i>'
                                }
                            </div>
                        </div>
                    </div>
                    
                    <div class="member-schedule">
                        ${conflicts.length > 0 ? `
                            <div class="conflicts-compact">
                                <small class="conflicts-label text-warning fw-medium mb-1 d-block">
                                    <i class="bi bi-clock me-1"></i>Busy (${conflicts.length})
                                </small>
                                ${conflicts.map(conflict => `
                                    <div class="conflict-compact">
                                        <div class="conflict-time-badge">${conflict.startTime}-${conflict.endTime}</div>
                                        <div class="conflict-title-small">${conflict.title}</div>
                                        ${conflict.type ? `<div class="conflict-type">${conflict.type}</div>` : ''}
                                    </div>
                                `).join('')}
                            </div>
                        ` : `
                            <div class="available-compact">
                                <small class="text-success fw-medium">
                                    <i class="bi bi-check-circle-fill me-1"></i>
                                    Available all day
                                </small>
                            </div>
                        `}
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        memberSchedules.innerHTML = html;
        memberSchedules.style.display = 'block';
    }
    
    // Select a time suggestion and fill the form (global function)
    window.selectTimeSuggestion = function(startTime, endTime, date) {
        // Fill hidden fields
        document.getElementById('meetingStartTime').value = startTime;
        document.getElementById('meetingEndTime').value = endTime;
        document.getElementById('meetingDate').value = date;
        
        // Update visual feedback
        document.querySelectorAll('.suggestion-card').forEach(card => {
            card.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
        });
        
        // Highlight selected card
        event.currentTarget.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
        
        // Enable save button
        document.getElementById('saveMeetingBtn').disabled = false;
        
        // Show confirmation
        const availabilityResults = document.getElementById('availabilityResults');
        availabilityResults.innerHTML = `<i class="bi bi-check-circle me-2 text-success"></i><strong>Selected:</strong> ${startTime} - ${endTime} on ${new Date(date).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })}`;
        availabilityResults.className = 'alert alert-info';
        availabilityResults.style.display = 'block';
    };

    // Create Google Meet link functionality
    document.addEventListener('DOMContentLoaded', function() {
        const createGoogleMeetBtn = document.getElementById('createGoogleMeetBtn');
        const meetingLinkInput = document.getElementById('meetingLink');
        const meetingTitleInput = document.getElementById('meetingTitle');
        const meetingDescriptionInput = document.getElementById('meetingDescription');
        const meetingDateInput = document.getElementById('meetingDate');
        const meetingStartTimeInput = document.getElementById('meetingStartTime');
        const meetingEndTimeInput = document.getElementById('meetingEndTime');

        if (createGoogleMeetBtn) {
            createGoogleMeetBtn.addEventListener('click', function() {
                // Validate that time and date are selected
                const date = meetingDateInput.value;
                const startTime = meetingStartTimeInput.value;
                const endTime = meetingEndTimeInput.value;

                if (!date || !startTime || !endTime) {
                    alert('Please select a date and time first by choosing from the time suggestions.');
                    return;
                }

                // Show loading state
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Creating Meeting...';
                this.disabled = true;

                // Get meeting details
                const title = meetingTitleInput.value || 'Team Meeting';
                const description = meetingDescriptionInput.value || 'Project team meeting';

                // Create time-restricted Google Meet link
                createTimedGoogleMeetLink(title, description, date, startTime, endTime)
                    .then(meetingData => {
                        // Set the meeting link with time validation
                        meetingLinkInput.value = meetingData.accessUrl;
                        
                        // Store meeting data for validation
                        window.currentMeetingData = meetingData;
                        
                        // Show success state
                        this.innerHTML = '<i class="bi bi-check-circle me-2"></i>Meeting Created!';
                        this.classList.remove('btn-outline-primary');
                        this.classList.add('btn-success');
                        
                        // Show meeting info
                        showMeetingInfo(meetingData);
                    })
                    .catch(error => {
                        console.error('Error creating meeting:', error);
                        this.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Error Creating Meeting';
                        this.classList.remove('btn-outline-primary');
                        this.classList.add('btn-danger');
                    })
                    .finally(() => {
                        // Reset button after 3 seconds
                        setTimeout(() => {
                            this.innerHTML = originalText;
                            this.disabled = false;
                            this.classList.remove('btn-success', 'btn-danger');
                            this.classList.add('btn-outline-primary');
                        }, 3000);
                    });
            });
        }

        // Test meeting button for current time
        const testMeetingBtn = document.getElementById('testMeetingBtn');
        if (testMeetingBtn) {
            testMeetingBtn.addEventListener('click', function() {
                // Show loading state
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Creating Test...';
                this.disabled = true;

                // Get current time and create 30-minute meeting
                const now = new Date();
                const endTime = new Date(now.getTime() + 30 * 60 * 1000); // 30 minutes later

                // Format times for the function
                const currentDate = now.toISOString().split('T')[0];
                const currentTime = now.toTimeString().slice(0, 5);
                const testEndTime = endTime.toTimeString().slice(0, 5);

                // Get meeting details or use defaults
                const title = meetingTitleInput.value || 'Test Meeting';
                const description = meetingDescriptionInput.value || 'Test meeting for current time';

                // Create time-restricted Google Meet link for current time
                createTimedGoogleMeetLink(title, description, currentDate, currentTime, testEndTime)
                    .then(meetingData => {
                        // Set the meeting link with time validation
                        meetingLinkInput.value = meetingData.accessUrl;
                        
                        // Store meeting data for validation
                        window.currentMeetingData = meetingData;
                        
                        // Show success state
                        this.innerHTML = '<i class="bi bi-check-circle me-2"></i>Test Created!';
                        this.classList.remove('btn-outline-secondary');
                        this.classList.add('btn-success');
                        
                        // Show meeting info with test indicator
                        showTestMeetingInfo(meetingData);
                    })
                    .catch(error => {
                        console.error('Error creating test meeting:', error);
                        this.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Error';
                        this.classList.remove('btn-outline-secondary');
                        this.classList.add('btn-danger');
                    })
                    .finally(() => {
                        // Reset button after 3 seconds
                        setTimeout(() => {
                            this.innerHTML = originalText;
                            this.disabled = false;
                            this.classList.remove('btn-success', 'btn-danger');
                            this.classList.add('btn-outline-secondary');
                        }, 3000);
                    });
            });
        }
    });

    // Function to create time-restricted Google Meet link
    async function createTimedGoogleMeetLink(title, description, date, startTime, endTime) {
        return new Promise((resolve) => {
            // Create unique meeting ID
            const meetingId = generateMeetingId();
            
            // Create meeting start and end datetime objects
            const startDateTime = new Date(`${date}T${startTime}:00`);
            const endDateTime = new Date(`${date}T${endTime}:00`);
            
            // Use Google Meet's instant meeting URL (new meeting each time)
            const instantMeetUrl = 'https://meet.google.com/new';
            
            // Create the meeting data
            const meetingData = {
                id: meetingId,
                title: title,
                description: description,
                startTime: startDateTime,
                endTime: endDateTime,
                googleMeetUrl: instantMeetUrl,
                accessUrl: `${window.location.origin}${window.location.pathname}?joinMeeting=${meetingId}`,
                calendarUrl: createCalendarLink(title, description, date, startTime, endTime, instantMeetUrl)
            };
            
            // Store meeting data in localStorage for validation
            const meetings = JSON.parse(localStorage.getItem('scheduledMeetings') || '{}');
            meetings[meetingId] = meetingData;
            localStorage.setItem('scheduledMeetings', JSON.stringify(meetings));
            
            // Simulate API call delay
            setTimeout(() => {
                resolve(meetingData);
            }, 1500);
        });
    }

    // Function to create calendar link with Google Meet
    function createCalendarLink(title, description, date, startTime, endTime, meetingUrl) {
        const baseUrl = 'https://calendar.google.com/calendar/render';
        const startDateTime = formatDateForGoogle(new Date(`${date}T${startTime}:00`));
        const endDateTime = formatDateForGoogle(new Date(`${date}T${endTime}:00`));
        
        const params = new URLSearchParams({
            action: 'TEMPLATE',
            text: title,
            details: `${description}\n\nJoin Google Meet: ${meetingUrl}\n\nNote: This meeting link is only active during the scheduled time.`,
            dates: `${startDateTime}/${endDateTime}`,
            add: 'conferenceType=hangoutsMeet'
        });
        
        return `${baseUrl}?${params.toString()}`;
    }

    // Generate unique meeting ID
    function generateMeetingId() {
        const chars = 'abcdefghijklmnopqrstuvwxyz';
        const segments = [];
        
        // Generate 3 segments of 3-4 characters each (like Google Meet format)
        for (let i = 0; i < 3; i++) {
            let segment = '';
            const length = i === 1 ? 4 : 3; // Middle segment is 4 chars
            for (let j = 0; j < length; j++) {
                segment += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            segments.push(segment);
        }
        
        return segments.join('-');
    }

    // Helper function to format date for Google Calendar
    function formatDateForGoogle(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hour = String(date.getHours()).padStart(2, '0');
        const minute = String(date.getMinutes()).padStart(2, '0');
        const second = '00';
        
        return `${year}${month}${day}T${hour}${minute}${second}`;
    }

    // Send system message to project chat when meeting is created (similar to timeline editor)
    async function sendMeetingSystemMessage(meetingDate, startTime, meetingTitle, timeDifference, meetingLink = '') {
        try {
            // Get current user info first
            const userResponse = await fetch('src/model/get_current_user.php', {
                method: 'GET',
                credentials: 'include'
            });
            
            const userData = await userResponse.json();
            
            // Get user name from response or use fallback
            let currentUserName = 'Someone';
            if (userData && userData.success && userData.isLoggedIn && userData.user) {
                currentUserName = userData.user.name || 'Someone';
            }
            
            // Create appropriate message based on timing
            let systemMessage = '';
            const meetingDateTime = new Date(`${meetingDate}T${startTime}:00`);
            const formattedTime = meetingDateTime.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            const formattedDate = meetingDateTime.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
            
            if (timeDifference <= 5 && timeDifference >= -5) {
                // Meeting starting now
                systemMessage = `🚀 ${currentUserName} just scheduled "${meetingTitle}" and it's starting NOW! `;
                if (meetingLink) {
                    systemMessage += `[Join Meeting](${meetingLink})`;
                }
            } else if (timeDifference <= 30) {
                // Meeting starting soon
                const minutesUntil = Math.max(1, Math.round(timeDifference));
                systemMessage = `🔔 ${currentUserName} scheduled "${meetingTitle}" for ${formattedTime} (in ${minutesUntil} minute${minutesUntil > 1 ? 's' : ''}). `;
                if (meetingLink) {
                    systemMessage += `[Join Meeting](${meetingLink})`;
                }
            } else {
                // Meeting scheduled for later
                systemMessage = `📅 ${currentUserName} scheduled "${meetingTitle}" for ${formattedTime} on ${formattedDate}. `;
                if (meetingLink) {
                    systemMessage += `[Meeting Link](${meetingLink})`;
                }
            }
            
            // Send the system message
            const systemMessageData = new FormData();
            systemMessageData.append('projectId', projectId);
            systemMessageData.append('message', systemMessage);
            
            return fetch('src/model/send_system_chat_message.php', {
                method: 'POST',
                body: systemMessageData
            });
            
        } catch (error) {
            console.error('Error sending meeting system message:', error);
            throw error;
        }
    }

    // Show meeting information after creation
    function showMeetingInfo(meetingData) {
        const availabilityResults = document.getElementById('availabilityResults');
        const meetingTime = meetingData.startTime.toLocaleString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        const endTime = meetingData.endTime.toLocaleString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        availabilityResults.innerHTML = `
            <div class="d-flex align-items-start">
                <i class="bi bi-check-circle-fill me-2 text-success mt-1"></i>
                <div>
                    <strong>📅 Scheduled Meeting Created!</strong><br>
                    <small class="text-muted">${meetingTime} - ${endTime}</small><br>
                    <small class="text-info">Meeting ID: ${meetingData.id}</small><br>
                    <div class="mt-2">
                        <a href="${meetingData.accessUrl}" class="btn btn-sm btn-primary me-2">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Join Link (Time Restricted)
                        </a>
                        <a href="${meetingData.calendarUrl}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-calendar-plus me-1"></i>Add to Calendar
                        </a>
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Join link will be active 5 minutes before the meeting starts.
                    </small>
                </div>
            </div>
        `;
        availabilityResults.className = 'alert alert-success';
        availabilityResults.style.display = 'block';
    }

    // Show test meeting information after creation
    function showTestMeetingInfo(meetingData) {
        const availabilityResults = document.getElementById('availabilityResults');
        const meetingTime = meetingData.startTime.toLocaleString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
        const endTime = meetingData.endTime.toLocaleString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        availabilityResults.innerHTML = `
            <div class="d-flex align-items-start">
                <i class="bi bi-play-circle-fill me-2 text-warning mt-1"></i>
                <div>
                    <strong>🧪 Test Meeting Created!</strong><br>
                    <small class="text-muted">Active: ${meetingTime} - ${endTime} (30 min)</small><br>
                    <small class="text-info">Meeting ID: ${meetingData.id}</small><br>
                    <div class="mt-2">
                        <a href="${meetingData.accessUrl}" target="_blank" class="btn btn-sm btn-warning me-2">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Test Join Link
                        </a>
                        <button onclick="window.open('${meetingData.googleMeetUrl}', '_blank')" class="btn btn-sm btn-success">
                            <i class="bi bi-camera-video me-1"></i>Direct Meet
                        </button>
                    </div>
                </div>
            </div>
        `;
        availabilityResults.className = 'alert alert-warning';
        availabilityResults.style.display = 'block';
    }

    // Check if meeting is accessible at current time
    async function validateMeetingAccess(meetingId) {
        // First, check localStorage for Google Meet style meetings
        const meetings = JSON.parse(localStorage.getItem('scheduledMeetings') || '{}');
        let meeting = meetings[meetingId];
        
        // If not found in localStorage, check the database for regular meetings
        if (!meeting) {
            try {
                meeting = await getMeetingFromDatabase(meetingId);
            } catch (error) {
                console.error('Error fetching meeting from database:', error);
                return { accessible: false, reason: 'Meeting not found' };
            }
        }
        
        if (!meeting) {
            return { accessible: false, reason: 'Meeting not found' };
        }
        
        const now = new Date();
        let startTime, endTime;
        
        // Handle different meeting data formats
        if (meeting.startTime instanceof Date) {
            // Google Meet style meeting (from localStorage)
            startTime = meeting.startTime;
            endTime = meeting.endTime;
        } else {
            // Database meeting - handle different datetime formats
            if (meeting.startTime.includes('T') && meeting.startTime.includes('Z')) {
                // Full datetime strings (ISO format)
                startTime = new Date(meeting.startTime);
                endTime = new Date(meeting.endTime);
            } else {
                // Separate date and time components
                const meetingDate = meeting.date || new Date().toISOString().split('T')[0];
                startTime = new Date(`${meetingDate}T${meeting.startTime}:00`);
                endTime = new Date(`${meetingDate}T${meeting.endTime}:00`);
            }
        }
        
        // Allow access 5 minutes before the meeting starts
        const earlyAccess = new Date(startTime.getTime() - 5 * 60 * 1000);
        
        if (now < earlyAccess) {
            const timeUntil = Math.ceil((startTime - now) / (1000 * 60));
            const startDateTime = startTime.toLocaleString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            return { 
                accessible: false, 
                reason: `Meeting starts in ${timeUntil} minutes on ${startDateTime}` 
            };
        }
        
        if (now > endTime) {
            const endDateTime = endTime.toLocaleString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            return { 
                accessible: false, 
                reason: `Meeting ended on ${endDateTime}` 
            };
        }
        
        return { 
            accessible: true, 
            meeting: meeting,
            meetingLink: meeting.googleMeetUrl || meeting.meetingLink || 'https://meet.google.com/new'
        };
    }
    
    // Get meeting from database by ID
    async function getMeetingFromDatabase(meetingId) {
        // Construct proper URL for the API endpoint
        let apiUrl;
        if (window.location.port === '3000') {
            apiUrl = `http://localhost/uiurp/src/model/get_meeting_by_id.php?id=${meetingId}`;
        } else {
            apiUrl = `src/model/get_meeting_by_id.php?id=${meetingId}`;
        }
        
        try {
            const response = await fetch(apiUrl, {
                method: 'GET',
                credentials: 'include'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            console.log('Database meeting response:', data);
            
            if (data.success && data.meeting) {
                console.log('Meeting data from database:', data.meeting);
                return data.meeting;
            } else {
                console.log('No meeting found in database response');
                return null;
            }
        } catch (error) {
            console.error('Error fetching meeting from database:', error);
            return null;
        }
    }

    // Handle meeting join from URL parameter
    document.addEventListener('DOMContentLoaded', async function() {
        const urlParams = new URLSearchParams(window.location.search);
        const joinMeetingId = urlParams.get('joinMeeting');
        
        if (joinMeetingId) {
            try {
                const access = await validateMeetingAccess(joinMeetingId);
                
                if (access.accessible) {
                    // Directly redirect to Google Meet for valid meetings
                    const meetingUrl = access.meetingLink || access.meeting.googleMeetUrl || access.meeting.meetingLink;
                    console.log('Redirecting to Google Meet:', meetingUrl);
                    window.location.href = meetingUrl;
                    return; // Don't execute cleanup
                } else {
                    // Show better alert without redirection
                    showMeetingAccessAlert(access.reason, access.meeting);
                    // Clean URL without reloading the page
                    history.replaceState({}, document.title, window.location.pathname);
                    return;
                }
            } catch (error) {
                console.error('Error validating meeting access:', error);
                showMeetingAccessAlert('Error joining meeting. Please try again.', null);
                // Clean URL without reloading the page
                history.replaceState({}, document.title, window.location.pathname);
                return;
            }
        }
    });

    // Show meeting access alert with better UI
    function showMeetingAccessAlert(reason, meeting) {
        // Create a better alert modal instead of browser alert
        const alertHtml = `
            <div class="modal fade" id="meetingAccessModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Meeting Access Restricted
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning mb-3">
                                <strong>${reason}</strong>
                            </div>
                            ${meeting ? `
                                <div class="meeting-info">
                                    <h6><i class="bi bi-calendar-event me-2"></i>${meeting.title || 'Meeting'}</h6>
                                    ${meeting.description ? `<p class="text-muted small mb-2">${meeting.description}</p>` : ''}
                                    <div class="d-flex align-items-center text-muted mb-1">
                                        <i class="bi bi-clock me-2"></i>
                                        <small>Access allowed 5 minutes before meeting starts</small>
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-arrow-left me-1"></i>Back to Project
                            </button>
                            ${meeting ? `
                                <button type="button" class="btn btn-primary" onclick="showMeetingDetails('${meeting.id || meeting.meetingId}')">
                                    <i class="bi bi-info-circle me-1"></i>Meeting Details
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('meetingAccessModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add modal to page
        document.body.insertAdjacentHTML('beforeend', alertHtml);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('meetingAccessModal'));
        modal.show();
        
        // Clean up modal after it's hidden
        document.getElementById('meetingAccessModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }

    // Handle meeting join click without page navigation
    async function handleMeetingJoin(meetingId) {
        try {
            const access = await validateMeetingAccess(meetingId);
            
            if (access.accessible) {
                // Directly redirect to Google Meet for valid meetings
                const meetingUrl = access.meetingLink || access.meeting.googleMeetUrl || access.meeting.meetingLink;
                console.log('Redirecting to Google Meet:', meetingUrl);
                window.open(meetingUrl, '_blank'); // Open in new tab
            } else {
                // Show better alert without any navigation
                showMeetingAccessAlert(access.reason, access.meeting);
            }
        } catch (error) {
            console.error('Error validating meeting access:', error);
            showMeetingAccessAlert('Error joining meeting. Please try again.', null);
        }
    }

    // Show meeting details in meetings section
    function showMeetingDetails(meetingId) {
        // Close the access modal
        const accessModal = bootstrap.Modal.getInstance(document.getElementById('meetingAccessModal'));
        if (accessModal) {
            accessModal.hide();
        }
        
        // Scroll to meetings section and highlight the specific meeting
        const meetingsSection = document.getElementById('meetings-container');
        if (meetingsSection) {
            meetingsSection.scrollIntoView({ behavior: 'smooth' });
            
            // Highlight the specific meeting card if found
            setTimeout(() => {
                const meetingCards = document.querySelectorAll('.meeting-card');
                meetingCards.forEach(card => {
                    const cardContent = card.innerHTML;
                    if (cardContent.includes(meetingId)) {
                        card.style.boxShadow = '0 0 15px rgba(255, 193, 7, 0.6)';
                        card.style.border = '2px solid #ffc107';
                        setTimeout(() => {
                            card.style.boxShadow = '';
                            card.style.border = '';
                        }, 3000);
                    }
                });
            }, 500);
        }
    }
    

    
    // Generate meeting suggestions
    function generateMeetingSuggestions(memberSchedules) {
        const availabilityResults = document.getElementById('availabilityResults');
        const suggestedTimes = document.getElementById('suggestedTimes');
        const timeSlots = document.getElementById('timeSlots');
        
        // Define possible meeting times (business hours)
        const meetingDuration = 60; // 1 hour default
        const possibleTimes = [
            '09:00', '10:00', '11:00', '14:00', '15:00', '16:00'
        ];
        
        // Get next 7 days (excluding weekends for now)
        const suggestions = [];
        const today = new Date();
        
        for (let dayOffset = 1; dayOffset <= 14; dayOffset++) {
            const targetDate = new Date(today);
            targetDate.setDate(today.getDate() + dayOffset);
            
            // Skip weekends
            if (targetDate.getDay() === 0 || targetDate.getDay() === 6) {
                continue;
            }
            
            const dayName = targetDate.toLocaleDateString('en-US', { weekday: 'long' });
            const dateString = targetDate.toISOString().split('T')[0];
            
            possibleTimes.forEach(startTime => {
                const endTime = addMinutesToTime(startTime, meetingDuration);
                
                // Check if this time slot is available for all members
                const availability = analyzeTimeSlot(dateString, startTime, endTime, memberSchedules);
                
                if (suggestions.length < 6) { // Limit to 6 suggestions
                    suggestions.push({
                        date: dateString,
                        displayDate: targetDate.toLocaleDateString('en-US', { 
                            weekday: 'short', 
                            month: 'short', 
                            day: 'numeric' 
                        }),
                        startTime: startTime,
                        endTime: endTime,
                        available: availability.availableCount,
                        total: memberSchedules.length,
                        conflicts: availability.conflicts,
                        score: availability.availableCount / memberSchedules.length
                    });
                }
            });
        }
        
        // Sort by availability score (highest first)
        suggestions.sort((a, b) => b.score - a.score);
        
        // Display results
        if (suggestions.length > 0) {
            availabilityResults.innerHTML = '<i class="bi bi-lightbulb me-2 text-success"></i><strong>Found optimal meeting times!</strong>';
            availabilityResults.className = 'alert alert-success';
            
            // Render time slot suggestions
            renderTimeSuggestions(suggestions.slice(0, 6)); // Show top 6
            suggestedTimes.style.display = 'block';
        } else {
            availabilityResults.innerHTML = '<i class="bi bi-exclamation-triangle me-2 text-warning"></i><strong>No optimal times found in the next 2 weeks</strong>';
            availabilityResults.className = 'alert alert-warning';
        }
    }
    
    // Analyze a specific time slot with detailed conflict information
    function analyzeTimeSlotDetailed(date, startTime, endTime, memberSchedules, dayName) {
        let availableCount = 0;
        let conflicts = [];
        let totalMembers = memberSchedules.length;
        
        console.log(`Analyzing ${startTime}-${endTime} on ${dayName} for ${totalMembers} members`);
        
        memberSchedules.forEach(member => {
            let hasConflict = false;
            let memberConflicts = [];
            
            // Get this member's schedule for the specific day
            const daySchedule = member.schedule.filter(item => item.day === dayName);
            
            console.log(`${member.name} (${member.role}): ${daySchedule.length} schedule items on ${dayName}`);
            
            if (daySchedule.length === 0) {
                // Member has no schedule items for this day - completely free
                availableCount++;
                console.log(`  → ${member.name} is completely free`);
            } else {
                // Check each schedule item for conflicts
                daySchedule.forEach(scheduleItem => {
                    if (timesOverlap(startTime, endTime, scheduleItem.startTime, scheduleItem.endTime)) {
                        hasConflict = true;
                        memberConflicts.push({
                            title: scheduleItem.title,
                            time: `${scheduleItem.startTime}-${scheduleItem.endTime}`,
                            type: scheduleItem.type || 'Unknown'
                        });
                        console.log(`  → CONFLICT: ${scheduleItem.title} (${scheduleItem.startTime}-${scheduleItem.endTime})`);
                    } else {
                        console.log(`  → No conflict with: ${scheduleItem.title} (${scheduleItem.startTime}-${scheduleItem.endTime})`);
                    }
                });
                
                if (!hasConflict) {
                    // Member has schedule items but none conflict with our time slot
                    availableCount++;
                    console.log(`  → ${member.name} is available (no conflicts)`);
                } else {
                    // Add conflicts to the main conflicts array
                    conflicts.push({
                        member: member.name,
                        role: member.role,
                        conflicts: memberConflicts
                    });
                }
            }
        });
        
        const isCompletelyFree = availableCount === totalMembers;
        console.log(`Result: ${availableCount}/${totalMembers} available. Completely free: ${isCompletelyFree}`);
        
        return {
            availableCount: availableCount,
            totalMembers: totalMembers,
            conflicts: conflicts,
            isCompletelyFree: isCompletelyFree
        };
    }
    
    // Legacy function for backward compatibility
    function analyzeTimeSlot(date, startTime, endTime, memberSchedules) {
        const targetDate = new Date(date);
        const dayName = targetDate.toLocaleDateString('en-US', { weekday: 'long' });
        
        const detailed = analyzeTimeSlotDetailed(date, startTime, endTime, memberSchedules, dayName);
        
        // Convert detailed conflicts to simple format for backward compatibility
        const simpleConflicts = [];
        detailed.conflicts.forEach(memberConflict => {
            memberConflict.conflicts.forEach(conflict => {
                simpleConflicts.push({
                    member: memberConflict.member,
                    conflict: conflict.title
                });
            });
        });
        
        return {
            availableCount: detailed.availableCount,
            conflicts: simpleConflicts
        };
    }
    
    // Render time suggestions
    function renderTimeSuggestions(suggestions) {
        const timeSlots = document.getElementById('timeSlots');
        timeSlots.innerHTML = '';
        
        // Show only top 8 suggestions for the selected date
        const topSuggestions = suggestions.slice(0, 8);
        
        topSuggestions.forEach((suggestion, index) => {
            const timeCard = document.createElement('div');
            timeCard.className = 'time-suggestion-card';
            timeCard.setAttribute('data-date', suggestion.date);
            timeCard.setAttribute('data-start', suggestion.startTime);
            timeCard.setAttribute('data-end', suggestion.endTime);
            
            const scorePercentage = Math.round(suggestion.score * 100);
            const isCompletelyFree = suggestion.isCompletelyFree || scorePercentage === 100;
            
            let scoreClass = 'success';
            let cardClass = timeCard.className;
            let badgeText = `${scorePercentage}%`;
            let buttonClass = 'btn-outline-primary';
            
            if (isCompletelyFree) {
                scoreClass = 'success';
                cardClass += ' border-success';
                badgeText = 'FREE';
                buttonClass = 'btn-success';
            } else if (scorePercentage >= 70) {
                scoreClass = 'warning';
                cardClass += ' border-warning';
            } else {
                scoreClass = 'danger';
                cardClass += ' border-danger';
            }
            
            timeCard.className = cardClass;
            
            timeCard.innerHTML = `
                <div class="time-info">
                    <div class="fw-bold">${formatTime(suggestion.startTime)}</div>
                    <div class="text-muted small">${formatTime(suggestion.endTime)}</div>
                    ${isCompletelyFree ? '<div class="text-success small fw-bold">All members free!</div>' : ''}
                </div>
                <div class="availability-info mt-2">
                    <span class="badge bg-${scoreClass} mb-2">${badgeText}</span>
                    <div class="small text-muted">${suggestion.available}/${suggestion.total} available</div>
                    ${suggestion.conflicts && suggestion.conflicts.length > 0 ? `
                        <div class="conflicts mt-1">
                            <small class="text-warning">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                ${suggestion.conflicts.length} conflict(s)
                            </small>
                        </div>
                    ` : ''}
                </div>
                <button class="btn ${buttonClass} btn-sm mt-2 w-100 use-time-btn">
                    <i class="bi bi-check-circle me-1"></i>
                    ${isCompletelyFree ? 'Select (Perfect!)' : 'Select'}
                </button>
            `;
            
            timeSlots.appendChild(timeCard);
        });
        
        // Add click handlers to use suggested times
        timeSlots.querySelectorAll('.use-time-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const card = this.closest('.time-suggestion-card');
                const startTime = card.dataset.start;
                const endTime = card.dataset.end;
                
                // Set the hidden form values
                document.getElementById('meetingStartTime').value = startTime;
                document.getElementById('meetingEndTime').value = endTime;
                
                // Check availability for this specific time
                analyzeAvailability(selectedDate, startTime, endTime, memberScheduleData);
                
                // Highlight the selected card
                timeSlots.querySelectorAll('.time-suggestion-card').forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                
                showToast('Meeting time selected!', 'success');
            });
        });
    }
    
    // Add minutes to time string
    function addMinutesToTime(timeString, minutes) {
        const [hours, mins] = timeString.split(':').map(Number);
        const date = new Date();
        date.setHours(hours, mins, 0, 0);
        date.setMinutes(date.getMinutes() + minutes);
        
        return date.toTimeString().slice(0, 5);
    }
    
    // Analyze availability with detailed member breakdown
    function analyzeAvailability(date, startTime, endTime, memberSchedules) {
        const targetDate = new Date(date);
        const dayName = targetDate.toLocaleDateString('en-US', { weekday: 'long' });
        
        const availabilityResults = document.getElementById('availabilityResults');
        const memberSchedulesDiv = document.getElementById('memberSchedules');
        
        // Use the detailed analysis function
        const analysis = analyzeTimeSlotDetailed(date, startTime, endTime, memberSchedules, dayName);
        
        let availableMembers = [];
        let conflictingMembersDetails = [];
        
        // Build available members list
        memberSchedules.forEach(member => {
            const memberHasConflict = analysis.conflicts.some(conflict => conflict.member === member.name);
            if (!memberHasConflict) {
                availableMembers.push(member.name);
            }
        });
        
        // Build conflicting members details
        analysis.conflicts.forEach(memberConflict => {
            let conflictText = '';
            memberConflict.conflicts.forEach(conflict => {
                conflictText += `${conflict.title} (${formatTime(conflict.time.split('-')[0])} - ${formatTime(conflict.time.split('-')[1])}); `;
            });
            
            conflictingMembersDetails.push({
                name: memberConflict.member,
                role: memberConflict.role,
                conflicts: conflictText
            });
        });
        
        // Display results
        let resultHTML = '';
        if (analysis.isCompletelyFree) {
            resultHTML = '<i class="bi bi-check-circle me-2 text-success"></i><strong>Perfect! All team members are available!</strong>';
            availabilityResults.className = 'alert alert-success';
        } else if (analysis.availableCount > analysis.totalMembers * 0.7) {
            resultHTML = `<i class="bi bi-exclamation-triangle me-2 text-warning"></i><strong>Good availability: ${analysis.availableCount}/${analysis.totalMembers} members available</strong>`;
            availabilityResults.className = 'alert alert-warning';
        } else {
            resultHTML = `<i class="bi bi-x-circle me-2 text-danger"></i><strong>Limited availability: ${analysis.availableCount}/${analysis.totalMembers} members available</strong>`;
            availabilityResults.className = 'alert alert-danger';
        }
        
        availabilityResults.innerHTML = resultHTML;
        availabilityResults.style.display = 'block';
        
        // Show detailed schedule information
        let scheduleHTML = `
            <div class="mt-3">
                <h6>Detailed Availability for ${formatTime(startTime)} - ${formatTime(endTime)} on ${dayName}:</h6>
        `;
        
        // Available members
        if (availableMembers.length > 0) {
            scheduleHTML += `
                <div class="mb-3 p-2 bg-success bg-opacity-10 rounded">
                    <strong class="text-success">
                        <i class="bi bi-check-circle me-1"></i>Available Members (${availableMembers.length}):
                    </strong>
                    <div class="mt-1">${availableMembers.join(', ')}</div>
                </div>
            `;
        }
        
        // Conflicting members
        if (conflictingMembersDetails.length > 0) {
            scheduleHTML += `
                <div class="mb-3 p-2 bg-warning bg-opacity-10 rounded">
                    <strong class="text-warning">
                        <i class="bi bi-exclamation-triangle me-1"></i>Members with Conflicts (${conflictingMembersDetails.length}):
                    </strong>
                    <div class="mt-2">
            `;
            
            conflictingMembersDetails.forEach(member => {
                scheduleHTML += `
                    <div class="mb-2 border-start border-warning ps-2">
                        <strong>${member.name}</strong> <small class="text-muted">(${member.role})</small>
                        <div class="small text-muted">${member.conflicts}</div>
                    </div>
                `;
            });
            
            scheduleHTML += '</div></div>';
        }
        
        scheduleHTML += '</div>';
        memberSchedulesDiv.innerHTML = scheduleHTML;
        memberSchedulesDiv.style.display = 'block';
    }
    
    // Check if two time ranges overlap
    function timesOverlap(start1, end1, start2, end2) {
        return start1 < end2 && end1 > start2;
    }
    
    // Format time for display
    function formatTime(timeString) {
        try {
            const [hours, minutes] = timeString.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const hour12 = hour % 12 || 12;
            return `${hour12}:${minutes} ${ampm}`;
        } catch (e) {
            return timeString;
        }
    }
    
    // Validate URL
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
    
    // Save meeting
    function saveMeeting() {
        const title = document.getElementById('meetingTitle').value.trim();
        const date = document.getElementById('meetingDate').value;
        const startTime = document.getElementById('meetingStartTime').value;
        const endTime = document.getElementById('meetingEndTime').value;
        const description = document.getElementById('meetingDescription').value.trim();
        const meetingLink = document.getElementById('meetingLink').value.trim();
        
        if (!title || !date) {
            showToast('Please fill in the meeting title and select a date', 'warning');
            return;
        }
        
        if (!startTime || !endTime) {
            showToast('Please select a time slot from the suggestions', 'warning');
            return;
        }
        
        if (startTime >= endTime) {
            showToast('End time must be after start time', 'warning');
            return;
        }
        
        // Validate meeting link if provided
        if (meetingLink && !isValidUrl(meetingLink)) {
            showToast('Please enter a valid meeting link', 'warning');
            return;
        }
        
        const saveMeetingBtn = document.getElementById('saveMeetingBtn');
        const originalText = saveMeetingBtn.innerHTML;
        saveMeetingBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Saving...';
        saveMeetingBtn.disabled = true;
        
        const meetingData = {
            projectId: projectId,
            title: title,
            date: date,
            startTime: startTime,
            endTime: endTime,
            description: description,
            meetingLink: meetingLink,
            type: 'Project Meeting'
        };
        
        // Construct proper URL for the API endpoint
        // If we're on a development server (port 3000), point to XAMPP on default port
        let apiUrl;
        if (window.location.port === '3000') {
            // Development server - point to XAMPP
            apiUrl = window.location.protocol + '//' + window.location.hostname + '/uiurp/src/model/save_project_meeting.php';
        } else {
            // Same server - use relative path with proper construction
            apiUrl = window.location.protocol + '//' + window.location.hostname + 
                    (window.location.port && window.location.port !== '80' && window.location.port !== '443' 
                     ? ':' + window.location.port : '') + 
                    window.location.pathname.replace(/\/[^\/]*$/, '') + '/src/model/save_project_meeting.php';
        }
        
        console.log('Making request to:', apiUrl);
        
        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(meetingData),
            credentials: 'include'  // Include session cookies for cross-origin
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response OK:', response.ok);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text().then(text => {
                console.log('Raw response text:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text that failed to parse:', text);
                    throw new Error('Invalid JSON response: ' + text.substring(0, 200) + '...');
                }
            });
        })
        .then(data => {
            console.log('Parsed response data:', data);
            if (data.success) {
                showToast('Meeting scheduled successfully!', 'success');
                
                // Check if the meeting was created for current time or soon, and refresh chat
                const meetingDate = document.getElementById('meetingDate').value;
                const startTime = document.getElementById('meetingStartTime').value;
                
                if (meetingDate && startTime) {
                    const meetingDateTime = new Date(`${meetingDate}T${startTime}:00`);
                    const currentDateTime = new Date();
                    const timeDifference = (meetingDateTime.getTime() - currentDateTime.getTime()) / (1000 * 60); // in minutes
                    
                    // If meeting is within the next 60 minutes, refresh chat to show system messages
                    if (timeDifference >= -5 && timeDifference <= 60) {
                        console.log('Meeting scheduled for soon, refreshing chat to show system messages');
                        
                        // Refresh chat messages after a short delay to allow system message to be sent
                        // Send additional real-time system message to chat (similar to timeline editor)
                        const title = document.getElementById('meetingTitle').value.trim();
                        const meetingLink = document.getElementById('meetingLink').value.trim();
                        sendMeetingSystemMessage(meetingDate, startTime, title, timeDifference, meetingLink)
                            .then(() => {
                                console.log('Real-time system message sent for meeting');
                            })
                            .catch(error => {
                                console.error('Error sending real-time system message:', error);
                            });
                        
                        setTimeout(() => {
                            // Check if chat overlay exists and is visible
                            const chatOverlay = document.getElementById('projectChatOverlay');
                            if (chatOverlay && chatOverlay.classList.contains('active')) {
                                // If chat is open, reload messages
                                if (typeof loadChatMessages === 'function') {
                                    loadChatMessages();
                                } else if (typeof window.loadChatMessages === 'function') {
                                    window.loadChatMessages();
                                }
                            }
                            
                            // Also trigger meeting notification refresh if available
                            if (typeof window.globalMeetingNotifications !== 'undefined' && 
                                window.globalMeetingNotifications.refreshNotifications) {
                                window.globalMeetingNotifications.refreshNotifications();
                            }
                        }, 2000); // 2 second delay to allow system message to be processed
                    }
                }
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('meetingModal'));
                modal.hide();
                
                // Reset form
                document.getElementById('meetingForm').reset();
                document.getElementById('availabilityResults').style.display = 'none';
                document.getElementById('memberSchedules').style.display = 'none';
                document.getElementById('timeSuggestionsContainer').style.display = 'none';
                document.getElementById('timeSuggestionsLoading').style.display = 'none';
                
                // Reload meetings
                loadProjectMeetings();
            } else {
                showToast(data.message || 'Error saving meeting', 'danger');
            }
        })
        .catch(error => {
            console.error('Error saving meeting:', error);
            showToast('Error saving meeting. Please try again.', 'danger');
        })
        .finally(() => {
            saveMeetingBtn.innerHTML = originalText;
            saveMeetingBtn.disabled = false;
        });
    }
    
    // Project-specific meeting notification integration
    function initializeProjectMeetingNotifications() {
        // Update the global notification system with this project's ID
        if (window.globalMeetingNotifications && projectId) {
            window.globalMeetingNotifications.setProjectId(projectId);
            console.log('Project meeting notifications initialized for project:', projectId);
        }
        
        // Listen for meeting notifications specific to this project
        document.addEventListener('meetingNotificationsProcessed', function(event) {
            const detail = event.detail;
            if (detail.projectId === projectId || !detail.projectId) {
                console.log('Project-specific meeting notifications processed:', detail);
                
                // Refresh chat messages to show new system messages
                if (typeof loadChatMessages === 'function') {
                    setTimeout(() => {
                        loadChatMessages();
                    }, 1000);
                }
            }
        });
    }
    
    // Initialize meeting feature when project is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for project to be loaded, then initialize meetings
        setTimeout(() => {
            if (projectId) {
                initializeMeetingFeature();
                initializeProjectMeetingNotifications();
            }
        }, 1000);
    });