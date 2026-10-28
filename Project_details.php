<?php
// Set secure session cookie parameters before session_start()
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => $cookieParams['lifetime'],
    'path' => $cookieParams['path'],
    'domain' => $cookieParams['domain'],
    'secure' => $secure, // Only send cookie over HTTPS
    'httponly' => true,  // Prevent JS access
    'samesite' => 'Strict' // Prevent CSRF
]);
session_start();

// Session idle timeout (30 minutes)
$timeout = 30 * 60; // 30 minutes in seconds
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    // Save current URL before logout
    $currentUrl = $_SERVER['REQUEST_URI'];
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1&redirect=' . urlencode($currentUrl));
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Show timeout message if redirected due to inactivity
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    echo '<div class="alert alert-warning text-center" style="margin: 20px;">Your session has expired due to inactivity. Please log in again.</div>';
}

// Define a constant to indicate this is the Project_details.php file
// This is used by included files like timeline_editor_overlay.php
define('INCLUDED_IN_PROJECT_DETAILS', true);

// Include MongoDB connection
require __DIR__ . '/vendor/autoload.php';

// Connect to MongoDB
$client = new MongoDB\Client(require __DIR__ . '/src/config/mongo_uri.php');
$db = $client->uiurp;
$studentsCollection = $db->students;
$facultiesCollection = $db->faculties;

// Import MongoDB BSON classes for ObjectId
use MongoDB\BSON\ObjectId;

// Helper function to check if a user profile exists and get profile URL
function getUserProfileInfo($userId, $userType = null) {
    global $studentsCollection, $facultiesCollection;
    
    if (!$userId) {
        return null;
    }
    
    try {
        // If userType is specified, check only that collection
        if ($userType === 'student') {
            $student = $studentsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
            if ($student) {
                return [
                    'exists' => true,
                    'url' => "Student_Profile.php?id=" . $userId,
                    'type' => 'student'
                ];
            }
        } elseif ($userType === 'faculty') {
            $faculty = $facultiesCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
            if ($faculty) {
                return [
                    'exists' => true,
                    'url' => "Faculty_Profile.php?id=" . $userId,
                    'type' => 'faculty'
                ];
            }
        } else {
            // Check both collections if type is not specified
            $student = $studentsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
            if ($student) {
                return [
                    'exists' => true,
                    'url' => "Student_Profile.php?id=" . $userId,
                    'type' => 'student'
                ];
            }
            
            $faculty = $facultiesCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
            if ($faculty) {
                return [
                    'exists' => true,
                    'url' => "Faculty_Profile.php?id=" . $userId,
                    'type' => 'faculty'
                ];
            }
        }
    } catch (Exception $e) {
        // Invalid ObjectId or other error
        return null;
    }
    
    return null;
}

// Helper function to create clickable name with profile link
function createProfileLink($name, $userId, $userType = null) {
    $profileInfo = getUserProfileInfo($userId, $userType);
    
    if ($profileInfo && $profileInfo['exists']) {
        return "<a href='{$profileInfo['url']}' class='profile-link' title='View {$profileInfo['type']} profile'>{$name}</a>";
    }
    
    return $name;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Project Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/styles/home.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Performance optimization styles -->
    <link rel="stylesheet" href="assets/styles/performance.css">
    <link rel="stylesheet" href="assets/styles/project-details.css">
    
    <!-- Performance optimization script - Load early for immediate optimizations -->
    <script src="assets/js/performance-optimizer.js" defer></script>
    
    <!-- Prevent Theme Flash Script - Must run immediately -->
    <script>
    (function() {
        // Get saved theme immediately to prevent flash
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }
    })();
    </script>
    
    
