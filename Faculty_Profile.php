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
// Get all images from the Resources folder
$imageFolder = "assets/resources/research_picture/";
$images = glob($imageFolder . "*.{jpg,png,jpeg,gif}", GLOB_BRACE);

// Include the MongoDB PHP library
require 'vendor/autoload.php';

// Helper function to safely convert various ObjectId formats
function convertToObjectId($id) {
    if (empty($id)) {
        return null;
    }
    
    try {
        // If it's already a MongoDB ObjectId object
        if (is_object($id) && $id instanceof MongoDB\BSON\ObjectId) {
            return $id;
        }
        
        // If it's an array (serialized ObjectId format)
        if (is_array($id)) {
            if (isset($id['$oid'])) {
                return new MongoDB\BSON\ObjectId($id['$oid']);
            }
            // Sometimes it might be nested differently
            if (isset($id['oid'])) {
                return new MongoDB\BSON\ObjectId($id['oid']);
            }
        }
        
        // If it's a string representation
        if (is_string($id)) {
            // Remove any whitespace
            $id = trim($id);
            // Check if it's a valid 24-character hex string
            if (strlen($id) === 24 && ctype_xdigit($id)) {
                return new MongoDB\BSON\ObjectId($id);
            }
        }
        
        // If we can't convert it, log for debugging and return null
        error_log("Unable to convert ID to ObjectId: " . print_r($id, true));
        return null;
        
    } catch (Exception $e) {
        error_log("Error converting ID to ObjectId: " . $e->getMessage() . " | ID: " . print_r($id, true));
        return null;
    }
}

// Create a MongoDB client
$client = new MongoDB\Client(require __DIR__ . '/src/config/mongo_uri.php');
$collection = $client->uiurp->faculties;

// Check if the 'id' parameter is set in the URL
if (isset($_GET['id'])) { 
    try {
        // Convert the 'id' parameter to a MongoDB ObjectId
        $faculty_id = new MongoDB\BSON\ObjectId($_GET['id']);

        // Find the faculty member by _id
        $faculty = $collection->findOne(['_id' => $faculty_id]);

        // Check if the faculty member exists
        if ($faculty) {
            // Convert MongoDB BSON document to an associative array
            $faculty = iterator_to_array($faculty);
        } else {
            // If no faculty member is found, redirect or show an error
            header("Location: faculty_page.php");
            exit();
        }
    } catch (Exception $e) {
        // Handle invalid ObjectId or other errors
        header("Location: faculty_page.php");
        exit();
    }
} else {
    // If no 'id' parameter is provided, redirect or show an error
    header("Location: faculty_page.php");
    exit();
}

// Set default profile image if not present
$profileImage = $faculty['profile_image'] ?? 'assets/resources/imgPlaceholder.png';

// Check if current user is the faculty member they're viewing
$isOwnProfile = false;
if (isset($_SESSION['user_id'], $_SESSION['user_type']) && 
    $_SESSION['user_type'] === 'faculty' && 
    $_SESSION['user_id'] === (string)$faculty['_id']) {
    $isOwnProfile = true;
}

