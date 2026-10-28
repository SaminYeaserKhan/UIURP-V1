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

// Include autoloader for MongoDB and recommendation engine
require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/model/recommendation_engine.php';
require_once __DIR__ . '/src/model/user_preferences.php';

// Initialize recommendation engine
$recommendationEngine = new RecommendationEngine();

// Get personalized recommendations if user is logged in
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $recommendations = $recommendationEngine->getDashboardRecommendations($userId);
    
    $projects = $recommendations['projects'];
    $events = $recommendations['events'];
    $forumPosts = $recommendations['forum_posts'];
    $faculties = $recommendations['faculties'];
    
    $isPersonalized = $recommendations['is_personalized'] ?? false;
    
    // Create combined recommendations array for "For You" section
    $combinedRecommendations = [];
    
    // Add projects to combined array with content type
    foreach ($projects as $item) {
        $item['content_type'] = 'project';
        $combinedRecommendations[] = $item;
    }
    
    // Add events to combined array with content type
    foreach ($events as $item) {
        $item['content_type'] = 'event';
        $combinedRecommendations[] = $item;
    }
    
    // Add forum posts to combined array with content type
    foreach ($forumPosts as $item) {
        $item['content_type'] = 'forum_post';
        $combinedRecommendations[] = $item;
    }
    
    // Add faculties to combined array with content type
    foreach ($faculties as $item) {
        $item['content_type'] = 'faculty';
        $combinedRecommendations[] = $item;
    }
    
    // Sort by relevance score if personalized
    if ($isPersonalized) {
        usort($combinedRecommendations, function($a, $b) {
            $scoreA = $a['relevance_score'] ?? 0;
            $scoreB = $b['relevance_score'] ?? 0;
            return $scoreB <=> $scoreA; // Sort in descending order
        });
    }
} else {
    // Fallback to recent content for non-logged-in users
    $projects = [];
    $events = [];
    $forumPosts = [];
    $faculties = [];
    $combinedRecommendations = [];
    $isPersonalized = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UIU Research Portal</title>
    
    <!-- Core styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <!-- Animation libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <!-- Custom styles -->
    <link rel="stylesheet" href="assets/styles/home.css">
    <link rel="stylesheet" href="assets/styles/theme.css">
    <!-- Performance optimization styles -->
    <link rel="stylesheet" href="assets/styles/performance.css">
    <link rel="stylesheet" href="assets/styles/index-inline-extracted.css">
    <script>
    (function() {
        // Get saved theme immediately to prevent flash
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }
    })();
    </script>
    
    <!-- Performance optimization script - Load early for immediate optimizations -->
    <script src="assets/js/performance-optimizer.js" defer></script>
    
    <!-- Preload fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    
    
