document.addEventListener('DOMContentLoaded', function() {
    // Initialize particles.js
    particlesJS('particles-js', {
        "particles": {
            "number": {
                "value": 100,
                "density": {
                    "enable": true,
                    "value_area": 1500
                }
            },
            "color": {
                "value": ["#4cc9f0", "#7209b7", "#4361ee", "#3a0ca3", "#f72585"]
            },
            "shape": {
                "type": ["circle", "triangle", "polygon", "edge", "star"],
                "stroke": {
                    "width": 0,
                    "color": "#000000"
                },
                "polygon": {
                    "nb_sides": 6
                }
            },
            "opacity": {
                "value": 0.4,
                "random": true,
                "anim": {
                    "enable": true,
                    "speed": 0.8,
                    "opacity_min": 0.1,
                    "sync": false
                }
            },
            "size": {
                "value": 8,
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
                "distance": 180,
                "color": "#4cc9f0",
                "opacity": 0.3,
                "width": 1.2,
                "shadow": {
                    "enable": true,
                    "blur": 5,
                    "color": "#4cc9f0"
                }
            },
            "move": {
                "enable": true,
                "speed": 1.5,
                "direction": "none",
                "random": true,
                "straight": false,
                "out_mode": "out",
                "bounce": false,
                "attract": {
                    "enable": true,
                    "rotateX": 500,
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
                        "opacity": 0.8,
                        "color": "#f72585"
                    }
                },
                "bubble": {
                    "distance": 150,
                    "size": 12,
                    "duration": 2,
                    "opacity": 0.8,
                    "speed": 3
                },
                "repulse": {
                    "distance": 200,
                    "duration": 2
                },
                "push": {
                    "particles_nb": 10
                },
                "remove": {
                    "particles_nb": 2
                }
            }
        },
        "retina_detect": true,
        "fps_limit": 60
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
    
    // Keep particles active
    setInterval(() => {
        reinitializeParticlesIfNeeded();
    }, 2000);
    
    // Add fade-in effect for particles
    setTimeout(() => {
        document.body.classList.add('loaded');
    }, 300);
    
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
    
    // Add global click handler for adding particles but prevent it from stopping animation
    document.addEventListener('click', function(e) {
        // Don't create particles for clicks on interactive elements
        if (e.target.closest('a, button, input, .card, .form-control, .toggle-btn')) {
            return;
        }
        
        if (window.pJSDom && window.pJSDom[0] && window.pJSDom[0].pJS) {
            const pJS = window.pJSDom[0].pJS;
            
            // Create burst effect
            const burst = document.createElement('div');
            burst.classList.add('particle-burst');
            burst.style.left = e.pageX + 'px';
            burst.style.top = e.pageY + 'px';
            document.body.appendChild(burst);
            
            setTimeout(() => {
                burst.remove();
            }, 1000);
            
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
    
    // Initialize AOS
    AOS.init({
        duration: 800,
        once: false,
        mirror: true
    });
    
    // Elements
    const projectForm = document.getElementById('projectForm');
    const projectIdInput = document.getElementById('projectId');
    const addKeywordBtn = document.getElementById('addKeyword');
    const keywordInput = document.getElementById('keyword');
    const keywordsContainer = document.getElementById('keywordsContainer');
    const keywordsListInput = document.getElementById('keywordsList');
    const addMemberBtn = document.getElementById('addMember');
    const membersContainer = document.getElementById('membersContainer');
    const addTimelineItemBtn = document.getElementById('addTimelineItem');
    const timelineContainer = document.getElementById('timelineContainer');
    const coverImageInput = document.getElementById('coverImage');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    const imagePreview = document.getElementById('imagePreview');
    const removeImageBtn = document.getElementById('removeImage');
    const resetFormBtn = document.getElementById('resetForm');
    const spinnerOverlay = document.getElementById('spinner');
    const toastContainer = document.getElementById('toastContainer');
    const userProjectsList = document.getElementById('userProjectsList');
    const projectFilesInput = document.getElementById('projectFiles');
    const filesPreviewContainer = document.getElementById('filesPreview');
    const mediaFilesInput = document.getElementById('mediaFiles');
    const mediaPreviewContainer = document.getElementById('mediaPreview');
    const viewsCountInput = document.getElementById('viewsCount');
    const downloadsCountInput = document.getElementById('downloadsCount');
    const favoritesCountInput = document.getElementById('favoritesCount');
    
    // State
    let keywords = [];
    let isEditing = false;
    let originalImageUrl = '';
    let timelineItems = [];
    let isLoggedIn = false;
    let uploadedFiles = [];
    let uploadedMedia = [];
    
    // Global students data for dropdown
    let allStudentsData = [];
    
    // Global current user data
    let currentUserData = null;
    
    // Initialize - Load user's projects and faculty data
    loadUserProjects();
    loadFacultyForDropdown();
    loadStudentsForDropdown();
    loadCurrentUser();
    
    // Initialize the initial member row with student search
    setTimeout(() => {
        // First add a member row if none exists
        if (document.querySelectorAll('.member-row').length === 0) {
            addMemberRow();
        }
        
        const initialMemberRow = document.querySelector('.member-row');
        if (initialMemberRow) {
            setupStudentSearch(initialMemberRow);
        }
    }, 200);
    
    // Form submission
    projectForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        // Generate random stats
        generateRandomStats();
        
        // Get form data
        const formData = new FormData(projectForm);
        
        // Add members data
        const members = getMembersData();
        formData.append('members', JSON.stringify(members));
        
        // Add timeline data
        const timeline = getTimelineData();
        formData.append('timeline', JSON.stringify(timeline));
        
        // Add links data
        const links = {
            github: formData.get('github') || '',
            website: formData.get('website') || '',
            paper: formData.get('paper') || '',
            doi: formData.get('doi') || '',
            youtube: formData.get('youtube') || ''
        };
        formData.append('links', JSON.stringify(links));
        
        // Show loading spinner
        showSpinner();
        
        // Send form data to server
        const url = isEditing ? 'src/model/update_project.php' : 'src/model/create_project_debug.php';
        
        console.log('Submitting form to:', url);
        console.log('Form data:', Object.fromEntries(formData));
        
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text(); // Get as text first to debug
        })
        .then(responseText => {
            console.log('Raw response:', responseText);
            hideSpinner();
            
            try {
                const data = JSON.parse(responseText);
                console.log('Parsed JSON:', data);
                
                if (data.success) {
                    // Show success toast
                    showToast('Success', isEditing ? 'Project updated successfully!' : 'Project created successfully!', 'success');
                    
                    // Reset form
                    resetForm();
                    
                    // Reload user's projects
                    loadUserProjects();
                    
                    // Switch to Edit Projects tab
                    document.getElementById('edit-projects-tab').click();
                } else {
                    // Show error toast
                    showToast('Error', data.message || 'An error occurred. Please try again.', 'error');
                }
            } catch (parseError) {
                console.error('JSON Parse Error:', parseError);
                console.error('Raw response was:', responseText);
                showToast('Error', 'Invalid response from server.', 'error');
            }
        })
        .catch(error => {
            hideSpinner();
            console.error('Fetch Error:', error);
            showToast('Error', 'Network error: ' + error.message, 'error');
        });
    });
    
    // Generate random stats for the project
    function generateRandomStats() {
        // Generate random numbers from 10 to 1000
        viewsCountInput.value = Math.floor(Math.random() * 990) + 10;
        downloadsCountInput.value = Math.floor(Math.random() * 990) + 10;
        favoritesCountInput.value = Math.floor(Math.random() * 990) + 10;
    }
    
    // Handle file input change for project files
    projectFilesInput.addEventListener('change', function(e) {
        handleFilesUpload(this.files, filesPreviewContainer, 'files');
    });
    
    // Handle file input change for media files
    mediaFilesInput.addEventListener('change', function(e) {
        handleFilesUpload(this.files, mediaPreviewContainer, 'media');
    });
    
    // Handle files upload preview
    function handleFilesUpload(files, previewContainer, type) {
        if (!files || files.length === 0) return;
        
        previewContainer.innerHTML = '';
        
        // Process each file
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            
            // Check file size (max 25MB)
            if (file.size > 25 * 1024 * 1024) {
                showToast('Error', `File ${file.name} is too large. Maximum size is 25MB.`, 'error');
                continue;
            }
            
            const filePreview = document.createElement('div');
            filePreview.className = type === 'media' ? 'col-md-3 mb-2' : 'mb-2';
            
            // Different preview for media vs documents
            if (type === 'media' && file.type.startsWith('image/')) {
                // Image preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    filePreview.innerHTML = `
                        <div class="card">
                            <img src="${e.target.result}" class="card-img-top" style="height: 150px; object-fit: cover;">
                            <div class="card-body p-2">
                                <p class="card-text small text-truncate">${file.name}</p>
                            </div>
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
                
                if (type === 'media') {
                    uploadedMedia.push(file);
                }
            } else if (type === 'media' && file.type.startsWith('video/')) {
                // Video preview
                filePreview.innerHTML = `
                    <div class="card">
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
                            <i class="bi bi-film fs-1 text-primary"></i>
                        </div>
                        <div class="card-body p-2">
                            <p class="card-text small text-truncate">${file.name}</p>
                        </div>
                    </div>
                `;
                
                if (type === 'media') {
                    uploadedMedia.push(file);
                }
            } else {
                // Document preview
                const fileIcon = getFileIcon(file.name);
                filePreview.innerHTML = `
                    <div class="alert alert-light d-flex align-items-center">
                        <i class="${fileIcon} me-2 text-primary"></i>
                        <span class="text-truncate">${file.name}</span>
                        <span class="ms-auto badge bg-secondary">${formatFileSize(file.size)}</span>
                    </div>
                `;
                
                if (type === 'files') {
                    uploadedFiles.push(file);
                }
            }
            
            previewContainer.appendChild(filePreview);
        }
    }
    
    // Helper function to get icon based on file extension
    function getFileIcon(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        
        switch (ext) {
            case 'pdf':
                return 'bi bi-file-earmark-pdf';
            case 'doc':
            case 'docx':
                return 'bi bi-file-earmark-word';
            case 'xls':
            case 'xlsx':
                return 'bi bi-file-earmark-excel';
            case 'ppt':
            case 'pptx':
                return 'bi bi-file-earmark-slides';
            case 'zip':
            case 'rar':
            case '7z':
                return 'bi bi-file-earmark-zip';
            case 'txt':
                return 'bi bi-file-earmark-text';
            case 'csv':
                return 'bi bi-file-earmark-spreadsheet';
            default:
                return 'bi bi-file-earmark';
        }
    }
    
    // Helper function to format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Handle file input change
    coverImageInput.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            
            // Check file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                showToast('Error', 'Image size should be less than 5MB', 'error');
                this.value = '';
                return;
            }
            
            // Check file type
            const fileType = file.type;
            if (!fileType.match('image.*')) {
                showToast('Error', 'Please select an image file', 'error');
                this.value = '';
                return;
            }
            
            // Preview image
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreviewContainer.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Remove image button
    removeImageBtn.addEventListener('click', function() {
        coverImageInput.value = '';
        imagePreviewContainer.style.display = 'none';
        imagePreview.src = '';
    });
    
    // Add keyword
    addKeywordBtn.addEventListener('click', function() {
        addKeyword();
    });
    
    keywordInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addKeyword();
        }
    });
    
    // Add member
    addMemberBtn.addEventListener('click', function() {
        addMemberRow();
    });
    
    // Add timeline item
    addTimelineItemBtn.addEventListener('click', function() {
        addTimelineItem();
    });
    
    // Reset form
    resetFormBtn.addEventListener('click', function() {
        if (confirm('Are you sure you want to reset the form? All unsaved changes will be lost.')) {
            resetForm();
        }
    });
    
    // Functions
    function validateForm() {
        // Validate required fields
        const requiredFields = ['title', 'abstract', 'field'];
        let valid = true;
        
        requiredFields.forEach(field => {
            const input = document.getElementById(field);
            if (!input.value.trim()) {
                input.classList.add('is-invalid');
                valid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        // Validate at least one member
        const memberRows = document.querySelectorAll('.member-row');
        if (memberRows.length === 0) {
            showToast('Error', 'Please add at least one team member', 'error');
            valid = false;
        }
        
        // Validate member names
        let memberNamesValid = true;
        document.querySelectorAll('.member-name').forEach(input => {
            if (!input.value.trim()) {
                input.classList.add('is-invalid');
                memberNamesValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        if (!memberNamesValid) {
            showToast('Error', 'All team members must have a name', 'error');
            valid = false;
        }
        
        return valid;
    }
    
    function addKeyword() {
        const keyword = keywordInput.value.trim();
        if (keyword) {
            if (!keywords.includes(keyword)) {
                keywords.push(keyword);
                updateKeywordsDisplay();
            }
            keywordInput.value = '';
        }
    }
    
    function updateKeywordsDisplay() {
        keywordsContainer.innerHTML = '';
        keywordsListInput.value = JSON.stringify(keywords);
        
        keywords.forEach((keyword, index) => {
            const badge = document.createElement('span');
            badge.className = 'keyword-badge';
            badge.innerHTML = `${keyword} <i class="bi bi-x-circle" data-index="${index}"></i>`;
            keywordsContainer.appendChild(badge);
            
            // Add click event to remove keyword
            badge.querySelector('i').addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                keywords.splice(index, 1);
                updateKeywordsDisplay();
            });
        });
    }
    
    function addMemberRow() {
        const row = document.createElement('div');
        row.className = 'row mb-2 member-row';
        const rowId = 'member-row-' + Date.now();
        row.innerHTML = `
            <div class="col-md-3">
                <div class="student-search-container position-relative">
                    <input type="text" class="form-control member-name student-search" placeholder="Type to search students..." autocomplete="off" required>
                    <input type="hidden" class="member-student-id">
                    <div class="student-dropdown">
                        </div>
                </div>
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control member-role" placeholder="Role (e.g., Author, Researcher)">
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control member-contribution" placeholder="Contribution %" min="0" max="100">
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control member-userid" placeholder="User ID (optional)">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger remove-member">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        
        membersContainer.appendChild(row);
        
        // Setup student search functionality for this row
        setupStudentSearch(row);
        
        // Add event listener to remove button
        row.querySelector('.remove-member').addEventListener('click', function() {
            row.remove();
        });
    }
    
    function getMembersData() {
        const members = [];
        const memberRows = document.querySelectorAll('.member-row');
        
        memberRows.forEach(row => {
            const name = row.querySelector('.member-name').value.trim();
            const role = row.querySelector('.member-role').value.trim();
            const contribution = parseInt(row.querySelector('.member-contribution').value) || 0;
            const userId = row.querySelector('.member-userid').value.trim();
            const selectedStudentId = row.querySelector('.member-student-id').value.trim();
            
            if (name) {
                const member = {
                    name: name,
                    role: role || 'Author',
                    contribution: contribution
                };
                
                // Use selected student ID if available, otherwise use manually entered userId
                if (selectedStudentId) {
                    member.userId = { '$oid': selectedStudentId };
                } else if (userId) {
                    member.userId = { '$oid': userId };
                }
                
                members.push(member);
            }
        });
        
        return members;
    }
    
    function resetForm() {
        projectForm.reset();
        projectIdInput.value = '';
        keywords = [];
        updateKeywordsDisplay();
        
        // Reset members
        membersContainer.innerHTML = '';
        addMemberRow();
        

        
        // Reset timeline
        timelineContainer.innerHTML = '';
        timelineItems = [];
        
        // Reset image preview
        imagePreviewContainer.style.display = 'none';
        imagePreview.src = '';
        
        // Reset files and media previews
        filesPreviewContainer.innerHTML = '';
        mediaPreviewContainer.innerHTML = '';
        uploadedFiles = [];
        uploadedMedia = [];
        
        // Reset supervisor dropdown to default
        clearSupervisorSelection();
        
        // Reset editing state
        isEditing = false;
        
        // Reset any validation styling
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
    }
    
    function showSpinner() {
        spinnerOverlay.classList.add('show');
    }
    
    function hideSpinner() {
        spinnerOverlay.classList.remove('show');
    }
    
    function showToast(title, message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <div class="toast-header">
                <span class="toast-title">${title}</span>
                <button type="button" class="toast-close">&times;</button>
            </div>
            <div class="toast-body">${message}</div>
        `;
        
        toastContainer.appendChild(toast);
        
        // Show toast
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        // Hide toast after 5 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 5000);
        
        // Close button
        toast.querySelector('.toast-close').addEventListener('click', function() {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        });
    }
    
    // Faculty Management Functions
    let allFacultyData = [];
    let currentHighlightedIndex = -1;
    
    function loadFacultyForDropdown() {
        // Check if global faculty data is available (from Faculty_Page.php)
        if (window.facultyData && window.facultyData.length > 0) {
            allFacultyData = window.facultyData;
            setupSupervisorSearch();
            return;
        }
        
        // If global data is not available, fetch it directly
        // This handles cases where project management is accessed without visiting faculty page first
        fetch('src/model/load_faculty.php')
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    // Store globally for future use
                    window.facultyData = data;
                    allFacultyData = data;
                    setupSupervisorSearch();
                } else {
                    console.error('No faculty data received');
                    showToast('Warning', 'Could not load faculty data for supervisor dropdown', 'warning');
                }
            })
            .catch(error => {
                console.error('Error fetching faculty data:', error);
                showToast('Warning', 'Could not load faculty data for supervisor dropdown', 'warning');
            });
    }
    
    function setupSupervisorSearch() {
        const supervisorInput = document.getElementById('supervisor');
        const supervisorDropdown = document.getElementById('supervisorDropdown');
        const supervisorIdInput = document.getElementById('supervisorId');
        
        if (!supervisorInput || !supervisorDropdown) return;
        
        // Input event for filtering
        supervisorInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            filterAndDisplayFaculty(searchTerm);
        });
        
        // Focus event to show dropdown
        supervisorInput.addEventListener('focus', function() {
            const searchTerm = this.value.toLowerCase().trim();
            filterAndDisplayFaculty(searchTerm);
        });
        
        // Blur event to hide dropdown (with delay for clicks)
        supervisorInput.addEventListener('blur', function() {
            setTimeout(() => {
                hideSupervisorDropdown();
            }, 150);
        });
        
        // Keyboard navigation
        supervisorInput.addEventListener('keydown', function(e) {
            const options = supervisorDropdown.querySelectorAll('.supervisor-option');
            
            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    currentHighlightedIndex = Math.min(currentHighlightedIndex + 1, options.length - 1);
                    updateHighlight(options);
                    break;
                    
                case 'ArrowUp':
                    e.preventDefault();
                    currentHighlightedIndex = Math.max(currentHighlightedIndex - 1, -1);
                    updateHighlight(options);
                    break;
                    
                case 'Enter':
                    e.preventDefault();
                    if (currentHighlightedIndex >= 0 && options[currentHighlightedIndex]) {
                        selectFaculty(options[currentHighlightedIndex]);
                    }
                    break;
                    
                case 'Escape':
                    hideSupervisorDropdown();
                    supervisorInput.blur();
                    break;
            }
        });
        
        // Click outside to close
        document.addEventListener('click', function(e) {
            if (!supervisorInput.contains(e.target) && !supervisorDropdown.contains(e.target)) {
                hideSupervisorDropdown();
            }
        });
    }
    
    function filterAndDisplayFaculty(searchTerm) {
        const supervisorDropdown = document.getElementById('supervisorDropdown');
        
        if (!allFacultyData || allFacultyData.length === 0) {
            supervisorDropdown.innerHTML = '<div class="supervisor-no-results">No faculty data available</div>';
            supervisorDropdown.classList.add('show');
            return;
        }
        
        // Filter faculty based on search term
        const filteredFaculty = allFacultyData.filter(faculty => 
            faculty.name.toLowerCase().includes(searchTerm) ||
            (faculty.email && faculty.email.toLowerCase().includes(searchTerm)) ||
            (faculty.department && faculty.department.toLowerCase().includes(searchTerm))
        );
        
        // Clear previous options
        supervisorDropdown.innerHTML = '';
        currentHighlightedIndex = -1;
        
        if (filteredFaculty.length === 0) {
            supervisorDropdown.innerHTML = '<div class="supervisor-no-results">No faculty found matching your search</div>';
        } else {
            filteredFaculty.forEach((faculty, index) => {
                const option = document.createElement('div');
                option.className = 'supervisor-option';
                
                // Extract the ObjectId string properly
                let facultyId;
                if (faculty._id && typeof faculty._id === 'object' && faculty._id.$oid) {
                    facultyId = faculty._id.$oid;
                } else {
                    facultyId = String(faculty._id);
                }
                
                option.setAttribute('data-faculty-id', facultyId);
                option.setAttribute('data-faculty-name', faculty.name);
                
                // Create faculty info display
                let facultyInfo = '';
                if (faculty.email || faculty.department) {
                    const infoParts = [];
                    if (faculty.email) infoParts.push(faculty.email);
                    if (faculty.department) infoParts.push(faculty.department);
                    facultyInfo = `<div class="faculty-info">${infoParts.join(' • ')}</div>`;
                }
                
                option.innerHTML = `
                    <div>
                        <div class="faculty-name">${faculty.name}</div>
                        ${facultyInfo}
                    </div>
                `;
                
                // Click event for selection
                option.addEventListener('click', function() {
                    selectFaculty(this);
                });
                
                supervisorDropdown.appendChild(option);
            });
        }
        
        supervisorDropdown.classList.add('show');
        
        // Always position the supervisor dropdown above the input field
        // to prevent overlapping with student search fields below
        supervisorDropdown.style.bottom = '100%';
        supervisorDropdown.style.top = 'auto';
        supervisorDropdown.style.borderRadius = 'var(--border-radius) var(--border-radius) 0 0';
        supervisorDropdown.style.borderTop = '1px solid var(--border)';
        supervisorDropdown.style.borderBottom = 'none';
        supervisorDropdown.style.boxShadow = '0 -4px 12px rgba(0, 0, 0, 0.15)';
    }
    
    function updateHighlight(options) {
        // Remove previous highlights
        options.forEach(option => option.classList.remove('highlighted'));
        
        // Add highlight to current option
        if (currentHighlightedIndex >= 0 && options[currentHighlightedIndex]) {
            options[currentHighlightedIndex].classList.add('highlighted');
            
            // Scroll into view if needed
            options[currentHighlightedIndex].scrollIntoView({
                block: 'nearest',
                behavior: 'smooth'
            });
        }
    }
    
    function selectFaculty(optionElement) {
        const facultyId = optionElement.getAttribute('data-faculty-id');
        const facultyName = optionElement.getAttribute('data-faculty-name');
        
        // Set the input values
        document.getElementById('supervisor').value = facultyName;
        document.getElementById('supervisorId').value = facultyId;
        
        // Hide dropdown
        hideSupervisorDropdown();
        
        // Optional: Show confirmation
        console.log('Selected faculty:', facultyName, 'ID:', facultyId);
    }
    
    function hideSupervisorDropdown() {
        const supervisorDropdown = document.getElementById('supervisorDropdown');
        supervisorDropdown.classList.remove('show');
        currentHighlightedIndex = -1;
    }
    
    function clearSupervisorSelection() {
        document.getElementById('supervisor').value = '';
        document.getElementById('supervisorId').value = '';
        hideSupervisorDropdown();
    }
    
    // Legacy function for compatibility - now just calls the new setup
    function populateFacultyDropdown(faculties) {
        // This function is kept for compatibility but the new searchable dropdown
        // doesn't need traditional population since it filters on demand
        allFacultyData = faculties;
        setupSupervisorSearch();
    }
    
    // Project Management Functions
    function loadUserProjects() {
        showSpinner();
        
        fetch('src/model/get_user_projects.php')
            .then(response => response.json())
            .then(data => {
                hideSpinner();
                
                if (data.success) {
                    // Update login status
                    isLoggedIn = data.isLoggedIn;
                    
                    // Log debugging info to console
                    console.log("Debug info:", data.debug);
                    
                    // Display projects, either user's projects or public ones
                    if (data.projects && data.projects.length > 0) {
                        // Show all user-related projects (created by user, member of, or supervising)
                        if (isLoggedIn) {
                            // The backend already filters for user's projects correctly
                            // (createdBy, members, and supervisor checks are done server-side)
                            // So we can display all returned projects
                            displayUserProjects(data.projects, false);
                        } else {
                            // For non-logged in users, show empty state
                            displayEmptyState('Join the Research Community', 
                                'Access your personal research dashboard and connect with fellow researchers worldwide.');
                        }
                    } else {
                        const userId = data.debug?.userId || 'unknown';
                        let title = isLoggedIn ? 'Ready to Innovate?' : 'Join the Research Community';
                        let message = isLoggedIn ? 
                            `Transform your ideas into groundbreaking research projects. Share your discoveries with the academic community and make an impact.` : 
                            'Access your personal research dashboard and connect with fellow researchers worldwide.';
                        displayEmptyState(title, message);
                    }
                } else {
                    // Show empty state or error
                    displayEmptyState('Ready to Innovate?', 'Transform your ideas into groundbreaking research projects. Share your discoveries with the academic community and make an impact.');
                    console.error('Error:', data.message);
                }
            })
            .catch(error => {
                hideSpinner();
                console.error('Error:', error);
                displayEmptyState('Connection Issue', 'Unable to load your projects right now. Please check your connection and try again.');
            });
    }
    
    function displayUserProjects(projects, isPublicView = false) {
        if (!projects || projects.length === 0) {
            const message = isPublicView ? 
                'No public projects found.' : 
                'You haven\'t created any research projects yet. Click "Create New Project" to get started.';
                
            displayEmptyState('No projects found', message);
            return;
        }
        
        userProjectsList.innerHTML = '';
        
        projects.forEach(project => {
            const col = document.createElement('div');
            col.className = 'col-lg-4 col-md-6';
            col.setAttribute('data-aos', 'fade-up');
            
            // Determine badge class based on privacy
            const badgeClass = project.privacy === 0 ? 'badge-public' : 'badge-private';
            const badgeText = project.privacy === 0 ? 'Public' : 'Private';
            
            // Format the date with robust handling
            let formattedDate = 'Date not available';
            try {
                let dateValue;
                const dateInput = project.createdAt;
                
                // Handle different date formats
                if (typeof dateInput === 'object') {
                    // MongoDB date object with $date property
                    if (dateInput.$date) {
                        if (typeof dateInput.$date === 'string') {
                            dateValue = dateInput.$date;
                        } else if (typeof dateInput.$date === 'object' && dateInput.$date.$numberLong) {
                            // Handle MongoDB long format
                            dateValue = parseInt(dateInput.$date.$numberLong);
                        } else {
                            dateValue = dateInput.$date;
                        }
                    } else {
                        dateValue = dateInput;
                    }
                } else if (typeof dateInput === 'string') {
                    // Direct string date
                    dateValue = dateInput;
                } else if (typeof dateInput === 'number') {
                    // Timestamp
                    dateValue = dateInput;
                }
                
                // Create date object and format
                const date = new Date(dateValue);
                if (!isNaN(date.getTime())) {
                    formattedDate = date.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                }
            } catch (error) {
                console.error('Error formatting date:', error, 'Input:', project.createdAt);
            }
            
            // Get image path
            let imageSrc = getRandomResearchImage();
            if (project.coverImage && project.coverImage.url) {
                imageSrc = project.coverImage.url;
            }
            
            // Determine if action buttons should be shown (only for logged-in users and their projects)
            const showActionButtons = isLoggedIn && !isPublicView;
            
            // Card link wrapper
            const cardLink = document.createElement('a');
            cardLink.href = `Project_details.php?id=${project._id.$oid}`;
            cardLink.style.textDecoration = 'none'; // Prevent underline
            cardLink.style.color = 'inherit';     // Inherit text color

            cardLink.innerHTML = `
                <div class="card-image">
                    <img src="${imageSrc}" alt="${project.title}" loading="lazy" onerror="this.onerror=null; this.src='${getRandomResearchImage()}';">
                </div>
                <div class="card-content">
                    <div class="project-badge ${badgeClass}">${badgeText}</div>
                    <h3 class="card-title">${project.title}</h3>
                    <p class="card-description">${project.abstract ? (project.abstract.length > 120 ? project.abstract.substring(0, 120) + '...' : project.abstract) : 'No description available'}</p>
                    <div class="project-meta">
                        <div class="meta-item">
                            <i class="meta-icon far fa-calendar-alt"></i>
                            <span>${formattedDate}</span>
                        </div>
                        <div class="meta-item">
                            <i class="meta-icon fas fa-graduation-cap"></i>
                            <span>${project.field || 'Research'}</span>
                        </div>
                        ${showActionButtons ? `
                        <div class="meta-item project-actions">
                            <a href="edit_project.php?id=${project._id.$oid}" class="btn btn-sm btn-outline-primary edit-project-btn">
                                <i class="bi bi-pencil-fill me-1"></i>Edit
                            </a>
                            <button class="btn btn-sm btn-outline-danger ms-2 delete-project-btn" data-id="${project._id.$oid}" data-title="${project.title}">
                                <i class="bi bi-trash-fill me-1"></i>Delete
                            </button>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            const projectCardDiv = document.createElement('div');
            projectCardDiv.className = 'project-card position-relative';
            projectCardDiv.appendChild(cardLink);
            col.appendChild(projectCardDiv);
            
            userProjectsList.appendChild(col);
            
            // Add event listeners for edit and delete buttons if shown
            // These should stop propagation to prevent card click
            if (showActionButtons) {
                const editBtn = col.querySelector('.edit-project-btn');
                if(editBtn) {
                    editBtn.addEventListener('click', function(e) {
                        e.stopPropagation(); // Prevent card click
                        // The link will handle navigation
                    });
                }

                const deleteBtn = col.querySelector('.delete-project-btn');
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', function(e) {
                        e.preventDefault(); // Prevent link navigation
                        e.stopPropagation(); // Prevent card click
                    const projectId = this.getAttribute('data-id');
                        const projectTitle = this.getAttribute('data-title');
                        deleteProject(projectId, projectTitle);
                });
                }
            }
        });
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    function displayEmptyState(title, message) {
        userProjectsList.innerHTML = `
            <div class="col-12">
                <div class="empty-projects-container text-center py-5">
                    <div class="empty-icon-container mb-4">
                        <i class="bi bi-lightbulb display-1 text-primary opacity-75"></i>
                    </div>
                    <h3 class="fw-bold mb-3">${title}</h3>
                    <p class="mx-auto" style="max-width: 500px; color: var(--text-secondary);">${message}</p>
                    
                    <div class="mt-4">
                        ${title !== 'Join the Research Community' ? 
                            `<button class="btn btn-primary btn-lg create-project-button px-4">
                                <i class="bi bi-plus-circle me-2"></i>Start Your Research Journey
                            </button>` : 
                            `<a href="login.php" class="btn btn-primary btn-lg px-4">
                                <i class="bi bi-person-fill me-2"></i>Login to Continue
                            </a>`
                        }
                    </div>
                </div>
            </div>
        `;
        
        // Add custom styling for the empty state
        const style = document.createElement('style');
        style.textContent = `
            .empty-projects-container {
                background: #00112957;
                border-radius: 16px;
                padding: 3rem;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
                transition: all 0.3s ease;
            }
            
            .empty-projects-container:hover {
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
                transform: translateY(-5px);
            }
            
            .empty-icon-container {
                position: relative;
                display: inline-block;
                animation: float-animation 3s ease-in-out infinite;
            }
            
            .empty-icon-container::before {
                content: '';
                position: absolute;
                width: 80px;
                height: 20px;
                background: rgba(0, 0, 0, 0.05);
                border-radius: 50%;
                bottom: -10px;
                left: 50%;
                transform: translateX(-50%);
                filter: blur(5px);
                animation: shadow-animation 3s ease-in-out infinite;
            }
            
            @keyframes float-animation {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-15px); }
            }
            
            @keyframes shadow-animation {
                0%, 100% { transform: translateX(-50%) scale(1); opacity: 0.3; }
                50% { transform: translateX(-50%) scale(0.8); opacity: 0.1; }
            }
            
            .create-project-button {
                background: linear-gradient(135deg, #4361ee, #3a0ca3);
                border: none;
                box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
                transition: all 0.3s ease;
            }
            
            .create-project-button:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
                background: linear-gradient(135deg, #3a56e4, #2f09a0);
            }
        `;
        document.head.appendChild(style);
        
        // Add event listener to the create project button
        const createBtn = document.querySelector('.create-project-button');
        if (createBtn) {
            createBtn.addEventListener('click', function() {
            document.getElementById('new-project-tab').click();
        });
        }
    }
    
    function editProject(projectId) {
        showSpinner();
        
        fetch(`src/model/get_project.php?id=${projectId}`)
            .then(response => response.json())
            .then(data => {
                hideSpinner();
                
                if (data.success && data.project) {
                    fillProjectForm(data.project);
                    document.getElementById('new-project-tab').click();
                } else {
                    showToast('Error', data.message || 'Failed to load project details', 'error');
                }
            })
            .catch(error => {
                hideSpinner();
                console.error('Error:', error);
                showToast('Error', 'An error occurred while loading project details', 'error');
            });
    }
    
    function fillProjectForm(project) {
        // Set editing state
        isEditing = true;
        projectIdInput.value = project._id.$oid;
        
        // Fill basic info
        document.getElementById('title').value = project.title || '';
        document.getElementById('abstract').value = project.abstract || '';
        document.getElementById('description').value = project.description || '';
        document.getElementById('field').value = project.field || '';
        document.getElementById('institution').value = project.institution || 'United International University';
        document.getElementById('privacy').value = project.privacy !== undefined ? project.privacy.toString() : '0';
        
        // Fill dates if they exist
        if (project.createdAt && project.createdAt.$date) {
            const createdDate = new Date(project.createdAt.$date);
            document.getElementById('createdAt').value = createdDate.toISOString().split('T')[0];
        }
        
        if (project.updatedAt && project.updatedAt.$date) {
            const updatedDate = new Date(project.updatedAt.$date);
            document.getElementById('updatedAt').value = updatedDate.toISOString().split('T')[0];
        }
        
        // Fill estimated completion date if it exists
        if (project.estimatedCompletionDate && project.estimatedCompletionDate.$date) {
            const estimatedDate = new Date(project.estimatedCompletionDate.$date);
            document.getElementById('estimatedCompletionDate').value = estimatedDate.toISOString().split('T')[0];
        }
        
        // Fill supervisor
        if (project.supervisor) {
            const supervisorInput = document.getElementById('supervisor');
            const supervisorIdInput = document.getElementById('supervisorId');
            
            if (typeof project.supervisor === 'string') {
                // Simple string supervisor name
                supervisorInput.value = project.supervisor;
                supervisorIdInput.value = ''; // No ID available for string supervisors
            } else if (typeof project.supervisor === 'object') {
                // Structured supervisor object
                if (project.supervisor.name) {
                    supervisorInput.value = project.supervisor.name;
                    
                    // Try to find the faculty ID if available
                    if (project.supervisor.userId && project.supervisor.userId.$oid) {
                        supervisorIdInput.value = project.supervisor.userId.$oid;
                    } else {
                        // Try to match by name in faculty data
                        const matchingFaculty = allFacultyData.find(faculty => 
                            faculty.name.toLowerCase() === project.supervisor.name.toLowerCase()
                        );
                        if (matchingFaculty) {
                            // Extract the ObjectId string properly
                            let facultyId;
                            if (matchingFaculty._id && typeof matchingFaculty._id === 'object' && matchingFaculty._id.$oid) {
                                facultyId = matchingFaculty._id.$oid;
                            } else {
                                facultyId = String(matchingFaculty._id);
                            }
                            supervisorIdInput.value = facultyId;
                        } else {
                            supervisorIdInput.value = ''; // Custom supervisor
                        }
                    }
                }
            }
        }
        
        // Fill links
        if (project.links) {
            document.getElementById('github').value = project.links.github || '';
            document.getElementById('website').value = project.links.website || '';
            document.getElementById('paper').value = project.links.paper || '';
            document.getElementById('doi').value = project.links.doi || '';
            document.getElementById('youtube').value = project.links.youtube || '';
        }
        
        // Fill references
        if (project.references && Array.isArray(project.references)) {
            // Use the new function to fill references
            fillReferencesFromExisting(project.references);
        }
        
        // Fill keywords
        keywords = project.keywords || [];
        updateKeywordsDisplay();
        
        // Fill members
        membersContainer.innerHTML = '';
        if (project.members && project.members.length > 0) {
            project.members.forEach((member, index) => {
                const row = document.createElement('div');
                row.className = 'row mb-2 member-row';
                const memberUserId = member.userId && member.userId.$oid ? member.userId.$oid : '';
                
                // Check if this member is the creator (first member or matches current user)
                const isCreator = (index === 0 && member.role === 'Creator') || 
                                 (currentUserData && memberUserId === currentUserData.id) ||
                                 member.role === 'Creator';
                
                row.innerHTML = `
                    <div class="col-md-3">
                        <div class="student-search-container position-relative">
                            <input type="text" class="form-control member-name student-search" placeholder="Type to search students..." autocomplete="off" required value="${member.name || ''}" ${isCreator ? 'readonly' : ''}>
                            <input type="hidden" class="member-student-id" value="${memberUserId}">
                            <div class="student-dropdown">
                                </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control member-role" placeholder="Role (e.g., Author, Researcher)" value="${member.role || ''}">
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control member-contribution" placeholder="Contribution %" min="0" max="100" value="${member.contribution || 0}">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control member-userid" placeholder="User ID (optional)" value="${memberUserId}">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger remove-member" ${isCreator ? 'disabled' : ''}>
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `;
                
                membersContainer.appendChild(row);
                
                // Apply creator styling if this is the creator
                if (isCreator) {
                    const studentInput = row.querySelector('.member-name.student-search');
                    const removeBtn = row.querySelector('.remove-member');
                    
                    // Style the creator field
                    if (studentInput) {
                        studentInput.style.backgroundColor = 'transparent';
                        studentInput.style.color = 'var(--text-secondary, #6c757d)';
                        
                        // Add creator badge
                        const creatorBadge = document.createElement('small');
                        creatorBadge.className = 'text-primary mt-1 d-block';
                        creatorBadge.innerHTML = '<i class="bi bi-person-fill me-1"></i>Project Creator';
                        studentInput.parentElement.appendChild(creatorBadge);
                    }
                    
                    // Style the remove button
                    if (removeBtn) {
                        removeBtn.title = 'Cannot remove project creator';
                        removeBtn.style.opacity = '0.5';
                    }
                }
                
                // Setup student search functionality for this row (will skip readonly fields)
                setupStudentSearch(row);
                
                // Add event listener to remove button
                row.querySelector('.remove-member').addEventListener('click', function() {
                    if (!this.disabled) {
                        row.remove();
                    }
                });
            });
        } else {
            // Add at least one empty row
            addMemberRow();
            

        }
        
        // Fill timeline items
        timelineContainer.innerHTML = '';
        timelineItems = [];
        
        if (project.timeline && project.timeline.length > 0) {
            timelineItems = [...project.timeline];
            updateTimelineDisplay();
        }
        
        // Handle image preview
        if (project.coverImage && project.coverImage.url) {
            imagePreview.src = project.coverImage.url;
            imagePreviewContainer.style.display = 'block';
            originalImageUrl = project.coverImage.url;
        } else {
            imagePreviewContainer.style.display = 'none';
            originalImageUrl = '';
        }
        
        // Display files if they exist
        if (project.files && project.files.length > 0) {
            filesPreviewContainer.innerHTML = '';
            project.files.forEach(file => {
                const filePreview = document.createElement('div');
                filePreview.className = 'mb-2';
                
                // Extract the file name from either name or path
                const fileName = file.name || (file.path ? file.path.split('/').pop() : '') || (file.url ? file.url.split('/').pop() : '');
                const fileIcon = getFileIcon(fileName);
                const fileSize = file.size ? formatFileSize(file.size) : '';
                
                filePreview.innerHTML = `
                    <div class="alert alert-light d-flex align-items-center">
                        <i class="${fileIcon} me-2 text-primary"></i>
                        <span class="text-truncate">${fileName}</span>
                        <span class="ms-auto badge bg-secondary">${fileSize}</span>
                    </div>
                `;
                
                filesPreviewContainer.appendChild(filePreview);
            });
        }
        
        // Display media if they exist
        if (project.media && project.media.length > 0) {
            mediaPreviewContainer.innerHTML = '';
            project.media.forEach(media => {
                const mediaPreview = document.createElement('div');
                mediaPreview.className = 'col-md-3 mb-2';
                
                // Get the caption or extract from URL
                const caption = media.caption || media.url.split('/').pop();
                
                if (media.type === 'image') {
                    mediaPreview.innerHTML = `
                        <div class="card">
                            <img src="${media.url}" class="card-img-top" style="height: 150px; object-fit: cover;">
                            <div class="card-body p-2">
                                <p class="card-text small text-truncate">${caption}</p>
                            </div>
                        </div>
                    `;
                } else {
                    mediaPreview.innerHTML = `
                        <div class="card">
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
                                <i class="bi bi-film fs-1 text-primary"></i>
                            </div>
                            <div class="card-body p-2">
                                <p class="card-text small text-truncate">${caption}</p>
                            </div>
                        </div>
                    `;
                }
                
                mediaPreviewContainer.appendChild(mediaPreview);
            });
        }
        
        // Fill stats (hidden fields)
        if (project.stats) {
            viewsCountInput.value = project.stats.views || '';
            downloadsCountInput.value = project.stats.downloads || '';
            favoritesCountInput.value = project.stats.favorites || '';
        }
    }
    
    function deleteProject(projectId, projectTitle) {
        // Simplified approach with better error handling
        if (confirm(`Are you sure you want to delete "${projectTitle}"? This action cannot be undone.`)) {
            // Show spinner
            showSpinner();
            
            // Log what we're trying to delete for debugging
            console.log("Attempting to delete project:", projectId, projectTitle);
            
            // Send delete request
            fetch('src/model/delete_project.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ projectId: projectId })
            })
            .then(response => {
                console.log("Delete response status:", response.status);
                return response.json();
            })
            .then(data => {
                hideSpinner();
                console.log("Delete response data:", data);
                
                if (data.success) {
                    showToast('Success', 'Project deleted successfully', 'success');
                    loadUserProjects();
                } else {
                    showToast('Error', data.message || 'Failed to delete project', 'error');
                }
            })
            .catch(error => {
                hideSpinner();
                console.error('Error deleting project:', error);
                showToast('Error', 'An error occurred while deleting the project', 'error');
            });
        }
    }
    
    // Utility function to get a random research image
    function getRandomResearchImage() {
        const researchImages = [
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
        
        return researchImages[Math.floor(Math.random() * researchImages.length)];
    }
    
    // Timeline management functions
    function addTimelineItem(item = null) {
        const now = new Date();
        const formattedDate = now.toISOString().split('T')[0]; // YYYY-MM-DD format
        
        const newItem = item || {
            title: '',
            description: '',
            date: formattedDate,
            status: 'Planned',
            assignedBy: null,
            assignedTo: []
        };
        
        // Add to the array
        if (!item) {
            timelineItems.push(newItem);
        }
        
        // Create the DOM element
        updateTimelineDisplay();
    }
    
    function updateTimelineDisplay() {
        timelineContainer.innerHTML = '';
        
        if (timelineItems.length === 0) {
            timelineContainer.innerHTML = '<p class="text-muted text-center py-3">No timeline items added yet.</p>';
            return;
        }
        
        // Sort timeline items by date
        timelineItems.sort((a, b) => new Date(a.date) - new Date(b.date));
        
        timelineItems.forEach((item, index) => {
            const statusClasses = {
                'Completed': 'completed status-completed',
                'In Progress': 'in-progress status-in-progress',
                'Planned': 'planned status-planned',
                'Delayed': 'delayed status-delayed'
            };
            
            const statusClass = statusClasses[item.status] || 'planned status-planned';
            
            const timelineItem = document.createElement('div');
            timelineItem.className = `timeline-item ${item.status.toLowerCase().replace(' ', '-')}`;
            timelineItem.dataset.index = index;
            
            // Format date for display
            const displayDate = new Date(item.date);
            const formattedDisplayDate = displayDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            // Generate assignment display text
            let assignmentInfo = '';
            
            // Ensure backward compatibility - handle various assignment field formats
            let assignedBy = '';
            let assignedByName = '';
            
            if (item.assignedBy) {
                // Handle new format (object with id and name)
                if (typeof item.assignedBy === 'object' && item.assignedBy !== null) {
                    assignedBy = item.assignedBy.id || '';
                    assignedByName = item.assignedBy.name || '';
                } 
                // Handle legacy format (string)
                else {
                    assignedBy = item.assignedBy;
                    const assignedByMember = findMemberById(assignedBy);
                    assignedByName = assignedByMember ? assignedByMember.name : assignedBy;
                }
            }
            
            let assignedTo = [];
            let assignedToNames = [];
            
            if (item.assignedTo) {
                if (Array.isArray(item.assignedTo)) {
                    assignedTo = item.assignedTo;
                    
                    // Map to names, handling both new and legacy formats
                    assignedToNames = assignedTo.map(assignee => {
                        // Handle new format (object with id and name)
                        if (typeof assignee === 'object' && assignee !== null) {
                            return assignee.name || 'Unknown';
                        }
                        // Handle legacy format (string ID or name)
                        const member = findMemberById(assignee);
                        return member ? member.name : assignee;
                    });
                } else {
                    // Handle single assignee case (backward compatibility)
                    assignedTo = [item.assignedTo];
                    const member = findMemberById(item.assignedTo);
                    assignedToNames = [member ? member.name : item.assignedTo];
                }
            }
            
            if (assignedByName || assignedToNames.length > 0) {
                assignmentInfo = '<div class="assignment-info mt-2">';
                
                if (assignedByName) {
                    assignmentInfo += `<span class="text-muted small me-3"><i class="bi bi-person-plus"></i> Assigned by: <strong>${assignedByName}</strong></span>`;
                }
                
                if (assignedToNames.length > 0) {
                    assignmentInfo += `<span class="text-muted small"><i class="bi bi-person-check"></i> Assigned to: <strong>${assignedToNames.join(', ')}</strong></span>`;
                }
                
                assignmentInfo += '</div>';
            }
            
            timelineItem.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="timeline-date">
                        <i class="bi bi-calendar3"></i>
                        ${formattedDisplayDate}
                        <span class="status-badge ${statusClass}">${item.status}</span>
                    </div>
                    <div class="timeline-controls">
                        <button type="button" class="btn btn-sm btn-outline-primary edit-timeline" data-index="${index}">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-timeline" data-index="${index}">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </div>
                </div>
                <h6 class="mb-2">${item.title}</h6>
                <p class="mb-2 small text-muted">${item.description}</p>
                ${assignmentInfo}
            `;
            
            timelineContainer.appendChild(timelineItem);
            
            // Add event listeners
            const editBtn = timelineItem.querySelector('.edit-timeline');
            const deleteBtn = timelineItem.querySelector('.delete-timeline');
            
            editBtn.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                editTimelineItem(index);
            });
            
            deleteBtn.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                deleteTimelineItem(index);
            });
        });
    }
    
    function editTimelineItem(index) {
        const item = timelineItems[index];
        
        // Create a modal dialog for editing
        const modalId = 'timelineEditModal';
        let modal = document.getElementById(modalId);
        
        // If modal doesn't exist, create it
        if (!modal) {
            modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = modalId;
            modal.tabIndex = -1;
            modal.setAttribute('aria-labelledby', `${modalId}Label`);
            modal.setAttribute('aria-hidden', 'true');
            
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="${modalId}Label">Edit Timeline Item</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="timelineEditForm">
                                <div class="mb-2">
                                    <label for="timelineTitle" class="form-label">Title</label>
                                    <input type="text" class="form-control" id="timelineTitle" required>
                                </div>
                                <div class="mb-2">
                                    <label for="timelineDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="timelineDescription" rows="3"></textarea>
                                </div>
                                <div class="mb-2">
                                    <label for="timelineDate" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="timelineDate" required>
                                </div>
                                <div class="mb-2">
                                    <label for="timelineStatus" class="form-label">Status</label>
                                    <select class="form-select" id="timelineStatus">
                                        <option value="Planned">Planned</option>
                                        <option value="In Progress">In Progress</option>
                                        <option value="Completed">Completed</option>
                                        <option value="Delayed">Delayed</option>
                                    </select>
                                </div>
                                <div class="mb-2" id="assignedByContainer">
                                    <label for="timelineAssignedBy" class="form-label">Assigned By</label>
                                    <select class="form-select" id="timelineAssignedBy">
                                        <option value="">Select member (optional)</option>
                                    </select>
                                </div>
                                <div class="mb-2" id="assignedToContainer">
                                    <label for="timelineAssignedTo" class="form-label">Assigned To</label>
                                    <select class="form-select" id="timelineAssignedTo" multiple>
                                        <option value="">Select members (optional)</option>
                                    </select>
                                    <div class="form-text">Hold Ctrl/Cmd to select multiple members. Only student team members can be assigned.</div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveTimelineChanges">Save</button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
        }
        
        // Set form values
        document.getElementById('timelineTitle').value = item.title;
        document.getElementById('timelineDescription').value = item.description;
        document.getElementById('timelineDate').value = item.date;
        document.getElementById('timelineStatus').value = item.status;
        
        // Populate assignment dropdowns with project members
        populateAssignmentDropdowns();
        
        // Auto-select current user in "Assigned By" if no assignment exists or if editing
        const assignedBySelect = document.getElementById('timelineAssignedBy');
        const assignedToSelect = document.getElementById('timelineAssignedTo');
        
        // Set assignment values - if no assignedBy exists, auto-select current user
        let assignedByValue = '';
        
        // Handle new format (object with id and name)
        if (item.assignedBy && typeof item.assignedBy === 'object' && item.assignedBy.id) {
            assignedByValue = item.assignedBy.id;
        } 
        // Handle legacy format (string ID or name)
        else if (item.assignedBy) {
            assignedByValue = item.assignedBy;
        }
        
        // If no assigned by, try to auto-select current user
        if (!assignedByValue && currentUserData) {
            // Try to find current user in the dropdown options
            const currentUserOption = Array.from(assignedBySelect.options).find(option => 
                option.value === currentUserData.id || option.value === currentUserData.name
            );
            if (currentUserOption) {
                assignedByValue = currentUserOption.value;
            }
        }
        
        document.getElementById('timelineAssignedBy').value = assignedByValue;
        
        // Handle multiple assignees for "Assigned To" (with backward compatibility)
        let assignedToArray = [];
        
        // Handle new format (array of objects with id and name)
        if (item.assignedTo && Array.isArray(item.assignedTo)) {
            // Extract IDs from objects if in new format
            assignedToArray = item.assignedTo.map(assignee => 
                (assignee && typeof assignee === 'object' && assignee.id) ? assignee.id : assignee
            );
        } 
        // Handle legacy format
        else if (item.assignedTo) {
            assignedToArray = Array.isArray(item.assignedTo) ? item.assignedTo : [item.assignedTo];
        }
        
        // Select the appropriate options in the dropdown
        Array.from(assignedToSelect.options).forEach(option => {
            option.selected = assignedToArray.includes(option.value);
        });
        
        // Initialize and show the modal
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
        
        // Handle form submission
        const saveBtn = document.getElementById('saveTimelineChanges');
        
        // Remove any existing event listeners
        const newSaveBtn = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newSaveBtn, saveBtn);
        
        newSaveBtn.addEventListener('click', function() {
            const title = document.getElementById('timelineTitle').value.trim();
            const description = document.getElementById('timelineDescription').value.trim();
            const date = document.getElementById('timelineDate').value;
            const status = document.getElementById('timelineStatus').value;
            const assignedByValue = document.getElementById('timelineAssignedBy').value;
            
            // Get assignedBy information (both ID and name)
            let assignedBy = null;
            if (assignedByValue) {
                const selectedOption = document.getElementById('timelineAssignedBy').options[
                    document.getElementById('timelineAssignedBy').selectedIndex
                ];
                assignedBy = {
                    id: assignedByValue,
                    name: selectedOption.text.replace(/ \(.*\)$/, ''), // Remove role from text
                    type: selectedOption.text.toLowerCase().includes('supervisor') ? 'faculty' : 'student'
                };
            }
            
            // Get multiple selected values for "Assigned To" with both ID and name
            const assignedToSelect = document.getElementById('timelineAssignedTo');
            const assignedTo = Array.from(assignedToSelect.selectedOptions)
                .filter(option => option.value !== '')
                .map(option => ({
                    id: option.value,
                    name: option.text.replace(/ \(.*\)$/, ''), // Remove role from text
                    type: 'student' // Assuming assignedTo are only students
                }));
            
            if (!title || !date) {
                // Show validation error
                if (!title) document.getElementById('timelineTitle').classList.add('is-invalid');
                if (!date) document.getElementById('timelineDate').classList.add('is-invalid');
                return;
            }
            
            // Update timelineItems array
            timelineItems[index] = {
                title,
                description,
                date,
                status,
                assignedBy,
                assignedTo
            };
            
            // Update the UI
            updateTimelineDisplay();
            
            // Close the modal
            modalInstance.hide();
        });
    }
    
    function deleteTimelineItem(index) {
        if (confirm('Are you sure you want to delete this timeline item?')) {
            timelineItems.splice(index, 1);
            updateTimelineDisplay();
        }
    }
    
    function getTimelineData() {
        return timelineItems;
    }
    
    // Helper function to populate assignment dropdowns with project members
    function populateAssignmentDropdowns() {
        const assignedBySelect = document.getElementById('timelineAssignedBy');
        const assignedToSelect = document.getElementById('timelineAssignedTo');
        
        if (!assignedBySelect || !assignedToSelect) return;
        
        // Clear existing options (except the first default option)
        assignedBySelect.innerHTML = '<option value="">Select member (optional)</option>';
        assignedToSelect.innerHTML = '<option value="">Select members (optional)</option>';
        
        // Get current project members
        const members = getMembersData();
        
        // Add all team members to "Assigned By" dropdown, but only students to "Assigned To"
        members.forEach(member => {
            if (member.name && member.name.trim()) {
                // Use student ID if available, otherwise use member name
                const optionValue = (member.userId && member.userId.$oid) ? member.userId.$oid : member.name;
                const optionText = `${member.name}${member.role ? ' (' + member.role + ')' : ''}`;
                
                // Add all members to "Assigned By" dropdown
                const assignedByOption = new Option(optionText, optionValue);
                assignedBySelect.add(assignedByOption);
                
                // Check if the member is a student (not supervisor/faculty) for "Assigned To"
                const isStudent = !member.role || 
                                 (member.role.toLowerCase() !== 'supervisor' && 
                                  member.role.toLowerCase() !== 'faculty' &&
                                  member.role.toLowerCase() !== 'creator/supervisor');
                
                // Only add students to "Assigned To" dropdown
                if (isStudent) {
                    const assignedToOption = new Option(optionText, optionValue);
                    assignedToSelect.add(assignedToOption);
                }
            }
        });
        
        // Add supervisor to "Assigned By" dropdown
        const supervisorSelect = document.getElementById('supervisor');
        const supervisorIdInput = document.getElementById('supervisorId');
        
        if (supervisorSelect && supervisorSelect.value && supervisorSelect.value.trim()) {
            const supervisorValue = supervisorIdInput && supervisorIdInput.value ? supervisorIdInput.value : supervisorSelect.value;
            const supervisorText = `${supervisorSelect.value} (Supervisor)`;
            
            const supervisorByOption = new Option(supervisorText, supervisorValue);
            assignedBySelect.add(supervisorByOption);
        }
    }
    
    // Helper function to find member by ID or name
    function findMemberById(id) {
        if (!id) return null;
        
        // Check current project members
        const members = getMembersData();
        
        // First try to find by user ID
        let member = members.find(m => 
            (m.userId && m.userId.$oid === id) || 
            (m.userId && typeof m.userId === 'string' && m.userId === id)
        );
        
        // If not found by ID, try by name
        if (!member) {
            member = members.find(m => m.name === id);
        }
        
        // Check supervisor
        if (!member) {
            const supervisorSelect = document.getElementById('supervisor');
            const supervisorIdInput = document.getElementById('supervisorId');
            
            if (supervisorSelect && supervisorSelect.value) {
                const supervisorId = supervisorIdInput && supervisorIdInput.value ? supervisorIdInput.value : supervisorSelect.value;
                const supervisorName = supervisorSelect.value;
                
                if (supervisorId === id || supervisorName === id) {
                    return { name: supervisorName, role: 'Supervisor' };
                }
            }
        }
        
        return member || { name: id, role: 'Unknown' };
    }
    
    // Initialize references array
    let referencesList = [];
    
    // Handle adding references
    const addReferenceBtn = document.getElementById('add-reference-btn');
    const referenceTitleInput = document.getElementById('reference-title');
    const referenceLinkInput = document.getElementById('reference-link');
    const referencesContainer = document.getElementById('references-container');
    const referencesInput = document.getElementById('references');
    
    addReferenceBtn.addEventListener('click', function() {
        const title = referenceTitleInput.value.trim();
        const link = referenceLinkInput.value.trim();
        
        if (title && link) {
            // Add to references array
            referencesList.push({ title, link });
            
            // Update hidden input
            updateReferencesInput();
            
            // Add to UI
            addReferenceToUI(title, link, referencesList.length - 1);
            
            // Clear inputs
            referenceTitleInput.value = '';
            referenceLinkInput.value = '';
            referenceTitleInput.focus();
        } else {
            alert('Please enter both a title and a link for the reference');
        }
    });
    
    // Function to add reference to UI
    function addReferenceToUI(title, link, index) {
        const referenceItem = document.createElement('div');
        referenceItem.className = 'reference-item mb-2 p-2 border rounded';
        referenceItem.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>${title}</strong>
                    <div><a href="${link}" target="_blank" class="small">${link}</a></div>
                </div>
                <button type="button" class="btn btn-sm btn-danger remove-reference" data-index="${index}">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        
        referencesContainer.appendChild(referenceItem);
        
        // Add event listener to remove button
        referenceItem.querySelector('.remove-reference').addEventListener('click', function() {
            const index = parseInt(this.getAttribute('data-index'));
            removeReference(index);
        });
    }
    
    // Function to remove reference
    function removeReference(index) {
        referencesList.splice(index, 1);
        updateReferencesInput();
        
        // Rebuild UI
        referencesContainer.innerHTML = '';
        referencesList.forEach((ref, idx) => {
            addReferenceToUI(ref.title, ref.link, idx);
        });
    }
    
    // Function to update hidden input
    function updateReferencesInput() {
        // Convert references to the format expected by the server
        const referencesText = referencesList.map(ref => `${ref.title} | ${ref.link}`).join('\n');
        referencesInput.value = referencesText;
    }
    
    // Fill references when editing a project
    function fillReferencesFromExisting(references) {
        if (!references || !Array.isArray(references)) return;
        
        referencesList = [];
        referencesContainer.innerHTML = '';
        
        references.forEach((ref, index) => {
            // Handle both new and old format
            if (ref.title && ref.link) {
                referencesList.push({ title: ref.title, link: ref.link });
                addReferenceToUI(ref.title, ref.link, index);
            } else if (ref.cite) {
                // Handle old format (try to extract title and link)
                const parts = ref.cite.split('|').map(part => part.trim());
                if (parts.length === 2) {
                    referencesList.push({ title: parts[0], link: parts[1] });
                    addReferenceToUI(parts[0], parts[1], index);
                } else {
                    referencesList.push({ title: ref.cite, link: ref.cite });
                    addReferenceToUI(ref.cite, ref.cite, index);
                }
            }
        });
        
        updateReferencesInput();
    }
    
    // Expose the function globally so it can be called when loading project data
    window.fillReferencesFromExisting = fillReferencesFromExisting;
    
    // Student Management Functions
    function loadStudentsForDropdown() {
        // Check if global students data is available
        if (window.studentsData && window.studentsData.length > 0) {
            allStudentsData = window.studentsData;
            return;
        }
        
        // If global data is not available, fetch it directly
        fetch('src/model/load_students.php')
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    // Store globally for future use
                    window.studentsData = data;
                    allStudentsData = data;
                    console.log('Students loaded:', allStudentsData.length);
                } else {
                    console.error('No students data received');
                    showToast('Warning', 'Could not load students data for team member dropdown', 'warning');
                }
            })
            .catch(error => {
                console.error('Error fetching students data:', error);
                showToast('Warning', 'Could not load students data for team member dropdown', 'warning');
            });
    }
    
    function setupStudentSearch(memberRow) {
        const studentInput = memberRow.querySelector('.member-name.student-search');
        const studentDropdown = memberRow.querySelector('.student-dropdown');
        const studentIdInput = memberRow.querySelector('.member-student-id');
        
        if (!studentInput || !studentDropdown) return;
        
        // Skip setup for readonly fields (creator's row)
        if (studentInput.hasAttribute('readonly')) {
            return;
        }
        
        let currentHighlightedIndex = -1;
        
        // Input event for filtering
        studentInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            filterAndDisplayStudents(searchTerm, studentDropdown, () => {
                currentHighlightedIndex = -1;
            });
        });
        
        // Focus event to show dropdown
        studentInput.addEventListener('focus', function() {
            const searchTerm = this.value.toLowerCase().trim();
            filterAndDisplayStudents(searchTerm, studentDropdown, () => {
                currentHighlightedIndex = -1;
            });
        });
        
        // Blur event to hide dropdown (with delay for clicks)
        studentInput.addEventListener('blur', function() {
            setTimeout(() => {
                hideStudentDropdown(studentDropdown);
            }, 150);
        });
        
        // Keyboard navigation
        studentInput.addEventListener('keydown', function(e) {
            const options = studentDropdown.querySelectorAll('.student-option');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                currentHighlightedIndex = Math.min(currentHighlightedIndex + 1, options.length - 1);
                updateStudentHighlight(options, currentHighlightedIndex);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                currentHighlightedIndex = Math.max(currentHighlightedIndex - 1, -1);
                updateStudentHighlight(options, currentHighlightedIndex);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (currentHighlightedIndex >= 0 && options[currentHighlightedIndex]) {
                    selectStudent(options[currentHighlightedIndex], studentInput, studentIdInput, studentDropdown);
                }
            } else if (e.key === 'Escape') {
                hideStudentDropdown(studentDropdown);
            }
        });
    }
    
    function filterAndDisplayStudents(searchTerm, dropdown, callback) {
        if (!allStudentsData || allStudentsData.length === 0) {
            dropdown.innerHTML = '<div class="student-no-results">No students data available</div>';
            dropdown.classList.add('show');
            return;
        }
        
        // Filter students based on search term
        const filteredStudents = allStudentsData.filter(student => 
            student.name.toLowerCase().includes(searchTerm) ||
            student.student_id.toLowerCase().includes(searchTerm)
        );
        
        // Clear previous options
        dropdown.innerHTML = '';
        
        if (filteredStudents.length === 0) {
            dropdown.innerHTML = '<div class="student-no-results">No students found matching your search</div>';
        } else {
            filteredStudents.forEach(student => {
                const option = document.createElement('div');
                option.className = 'student-option';
                option.innerHTML = `
                    <div>
                        <div class="student-name">${student.name}</div>
                        <div class="student-info">ID: ${student.student_id}</div>
                    </div>
                `;
                
                // Store student data in the element
                option.studentData = student;
                
                // Click handler
                option.addEventListener('click', function() {
                    const studentInput = dropdown.closest('.member-row').querySelector('.member-name.student-search');
                    const studentIdInput = dropdown.closest('.member-row').querySelector('.member-student-id');
                    selectStudent(this, studentInput, studentIdInput, dropdown);
                });
                
                dropdown.appendChild(option);
            });
        }
        
        dropdown.classList.add('show');
        
        // Force the dropdown to appear above the input by default
        // Only reposition downward if there's not enough space above
        const containerRect = dropdown.closest('.student-search-container').getBoundingClientRect();
        const spaceAbove = containerRect.top;
        
        // If there's not enough space above, position it below instead
        if (spaceAbove < 220) { // 220px accounts for dropdown height + some padding
            dropdown.style.bottom = 'auto';
            dropdown.style.top = '100%';
            dropdown.style.borderRadius = '0 0 var(--border-radius) var(--border-radius)';
            dropdown.style.borderTop = 'none';
            dropdown.style.borderBottom = '1px solid var(--border)';
            dropdown.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
        } else {
            // Reset to upward positioning (default)
            dropdown.style.bottom = '100%';
            dropdown.style.top = 'auto';
            dropdown.style.borderRadius = 'var(--border-radius) var(--border-radius) 0 0';
            dropdown.style.borderTop = '1px solid var(--border)';
            dropdown.style.borderBottom = 'none';
            dropdown.style.boxShadow = '0 -4px 12px rgba(0, 0, 0, 0.15)';
        }
        
        if (callback) callback();
    }
    
    function selectStudent(option, input, hiddenInput, dropdown) {
        const studentData = option.studentData;
        if (studentData) {
            input.value = studentData.name;
            hiddenInput.value = studentData.id;
            hideStudentDropdown(dropdown);
            
            // Trigger change event
            input.dispatchEvent(new Event('change'));
        }
    }
    
    function updateStudentHighlight(options, index) {
        options.forEach((option, i) => {
            if (i === index) {
                option.classList.add('highlighted');
            } else {
                option.classList.remove('highlighted');
            }
        });
    }
    
    function hideStudentDropdown(dropdown) {
        dropdown.classList.remove('show');
    }
    
    // Current User Management Functions
    function loadCurrentUser() {
        fetch('src/model/get_current_user.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.isLoggedIn && data.user) {
                    currentUserData = data.user;
                    console.log('Current user loaded:', currentUserData);
                    

                } else {
                    console.log('User not logged in or no user data available');
                    currentUserData = null;
                }
            })
            .catch(error => {
                console.error('Error fetching current user data:', error);
                currentUserData = null;
            });
    }
    

});