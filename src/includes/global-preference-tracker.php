<?php
/**
 * Global Preference Tracking Initializer
 * Include this file in all pages to enable global preference tracking
 */

// Only initialize if user is logged in
if (isset($_SESSION['user_id'])) {
    // Include necessary files for preference tracking
    require_once __DIR__ . '/../model/user_preferences.php';
    require_once __DIR__ . '/../model/recommendation_engine.php';
    
    // Initialize global tracking variables
    $GLOBALS['preference_tracking_enabled'] = true;
    $GLOBALS['current_user_id'] = $_SESSION['user_id'];
    
    // Get current page information for tracking
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    $current_url = $_SERVER['REQUEST_URI'];
    
    // Track page view automatically
    $userPreferences = new UserPreferences();
    $pageMetadata = [
        'page_name' => $current_page,
        'url' => $current_url,
        'title' => isset($GLOBALS['page_title']) ? $GLOBALS['page_title'] : '',
        'referrer' => $_SERVER['HTTP_REFERER'] ?? ''
    ];
    
    $userPreferences->trackInteraction(
        $_SESSION['user_id'],
        'view',
        $current_page,
        'page',
        $pageMetadata
    );
    
    // Make recommendation engine available globally
    $GLOBALS['recommendation_engine'] = new RecommendationEngine();
} else {
    $GLOBALS['preference_tracking_enabled'] = false;
    $GLOBALS['current_user_id'] = null;
    $GLOBALS['recommendation_engine'] = null;
}

/**
 * Helper function to check if tracking is enabled
 */
function isPreferenceTrackingEnabled() {
    return $GLOBALS['preference_tracking_enabled'] ?? false;
}

/**
 * Helper function to get current user ID
 */
function getCurrentUserId() {
    return $GLOBALS['current_user_id'] ?? null;
}

/**
 * Helper function to get recommendation engine
 */
function getRecommendationEngine() {
    return $GLOBALS['recommendation_engine'] ?? null;
}

/**
 * Helper function to get personalized recommendations for current user
 */
function getPersonalizedRecommendations($type = 'mixed', $limit = 6) {
    if (!isPreferenceTrackingEnabled()) {
        return [];
    }
    
    $engine = getRecommendationEngine();
    if (!$engine) {
        return [];
    }
    
    $userId = getCurrentUserId();
    
    switch ($type) {
        case 'projects':
            return $engine->getRecommendedProjects($userId, $limit);
        case 'events':
            return $engine->getRecommendedEvents($userId, $limit);
        case 'forum_posts':
            return $engine->getRecommendedForumPosts($userId, $limit);
        case 'faculty':
            return $engine->getRecommendedFaculties($userId, $limit);
        case 'mixed':
        default:
            return $engine->getDashboardRecommendations($userId);
    }
}

/**
 * Output preference tracking JavaScript initialization
 */
function outputPreferenceTrackingScript() {
    if (!isPreferenceTrackingEnabled()) {
        return;
    }
    
    // Determine correct path to assets based on the including page's location
    // Since this function is called from footer.php which is included by pages in root directory,
    // we need to go from root directory to assets
    $scriptPath = 'assets/js/preference_tracker.js';
    
    // Check if the including page is in a subdirectory by looking at the REQUEST_URI
    $requestUri = $_SERVER['REQUEST_URI'];
    $scriptSelf = $_SERVER['SCRIPT_NAME'];
    
    // Count directory depth from root
    $pathParts = explode('/', trim($scriptSelf, '/'));
    $depth = count($pathParts) - 1; // -1 because the last part is the filename
    
    // Adjust script path based on depth
    if ($depth > 0) {
        $scriptPath = str_repeat('../', $depth) . 'assets/js/preference_tracker.js';
    }
    
    // Debug logging
    error_log("DEBUG: Script path determined as: $scriptPath for page: $scriptSelf");
    
    echo '<script src="' . $scriptPath . '"></script>';
    echo '<script>';
    echo 'document.addEventListener("DOMContentLoaded", function() {';
    echo '    // Check if PreferenceTracker is already initialized to avoid duplicates';
    echo '    if (typeof PreferenceTracker !== "undefined" && !window.preferenceTracker) {';
    echo '        window.preferenceTracker = new PreferenceTracker();';
    echo '        console.log("Global Preference Tracker initialized on page: " + window.location.pathname);';
    echo '    } else if (window.preferenceTracker) {';
    echo '        console.log("PreferenceTracker already initialized, skipping");';
    echo '    } else {';
    echo '        console.warn("PreferenceTracker class not found, script may not have loaded properly");';
    echo '        console.warn("Expected script path: ' . $scriptPath . '");';
    echo '    }';
    echo '});';
    echo '</script>';
}
?> 