</head>
<body>
    <!-- Scroll navigation dots -->
    <div class="scroll-navigation">
        <div class="scroll-dot" data-section="Search" data-index="0"></div>
        <div class="scroll-dot" data-section="Hero" data-index="1"></div>
        <div class="scroll-dot" data-section="Featured Research" data-index="3"></div>
        <div class="scroll-dot" data-section="Faculty Spotlight" data-index="4"></div>
        <div class="scroll-dot" data-section="Research Events" data-index="5"></div>
        <div class="scroll-dot" data-section="Research Guidance" data-index="6"></div>
        <div class="scroll-dot" data-section="Footer" data-index="7"></div>
    </div>
    <!-- Preloader -->
    <div class="preloader">
        <div class="loading-animation"></div>
    </div>

    <!-- Background elements -->
    <div class="bg-gradient"></div>
    <div class="floating-shape shape-1 animate-float"></div>
    <div class="floating-shape shape-2 animate-float"></div>
    <div class="floating-shape shape-3 animate-float"></div>

  <?php 
        // Include navbar
        $navbar_path = 'src/includes/navbar.php';
        if (file_exists($navbar_path)) {
            include $navbar_path; 
        } else {
            echo "<div class='alert alert-danger'>Navigation menu file not found at: $navbar_path</div>";
        }
    ?>
    

        <!-- Search Section -->
    <section class="search-section position-relative vh-100">
        <div class="search-particles" id="search-particles"></div>
        <div class="search-blur-effect"></div>
        <div class="search-glow"></div>
        <div class="container position-relative h-100 d-flex flex-column justify-content-center">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="search-container" data-aos="fade-up" data-aos-duration="1200">
                        <div class="neo-badge mb-3 mx-auto text-center" style="max-width: fit-content;">
                            <span class="badge-text">UIU Research Portal</span>
                            <div class="badge-glow"></div>
                        </div>
                        
                        <div class="search-header text-center mb-3">
                            <h1 class="display-4 fw-bold mb-2" style="color: var(--text-primary); transition: color 0.4s ease;">Find Your Research Interests</h1>
                            <p class="lead" style="color: var(--text-secondary); transition: color 0.4s ease;">Discover projects aligned with your academic pursuits</p>
                        </div>
                        
                        <div class="futuristic-search-bar">
                            <div class="search-icon-container">
                                <i class="bi bi-search"></i>
                            </div>
                            <input type="text" placeholder="Search for research topics, keywords, or faculty..." id="search-bar" class="form-control">
                            <button id="search-bttn" class="search-button">
                                <span>Search</span>
                                <div class="search-button-effect"></div>
                                <div class="search-button-glow"></div>
        </button>
                            <div class="search-bar-glow"></div>
                        </div>
    
                        <div class="interactive-tag-cloud mt-4" id="keywordsList">
        <!-- Keywords will be dynamically added here -->
                            <div class="popular-searches">
                                <span class="popular-label">Popular Searches:</span>
                            </div>
                            <!-- Placeholder tags that will be replaced by dynamic content -->
                            <span class="keyword-tag">Machine Learning</span>
                            <span class="keyword-tag">Blockchain</span>
                            <span class="keyword-tag">Cybersecurity</span>
                        </div>
                    </div>
    </div>
  </div>

                                        <!-- Scroll Down Indicator -->
            <div class="scroll-down-container">
                <div class="scroll-down-text" style="color: var(--text-secondary); transition: color 0.4s ease;">Discover More</div>
                <div class="scroll-down-arrow" id="scrollToHero" style="color: var(--text-primary); transition: color 0.4s ease;">
                    <i class="bi bi-chevron-down"></i>
                </div>
            </div>
        </div>
        <div class="search-grid-overlay"></div>
    </section>
    
    <!-- Futuristic Hero Section -->
    <section class="neo-hero-section vh-100" id="hero-section">
        <!-- Animated Background Elements -->
        <div class="neo-hero-bg">
            <div class="neo-particles-container" id="particles-js"></div>
            <div class="neo-glow-orb orb-1"></div>
            <div class="neo-glow-orb orb-2"></div>
            <div class="neo-glow-orb orb-3"></div>
            <div class="neo-grid"></div>
            <div class="neo-circuit-lines">
                <div class="circuit-line line-1"></div>
                <div class="circuit-line line-2"></div>
                <div class="circuit-line line-3"></div>
                <div class="circuit-line line-4"></div>
                <div class="circuit-dot dot-1"></div>
                <div class="circuit-dot dot-2"></div>
                <div class="circuit-dot dot-3"></div>
      </div>
        </div>

        <div class="container position-relative">
            <div class="row h-100 align-items-center">
                <!-- Text Content -->
                <div class="col-lg-6">
                    <div class="neo-hero-content" data-aos="fade-up">
                        <div class="neo-badge">
                            <span class="badge-text">Explore Research</span>
                            <div class="badge-glow"></div>
                        </div>
                        
                        <h1 class="neo-hero-title">
                            <span class="title-line" data-aos="fade-right" data-aos-delay="100">Discover</span>
                            <span class="title-line" data-aos="fade-right" data-aos-delay="300">groundbreaking</span>
                            <span class="title-line gradient-text" data-aos="fade-right" data-aos-delay="500">research <span class="hero-text-uiu">at UIU</span></span>
                        </h1>
                        
                        <p class="neo-hero-subtitle" data-aos="fade-up" data-aos-delay="700">
                            Connect with innovative researchers, explore cutting-edge projects, and collaborate on ideas that shape the future.
                        </p>
                        
                        <div class="neo-hero-buttons" data-aos="fade-up" data-aos-delay="900">
                            <a href="Research_page.php" class="neo-button primary">
                                <span class="button-content">Explore Projects</span>
                                <span class="button-icon"><i class="bi bi-search"></i></span>
                                <span class="button-glow"></span>
                            </a>
                            <a href="project_management.php#new-project" class="neo-button secondary">
                                <span class="button-content">Start Research</span>
                                <span class="button-icon"><i class="bi bi-plus-circle"></i></span>
                                <span class="button-border"></span>
                            </a>
                        </div>
                        
                        <!-- Research Stats -->
                        <div class="neo-stats-container" data-aos="fade-up" data-aos-delay="1100">
                            <div class="stats-header">
                                <span class="stats-icon"><i class="bi bi-graph-up-arrow"></i></span>
                                <h6>Research Impact</h6>
                            </div>
                            
                            <div class="neo-stats-grid">
                                <div class="neo-stat-item" data-aos="zoom-in" data-aos-delay="1200">
                                    <div class="stat-value" data-counter="250">0</div>
                                    <div class="stat-label">Projects</div>
                                    <div class="stat-icon"><i class="bi bi-folder-fill"></i></div>
                                </div>
                                
                                <div class="neo-stat-item" data-aos="zoom-in" data-aos-delay="1300">
                                    <div class="stat-value" data-counter="120">0</div>
                                    <div class="stat-label">Researchers</div>
                                    <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                                </div>
                                
                                <div class="neo-stat-item" data-aos="zoom-in" data-aos-delay="1400">
                                    <div class="stat-value" data-counter="85">0</div>
                                    <div class="stat-label">Publications</div>
                                    <div class="stat-icon"><i class="bi bi-journal-text"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Bulletin Board -->
                <div class="col-lg-6 position-relative d-none d-lg-block">
                    <div class="bulletin-board-container" style="margin-left: 2rem; margin-top: 2rem; transform: translateX(20px);">
                        <div class="bulletin-board-header">
                            <div class="board-title">
                                <i class="bi bi-pin-angle-fill"></i>
                                <span>
                                    <?php if ($isPersonalized): ?>
                                        Bulletin Board
                                        <span class="personalized-badge" style="color: var(--neo-blue); font-size: 0.7rem; margin-left: 0.5rem;">
                                            <i class="bi bi-stars"></i>
                                        </span>
                                    <?php else: ?>
                                        Bulletin Board
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="bulletin-board-content">
                            <?php if (isset($_SESSION['user_id']) && !empty($combinedRecommendations)): ?>
                                <!-- Display personalized content -->
                                <?php 
                                $displayCount = min(count($combinedRecommendations), 20); // Show up to 20 items
                                for ($i = 0; $i < $displayCount; $i++): 
                                    $item = $combinedRecommendations[$i];
                                    $contentType = $item['content_type'];
                                    $hasRelevanceScore = isset($item['relevance_score']) && $item['relevance_score'] > 0;
                                ?>
                                    <div class="bulletin-item<?= $hasRelevanceScore ? ' recommended' : '' ?>" data-item-type="<?= $contentType ?>" data-item-id="<?= $item['_id'] ?>">
                                        <?php if ($contentType === 'project'): ?>
                                        <a href="Project_details.php?id=<?= $item['_id'] ?>" class="bulletin-link">
                                            <div class="bulletin-content-badge project">Project</div>
                                            <div class="bulletin-title"><?= htmlspecialchars(substr($item['title'] ?? 'Untitled Project', 0, 40)) ?><?= strlen($item['title'] ?? '') > 40 ? '...' : '' ?></div>
                                            <div class="bulletin-description"><?= htmlspecialchars(substr($item['description'] ?? 'No description available', 0, 80)) ?><?= strlen($item['description'] ?? '') > 80 ? '...' : '' ?></div>
                                            
                                            <?php if (isset($item['tags']) && is_array($item['tags']) && !empty($item['tags'])): ?>
                                            <div class="bulletin-tags">
                                                <?php foreach (array_slice($item['tags'], 0, 2) as $tag): ?>
                                                <span class="bulletin-tag"><?= htmlspecialchars($tag) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div class="bulletin-meta">
                                                <div class="bulletin-meta-item">
                                                    <i class="bi bi-calendar3"></i>
                                                    <?php 
                                                    if (isset($item['createdAt'])) {
                                                        if (is_object($item['createdAt']) && method_exists($item['createdAt'], 'toDateTime')) {
                                                            echo $item['createdAt']->toDateTime()->format('M j');
                                                        } else {
                                                            echo 'Recent';
                                                        }
                                                    } else {
                                                        echo 'Recent';
                                                    }
                                                    ?>
                                                </div>
                                                <div class="bulletin-meta-item">
                                                    <i class="bi bi-people"></i>
                                                    <?= isset($item['members']) ? count($item['members']) : '1' ?> members
                                                </div>
                                            </div>
                                        </a>
                                        
                                        <?php elseif ($contentType === 'event'): ?>
                                        <a href="events.php#event-<?= $item['_id'] ?>" class="bulletin-link">
                                            <div class="bulletin-content-badge event">Event</div>
                                            <div class="bulletin-title"><?= htmlspecialchars(substr($item['title'] ?? 'Untitled Event', 0, 40)) ?><?= strlen($item['title'] ?? '') > 40 ? '...' : '' ?></div>
                                            <div class="bulletin-description"><?= htmlspecialchars(substr($item['description'] ?? 'No description available', 0, 80)) ?><?= strlen($item['description'] ?? '') > 80 ? '...' : '' ?></div>
                                            
                                            <div class="bulletin-meta">
                                                <div class="bulletin-meta-item">
                                                    <i class="bi bi-calendar3"></i>
                                                    <?php 
                                                    if (isset($item['eventDate'])) {
                                                        if (is_array($item['eventDate']) && isset($item['eventDate']['$date'])) {
                                                            $timestamp = $item['eventDate']['$date']['$numberLong'] ?? $item['eventDate']['$date'];
                                                            echo date('M j, Y', $timestamp / 1000);
                                                        } else {
                                                            echo 'TBD';
                                                        }
                                                    } else {
                                                        echo 'TBD';
                                                    }
                                                    ?>
                                                </div>
                                                <?php if (isset($item['location']) && !empty($item['location'])): ?>
                                                <div class="bulletin-meta-item">
                                                    <i class="bi bi-geo-alt"></i>
                                                    <?= htmlspecialchars(substr($item['location'], 0, 15)) ?>
                                                </div>
                                                <?php endif; ?>
                                                <div class="bulletin-status-badge bulletin-status-<?= strtolower($item['status'] ?? 'upcoming') ?>">
                                                    <?= htmlspecialchars($item['status'] ?? 'Upcoming') ?>
                                                </div>
                                            </div>
                                        </a>
                                        
                                        <?php elseif ($contentType === 'forum_post'): ?>
                                        <a href="post_details.php?id=<?= $item['_id'] ?>" class="bulletin-link">
                                            <div class="bulletin-content-badge discussion">Discussion</div>
                                            <div class="bulletin-title"><?= htmlspecialchars(substr($item['title'] ?? 'Untitled Post', 0, 40)) ?><?= strlen($item['title'] ?? '') > 40 ? '...' : '' ?></div>
                                            <div class="bulletin-description"><?= htmlspecialchars(substr(strip_tags($item['content'] ?? 'No content available'), 0, 80)) ?><?= strlen(strip_tags($item['content'] ?? '')) > 80 ? '...' : '' ?></div>
                                            
                                            <?php if (isset($item['tags']) && is_array($item['tags']) && !empty($item['tags'])): ?>
                                            <div class="bulletin-tags">
                                                <?php foreach (array_slice($item['tags'], 0, 2) as $tag): ?>
                                                <span class="bulletin-tag"><?= htmlspecialchars($tag) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div class="bulletin-meta">
                                                <div class="bulletin-meta-item">
                                                    <i class="bi bi-person"></i>
                                                    <?= htmlspecialchars(substr($item['user_name'] ?? 'Anonymous', 0, 12)) ?>
                                                </div>
                                                <div class="bulletin-meta-item">
                                                    <i class="bi bi-arrow-up"></i>
                                                    <?= $item['upvotes'] ?? 0 ?>
                                                </div>
                                                <div class="bulletin-meta-item">
                                                    <i class="bi bi-chat"></i>
                                                    <?= isset($item['comments']) ? count($item['comments']) : 0 ?>
                                                </div>
                                            </div>
                                        </a>
                                        
                                        <?php elseif ($contentType === 'faculty'): ?>
                                        <a href="Faculty_Profile.php?id=<?= $item['_id'] ?>" class="bulletin-link">
                                            <div class="bulletin-content-badge faculty">Faculty</div>
                                            <div class="bulletin-title"><?= htmlspecialchars(substr($item['name'] ?? 'Faculty Member', 0, 35)) ?><?= strlen($item['name'] ?? '') > 35 ? '...' : '' ?></div>
                                            <div class="bulletin-description"><?= htmlspecialchars(substr($item['bio'] ?? $item['about'] ?? 'Faculty member at UIU', 0, 75)) ?><?= strlen($item['bio'] ?? $item['about'] ?? '') > 75 ? '...' : '' ?></div>
                                            
                                            <?php if (isset($item['research_interests']) && is_array($item['research_interests']) && !empty($item['research_interests'])): ?>
                                            <div class="bulletin-tags">
                                                <?php foreach (array_slice($item['research_interests'], 0, 2) as $interest): ?>
                                                <span class="bulletin-tag"><?= htmlspecialchars($interest) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php elseif (isset($item['specialty']) && !empty($item['specialty'])): ?>
                                            <div class="bulletin-tags">
                                                <span class="bulletin-tag"><?= htmlspecialchars($item['specialty']) ?></span>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div class="bulletin-meta">
                                                <div class="bulletin-meta-item">
                                                    <i class="bi bi-mortarboard"></i>
                                                    <?= htmlspecialchars($item['designation'] ?? $item['position'] ?? 'Faculty') ?>
                                                </div>
                                                <?php if (isset($item['department']) && !empty($item['department'])): ?>
                                                <div class="bulletin-meta-item">
                                                    <i class="bi bi-building"></i>
                                                    <?= htmlspecialchars(substr($item['department'], 0, 12)) ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endfor; ?>
                            <?php else: ?>
                                <!-- Default content for non-logged-in users -->
                                <div class="bulletin-item">
                                    <a href="Research_page.php" class="bulletin-link">
                                        <div class="bulletin-content-badge project">Project</div>
                                        <div class="bulletin-title">Explore Research Projects</div>
                                        <div class="bulletin-description">Browse through innovative research projects from UIU students and faculty members</div>
                                        <div class="bulletin-meta">
                                            <div class="bulletin-meta-item">
                                                <i class="bi bi-search"></i>
                                                Discover
                                            </div>
                                            <div class="bulletin-meta-item">
                                                <i class="bi bi-collection"></i>
                                                250+ Projects
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="bulletin-item">
                                    <a href="events.php" class="bulletin-link">
                                        <div class="bulletin-content-badge event">Event</div>
                                        <div class="bulletin-title">Upcoming Events</div>
                                        <div class="bulletin-description">Stay updated with research conferences, seminars, and academic events</div>
                                        <div class="bulletin-meta">
                                            <div class="bulletin-meta-item">
                                                <i class="bi bi-calendar3"></i>
                                                Schedule
                                            </div>
                                            <div class="bulletin-meta-item">
                                                <i class="bi bi-clock"></i>
                                                Live Events
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="bulletin-item">
                                    <a href="forum_index.php" class="bulletin-link">
                                        <div class="bulletin-content-badge discussion">Discussion</div>
                                        <div class="bulletin-title">Join Discussions</div>
                                        <div class="bulletin-description">Engage with the research community through discussions and Q&A</div>
                                        <div class="bulletin-meta">
                                            <div class="bulletin-meta-item">
                                                <i class="bi bi-chat-dots"></i>
                                                Participate
                                            </div>
                                            <div class="bulletin-meta-item">
                                                <i class="bi bi-people"></i>
                                                Active Community
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="bulletin-item">
                                    <a href="Faculty_Page.php" class="bulletin-link">
                                        <div class="bulletin-content-badge faculty">Faculty</div>
                                        <div class="bulletin-title">Meet Our Faculty</div>
                                        <div class="bulletin-description">Connect with expert faculty members and research supervisors</div>
                                        <div class="bulletin-meta">
                                            <div class="bulletin-meta-item">
                                                <i class="bi bi-people"></i>
                                                Connect
                                            </div>
                                            <div class="bulletin-meta-item">
                                                <i class="bi bi-mortarboard"></i>
                                                120+ Faculty
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="bulletin-item">
                                    <a href="project_management.php" class="bulletin-link">
                                        <div class="bulletin-content-badge project">Project</div>
                                        <div class="bulletin-title">Start Your Research</div>
                                        <div class="bulletin-meta">
                                            <i class="bi bi-plus-circle"></i>
                                            Create
                                        </div>
                                    </a>
                                </div>
                                <div class="bulletin-item">
                                    <a href="login.php" class="bulletin-link">
                                        <div class="bulletin-content-badge discussion">Login</div>
                                        <div class="bulletin-title">Join the Community</div>
                                        <div class="bulletin-meta">
                                            <i class="bi bi-box-arrow-in-right"></i>
                                            Sign In
                                        </div>
                                    </a>
                                </div>
                                <div class="bulletin-item">
                                    <a href="Research_page.php" class="bulletin-link">
                                        <div class="bulletin-content-badge project">Project</div>
                                        <div class="bulletin-title">Machine Learning Research</div>
                                        <div class="bulletin-description">Cutting-edge AI and machine learning research projects for modern applications</div>
                                        <div class="bulletin-tags">
                                            <span class="bulletin-tag">AI</span>
                                            <span class="bulletin-tag">ML</span>
                                        </div>
                                        <div class="bulletin-meta">
                                            <div class="bulletin-meta-item">
                                                <i class="bi bi-cpu"></i>
                                                AI & ML
                                            </div>
                                            <div class="bulletin-meta-item">
                                                <i class="bi bi-people"></i>
                                                15+ Projects
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="bulletin-item">
                                    <a href="Research_page.php" class="bulletin-link">
                                        <div class="bulletin-content-badge project">Project</div>
                                        <div class="bulletin-title">Cybersecurity Studies</div>
                                        <div class="bulletin-meta">
                                            <i class="bi bi-shield-check"></i>
                                            Security
                                        </div>
                                    </a>
                                </div>
                                <div class="bulletin-item">
                                    <a href="literature_matrix.php" class="bulletin-link">
                                        <div class="bulletin-content-badge discussion">Tools</div>
                                        <div class="bulletin-title">Literature Matrix</div>
                                        <div class="bulletin-meta">
                                            <i class="bi bi-grid-3x3"></i>
                                            Research Tool
                                        </div>
                                    </a>
                                </div>
                                <div class="bulletin-item">
                                    <a href="Research_page.php" class="bulletin-link">
                                        <div class="bulletin-content-badge project">Project</div>
                                        <div class="bulletin-title">Blockchain Technology</div>
                                        <div class="bulletin-meta">
                                            <i class="bi bi-link-45deg"></i>
                                            Blockchain
                                        </div>
                                    </a>
                                </div>
                                <div class="bulletin-item">
                                    <a href="Research_page.php" class="bulletin-link">
                                        <div class="bulletin-content-badge project">Project</div>
                                        <div class="bulletin-title">Data Science Research</div>
                                        <div class="bulletin-meta">
                                            <i class="bi bi-bar-chart"></i>
                                            Data Science
                                        </div>
                                    </a>
                                </div>
                                <div class="bulletin-item">
                                    <a href="Research_page.php" class="bulletin-link">
                                        <div class="bulletin-content-badge project">Project</div>
                                        <div class="bulletin-title">IoT & Smart Systems</div>
                                        <div class="bulletin-meta">
                                            <i class="bi bi-wifi"></i>
                                            IoT
                                        </div>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    
    
    



    <!-- Futuristic Projects Section -->
    <section class="featured-projects section-padding position-relative">
        <div class="projects-bg-gradient"></div>
        <div class="projects-grid-overlay"></div>
        <div class="projects-glow-sphere"></div>
        
        <div class="container position-relative">
            <!-- Section header -->
            <div class="row mb-5">
                <div class="col-lg-6" data-aos="fade-right" data-aos-duration="800">
                    <div class="section-header">
                        <h2 class="section-title futuristic-title">Innovative <span class="text-gradient">Projects</span>
                        <p class="section-subtitle">Explore some of our most groundbreaking research projects from across the university, pushing the boundaries of knowledge and technology.</p>
                        <div class="title-underline"></div>
                    </div>
                </div>
                <div class="col-lg-6 d-flex align-items-center justify-content-lg-end" data-aos="fade-left" data-aos-duration="800">
                    <div class="futuristic-filter-tabs">
                        <button class="filter-btn active" data-filter="all">
                            <span class="btn-content">All Projects</span>
                            <span class="btn-glow"></span>
                        </button>
                        <button class="filter-btn" data-filter="technology">
                            <span class="btn-content">Technology</span>
                            <span class="btn-glow"></span>
                        </button>
                        <button class="filter-btn" data-filter="science">
                            <span class="btn-content">Science</span>
                            <span class="btn-glow"></span>
                        </button>
                        <button class="filter-btn" data-filter="engineering">
                            <span class="btn-content">Engineering</span>
                            <span class="btn-glow"></span>
                        </button>
                        <button class="filter-btn" data-filter="medical">
                            <span class="btn-content">Medical</span>
                            <span class="btn-glow"></span>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Projects grid -->
            <div class="row g-4 project-grid">
                <!-- Project Item 1 -->
                <div class="col-md-6 col-lg-4 project-item" data-category="technology" data-aos="fade-up" data-aos-duration="800">
                    <div class="futuristic-card">
                        <div class="card-glow"></div>
                        <div class="card-badge technology">Technology</div>
                        <div class="card-img-container">
                            <img src="assets/resources/research_picture/pub_1.jpg" class="card-img-top" alt="AI Research">
                            <div class="card-img-overlay">
                                <div class="view-project-wrapper">
                                    <span class="view-project"><i class="bi bi-eye"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="card-tags">
                                <span>Machine Learning</span>
                                <span>Climate Science</span>
                            </div>
                            <h5 class="card-title">Machine Learning for Climate Prediction</h5>
                            <p class="card-text">Developing advanced neural networks to improve climate change prediction models.</p>
                            <div class="card-meta">
                                <span><i class="bi bi-person"></i> Dr. Sarah Johnson</span>
                                <span><i class="bi bi-calendar"></i> 2023</span>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="Project_details.php?id=1" class="card-link">
                                <span>View Details</span> 
                                <i class="bi bi-arrow-right"></i>
                                <span class="link-hover-effect"></span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Project Item 2 -->
                <div class="col-md-6 col-lg-4 project-item" data-category="science" data-aos="fade-up" data-aos-duration="800" data-aos-delay="100">
                    <div class="futuristic-card">
                        <div class="card-glow"></div>
                        <div class="card-badge science">Science</div>
                        <div class="card-img-container">
                            <img src="assets/resources/research_picture/pub_2.jpg" class="card-img-top" alt="Science Research">
                            <div class="card-img-overlay">
                                <div class="view-project-wrapper">
                                    <span class="view-project"><i class="bi bi-eye"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="card-tags">
                                <span>Quantum</span>
                                <span>Computing</span>
                            </div>
                            <h5 class="card-title">Quantum Computing Applications</h5>
                            <p class="card-text">Exploring practical applications of quantum computing in cryptography and database management.</p>
                            <div class="card-meta">
                                <span><i class="bi bi-person"></i> Prof. Michael Chen</span>
                                <span><i class="bi bi-calendar"></i> 2023</span>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="Project_details.php?id=2" class="card-link">
                                <span>View Details</span>
                                <i class="bi bi-arrow-right"></i>
                                <span class="link-hover-effect"></span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Project Item 3 -->
                <div class="col-md-6 col-lg-4 project-item" data-category="engineering" data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
                    <div class="futuristic-card">
                        <div class="card-glow"></div>
                        <div class="card-badge engineering">Engineering</div>
                        <div class="card-img-container">
                            <img src="assets/resources/research_picture/pub_3.jpg" class="card-img-top" alt="Engineering Research">
                            <div class="card-img-overlay">
                                <div class="view-project-wrapper">
                                    <span class="view-project"><i class="bi bi-eye"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="card-tags">
                                <span>Sustainability</span>
                                <span>Materials</span>
                            </div>
                            <h5 class="card-title">Sustainable Building Materials</h5>
                            <p class="card-text">Developing eco-friendly building materials from recycled plastics and agricultural waste.</p>
                            <div class="card-meta">
                                <span><i class="bi bi-person"></i> Dr. Robert Park</span>
                                <span><i class="bi bi-calendar"></i> 2023</span>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="Project_details.php?id=3" class="card-link">
                                <span>View Details</span>
                                <i class="bi bi-arrow-right"></i>
                                <span class="link-hover-effect"></span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Project Item 4 - Medical -->
                <div class="col-md-6 col-lg-4 project-item" data-category="medical" data-aos="fade-up" data-aos-duration="800" data-aos-delay="300">
                    <div class="futuristic-card">
                        <div class="card-glow"></div>
                        <div class="card-badge medical">Medical</div>
                        <div class="card-img-container">
                            <img src="assets/resources/research_picture/pub_4.jpg" class="card-img-top" alt="Medical Research">
                            <div class="card-img-overlay">
                                <div class="view-project-wrapper">
                                    <span class="view-project"><i class="bi bi-eye"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="card-tags">
                                <span>AI Diagnostics</span>
                                <span>Medical Imaging</span>
                            </div>
                            <h5 class="card-title">Deep Learning for Medical Image Segmentation</h5>
                            <p class="card-text">Applying convolutional neural networks to segment medical images and identify critical regions like tumors in MRI scans.</p>
                            <div class="card-meta">
                                <span><i class="bi bi-person"></i> Dr. Amina Rahman</span>
                                <span><i class="bi bi-calendar"></i> 2023</span>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="Project_details.php?id=4" class="card-link">
                                <span>View Details</span>
                                <i class="bi bi-arrow-right"></i>
                                <span class="link-hover-effect"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- More projects button -->
            <div class="text-center mt-5" data-aos="fade-up" data-aos-duration="800">
                <a href="Research_page.php" class="futuristic-button">
                    <span class="button-content">Discover All Research Projects</span>
                    <i class="bi bi-arrow-right ms-2"></i>
                    <span class="button-glow"></span>
                </a>
            </div>
        </div>
    </section>
    
    
    
    



    <!-- Faculty Spotlight Section -->
    <section class="faculty-spotlight section-padding position-relative">
        <!-- Futuristic background elements -->
        <div class="faculty-bg-grid"></div>
        <div class="faculty-orb orb-1"></div>
        <div class="faculty-orb orb-2"></div>
        <div class="faculty-glow-effect"></div>
        
        <div class="container position-relative mt-4">
            <div class="text-center mb-2" data-aos="fade-up">
                <div class="section-header text-center">
                    <h2 class="futuristic-title">Faculty <span class="text-gradient">Spotlight</span></h2>
                    <p class="section-subtitle mx-auto">Meet our distinguished faculty members who are leading cutting-edge research and shaping the future of innovation at UIU.</p>
                    <div class="d-flex justify-content-center mt-1">
                        <div class="title-underline"></div>
                    </div>
                        </div>
                    </div>
                    
            <div class="faculty-showcase" data-aos="fade-up" data-aos-delay="100">
                <div class="row g-2 justify-content-center" id="randomFacultyList">
                    <!-- Faculty members will be dynamically loaded here -->
                    <div class="text-center w-100 py-5">
                        <div class="neo-loader">
                            <span></span>
                            <span></span>
                            <span></span>
                                </div>
                        <p class="mt-4 text-light">Loading brilliant minds...</p>
                            </div>
                        </div>
                    </div>
                    
            
            

        </div>
    </section>

    

