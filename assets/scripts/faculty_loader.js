// facultyLoader.js

document.addEventListener('DOMContentLoaded', function () {
    const facultyList = document.getElementById('facultyList');
    const facultySearch = document.getElementById('facultySearch');
    const toggleAlphabetical = document.getElementById('toggle-alphabetical');
    const toggleDepartment = document.getElementById('toggle-department');
    const toggleAllFaculty = document.getElementById('toggle-all-faculty');
    
    let allFaculty = [];
    let currentFilter = 'all';
    let currentSort = 'default';
    let currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';

    // Theme change listener
    document.addEventListener('themeChanged', function(e) {
        currentTheme = e.detail ? e.detail.theme : document.documentElement.getAttribute('data-theme');
        updateDisplay(); // Re-render faculty cards with new theme
    });

    function renderFaculty(facultyData) {
        facultyList.innerHTML = '';
        if (facultyData.length === 0) {
            facultyList.innerHTML = '<p class="text-center text-muted">No faculty members found matching your criteria.</p>';
            return;
        }
        
        // Create a row container
        const rowDiv = document.createElement('div');
        rowDiv.className = 'row g-4';
        facultyList.appendChild(rowDiv);
        
        facultyData.forEach((faculty, index) => {
            const profileImage = faculty.profile_image ? faculty.profile_image : 'assets/resources/user_avater.png';
            const shortBio = faculty.bio ? (faculty.bio.length > 100 ? faculty.bio.substring(0, 100) + '...' : faculty.bio) : 'No biography available.';
            
            // Create specialty display (use first field of research if available)
            let specialty = '';
            if (faculty.interested_fields_of_research && faculty.interested_fields_of_research.length > 0) {
                specialty = faculty.interested_fields_of_research[0];
            } else {
                specialty = 'Research Faculty';
            }
            
            // Get department or default
            const department = faculty.department || 'General';
            const position = faculty.position || 'Faculty Member';
            
            // Prepare tracking metadata
            const trackingData = {
                title: faculty.name,
                specialty: specialty,
                bio: shortBio,
                department: department,
                researchInterests: faculty.interested_fields_of_research || [],
                tags: [specialty, 'Faculty', department].concat(faculty.interested_fields_of_research || []).filter(Boolean)
            };
            
            const facultyCard = `
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="neo-faculty-card" 
                         data-faculty-id="${faculty._id}" 
                         data-item-type="faculty" 
                         data-item-id="${faculty._id}"
                         data-department="${department}"
                         data-specialty="${specialty}"
                         data-tracking-metadata='${JSON.stringify(trackingData).replace(/'/g, "&apos;").replace(/"/g, "&quot;")}'
                         style="cursor: pointer;">
                        <div class="card-border"></div>
                        <div class="faculty-img-wrapper">
                            <div class="faculty-img-container" id="img-container-${index}">
                                <img src="${profileImage}" alt="${faculty.name}" class="img-fluid" 
                                     onload="handleImageLoad(${index})" onerror="handleImageError(${index})">
                                <div class="img-overlay"></div>
                            </div>
                        </div>
                        <div class="faculty-info">
                            <h4 class="faculty-name">${faculty.name}</h4>
                            <p class="faculty-position">${position}</p>
                            <p class="faculty-department text-muted small">
                                <i class="fas fa-building me-1"></i>${department}
                            </p>
                            <div class="faculty-quote">
                                <q>${shortBio}</q>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            rowDiv.insertAdjacentHTML('beforeend', facultyCard);
        });
        
        // Add event listeners to faculty cards
        setTimeout(() => {
            const facultyCards = document.querySelectorAll('.neo-faculty-card');
            facultyCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    // Allow the preference tracker to handle the click first
                    const facultyId = this.getAttribute('data-faculty-id');
                    
                    if (facultyId) {
                        // Small delay to ensure preference tracking completes
                        setTimeout(() => {
                            window.location.href = `Faculty_Profile.php?id=${facultyId}`;
                        }, 100);
                    }
                });
            });
            
            // Add hover effects that respect the theme
            addThemeAwareHoverEffects();
            
            console.log('Faculty Loader: Enhanced click tracking enabled for', facultyCards.length, 'faculty cards');
        }, 500);
    }

    // Add hover effects that respect the current theme
    function addThemeAwareHoverEffects() {
        const facultyCards = document.querySelectorAll('.neo-faculty-card');
        facultyCards.forEach(card => {
            // Add mouseover and mouseout effects
            card.addEventListener('mouseover', function() {
                const theme = document.documentElement.getAttribute('data-theme') || 'dark';
                if (theme === 'light') {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 8px 32px rgba(67, 97, 238, 0.12)';
                } else {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.25)';
                }
            });
            
            card.addEventListener('mouseout', function() {
                this.style.transform = '';
                this.style.boxShadow = '';
            });
        });
    }
    
    function sortFaculty(facultyData, sortType) {
        const sortedData = [...facultyData];
        
        switch (sortType) {
            case 'alphabetical':
                return sortedData.sort((a, b) => a.name.localeCompare(b.name));
            case 'department':
                return sortedData.sort((a, b) => {
                    const deptA = a.department || 'General';
                    const deptB = b.department || 'General';
                    if (deptA === deptB) {
                        return a.name.localeCompare(b.name);
                    }
                    return deptA.localeCompare(deptB);
                });
            default:
                return sortedData;
        }
    }
    
    function filterFaculty(query = '') {
        let filteredData = [...allFaculty];
        
        // Apply text search if query provided
        if (query.trim()) {
            const searchQuery = query.toLowerCase();
            filteredData = filteredData.filter(faculty => {
                const name = faculty.name.toLowerCase();
                const bio = (faculty.bio || '').toLowerCase();
                const department = (faculty.department || '').toLowerCase();
                const position = (faculty.position || '').toLowerCase();
                const researchFields = (faculty.interested_fields_of_research || [])
                    .join(' ').toLowerCase();
                
                return name.includes(searchQuery) ||
                       bio.includes(searchQuery) ||
                       department.includes(searchQuery) ||
                       position.includes(searchQuery) ||
                       researchFields.includes(searchQuery);
            });
        }
        
        // Apply sorting
        const sortedData = sortFaculty(filteredData, currentSort);
        
        return sortedData;
    }
    
    function updateDisplay() {
        const query = facultySearch ? facultySearch.value : '';
        const filteredData = filterFaculty(query);
        renderFaculty(filteredData);
        
        // Update button states
        updateButtonStates();
    }
    
    function updateButtonStates() {
        // Remove active class from all buttons
        [toggleAlphabetical, toggleDepartment, toggleAllFaculty].forEach(btn => {
            if (btn) btn.classList.remove('active');
        });
        
        // Add active class to current sort button
        switch (currentSort) {
            case 'alphabetical':
                if (toggleAlphabetical) toggleAlphabetical.classList.add('active');
                break;
            case 'department':
                if (toggleDepartment) toggleDepartment.classList.add('active');
                break;
            default:
                if (toggleAllFaculty) toggleAllFaculty.classList.add('active');
                break;
        }
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
                img.src = 'assets/resources/user_avater.png';
                img.onload = () => container.classList.add('loaded');
            }
        }
    };

    // Load faculty data
    fetch('src/model/load_faculty.php')
        .then(response => response.json())
        .then(data => {
            allFaculty = data;
            updateDisplay();
        })
        .catch(error => {
            if (facultyList) {
                facultyList.innerHTML = `<p class="text-danger">Failed to load faculty data.</p>`;
            }
            console.error('Error loading faculty data:', error);
        });

    // Event listeners
    if (facultySearch) {
        facultySearch.addEventListener('input', function () {
            updateDisplay();
        });
    }
    
    if (toggleAlphabetical) {
        toggleAlphabetical.addEventListener('click', function() {
            currentSort = currentSort === 'alphabetical' ? 'default' : 'alphabetical';
            updateDisplay();
        });
    }
    
    if (toggleDepartment) {
        toggleDepartment.addEventListener('click', function() {
            currentSort = currentSort === 'department' ? 'default' : 'department';
            updateDisplay();
        });
    }
    
    if (toggleAllFaculty) {
        toggleAllFaculty.addEventListener('click', function() {
            currentSort = 'default';
            if (facultySearch) facultySearch.value = '';
            updateDisplay();
        });
    }
    
    // Listen for theme changes from storage events (cross-tab sync)
    window.addEventListener('storage', function(e) {
        if (e.key === 'theme') {
            currentTheme = e.newValue || 'dark';
            document.documentElement.setAttribute('data-theme', currentTheme);
            updateDisplay();
        }
    });
});
