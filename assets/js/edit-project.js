    document.addEventListener('DOMContentLoaded', function() {
        // Check if user should be scrolled to timeline section
        if (window.location.hash === '#timeline-section') {
            // Delay scroll to ensure page is fully loaded
            setTimeout(() => {
                const timelineSection = document.getElementById('timeline-section');
                if (timelineSection) {
                    timelineSection.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start' 
                    });
                    // Add a subtle highlight effect to draw attention
                    timelineSection.style.transition = 'box-shadow 0.3s ease';
                    timelineSection.style.boxShadow = '0 0 20px rgba(37, 99, 235, 0.3)';
                    setTimeout(() => {
                        timelineSection.style.boxShadow = '';
                    }, 2000);
                }
            }, 1000);
        }
        
        // Debug mode for troubleshooting
        const DEBUG = true;
        
        // Initialize particles.js with modern configuration
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
                    "value": ["#2563eb", "#8b5cf6", "#0ea5e9", "#14b8a6", "#f59e0b"]
                },
                "shape": {
                    "type": ["circle", "triangle"],
                    "stroke": {
                        "width": 0,
                        "color": "#000000"
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
                        "size_min": 2,
                        "sync": false
                    }
                },
                "line_linked": {
                    "enable": true,
                    "distance": 150,
                    "color": "#2563eb",
                    "opacity": 0.2,
                    "width": 1
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
                        "enable": false,
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
                    "bubble": {
                        "distance": 400,
                        "size": 40,
                        "duration": 2,
                        "opacity": 8,
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
        
        // Initialize AOS animations with enhanced settings
        AOS.init({
            duration: 800,
            easing: 'cubic-bezier(0.19, 1, 0.22, 1)',
            once: false,
            mirror: true,
            anchorPlacement: 'top-bottom',
            offset: 50,
            delay: 100
        });
        
        // Elements
        const editProjectForm = document.getElementById('editProjectForm');
        const projectLoadingState = document.getElementById('projectLoadingState');
        const projectNotFound = document.getElementById('projectNotFound');
        const spinnerOverlay = document.getElementById('spinner');
        const toastContainer = document.getElementById('toastContainer');
        
        // Helper function to safely format dates from MongoDB format
        function formatMongoDate(mongoDate) {
            try {
                if (!mongoDate) return '';
                
                // Handle different MongoDB date formats
                let date;
                
                // Case 1: Object with $date property (MongoDB BSON Date)
                if (mongoDate.$date) {
                    // Handle ISO string or millisecond timestamp
                    if (typeof mongoDate.$date === 'string') {
                        date = new Date(mongoDate.$date);
                    } else if (typeof mongoDate.$date === 'number') {
                        date = new Date(mongoDate.$date);
                    } else {
                        return '';
                    }
                } 
                // Case 2: Direct ISO string (like "2023-09-15T08:30:00.000Z")
                else if (typeof mongoDate === 'string' && mongoDate.includes('T')) {
                    date = new Date(mongoDate);
                }
                // Case 3: Regular date object
                else if (mongoDate instanceof Date) {
                    date = mongoDate;
                }
                // Case 4: Try to parse as string
                else {
                    date = new Date(mongoDate);
                }
                
                // Check if date is valid
                if (isNaN(date.getTime())) {
                    console.warn('Invalid date:', mongoDate);
                    return '';
                }
                
                // Return in YYYY-MM-DD format
                return date.toISOString().split('T')[0];
            } catch (error) {
                console.error('Error formatting date:', error, mongoDate);
                return '';
            }
        }
        
        // Get project ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const projectId = urlParams.get('id');
        
        // If no project ID is provided, show error
        if (!projectId) {
            projectLoadingState.style.display = 'none';
            projectNotFound.style.display = 'block';
            return;
        }
        
        // Fetch project details
        loadProjectDetails(projectId);
        
        // Functions
        function loadProjectDetails(id) {
            showSpinner();
            
            if (DEBUG) console.log('Loading project with ID:', id);
            
            // First try to load from MongoDB
            fetch(`src/model/get_project.php?id=${id}`)
                .then(response => {
                    if (DEBUG) console.log('Database server response:', response);
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.project) {
                        // Project found in database, use it
                        if (DEBUG) console.log('Project found in database:', data.project);
                        hideSpinner();
                        buildEditForm(data.project);
                        projectLoadingState.style.display = 'none';
                        editProjectForm.style.display = 'block';
                    } else {
                        // Project not found
                        if (DEBUG) console.error('Project not found');
                        hideSpinner();
                        projectLoadingState.style.display = 'none';
                        projectNotFound.style.display = 'block';
                        
                        // Add error details to the UI
                        const errorDetails = document.createElement('p');
                        errorDetails.className = 'text-danger mt-2';
                        errorDetails.textContent = data.message || 'Project not found';
                        projectNotFound.querySelector('p').after(errorDetails);
                    }
                })
                .catch(error => {
                    hideSpinner();
                    console.error('Error:', error);
                    projectLoadingState.style.display = 'none';
                    projectNotFound.style.display = 'block';
                    
                    // Add error details to the UI for debugging
                    const errorDetails = document.createElement('p');
                    errorDetails.className = 'text-danger mt-2';
                    errorDetails.textContent = 'Network error: ' + (error.message || 'Unknown error');
                    projectNotFound.querySelector('p').after(errorDetails);
                });
        }
        
        function buildEditForm(project) {
            // Create a hidden input for project ID
            const projectId = project._id.$oid;
            
            // Build the form HTML
            let formHTML = `
                <input type="hidden" id="projectId" name="projectId" value="${projectId}">
                
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="title" class="form-label">Project Title*</label>
                            <input type="text" class="form-control" id="title" name="title" required value="${project.title || ''}">
                        </div>
                        
                        <div class="mb-3">
                            <label for="abstract" class="form-label">Abstract*</label>
                            <textarea class="form-control" id="abstract" name="abstract" rows="3" required>${project.abstract || ''}</textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Full Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5">${project.description || ''}</textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="coverImage" class="form-label">Cover Image</label>
                            <div class="file-upload">
                                <div class="file-upload-btn" id="coverImageBtn">
                                    <i class="bi bi-cloud-arrow-up"></i>
                                    <p>Click or drag to upload a new image</p>
                                </div>
                                <input type="file" class="form-control" id="coverImage" name="coverImage" accept="image/*">
                            </div>
                            <div id="imagePreviewContainer" class="mt-3 text-center" ${project.coverImage && project.coverImage.url ? '' : 'style="display: none;"'}>
                                <img id="imagePreview" class="preview-image" src="${project.coverImage && project.coverImage.url ? project.coverImage.url : ''}">
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="removeImage">
                                    <i class="bi bi-trash me-1"></i>Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="field" class="form-label">Research Field*</label>
                            <input type="text" class="form-control" id="field" name="field" required value="${project.field || ''}">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="institution" class="form-label">Institution</label>
                            <input type="text" class="form-control" id="institution" name="institution" value="${project.institution || 'United International University'}">
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="createdAt" class="form-label">Created At</label>
                            <input type="date" class="form-control" id="createdAt" name="createdAt" value="${formatMongoDate(project.createdAt)}">
                            <small class="text-muted">Leave empty for current date</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="updatedAt" class="form-label">Updated At</label>
                            <input type="date" class="form-control" id="updatedAt" name="updatedAt" value="${formatMongoDate(project.updatedAt)}">
                            <small class="text-muted">Leave empty for current date</small>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="estimatedCompletionDate" class="form-label">Estimated Completion Date</label>
                            <input type="date" class="form-control" id="estimatedCompletionDate" name="estimatedCompletionDate" value="${formatMongoDate(project.estimatedCompletionDate)}">
                            <small class="text-muted">Expected date when the project will be completed</small>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="privacy" class="form-label">Privacy Setting</label>
                            <select class="form-select" id="privacy" name="privacy" required>
                                <option value="0" ${(project.privacy === 0 || project.privacy === '0' || project.privacy === undefined) ? 'selected' : ''}>Public - Visible to everyone</option>
                                <option value="1" ${(project.privacy === 1 || project.privacy === '1') ? 'selected' : ''}>Private - Visible only to you and collaborators</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a privacy setting.
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="keywords" class="form-label">Keywords</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="keyword" placeholder="Add keyword">
                                <button class="btn btn-outline-primary" type="button" id="addKeyword">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                            <div id="keywordsContainer" class="mt-2">
                                <!-- Keywords will appear here -->
                                ${buildKeywordsBadges(project.keywords || [])}
                            </div>
                            <input type="hidden" id="keywordsList" name="keywords">
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="bi bi-link-45deg me-2"></i>External Links
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="github" class="form-label">GitHub Repository URL</label>
                                            <input type="url" class="form-control" id="github" name="github" placeholder="https://github.com/yourusername/your-repo" value="${project.links && project.links.github ? project.links.github : ''}">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="website" class="form-label">Project Website URL</label>
                                            <input type="url" class="form-control" id="website" name="website" placeholder="https://yourproject.example.com" value="${project.links && project.links.website ? project.links.website : ''}">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="paper" class="form-label">Research Paper URL</label>
                                            <input type="url" class="form-control" id="paper" name="paper" placeholder="https://journal.example.com/your-paper" value="${project.links && project.links.paper ? project.links.paper : ''}">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="doi" class="form-label">DOI</label>
                                            <input type="text" class="form-control" id="doi" name="doi" placeholder="10.xxxx/xxxxx" value="${project.links && project.links.doi ? project.links.doi : ''}">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="youtube" class="form-label">YouTube Video URL</label>
                                            <input type="url" class="form-control" id="youtube" name="youtube" placeholder="https://youtube.com/watch?v=xxxx" value="${project.links && project.links.youtube ? project.links.youtube : ''}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="bi bi-people-fill me-2"></i>Project Team
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="supervisor" class="form-label">Project Supervisor</label>
                                    <div class="supervisor-search-container position-relative">
                                        <input type="text" class="form-control" id="supervisor" name="supervisor" 
                                               placeholder="Type to search faculty..." autocomplete="off"
                                               value="${project.supervisor ? (project.supervisor.name || '') : ''}">
                                        <input type="hidden" id="supervisorId" name="supervisorId" 
                                               value="${project.supervisor && project.supervisor.userId ? (project.supervisor.userId.$oid || '') : ''}">
                                        <div class="supervisor-dropdown" id="supervisorDropdown">
                                            <!-- Faculty options will be populated here -->
                                        </div>
                                    </div>
                                    <div class="form-text">Choose a faculty member to supervise this project</div>
                                </div>
                                
                                <label class="form-label">Team Members</label>
                                <div id="membersContainer">
                                    <!-- Team members will be added here -->
                                </div>
                                <button type="button" class="btn btn-outline-primary mt-2" id="addMember">
                                    <i class="bi bi-plus-circle me-2"></i>Add Team Member
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project Timeline Section -->
                <div class="row mb-4" id="timeline-section">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="bi bi-calendar-event me-2"></i>Project Timeline
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">Add key milestones and events to track your project's progress.</p>
                                
                                <div id="timelineContainer">
                                    <!-- Timeline items will be added here -->
                                </div>
                                
                                <button type="button" class="btn btn-outline-primary mt-3" id="addTimelineItem">
                                    <i class="bi bi-plus-circle me-2"></i>Add Timeline Item
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Files Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="bi bi-file-earmark me-2"></i>Project Files
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="projectFiles" class="form-label">Upload Files (Reports, Papers, Data, etc.)</label>
                                    <input class="form-control" type="file" id="projectFiles" name="projectFiles[]" multiple>
                                    <div id="filesPreview" class="mt-2">
                                        <!-- Existing files will be displayed here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Media Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="bi bi-camera-video me-2"></i>Additional Media
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="mediaFiles" class="form-label">Upload Images or Videos</label>
                                    <input class="form-control" type="file" id="mediaFiles" name="mediaFiles[]" multiple accept="image/*,video/*">
                                    <div id="mediaPreview" class="mt-2 row g-2">
                                        <!-- Existing media will be displayed here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- References Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="bi bi-journal-text me-2"></i>References
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Project References</label>
                                    <div class="row g-2 py-3">
                                        <div class="col-md-5">
                                            <input type="text" class="form-control" id="referenceTitle" placeholder="Title/Author">
                                        </div>
                                        <div class="col-md-5">
                                            <input type="text" class="form-control" id="referenceLink" placeholder="URL (optional)">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-primary w-100" id="addReference">
                                                <i class="bi bi-plus-lg"></i> Add
                                            </button>
                                        </div>
                                    </div>
                                    <div id="references-container">
                                        <!-- References will appear here -->
                                    </div>
                                    <input type="hidden" id="referencesList" name="references">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Section (Hidden from user but will store/update random stats) -->
                <input type="hidden" id="viewsCount" name="viewsCount" value="${project.stats ? project.stats.views || '' : ''}">
                <input type="hidden" id="downloadsCount" name="downloadsCount" value="${project.stats ? project.stats.downloads || '' : ''}">
                <input type="hidden" id="favoritesCount" name="favoritesCount" value="${project.stats ? project.stats.favorites || '' : ''}">
                
                <!-- Comments Section (Hidden, will be initialized as empty array if not present) -->
                <input type="hidden" id="commentsArray" name="commentsArray" value="${JSON.stringify(project.comments || [])}">
            `;
            
            // Additional form sections will be added in separate functions
            
            // Add action buttons at the bottom
            formHTML += `
                <div class="text-end mt-4">
                    <a href="project_management.php" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Save Changes
                    </button>
                </div>
            `;
            
            // Set form HTML
            editProjectForm.innerHTML = formHTML;
            
            // Initialize form elements
            initializeForm(project);
        }
        
        function buildKeywordsBadges(keywords) {
            if (!keywords || keywords.length === 0) return '';
            
            return keywords.map((keyword, index) => {
                return `<span class="keyword-badge">${keyword} <i class="bi bi-x-circle" data-index="${index}"></i></span>`;
            }).join('');
        }
        
        function formatReferencesForTextarea(references) {
            if (!references || references.length === 0) return '';
            
            // If references is already a string, return as is
            if (typeof references === 'string') return references;
            
            // If it's an array of reference objects, convert to text format
            if (Array.isArray(references)) {
                // Check if references have the format from our new UI
                if (references.length > 0 && (references[0].title || references[0].link)) {
                    return references.map(ref => {
                        if (ref.link) {
                            return `${ref.title} | ${ref.link}`;
                        }
                        return ref.title;
                    }).join('\n');
                }
                
                // Handle old format with 'cite' property
                return references.map(ref => ref.cite || '').join('\n');
            }
            
            return '';
        }
        
        function initializeForm(project) {
            // Initialize keywords
            window.keywords = project.keywords || [];
            document.getElementById('keywordsList').value = JSON.stringify(window.keywords);
            
            // Add keyword functionality
            const addKeywordBtn = document.getElementById('addKeyword');
            const keywordInput = document.getElementById('keyword');
            const keywordsContainer = document.getElementById('keywordsContainer');
            
            addKeywordBtn.addEventListener('click', function() {
                addKeyword();
            });
            
            keywordInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addKeyword();
                }
            });
            
            // Initialize click handlers for existing keyword badges
            document.querySelectorAll('.keyword-badge i').forEach(icon => {
                icon.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    window.keywords.splice(index, 1);
                    updateKeywordsDisplay();
                });
            });
            
            // Initialize references
            window.references = [];
            
            // Parse existing references from the project
            if (project.references) {
                if (Array.isArray(project.references)) {
                    // Check the format of the references
                    if (project.references.length > 0) {
                        if (project.references[0].title || project.references[0].link) {
                            // Already in the new format
                            window.references = project.references;
                        } else if (project.references[0].cite) {
                            // Old format with cite property
                            window.references = project.references.map(ref => {
                                // Check if the cite contains a URL
                                const parts = ref.cite.split('|').map(part => part.trim());
                                if (parts.length > 1) {
                                    return {
                                        title: parts[0],
                                        link: parts[1]
                                    };
                                }
                                return {
                                    title: ref.cite,
                                    link: ''
                                };
                            });
                        }
                    }
                } else if (typeof project.references === 'string') {
                    // Handle string format
                    const lines = project.references.split('\n');
                    window.references = lines.map(line => {
                        const parts = line.split('|').map(part => part.trim());
                        if (parts.length > 1) {
                            return {
                                title: parts[0],
                                link: parts[1]
                            };
                        }
                        return {
                            title: line,
                            link: ''
                        };
                    });
                }
            }
            
            // Update references display
            updateReferencesDisplay();
            
            // Setup reference form handlers
            const addReferenceBtn = document.getElementById('addReference');
            const referenceTitleInput = document.getElementById('referenceTitle');
            const referenceLinkInput = document.getElementById('referenceLink');
            
            addReferenceBtn.addEventListener('click', function() {
                addReference();
            });
            
            referenceTitleInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addReference();
                }
            });
            
            referenceLinkInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addReference();
                }
            });
            
            // Initialize cover image handling
            const coverImageInput = document.getElementById('coverImage');
            const imagePreviewContainer = document.getElementById('imagePreviewContainer');
            const imagePreview = document.getElementById('imagePreview');
            const removeImageBtn = document.getElementById('removeImage');
            
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
            
            removeImageBtn.addEventListener('click', function() {
                coverImageInput.value = '';
                imagePreviewContainer.style.display = 'none';
                imagePreview.src = '';
            });
            
            // Initialize team members
            window.members = project.members || [];
            const membersContainer = document.getElementById('membersContainer');
            const addMemberBtn = document.getElementById('addMember');
            
            // Populate existing members
            if (window.members.length > 0) {
                window.members.forEach(member => {
                    addMemberRow(member);
                });
            } else {
                // Add at least one empty row
                addMemberRow();
            }
            
            // Add event listener for adding new members
            addMemberBtn.addEventListener('click', function() {
                addMemberRow();
            });
            
            // Initialize timeline items
            window.timelineItems = [];
            const timelineContainer = document.getElementById('timelineContainer');
            const addTimelineItemBtn = document.getElementById('addTimelineItem');
            
            // Parse existing timeline items from the project
            if (project.timeline && project.timeline.length > 0) {
                window.timelineItems = project.timeline.map(item => {
                    // Handle MongoDB date format
                    let date = item.date;
                    if (item.date && item.date.$date) {
                        date = new Date(item.date.$date).toISOString().split('T')[0];
                    } else if (typeof item.date === 'string' && item.date.includes('T')) {
                        date = new Date(item.date).toISOString().split('T')[0];
                    } else if (item.date) {
                        date = item.date;
                    } else {
                        date = new Date().toISOString().split('T')[0];
                    }
                    
                    return {
                        title: item.title || '',
                        description: item.description || '',
                        date: date,
                        status: item.status || 'Planned',
                        assignedBy: item.assignedBy || '',
                        assignedTo: Array.isArray(item.assignedTo) ? item.assignedTo : (item.assignedTo ? [item.assignedTo] : [])
                    };
                });
                updateTimelineDisplay();
            }
            
            // Add event listener for adding new timeline items
            addTimelineItemBtn.addEventListener('click', function() {
                addTimelineItem();
            });
            
            // Initialize files preview
            const filesPreviewContainer = document.getElementById('filesPreview');
            if (project.files && project.files.length > 0) {
                project.files.forEach(file => displayFilePreview(file, filesPreviewContainer));
            }
            
            // Initialize media preview
            const mediaPreviewContainer = document.getElementById('mediaPreview');
            if (project.media && project.media.length > 0) {
                project.media.forEach(media => displayMediaPreview(media, mediaPreviewContainer));
            }
            
            // Initialize form submission
            const editProjectForm = document.getElementById('editProjectForm');
            editProjectForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitForm(project._id.$oid);
            });
        }
        
        function addMemberRow(member = null) {
            const membersContainer = document.getElementById('membersContainer');
            const row = document.createElement('div');
            row.className = 'row mb-2 member-row';
            
            const name = member ? member.name || '' : '';
            const role = member ? member.role || '' : '';
            const contribution = member ? member.contribution || 0 : '';
            const userId = member && member.userId && member.userId.$oid ? member.userId.$oid : '';
            
            row.innerHTML = `
                <div class="col-md-3">
                    <div class="student-search-container position-relative">
                        <input type="text" class="form-control member-name student-search" placeholder="Type to search students..." autocomplete="off" required value="${name}">
                        <input type="hidden" class="member-student-id" value="${userId}">
                        <div class="student-dropdown">
                            <!-- Student options will be populated here -->
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control member-role" placeholder="Role (e.g., Author, Researcher)" value="${role}">
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control member-contribution" placeholder="Contribution %" min="0" max="100" value="${contribution}">
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control member-userid" placeholder="User ID (optional)" value="${userId}">
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
        
        function updateTimelineDisplay() {
            const timelineContainer = document.getElementById('timelineContainer');
            timelineContainer.innerHTML = '';
            
            if (window.timelineItems.length === 0) {
                timelineContainer.innerHTML = '<p class="text-muted text-center py-3">No timeline items added yet.</p>';
                return;
            }
            
            // Sort timeline items by date
            window.timelineItems.sort((a, b) => {
                const dateA = formatMongoDate(a.date);
                const dateB = formatMongoDate(b.date);
                return new Date(dateA || 0) - new Date(dateB || 0);
            });
            
            window.timelineItems.forEach((item, index) => {
                const statusClasses = {
                    'Completed': 'completed status-completed',
                    'In Progress': 'in-progress status-in-progress',
                    'Planned': 'planned status-planned',
                    'Delayed': 'delayed status-delayed'
                };
                
                const statusClass = statusClasses[item.status] || 'planned status-planned';
                
                const timelineItem = document.createElement('div');
                timelineItem.className = `timeline-item ${item.status ? item.status.toLowerCase().replace(' ', '-') : 'planned'}`;
                timelineItem.dataset.index = index;
                
                // Format date for display
                const formattedDate = formatMongoDate(item.date);
                const displayDate = formattedDate ? new Date(formattedDate) : new Date();
                const formattedDisplayDate = displayDate.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                
                // Generate assignment display text
                let assignmentInfo = '';
                
                // Ensure backward compatibility - handle various assignment field formats
                let assignedByName = '';
                
                if (item.assignedBy) {
                    // Handle new format (object with id and name)
                    if (typeof item.assignedBy === 'object' && item.assignedBy !== null) {
                        assignedByName = item.assignedBy.name || '';
                    } 
                    // Handle legacy format (string)
                    else {
                        const assignedBy = item.assignedBy;
                        const assignedByMember = findMemberById(assignedBy);
                        assignedByName = assignedByMember ? assignedByMember.name : assignedBy;
                    }
                }
                
                let assignedToNames = [];
                
                if (item.assignedTo) {
                    if (Array.isArray(item.assignedTo)) {
                        // Map to names, handling both new and legacy formats
                        assignedToNames = item.assignedTo.map(assignee => {
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
                            <span class="status-badge ${statusClass}">${item.status || 'Planned'}</span>
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
                    <h6 class="mb-2">${item.title || ''}</h6>
                    <p class="mb-2 small text-muted">${item.description || ''}</p>
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
                window.timelineItems.push(newItem);
            }
            
            // Update the display
            updateTimelineDisplay();
            
            // If it's a new item, open the edit dialog
            if (!item) {
                editTimelineItem(window.timelineItems.length - 1);
            }
        }
        
        function editTimelineItem(index) {
            const item = window.timelineItems[index];
            
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
                                <h5 class="modal-title" id="${modalId}Label">
                                    <i class="bi bi-calendar-event"></i> Edit Timeline Item
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="timelineEditForm">
                                    <!-- Basic Information -->
                                    <div class="section-header">
                                        <h6><i class="bi bi-info-circle"></i> Basic Information</h6>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="mb-2">
                                                <label for="timelineTitle" class="form-label">
                                                    <i class="bi bi-type"></i>Title *
                                                </label>
                                                <input type="text" class="form-control" id="timelineTitle" 
                                                       placeholder="Enter milestone title..." required>
                                                <div class="invalid-feedback">Title required</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-2">
                                                <label for="timelineDate" class="form-label">
                                                    <i class="bi bi-calendar3"></i>Date *
                                                </label>
                                                <input type="date" class="form-control" id="timelineDate" required>
                                                <div class="invalid-feedback">Date required</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label for="timelineDescription" class="form-label">
                                            <i class="bi bi-text-paragraph"></i>Description
                                        </label>
                                        <textarea class="form-control" id="timelineDescription" rows="2" 
                                                  placeholder="Describe what needs to be accomplished..."></textarea>
                                    </div>

                                    <!-- Status -->
                                    <div class="section-header">
                                        <h6><i class="bi bi-flag"></i> Status</h6>
                                    </div>
                                    <div class="mb-2">
                                        <label for="timelineStatus" class="form-label">
                                            <i class="bi bi-speedometer2"></i>Current Status
                                        </label>
                                        <select class="form-select" id="timelineStatus">
                                            <option value="Planned">📋 Planned</option>
                                            <option value="In Progress">⚡ In Progress</option>
                                            <option value="Completed">✅ Completed</option>
                                            <option value="Delayed">⚠️ Delayed</option>
                                        </select>
                                    </div>

                                    <!-- Assignment -->
                                    <div class="section-header">
                                        <h6><i class="bi bi-people"></i> Assignment</h6>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <label for="timelineAssignedBy" class="form-label">
                                                    <i class="bi bi-person-plus"></i>Assigned By
                                                </label>
                                                <select class="form-select" id="timelineAssignedBy">
                                                    <option value="">Select member</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <label for="timelineAssignedTo" class="form-label">
                                                    <i class="bi bi-person-check"></i>Assigned To
                                                </label>
                                                <select class="form-select" id="timelineAssignedTo" multiple size="3">
                                                    <option value="">Select members</option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="bi bi-info-circle"></i> Hold Ctrl/Cmd to select multiple
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x"></i> Cancel
                                </button>
                                <button type="button" class="btn btn-primary" id="saveTimelineChanges">
                                    <i class="bi bi-check"></i> Save
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
            }
            
            // Set form values
            document.getElementById('timelineTitle').value = item.title || '';
            document.getElementById('timelineDescription').value = item.description || '';
            document.getElementById('timelineDate').value = formatMongoDate(item.date) || new Date().toISOString().split('T')[0];
            document.getElementById('timelineStatus').value = item.status || 'Planned';
            
            // Populate assignment dropdowns with project members
            populateAssignmentDropdowns();
            
            // Auto-select current user in "Assigned By" if no assignment exists
            const assignedBySelect = document.getElementById('timelineAssignedBy');
            const assignedToSelect = document.getElementById('timelineAssignedTo');
            
            // Set assignment values - if no assignedBy exists, try to auto-select current user
            let assignedByValue = '';
            
            // Handle new format (object with id and name)
            if (item.assignedBy && typeof item.assignedBy === 'object' && item.assignedBy.id) {
                assignedByValue = item.assignedBy.id;
            } 
            // Handle legacy format (string ID or name)
            else if (item.assignedBy) {
                assignedByValue = item.assignedBy;
            }
            
            if (!assignedByValue) {
                // Try to find current user in the dropdown options
                const currentUserOption = Array.from(assignedBySelect.options).find(option => 
                    option.value && option.value !== ''
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
                window.timelineItems[index] = {
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
                window.timelineItems.splice(index, 1);
                updateTimelineDisplay();
            }
        }
        
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
        
        // Helper function to get current project members
        function getMembersData() {
            const members = [];
            
            // Get members from the form
            document.querySelectorAll('.member-row').forEach(row => {
                const name = row.querySelector('.member-name').value.trim();
                if (name) {
                    const role = row.querySelector('.member-role').value.trim();
                    const userId = row.querySelector('.member-userid').value.trim();
                    
                    members.push({
                        name: name,
                        role: role || 'Team Member',
                        userId: userId ? { $oid: userId } : null
                    });
                }
            });
            
            return members;
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
        
        function displayFilePreview(file, container) {
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
            
            container.appendChild(filePreview);
        }
        
        function displayMediaPreview(media, container) {
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
            
            container.appendChild(mediaPreview);
        }
        
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
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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
        
        function submitForm(projectId) {
            // Show loading state
            const saveBtn = document.querySelector('button[type="submit"]');
            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
            
            // Get form values
            const title = document.getElementById('title').value.trim();
            const abstract = document.getElementById('abstract').value.trim();
            const description = document.getElementById('description').value.trim();
            const researchField = document.getElementById('field').value.trim();
            const institution = document.getElementById('institution').value.trim();
            const privacySelect = document.getElementById('privacy');
            const privacy = privacySelect.value;
            
            if (DEBUG) {
                console.log('Submitting form with values:', {
                    projectId,
                    title,
                    abstract,
                    description,
                    field: researchField,
                    institution,
                    privacy
                });
            }
            
            // Validate required fields
            let hasErrors = false;
            
            if (!title) {
                document.getElementById('title').classList.add('is-invalid');
                hasErrors = true;
            } else {
                document.getElementById('title').classList.remove('is-invalid');
            }
            
            if (!abstract) {
                document.getElementById('abstract').classList.add('is-invalid');
                hasErrors = true;
            } else {
                document.getElementById('abstract').classList.remove('is-invalid');
            }
            
            if (!researchField) {
                document.getElementById('field').classList.add('is-invalid');
                hasErrors = true;
            } else {
                document.getElementById('field').classList.remove('is-invalid');
            }
            
            // Validate privacy
            if (privacy === '' || privacy === null || privacy === undefined) {
                privacySelect.classList.add('is-invalid');
                hasErrors = true;
            } else {
                privacySelect.classList.remove('is-invalid');
            }
            
            if (hasErrors) {
                showToast('Error', 'Please fill in all required fields', 'error');
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
                return;
            }
            
            // Get keywords from global variable
            const keywords = window.keywords || [];
            
            // Get dates
            const createdDate = document.getElementById('createdAt').value;
            const updatedDate = document.getElementById('updatedAt').value || new Date().toISOString().split('T')[0];
            const estimatedCompletionDate = document.getElementById('estimatedCompletionDate').value;
            
            // Get links
            const githubUrl = document.getElementById('github').value.trim();
            const projectUrl = document.getElementById('website').value.trim();
            const paperUrl = document.getElementById('paper').value.trim();
            const doi = document.getElementById('doi').value.trim();
            const youtubeUrl = document.getElementById('youtube').value.trim();
            
            // Get supervisor
            const supervisor = document.getElementById('supervisor').value.trim();
            
            // Get team members
            const teamMembers = [];
            document.querySelectorAll('.member-row').forEach(row => {
                const memberName = row.querySelector('.member-name').value.trim();
                if (memberName) { // Only add if there's a name
                    const role = row.querySelector('.member-role').value.trim();
                    const contribution = parseInt(row.querySelector('.member-contribution').value) || 0;
                    // Check both the hidden member-student-id and the visible member-userid fields
                    let userId = row.querySelector('.member-student-id').value.trim();
                    if (!userId) {
                        userId = row.querySelector('.member-userid').value.trim();
                    }
                    
                    teamMembers.push({
                        name: memberName,
                        role: role,
                        contribution: contribution,
                        userId: userId ? { $oid: userId } : null
                    });
                    
                    if (DEBUG) {
                        console.log('Added team member:', {
                            name: memberName,
                            role: role,
                            contribution: contribution,
                            userId: userId ? { $oid: userId } : null
                        });
                    }
                }
            });
            
            // Get timeline items (already in window.timelineItems)
            
            // Get references from the window.references array
            // Make sure the references input is updated with the latest data
            document.getElementById('referencesList').value = JSON.stringify(window.references);
            const references = window.references || [];
            
            if (DEBUG) {
                console.log('References data being submitted:', references);
            }
            
            // Get stats fields
            const views = parseInt(document.getElementById('viewsCount').value) || 0;
            const downloads = parseInt(document.getElementById('downloadsCount').value) || 0;
            const favorites = parseInt(document.getElementById('favoritesCount').value) || 0;
            
            // Get comments field
            const commentsValue = document.getElementById('commentsArray').value;
            const comments = commentsValue ? JSON.parse(commentsValue) : [];
            
            // Create FormData object for file uploads
            const formData = new FormData();
            
            // Add project ID
            formData.append('project_id', projectId);
            
            // Add basic information
            formData.append('title', title);
            formData.append('abstract', abstract);
            formData.append('description', description);
            formData.append('field', researchField);
            formData.append('institution', institution);
            formData.append('privacy', privacy);
            formData.append('keywords', JSON.stringify(keywords));
            
            // Add dates
            formData.append('created_date', createdDate);
            formData.append('updated_date', updatedDate);
            formData.append('estimatedCompletionDate', estimatedCompletionDate);
            
            // Add links
            formData.append('github_url', githubUrl);
            formData.append('project_url', projectUrl);
            formData.append('paper_url', paperUrl);
            formData.append('doi', doi);
            formData.append('youtube_url', youtubeUrl);
            
            // Add supervisor and supervisorId
            formData.append('supervisor', supervisor);
            const supervisorId = document.getElementById('supervisorId').value.trim();
            formData.append('supervisorId', supervisorId);
            
            // Add team members
            formData.append('team_members', JSON.stringify(teamMembers));
            
            // Add timeline items
            formData.append('timeline', JSON.stringify(window.timelineItems));
            
            // Add references
            formData.append('references', JSON.stringify(references));
            
            // Add stats
            formData.append('views', views);
            formData.append('downloads', downloads);
            formData.append('favorites', favorites);
            
            // Add comments
            formData.append('comments', JSON.stringify(comments));
            
            // Add cover image if selected
            const coverImageInput = document.getElementById('coverImage');
            if (coverImageInput.files && coverImageInput.files[0]) {
                formData.append('cover_image', coverImageInput.files[0]);
            }
            
            // Add project files if selected
            const filesInput = document.getElementById('projectFiles');
            if (filesInput.files && filesInput.files.length > 0) {
                for (let i = 0; i < filesInput.files.length; i++) {
                    formData.append('project_files[]', filesInput.files[i]);
                }
            }
            
            // Add media files if selected
            const mediaInput = document.getElementById('mediaFiles');
            if (mediaInput.files && mediaInput.files.length > 0) {
                for (let i = 0; i < mediaInput.files.length; i++) {
                    formData.append('project_media[]', mediaInput.files[i]);
                }
            }
            
            // For debugging - log all form data
            if (DEBUG) {
                console.log('Submitting FormData:');
                for (let [key, value] of formData.entries()) {
                    console.log(key, value);
                }
            }
            
            // Send the request
            fetch('src/model/update_project.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message and redirect to project details page
                    showToast('Success', 'Project updated successfully!', 'success');
                    setTimeout(() => {
                        window.location.href = `Project_details.php?id=${projectId}`;
                    }, 1500);
                } else {
                    // Show error message
                    showToast('Error', data.message || 'Failed to update project', 'error');
                    
                    // Reset button state
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error', 'An error occurred while updating the project', 'error');
                
                // Reset button state
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            });
        }
        
        // Function to handle keywords
        function addKeyword() {
            const keywordInput = document.getElementById('keyword');
            const keyword = keywordInput.value.trim();
            
            if (keyword) {
                if (!window.keywords.includes(keyword)) {
                    window.keywords.push(keyword);
                    updateKeywordsDisplay();
                }
                keywordInput.value = '';
            }
        }
        
        function updateKeywordsDisplay() {
            const keywordsContainer = document.getElementById('keywordsContainer');
            const keywordsList = document.getElementById('keywordsList');
            
            keywordsContainer.innerHTML = '';
            keywordsList.value = JSON.stringify(window.keywords);
            
            if (window.keywords.length === 0) {
                keywordsContainer.innerHTML = '<p class="text-muted">No keywords added yet.</p>';
                return;
            }
            
            window.keywords.forEach((keyword, index) => {
                const badge = document.createElement('span');
                badge.className = 'badge bg-primary keyword-badge me-2 mb-2';
                badge.innerHTML = `${keyword} <i class="bi bi-x-circle ms-1" data-index="${index}" role="button"></i>`;
                keywordsContainer.appendChild(badge);
                
                // Add event listener to remove icon
                badge.querySelector('i').addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    window.keywords.splice(index, 1);
                    updateKeywordsDisplay();
                });
            });
        }
        
        function updateReferencesDisplay() {
            const referencesContainer = document.getElementById('references-container');
            const referencesList = document.getElementById('referencesList');
            
            referencesContainer.innerHTML = '';
            referencesList.value = JSON.stringify(window.references);
            
            if (window.references.length === 0) {
                referencesContainer.innerHTML = '<p class="text-muted text-center py-3">No references added yet.</p>';
                return;
            }
            
            window.references.forEach((reference, index) => {
                const referenceItem = document.createElement('div');
                referenceItem.className = 'reference-item';
                
                let linkHtml = '';
                if (reference.link) {
                    linkHtml = `<div class="reference-link">
                        <a href="${reference.link}" target="_blank">${reference.link}</a>
                    </div>`;
                }
                
                referenceItem.innerHTML = `
                    <div class="reference-content">
                        <div class="reference-title">${reference.title}</div>
                        ${linkHtml}
                    </div>
                    <i class="bi bi-x-circle remove-reference" data-index="${index}"></i>
                `;
                
                referencesContainer.appendChild(referenceItem);
                
                // Add event listener to remove icon
                referenceItem.querySelector('.remove-reference').addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    window.references.splice(index, 1);
                    updateReferencesDisplay();
                });
            });
        }
        
        function addReference() {
            const titleInput = document.getElementById('referenceTitle');
            const linkInput = document.getElementById('referenceLink');
            
            const title = titleInput.value.trim();
            const link = linkInput.value.trim();
            
            if (title) {
                window.references.push({
                    title: title,
                    link: link
                });
                
                // Clear inputs
                titleInput.value = '';
                linkInput.value = '';
                
                // Update display
                updateReferencesDisplay();
                
                // Focus on title input for next entry
                titleInput.focus();
            }
        }
        
        // ===== DROPDOWN FUNCTIONALITY =====
        
        // Global variables for dropdown functionality
        let allFacultyData = [];
        let allStudentsData = [];
        let currentHighlightedIndex = -1;
        
        // Load data and initialize dropdowns when page loads
        function initializeDropdowns() {
            loadFacultyForDropdown();
            loadStudentsForDropdown();
        }
        
        function loadFacultyForDropdown() {
            // Check if global faculty data is available (from Faculty_Page.php)
            if (window.facultyData && window.facultyData.length > 0) {
                allFacultyData = window.facultyData;
                setupSupervisorSearch();
                return;
            }
            
            // If global data is not available, fetch it directly
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
                    }
                })
                .catch(error => {
                    console.error('Error fetching faculty data:', error);
                });
        }
        
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
                        
                        // Add debugging to inspect the structure
                        if (DEBUG) {
                            console.log('Loaded student data:', data[0]);
                        }
                    } else {
                        console.error('No students data received');
                    }
                })
                .catch(error => {
                    console.error('Error fetching students data:', error);
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
            
            // Position dropdown
            positionDropdown(supervisorDropdown, document.getElementById('supervisor'));
        }
        
        function selectFaculty(optionElement) {
            const facultyId = optionElement.getAttribute('data-faculty-id');
            const facultyName = optionElement.getAttribute('data-faculty-name');
            
            // Set the input values
            document.getElementById('supervisor').value = facultyName;
            document.getElementById('supervisorId').value = facultyId;
            
            // Hide dropdown
            hideSupervisorDropdown();
        }
        
        function hideSupervisorDropdown() {
            const supervisorDropdown = document.getElementById('supervisorDropdown');
            supervisorDropdown.classList.remove('show');
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
        
        function setupStudentSearch(memberRow) {
            const studentInput = memberRow.querySelector('.member-name.student-search');
            const studentDropdown = memberRow.querySelector('.student-dropdown');
            const studentIdInput = memberRow.querySelector('.member-student-id');
            
            if (!studentInput || !studentDropdown) return;
            
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
                (student.name && student.name.toLowerCase().includes(searchTerm)) || 
                (student.student_id && student.student_id.toLowerCase().includes(searchTerm))
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
            
            // Position dropdown
            positionDropdown(dropdown, dropdown.closest('.student-search-container'));
            
            if (callback) callback();
        }
        
        function selectStudent(option, input, hiddenInput, dropdown) {
            const studentData = option.studentData;
            if (studentData) {
                input.value = studentData.name;
                // Store the MongoDB _id of the student in the hidden input and member-userid field
                if (studentData._id && studentData._id.$oid) {
                    hiddenInput.value = studentData._id.$oid;
                    const memberRow = dropdown.closest('.member-row');
                    if (memberRow) {
                        const userIdInput = memberRow.querySelector('.member-userid');
                        if (userIdInput) {
                            userIdInput.value = studentData._id.$oid;
                        }
                    }
                } else if (studentData.id) {
                    hiddenInput.value = studentData.id;
                    const memberRow = dropdown.closest('.member-row');
                    if (memberRow) {
                        const userIdInput = memberRow.querySelector('.member-userid');
                        if (userIdInput) {
                            userIdInput.value = studentData.id;
                        }
                    }
                }
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
        
        function positionDropdown(dropdown, container) {
            const containerRect = container.getBoundingClientRect();
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
        }
        
        // Initialize dropdowns when document is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeDropdowns);
        } else {
            initializeDropdowns();
        }

        // Listen for theme changes from navbar toggle
        document.addEventListener('themeChanged', function(e) {
            const newTheme = e.detail.theme;
            console.log('Theme changed to:', newTheme);
            
            // Re-trigger any theme-dependent animations or effects
            const particles = document.getElementById('particles-js');
            if (particles && window.pJSDom && window.pJSDom[0]) {
                // Update particles colors based on theme
                const pJS = window.pJSDom[0].pJS;
                if (newTheme === 'light') {
                    pJS.particles.color.value = ["#2563eb", "#8b5cf6", "#0ea5e9", "#14b8a6", "#f59e0b"];
                    pJS.particles.line_linked.color = "#2563eb";
                } else {
                    pJS.particles.color.value = ["#2563eb", "#8b5cf6", "#0ea5e9", "#14b8a6", "#f59e0b"];
                    pJS.particles.line_linked.color = "#2563eb";
                }
            }
        });
    });