<!-- Futuristic Research Events Section -->
<section class="events-section section-padding">
    <!-- Futuristic background elements -->
    <div class="events-bg-grid"></div>
    <div class="events-orb orb-1"></div>
    <div class="events-orb orb-2"></div>
    <div class="events-glow-effect"></div>
    
    <?php
    // Include MongoDB autoloader if not already included
    if (!class_exists('MongoDB\Client')) {
        require __DIR__ . '/vendor/autoload.php';
    }

    // Function to connect to MongoDB and get random events
    function getRandomEventsFromMongoDB($limit = 3) {
        try {
            $mongoClient = new MongoDB\Client(require __DIR__ . '/src/config/mongo_uri.php');
            $db = $mongoClient->uiurp;
            $collection = $db->events;
            
            // Get all events
            $allEvents = $collection->find([])->toArray();
            
            // If we have events, select random ones
            if (count($allEvents) > 0) {
                // Shuffle the array of events
                shuffle($allEvents);
                
                // Take the first $limit events
                $randomEvents = array_slice($allEvents, 0, $limit);
                
                // Convert MongoDB document to arrays and format dates
                $formattedEvents = [];
                foreach ($randomEvents as $event) {
                    $eventArray = json_decode(json_encode($event), true);
                    
                    // Helper function to convert MongoDB date
                    $convertDate = function($dateValue) {
                        if (is_array($dateValue) && isset($dateValue['$date'])) {
                            if (is_array($dateValue['$date']) && isset($dateValue['$date']['$numberLong'])) {
                                return date('Y-m-d\TH:i:s\Z', intval($dateValue['$date']['$numberLong']) / 1000);
                            } elseif (is_numeric($dateValue['$date'])) {
                                return date('Y-m-d\TH:i:s\Z', $dateValue['$date'] / 1000);
                            }
                        }
                        return $dateValue;
                    };
                    
                    // Convert MongoDB UTCDateTime objects to readable dates
                    if (isset($eventArray['eventDate'])) {
                        $eventArray['eventDate'] = $convertDate($eventArray['eventDate']);
                    }
                    
                    $formattedEvents[] = $eventArray;
                }
                
                return $formattedEvents;
            }
            
            return [];
        } catch (Exception $e) {
            error_log("Error fetching random events: " . $e->getMessage());
            return [];
        }
    }

    // Get 3 random events
    $randomEvents = getRandomEventsFromMongoDB(3);
    ?>
    
    <div class="container position-relative">
        <div class="section-header text-center mb-5" data-aos="fade-up">
            <div class="badge-wrapper d-flex justify-content-center mb-3">
                <span class="neo-badge event-badge"><i class="bi bi-calendar-event me-2"></i>Upcoming Opportunities</span>
                </div>
            <h2 class="futuristic-title">Research <span class="text-gradient">Events</span></h2>
            <p class="section-subtitle mx-auto">Discover symposiums, workshops, and networking opportunities to expand your research horizons</p>
            <div class="title-underline mx-auto"></div>
        </div>
        
        <div class="row g-4 event-timeline">
            <?php 
            // If we have events, display them; otherwise, show default placeholder content
            if (!empty($randomEvents)): 
                foreach ($randomEvents as $index => $event):
                    // Format the date for display
                    $eventDate = new DateTime($event['eventDate']);
                    
                    // Determine if the event has registration or join link
                    $hasRegistration = isset($event['registration']['required']) && $event['registration']['required'] && !empty($event['registration']['link']);
                    $hasJoinLink = isset($event['location']['type']) && $event['location']['type'] === 'Virtual' && !empty($event['location']['joinLink']);
            ?>
            <!-- Event <?php echo $index + 1; ?> -->
            <div class="col-md-6 col-lg-4 mb-4" data-aos="fade-up" <?php echo $index > 0 ? 'data-aos-delay="'.(100*$index).'"' : ''; ?>>
                <div class="neo-event-card">
                    <div class="card-border"></div>
                    <div class="card-glow"></div>
                    
                    <div class="event-date-badge">
                        <div class="date-content">
                            <span class="event-day"><?php echo $eventDate->format('d'); ?></span>
                            <span class="event-month"><?php echo strtoupper($eventDate->format('M')); ?></span>
                        </div>
                        <div class="date-glow"></div>
                    </div>
                    
                    <div class="event-content">
                        <div class="event-tags">
                            <span class="event-tag"><?php echo htmlspecialchars($event['eventType']); ?></span>
                            <?php if (isset($event['status'])): ?>
                            <span class="event-tag"><?php echo htmlspecialchars($event['status']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <h4 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h4>
                        
                        <div class="event-meta">
                            <div class="meta-item">
                                <i class="bi bi-clock"></i>
                                <span><?php echo isset($event['startTime']) ? htmlspecialchars($event['startTime']) : ''; ?> - <?php echo isset($event['endTime']) ? htmlspecialchars($event['endTime']) : ''; ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="bi bi-geo-alt"></i>
                                <span>
                                    <?php if (isset($event['location']['type'])): ?>
                                        <?php if ($event['location']['type'] === 'Virtual'): ?>
                                            Virtual - <?= htmlspecialchars($event['location']['virtualPlatform'] ?? 'Online') ?>
                                        <?php elseif ($event['location']['type'] === 'Physical'): ?>
                                            <?= htmlspecialchars($event['location']['room'] ?? 'On Campus') ?>
                                        <?php else: ?>
                                            Hybrid - <?= htmlspecialchars($event['location']['room'] ?? 'Multiple Locations') ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        TBD
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        
                        <p class="event-description"><?php echo htmlspecialchars(substr($event['description'] ?? '', 0, 120)) . (strlen($event['description'] ?? '') > 120 ? '...' : ''); ?></p>
                        
                        <?php if ($hasRegistration): ?>
                        <a href="<?php echo htmlspecialchars($event['registration']['link']); ?>" target="_blank" class="neo-button small">
                            <span class="button-content">Register Now</span>
                            <span class="button-icon"><i class="bi bi-arrow-right"></i></span>
                            <div class="button-glow"></div>
                        </a>
                        <?php elseif ($hasJoinLink): ?>
                        <a href="<?php echo htmlspecialchars($event['location']['joinLink']); ?>" target="_blank" class="neo-button small">
                            <span class="button-content">Join Online</span>
                            <span class="button-icon"><i class="bi bi-arrow-right"></i></span>
                            <div class="button-glow"></div>
                        </a>
                        <?php else: ?>
                        <a href="events.php" class="neo-button small">
                            <span class="button-content">View Details</span>
                            <span class="button-icon"><i class="bi bi-arrow-right"></i></span>
                            <div class="button-glow"></div>
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-circuit-pattern"></div>
                </div>
            </div>
            <?php 
                endforeach;
            else:
                // Display placeholder content if no events are found
            ?>
            <!-- Event 1 (Placeholder) -->
            <div class="col-md-6 col-lg-4 mb-4" data-aos="fade-up">
                <div class="neo-event-card">
                    <div class="card-border"></div>
                    <div class="card-glow"></div>
                    
                    <div class="event-date-badge">
                        <div class="date-content">
                            <span class="event-day">15</span>
                            <span class="event-month">DEC</span>
                        </div>
                        <div class="date-glow"></div>
                    </div>
                    
                    <div class="event-content">
                        <div class="event-tags">
                            <span class="event-tag">Conference</span>
                            <span class="event-tag">Research</span>
                        </div>
                        
                        <h4 class="event-title">Annual Research Symposium</h4>
                        
                        <div class="event-meta">
                            <div class="meta-item">
                                <i class="bi bi-clock"></i>
                                <span>10:00 AM - 4:00 PM</span>
                            </div>
                            <div class="meta-item">
                                <i class="bi bi-geo-alt"></i>
                                <span>UIU Main Auditorium</span>
                            </div>
                        </div>
                        
                        <p class="event-description">Join us for presentations from leading researchers across multiple disciplines, networking opportunities, and research showcases.</p>
                        
                        <a href="#" class="neo-button small">
                            <span class="button-content">Register Now</span>
                            <span class="button-icon"><i class="bi bi-arrow-right"></i></span>
                            <div class="button-glow"></div>
                        </a>
                    </div>
                    
                    <div class="card-circuit-pattern"></div>
                </div>
            </div>
            
            <!-- Event 2 (Placeholder) -->
            <div class="col-md-6 col-lg-4 mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="neo-event-card">
                    <div class="card-border"></div>
                    <div class="card-glow"></div>
                    
                    <div class="event-date-badge">
                        <div class="date-content">
                            <span class="event-day">22</span>
                            <span class="event-month">DEC</span>
                        </div>
                        <div class="date-glow"></div>
                    </div>
                    
                    <div class="event-content">
                        <div class="event-tags">
                            <span class="event-tag">Workshop</span>
                            <span class="event-tag">AI</span>
                        </div>
                        
                        <h4 class="event-title">AI Research Workshop</h4>
                        
                        <div class="event-meta">
                            <div class="meta-item">
                                <i class="bi bi-clock"></i>
                                <span>2:00 PM - 5:00 PM</span>
                            </div>
                            <div class="meta-item">
                                <i class="bi bi-geo-alt"></i>
                                <span>Virtual Event</span>
                            </div>
                        </div>
                        
                        <p class="event-description">A practical workshop on applying machine learning to research problems with hands-on training sessions and expert guidance.</p>
                        
                        <a href="#" class="neo-button small">
                            <span class="button-content">Join Online</span>
                            <span class="button-icon"><i class="bi bi-arrow-right"></i></span>
                            <div class="button-glow"></div>
                        </a>
                    </div>
                    
                    <div class="card-circuit-pattern"></div>
                </div>
            </div>
            
            <!-- Event 3 (Placeholder) -->
            <div class="col-md-6 col-lg-4 mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="neo-event-card">
                    <div class="card-border"></div>
                    <div class="card-glow"></div>
                    
                    <div class="event-date-badge">
                        <div class="date-content">
                            <span class="event-day">10</span>
                            <span class="event-month">JAN</span>
                        </div>
                        <div class="date-glow"></div>
                    </div>
                    
                    <div class="event-content">
                        <div class="event-tags">
                            <span class="event-tag">Workshop</span>
                            <span class="event-tag">Funding</span>
                        </div>
                        
                        <h4 class="event-title">Grant Writing Workshop</h4>
                        
                        <div class="event-meta">
                            <div class="meta-item">
                                <i class="bi bi-clock"></i>
                                <span>9:00 AM - 1:00 PM</span>
                            </div>
                            <div class="meta-item">
                                <i class="bi bi-geo-alt"></i>
                                <span>Science Building, Room 305</span>
                            </div>
                        </div>
                        
                        <p class="event-description">Learn strategies for writing successful research grant proposals with feedback from experienced researchers and grant reviewers.</p>
                        
                        <a href="#" class="neo-button small">
                            <span class="button-content">Register Now</span>
                            <span class="button-icon"><i class="bi bi-arrow-right"></i></span>
                            <div class="button-glow"></div>
                        </a>
                    </div>
                    
                    <div class="card-circuit-pattern"></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-5" data-aos="fade-up">
            <a href="events.php" class="neo-button secondary">
                <span class="button-content">Explore All Events</span>
                <span class="button-icon"><i class="bi bi-arrow-right"></i></span>
                <div class="button-glow"></div>
            </a>
        </div>
</div>
</section>





    <!-- Native CSS Scroll Snap -->
    

    <!-- Enhanced Scroll Snapping & Navigation Script -->
    

    <!-- Ensure the badge is clickable -->
    

    <!-- Theme Initialization Script -->
    <script>
        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Check for saved theme preference or default to 'dark'
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            // Add theme transition class to body for smooth transitions
            document.body.classList.add('theme-transition');
            
            // Update any text elements that need theme-specific styling
            updateThemeSpecificElements(savedTheme);
        });
        
        // Function to update theme-specific elements
        function updateThemeSpecificElements(theme) {
            // Update any hardcoded text colors that need to change with theme
            const textElements = document.querySelectorAll('.text-light, .text-muted');
            textElements.forEach(element => {
                if (theme === 'light') {
                    element.style.color = 'var(--text-primary)';
                } else {
                    element.style.color = ''; // Reset to default
                }
            });
            
            // Update search section text colors
            const searchTitle = document.querySelector('.search-header h1');
            const searchSubtitle = document.querySelector('.search-header p');
            
            if (searchTitle) {
                searchTitle.style.color = 'var(--text-primary)';
            }
            if (searchSubtitle) {
                searchSubtitle.style.color = 'var(--text-secondary)';
            }
            
            // Update scroll down text
            const scrollText = document.querySelector('.scroll-down-text');
            const scrollArrow = document.querySelector('.scroll-down-arrow');
            
            if (scrollText) {
                scrollText.style.color = 'var(--text-secondary)';
            }
            if (scrollArrow) {
                scrollArrow.style.color = 'var(--text-primary)';
            }
        }
        
        // Listen for theme changes from navbar toggle
        document.addEventListener('themeChanged', function(e) {
            updateThemeSpecificElements(e.detail.theme);
            updateChartColors(e.detail.theme);
        });
        
        // Function to update Chart.js colors based on theme
        function updateChartColors(theme) {
            if (typeof Chart !== 'undefined' && window.researchChart) {
                const isLight = theme === 'light';
                const textColor = isLight ? '#1e293b' : '#ffffff';
                const gridColor = isLight ? 'rgba(67, 97, 238, 0.1)' : 'rgba(255, 255, 255, 0.1)';
                
                // Update chart options
                if (window.researchChart.options.scales.x) {
                    window.researchChart.options.scales.x.ticks.color = textColor;
                    window.researchChart.options.scales.x.grid.color = gridColor;
                }
                if (window.researchChart.options.scales.y) {
                    window.researchChart.options.scales.y.ticks.color = textColor;
                    window.researchChart.options.scales.y.grid.color = gridColor;
                }
                if (window.researchChart.options.plugins && window.researchChart.options.plugins.legend) {
                    window.researchChart.options.plugins.legend.labels.color = textColor;
                }
                
                // Update the chart
                window.researchChart.update();
            }
            
            // Also try to update any other charts that might exist
            if (typeof Chart !== 'undefined') {
                Chart.helpers.each(Chart.instances, function(instance) {
                    const isLight = theme === 'light';
                    const textColor = isLight ? '#1e293b' : '#ffffff';
                    const gridColor = isLight ? 'rgba(67, 97, 238, 0.1)' : 'rgba(255, 255, 255, 0.1)';
                    
                    if (instance.options.scales) {
                        Object.keys(instance.options.scales).forEach(scaleKey => {
                            if (instance.options.scales[scaleKey].ticks) {
                                instance.options.scales[scaleKey].ticks.color = textColor;
                            }
                            if (instance.options.scales[scaleKey].grid) {
                                instance.options.scales[scaleKey].grid.color = gridColor;
                            }
                        });
                    }
                    
                    if (instance.options.plugins && instance.options.plugins.legend) {
                        instance.options.plugins.legend.labels.color = textColor;
                    }
                    
                    instance.update();
                });
            }
        }
        
        // Call updateChartColors when charts are loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for charts to be initialized and then update colors
            setTimeout(() => {
                const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
                updateChartColors(currentTheme);
            }, 2000);
        });
    </script>
    
    <!-- Include Global Meeting Notifications -->
    <?php include 'src/includes/global-meeting-notifications.php'; ?>
    
    <!-- Preference Tracking Script -->
    <script src="assets/js/preference_tracker.js"></script>
    
    <?php if (isset($_SESSION['user_id'])): ?>
    <!-- User is logged in, enable advanced tracking -->
    <script>
        document.body.setAttribute('data-user-logged-in', 'true');
    </script>
    <?php endif; ?>