// Get target user ID for schedule
$targetUserId = $faculty_id;
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Profile - <?= $faculty['name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles/faculty_profile.css">
</head>
<body>
    <?php include 'src/includes/navbar.php'; ?>

    <!-- Profile Header -->
    <header class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-4">Hello, I'm <strong><?= $faculty['name']; ?></strong></h1>
                    <p class="lead"><?= $faculty['bio']; ?></p>
                    
                    <div class="info-item">
                        <i class="bi bi-building"></i> 
                        <strong>Office:</strong> <?= $faculty['office_number'] ?? 'N/A'; ?>
                    </div>
                    
                    <div class="info-item">
                        <i class="bi bi-envelope"></i> 
                        <strong>Email:</strong> <?= $faculty['email'] ?? 'N/A'; ?>
                    </div>
                    
                    <div class="info-item">
                        <i class="bi bi-telephone"></i> 
                        <strong>Phone:</strong> <?= $faculty['phone'] ?? 'N/A'; ?>
                    </div>
                    
                    <div class="action-buttons mt-4">
                        <a href="#projects" class="btn btn-primary me-2 mb-2">
                            <i class="bi bi-journal-richtext me-1"></i> My Publications
                        </a>
                        <a href="#interested-fields" class="btn btn-primary me-2 mb-2">
                            <i class="bi bi-stars me-1"></i> Research Fields
                        </a>
                        <a href="#prerequisite" class="btn btn-primary me-2 mb-2">
                            <i class="bi bi-list-check me-1"></i> Prerequisites
                        </a>
                        <a href="#resource" class="btn btn-primary me-2 mb-2">
                            <i class="bi bi-book me-1"></i> Resources
                        </a>
                    </div>
                </div>
                <div class="col-md-4 profile-image-container">
                    <div class="profile-image-wrapper">
                        <img src="<?= $profileImage; ?>" alt="Profile Picture" id="facultyImage" 
                             loading="lazy" 
                             onerror="this.onerror=null; this.src='assets/resources/imgPlaceholder.png'; this.alt='Default Profile Picture';">
                    </div>
                    <div class="mt-3 text-center">
                        <?php if ($isOwnProfile): ?>
                        <a href="edit_faculty.php?id=<?= $faculty['_id']; ?>" class="btn btn-outline-secondary me-2 mb-2">
                            <i class="bi bi-pencil-square me-1"></i> Edit Profile
                        </a>
                        <?php endif; ?>
                        <button class="btn btn-secondary mb-2" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                            <i class="bi bi-calendar-week me-1"></i> Schedule
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Schedule Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content glass-card">
          <div class="modal-header">
            <h5 class="modal-title" id="scheduleModalLabel">
              <?= htmlspecialchars($faculty['name'] ?? 'Faculty') ?>'s Schedule
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Loading indicator -->
            <div id="schedule-loading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading schedule...</span>
              </div>
              <p class="mt-2 text-secondary">Loading schedule data...</p>
            </div>
            
            <!-- Schedule Display -->
            <div id="schedule-container" style="display: none;">
              <div id="schedule-list" class="mb-4">
                <!-- Schedule items will be displayed here -->
              </div>
              
              <!-- No schedules message -->
              <div id="no-schedules" class="empty-state" style="display: none;">
                <i class="bi bi-calendar-x"></i>
                <h4>No Schedule Items</h4>
                <p>No schedule items have been added yet.</p>
              </div>
            </div>
            
            <!-- Add/Edit Schedule Form -->
            <div id="schedule-form-container" style="display: none;">
              <h5 class="mb-3" id="form-title">Add Schedule Item</h5>
              <form id="schedule-form">
                <input type="hidden" id="schedule-id" value="">
                
                <div class="mb-3">
                  <label for="schedule-title" class="form-label">Title*</label>
                  <input type="text" class="form-control" id="schedule-title" required>
                </div>
                
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label for="schedule-day" class="form-label">Day*</label>
                    <select class="form-select" id="schedule-day" required>
                      <option value="">Select Day</option>
                      <option value="Monday">Monday</option>
                      <option value="Tuesday">Tuesday</option>
                      <option value="Wednesday">Wednesday</option>
                      <option value="Thursday">Thursday</option>
                      <option value="Friday">Friday</option>
                      <option value="Saturday">Saturday</option>
                      <option value="Sunday">Sunday</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label for="schedule-type" class="form-label">Type*</label>
                    <select class="form-select" id="schedule-type" required>
                      <option value="">Select Type</option>
                      <option value="Class">Class</option>
                      <option value="Lab">Lab</option>
                      <option value="Office Hours">Office Hours</option>
                      <option value="Meeting">Meeting</option>
                      <option value="Research">Research Work</option>
                      <option value="Other">Other</option>
                    </select>
                  </div>
                </div>
                
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label for="schedule-start-time" class="form-label">Start Time*</label>
                    <input type="time" class="form-control" id="schedule-start-time" required>
                  </div>
                  <div class="col-md-6">
                    <label for="schedule-end-time" class="form-label">End Time*</label>
                    <input type="time" class="form-control" id="schedule-end-time" required>
                  </div>
                </div>
                
                <div class="mb-3">
                  <label for="schedule-location" class="form-label">Location</label>
                  <input type="text" class="form-control" id="schedule-location">
                </div>
                
                <div class="mb-3">
                  <label for="schedule-description" class="form-label">Description</label>
                  <textarea class="form-control" id="schedule-description" rows="3"></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                  <button type="button" class="btn btn-outline-secondary" id="cancel-schedule-form">Cancel</button>
                  <div class="buttons-wrapper d-flex">
                    <button type="button" class="btn btn-danger me-2" id="delete-schedule" style="display: none;">Delete</button>
                    <button type="submit" class="btn btn-primary" id="save-schedule">Save</button>
                  </div>
                </div>
              </form>
            </div>
          </div>
          <div class="modal-footer">
            <?php if ($isOwnProfile): ?>
              <button type="button" class="btn btn-primary" id="add-schedule-btn">
                <i class="bi bi-plus-circle"></i> Add Schedule Item
              </button>
            <?php endif; ?>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Research Fields Section -->
<section id="interested-fields" class="profile-section py-5">
    <div class="container">
        <div class="d-flex justify-content-center align-items-center gap-2 mb-5">
            <h2 class="mb-0">Research Fields</h2>
            <?php if (
                isset($_SESSION['user_id'], $_SESSION['user_type']) &&
                $_SESSION['user_type'] === 'faculty' &&
                $_SESSION['user_id'] === (string)$faculty['_id']
            ): ?>
                <button class="btn btn-sm btn-outline-secondary rounded-circle" data-bs-toggle="modal" data-bs-target="#editResearchFieldsModal">
                    <i class="bi bi-plus"></i>
                </button>
            <?php endif; ?>
        </div>

        <?php if (!empty($faculty['interested_fields_of_research']) && count($faculty['interested_fields_of_research']) > 0): ?>
            <div class="row justify-content-center">
                <?php foreach ($faculty['interested_fields_of_research'] as $index => $field): ?>
                    <div class="col-md-4 col-sm-6 mb-4" style="--delay: <?= $index ?>;">
                        <div class="field-box p-4 h-100 shadow-sm border rounded">
                            <h5 class="mb-2"><?= htmlspecialchars($field['name']); ?></h5>
                            <p class="text-muted mb-0"><?= htmlspecialchars($field['description']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state text-center">
                <i class="bi bi-stars display-4 text-muted"></i>
                <h4 class="mt-3">No Research Fields Added</h4>
                <p class="text-muted">Research fields help students understand your areas of expertise and research focus.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

    <!-- Publications Section -->
    <section id="projects" class="profile-section py-5">
        <div class="container">
            <!-- Centered heading + edit button -->
            <div class="d-flex justify-content-center align-items-center gap-2 mb-5">
                <h2 class="mb-0">Publications</h2>
                <?php if (
                    isset($_SESSION['user_id'], $_SESSION['user_type']) &&
                    $_SESSION['user_type'] === 'faculty' &&
                    $_SESSION['user_id'] === (string)$faculty['_id']
                ): ?>
                    <button class="btn btn-sm btn-outline-secondary rounded-circle" data-bs-toggle="modal" data-bs-target="#editPublicationsModal">
                        <i class="bi bi-plus"></i>
                    </button>
                <?php endif; ?>
            </div>

            <?php if (!empty($faculty['projects']) && count($faculty['projects']) > 0): ?>
                <!-- Projects grid -->
                <div class="row">
                    <?php foreach ($faculty['projects'] as $index => $project): ?>
                        <div class="col-md-4 p-3" style="--delay: <?= $index ?>;">
                            <a href="<?= htmlspecialchars($project['link'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer" style="text-decoration: none; color: inherit;">
                                <div class="card h-100 glass-card">
                                    <?php 
                                        $randomImage = $images[array_rand($images)]; 
                                    ?>
                                    <img src="<?= htmlspecialchars($randomImage); ?>" class="card-img-top" alt="Project Image" loading="lazy">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($project['title']); ?></h5>
                                        <p class="card-text"><?= htmlspecialchars($project['description']); ?></p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-journal-richtext"></i>
                    <h4>No Publications Added</h4>
                    <p>Share your research publications and academic work with students and colleagues.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Prerequisites Section -->
    <section id="prerequisite" class="profile-section py-5">
        <div class="container">
            <!-- Center heading and edit button together -->
            <div class="d-flex justify-content-center align-items-center gap-2 mb-5">
                <h2 class="mb-0">Prerequisites</h2>
                <?php if (isset($_SESSION['user_id'], $_SESSION['user_type']) 
                        && $_SESSION['user_type'] === 'faculty' 
                        && $_SESSION['user_id'] === (string)$faculty['_id']): ?>
                    <button class="btn btn-sm btn-outline-secondary rounded-circle" data-bs-toggle="modal" data-bs-target="#editPrerequisitesModal">
                        <i class="bi bi-plus"></i>
                    </button>
                <?php endif; ?>
            </div>

            <?php if (!empty($faculty['prerequisites']) && count($faculty['prerequisites']) > 0): ?>
                <div class="row justify-content-center">
                    <?php foreach ($faculty['prerequisites'] as $index => $prerequisite): ?>
    <div class="col-md-5 col-lg-4 mb-4" style="--delay: <?= $index ?>;">
        <div class="prerequisite-card p-4 h-100 shadow-sm border rounded">
            <h5 class="mb-2"><?= htmlspecialchars($prerequisite['name']); ?></h5>
            <span class="badge bg-primary mb-2"><?= htmlspecialchars($prerequisite['level']); ?></span>
            <p class="text-muted mb-0"><?= htmlspecialchars($prerequisite['description']); ?></p>
        </div>
    </div>
<?php endforeach; ?>

                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-list-check"></i>
                    <h4>No Prerequisites Added</h4>
                    <p>Prerequisites help students understand what knowledge or skills they need before working with you.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Resources Section -->
    <section id="resource" class="profile-section py-5">
    <div class="container">
        <!-- Heading with Edit Button -->
        <div class="d-flex justify-content-center align-items-center gap-2 mb-5">
            <h2 class="mb-0">Learning Resources</h2>
            <?php if (
                isset($_SESSION['user_id'], $_SESSION['user_type']) &&
                $_SESSION['user_type'] === 'faculty' &&
                $_SESSION['user_id'] === (string)$faculty['_id']
            ): ?>
                <button class="btn btn-sm btn-outline-secondary rounded-circle" data-bs-toggle="modal" data-bs-target="#editResourcesModal">
                    <i class="bi bi-plus"></i>
                </button>
            <?php endif; ?>
        </div>

        <!-- Resource Cards -->
        <?php if (!empty($faculty['resources_to_learn_prerequisites']) && count($faculty['resources_to_learn_prerequisites']) > 0): ?>
            <div class="row justify-content-center">
                <?php foreach ($faculty['resources_to_learn_prerequisites'] as $index => $resource): ?>
                    <div class="col-md-5 col-lg-4 mb-4" style="--delay: <?= $index ?>;">
                        <a href="<?= htmlspecialchars($resource['link']); ?>" target="_blank"
                           class="resource-card d-block p-4 text-decoration-none h-100 shadow-sm border rounded">
                            <h5 class="mb-2">
                                <i class="bi bi-link-45deg me-2"></i>
                                <?= htmlspecialchars($resource['topic']); ?>
                            </h5>
                            <p class="text-muted small mb-0"><?= htmlspecialchars($resource['description']); ?></p>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state text-center mt-4">
                <i class="bi bi-book display-4 text-muted"></i>
                <h4 class="mt-3">No Learning Resources Added</h4>
                <p class="text-muted">Share helpful resources that students can use to prepare for working with you or learn about your research areas.</p>
            </div>
        <?php endif; ?>
    </div>
</section>


    <!-- Contact Section -->
    <section class="contact-section profile-section py-5">
        <div class="container">
            <h2 class="text-center mb-5">Do you have a Project Idea? Let's discuss!</h2>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="contact-info glass-card">
                        <h4>
                            <i class="bi bi-info-circle me-2"></i>
                            Contact Information
                        </h4>
                        <p>
                            <i class="bi bi-building"></i>
                            Office: <?= $faculty['office_number'] ?? 'N/A'; ?>
                        </p>
                        <p>
                            <i class="bi bi-envelope"></i>
                            Email: <?= $faculty['email'] ?? 'N/A'; ?>
                        </p>
                        <p>
                            <i class="bi bi-telephone"></i>
                            Phone: <?= $faculty['phone'] ?? 'N/A'; ?>
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="contact-form glass-card">
                        <form>
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" placeholder="Enter your name">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" placeholder="Enter your email">
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" rows="4" placeholder="Enter your message"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-send me-1"></i> Submit
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'src/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Check for user's theme preference
    document.addEventListener('DOMContentLoaded', function() {
        // Check for saved theme preference or default to 'dark'
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
        
        // Listen for theme changes from navbar (if it has theme toggle functionality)
        window.addEventListener('themeChanged', function(e) {
            const newTheme = e.detail.theme;
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        });
        
        // Apply proper close button styling based on theme
        const btnClose = document.querySelector('.btn-close');
        if (btnClose) {
            btnClose.classList.add(savedTheme === 'dark' ? 'btn-close-white' : '');
        }
        
        // Add animation delay to cards for staggered animation
        document.querySelectorAll('.card, .field-box, .prerequisite-card, .resource-card').forEach(function(el, index) {
            if (!el.style.getPropertyValue('--delay')) {
                el.style.setProperty('--delay', index % 5); // Cycle through 0-4 delay values
            }
        });
        
        // Add fade-in animation to elements
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        // Observe elements for animation
        document.querySelectorAll('.field-box, .prerequisite-card, .resource-card, .card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    });
    
    // Schedule Management
    document.addEventListener('DOMContentLoaded', function() {
      // Variables
      const userId = <?= json_encode((string)$targetUserId) ?>;
      const isOwnProfile = <?= json_encode($isOwnProfile) ?>;
      const scheduleModal = document.getElementById('scheduleModal');
      const scheduleLoading = document.getElementById('schedule-loading');
      const scheduleContainer = document.getElementById('schedule-container');
      const scheduleList = document.getElementById('schedule-list');
      const noSchedules = document.getElementById('no-schedules');
      const scheduleFormContainer = document.getElementById('schedule-form-container');
      const scheduleForm = document.getElementById('schedule-form');
      const addScheduleBtn = document.getElementById('add-schedule-btn');
      const cancelFormBtn = document.getElementById('cancel-schedule-form');
      const deleteScheduleBtn = document.getElementById('delete-schedule');
      
      // Initialize schedule modal
      if (scheduleModal) {
        scheduleModal.addEventListener('shown.bs.modal', function() {
          loadSchedule();
        });
      }
      
      // Add schedule button
      if (addScheduleBtn) {
        addScheduleBtn.addEventListener('click', function() {
          showScheduleForm();
        });
      }
      
      // Cancel form button
      if (cancelFormBtn) {
        cancelFormBtn.addEventListener('click', function() {
          hideScheduleForm();
        });
      }
      
      // Schedule form submission
      if (scheduleForm) {
        scheduleForm.addEventListener('submit', function(e) {
          e.preventDefault();
          saveSchedule();
        });
      }
      
      // Delete schedule button
      if (deleteScheduleBtn) {
        deleteScheduleBtn.addEventListener('click', function() {
          deleteSchedule();
        });
      }
      
      // Load schedule data
      function loadSchedule() {
        if (!scheduleLoading || !scheduleContainer || !scheduleList || !noSchedules) return;
        
        scheduleLoading.style.display = 'block';
        scheduleContainer.style.display = 'none';
        scheduleFormContainer.style.display = 'none';
        
        // Fetch schedule data from server
        fetch('src/model/get_schedule.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ userId: userId })
        })
        .then(response => response.json())
        .then(data => {
          scheduleLoading.style.display = 'none';
          scheduleContainer.style.display = 'block';
          
          if (data.success && data.schedule && data.schedule.length > 0) {
            scheduleList.innerHTML = '';
            noSchedules.style.display = 'none';
            
            // Sort schedule items by day of week
            const dayOrder = {
              'Monday': 1,
              'Tuesday': 2,
              'Wednesday': 3,
              'Thursday': 4,
              'Friday': 5,
              'Saturday': 6,
              'Sunday': 7
            };
            
            data.schedule.sort((a, b) => {
              if (dayOrder[a.day] !== dayOrder[b.day]) {
                return dayOrder[a.day] - dayOrder[b.day];
              }
              return a.startTime.localeCompare(b.startTime);
            });
            
            // Group schedule items by day
            const scheduleByDay = {};
            data.schedule.forEach(item => {
              if (!scheduleByDay[item.day]) {
                scheduleByDay[item.day] = [];
              }
              scheduleByDay[item.day].push(item);
            });
            
            // Create day sections
            for (const [day, items] of Object.entries(scheduleByDay)) {
              const daySection = document.createElement('div');
              daySection.className = 'mb-4';
              
              const dayHeader = document.createElement('h5');
              dayHeader.className = 'mb-3';
              dayHeader.textContent = day;
              daySection.appendChild(dayHeader);
              
              // Create schedule items for the day
              items.forEach(item => {
                const scheduleItem = createScheduleItem(item);
                daySection.appendChild(scheduleItem);
              });
              
              scheduleList.appendChild(daySection);
            }
          } else {
            scheduleList.innerHTML = '';
            noSchedules.style.display = 'block';
          }
        })
        .catch(error => {
          console.error('Error loading schedule:', error);
          scheduleLoading.style.display = 'none';
          scheduleContainer.style.display = 'block';
          scheduleList.innerHTML = '';
          noSchedules.style.display = 'block';
          
          const noSchedulesTitle = noSchedules.querySelector('h4');
          const noSchedulesText = noSchedules.querySelector('p');
          if (noSchedulesTitle) noSchedulesTitle.textContent = 'Error Loading Schedule';
          if (noSchedulesText) noSchedulesText.textContent = 'There was an error loading the schedule data. Please try again later.';
        });
      }
      
      // Create schedule item element
      function createScheduleItem(item) {
        const scheduleItem = document.createElement('div');
        scheduleItem.className = 'card section-card mb-3';
        scheduleItem.setAttribute('data-id', item._id);
        
        // Format time
        const startTime = formatTime(item.startTime);
        const endTime = formatTime(item.endTime);
        const timeString = `${startTime} - ${endTime}`;
        
        // Choose icon based on type
        let typeIcon = 'bi-calendar-event';
        switch (item.type) {
          case 'Class': typeIcon = 'bi-book'; break;
          case 'Lab': typeIcon = 'bi-flask'; break;
          case 'Meeting': typeIcon = 'bi-people'; break;
          case 'Office Hours': typeIcon = 'bi-clock'; break;
          case 'Research': typeIcon = 'bi-search'; break;
          default: typeIcon = 'bi-calendar-event';
        }
        
        scheduleItem.innerHTML = `
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h5 class="card-title mb-0">${item.title}</h5>
              <span class="badge bg-primary">${item.type}</span>
            </div>
            <div class="d-flex align-items-center text-secondary mb-2">
              <i class="bi ${typeIcon} me-2"></i>
              <span>${timeString}</span>
            </div>
            ${item.location ? `
              <div class="d-flex align-items-center text-secondary mb-2">
                <i class="bi bi-geo-alt me-2"></i>
                <span>${item.location}</span>
              </div>
            ` : ''}
            ${item.description ? `
              <p class="card-text mt-2">${item.description}</p>
            ` : ''}
          </div>
        `;
        
        // Add edit capability if own profile
        if (isOwnProfile) {
          scheduleItem.style.cursor = 'pointer';
          scheduleItem.addEventListener('click', function() {
            editSchedule(item);
          });
        }
        
        return scheduleItem;
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
      
      // Show schedule form
      function showScheduleForm(scheduleData = null) {
        if (!scheduleFormContainer) return;
        
        scheduleContainer.style.display = 'none';
        scheduleFormContainer.style.display = 'block';
        
        // Reset form
        scheduleForm.reset();
        document.getElementById('schedule-id').value = '';
        deleteScheduleBtn.style.display = 'none';
        
        // If editing, populate form with schedule data
        if (scheduleData) {
          document.getElementById('form-title').textContent = 'Edit Schedule Item';
          document.getElementById('schedule-id').value = scheduleData._id;
          document.getElementById('schedule-title').value = scheduleData.title;
          document.getElementById('schedule-day').value = scheduleData.day;
          document.getElementById('schedule-type').value = scheduleData.type;
          document.getElementById('schedule-start-time').value = scheduleData.startTime;
          document.getElementById('schedule-end-time').value = scheduleData.endTime;
          document.getElementById('schedule-location').value = scheduleData.location || '';
          document.getElementById('schedule-description').value = scheduleData.description || '';
          deleteScheduleBtn.style.display = 'block';
        } else {
          document.getElementById('form-title').textContent = 'Add Schedule Item';
        }
      }
      
      // Hide schedule form
      function hideScheduleForm() {
        if (!scheduleFormContainer) return;
        
        scheduleFormContainer.style.display = 'none';
        scheduleContainer.style.display = 'block';
      }
      
      // Edit schedule
      function editSchedule(scheduleData) {
        showScheduleForm(scheduleData);
      }
      
      // Save schedule
      function saveSchedule() {
        const scheduleId = document.getElementById('schedule-id').value;
        const scheduleData = {
          userId: userId,
          title: document.getElementById('schedule-title').value,
          day: document.getElementById('schedule-day').value,
          type: document.getElementById('schedule-type').value,
          startTime: document.getElementById('schedule-start-time').value,
          endTime: document.getElementById('schedule-end-time').value,
          location: document.getElementById('schedule-location').value,
          description: document.getElementById('schedule-description').value
        };
        
        if (scheduleId) {
          scheduleData._id = scheduleId;
        }
        
        // Save to server
        const endpoint = scheduleId ? 'update_schedule.php' : 'add_schedule.php';
        fetch(`src/model/${endpoint}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(scheduleData)
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Reload schedule data
            hideScheduleForm();
            loadSchedule();
          } else {
            alert('Error saving schedule: ' + (data.message || 'Unknown error'));
          }
        })
        .catch(error => {
          console.error('Error saving schedule:', error);
          alert('Error saving schedule. Please try again later.');
        });
      }
      
      // Delete schedule
      function deleteSchedule() {
        const scheduleId = document.getElementById('schedule-id').value;
        if (!scheduleId) return;
        
        if (confirm('Are you sure you want to delete this schedule item?')) {
          fetch('src/model/delete_schedule.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
              userId: userId,
              scheduleId: scheduleId 
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Reload schedule data
              hideScheduleForm();
              loadSchedule();
            } else {
              alert('Error deleting schedule: ' + (data.message || 'Unknown error'));
            }
          })
          .catch(error => {
            console.error('Error deleting schedule:', error);
            alert('Error deleting schedule. Please try again later.');
          });
        }
      }
    });
    
    // Faculty ID for API calls
    const facultyId = '<?= (string)$faculty['_id']; ?>';
    
    // Research Fields functionality
    let researchFieldCounter = <?= !empty($faculty['interested_fields_of_research']) ? count($faculty['interested_fields_of_research']) : 0; ?>;
    
    // Prerequisites functionality
    let prerequisiteCounter = <?= !empty($faculty['prerequisites']) ? count($faculty['prerequisites']) : 0; ?>;
    
    // Publications functionality
    let publicationCounter = <?= !empty($faculty['projects']) ? count($faculty['projects']) : 0; ?>;
    
    // Learning Resources functionality
    let resourceCounter = <?= !empty($faculty['resources_to_learn_prerequisites']) ? count($faculty['resources_to_learn_prerequisites']) : 0; ?>;
    
    document.addEventListener('DOMContentLoaded', function() {
        // Add research field functionality
        const addResearchFieldBtn = document.getElementById('addResearchFieldBtn');
        if (addResearchFieldBtn) {
            addResearchFieldBtn.addEventListener('click', addResearchField);
        }
        
        // Remove research field functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-research-field-btn')) {
                e.target.closest('.research-field-block').remove();
            }
        });
        
        // Add prerequisite functionality
        const addPrerequisiteBtn = document.getElementById('addPrerequisiteBtn');
        if (addPrerequisiteBtn) {
            addPrerequisiteBtn.addEventListener('click', addPrerequisite);
        }
        
        // Remove prerequisite functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-prerequisite-btn')) {
                e.target.closest('.prerequisite-block').remove();
            }
        });
        
        // Add publication functionality
        const addPublicationBtn = document.getElementById('addPublicationBtn');
        if (addPublicationBtn) {
            addPublicationBtn.addEventListener('click', addPublication);
        }
        
        // Remove publication functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-publication-btn')) {
                e.target.closest('.publication-block').remove();
            }
        });
        
        // Add resource functionality
        const addResourceBtn = document.getElementById('addResourceBtn');
        if (addResourceBtn) {
            addResourceBtn.addEventListener('click', addResource);
        }
        
        // Remove resource functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-resource-btn')) {
                e.target.closest('.resource-block').remove();
            }
        });
    });
    
    function addResearchField() {
        researchFieldCounter++;
        const container = document.getElementById('researchFieldsContainer');
        const newField = document.createElement('div');
        newField.className = 'mb-3 p-3 border rounded research-field-block';
        newField.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Research Field ${researchFieldCounter}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger remove-research-field-btn">Remove</button>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Field Name</label>
                    <input type="text" class="form-control research-field-name" placeholder="e.g., Machine Learning" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Description</label>
                    <textarea class="form-control research-field-description" rows="2" placeholder="Brief description of the research field"></textarea>
                </div>
            </div>
        `;
        container.appendChild(newField);
    }
    
    function saveResearchFields() {
        const fields = [];
        const fieldBlocks = document.querySelectorAll('.research-field-block');
        
        fieldBlocks.forEach(block => {
            const fieldName = block.querySelector('.research-field-name').value.trim();
            const fieldDescription = block.querySelector('.research-field-description').value.trim();
            if (fieldName) {
                fields.push({
                    name: fieldName,
                    description: fieldDescription
                });
            }
        });
        
        fetch('src/model/update_faculty_section.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                faculty_id: facultyId,
                section: 'interested_fields_of_research',
                data: fields
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal and reload page to show updated data
                const modal = bootstrap.Modal.getInstance(document.getElementById('editResearchFieldsModal'));
                modal.hide();
                location.reload();
            } else {
                alert('Error saving research fields: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error saving research fields:', error);
            alert('Error saving research fields. Please try again.');
        });
    }

    function addPrerequisite() {
        prerequisiteCounter++;
        const container = document.getElementById('prerequisitesContainer');
        const newPrerequisite = document.createElement('div');
        newPrerequisite.className = 'mb-3 p-3 border rounded prerequisite-block';
        newPrerequisite.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Prerequisite ${prerequisiteCounter}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger remove-prerequisite-btn">Remove</button>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Prerequisite Name</label>
                    <input type="text" class="form-control prerequisite-name" placeholder="e.g., Basic knowledge of Python" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Level</label>
                    <select class="form-select prerequisite-level">
                        <option value="Beginner">Beginner</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advanced">Advanced</option>
                    </select>
                </div>
            </div>
            <div class="mt-2">
                <label class="form-label">Description</label>
                <textarea class="form-control prerequisite-description" rows="2" placeholder="Brief description of the prerequisite"></textarea>
            </div>
        `;
        container.appendChild(newPrerequisite);
    }
    
    function savePrerequisites() {
        const prerequisites = [];
        const prerequisiteBlocks = document.querySelectorAll('.prerequisite-block');
        
        prerequisiteBlocks.forEach(block => {
            const prerequisiteName = block.querySelector('.prerequisite-name').value.trim();
            const prerequisiteLevel = block.querySelector('.prerequisite-level').value;
            const prerequisiteDescription = block.querySelector('.prerequisite-description').value.trim();
            if (prerequisiteName) {
                prerequisites.push({
                    name: prerequisiteName,
                    level: prerequisiteLevel,
                    description: prerequisiteDescription
                });
            }
        });
        
        fetch('src/model/update_faculty_section.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                faculty_id: facultyId,
                section: 'prerequisites',
                data: prerequisites
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('editPrerequisitesModal'));
                modal.hide();
                location.reload();
            } else {
                alert('Error saving prerequisites: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error saving prerequisites:', error);
            alert('Error saving prerequisites. Please try again.');
        });
    }
    
    function addPublication() {
        publicationCounter++;
        const container = document.getElementById('publicationsContainer');
        const newPublication = document.createElement('div');
        newPublication.className = 'mb-3 p-3 border rounded publication-block';
        newPublication.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Publication ${publicationCounter}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger remove-publication-btn">Remove</button>
            </div>
            <div class="mb-2">
                <label class="form-label">Title</label>
                <input type="text" class="form-control publication-title" required>
            </div>
            <div class="mb-2">
                <label class="form-label">Description</label>
                <textarea class="form-control publication-description" rows="3"></textarea>
            </div>
            <div class="mb-2">
                <label class="form-label">Link</label>
                <input type="url" class="form-control publication-link" placeholder="https://...">
            </div>
        `;
        container.appendChild(newPublication);
    }
    
    function savePublications() {
        const publications = [];
        const publicationBlocks = document.querySelectorAll('.publication-block');
        
        publicationBlocks.forEach(block => {
            const title = block.querySelector('.publication-title').value.trim();
            const description = block.querySelector('.publication-description').value.trim();
            const link = block.querySelector('.publication-link').value.trim();
            
            if (title) {
                publications.push({
                    title: title,
                    description: description,
                    link: link
                });
            }
        });
        
        fetch('src/model/update_faculty_section.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                faculty_id: facultyId,
                section: 'projects',
                data: publications
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('editPublicationsModal'));
                modal.hide();
                location.reload();
            } else {
                alert('Error saving publications: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error saving publications:', error);
            alert('Error saving publications. Please try again.');
        });
    }

    function addResource() {
        resourceCounter++;
        const container = document.getElementById('resourcesContainer');
        const newResource = document.createElement('div');
        newResource.className = 'mb-3 p-3 border rounded resource-block';
        newResource.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Resource ${resourceCounter}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger remove-resource-btn">Remove</button>
            </div>
            <div class="mb-2">
                <label class="form-label">Topic</label>
                <input type="text" class="form-control resource-topic" required>
            </div>
            <div class="mb-2">
                <label class="form-label">Description</label>
                <textarea class="form-control resource-description" rows="2" placeholder="Brief description of the resource"></textarea>
            </div>
            <div class="mb-2">
                <label class="form-label">Link</label>
                <input type="url" class="form-control resource-link" placeholder="https://...">
            </div>
        `;
        container.appendChild(newResource);
    }
    
    function saveResources() {
        const resources = [];
        const resourceBlocks = document.querySelectorAll('.resource-block');
        
        resourceBlocks.forEach(block => {
            const topic = block.querySelector('.resource-topic').value.trim();
            const description = block.querySelector('.resource-description').value.trim();
            const link = block.querySelector('.resource-link').value.trim();
            
            if (topic) {
                resources.push({
                    topic: topic,
                    description: description,
                    link: link
                });
            }
        });
        
        fetch('src/model/update_faculty_section.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                faculty_id: facultyId,
                section: 'resources_to_learn_prerequisites',
                data: resources
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('editResourcesModal'));
                modal.hide();
                location.reload();
            } else {
                alert('Error saving resources: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error saving resources:', error);
            alert('Error saving resources. Please try again.');
        });
    }
    </script>

    <!-- Edit Research Fields Modal -->
    <div class="modal fade edit-modal" id="editResearchFieldsModal" tabindex="-1" aria-labelledby="editResearchFieldsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content glass-card">
                <div class="modal-header">
                    <h5 class="modal-title" id="editResearchFieldsModalLabel">Edit Research Fields</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="researchFieldsForm">
                        <div id="researchFieldsContainer">
                            <?php if (!empty($faculty['interested_fields_of_research'])): ?>
                                <?php foreach ($faculty['interested_fields_of_research'] as $index => $field): ?>
                                    <div class="mb-3 p-3 border rounded research-field-block">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0">Research Field <?= $index + 1; ?></h6>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-research-field-btn">Remove</button>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Field Name</label>
                                                <input type="text" class="form-control research-field-name" value="<?= htmlspecialchars($field['name']); ?>" placeholder="e.g., Machine Learning" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Description</label>
                                                <textarea class="form-control research-field-description" rows="2" placeholder="Brief description of the research field"><?= htmlspecialchars($field['description']); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-outline-primary" id="addResearchFieldBtn">
                            <i class="bi bi-plus-lg"></i> Add Research Field
                        </button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveResearchFields()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Publications Modal -->
    <div class="modal fade edit-modal" id="editPublicationsModal" tabindex="-1" aria-labelledby="editPublicationsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content glass-card">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPublicationsModalLabel">Edit Publications</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="publicationsForm">
                        <div id="publicationsContainer">
                            <?php if (!empty($faculty['projects'])): ?>
                                <?php foreach ($faculty['projects'] as $index => $project): ?>
                                    <div class="mb-3 p-3 border rounded publication-block">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0">Publication <?= $index + 1; ?></h6>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-publication-btn">Remove</button>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Title</label>
                                            <input type="text" class="form-control publication-title" value="<?= htmlspecialchars($project['title'] ?? ''); ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control publication-description" rows="3"><?= htmlspecialchars($project['description'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Link</label>
                                            <input type="url" class="form-control publication-link" value="<?= htmlspecialchars($project['link'] ?? ''); ?>" placeholder="https://...">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-outline-primary" id="addPublicationBtn">
                            <i class="bi bi-plus-lg"></i> Add Publication
                        </button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="savePublications()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Prerequisites Modal -->
    <div class="modal fade edit-modal" id="editPrerequisitesModal" tabindex="-1" aria-labelledby="editPrerequisitesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content glass-card">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPrerequisitesModalLabel">Edit Prerequisites</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="prerequisitesForm">
                        <div id="prerequisitesContainer">
                            <?php if (!empty($faculty['prerequisites'])): ?>
                                <?php foreach ($faculty['prerequisites'] as $index => $prerequisite): ?>
                                    <div class="mb-3 p-3 border rounded prerequisite-block">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0">Prerequisite <?= $index + 1; ?></h6>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-prerequisite-btn">Remove</button>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Prerequisite Name</label>
                                                <input type="text" class="form-control prerequisite-name" value="<?= htmlspecialchars($prerequisite['name']); ?>" placeholder="e.g., Basic knowledge of Python" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Level</label>
                                                <select class="form-select prerequisite-level">
                                                    <option value="Beginner" <?= $prerequisite['level'] === 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                                                    <option value="Intermediate" <?= $prerequisite['level'] === 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                                    <option value="Advanced" <?= $prerequisite['level'] === 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control prerequisite-description" rows="2" placeholder="Brief description of the prerequisite"><?= htmlspecialchars($prerequisite['description']); ?></textarea>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-outline-primary" id="addPrerequisiteBtn">
                            <i class="bi bi-plus-lg"></i> Add Prerequisite
                        </button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="savePrerequisites()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Learning Resources Modal -->
    <div class="modal fade edit-modal" id="editResourcesModal" tabindex="-1" aria-labelledby="editResourcesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content glass-card">
                <div class="modal-header">
                    <h5 class="modal-title" id="editResourcesModalLabel">Edit Learning Resources</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="resourcesForm">
                        <div id="resourcesContainer">
                            <?php 
                            $resources = $faculty['resources_to_learn_prerequisites'] ?? [];
                            if ($resources instanceof \MongoDB\Model\BSONArray) {
                                $resources = (array)$resources;
                            }
                            if (!empty($resources)): ?>
                                <?php foreach ($resources as $index => $resource): ?>
                                    <div class="mb-3 p-3 border rounded resource-block">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0">Resource <?= $index + 1; ?></h6>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-resource-btn">Remove</button>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Topic</label>
                                            <input type="text" class="form-control resource-topic" value="<?= htmlspecialchars($resource['topic'] ?? ''); ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control resource-description" rows="2" placeholder="Brief description of the resource"><?= htmlspecialchars($resource['description'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Link</label>
                                            <input type="url" class="form-control resource-link" value="<?= htmlspecialchars($resource['link'] ?? ''); ?>" placeholder="https://...">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-outline-primary" id="addResourceBtn">
                            <i class="bi bi-plus-lg"></i> Add Resource
                        </button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveResources()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>