</head>
<body>
    <?php include 'src/includes/navbar.php'; ?>
    
    <!-- Inject PHP session data into JavaScript -->
    <script>
        <?php if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['user_id'])): ?>
        var currentUserId = "<?php echo $_SESSION['user_id']; ?>";
        var currentUserName = "<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>";
        var currentUserType = "<?php echo isset($_SESSION['user_type']) ? $_SESSION['user_type'] : ''; ?>";
        <?php else: ?>
        var currentUserId = null;
        var currentUserName = null;
        var currentUserType = null;
        <?php endif; ?>
    </script>

    <!-- Background particles -->
    <div id="particles-js"></div>

    <!-- Special accent elements -->
    <div class="floating-accent"></div>
    <div class="floating-accent"></div>
    <div class="floating-accent"></div>
    
    <!-- Modern hero background -->
    <div class="hero-background">
        <div class="hero-grid"></div>
        <div class="hero-glow"></div>
    </div>

    <div class="container my-5">
        <!-- Loading spinner -->
        <div id="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <!-- Project not found message -->
        <div id="project-not-found">
            <div class="alert alert-warning shadow" role="alert">
                <h4 class="alert-heading">Project Not Found!</h4>
                <p>The research project you're looking for could not be found. It may have been removed or the link might be incorrect.</p>
                <hr>
                <a href="Research_page.php" class="btn btn-primary">Back to Research Projects</a>
            </div>
        </div>

        <!-- Project details container -->
        <div id="project-details" style="display: none;">
            <!-- Project header section -->
            <div id="project-header" class="project-header mb-4" data-aos="fade-up"></div>
            
            <!-- Main content -->
            <div class="row">
                <!-- Left column: Description and content -->
                <div class="col-lg-8">
                    <div id="project-abstract" class="abstract-box" data-aos="fade-up" data-aos-delay="100"></div>
                    
                    <div id="project-description" class="mb-4" data-aos="fade-up" data-aos-delay="200"></div>
                    
                    <div id="project-media" class="mb-5" data-aos="fade-up" data-aos-delay="300">
                        <h3 class="section-title">Media</h3>
                        <div class="row" id="media-container">
                            <!-- Media items will be loaded here -->
                        </div>
                    </div>
                    
                    <div id="project-timeline" class="mb-5" data-aos="fade-up" data-aos-delay="400">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="section-title mb-0">Project Timeline</h3>
                            <button id="editTimelineBtn" class="btn btn-outline-primary btn-sm" title="Edit Timeline">
                                <i class="bi bi-pencil-square me-1"></i>Edit Timeline
                            </button>
                        </div>
                        <div class="timeline-container" id="timeline-container">
                            <!-- Timeline items will be loaded here -->
                        </div>
                    </div>
                    
                    <div id="project-references" class="mb-5" data-aos="fade-up" data-aos-delay="500">
                        <h3 class="section-title">References</h3>
                        <div id="references-container">
                            <!-- References will be loaded here -->
                        </div>
                    </div>
                </div>
                
                <!-- Right column: Metadata and sidebar info -->
                <div class="col-lg-4" style="padding-top: 0;">
                    <div class="metadata-card mb-4 mt-0" style="margin-top: 0 !important;" data-aos="fade-left" data-aos-delay="100">
                        <h4 class="mb-3">Project Information</h4>
                        <div id="project-info">
                            <!-- Project info will be loaded here -->
                        </div>
                    </div>
                    
                    <!-- Project Meetings Section -->
                    <div class="card mb-4" data-aos="fade-left" data-aos-delay="150" id="project-meetings-section">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-white">
                                <i class="bi bi-calendar-event me-2"></i>Project Meetings
                            </h5>
                            <button id="addMeetingBtn" class="btn btn-sm btn-primary" title="Schedule Meeting">
                                <i class="bi bi-plus-circle"></i>
                            </button>
                        </div>
                        <div class="card-body" id="meetings-container" style="max-height: 300px; overflow-y: auto;">
                            <div id="meetings-loading" style="display: none;">
                                <div class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2 mb-0 text-muted small">Loading meetings...</p>
                                </div>
                            </div>
                            <div id="meetings-list">
                                <!-- Meetings will be loaded here -->
                            </div>
                            <div id="no-meetings" class="text-center py-4">
                                <i class="bi bi-calendar-x text-primary" style="font-size: 2rem;"></i>
                                <p class="text-primary mt-2 mb-0">No meetings scheduled yet</p>
                                <small class="text-primary">Click the + button to schedule a meeting</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4" data-aos="fade-left" data-aos-delay="200">
                        <div class="card-header">
                            <h5 class="mb-0 text-white">Team Members</h5>
                        </div>
                        <div class="card-body" id="team-members">
                            <!-- Team members will be loaded here -->
                        </div>
                    </div>
                    
                    <div class="card mb-4" data-aos="fade-left" data-aos-delay="300">
                        <div class="card-header">
                            <h5 class="mb-0 text-white">Keywords</h5>
                        </div>
                        <div class="card-body" id="keywords-container">
                            <!-- Keywords will be loaded here -->
                        </div>
                    </div>
                    
                    <div class="card mb-4" data-aos="fade-left" data-aos-delay="400">
                        <div class="card-header">
                            <h5 class="mb-0 text-white">Files & Resources</h5>
                        </div>
                        <div class="card-body" id="resources-container">
                            <!-- Resources will be loaded here -->
                        </div>
                    </div>
                    
                    <div class="card mb-4" data-aos="fade-left" data-aos-delay="500">
                        <div class="card-header">
                            <h5 class="mb-0 text-white">External Links</h5>
                        </div>
                        <div class="card-body" id="links-container">
                            <!-- Links will be loaded here -->
                        </div>
                    </div>
                    
                    <div class="card mb-4" data-aos="fade-left" data-aos-delay="600">
                        <div class="card-header">
                            <h5 class="mb-0 text-white">Project Stats</h5>
                        </div>
                        <div class="card-body" id="stats-container">
                            <!-- Stats will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'src/includes/footer.php'; ?>

    <!-- Add lightbox container -->
    <div class="lightbox" id="lightbox">
        <div class="lightbox-content">
            <img src="" alt="" class="lightbox-image" id="lightbox-image">
            <div class="lightbox-caption" id="lightbox-caption"></div>
        </div>
        <div class="lightbox-close" id="lightbox-close">
            <i class="bi bi-x-lg"></i>
        </div>
        <div class="lightbox-navigation">
            <div class="lightbox-nav-btn" id="lightbox-prev">
                <i class="bi bi-chevron-left"></i>
            </div>
            <div class="lightbox-nav-btn" id="lightbox-next">
                <i class="bi bi-chevron-right"></i>
            </div>
        </div>
    </div>

    <!-- Include Timeline Editor Overlay -->
    <?php include 'timeline_editor_overlay.php'; ?>
    
    <!-- Meeting Creation Modal -->
    <div class="modal fade" id="meetingModal" tabindex="-1" aria-labelledby="meetingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="meetingModalLabel">
                        <i class="bi bi-calendar-plus me-2"></i>Schedule Meeting
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Meeting Form -->
                    <form id="meetingForm">
                        <!-- Loading indicator for suggestions -->
                        <div id="timeSuggestionsLoading" class="loading-state" style="display: none;">
                            <div class="d-flex align-items-center justify-content-center py-4">
                                <div class="spinner-border spinner-border-sm text-primary me-3" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <div>
                                    <p class="mb-1 fw-medium">Analyzing Team Schedules</p>
                                    <small class="text-muted">Finding optimal meeting times...</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- No Date Selected Message -->
                        <div id="noDateSelected" class="empty-state">
                            <div class="text-center py-4">
                                <div class="empty-icon mb-3">
                                    <i class="bi bi-calendar-date text-muted"></i>
                                </div>
                                <h6 class="text-muted mb-2">Select a Date First</h6>
                                <p class="text-muted small mb-0">Choose a meeting date from the calendar to see available time slots</p>
                            </div>
                        </div>
                        
                        <!-- Three Column Layout -->
                        <div class="row g-4">
                            <!-- Left Column: Meeting Details & Calendar -->
                                        <div class="col-lg-4">
                                            <div class="meeting-details-section">
                                                <div class="section-header mb-3">
                                                    <h6 class="text-primary mb-1">
                                                        <i class="bi bi-calendar3 me-2"></i>Meeting Details
                                </h6>
                                                    <small class="text-muted">Set up your team meeting</small>
                                                </div>
                                
                                                <!-- Meeting Form Fields -->
                                                <div class="meeting-form-fields">
                                <div class="mb-3">
                                                        <label for="meetingTitle" class="form-label fw-medium">Meeting Title</label>
                                                        <input type="text" class="form-control" id="meetingTitle" placeholder="Enter meeting title">
                                </div>
                                
                                <div class="mb-3">
                                                        <label for="meetingDescription" class="form-label fw-medium">Description</label>
                                                        <textarea class="form-control" id="meetingDescription" rows="3" placeholder="Meeting agenda or description"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                                        <label for="meetingLink" class="form-label fw-medium">Meeting Link</label>
                                                        <input type="url" class="form-control" id="meetingLink" placeholder="Zoom, Google Meet, etc.">
                                                        <div class="mt-2">
                                                            <button type="button" id="createGoogleMeetBtn" class="btn btn-outline-primary btn-sm w-100">
                                                                <i class="bi bi-camera-video me-2"></i>Create Google Meet Link
                                                            </button>
                                                            <button type="button" id="testMeetingBtn" class="btn btn-outline-secondary btn-sm w-100 mt-2">
                                                                <i class="bi bi-play-circle me-2"></i>Test Meeting (Current Time)
                                                            </button>
                                                        </div>
                                </div>
                                
                                <!-- Manual Time Selection for Testing -->
                                <div class="mb-3">
                                    <label class="form-label fw-medium text-warning">
                                        <i class="bi bi-tools me-2"></i>Manual Time Selection (Testing)
                                    </label>
                                    <div class="border border-warning rounded p-3" style="background-color: #fff8e1;">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <label for="manualStartTime" class="form-label">Start Time</label>
                                                <input type="time" class="form-control form-control-sm" id="manualStartTime">
                                            </div>
                                            <div class="col-6">
                                                <label for="manualEndTime" class="form-label">End Time</label>
                                                <input type="time" class="form-control form-control-sm" id="manualEndTime">
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-warning btn-sm w-100" onclick="setManualTime()">
                                                <i class="bi bi-clock me-2"></i>Set Manual Time
                                            </button>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            <i class="bi bi-info-circle me-1"></i>
                                            For testing: Bypass suggestions and set exact time
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                                        <label for="meetingDate" class="form-label fw-medium">Select Date</label>
                                                        <input type="date" class="form-control" id="meetingDate">
                                                    </div>
                                                    
                                                    <!-- Calendar Widget -->
                                                    <div class="mb-3">
                                                        <label class="form-label fw-medium">Calendar View</label>
                                    <div id="meetingCalendar" class="custom-calendar">
                                        <!-- Calendar will be rendered here -->
                                    </div>
                                                    </div>
                                                </div>
                                </div>
                            </div>
                            
                                        <!-- Middle Column: Time Suggestions -->
                                        <div class="col-lg-4">
                                            <div id="timeSuggestionsContainer" class="suggestions-container" style="display: none;">
                                                <div class="recommendations-section">
                                                    <div class="section-header mb-3">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <h6 class="text-success mb-0">
                                                                <i class="bi bi-lightbulb-fill me-2"></i>Time Suggestions
                                                            </h6>
                                                            <button type="button" class="btn btn-outline-success btn-sm" onclick="refreshTimeSuggestions()" title="Recalculate suggestions">
                                                                <i class="bi bi-arrow-clockwise"></i>
                                                            </button>
                                                        </div>
                                                        
                                                        <!-- Time Filter Buttons -->
                                                        <div class="mb-2">
                                                            <div class="btn-group btn-group-sm" role="group" aria-label="Time filter">
                                                                <button type="button" class="btn btn-outline-secondary active" id="filterAll" onclick="filterSuggestions('all')">
                                                                    All
                                                                </button>
                                                                <button type="button" class="btn btn-outline-secondary" id="filterAM" onclick="filterSuggestions('am')">
                                                                    AM
                                                                </button>
                                                                <button type="button" class="btn btn-outline-secondary" id="filterPM" onclick="filterSuggestions('pm')">
                                                                    PM
                                                                </button>
                                                            </div>
                                                        </div>
                                                        
                                                        <small class="text-muted">When everyone is free</small>
                                                        
                                                        <!-- Availability Status moved below heading -->
                                                <div id="availabilityResults" class="availability-status-inline mb-2" style="display: none;">
                                                    <!-- Availability results will be shown here -->
                                                </div>
                                </div>
                                
                                                    <!-- Time Slots Grid -->
                                                    <div id="timeSlots" class="enhanced-time-slots">
                                                        <!-- Suggested time slots will be shown here -->
                                    </div>
                                </div>
                                </div>
                            </div>
                                
                            <!-- Right Column: Team Availability Overview -->
                            <div class="col-lg-4">
                                <div id="memberSchedules" class="team-overview-sidebar" style="display: none;">
                                    <!-- Member schedules will be shown here -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hidden fields for selected time -->
                        <input type="hidden" id="meetingStartTime">
                        <input type="hidden" id="meetingEndTime">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="saveMeetingBtn" class="btn btn-primary">
                        <i class="bi bi-calendar-check me-1"></i>Schedule Meeting
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script src="assets/js/timeline_debug.js"></script>
    <script>
        // Initialize AOS animations with performance optimization
        document.addEventListener('DOMContentLoaded', function() {
            // Use performance optimizer for AOS if available
            if (window.performanceOptimizer) {
                window.performanceOptimizer.optimizeAOS();
            } else {
                // Fallback optimized AOS configuration
                const isMobile = window.innerWidth <= 768;
                
                AOS.init({
                    duration: isMobile ? 300 : 600,
                    easing: 'ease-out',
                    once: true, // Only animate once for better performance
                    mirror: false, // Disable mirror for better performance
                    anchorPlacement: 'top-bottom',
                    offset: 50,
                    disable: isMobile ? 'mobile' : false
                });
            }
        });
        
        // Add futuristic effects to the page
        document.addEventListener('DOMContentLoaded', function() {
            // Add glow effect class to cards and interactive elements
            const glowElements = document.querySelectorAll(
                '.project-header, .card, .abstract-box, .metadata-card, .timeline-content, .member-card, .btn-primary'
            );
            
            glowElements.forEach(el => {
                el.classList.add('glow-effect');
            });
            
            // Add text-link class to appropriate links
            document.querySelectorAll('.link-hover').forEach(link => {
                link.classList.add('text-link');
            });
            
            // Add futuristic border effect to media cards
            document.querySelectorAll('.media-card').forEach(card => {
                card.classList.add('futuristic-border');
            });
            
            // Add typing cursor effect to project title
            const projectTitle = document.querySelector('.project-header h1');
            if (projectTitle) {
                const cursor = document.createElement('span');
                cursor.className = 'typing-cursor';
                projectTitle.appendChild(cursor);
            }
            
            // Add data loading effect to stats
            document.querySelectorAll('.progress-bar').forEach(bar => {
                bar.classList.add('data-loading');
            });
            
            // Add text glow effect to important items
            document.querySelectorAll('.status-badge, .timeline-title, .member-name').forEach(item => {
                item.classList.add('text-glow');
            });
            
            // Add terminal effect to tech-related text
            document.querySelectorAll('.member-id span').forEach(item => {
                item.classList.add('terminal-text');
            });
        });

            // Get project ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const projectId = urlParams.get('id');
        
        // Handle edit timeline button click
        document.getElementById('editTimelineBtn').addEventListener('click', function() {
            // Open the timeline editor modal
            const timelineEditorModal = new bootstrap.Modal(document.getElementById('timelineEditorModal'));
            timelineEditorModal.show();
        });

        // Function to create ripple effect on buttons
        function createRipple(event) {
            const button = event.currentTarget;
            
            // Remove any existing ripple elements
            const ripples = button.getElementsByClassName("ripple");
            for (let i = 0; i < ripples.length; i++) {
                button.removeChild(ripples[i]);
            }
            
            const circle = document.createElement("span");
            const diameter = Math.max(button.clientWidth, button.clientHeight);
            const radius = diameter / 2;
            
            // Calculate position of ripple based on click coordinates
            circle.style.width = circle.style.height = `${diameter}px`;
            circle.style.left = `${event.clientX - button.getBoundingClientRect().left - radius}px`;
            circle.style.top = `${event.clientY - button.getBoundingClientRect().top - radius}px`;
            circle.classList.add("ripple");
            
            button.appendChild(circle);
        }
        
        // Apply ripple effect to buttons when DOM is fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // We already initialized AOS above, so removing this duplicate initialization
            /* 
            AOS.init({
                duration: 1000,
                easing: 'ease-in-out',
                once: true,
                mirror: false
            });
            */
            
            // Setup ripple effect for buttons
            const buttons = document.querySelectorAll('.custom-btn, .toggle-btn');
            buttons.forEach(button => {
                button.addEventListener('click', createRipple);
            });
            
            // Animate project title if available
            if (projectId) {
                animateProjectTitle();
            }
        });
        
        // Function to animate project title with shimmer effect
        function animateProjectTitle() {
            const projectTitle = document.querySelector('.project-title');
            if (projectTitle) {
                // Add shimmer class for the effect
                projectTitle.classList.add('shimmer-text');
                
                // Use GSAP to animate the title
                gsap.to(projectTitle, {
                    backgroundPosition: '200% center',
                    color: '#333333',
                    duration: 3,
                    ease: "power1.inOut",
                    repeat: -1,
                    yoyo: true
                });
            }
        }

        // Function to fetch and display project details
        document.addEventListener('DOMContentLoaded', function() {
            // We already initialized AOS above, so removing this duplicate initialization
            /* 
            AOS.init({
                duration: 1000,
                easing: 'ease-in-out',
                once: true,
                mirror: false
            });
            */

            // Setup ripple effect for buttons
            const buttons = document.querySelectorAll('.custom-btn, .toggle-btn');
            buttons.forEach(button => {
                button.addEventListener('click', createRipple);
            });

            // Function to create ripple effect on button click
            function createRipple(event) {
                const button = event.currentTarget;
                const circle = document.createElement('span');
                const diameter = Math.max(button.clientWidth, button.clientHeight);
                const radius = diameter / 2;

                circle.style.width = circle.style.height = `${diameter}px`;
                circle.style.left = `${event.clientX - button.getBoundingClientRect().left - radius}px`;
                circle.style.top = `${event.clientY - button.getBoundingClientRect().top - radius}px`;
                circle.classList.add('ripple');

                const ripple = button.querySelector('.ripple');
                if (ripple) {
                    ripple.remove();
                }

                button.appendChild(circle);
            }

            // Function to animate project title with shimmer effect
            function animateProjectTitle() {
                const projectTitle = document.querySelector('.project-title');
                if (!projectTitle) return;

                // Initial entrance animation
                gsap.fromTo(projectTitle, 
                    { opacity: 0, y: -20 }, 
                    { opacity: 1, y: 0, duration: 1, ease: "power2.out" }
                );

                // Create shimmer effect
                const shimmer = document.createElement('div');
                shimmer.classList.add('shimmer');
                projectTitle.appendChild(shimmer);

                // Continuous shimmer animation
                gsap.to(shimmer, {
                    x: "100%", 
                    duration: 2.5, 
                    repeat: -1, 
                    ease: "power1.inOut",
                    delay: 1
                });
            }

            // Add CSS for shimmer effect
            const style = document.createElement('style');
            style.textContent = `
                .project-title {
                    position: relative;
                    overflow: hidden;
                }
                .shimmer {
                    position: absolute;
                    top: 0;
                    left: -100%;
                    width: 50%;
                    height: 100%;
                    background: linear-gradient(
                        90deg, 
                        rgba(255,255,255,0) 0%, 
                        rgba(255,255,255,0.3) 50%, 
                        rgba(255,255,255,0) 100%
                    );
                    pointer-events: none;
                }
            `;
            document.head.appendChild(style);

            // Get the project ID from the URL
            const urlParams = new URLSearchParams(window.location.search);
            const projectId = urlParams.get('id');
            
            if (!projectId) {
                showProjectNotFound();
                return;
            }
            
            // Fetch project details
            fetch(`src/model/get_project.php?id=${projectId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success || data.error) {
                        showProjectNotFound();
                        return;
                    }
                    
                    const project = data.project;
                    
                    // Store project data globally for timeline editor
                    window.currentProject = project;
                    
                    // Hide loading spinner and show project details with nice fade effect
                    const loadingSpinner = document.getElementById('loading-spinner');
                    const projectDetails = document.getElementById('project-details');
                    
                    fadeOut(loadingSpinner, 400, function() {
                        fadeIn(projectDetails, 600);
                        
                        // Render project details
                        renderProjectHeader(project);
                        renderProjectAbstract(project);
                        renderProjectDescription(project);
                        renderProjectInfo(project);
                        renderTeamMembers(project);
                        renderKeywords(project);
                        renderResources(project);
                        renderExternalLinks(project);
                        renderTimeline(project);
                        renderReferences(project);
                        renderMedia(project);
                        renderStats(project);
                        
                        // Show Edit Timeline button only for project owners or admins
                        const editTimelineBtn = document.getElementById('editTimelineBtn');
                        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
                        const loggedInUserId = <?php echo isset($_SESSION['user_id']) ? "'" . $_SESSION['user_id'] . "'" : 'null'; ?>;
                        
                        // Check if the current user created this project or is a member
                        let canEditTimeline = false;
                        
                        if (isLoggedIn) {
                            // Project creator check
                            if (project.createdBy && (
                                (project.createdBy.$oid && project.createdBy.$oid === loggedInUserId) || 
                                project.createdBy === loggedInUserId
                            )) {
                                canEditTimeline = true;
                            }
                            
                            // Project member check (supervisor or team member)
                            if (!canEditTimeline && project.members && Array.isArray(project.members)) {
                                canEditTimeline = project.members.some(member => 
                                    member.userId && (
                                        (member.userId.$oid && member.userId.$oid === loggedInUserId) || 
                                        member.userId === loggedInUserId
                                    )
                                );
                            }
                            
                            // Supervisor check
                            if (!canEditTimeline && project.supervisor && project.supervisor.userId) {
                                if ((project.supervisor.userId.$oid && project.supervisor.userId.$oid === loggedInUserId) ||
                                    project.supervisor.userId === loggedInUserId) {
                                    canEditTimeline = true;
                                }
                            }
                        }
                        
                        editTimelineBtn.style.display = canEditTimeline ? 'inline-flex' : 'none';
                        
                        // Control visibility of Project Meetings section based on the same edit access
                        const projectMeetingsSection = document.getElementById('project-meetings-section');
                        if (projectMeetingsSection) {
                            projectMeetingsSection.style.display = canEditTimeline ? 'block' : 'none';
                        }
                        
                        // Show milestone status dropdowns for supervisors (with slight delay to ensure DOM is ready)
                        setTimeout(() => {
                            checkSupervisorAccess(project, loggedInUserId);
                        }, 100);
                        
                        // Add title animation effect
                        animateProjectTitle();
                        
                        // Handle video iframe errors
                        handleVideoPlaceholders();
                        
                        // Refresh AOS after content is loaded
                        setTimeout(() => {
                            AOS.refresh();
                        }, 500);
                    });
                })
                .catch(error => {
                    console.error('Error fetching project:', error);
                    showProjectNotFound();
                });
        });
        
        // Function to handle video iframe loading errors
        function handleVideoPlaceholders() {
            const placeholderImage = 'assets/images/Research_Card_Placeholder.png';
            const iframes = document.querySelectorAll('.ratio iframe');
            
            iframes.forEach(iframe => {
                // Check if iframe is loaded correctly
                iframe.addEventListener('error', function() {
                    this.style.display = 'none';
                    this.parentNode.classList.add('placeholder-bg');
                });
                
                // Also try to detect if iframe src is invalid or doesn't load
                setTimeout(() => {
                    try {
                        if (!iframe.contentWindow || iframe.contentWindow.document.body.innerHTML === '') {
                            iframe.style.display = 'none';
                            iframe.parentNode.classList.add('placeholder-bg');
                        }
                    } catch (e) {
                        // If we can't access the iframe (cross-origin), assume it's working
                        // This is a limitation, but at least we tried
                    }
                }, 1000);
            });
        }
        
        // Custom fade functions for smoother transitions
        function fadeOut(element, duration, callback) {
            if (!element) return;
            
            element.style.opacity = 1;
            element.style.transition = `opacity ${duration}ms ease`;
            
            setTimeout(() => {
                element.style.opacity = 0;
                
                setTimeout(() => {
                    element.style.display = 'none';
                    if (typeof callback === 'function') callback();
                }, duration);
            }, 10);
        }
        
        function fadeIn(element, duration, callback) {
            if (!element) return;
            
            element.style.opacity = 0;
            element.style.display = 'block';
            element.style.transition = `opacity ${duration}ms ease`;
            
            setTimeout(() => {
                element.style.opacity = 1;
                
                setTimeout(() => {
                    if (typeof callback === 'function') callback();
                }, duration);
            }, 10);
        }
        
        function showProjectNotFound() {
            const loadingSpinner = document.getElementById('loading-spinner');
            const projectNotFound = document.getElementById('project-not-found');
            
            fadeOut(loadingSpinner, 400, function() {
                fadeIn(projectNotFound, 600);
            });
        }
        
        // Helper function to create clickable profile links
        async function createProfileLink(name, userId, userType = null) {
            if (!name) return 'Unknown';
            
            // Primary method: Use userId if available
            if (userId) {
                try {
                    const response = await fetch('src/model/check_profile_exists.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ userId: userId, userType: userType })
                    });
                    
                    if (response.ok) {
                        const result = await response.json();
                        if (result.exists) {
                            const profileType = result.type === 'faculty' ? 'Faculty_Profile.php' : 'Student_Profile.php';
                            return `<a href="${profileType}?id=${userId}" class="profile-link ${result.type === 'faculty' ? 'supervisor-link' : 'member-link'}" title="View ${result.type} profile">${name}</a>`;
                        }
                    }
                } catch (error) {
                    console.log('Profile check failed for user:', userId);
                }
            }
            
            // Fallback method: Try to find supervisor by name in global faculty data
            if (userType === 'faculty' || !userType) {
                try {
                    // Check if global faculty data is available
                    if (window.facultyData && Array.isArray(window.facultyData)) {
                        const matchingFaculty = window.facultyData.find(faculty => 
                            faculty.name && faculty.name.toLowerCase().trim() === name.toLowerCase().trim()
                        );
                        
                        if (matchingFaculty && matchingFaculty._id) {
                            console.log(`Found faculty by name: ${name} -> ${matchingFaculty._id}`);
                            return `<a href="Faculty_Profile.php?id=${matchingFaculty._id}" class="profile-link supervisor-link" title="View faculty profile">${name}</a>`;
                        }
                    }
                    
                    // If global faculty data is not available, try to fetch it
                    if (!window.facultyData) {
                        const facultyResponse = await fetch('src/model/load_faculty.php');
                        if (facultyResponse.ok) {
                            const facultyData = await facultyResponse.json();
                            window.facultyData = facultyData; // Cache for future use
                            
                            const matchingFaculty = facultyData.find(faculty => 
                                faculty.name && faculty.name.toLowerCase().trim() === name.toLowerCase().trim()
                            );
                            
                            if (matchingFaculty && matchingFaculty._id) {
                                console.log(`Found faculty by name (from fetch): ${name} -> ${matchingFaculty._id}`);
                                return `<a href="Faculty_Profile.php?id=${matchingFaculty._id}" class="profile-link supervisor-link" title="View faculty profile">${name}</a>`;
                            }
                        }
                    }
                } catch (error) {
                    console.log('Faculty name lookup failed:', error);
                }
            }
            
            // Return original name if no profile found
            return name;
        }
        
        function renderProjectHeader(project) {
            const headerEl = document.getElementById('project-header');
            const isPublic = project.privacy === 0;
            
            // Create supervisor info with potential link
            let supervisorInfo = '';
            if (project.supervisor) {
                const supervisorName = project.supervisor.name || (typeof project.supervisor === 'string' ? project.supervisor : (project.supervisor.$oid || 'Unknown'));
                const supervisorId = project.supervisor.userId ? (project.supervisor.userId.$oid || project.supervisor.userId) : null;
                
                // Initially show supervisor name (will be updated to clickable link if profile exists)
                supervisorInfo = `<div class="meta-item"><i class="bi bi-person-badge"></i><strong>Supervisor:</strong> <span class="supervisor-display">${supervisorName}</span></div>`;
                
                // Try to make supervisor clickable (with or without userId)
                createProfileLink(supervisorName, supervisorId, 'faculty').then(linkedName => {
                    // Update all supervisor displays with clickable link
                    document.querySelectorAll('.supervisor-display').forEach(el => {
                        if (el.textContent.trim() === supervisorName) {
                            el.innerHTML = linkedName;
                        }
                    });
                }).catch(error => {
                    console.log('Failed to create supervisor profile link:', error);
                    // Keep the original name if profile check fails
                });
            }
            
            // Check if the current user is part of the project team (member or supervisor)
            // We need to fetch the current logged-in user information from PHP session
            let isAuthorized = false;
            let currentUser = null;
            
            // Fetch the current user ID from a PHP variable injected into the page
            if (typeof currentUserId !== 'undefined') {
                currentUser = currentUserId;
            }
            
            // Check if the current user is authorized to edit the project
            if (currentUser) {
                // Check if user is in members list (team members)
                if (project.members && project.members.length > 0) {
                    isAuthorized = project.members.some(member => 
                        (member.userId && member.userId.$oid === currentUser) || 
                        (member.userId === currentUser)
                    );
                }
                
                // Enhanced supervisor authorization check - comprehensive patterns
                if (!isAuthorized && project.supervisor) {
                    // Check various supervisor data formats
                    
                    // Format 1: supervisor.userId with ObjectId
                    if (project.supervisor.userId && project.supervisor.userId.$oid) {
                        isAuthorized = (project.supervisor.userId.$oid === currentUser);
                    }
                    
                    // Format 2: supervisor.userId as string
                    if (!isAuthorized && project.supervisor.userId) {
                        isAuthorized = (project.supervisor.userId === currentUser);
                    }
                    
                    // Format 3: Direct supervisor field (for simple projects)
                    if (!isAuthorized && typeof project.supervisor === 'string') {
                        isAuthorized = (project.supervisor === currentUser);
                    }
                    
                    // Format 4: supervisor.$oid (direct ObjectId)
                    if (!isAuthorized && project.supervisor.$oid) {
                        isAuthorized = (project.supervisor.$oid === currentUser);
                    }
                    
                    // Format 5: Name-based matching for faculty users
                    if (!isAuthorized && currentUserType === 'faculty' && currentUserName && project.supervisor.name) {
                        isAuthorized = (project.supervisor.name === currentUserName);
                    }
                    
                    // Debug logging for troubleshooting
                    console.log('Supervisor check debug:', {
                        currentUser: currentUser,
                        currentUserName: currentUserName,
                        currentUserType: currentUserType,
                        supervisor: project.supervisor,
                        isAuthorized: isAuthorized
                    });
                }
                
                // Check if user is the project creator
                if (!isAuthorized && project.createdBy) {
                    if (typeof project.createdBy === 'object' && project.createdBy.$oid) {
                        isAuthorized = (project.createdBy.$oid === currentUser);
                    } else if (typeof project.createdBy === 'string') {
                        isAuthorized = (project.createdBy === currentUser);
                    }
                }
            }
            
            // Only show edit and leave buttons if user is a team member or supervisor
            const editBtn = isAuthorized ? `
                <button id="editProjectBtn" class="btn btn-outline-primary ms-2" data-project-id="${project._id.$oid}">
                    <i class="bi bi-pencil-square"></i>Edit Project
                </button>
            ` : '';

            const leaveBtn = isAuthorized ? `
                <button id="leaveProjectBtn" class="btn btn-outline-danger ms-2" data-project-id="${project._id.$oid}" data-project-name="${project.title}" title="Leave this project and group chat">
                    <i class="bi bi-box-arrow-left"></i>Leave Project
                </button>
            ` : '';
            
            headerEl.innerHTML = `
                <div class="d-flex justify-content-between align-items-start position-relative z-1">
                    <div>
                        <h1 class="float-animation display-4">${project.title}</h1>
                        <div class="mb-3 d-flex align-items-center mt-3">
                            <button id="literatureMatrixBtn" class="btn btn-outline-primary me-2" data-project-id="${project._id.$oid}">
                                <i class="bi bi-journal-text"></i> Literature Matrix
                            </button>
                            <button id="paperBtn" class="btn btn-outline-success me-2" data-project-id="${project._id.$oid}">
                                <i class="bi bi-file-earmark-text"></i> Paper
                            </button>
                            ${editBtn}
                            ${leaveBtn}
                        </div>
                    </div>
                </div>
                
                <div class="divider"></div>
                
                <div class="row section-row">
                    <div class="col-md-6">
                        <div class="meta-item">
                            <i class="bi bi-mortarboard-fill"></i>
                            <div><strong>Field:</strong> ${project.field || 'Not specified'}</div>
                    </div>
                        <div class="meta-item">
                            <i class="bi bi-building"></i>
                            <div><strong>Institution:</strong> ${project.institution || 'United International University'}</div>
                        </div>
                        ${supervisorInfo}
                    </div>
                    <div class="col-md-6">
                        <div class="meta-item">
                            <i class="bi bi-calendar-plus"></i>
                            <div><strong>Created:</strong> ${formatDate(project.createdAt)}</div>
                        </div>
                        <div class="meta-item">
                            <i class="bi bi-calendar-check"></i>
                            <div><strong>Last Updated:</strong> ${formatDate(project.updatedAt)}</div>
                        </div>
                        <div class="meta-item">
                            <i class="bi bi-calendar-event"></i>
                            <div><strong>Estimated Completion:</strong> ${project.estimatedCompletionDate ? formatDate(project.estimatedCompletionDate) : 'Not specified'}</div>
                        </div>
                        <div class="meta-item">
                            <i class="bi bi-eye"></i>
                            <div><strong>Views:</strong> ${project.stats?.views || '0'}</div>
                        </div>
                    </div>
                </div>
            `;
            
            // Add event listener for the edit button
            const editButton = document.getElementById('editProjectBtn');
            if (editButton) {
                editButton.addEventListener('click', function() {
                    const projectId = this.getAttribute('data-project-id');
                    window.location.href = `edit_project.php?id=${projectId}`;
                });
                
                // Add ripple effect to the button
                editButton.addEventListener('mousedown', createRipple);
            }

            // Add event listener for the leave button
            const leaveButton = document.getElementById('leaveProjectBtn');
            if (leaveButton) {
                leaveButton.addEventListener('click', function() {
                    const projectId = this.getAttribute('data-project-id');
                    const projectName = this.getAttribute('data-project-name');
                    
                    // Show confirmation dialog
                    const confirmMessage = `Are you sure you want to leave "${projectName}"?\n\nThis will:\n• Remove you from the project team\n• Remove you from the group chat\n• You will lose access to all project discussions\n\nThis action cannot be undone.`;
                    
                    if (!confirm(confirmMessage)) {
                        return;
                    }
                    
                    // Disable the button to prevent multiple clicks
                    this.disabled = true;
                    const originalContent = this.innerHTML;
                    this.innerHTML = '<i class="bi bi-hourglass-split"></i>Leaving...';
                    
                    // Send leave request to server
                    const formData = new FormData();
                    formData.append('projectId', projectId);
                    
                    fetch('src/model/leave_project.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message
                            if (typeof showToast === 'function') {
                                showToast('success', 'Left Project', data.message || 'You have successfully left the project.');
                            } else {
                                alert('You have successfully left the project.');
                            }
                            
                            // Redirect to projects page after a short delay
                            setTimeout(() => {
                                window.location.href = 'Research_page.php';
                            }, 1500);
                            
                        } else {
                            // Show error message
                            if (typeof showToast === 'function') {
                                showToast('error', 'Failed to Leave', data.message || 'Failed to leave the project. Please try again.');
                            } else {
                                alert(data.message || 'Failed to leave the project. Please try again.');
                            }
                            
                            // Re-enable the button
                            this.disabled = false;
                            this.innerHTML = originalContent;
                        }
                    })
                    .catch(error => {
                        console.error('Error leaving project:', error);
                        
                        // Show error message
                        if (typeof showToast === 'function') {
                            showToast('error', 'Error', 'An error occurred while trying to leave the project. Please try again.');
                        } else {
                            alert('An error occurred while trying to leave the project. Please try again.');
                        }
                        
                        // Re-enable the button
                        this.disabled = false;
                        this.innerHTML = originalContent;
                    });
                });
                
                // Add ripple effect to the button
                leaveButton.addEventListener('mousedown', createRipple);
            }
            
            // Add ripple effect to the timeline edit button
            const timelineEditBtn = document.getElementById('editTimelineBtn');
            if (timelineEditBtn) {
                timelineEditBtn.addEventListener('mousedown', createRipple);
            }
            
            // Update page title
            document.title = `${project.title} | UIU Research Platform`;
            
            // Add animation for the title to make it stand out
            animateProjectTitle();
            
            // Add event listeners for Literature Matrix button
            const litMatrixButton = document.getElementById('literatureMatrixBtn');
            if (litMatrixButton) {
                litMatrixButton.addEventListener('click', function() {
                    const projectId = this.getAttribute('data-project-id');
                    window.location.href = `literature_matrix.php?id=${projectId}`;
                });
                litMatrixButton.addEventListener('mousedown', createRipple);
            }

            // Add event listeners for Paper button
            const paperButton = document.getElementById('paperBtn');
            if (paperButton) {
                paperButton.addEventListener('click', async function() {
                    const projectId = this.getAttribute('data-project-id');
                    try {
                        const response = await fetch('src/model/create_etherpad.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                projectId: projectId
                            })
                        });

                        const data = await response.json();
                        if (data.success) {
                            // Create modal for Etherpad
                            const modalHtml = `
                                <div class="modal fade" id="etherpadModal" tabindex="-1" aria-labelledby="etherpadModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-xl modal-fullscreen-lg-down">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="etherpadModalLabel">Collaborative Paper Writing</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-0">
                                                <iframe src="${data.embedUrl}" style="width: 100%; height: 80vh; border: none;"></iframe>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            // Add modal to body if it doesn't exist
                            if (!document.getElementById('etherpadModal')) {
                                document.body.insertAdjacentHTML('beforeend', modalHtml);
                            }
                            
                            // Show the modal
                            const modal = new bootstrap.Modal(document.getElementById('etherpadModal'));
                            modal.show();
                        } else {
                            throw new Error(data.message || 'Failed to create paper pad');
                        }
                    } catch (error) {
                        console.error('Error opening paper pad:', error);
                        alert('Failed to open paper pad. Please try again.');
                    }
                });
                paperButton.addEventListener('mousedown', createRipple);
            }
        }
        
        function renderProjectAbstract(project) {
            if (project.abstract) {
                const abstractEl = document.getElementById('project-abstract');
                
                // Wrap important keywords with highlight span
                // This makes key terms stand out in the abstract
                let enhancedAbstract = project.abstract;
                
                // Get keywords from project if available
                if (project.keywords && project.keywords.length > 0) {
                    // Sort keywords by length (longest first) to avoid partial replacements
                    const sortedKeywords = [...project.keywords].sort((a, b) => b.length - a.length);
                    
                    // Replace keywords with highlighted versions, but only the first occurrence
                    sortedKeywords.forEach(keyword => {
                        // Use case-insensitive regex but preserve original case in replacement
                        const regex = new RegExp(`(${keyword})`, 'i');
                        // Only replace if found and not already highlighted
                        if (regex.test(enhancedAbstract) && !enhancedAbstract.includes(`<span class="abstract-highlight">${keyword}</span>`)) {
                            enhancedAbstract = enhancedAbstract.replace(regex, `<span class="abstract-highlight">$1</span>`);
                        }
                    });
                }
                
                abstractEl.innerHTML = `
                    <h3 class="section-title">Abstract</h3>
                    <p>${enhancedAbstract}</p>
                    <div class="d-flex justify-content-end mt-3">
                        <small class="fst-italic" style="color: #ffffff !important;">Last updated: ${formatDate(project.updatedAt)}</small>
                    </div>
                `;
                
                // Ensure the abstract is visible before animation
                const abstractText = abstractEl.querySelector('p');
                if (abstractText) {
                    abstractText.style.opacity = 1;
                    
                    // Simpler, more reliable animation
                    setTimeout(() => {
                        abstractText.classList.add('animated');
                    }, 500);
                }
            }
        }
        
        function renderProjectDescription(project) {
            if (project.description) {
                const descriptionEl = document.getElementById('project-description');
                
                // Enhance description with keyword highlighting
                let enhancedDescription = project.description;
                
                // Get keywords from project if available
                if (project.keywords && project.keywords.length > 0) {
                    // Sort keywords by length (longest first) to avoid partial replacements
                    const sortedKeywords = [...project.keywords].sort((a, b) => b.length - a.length);
                    
                    // Replace keywords with highlighted versions
                    sortedKeywords.forEach(keyword => {
                        // Use case-insensitive regex but preserve original case in replacement
                        const regex = new RegExp(`(${keyword})`, 'gi');
                        enhancedDescription = enhancedDescription.replace(regex, 
                            `<span class="description-highlight">$1</span>`);
                    });
                }
                
                // Format content - identify and format sections based on common patterns
                // This tries to identify sections in the description and format them
                let formattedDescription = enhancedDescription;
                
                // Try to identify sections by looking for patterns like "Objectives:" or "Methodology:"
                const sectionRegex = /(?:<br>|<p>|^)([\w\s]+):\s*(?=<br>|<p>|$)/g;
                formattedDescription = formattedDescription.replace(sectionRegex, 
                    `<div class="description-section">
                        <div class="description-section-title">
                            <i class="bi bi-bookmark-fill"></i>$1
                        </div>`);
                
                // Close any opened section divs
                if (formattedDescription.includes('description-section-title')) {
                    formattedDescription += '</div>';
                }
                
                // Wrap content in a description box
                descriptionEl.innerHTML = `
                    <div class="description-box">
                        <h3 class="section-title">Description</h3>
                        <div>${formattedDescription}</div>
                        <div class="d-flex justify-content-end mt-3">
                            <small class="fst-italic" style="color: #ffffff !important;">Last updated: ${formatDate(project.updatedAt)}</small>
                        </div>
                    </div>
                `;
                
                // Ensure the description is visible
                const descriptionContent = descriptionEl.querySelector('.description-box > div');
                if (descriptionContent) {
                    descriptionContent.style.opacity = 1;
                    
                    // Apply subtle entrance animation
                    setTimeout(() => {
                        descriptionContent.classList.add('animated');
                    }, 700);
                }
            }
        }
        
        function renderProjectInfo(project) {
            const infoEl = document.getElementById('project-info');
            
            // Format the created and updated dates
            const createdDate = formatDate(project.createdAt);
            const updatedDate = formatDate(project.updatedAt);
            
            // Generate supervisor info if available
            let supervisorInfo = '';
            if (project.supervisor) {
                const supervisorName = project.supervisor.name || (typeof project.supervisor === 'string' ? project.supervisor : (project.supervisor.$oid || 'Unknown'));
                const supervisorId = project.supervisor.userId ? (project.supervisor.userId.$oid || project.supervisor.userId) : null;
                
                if (supervisorId) {
                    // Create clickable supervisor link
                    createProfileLink(supervisorName, supervisorId, 'faculty').then(linkedName => {
                        const supervisorInfoEl = document.querySelector('.supervisor-info-display');
                        if (supervisorInfoEl) {
                            supervisorInfoEl.innerHTML = linkedName;
                        }
                    });
                }
                
                supervisorInfo = `<p><i class="bi bi-person-badge me-2"></i><strong>Supervisor:</strong> <span class="supervisor-info-display">${supervisorName}</span></p>`;
            }
            
            infoEl.innerHTML = `
                <p><i class="bi bi-mortarboard-fill me-2"></i><strong>Field:</strong> ${project.field || 'Not specified'}</p>
                <p><i class="bi bi-building me-2"></i><strong>Institution:</strong> ${project.institution || 'United International University'}</p>
                ${supervisorInfo}
                <p><i class="bi bi-calendar-plus me-2"></i><strong>Created:</strong> ${createdDate}</p>
                <p><i class="bi bi-calendar-check me-2"></i><strong>Last Updated:</strong> ${updatedDate}</p>
                <p><i class="bi bi-shield-lock me-2"></i><strong>Status:</strong> ${project.privacy === 0 ? 'Public' : 'Private'}</p>
            `;
        }
        
        function renderTeamMembers(project) {
            const membersEl = document.getElementById('team-members');
            
            if (!project.members || project.members.length === 0) {
                membersEl.innerHTML = '<p style="color: var(--text-secondary);">No team members listed</p>';
                return;
            }
            
            let membersHTML = '';
            
            // Sort members by contribution percentage (if available)
            const sortedMembers = [...project.members].sort((a, b) => 
                (b.contribution || 0) - (a.contribution || 0)
            );
            
            sortedMembers.forEach((member, index) => {
                const name = member.name || 'Unnamed Member';
                const role = member.role || 'Team Member';
                const memberId = member.userId ? (member.userId.$oid || member.userId) : null;
                
                // Get contribution level label and icon
                let contributionLabel = '';
                let roleIcon = 'bi-person';
                
                if (member.contribution) {
                    if (member.contribution >= 70) {
                        contributionLabel = 'Lead Contributor';
                        roleIcon = 'bi-star-fill';
                    }
                    else if (member.contribution >= 40) {
                        contributionLabel = 'Major Contributor';
                        roleIcon = 'bi-star-half';
                    }
                    else if (member.contribution >= 20) {
                        contributionLabel = 'Contributor';
                        roleIcon = 'bi-star';
                    }
                    else {
                        contributionLabel = 'Supporting Member';
                        roleIcon = 'bi-person-check';
                    }
                }
                
                // Create progress bar with label
                const contribution = member.contribution ? `
                    <div class="contribution-section">
                        <div class="progress-label">
                            <div class="contribution-text">Contribution <strong>${member.contribution}%</strong></div>
                            <div class="progress-percentage">
                                ${contributionLabel ? `<span class="contribution-level">${contributionLabel}</span>` : ''}
                            </div>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: 0%" 
                            aria-valuenow="${member.contribution}" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                </div>` : '';
                
                membersHTML += `
                    <div class="member-card" data-contribution="${member.contribution || 0}" data-aos="fade-up">
                        <div class="member-name">
                            <i class="bi bi-person-circle me-2"></i><span class="member-name-display" data-member-id="${memberId || ''}">${name}</span>
                        </div>
                        <div class="member-role">
                            <i class="bi ${roleIcon} me-2"></i>${role}
                        </div>
                        ${contribution}
                    </div>
                `;
                
                // Check if member has a profile and make it clickable
                if (memberId) {
                    setTimeout(() => {
                        createProfileLink(name, memberId, 'student').then(linkedName => {
                            const memberNameEl = document.querySelector(`[data-member-id="${memberId}"]`);
                            if (memberNameEl) {
                                memberNameEl.innerHTML = linkedName;
                            }
                        });
                    }, index * 100); // Stagger the profile checks
                }
            });
            
            membersEl.innerHTML = membersHTML;
            
            // Animate progress bars with a delay
            setTimeout(() => {
                const progressBars = document.querySelectorAll('.progress-bar');
                progressBars.forEach(bar => {
                    const contribution = bar.getAttribute('aria-valuenow');
                    bar.style.width = `${contribution}%`;
                });
            }, 500);
        }
        
        function renderKeywords(project) {
            const keywordsEl = document.getElementById('keywords-container');
            
            if (!project.keywords || project.keywords.length === 0) {
                keywordsEl.innerHTML = '<p style="color: var(--text-secondary);">No keywords listed</p>';
                return;
            }
            
            let keywordsHTML = '<div>';
            
            project.keywords.forEach(keyword => {
                keywordsHTML += `<span class="badge badge-custom">${keyword}</span> `;
            });
            
            keywordsHTML += '</div>';
            keywordsEl.innerHTML = keywordsHTML;
        }
        
        function renderResources(project) {
            const resourcesEl = document.getElementById('resources-container');
            
            if (!project.files || project.files.length === 0) {
                resourcesEl.innerHTML = '<p style="color: var(--text-secondary);">No files available</p>';
                return;
            }
            
            let resourcesHTML = '<ul class="list-group list-group-flush">';
            
            project.files.forEach(file => {
                const icon = getFileIcon(file.type);
                const size = formatFileSize(file.size);
                const date = formatDate(file.uploadedAt);
                
                // Build secure download URL
                const filePath = file.path.replace(/^\//, ''); // Remove leading slash
                const downloadUrl = '/download.php?file=' + encodeURIComponent(filePath);
                
                resourcesHTML += `
                    <li class="list-group-item">
                        <div class="file-info">
                            <div class="file-name">
                                <i class="${icon} me-2"></i> ${file.name}
                            </div>
                            <small class="file-details">${size} - Uploaded on ${date}</small>
                        </div>
                        <a href="${downloadUrl}" class="btn btn-sm download-btn">
                            <i class="bi bi-download"></i>
                            <span class="btn-text">Download</span>
                        </a>
                    </li>
                `;
            });
            
            resourcesHTML += '</ul>';
            resourcesEl.innerHTML = resourcesHTML;
        }
        
        function renderExternalLinks(project) {
            const linksEl = document.getElementById('links-container');
            
            if (!project.links || Object.keys(project.links).length === 0) {
                linksEl.innerHTML = '<p style="color: var(--text-secondary);">No external links available</p>';
                return;
            }
            
            let linksHTML = '<ul class="list-group list-group-flush">';
            
            if (project.links.github) {
                linksHTML += `
                    <li class="list-group-item">
                        <i class="bi bi-github me-2"></i>
                        <a href="${project.links.github}" target="_blank" rel="noopener" class="link-hover">GitHub Repository</a>
                    </li>
                `;
            }
            
            if (project.links.paper) {
                linksHTML += `
                    <li class="list-group-item">
                        <i class="bi bi-file-text me-2"></i>
                        <a href="${project.links.paper}" target="_blank" rel="noopener" class="link-hover">Research Paper</a>
                    </li>
                `;
            }
            
            if (project.links.doi) {
                linksHTML += `
                    <li class="list-group-item">
                        <i class="bi bi-diagram-3 me-2"></i>
                        <a href="${project.links.doi}" target="_blank" rel="noopener" class="link-hover">DOI Reference</a>
                    </li>
                `;
            }
            
            if (project.links.youtube) {
                linksHTML += `
                    <li class="list-group-item">
                        <i class="bi bi-youtube me-2"></i>
                        <a href="${project.links.youtube}" target="_blank" rel="noopener" class="link-hover">YouTube Video</a>
                    </li>
                `;
            }
            
            if (project.links.website) {
                linksHTML += `
                    <li class="list-group-item">
                        <i class="bi bi-globe me-2"></i>
                        <a href="${project.links.website}" target="_blank" rel="noopener" class="link-hover">Project Website</a>
                    </li>
                `;
            }
            
            linksHTML += '</ul>';
            linksEl.innerHTML = linksHTML;
        }
        
        function renderTimeline(project) {
            const timelineEl = document.getElementById('timeline-container');
            
            if (!project.timeline || project.timeline.length === 0) {
                // Show a polished empty state instead of hiding
                timelineEl.innerHTML = `
                    <div class="timeline-empty-state">
                        <div class="empty-state-icon">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <h4>No Timeline Available</h4>
                        <p>This project doesn't have a timeline yet. Check back later for updates!</p>
                    </div>
                `;
                return;
            }
            
            // Sort timeline items by date
            const sortedTimeline = [...project.timeline].sort((a, b) => {
                let dateA, dateB;
                try {
                    dateA = a.date ? new Date(typeof a.date === 'object' && a.date.$date ? a.date.$date : a.date).getTime() : 0;
                } catch (e) { dateA = 0; }
                try {
                    dateB = b.date ? new Date(typeof b.date === 'object' && b.date.$date ? b.date.$date : b.date).getTime() : 0;
                } catch (e) { dateB = 0; }
                return dateA - dateB;
            });
            
            // Calculate progress statistics
            const completedItems = sortedTimeline.filter(item => 
                item.status && item.status.toLowerCase().includes('completed')).length;
            const totalItems = sortedTimeline.length;
            const progressPercentage = totalItems > 0 ? Math.round((completedItems / totalItems) * 100) : 0;
            
            // Create the modern timeline HTML
            let timelineHTML = `
                <!-- Enhanced Progress Header -->
                <div class="timeline-progress-header">
                    <div class="progress-stats">
                        <div class="progress-circle" data-progress="${progressPercentage}">
                            <svg class="progress-ring" width="60" height="60">
                                <circle cx="30" cy="30" r="25" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="3"/>
                                <circle cx="30" cy="30" r="25" fill="none" stroke="var(--primary-color)" stroke-width="3" 
                                        stroke-dasharray="157" stroke-dashoffset="${157 - (progressPercentage * 157 / 100)}" 
                                        stroke-linecap="round" class="progress-bar-circle"/>
                            </svg>
                            <div class="progress-text">
                                <span class="progress-number">${progressPercentage}%</span>
                </div>
                        </div>
                        <div class="progress-details">
                            <h4>Project Progress</h4>
                            <p>${completedItems} of ${totalItems} milestones completed</p>
                            <div class="progress-bar-container">
                                <div class="progress-bar-modern" style="width: ${progressPercentage}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Timeline Grid -->
                <div class="timeline-grid">
            `;
            
            // Display timeline items in a modern grid format
            sortedTimeline.forEach((item, index) => {
                const date = formatDate(item.date);
                const delay = 100 * (index + 1);
                
                // Determine status for styling
                let statusClass = 'pending';
                let statusIcon = 'bi-clock';
                let statusText = 'Pending';
                
                if (item.status) {
                    const statusLower = item.status.toLowerCase();
                    if (statusLower.includes('completed') || statusLower.includes('done') || statusLower.includes('finished')) {
                        statusClass = 'completed';
                        statusIcon = 'bi-check-circle-fill';
                        statusText = 'Completed';
                    } else if (statusLower.includes('progress') || statusLower.includes('active') || statusLower.includes('ongoing')) {
                        statusClass = 'in-progress';
                        statusIcon = 'bi-play-circle-fill';
                        statusText = 'In Progress';
                    } else if (statusLower.includes('delayed') || statusLower.includes('overdue')) {
                        statusClass = 'delayed';
                        statusIcon = 'bi-exclamation-triangle-fill';
                        statusText = 'Delayed';
                    }
                }
                
                // Get appropriate icon based on title or status
                let icon = 'bi-calendar-event';
                const titleLower = item.title.toLowerCase();
                if (titleLower.includes('research') || titleLower.includes('study')) icon = 'bi-search';
                if (titleLower.includes('design') || titleLower.includes('plan')) icon = 'bi-pencil-square';
                if (titleLower.includes('development') || titleLower.includes('build') || titleLower.includes('code')) icon = 'bi-code-slash';
                if (titleLower.includes('test') || titleLower.includes('validation')) icon = 'bi-check-circle';
                if (titleLower.includes('review') || titleLower.includes('evaluation')) icon = 'bi-eye';
                if (titleLower.includes('presentation') || titleLower.includes('demo')) icon = 'bi-easel';
                if (titleLower.includes('publication') || titleLower.includes('report')) icon = 'bi-journal-text';
                if (titleLower.includes('meeting') || titleLower.includes('discussion')) icon = 'bi-people';
                if (titleLower.includes('analysis') || titleLower.includes('analyze')) icon = 'bi-graph-up-arrow';
                if (titleLower.includes('implementation') || titleLower.includes('deploy')) icon = 'bi-gear';
                if (titleLower.includes('documentation') || titleLower.includes('document')) icon = 'bi-file-text';
                
                const description = item.description || 'No description available';
                
                // Build assigned by name for below title
                let assignedByNameForTitle = '';
                if (item.assignedBy) {
                    // Handle both new and legacy format
                    if (typeof item.assignedBy === 'object' && item.assignedBy !== null) {
                        const name = item.assignedBy.name || 'Unknown';
                        const userId = item.assignedBy.id;
                        const userType = 'faculty'; // Set explicit faculty type for assignedBy
                        
                        // Create a unique placeholder for this assigned by name
                        const placeholderClass = `assigned-by-placeholder-${index}`;
                        
                        // Initialize with just the name, will be replaced with link via createProfileLink
                        assignedByNameForTitle = `<div class="assigned-by-name ${placeholderClass}">by ${name}</div>`;
                        
                        // Attempt to create a profile link asynchronously
                        if (name && (name !== 'Unknown')) {
                            // Use the async createProfileLink function to fetch faculty profile
                            createProfileLink(name, userId, userType).then(linkedName => {
                                // Update all instances of this placeholder with the linked name
                                document.querySelectorAll(`.${placeholderClass}`).forEach(el => {
                                    el.innerHTML = `by ${linkedName}`;
                                });
                            }).catch(error => {
                                console.log('Failed to create faculty profile link:', error);
                            });
                        }
                    } else {
                        // Legacy format - use async method for this too
                        const assignedByMember = findMemberById(item.assignedBy, project);
                        const assignedByName = assignedByMember ? assignedByMember.name : item.assignedBy;
                        
                        // Create a unique placeholder for this assigned by name
                        const placeholderClass = `assigned-by-placeholder-${index}`;
                        
                        // Initialize with just the name
                        assignedByNameForTitle = `<div class="assigned-by-name ${placeholderClass}">by ${assignedByName}</div>`;
                        
                        // Try to make it a link if it's a faculty member
                        if (assignedByName && (assignedByName !== 'Unknown')) {
                            createProfileLink(assignedByName, item.assignedBy, 'faculty').then(linkedName => {
                                // Update all instances of this placeholder with the linked name
                                document.querySelectorAll(`.${placeholderClass}`).forEach(el => {
                                    el.innerHTML = `by ${linkedName}`;
                                });
                            }).catch(error => {
                                console.log('Failed to create faculty profile link:', error);
                            });
                        }
                    }
                }
                
                // Build assigned to info for bottom (only assigned to, no assigned by)
                let teamAssignmentInfo = '';
                if (item.assignedTo && Array.isArray(item.assignedTo) && item.assignedTo.length > 0) {
                    teamAssignmentInfo = '<div class="timeline-assignments-bottom">';
                    
                    // Create placeholders for all assignees
                    const assigneeData = item.assignedTo.map((assignee, idx) => {
                        if (typeof assignee === 'object' && assignee !== null) {
                            // New format
                            const name = assignee.name || 'Unknown';
                            const userId = assignee.id;
                            // Default to student but allow override if specified
                            const userType = assignee.type || 'student'; 
                            
                            return {
                                name,
                                userId,
                                userType,
                                placeholder: `assignee-${index}-${idx}`
                            };
                        } else {
                            // Legacy format
                            const assignedToMember = findMemberById(assignee, project);
                            const name = assignedToMember ? assignedToMember.name : assignee;
                            
                            return {
                                name,
                                userId: assignee,
                                userType: null, // Will be determined automatically
                                placeholder: `assignee-${index}-${idx}`
                            };
                        }
                    });
                    
                    // Generate initial placeholder HTML
                    const assignedToNames = assigneeData.map(data => data.name);
                    
                    let assignmentRow = '<div class="assignment-row-inline">';
                    assignmentRow += `
                        <div class="assignment-inline">
                            <span class="assignment-label">Assigned to:</span>
                            <div class="assignment-badges-inline">
                    `;
                    
                    assigneeData.forEach((data, idx) => {
                        assignmentRow += `
                            <span class="assignment-badge assigned-to-badge ${data.placeholder}">
                                <i class="bi bi-person-check-fill"></i> ${data.name}
                            </span>
                        `;
                        
                        // Asynchronously update with profile links
                        if (data.name && (data.name !== 'Unknown')) {
                            createProfileLink(data.name, data.userId, data.userType).then(linkedName => {
                                // Update all instances of this placeholder with the linked name
                                document.querySelectorAll(`.${data.placeholder}`).forEach(el => {
                                    el.innerHTML = `<i class="bi bi-person-check-fill"></i> ${linkedName}`;
                                });
                            }).catch(error => {
                                console.log(`Failed to create profile link for ${data.name}:`, error);
                            });
                        }
                    });
                    
                    assignmentRow += '</div></div>';
                    assignmentRow += '</div>';
                    teamAssignmentInfo += assignmentRow + '</div>';
                }
                
                timelineHTML += `
                    <div class="timeline-milestone ${statusClass}" 
                         data-aos="fade-up" 
                         data-aos-delay="${delay}"
                         data-timeline-index="${index}"
                         onclick="highlightTimelineItem(${index})">
                        <div class="milestone-icon">
                            <i class="bi ${icon}"></i>
                                </div>
                        <div class="milestone-content">
                            <div class="milestone-header">
                                <div class="milestone-title-section">
                                    <h5 class="milestone-title">${item.title}</h5>
                                    ${assignedByNameForTitle}
                            </div>
                                <div class="milestone-date">
                                    <i class="bi bi-calendar3"></i>
                                    <span>${date}</span>
                                </div>
                                <div class="milestone-status" data-milestone-index="${index}">
                                    <i class="bi ${statusIcon}"></i>
                                    <span class="status-display">${statusText}</span>
                                    <select class="milestone-status-dropdown form-select form-select-sm" style="display: none;" data-milestone-index="${index}">
                                        <option value="planned" ${statusClass === 'pending' || item.status?.toLowerCase().includes('planned') ? 'selected' : ''}>Planned</option>
                                        <option value="in-progress" ${statusClass === 'in-progress' || item.status?.toLowerCase().includes('progress') ? 'selected' : ''}>In Progress</option>
                                        <option value="completed" ${statusClass === 'completed' || item.status?.toLowerCase().includes('completed') ? 'selected' : ''}>Completed</option>
                                        <option value="delayed" ${statusClass === 'delayed' || item.status?.toLowerCase().includes('delayed') ? 'selected' : ''}>Delayed</option>
                                    </select>
                            </div>
                        </div>
                            <p class="milestone-description">${description}</p>
                            <div class="milestone-meta">
                                ${item.duration ? `
                                    <div class="milestone-duration">
                                        <i class="bi bi-stopwatch"></i>
                                        <span>${item.duration}</span>
                                    </div>
                                ` : ''}
                            </div>
                            ${teamAssignmentInfo}
                        </div>
                        <div class="milestone-connector"></div>
                    </div>
                `;
            });
            
            timelineHTML += '</div>'; // Close timeline-grid
            
            // Add project duration summary
            if (sortedTimeline.length > 0) {
                const firstItem = sortedTimeline[0];
                const lastItem = sortedTimeline[sortedTimeline.length - 1];
                
                if (firstItem.date && lastItem.date) {
                    const startDate = formatDate(firstItem.date);
                    const endDate = formatDate(lastItem.date);
                    
                    const startDateObj = new Date(typeof firstItem.date === 'object' && firstItem.date.$date ? 
                        firstItem.date.$date : firstItem.date);
                    const endDateObj = new Date(typeof lastItem.date === 'object' && lastItem.date.$date ? 
                        lastItem.date.$date : lastItem.date);
                    const durationDays = Math.ceil((endDateObj - startDateObj) / (1000 * 60 * 60 * 24));
                    
                    timelineHTML += `
                        <div class="timeline-summary">
                            <div class="summary-item">
                                <i class="bi bi-play-circle"></i>
                                <span>Started: ${startDate}</span>
                            </div>
                            <div class="summary-item">
                                <i class="bi bi-flag-checkered"></i>
                                <span>Latest: ${endDate}</span>
                            </div>
                            ${durationDays > 0 ? `
                                <div class="summary-item">
                                    <i class="bi bi-hourglass-split"></i>
                                    <span>Duration: ${durationDays} days</span>
                                </div>
                            ` : ''}
                        </div>
                    `;
                }
            }
            
            timelineEl.innerHTML = timelineHTML;
            
            // Initialize timeline enhancements
            initTimelineEnhancements();
        }
        
        // Helper function to find member by ID
        function findMemberById(memberId, projectData) {
            if (!memberId || !projectData) return null;
            
            // Handle the new format (object with id and name)
            if (typeof memberId === 'object' && memberId !== null) {
                if (memberId.id) {
                    // If we have an ID, try to find the actual member object for additional info
                    const foundMember = findMemberByActualId(memberId.id, projectData);
                    if (foundMember) return foundMember;
                }
                
                // If member not found by ID or no ID, return the object itself as it has name
                return memberId;
            }
            
            // Legacy format - check project members first
            if (projectData.members) {
                const member = projectData.members.find(member => {
                    const memberUserId = member.userId ? 
                        (member.userId.$oid || member.userId) : null;
                    return memberUserId === memberId || member.name === memberId;
                });
                
                if (member) return member;
            }
            
            // Check supervisor
            if (projectData.supervisor) {
                const supervisorUserId = projectData.supervisor.userId ? 
                    (projectData.supervisor.userId.$oid || projectData.supervisor.userId) : null;
                
                if (supervisorUserId === memberId || projectData.supervisor.name === memberId) {
                    return projectData.supervisor;
                }
            }
            
            return null;
        }
        
        // Helper function to find member by their actual ID
        function findMemberByActualId(memberId, projectData) {
            if (!memberId || !projectData) return null;
            
            // Check project members first
            if (projectData.members) {
                const member = projectData.members.find(member => {
                    const memberUserId = member.userId ? 
                        (member.userId.$oid || member.userId) : null;
                    return memberUserId === memberId;
                });
                
                if (member) return member;
            }
            
            // Check supervisor
            if (projectData.supervisor) {
                const supervisorUserId = projectData.supervisor.userId ? 
                    (projectData.supervisor.userId.$oid || projectData.supervisor.userId) : null;
                
                if (supervisorUserId === memberId) {
                    return projectData.supervisor;
                }
            }
            
            return null;
        }
        
        // Function to check supervisor access and enable milestone status editing
        function checkSupervisorAccess(project, loggedInUserId) {
            if (!loggedInUserId || !project) return;
            
            let isSupervisor = false;
            
            // Check if current user is the supervisor
            if (project.supervisor && project.supervisor.userId) {
                if ((project.supervisor.userId.$oid && project.supervisor.userId.$oid === loggedInUserId) ||
                    project.supervisor.userId === loggedInUserId) {
                    isSupervisor = true;
                }
            }
            
            // If user is supervisor, show dropdowns and hide status text
            if (isSupervisor) {
                const statusDisplays = document.querySelectorAll('.milestone-status .status-display');
                const statusDropdowns = document.querySelectorAll('.milestone-status-dropdown');
                
                statusDisplays.forEach(display => {
                    display.style.display = 'none';
                });
                
                statusDropdowns.forEach(dropdown => {
                    dropdown.style.display = 'inline-block';
                    
                    // Add change event listener
                    dropdown.addEventListener('change', function() {
                        const milestoneIndex = this.getAttribute('data-milestone-index');
                        const newStatus = this.value;
                        updateMilestoneStatus(milestoneIndex, newStatus);
                    });
                });
                
                // Store supervisor status globally for other functions
                window.currentUserIsSupervisor = true;
            }
        }
        
        // Function to update milestone status
        function updateMilestoneStatus(milestoneIndex, newStatus) {
            if (!currentProject || !currentProject._id) {
                console.error('No current project available');
                return;
            }
            
            // Show loading state
            const dropdown = document.querySelector(`[data-milestone-index="${milestoneIndex}"]`);
            if (dropdown) {
                dropdown.disabled = true;
            }
            
            // Update the milestone status in current project data
            if (currentProject.timeline && currentProject.timeline[milestoneIndex]) {
                currentProject.timeline[milestoneIndex].status = newStatus;
            }
            
            // Prepare FormData like timeline_editor_overlay.php does
            const data = new FormData();
            data.append('project_id', currentProject._id.$oid || currentProject._id);
            data.append('timeline', JSON.stringify(currentProject.timeline));
            data.append('action', 'update_timeline'); // Same action as timeline editor
            
            console.log('Sending milestone status update via timeline update');
            console.log('Request data:', {
                project_id: currentProject._id.$oid || currentProject._id,
                action: 'update_timeline',
                milestone_index: milestoneIndex,
                new_status: newStatus
            });
            
            // Determine correct path to update_project.php (same logic as timeline editor)
            let basePath = '';
            if (window.location.pathname.includes('/Project_details.php')) {
                basePath = 'src/model/update_project.php';
            } else {
                const pathParts = window.location.pathname.split('/');
                if (pathParts.length > 2) {
                    basePath = '../src/model/update_project.php';
                } else {
                    basePath = 'src/model/update_project.php';
                }
            }
            
            console.log('Using path for update_project.php:', basePath);
            
            // Send to server using same method as timeline editor
            fetch(basePath, {
                method: 'POST',
                body: data
            })
            .then(response => {
                console.log('Response received:', response.status);
                if (!response.ok) {
                    throw new Error(`Server responded with status ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                console.log('Response data:', result);
                
                if (result.success) {
                    // Fetch current user info and send system message like timeline editor does
                    fetch('src/model/get_current_user.php')
                    .then(response => response.json())
                    .then(userData => {
                        // Get user name from response or use fallback
                        let currentUserName = 'Someone';
                        if (userData && userData.success && userData.isLoggedIn && userData.user) {
                            currentUserName = userData.user.name || 'Someone';
                        }
                        
                        // Send system message based on assigned person and status
                        const milestone = currentProject.timeline[milestoneIndex];
                        const milestoneTitle = milestone?.title || 'a milestone';
                        

                        
                        // Get assigned person(s) for personalized message
                        let assignedPersonName = null;
                        
                        // Check if there are assigned people (assignedTo takes priority over assignedBy)
                        if (milestone?.assignedTo && Array.isArray(milestone.assignedTo) && milestone.assignedTo.length > 0) {
                            // Get first assigned person's name for the message
                            const firstAssignee = milestone.assignedTo[0];
                            if (typeof firstAssignee === 'object' && firstAssignee.name) {
                                assignedPersonName = firstAssignee.name;
                            } else if (typeof firstAssignee === 'string') {
                                // Try to find member by ID or use the string directly
                                const member = findMemberById(firstAssignee, currentProject);
                                assignedPersonName = member ? member.name : firstAssignee;
                            }
                            
                            // Handle multiple assignees
                            if (milestone.assignedTo.length > 1) {
                                assignedPersonName += " and " + (milestone.assignedTo.length - 1) + " other" + (milestone.assignedTo.length > 2 ? "s" : "");
                            }
                        } else if (milestone?.assignedBy) {
                            // Fall back to assignedBy if no assignedTo
                            if (typeof milestone.assignedBy === 'object' && milestone.assignedBy.name) {
                                assignedPersonName = milestone.assignedBy.name;
                            } else if (typeof milestone.assignedBy === 'string') {
                                const member = findMemberById(milestone.assignedBy, currentProject);
                                assignedPersonName = member ? member.name : milestone.assignedBy;
                            }
                        }
                        
                        // Create status-specific message
                        let systemMessage = '';
                        
                        if (assignedPersonName) {
                            switch (newStatus.toLowerCase()) {
                                case 'completed':
                                    systemMessage = `🎉 ${assignedPersonName} completed "${milestoneTitle}"!`;
                                    break;
                                case 'in-progress':
                                    // Handle grammar for multiple assignees
                                    const isPlural = assignedPersonName.includes(' and ') || assignedPersonName.includes(' others');
                                    systemMessage = `⚡ ${assignedPersonName} ${isPlural ? 'are' : 'is'} working on "${milestoneTitle}".`;
                                    break;
                                case 'delayed':
                                    systemMessage = `⏰ ${assignedPersonName} needs more time for "${milestoneTitle}".`;
                                    break;
                                case 'planned':
                                    systemMessage = `📋 "${milestoneTitle}" has been planned for ${assignedPersonName}.`;
                                    break;
                                default:
                                    systemMessage = `📝 ${assignedPersonName} updated "${milestoneTitle}" status to ${newStatus.charAt(0).toUpperCase() + newStatus.slice(1).replace('-', ' ')}.`;
                            }
                        } else {
                            // Fallback to original format if no assigned person
                            const statusDisplay = newStatus.charAt(0).toUpperCase() + newStatus.slice(1).replace('-', ' ');
                            systemMessage = `${currentUserName} updated milestone "${milestoneTitle}" status to ${statusDisplay}.`;
                        }
                        
                        const systemMessageData = new FormData();
                        systemMessageData.append('projectId', currentProject._id.$oid || currentProject._id);
                        systemMessageData.append('message', systemMessage);
                        
                        // Send the system message
                        return fetch('src/model/send_system_chat_message.php', {
                            method: 'POST',
                            body: systemMessageData
                        });
                    })
                    .then(response => response.json())
                    .then(msgResult => {
                        console.log('System message result:', msgResult);
                    })
                    .catch(error => {
                        console.error('Error sending system message:', error);
                    });
                    
                    // Re-render timeline to reflect changes
                    renderTimeline(currentProject);
                    
                    // Re-enable supervisor access after timeline re-render
                    setTimeout(() => {
                        const loggedInUserId = <?php echo isset($_SESSION['user_id']) ? "'" . $_SESSION['user_id'] . "'" : 'null'; ?>;
                        checkSupervisorAccess(currentProject, loggedInUserId);
                    }, 100);
                    
                    // Show success message
                    showStatusUpdateMessage('Milestone status updated successfully!', 'success');
                } else {
                    console.error('Error updating milestone status:', result.message);
                    showStatusUpdateMessage('Failed to update milestone status: ' + (result.message || 'Unknown error'), 'error');
                    
                    // Revert the change in local data
                    const originalStatus = getCurrentStatusFromTimeline(milestoneIndex);
                    if (currentProject.timeline && currentProject.timeline[milestoneIndex]) {
                        currentProject.timeline[milestoneIndex].status = originalStatus;
                    }
                    
                    // Revert dropdown value
                    if (dropdown) {
                        dropdown.value = originalStatus;
                    }
                }
            })
            .catch(error => {
                console.error('Error updating milestone status:', error);
                
                // Revert the change in local data
                const originalStatus = getCurrentStatusFromTimeline(milestoneIndex);
                if (currentProject.timeline && currentProject.timeline[milestoneIndex]) {
                    currentProject.timeline[milestoneIndex].status = originalStatus;
                }
                
                let errorMessage = 'An error occurred while updating the milestone status';
                if (error.message) {
                    errorMessage += ': ' + error.message;
                }
                
                showStatusUpdateMessage(errorMessage, 'error');
                
                // Revert dropdown value
                if (dropdown) {
                    dropdown.value = originalStatus;
                }
            })
            .finally(() => {
                // Re-enable dropdown
                if (dropdown) {
                    dropdown.disabled = false;
                }
            });
        }
        
        // Helper function to get current status from timeline
        function getCurrentStatusFromTimeline(milestoneIndex) {
            if (currentProject && currentProject.timeline && currentProject.timeline[milestoneIndex]) {
                const status = currentProject.timeline[milestoneIndex].status || 'planned';
                return status.toLowerCase().replace(/\s+/g, '-');
            }
            return 'planned';
        }
        
        // Function to show status update messages
        function showStatusUpdateMessage(message, type) {
            // Create or update notification element
            let notification = document.getElementById('milestone-status-notification');
            if (!notification) {
                notification = document.createElement('div');
                notification.id = 'milestone-status-notification';
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 12px 20px;
                    border-radius: 8px;
                    color: white;
                    font-weight: 500;
                    z-index: 10000;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                `;
                document.body.appendChild(notification);
            }
            
            // Set message and style based on type
            notification.textContent = message;
            notification.className = type === 'success' ? 'alert-success' : 'alert-danger';
            notification.style.backgroundColor = type === 'success' ? '#059669' : '#dc2626';
            
            // Show notification
            notification.style.opacity = '1';
            
            // Hide after 3 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
            }, 3000);
        }
        
        // Add timeline enhancement functions
        function initTimelineEnhancements() {
            // Add scroll-based progress animation
            function updateTimelineProgress() {
                const timelineContainer = document.getElementById('timeline-container');
                const progressLine = document.getElementById('timeline-progress');
                const progressIndicator = document.getElementById('progress-indicator');
                
                if (!timelineContainer || !progressLine) return;
                
                const containerRect = timelineContainer.getBoundingClientRect();
                const windowHeight = window.innerHeight;
                const containerTop = containerRect.top;
                const containerHeight = containerRect.height;
                
                // Calculate scroll progress
                let scrollProgress = 0;
                if (containerTop < windowHeight && containerTop + containerHeight > 0) {
                    const visibleHeight = Math.min(windowHeight - Math.max(containerTop, 0), 
                        containerHeight - Math.max(0, -containerTop));
                    scrollProgress = Math.max(0, Math.min(1, visibleHeight / containerHeight));
                }
                
                // Update progress line
                progressLine.style.height = `${scrollProgress * 100}%`;
                
                // Update progress indicator visibility
                if (progressIndicator) {
                    progressIndicator.style.opacity = scrollProgress > 0.1 ? '1' : '0';
                    progressIndicator.style.transform = `translateX(-50%) translateY(${scrollProgress < 0.1 ? '-20px' : '0'})`;
                }
                
                // Debug: Log scroll progress (remove this in production)
                if (scrollProgress > 0) {
                    console.log('Timeline scroll progress:', scrollProgress);
                }
            }
            
            // Add event listeners
            window.addEventListener('scroll', updateTimelineProgress, { passive: true });
            window.addEventListener('resize', updateTimelineProgress, { passive: true });
            
            // Initial call
            setTimeout(updateTimelineProgress, 100);
            
            // Add intersection observer for timeline items
            const timelineItems = document.querySelectorAll('.timeline-item');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('timeline-visible');
                        // Add a subtle pulse effect when item comes into view
                        setTimeout(() => {
                            const icon = entry.target.querySelector('.timeline-icon');
                            if (icon) {
                                icon.style.animation = 'pulse-once 0.6s ease-out';
                                setTimeout(() => {
                                    icon.style.animation = '';
                                }, 600);
                            }
                        }, 200);
                    }
                });
            }, {
                threshold: 0.3,
                rootMargin: '0px 0px -50px 0px'
            });
            
            timelineItems.forEach(item => observer.observe(item));
        }
        
        // Function to highlight a timeline item when clicked
        function highlightTimelineItem(index) {
            // Remove previous highlights
            document.querySelectorAll('.timeline-item').forEach(item => {
                item.classList.remove('timeline-highlighted');
            });
            
            // Add highlight to clicked item
            const clickedItem = document.querySelector(`[data-timeline-index="${index}"]`);
            if (clickedItem) {
                clickedItem.classList.add('timeline-highlighted');
                
                // Add temporary highlight class
                setTimeout(() => {
                    clickedItem.classList.remove('timeline-highlighted');
                }, 2000);
                
                // Scroll item into view smoothly
                clickedItem.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
            }
        }
        
        // Function to expand/collapse timeline descriptions
        function expandDescription(event, index) {
            event.stopPropagation();
            const item = document.querySelector(`[data-timeline-index="${index}"]`);
            const description = item.querySelector('.timeline-description');
            const button = event.target.closest('button');
            const icon = button.querySelector('i');
            
            if (description.classList.contains('expanded')) {
                // Collapse
                description.classList.remove('expanded');
                icon.className = 'bi bi-chevron-down';
                // Restore truncated text (you'd need to store original text)
            } else {
                // Expand
                description.classList.add('expanded');
                icon.className = 'bi bi-chevron-up';
                // Show full text (you'd need to store original text)
            }
        }
        
        // Add CSS for timeline enhancements
        const timelineCSS = `
            <style>
                .timeline-visible {
                    opacity: 1 !important;
                }
                
                .timeline-highlighted {
                    transform: translateY(-10px) !important;
                    z-index: 100 !important;
                }
                
                .timeline-highlighted .timeline-content {
                    box-shadow: 
                        0 25px 50px rgba(0, 0, 0, 0.3),
                        0 0 50px rgba(76, 201, 240, 0.5),
                        0 0 100px rgba(76, 201, 240, 0.2) !important;
                    border-left-width: 8px !important;
                }
                
                .timeline-duration,
                .timeline-team {
                    font-size: 0.8rem;
                    opacity: 0.8;
                    transition: opacity 0.3s ease;
                }
                
                        .timeline-assignment {
            font-size: 0.8rem;
            opacity: 0.8;
            transition: opacity 0.3s ease;
            border-left: 2px solid rgba(76, 201, 240, 0.3);
            padding-left: 8px;
            margin-top: 8px;
        }
        
        /* New CSS for inline assignment layout */
        .timeline-assignments-bottom {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .assignment-row-inline {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
        }
        
        .assignment-inline {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .assignment-badges-inline {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        
        .assignment-label {
            font-size: 0.8rem;
            opacity: 0.8;
            font-weight: 500;
        }
        
        .assignment-badge {
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 12px;
            white-space: nowrap;
        }
        
        .assigned-by-badge {
            background-color: rgba(76, 201, 240, 0.2);
            color: var(--accent-color);
            border: 1px solid rgba(76, 201, 240, 0.3);
        }
        
        .assigned-to-badge {
            background-color: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        /* Assigned by name below title */
        .assigned-by-name {
            font-size: 0.75rem;
            font-weight: 300;
            color: var(--text-muted);
            margin-top: 4px;
            opacity: 0.8;
            font-style: italic;
        }
                }
                
                .timeline-assignment .assignment-by {
                    margin-bottom: 4px;
                }
                
                .timeline-assignment .assignment-to {
                    /* Styling handled by Bootstrap classes in JavaScript */
                }
                
                .timeline-assignment .text-primary {
                    color: #2563eb !important;
                }
                
                .timeline-assignment .text-success {
                    color: #10b981 !important;
                }
                
                .timeline-assignment i {
                    font-size: 0.75rem;
                }
                
                .timeline-content:hover .timeline-duration,
                .timeline-content:hover .timeline-team,
                .timeline-content:hover .timeline-assignment {
                    opacity: 1;
                }
                
                .timeline-description.expanded {
                    max-height: none !important;
                    overflow: visible !important;
                }
                
                @keyframes pulse-once {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.3); }
                    100% { transform: scale(1); }
                }
            </style>
        `;
        
        // Inject the CSS
        if (!document.querySelector('#timeline-enhancements-css')) {
            const styleElement = document.createElement('div');
            styleElement.id = 'timeline-enhancements-css';
            styleElement.innerHTML = timelineCSS;
            document.head.appendChild(styleElement);
        }
        
        function renderReferences(project) {
            const referencesEl = document.getElementById('references-container');
            
            if (!project.references || project.references.length === 0) {
                document.getElementById('project-references').style.display = 'none';
                return;
            }
            
            let referencesHTML = '';
            
            project.references.forEach((ref, index) => {
                referencesHTML += `
                    <div class="reference-item" data-aos="fade-up" data-aos-delay="${100 * (index + 1)}">
                        <h5 class="mb-2">${ref.title}</h5>
                        <a href="${ref.link}" target="_blank" rel="noopener" class="small link-hover">
                            <i class="bi bi-link-45deg me-1"></i>${ref.link}
                        </a>
                    </div>
                `;
            });
            
            referencesEl.innerHTML = referencesHTML;
        }
        
        function renderMedia(project) {
            const mediaEl = document.getElementById('media-container');
            
            if (!project.media || project.media.length === 0) {
                document.getElementById('project-media').style.display = 'none';
                return;
            }
            
            let mediaHTML = '';
            
            // Store media items for lightbox functionality
            window.projectMedia = project.media;
            
            project.media.forEach((item, index) => {
                // Set placeholder image path
                const placeholderImage = 'assets/images/Research_Card_Placeholder.png';
                
                if (item.type === 'image') {
                    // Use secure download proxy for stored images, or direct URL for external images
                    let imageUrl = item.url || placeholderImage;
                    
                    // If it's a stored image (storage/ or uploads/), use download proxy with inline display
                    if (imageUrl && (imageUrl.includes('storage/') || imageUrl.includes('uploads/'))) {
                        const filePath = imageUrl.replace(/^\//, '');
                        imageUrl = '/download.php?file=' + encodeURIComponent(filePath) + '&inline=1';
                    }
                    
                    mediaHTML += `
                        <div class="col-sm-6 col-lg-4 mb-4" data-aos="zoom-in" data-aos-delay="${100 * (index + 1)}">
                            <div class="media-card image-card" data-media-index="${index}">
                                <div class="media-counter">${index + 1}/${project.media.length}</div>
                                <div class="media-type-badge">
                                    <i class="bi bi-image me-1"></i> Image
                                </div>
                                <img src="${imageUrl}" alt="${item.caption || 'Project image'}" class="media-image" 
                                     onerror="this.onerror=null; this.src='${placeholderImage}';">
                                <div class="media-overlay"></div>
                                <div class="media-caption">${item.caption || 'Project image'}</div>
                                <div class="media-zoom-icon" onclick="openLightbox(${index})">
                                    <i class="bi bi-zoom-in"></i>
                                </div>
                            </div>
                        </div>
                    `;
                } else if (item.type === 'video') {
                    // For videos, handle both stored videos and external URLs (YouTube, etc.)
                    let videoUrl = item.url || '';
                    const videoCaption = item.caption || 'Project video';
                    
                    // If it's a stored video (storage/ or uploads/), use download proxy with inline display
                    if (videoUrl && (videoUrl.includes('storage/') || videoUrl.includes('uploads/')) && 
                        !videoUrl.includes('youtube.com') && !videoUrl.includes('youtu.be')) {
                        const filePath = videoUrl.replace(/^\//, '');
                        videoUrl = '/download.php?file=' + encodeURIComponent(filePath) + '&inline=1';
                    }
                    
                    mediaHTML += `
                        <div class="col-md-6 mb-4" data-aos="zoom-in" data-aos-delay="${100 * (index + 1)}">
                            <div class="media-card video-card">
                                <div class="media-counter">${index + 1}/${project.media.length}</div>
                                <div class="media-type-badge">
                                    <i class="bi bi-film me-1"></i> Video
                                </div>
                                <div class="ratio ratio-16x9" id="video-container-${index}">
                                    ${videoUrl ? 
                                        (videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be') ?
                                            `<iframe src="${videoUrl}" title="${videoCaption}" allowfullscreen
                                                onerror="handleVideoError(this, '${placeholderImage}')"></iframe>` :
                                            `<video controls class="w-100 h-100" src="${videoUrl}">
                                                Your browser does not support the video tag.
                                            </video>`) : 
                                        `<div class="placeholder-bg" style="background-image: url('${placeholderImage}')"></div>`
                                    }
                                </div>
                                <div class="video-caption">${videoCaption}</div>
                            </div>
                        </div>
                    `;
                }
            });
            
            mediaEl.innerHTML = mediaHTML;
            
            // Initialize lightbox functionality
            initLightbox();
        }
        
        // Handler for video loading errors
        function handleVideoError(iframe, placeholderUrl) {
            if (iframe) {
                iframe.style.display = 'none';
                const container = iframe.parentNode;
                container.classList.add('placeholder-bg');
                container.style.backgroundImage = `url('${placeholderUrl}')`;
            }
        }
        
        // Lightbox functionality
        function initLightbox() {
            const lightbox = document.getElementById('lightbox');
            const lightboxClose = document.getElementById('lightbox-close');
            const lightboxPrev = document.getElementById('lightbox-prev');
            const lightboxNext = document.getElementById('lightbox-next');
            
            // Close lightbox when clicking the close button
            lightboxClose.addEventListener('click', closeLightbox);
            
            // Close lightbox when clicking outside the image
            lightbox.addEventListener('click', function(e) {
                if (e.target === lightbox) {
                    closeLightbox();
                }
            });
            
            // Navigation buttons
            lightboxPrev.addEventListener('click', showPrevImage);
            lightboxNext.addEventListener('click', showNextImage);
            
            // Keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (!lightbox.classList.contains('active')) return;
                
                if (e.key === 'Escape') closeLightbox();
                if (e.key === 'ArrowLeft') showPrevImage();
                if (e.key === 'ArrowRight') showNextImage();
            });
            
            // Make image cards clickable
            const imageCards = document.querySelectorAll('.image-card');
            imageCards.forEach(card => {
                card.addEventListener('click', function() {
                    const index = parseInt(card.getAttribute('data-media-index'));
                    openLightbox(index);
                });
            });
        }
        
        // Current image index in lightbox
        let currentImageIndex = 0;
        
        // Updated lightbox function for placeholder handling and secure download proxy
        function openLightbox(index) {
            const lightbox = document.getElementById('lightbox');
            const lightboxImage = document.getElementById('lightbox-image');
            const lightboxCaption = document.getElementById('lightbox-caption');
            
            currentImageIndex = index;
            
            // Get media item
            const media = window.projectMedia[index];
            const placeholderImage = 'assets/images/Research_Card_Placeholder.png';
            
            // Use secure download proxy for stored images
            let imageUrl = media.url || placeholderImage;
            if (imageUrl && (imageUrl.includes('storage/') || imageUrl.includes('uploads/'))) {
                const filePath = imageUrl.replace(/^\//, '');
                imageUrl = '/download.php?file=' + encodeURIComponent(filePath) + '&inline=1';
            }
            
            // Set image source and caption
            lightboxImage.src = imageUrl;
            lightboxCaption.textContent = media.caption || '';
            
            // Add error handling for the lightbox image
            lightboxImage.onerror = function() {
                this.onerror = null;
                this.src = placeholderImage;
            };
            
            // Show lightbox
            lightbox.classList.add('active');
            
            // Disable body scrolling
            document.body.style.overflow = 'hidden';
        }
        
        function closeLightbox() {
            const lightbox = document.getElementById('lightbox');
            lightbox.classList.remove('active');
            
            // Re-enable body scrolling
            document.body.style.overflow = '';
        }
        
        function showPrevImage() {
            if (!window.projectMedia) return;
            
            // Find previous image (skip videos)
            let index = currentImageIndex;
            do {
                index = (index - 1 + window.projectMedia.length) % window.projectMedia.length;
            } while (window.projectMedia[index].type !== 'image' && index !== currentImageIndex);
            
            if (window.projectMedia[index].type === 'image') {
                openLightbox(index);
            }
        }
        
        function showNextImage() {
            if (!window.projectMedia) return;
            
            // Find next image (skip videos)
            let index = currentImageIndex;
            do {
                index = (index + 1) % window.projectMedia.length;
            } while (window.projectMedia[index].type !== 'image' && index !== currentImageIndex);
            
            if (window.projectMedia[index].type === 'image') {
                openLightbox(index);
            }
        }
        
        function renderStats(project) {
            const statsEl = document.getElementById('stats-container');
            
            // Initialize stats if they don't exist
            if (!project.stats || typeof project.stats !== 'object') {
                project.stats = { views: 0, downloads: 0, favorites: 0 };
            }
            
            // Ensure numeric values
            const views = parseInt(project.stats.views) || 0;
            const downloads = parseInt(project.stats.downloads) || 0;
            const favorites = parseInt(project.stats.favorites) || 0;
            
            let statsHTML = '<ul class="list-group list-group-flush">';
            
            // Always show views (most basic stat)
            statsHTML += `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-eye me-2"></i>Views</span>
                    <span class="badge bg-primary rounded-pill">${views}</span>
                </li>
            `;
            
            // Show downloads if greater than 0 or if files exist
            if (downloads > 0 || (project.files && project.files.length > 0)) {
                statsHTML += `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-download me-2"></i>Downloads</span>
                        <span class="badge bg-primary rounded-pill">${downloads}</span>
                    </li>
                `;
            }
            
            // Always show favorites
            statsHTML += `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-star me-2"></i>Favorites</span>
                    <span class="badge bg-primary rounded-pill">${favorites}</span>
                </li>
            `;
            
            // Add team size as a stat
            const teamSize = (project.members ? project.members.length : 0) + (project.supervisor ? 1 : 0);
            if (teamSize > 0) {
                statsHTML += `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-people me-2"></i>Team Members</span>
                        <span class="badge bg-success rounded-pill">${teamSize}</span>
                    </li>
                `;
            }
            
            // Add file count if files exist
            if (project.files && project.files.length > 0) {
                statsHTML += `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-file-earmark me-2"></i>Files</span>
                        <span class="badge bg-info rounded-pill">${project.files.length}</span>
                    </li>
                `;
            }
            
            statsHTML += '</ul>';
            statsEl.innerHTML = statsHTML;
            
            // Add fade-in animation for stats
            const statItems = statsEl.querySelectorAll('.list-group-item');
            statItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(10px)';
                
                setTimeout(() => {
                    item.style.transition = 'all 0.3s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });
        }
        
        // Helper functions
        function formatDate(dateString) {
            if (!dateString) return 'Not specified';
            
            try {
                let dateValue = dateString;
                
                // Handle MongoDB UTCDateTime objects
                if (typeof dateString === 'object') {
                    // MongoDB UTCDateTime format: { "$date": "2024-01-01T00:00:00.000Z" }
                    if (dateString.$date) {
                        dateValue = dateString.$date;
                    }
                    // MongoDB BSON UTCDateTime format with $numberLong
                    else if (dateString.$date && dateString.$date.$numberLong) {
                        dateValue = parseInt(dateString.$date.$numberLong);
                    }
                    // Direct timestamp (milliseconds)
                    else if (typeof dateString === 'object' && dateString.toString && !isNaN(new Date(dateString).getTime())) {
                        dateValue = dateString.toString();
                    }
                    // Try extracting timestamp if it's a complex object
                    else if (dateString.sec) {
                        // MongoDB internal timestamp format
                        dateValue = dateString.sec * 1000; // Convert seconds to milliseconds
                    }
                }
                
                // Create Date object
                const date = new Date(dateValue);
                
                // Check if date is valid
                if (isNaN(date.getTime())) {
                    console.warn('Invalid date value:', dateString);
                    return 'Date unavailable';
                }
                
                return date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric'
                });
            } catch (error) {
                console.error('Error formatting date:', error, 'Input:', dateString);
                return 'Date format error';
            }
        }
        
        function getFileIcon(fileType) {
            if (!fileType) return 'bi bi-file';
            
            if (fileType.includes('pdf')) return 'bi bi-file-pdf';
            if (fileType.includes('word') || fileType.includes('document')) return 'bi bi-file-word';
            if (fileType.includes('excel') || fileType.includes('sheet')) return 'bi bi-file-excel';
            if (fileType.includes('powerpoint') || fileType.includes('presentation')) return 'bi bi-file-ppt';
            if (fileType.includes('image')) return 'bi bi-file-image';
            if (fileType.includes('video')) return 'bi bi-file-play';
            if (fileType.includes('audio')) return 'bi bi-file-music';
            if (fileType.includes('zip') || fileType.includes('rar') || fileType.includes('7z')) return 'bi bi-file-zip';
            if (fileType.includes('code') || fileType.includes('text')) return 'bi bi-file-code';
            
            return 'bi bi-file';
        }
        
        function formatFileSize(bytes) {
            if (!bytes || bytes === 0) return 'Unknown size';
            
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(1024));
            
            return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        function getStatusBadge(status) {
            if (!status) return '';
            
            const statusLower = status.toLowerCase();
            let badgeClass = 'bg-secondary text-white';
            let icon = 'bi-question-circle';
            
            if (statusLower === 'completed') {
                badgeClass = 'bg-success text-white';
                icon = 'bi-check-circle';
            }
            else if (statusLower === 'in progress') {
                badgeClass = 'bg-primary text-white';
                icon = 'bi-play-fill';
            }
            else if (statusLower === 'pending') {
                badgeClass = 'bg-warning text-dark';
                icon = 'bi-hourglass-split';
            }
            else if (statusLower === 'delayed') {
                badgeClass = 'bg-danger text-white';
                icon = 'bi-exclamation-triangle';
            }
            
            return `<span class="badge ${badgeClass}"><i class="bi ${icon} me-1"></i>${status}</span>`;
        }

        /**
         * Toggle project privacy between public and private
         * @param {string} projectId - The ID of the project to toggle
         */
        function toggleProjectPrivacy(projectId) {
            if (!projectId) {
                console.error('Missing project ID');
                return;
            }
            
            // Disable the button during the API call
            const toggleButton = document.getElementById('privacyToggleBtn');
            toggleButton.disabled = true;
            toggleButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
            
            // Call the API to toggle privacy
            fetch('src/model/toggle_project_privacy.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ projectId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showToast('Privacy setting updated successfully', 'success');
                    
                    // Reload the page to reflect the new privacy setting
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Show error message
                    showToast(data.error || 'Failed to update privacy setting', 'danger');
                    
                    // Re-enable the button
                    toggleButton.disabled = false;
                    const isCurrentlyPublic = toggleButton.classList.contains('btn-outline-warning');
                    const btnText = isCurrentlyPublic ? 'Make Private' : 'Make Public';
                    const iconClass = isCurrentlyPublic ? 'bi-lock-fill' : 'bi-unlock-fill';
                    toggleButton.innerHTML = `<i class="bi ${iconClass} me-1"></i>${btnText}`;
                }
            })
            .catch(error => {
                console.error('Error toggling privacy:', error);
                showToast('An error occurred. Please try again.', 'danger');
                
                // Re-enable the button
                toggleButton.disabled = false;
                const isCurrentlyPublic = toggleButton.classList.contains('btn-outline-warning');
                const btnText = isCurrentlyPublic ? 'Make Private' : 'Make Public';
                const iconClass = isCurrentlyPublic ? 'bi-lock-fill' : 'bi-unlock-fill';
                toggleButton.innerHTML = `<i class="bi ${iconClass} me-1"></i>${btnText}`;
            });
        }

        /**
         * Show a toast notification
         * @param {string} message - The message to display
         * @param {string} type - The type of toast (success, danger, warning, info)
         */
        function showToast(message, type = 'info') {
            // Create toast container if it doesn't exist
            let toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
                toastContainer.style.zIndex = '1080';
                document.body.appendChild(toastContainer);
            }
            
            // Create toast element
            const toastId = 'toast-' + Date.now();
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.id = toastId;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.setAttribute('data-aos', 'fade-left');
            
            // Get appropriate icon
            let icon = 'bi-info-circle';
            if (type === 'success') icon = 'bi-check-circle';
            if (type === 'danger') icon = 'bi-exclamation-circle';
            if (type === 'warning') icon = 'bi-exclamation-triangle';
            
            // Create toast content
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${icon} me-2"></i>${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            
            // Add toast to container
            toastContainer.appendChild(toast);
            
            // Initialize and show toast
            const bsToast = new bootstrap.Toast(toast, {
                autohide: true,
                delay: 3000
            });
            bsToast.show();
            
            // Add entrance animation
            toast.style.transform = 'translateX(100%)';
            toast.style.transition = 'transform 0.3s ease';
            
            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
            }, 50);
            
            // Remove toast from DOM after it's hidden
            toast.addEventListener('hidden.bs.toast', function() {
                // Add exit animation
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => {
                toast.remove();
                }, 300);
            });
        }
    </script>

    <script src="assets/js/project-details.js" defer></script>
    
    <!-- Include Global Meeting Notifications -->
    <?php include 'src/includes/global-meeting-notifications.php'; ?>
    
</body>
</html> 