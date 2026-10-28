<?php
session_start();

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
    
    // Debug overlay is now handled globally via navbar.php
} else {
    // Fallback to recent content for non-logged-in users
    $projects = $recommendationEngine->getRecommendedProjects('guest');
    $events = $recommendationEngine->getRecommendedEvents('guest');
    $forumPosts = $recommendationEngine->getRecommendedForumPosts('guest');
    $faculties = $recommendationEngine->getRecommendedFaculties('guest');
    
    $isPersonalized = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - UIU Research Portal</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom styles -->
    <link rel="stylesheet" href="assets/styles/theme.css">
    
    <!-- Prevent Theme Flash Script -->
    <script>
    (function() {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }
    })();
    </script>
    
    <style>
        :root {
            /* Theme Variables - Dark Mode (Default) */
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-muted: #64748b;
            --border-color: rgba(76, 201, 240, 0.1);
            --card-bg: rgba(30, 41, 59, 0.8);
            --neo-primary: #4361ee;
            --neo-blue: #4cc9f0;
            --neo-magenta: #f72585;
        }

        /* Light Theme Variables */
        [data-theme="light"] {
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --border-color: rgba(67, 97, 238, 0.15);
            --card-bg: rgba(255, 255, 255, 0.95);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            transition: all 0.3s ease;
        }

        .dashboard-container {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            padding: 2rem 0;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 0 1rem;
        }

        .dashboard-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .dashboard-subtitle {
            font-size: 1.1rem;
            color: var(--text-secondary);
            font-weight: 400;
        }

        .section {
            margin-bottom: 4rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 0 1rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-icon {
            color: var(--neo-blue);
        }

        .view-all-link {
            color: var(--neo-primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .view-all-link:hover {
            color: var(--neo-blue);
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 0 1rem;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border-color: var(--neo-blue);
            text-decoration: none;
            color: inherit;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .card-content {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .card-date {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .card-stats {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .faculty-card {
            text-align: center;
        }

        .faculty-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1rem;
            border: 3px solid var(--neo-blue);
        }

        .faculty-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .faculty-bio {
            color: var(--text-secondary);
            font-size: 0.85rem;
            line-height: 1.4;
        }

        .tag {
            display: inline-block;
            background: rgba(76, 201, 240, 0.1);
            color: var(--neo-blue);
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-right: 0.5rem;
        }

        .status-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-upcoming {
            background: rgba(76, 201, 240, 0.1);
            color: var(--neo-blue);
        }

        .status-ongoing {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .status-completed {
            background: rgba(156, 163, 175, 0.1);
            color: #9ca3af;
        }

        .personalized-badge {
            display: inline-flex;
            align-items: center;
            font-size: 0.8rem;
            color: var(--neo-blue);
            margin-left: 0.5rem;
            animation: sparkle 2s ease-in-out infinite;
        }

        .personalized-badge i {
            font-size: 0.7rem;
        }

        @keyframes sparkle {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.1); }
        }

        /* Recommendation indicator on cards */
        .card.recommended {
            position: relative;
        }

        .card.recommended::before {
            content: "★";
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            color: var(--neo-blue);
            font-size: 0.8rem;
            z-index: 10;
        }

        /* Vertical list container for "For You" section */
        .cards-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .cards-list .card {
            width: 100%;
        }

        /* Scrollable Container Styles for "For You" section */
        .scrollable-container {
            position: relative;
            width: 100%;
            padding: 1rem 0;
            overflow: hidden;
        }

        .scroll-cards-wrapper {
            display: flex;
            overflow-x: auto;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none; /* Firefox */
            padding: 1rem 0.5rem;
            gap: 1.25rem;
        }

        .scroll-cards-wrapper::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Edge */
        }

        .scroll-card {
            flex: 0 0 auto;
            width: 280px;
            min-width: 280px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
            position: relative;
        }

        .scroll-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border-color: var(--neo-blue);
            text-decoration: none;
            color: inherit;
        }

        .scroll-card.faculty-card {
            text-align: center;
        }

        .scroll-indicators {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 100%;
            display: flex;
            justify-content: space-between;
            pointer-events: none;
            z-index: 10;
        }

        .scroll-left, .scroll-right {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--neo-primary);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            pointer-events: auto;
            opacity: 0.8;
            transition: opacity 0.3s ease;
            margin: 0 1rem;
        }

        .scroll-left:hover, .scroll-right:hover {
            opacity: 1;
        }

        .scroll-left {
            left: 0;
        }

        .scroll-right {
            right: 0;
        }

        .scroll-card.recommended::before {
            content: "★";
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            color: var(--neo-magenta);
            font-size: 0.8rem;
            z-index: 10;
        }

        /* For You section scroll container (80vh) */
        .for-you-section {
            max-height: 80vh;
            overflow-y: auto;
            padding-right: 0.25rem; /* space for scrollbar */
        }

        .for-you-section::-webkit-scrollbar {
            width: 6px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-title {
                font-size: 2rem;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .cards-grid {
                grid-template-columns: 1fr;
            }

            .scroll-card {
                width: 260px;
                min-width: 260px;
            }
        }
    </style>
</head>
<body>
    <!-- Include Navbar -->
    <?php include_once('src/includes/navbar.php'); ?>

    <div class="dashboard-container">
        <div class="container">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <h1 class="dashboard-title">
                    <?php if ($isPersonalized): ?>
                        Personalized Dashboard
                        <span style="font-size: 0.5em; color: var(--neo-blue); margin-left: 0.5rem;">
                            <i class="bi bi-stars"></i>
                        </span>
                    <?php else: ?>
                        Dashboard
                    <?php endif; ?>
                </h1>
                <p class="dashboard-subtitle">
                    <?php if ($isPersonalized): ?>
                        Personalized content based on your interests and activity
                    <?php elseif (isset($_SESSION['user_id'])): ?>
                        <span style="color: var(--text-muted);">Keep interacting with content to get personalized recommendations!</span><br>
                        <small>Latest projects, events, discussions, and faculty information</small>
                    <?php else: ?>
                        Stay updated with the latest projects, events, discussions, and faculty information
                    <?php endif; ?>
                </p>
            </div>

            <!-- For You Section - Combined Content Based on User Preferences -->
            <?php if (isset($_SESSION['user_id']) && $isPersonalized): ?>
            <div class="section for-you-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="bi bi-lightning-charge-fill section-icon" style="color: var(--neo-magenta);"></i>
                        For You
                        <span class="personalized-badge" style="color: var(--neo-magenta);">
                            <i class="bi bi-stars"></i>
                        </span>
                    </h2>
                    <span class="text-muted" style="font-size: 0.9rem;">Content tailored to your interests</span>
                </div>
                
                <!-- Vertical list container for "For You" section -->
                <div class="cards-list">
                    <?php 
                    // Display more items from combined recommendations (at least 20)
                    $displayCount = min(count($combinedRecommendations), 25); // Show up to 25 items
                    for ($i = 0; $i < $displayCount; $i++): 
                        $item = $combinedRecommendations[$i];
                        $contentType = $item['content_type'];
                    ?>
                        <?php if ($contentType === 'project'): ?>
                        <a href="Project_details.php?id=<?= $item['_id'] ?>" class="card recommended" data-item-type="project" data-item-id="<?= $item['_id'] ?>">
                            <div class="content-type-badge" style="position: absolute; top: 0.5rem; left: 0.5rem; background: rgba(76, 201, 240, 0.1); color: var(--neo-blue); padding: 0.1rem 0.4rem; border-radius: 4px; font-size: 0.7rem;">Project</div>
                            <h3 class="card-title"><?= htmlspecialchars($item['title'] ?? 'Untitled Project') ?></h3>
                            <p class="card-content"><?= htmlspecialchars(substr($item['description'] ?? 'No description available', 0, 120)) ?>...</p>
                            
                            <?php if (isset($item['tags']) && is_array($item['tags']) && !empty($item['tags'])): ?>
                            <div class="card-tags" style="margin-bottom: 0.5rem;">
                                <?php foreach (array_slice($item['tags'], 0, 2) as $tag): ?>
                                <span class="tag"><?= htmlspecialchars($tag) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="card-meta">
                                <span class="card-date">
                                    <i class="bi bi-calendar3"></i>
                                    <?php 
                                    if (isset($item['createdAt'])) {
                                        if (is_object($item['createdAt']) && method_exists($item['createdAt'], 'toDateTime')) {
                                            echo $item['createdAt']->toDateTime()->format('M j, Y');
                                        } else {
                                            echo 'Recent';
                                        }
                                    } else {
                                        echo 'Recent';
                                    }
                                    ?>
                                </span>
                            </div>
                        </a>
                        
                        <?php elseif ($contentType === 'event'): ?>
                        <a href="events.php#event-<?= $item['_id'] ?>" class="card recommended" data-item-type="event" data-item-id="<?= $item['_id'] ?>">
                            <div class="content-type-badge" style="position: absolute; top: 0.5rem; left: 0.5rem; background: rgba(247, 37, 133, 0.1); color: var(--neo-magenta); padding: 0.1rem 0.4rem; border-radius: 4px; font-size: 0.7rem;">Event</div>
                            <h3 class="card-title"><?= htmlspecialchars($item['title'] ?? 'Untitled Event') ?></h3>
                            <p class="card-content"><?= htmlspecialchars(substr($item['description'] ?? 'No description available', 0, 120)) ?>...</p>
                            
                            <div class="card-meta">
                                <span class="card-date">
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
                                </span>
                                <span class="status-badge status-<?= strtolower($item['status'] ?? 'upcoming') ?>">
                                    <?= htmlspecialchars($item['status'] ?? 'Upcoming') ?>
                                </span>
                            </div>
                        </a>
                        
                        <?php elseif ($contentType === 'forum_post'): ?>
                        <a href="post_details.php?id=<?= $item['_id'] ?>" class="card recommended" data-item-type="forum_post" data-item-id="<?= $item['_id'] ?>">
                            <div class="content-type-badge" style="position: absolute; top: 0.5rem; left: 0.5rem; background: rgba(67, 97, 238, 0.1); color: var(--neo-primary); padding: 0.1rem 0.4rem; border-radius: 4px; font-size: 0.7rem;">Discussion</div>
                            <h3 class="card-title"><?= htmlspecialchars($item['title'] ?? 'Untitled Post') ?></h3>
                            <p class="card-content"><?= htmlspecialchars(substr($item['content'] ?? 'No content available', 0, 120)) ?>...</p>
                            
                            <div class="card-meta">
                                <span class="card-date">
                                    <i class="bi bi-person"></i>
                                    <?= htmlspecialchars($item['user_name'] ?? 'Anonymous') ?>
                                </span>
                                <span class="card-stats">
                                    <span class="stat-item">
                                        <i class="bi bi-arrow-up"></i>
                                        <?= $item['upvotes'] ?? 0 ?>
                                    </span>
                                </span>
                            </div>
                        </a>
                        
                        <?php elseif ($contentType === 'faculty'): ?>
                        <a href="Faculty_Profile.php?id=<?= $item['_id'] ?>" class="card faculty-card recommended" data-item-type="faculty" data-item-id="<?= $item['_id'] ?>">
                            <div class="content-type-badge" style="position: absolute; top: 0.5rem; left: 0.5rem; background: rgba(52, 211, 153, 0.1); color: #34D399; padding: 0.1rem 0.4rem; border-radius: 4px; font-size: 0.7rem;">Faculty</div>
                            <img src="<?= htmlspecialchars($item['profile_image'] ?? 'assets/resources/user_avater.png') ?>" 
                                 alt="<?= htmlspecialchars($item['name'] ?? 'Faculty Member') ?>" 
                                 class="faculty-avatar">
                            <h3 class="faculty-name"><?= htmlspecialchars($item['name'] ?? 'Faculty Member') ?></h3>
                            <p class="faculty-bio"><?= htmlspecialchars(substr($item['bio'] ?? 'No bio available', 0, 100)) ?>...</p>
                            
                            <?php if (isset($item['specialty']) || (isset($item['research_interests']) && is_array($item['research_interests']))): ?>
                            <div class="card-tags" style="margin-top: 0.5rem;">
                                <?php if (isset($item['specialty'])): ?>
                                <span class="tag specialty"><?= htmlspecialchars($item['specialty']) ?></span>
                                <?php endif; ?>
                                <?php if (isset($item['research_interests']) && is_array($item['research_interests'])): ?>
                                    <?php foreach (array_slice($item['research_interests'], 0, 1) as $interest): ?>
                                    <span class="tag research-interest"><?= htmlspecialchars($interest) ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Projects Section -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="bi bi-journal-richtext section-icon"></i>
                        <?php if ($isPersonalized): ?>
                            Recommended Projects
                            <span class="personalized-badge">
                                <i class="bi bi-star-fill"></i>
                            </span>
                        <?php else: ?>
                            Recent Projects
                        <?php endif; ?>
                    </h2>
                    <a href="Research_page.php" class="view-all-link">View All</a>
                </div>
                <div class="cards-grid">
                    <?php foreach ($projects as $project): ?>
                    <a href="Project_details.php?id=<?= $project['_id'] ?>" class="card<?= $isPersonalized && isset($project['relevance_score']) && $project['relevance_score'] > 0 ? ' recommended' : '' ?>" data-item-type="project" data-item-id="<?= $project['_id'] ?>">
                        <h3 class="card-title"><?= htmlspecialchars($project['title'] ?? 'Untitled Project') ?></h3>
                        <p class="card-content"><?= htmlspecialchars(substr($project['description'] ?? 'No description available', 0, 120)) ?>...</p>
                        
                        <!-- Add tags for projects if available -->
                        <?php if (isset($project['tags']) && is_array($project['tags']) && !empty($project['tags'])): ?>
                        <div class="card-tags" style="margin-bottom: 0.5rem;">
                            <?php foreach (array_slice($project['tags'], 0, 3) as $tag): ?>
                            <span class="tag"><?= htmlspecialchars($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-meta">
                            <span class="card-date">
                                <i class="bi bi-calendar3"></i>
                                <?php 
                                if (isset($project['createdAt'])) {
                                    if (is_object($project['createdAt']) && method_exists($project['createdAt'], 'toDateTime')) {
                                        echo $project['createdAt']->toDateTime()->format('M j, Y');
                                    } else {
                                        echo 'Recent';
                                    }
                                } else {
                                    echo 'Recent';
                                }
                                ?>
                            </span>
                            <span class="card-stats">
                                <span class="stat-item">
                                    <i class="bi bi-people"></i>
                                    <?= isset($project['members']) ? count($project['members']) : 0 ?>
                                </span>
                            </span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Events Section -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="bi bi-calendar-event section-icon"></i>
                        <?php if ($isPersonalized): ?>
                            Recommended Events
                            <span class="personalized-badge">
                                <i class="bi bi-star-fill"></i>
                            </span>
                        <?php else: ?>
                            Upcoming Events
                        <?php endif; ?>
                    </h2>
                    <a href="events.php" class="view-all-link">View All</a>
                </div>
                <div class="cards-grid">
                    <?php foreach ($events as $event): ?>
                    <a href="events.php#event-<?= $event['_id'] ?>" class="card<?= $isPersonalized && isset($event['relevance_score']) && $event['relevance_score'] > 0 ? ' recommended' : '' ?>" data-item-type="event" data-item-id="<?= $event['_id'] ?>">
                        <h3 class="card-title"><?= htmlspecialchars($event['title'] ?? 'Untitled Event') ?></h3>
                        <p class="card-content"><?= htmlspecialchars(substr($event['description'] ?? 'No description available', 0, 120)) ?>...</p>
                        
                        <!-- Add event type and tags -->
                        <div class="card-tags" style="margin-bottom: 0.5rem;">
                            <?php if (isset($event['eventType'])): ?>
                            <span class="tag event-type"><?= htmlspecialchars($event['eventType']) ?></span>
                            <?php endif; ?>
                            <?php if (isset($event['tags']) && is_array($event['tags'])): ?>
                                <?php foreach (array_slice($event['tags'], 0, 2) as $tag): ?>
                                <span class="tag"><?= htmlspecialchars($tag) ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-meta">
                            <span class="card-date">
                                <i class="bi bi-calendar3"></i>
                                <?php 
                                if (isset($event['eventDate'])) {
                                    if (is_array($event['eventDate']) && isset($event['eventDate']['$date'])) {
                                        $timestamp = $event['eventDate']['$date']['$numberLong'] ?? $event['eventDate']['$date'];
                                        echo date('M j, Y', $timestamp / 1000);
                                    } else {
                                        echo 'TBD';
                                    }
                                } else {
                                    echo 'TBD';
                                }
                                ?>
                            </span>
                            <span class="status-badge status-<?= strtolower($event['status'] ?? 'upcoming') ?>">
                                <?= htmlspecialchars($event['status'] ?? 'Upcoming') ?>
                            </span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Forum Posts Section -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="bi bi-chat-square-text section-icon"></i>
                        <?php if ($isPersonalized): ?>
                            Recommended Discussions
                            <span class="personalized-badge">
                                <i class="bi bi-star-fill"></i>
                            </span>
                        <?php else: ?>
                            Recent Discussions
                        <?php endif; ?>
                    </h2>
                    <a href="view_posts.php" class="view-all-link">View All</a>
                </div>
                <div class="cards-grid">
                    <?php foreach ($forumPosts as $post): ?>
                    <a href="post_details.php?id=<?= $post['_id'] ?>" class="card<?= $isPersonalized && isset($post['relevance_score']) && $post['relevance_score'] > 0 ? ' recommended' : '' ?>" data-item-type="forum_post" data-item-id="<?= $post['_id'] ?>">
                        <h3 class="card-title"><?= htmlspecialchars($post['title'] ?? 'Untitled Post') ?></h3>
                        <p class="card-content"><?= htmlspecialchars(substr($post['content'] ?? 'No content available', 0, 120)) ?>...</p>
                        
                        <?php if (isset($post['tags']) && is_array($post['tags'])): ?>
                        <div class="card-tags" style="margin-bottom: 0.5rem;">
                            <?php foreach (array_slice($post['tags'], 0, 2) as $tag): ?>
                            <span class="tag"><?= htmlspecialchars($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-meta">
                            <span class="card-date">
                                <i class="bi bi-person"></i>
                                <?= htmlspecialchars($post['user_name'] ?? 'Anonymous') ?>
                            </span>
                            <span class="card-stats">
                                <span class="stat-item">
                                    <i class="bi bi-arrow-up"></i>
                                    <?= $post['upvotes'] ?? 0 ?>
                                </span>
                            </span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Faculty Section -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="bi bi-mortarboard section-icon"></i>
                        <?php if ($isPersonalized): ?>
                            Recommended Faculty
                            <span class="personalized-badge">
                                <i class="bi bi-star-fill"></i>
                            </span>
                        <?php else: ?>
                            Faculty Members
                        <?php endif; ?>
                    </h2>
                    <a href="Faculty_Page.php" class="view-all-link">View All</a>
                </div>
                <div class="cards-grid">
                    <?php foreach ($faculties as $faculty): ?>
                    <a href="Faculty_Profile.php?id=<?= $faculty['_id'] ?>" class="card faculty-card<?= $isPersonalized && isset($faculty['relevance_score']) && $faculty['relevance_score'] > 0 ? ' recommended' : '' ?>" data-item-type="faculty" data-item-id="<?= $faculty['_id'] ?>">
                        <img src="<?= htmlspecialchars($faculty['profile_image'] ?? 'assets/resources/user_avater.png') ?>" 
                             alt="<?= htmlspecialchars($faculty['name'] ?? 'Faculty Member') ?>" 
                             class="faculty-avatar">
                        <h3 class="faculty-name"><?= htmlspecialchars($faculty['name'] ?? 'Faculty Member') ?></h3>
                        <p class="faculty-bio"><?= htmlspecialchars(substr($faculty['bio'] ?? 'No bio available', 0, 100)) ?>...</p>
                        
                        <div class="card-tags" style="margin-top: 0.5rem;">
                            <?php if (isset($faculty['specialty'])): ?>
                            <span class="tag specialty"><?= htmlspecialchars($faculty['specialty']) ?></span>
                            <?php endif; ?>
                            <?php if (isset($faculty['research_interests']) && is_array($faculty['research_interests'])): ?>
                                <?php foreach (array_slice($faculty['research_interests'], 0, 2) as $interest): ?>
                                <span class="tag research-interest"><?= htmlspecialchars($interest) ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include_once('src/includes/footer.php'); ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (isset($_SESSION['user_id'])): ?>
    <!-- User is logged in, enable advanced tracking -->
    <script>
        document.body.setAttribute('data-user-logged-in', 'true');
    </script>
    <?php endif; ?>
</body>
</html> 