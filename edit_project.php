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

// Define a constant to indicate this is the edit_project.php file
// This is used by included files like timeline_editor_overlay.php
define('INCLUDED_IN_EDIT_PROJECT', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project | UIU Research Portal</title>
    
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/styles/home.css">
    <link rel="stylesheet" href="assets/styles/edit-project.css">
</head>
<body>
    <?php include 'src/includes/navbar.php'; ?>
    
    <!-- Background Effects -->
    <div class="background-effects">
        <div class="cyber-grid"></div>
        <div class="floating-orb orb-1"></div>
        <div class="floating-orb orb-2"></div>
        <div class="floating-orb orb-3"></div>
    </div>
    
    <!-- Particles Background -->
    <div id="particles-js"></div>
    
    <!-- Loading Spinner -->
    <div class="spinner-overlay" id="spinner">
        <div class="spinner"></div>
    </div>
    
    <!-- Toast Notifications -->
    <div class="toast-container" id="toastContainer"></div>
    
    <div class="header-container">
        <div class="container text-center hero-content">
            <h1 class="hero-title" data-aos="fade-down" data-aos-duration="1000">Edit <span class="text-gradient" data-text="Project">Project</span></h1>
            <p data-aos="fade-up" data-aos-duration="1000" data-aos-delay="300">Update your research project details and share your progress with the academic community.</p>
        </div>
    </div>
    
    <div class="container my-5" style="margin-top: 40px !important; padding-top: 20px;">
        <div class="row">
            <div class="col-lg-12">
                <div class="card mt-3">
                    <div class="card-header">
                        <i class="bi bi-pencil-square me-2"></i>Edit Project Details
                    </div>
                    <div class="card-body">
                        <div id="projectLoadingState">
                            <div class="text-center p-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-3">Loading project details...</p>
                            </div>
                        </div>
                        
                        <div id="projectNotFound" style="display: none;">
                            <div class="text-center p-5">
                                <i class="bi bi-exclamation-circle text-warning" style="font-size: 3rem;"></i>
                                <h4 class="mt-3">Project Not Found</h4>
                                <p class="text-muted">The project you're looking for doesn't exist or you don't have permission to edit it.</p>
                                <a href="project_management.php" class="btn btn-primary mt-3">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Projects
                                </a>
                            </div>
                        </div>

                        <form id="editProjectForm" enctype="multipart/form-data" style="display: none;">
                            <!-- Form fields will be added here through JavaScript -->
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'src/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script src="assets/js/edit-project.js"></script>
    
    <!-- Show timeout message if redirected due to inactivity -->
    <?php
    if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
        echo '<div class="alert alert-warning text-center" style="margin: 20px;">Your session has expired due to inactivity. Please log in again.</div>';
    }
    ?>
    
    <!-- Include Timeline Editor Overlay -->
    <?php include 'timeline_editor_overlay.php'; ?>
</body>
</html>