<!-- Futuristic Research Guidance FAQ Section -->
<section id="faq-section" class="faq-section section-padding">
    <!-- Futuristic background elements -->
    <div class="faq-bg-particles"></div>
    <div class="faq-orb faq-orb-1"></div>
    <div class="faq-orb faq-orb-2"></div>
    <div class="faq-mesh-grid"></div>
    
    <div class="container position-relative">
        <div class="section-header text-center mb-5" data-aos="fade-up">
            <div class="badge-wrapper d-flex justify-content-center mb-3">
                <span class="neo-badge faq-badge"><i class="bi bi-question-circle me-2"></i>Research Guidance</span>
            </div>
            <h2 class="futuristic-title">Frequently <span class="text-gradient">Asked</span> Questions</h2>
            <p class="section-subtitle mx-auto">Find detailed answers to common research questions at UIU</p>
            <div class="title-underline mx-auto"></div>
        </div>
        
        <div class="row g-4 justify-content-center">
            <div class="col-lg-10">
                <div class="faq-container" data-aos="fade-up">
                    <div class="row">
        <div class="col-md-6">
                            <div class="accordion neo-accordion" id="faqAccordionLeft">
            <!-- FAQs will be dynamically added here -->
          </div>
        </div>
        <div class="col-md-6">
                            <div class="accordion neo-accordion" id="faqAccordionRight">
            <!-- FAQs will be dynamically added here -->
          </div>
        </div>
      </div>
      </div>
    </div>
        </div>
        
        <div class="text-center mt-5" data-aos="fade-up" data-aos-delay="200">
            <a href="#" class="neo-button primary question-button">
                <span class="button-content">Ask a Question</span>
                <span class="button-icon"><i class="bi bi-question-circle"></i></span>
                <div class="button-glow"></div>
            </a>
                    </div>
                    </div>
</section>





    <!-- Include Footer -->
    <?php include 'src/includes/footer.php'; ?>

  

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/index-inline-extracted.js"></script>

    <!-- Search Functionality Script -->
    
