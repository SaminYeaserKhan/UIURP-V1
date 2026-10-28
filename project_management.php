<?php
// Set secure session cookie parameters before session_start()
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => $cookieParams['lifetime'],
    'path' => $cookieParams['path'],
    'domain' => $cookieParams['domain'],
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();
// Session idle timeout (30 seconds)
$timeout = 30; // 30 seconds
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();
// Show timeout message if redirected due to inactivity
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    echo '<div class="alert alert-warning text-center" style="margin: 20px;">Your session has expired due to inactivity. Please log in again.</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Projects | UIU Research Portal</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="assets/styles/home.css">
    <link rel="stylesheet" href="assets/styles/theme.css">
    <link rel="stylesheet" href="assets/styles/project_management.css">
    
    <!-- Prevent Theme Flash Script - Must run immediately -->
    <script>
    (function() {
        // Get saved theme immediately to prevent flash - with default for Safari/Mac
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
        // Force update for Safari
        document.documentElement.style.setProperty('color-scheme', savedTheme);
        
        // Webkit-specific fix for Safari on Mac
        if (savedTheme === 'dark') {
            document.documentElement.style.background = 'linear-gradient(135deg, #0a0d1a 0%, #1a1a2e 50%, #16213e 100%)';
        } else {
            document.documentElement.style.background = 'linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #cbd5e1 100%)';
        }
    })();
    </script>
