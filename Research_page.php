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

// Session idle timeout (30 seconds)
$timeout = 30; // 30 seconds
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

// Add recommendation engine includes
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/model/recommendation_engine.php';
require_once __DIR__ . '/src/model/user_preferences.php';

// Initialize recommendation engine
$recommendationEngine = new RecommendationEngine();
$isPersonalized = false;

// Check if user has personalized data
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $recommendations = $recommendationEngine->getDashboardRecommendations($userId);
    $isPersonalized = $recommendations['is_personalized'] ?? false;
}



// Get search parameter from URL if it exists
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$sortOption = isset($_GET['sort']) ? $_GET['sort'] : (isset($_SESSION['user_id']) ? 'recommended' : 'newest');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Projects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/styles/home.css">
    <link rel="stylesheet" href="assets/styles/theme.css">
    <!-- Performance optimization styles -->
    <link rel="stylesheet" href="assets/styles/performance.css">
    <link rel="stylesheet" href="assets/styles/research-page.css">
    
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
    
    <!-- Performance optimization script - Load early for immediate optimizations -->
    <script src="assets/js/performance-optimizer.js" defer></script>
    
    
</head>
<body class="research-page" data-show-recommended="<?php echo (isset($_SESSION['user_id']) && $sortOption === 'recommended') ? 'true' : 'false'; ?>">
  <?php include 'src/includes/navbar.php'; ?>

    <!-- Enhanced Background Effects -->
    <div class="background-effects">
        <div class="cyber-grid"></div>
        <div class="floating-orb orb-1"></div>
        <div class="floating-orb orb-2"></div>
        <div class="floating-orb orb-3"></div>
    </div>
    
    <!-- Enhanced Particles -->
  <div id="particles-js"></div>
  
    <!-- Hero Section -->
    <section class="hero-section" id="hero-section">
        <!-- Dynamic Background Layers -->
        <div class="hero-dynamic-bg">
            <div class="bg-layer-1"></div>
            <div class="bg-layer-2"></div>
            <div class="bg-layer-3"></div>
    </div>
    
        <div class="container hero-content">
      <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <h1 class="hero-title" data-aos="fade-down" data-aos-duration="1000">
                        Discover Innovative Research
                    </h1>
                    <p class="hero-subtitle mb-3" data-aos="fade-up" data-aos-delay="200" style="font-size: 1.3rem; color: var(--text-secondary); max-width: 700px; margin: 0 auto 1rem; line-height: 1.6;">
                        Explore groundbreaking research projects from brilliant minds around the world
                    </p>
                    <?php if (isset($_SESSION['user_id']) && $sortOption === 'recommended'): ?>
                    <p class="personalized-subtitle mb-5" data-aos="fade-up" data-aos-delay="300" style="font-size: 1rem; color: var(--text-muted); max-width: 600px; margin: 0 auto 3rem; text-align: center;">
                        <?php if ($isPersonalized): ?>
                            <i class="fas fa-star" style="color: var(--modern-blue); margin-right: 8px;"></i>
                            Showing projects tailored to your interests and activity
                        <?php else: ?>
                            <small>Keep interacting with projects to get personalized recommendations!</small>
                        <?php endif; ?>
                    </p>
                    <?php else: ?>
                    <div style="margin-bottom: 2rem;"></div>
                    <?php endif; ?>
                    
                    <!-- Compact Search Bar -->
                    <div class="compact-search-container" data-aos="fade-up" data-aos-delay="300">
                        <div class="search-box-compact">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-search search-icon-compact"></i>
                                <input type="text" class="search-input-compact" placeholder="Search projects, keywords, or authors..." id="search-bar" autocomplete="off" value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>">
                                <button class="search-btn-compact" id="search-bttn">
                                    <i class="fas fa-arrow-right"></i>
            </button>
          </div>
        </div>
                        
                        <div class="text-center mt-3 d-flex justify-content-center gap-3 flex-wrap">
                            <?php if (isset($_SESSION['user_id'])): ?>
                            <button class="toggle-btn-compact" id="toggle-recommended">
                                <i class="fas fa-star me-2"></i>
                                <?php if ($isPersonalized): ?>
                                    Recommended for You
                                <?php else: ?>
                                    Recommended for You
                                <?php endif; ?>
                            </button>
                            <?php endif; ?>
                            <button class="toggle-btn-compact" id="toggle-sort">
                                <i class="fas fa-sort me-2"></i>Sort by Views
                            </button>
                            <button class="toggle-btn-compact" id="toggle-projects">
                                <i class="fas fa-filter me-2"></i>Show All Projects
                            </button>
                        </div>
    </div>
  </div>
    </div>
    </div>
  </section>

    <!-- Projects Section -->
    <section class="container my-5">
        <!-- Loading Animation -->
        <div class="loading-container" id="loader" style="display: none;">
            <div class="loading-spinner">
                <div class="spinner-ring"></div>
                <div class="spinner-ring"></div>
                <div class="spinner-ring"></div>
    </div>
            </div>
            
        <!-- Projects Grid -->
        <div class="projects-grid" id="projectsList">
            <!-- Projects will be dynamically loaded here -->
                    </div>
    </section>

    <?php include 'src/includes/footer.php'; ?>

    <!-- Scripts -->
    
  
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script src="assets/js/research-page.js"></script>
    
</body>
</html>