</head>
<body>
    <?php include 'src/includes/navbar.php'; ?>
    
    <div class="background-effects">
        <div class="floating-orb orb-1"></div>
        <div class="floating-orb orb-2"></div>
        <div class="floating-orb orb-3"></div>
        <div class="cyber-grid"></div>
    </div>
    
    <div id="particles-js"></div>
    
    <div class="spinner-overlay" id="spinner">
        <div class="spinner"></div>
    </div>
    
    <div class="toast-container" id="toastContainer"></div>
    
    <div class="header-container">
        <div class="container text-center hero-content">
            <h1 class="hero-title" data-aos="fade-down" data-aos-duration="1000">Edit <span class="text-gradient" data-text="Research">Research</span> Projects</h1>
            <p data-aos="fade-up" data-aos-duration="1000" data-aos-delay="300">Create, edit, and share your groundbreaking research with the academic community.</p>
        </div>
    </div>
    
    <div class="container my-5" style="margin-top: 40px !important; padding-top: 20px;">
        <div class="tabs-wrapper" style="padding-top: 60px; position: relative; z-index: 100;">
            <ul class="nav nav-tabs" id="projectManagementTabs" role="tablist" style="margin-top: 40px;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="edit-projects-tab" data-bs-toggle="tab" data-bs-target="#edit-projects" type="button" role="tab" aria-controls="edit-projects" aria-selected="true">
                        <i class="bi bi-collection me-2"></i>My Projects
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="new-project-tab" data-bs-toggle="tab" data-bs-target="#new-project" type="button" role="tab" aria-controls="new-project" aria-selected="false">
                        <i class="bi bi-plus-circle me-2"></i>Create New Project
                    </button>
                </li>
            </ul>
        </div>
        
        <div class="tab-content" id="projectManagementTabContent">
            <div class="tab-pane fade show active" id="edit-projects" role="tabpanel" aria-labelledby="edit-projects-tab">
                <div id="userProjectsList" class="row g-4">
                    </div>
            </div>
            
            <div class="tab-pane fade" id="new-project" role="tabpanel" aria-labelledby="new-project-tab">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card mt-3">
                            <div class="card-header">
                                <i class="bi bi-file-earmark-plus me-2"></i>New Research Project
                            </div>
                            <div class="card-body">
                                <form id="projectForm" enctype="multipart/form-data">
                                    <input type="hidden" id="projectId" name="projectId" value="">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-8">
                                            <div class="mb-2">
                                                <label for="title" class="form-label">Project Title*</label>
                                                <input type="text" class="form-control" id="title" name="title" required>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <label for="abstract" class="form-label">Abstract*</label>
                                                <textarea class="form-control" id="abstract" name="abstract" rows="3" required></textarea>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <label for="description" class="form-label">Full Description</label>
                                                <textarea class="form-control" id="description" name="description" rows="5"></textarea>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="coverImage" class="form-label">Cover Image</label>
                                                <div class="file-upload">
                                                    <div class="file-upload-btn" id="coverImageBtn">
                                                        <i class="bi bi-cloud-arrow-up"></i>
                                                        <p>Click or drag to upload an image</p>
                                                    </div>
                                                    <input type="file" class="form-control" id="coverImage" name="coverImage" accept="image/*">
                                                </div>
                                                <div id="imagePreviewContainer" class="mt-3 text-center" style="display: none;">
                                                    <img id="imagePreview" class="preview-image">
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
                                                <input type="text" class="form-control" id="field" name="field" required>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="institution" class="form-label">Institution</label>
                                                <input type="text" class="form-control" id="institution" name="institution" value="United International University">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
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
                                                    </div>
                                                <input type="hidden" id="keywordsList" name="keywords">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3 dropdown-container">
                                                <label for="privacy" class="form-label">Privacy Setting</label>
                                                <select class="form-select" id="privacy" name="privacy">
                                                    <option value="0">Public - Visible to everyone</option>
                                                    <option value="1">Private - Visible only to you and collaborators</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="createdAt" class="form-label">Created At</label>
                                                <input type="date" class="form-control" id="createdAt" name="createdAt">
                                                <small class="text-muted">Leave empty for current date</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="updatedAt" class="form-label">Updated At</label>
                                                <input type="date" class="form-control" id="updatedAt" name="updatedAt">
                                                <small class="text-muted">Leave empty for current date</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="estimatedCompletionDate" class="form-label">Estimated Completion Date</label>
                                                <input type="date" class="form-control" id="estimatedCompletionDate" name="estimatedCompletionDate">
                                                <small class="text-muted">Expected date when the project will be completed</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <div class="card">
                                                <div class="card-header">
                                                    <i class="bi bi-link-45deg me-2"></i>External Links
                                                </div>
                                                <div class="card-body pb-2">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-2">
                                                                <label for="github" class="form-label">GitHub Repository URL</label>
                                                                <input type="url" class="form-control" id="github" name="github" placeholder="https://github.com/yourusername/your-repo">
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="website" class="form-label">Project Website URL</label>
                                                                <input type="url" class="form-control" id="website" name="website" placeholder="https://yourproject.example.com">
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="paper" class="form-label">Research Paper URL</label>
                                                                <input type="url" class="form-control" id="paper" name="paper" placeholder="https://journal.example.com/your-paper">
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="doi" class="form-label">DOI</label>
                                                                <input type="text" class="form-control" id="doi" name="doi" placeholder="10.xxxx/xxxxx">
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="youtube" class="form-label">YouTube Video URL</label>
                                                                <input type="url" class="form-control" id="youtube" name="youtube" placeholder="https://youtube.com/watch?v=xxxx">
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
                                                    <div class="mb-3 dropdown-container">
                                                        <label for="supervisor" class="form-label">Project Supervisor</label>
                                                        <div class="supervisor-search-container position-relative">
                                                            <input type="text" class="form-control" id="supervisor" name="supervisor" 
                                                                   placeholder="Type to search faculty..." autocomplete="off">
                                                            <input type="hidden" id="supervisorId" name="supervisorId">
                                                            <div class="supervisor-dropdown" id="supervisorDropdown">
                                                                </div>
                                                        </div>
                                                        <div class="form-text">Choose a faculty member to supervise this project</div>
                                                    </div>
                                                    
                                                    <label class="form-label">Team Members</label>
                                                    <div id="membersContainer">
                                                        </div>
                                                    <button type="button" class="btn btn-outline-primary mt-2" id="addMember">
                                                        <i class="bi bi-plus-circle me-2"></i>Add Team Member
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="card">
                                                <div class="card-header">
                                                    <i class="bi bi-calendar-event me-2"></i>Project Timeline
                                                </div>
                                                <div class="card-body">
                                                    <p class="text-muted mb-3">Add key milestones and events to track your project's progress.</p>
                                                    
                                                    <div id="timelineContainer">
                                                        </div>
                                                    
                                                    <button type="button" class="btn btn-outline-primary mt-3" id="addTimelineItem">
                                                        <i class="bi bi-plus-circle me-2"></i>Add Timeline Item
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
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
                                                        <div id="filesPreview" class="mt-2"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
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
                                                        <div id="mediaPreview" class="mt-2 row g-2"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="card">
                                                <div class="card-header">
                                                    <i class="bi bi-journal-text me-2"></i>References
                                                </div>
                                                <div class="card-body">
                                                    <div class="mb-3">
                                                        <div id="references-container">
                                                            </div>
                                                        <div class="row mt-3">
                                                            <div class="col-md-5">
                                                                <input type="text" class="form-control" id="reference-title" placeholder="Reference Title">
                                                            </div>
                                                            <div class="col-md-5">
                                                                <input type="text" class="form-control" id="reference-link" placeholder="Reference Link">
                                                            </div>
                                                            <div class="col-md-2">
                                                                <button type="button" class="btn btn-primary w-100" id="add-reference-btn">
                                                                    <i class="bi bi-plus-circle"></i> Add
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <input type="hidden" id="references" name="references">
                                                        <div class="form-text">
                                                            Add each reference with a title and a link. Example: "Deep Learning for Renewable Energy Forecasting" with link "https://doi.org/10.1016/j.rser.2020.109898"
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" id="viewsCount" name="viewsCount">
                                    <input type="hidden" id="downloadsCount" name="downloadsCount">
                                    <input type="hidden" id="favoritesCount" name="favoritesCount">
                                    
                                    <input type="hidden" id="commentsArray" name="commentsArray" value="[]">
                                    
                                    <div class="text-end mt-3">
                                        <button type="button" class="btn btn-outline-secondary me-2" id="resetForm">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save me-1"></i>Save Project
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'src/includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    
    <script src="assets/js/project_management.js"></script>
    
    <?php include 'src/includes/global-meeting-notifications.php'; ?>
</body>
</html>