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
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/model/db_connect.php';

// Import MongoDB classes
require_once __DIR__ . '/vendor/autoload.php';

// Add recommendation engine includes
require_once __DIR__ . '/src/model/recommendation_engine.php';
require_once __DIR__ . '/src/model/user_preferences.php';

use \MongoDB\BSON\UTCDateTime;
use \MongoDB\BSON\ObjectId;
use \MongoDB\Client;
use \MongoDB\Collection;
use \MongoDB\Driver\Exception\ConnectionException;



// Redirect if not logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}


// Initialize recommendation engine
$recommendationEngine = new RecommendationEngine();
$isPersonalized = false;
$personalizedPosts = [];

try {
    $client = connectToDatabase();
    $db = $client->uiurp;
    $forumPostsCollection = $db->forum_posts;
    
    // Get filter and sorting parameters
    // Default to 'recommended' if user is logged in, otherwise 'newest'
    $defaultSort = isset($_SESSION['user_id']) ? 'recommended' : 'newest';
    $sortOption = $_GET['sort'] ?? $defaultSort;
    $tagFilter = $_GET['tag'] ?? '';
    
    // Check if user wants personalized recommendations
    if ($sortOption === 'recommended' && isset($_SESSION['user_id'])) {
        // Get personalized forum posts from recommendation engine
        $userId = $_SESSION['user_id'];
        
        // Get a larger set of personalized posts (up to 50)
        $personalizedPosts = $recommendationEngine->getRecommendedForumPosts($userId, 50);
        
        // Check if we got personalized results
        $hasPersonalizedResults = false;
        foreach ($personalizedPosts as $post) {
            if (isset($post['relevance_score']) && $post['relevance_score'] > 0) {
                $hasPersonalizedResults = true;
                break;
            }
        }
        
        $isPersonalized = $hasPersonalizedResults;
        
        // Apply tag filter to personalized results if specified
        if (!empty($tagFilter)) {
            $personalizedPosts = array_filter($personalizedPosts, function($post) use ($tagFilter) {
                $tags = $post['tags'] ?? [];
                if ($tags instanceof MongoDB\Model\BSONArray) {
                    $tags = $tags->getArrayCopy();
                }
                return in_array($tagFilter, $tags);
            });
        }
        
        $allPosts = [];
        foreach ($personalizedPosts as $post) {
            try {
                $currentTime = new \MongoDB\BSON\UTCDateTime((int)(microtime(true) * 1000));
                
                // Convert BSONArray objects to PHP arrays
                $tags = $post['tags'] ?? ['discussion'];
                if ($tags instanceof MongoDB\Model\BSONArray) {
                    $tags = $tags->getArrayCopy();
                }
                
                $upvotedBy = $post['upvoted_by'] ?? [];
                if ($upvotedBy instanceof MongoDB\Model\BSONArray) {
                    $upvotedBy = $upvotedBy->getArrayCopy();
                }
                
                $comments = $post['comments'] ?? [];
                if ($comments instanceof MongoDB\Model\BSONArray) {
                    $comments = $comments->getArrayCopy();
                }
                
                $attachments = $post['attachments'] ?? [];
                if ($attachments instanceof MongoDB\Model\BSONArray) {
                    $attachments = $attachments->getArrayCopy();
                }
                
                $mappedPost = [
                    '_id' => $post['_id'],
                    'user_id' => $post['user_id'] ?? null,
                    'user_name' => $post['user_name'] ?? 'Unknown User',
                    'user_profile_pic' => $post['user_profile_pic'] ?? 'uploads/profile_images/user_avater.png',
                    'title' => $post['title'] ?? 'Untitled Post',
                    'content' => $post['content'] ?? '',
                    'tags' => $tags,
                    'upvotes' => $post['upvotes'] ?? 0,
                    'upvoted_by' => $upvotedBy,
                    'comments' => $comments,
                    'created_at' => $post['created_at'] ?? $currentTime,
                    'updated_at' => $post['updated_at'] ?? $post['created_at'] ?? $currentTime,
                    'attachments' => $attachments,
                    'relevance_score' => $post['relevance_score'] ?? 0,
                    'is_recommended' => isset($post['relevance_score']) && $post['relevance_score'] > 0
                ];
                $allPosts[] = $mappedPost;
            } catch (Exception $e) {
                error_log("Error mapping personalized post: " . $e->getMessage());
                continue;
            }
        }
        
        // Debug output for personalized posts
        error_log("Personalized posts: " . count($allPosts) . " (Personalized: " . ($isPersonalized ? 'Yes' : 'No') . ")");
        
    } else {
        // Use traditional database query for other sorting options
        
        // Build query for forum_posts collection
        $queryPosts = [];
        if (!empty($tagFilter)) {
            // Use $in operator to find posts that contain the specified tag
            $queryPosts['tags'] = ['$in' => [$tagFilter]];
        }
        
        // Set up sorting
        $sortPosts = [];
        switch ($sortOption) {
            case 'upvotes':
                $sortPosts = ['upvotes' => -1];
                break;
            case 'newest':
            default:
                $sortPosts = ['created_at' => -1];
                break;
        }
        
        // Fetch posts from forum_posts collection
        $optionsPosts = ['sort' => $sortPosts];
        $newPosts = $forumPostsCollection->find($queryPosts, $optionsPosts)->toArray();
        
        // Debug output
        error_log("Found " . count($newPosts) . " posts in forum_posts collection");
        
        // Map posts to ensure consistent structure
        $allPosts = [];
        foreach ($newPosts as $post) {
            try {
                $currentTime = new \MongoDB\BSON\UTCDateTime((int)(microtime(true) * 1000));
                
                // Convert BSONArray objects to PHP arrays
                $tags = $post['tags'] ?? ['discussion'];
                if ($tags instanceof MongoDB\Model\BSONArray) {
                    $tags = $tags->getArrayCopy();
                }
                
                $upvotedBy = $post['upvoted_by'] ?? [];
                if ($upvotedBy instanceof MongoDB\Model\BSONArray) {
                    $upvotedBy = $upvotedBy->getArrayCopy();
                }
                
                $comments = $post['comments'] ?? [];
                if ($comments instanceof MongoDB\Model\BSONArray) {
                    $comments = $comments->getArrayCopy();
                }
                
                $attachments = $post['attachments'] ?? [];
                if ($attachments instanceof MongoDB\Model\BSONArray) {
                    $attachments = $attachments->getArrayCopy();
                }
                
                $mappedPost = [
                    '_id' => $post['_id'],
                    'user_id' => $post['user_id'] ?? null,
                    'user_name' => $post['user_name'] ?? 'Unknown User',
                    'user_profile_pic' => $post['user_profile_pic'] ?? 'uploads/profile_images/user_avater.png',
                    'title' => $post['title'] ?? 'Untitled Post',
                    'content' => $post['content'] ?? '',
                    'tags' => $tags,
                    'upvotes' => $post['upvotes'] ?? 0,
                    'upvoted_by' => $upvotedBy,
                    'comments' => $comments,
                    'created_at' => $post['created_at'] ?? $currentTime,
                    'updated_at' => $post['updated_at'] ?? $post['created_at'] ?? $currentTime,
                    'attachments' => $attachments,
                    'relevance_score' => 0,
                    'is_recommended' => false
                ];
                $allPosts[] = $mappedPost;
            } catch (Exception $e) {
                error_log("Error mapping post: " . $e->getMessage());
                continue;
            }
        }
        
        // Debug output
        error_log("Mapped " . count($allPosts) . " posts total");
        
        // Sort all posts according to sorting option
        if ($sortOption === 'upvotes') {
            usort($allPosts, function($a, $b) {
                return ($b['upvotes'] ?? 0) - ($a['upvotes'] ?? 0);
            });
        } else {
            // Sort by creation date
            usort($allPosts, function($a, $b) {
                $timeA = isset($a['created_at']) && !empty($a['created_at']) ? 
                    (is_object($a['created_at']) && method_exists($a['created_at'], 'toDateTime') ? 
                        $a['created_at']->toDateTime()->getTimestamp() : 
                        (is_object($a['created_at']) ? $a['created_at']->__toString() : 0)) : 0;
                
                $timeB = isset($b['created_at']) && !empty($b['created_at']) ? 
                    (is_object($b['created_at']) && method_exists($b['created_at'], 'toDateTime') ? 
                        $b['created_at']->toDateTime()->getTimestamp() : 
                        (is_object($b['created_at']) ? $b['created_at']->__toString() : 0)) : 0;
                
                return $timeB - $timeA;
            });
        }
    }
    
    // Get all available tags
    $tagCollection = $db->forum_tags;
    $allTags = $tagCollection->find()->toArray();
    
    // Debug output
    error_log("Found " . count($allTags) . " tags");
    
} catch (Exception $e) {
    // Log the error and show a user-friendly message
    error_log("Forum error: " . $e->getMessage());
    $error = "An error occurred while loading the forum. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum - UIU Research Portal</title>
    
    <!-- Core styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <!-- Animation libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <!-- Custom styles -->
    <link rel="stylesheet" href="assets/styles/home.css">
    <link rel="stylesheet" href="assets/styles/theme.css">
    <link rel="stylesheet" href="assets/styles/forum_style.css">
    <!-- Performance optimization styles -->
    <link rel="stylesheet" href="assets/styles/performance.css">
    
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
    
    <!-- Performance optimization script - Load early for immediate optimizations -->
    <script src="assets/js/performance-optimizer.js" defer></script>
    
    <!-- Preload fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Modern UI Styles with Theme Support */
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
            --success-color: #06b6d4;
            --warning-color: #f59e0b;
            --modern-blue: #0ea5e9;
            --modern-purple: #8b5cf6;
            --modern-teal: #14b8a6;
            --modern-gray: #6b7280;
            
            /* Dark Theme Variables (Default) */
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --bg-gradient: linear-gradient(135deg, #0a0d1a 0%, #1a1a2e 50%, #16213e 100%);
            --card-bg: rgba(15, 23, 42, 0.6);
            --glass-bg: rgba(22, 28, 45, 0.7);
            --surface-1: rgba(30, 41, 59, 0.6);
            --surface-2: rgba(15, 23, 42, 0.8);
            --border-color: rgba(37, 99, 235, 0.1);
            --border-glow: rgba(37, 99, 235, 0.5);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --text-muted: rgba(255, 255, 255, 0.5);
            --text-link: #4cc9f0;
        }

        /* Light Theme Variables */
        [data-theme="light"] {
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #e2e8f0;
            --bg-gradient: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 50%, #cbd5e1 100%);
            --card-bg: rgba(255, 255, 255, 0.8);
            --glass-bg: rgba(248, 250, 252, 0.9);
            --surface-1: rgba(248, 250, 252, 0.8);
            --surface-2: rgba(241, 245, 249, 0.9);
            --border-color: rgba(67, 97, 238, 0.15);
            --border-glow: rgba(67, 97, 238, 0.3);
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --text-link: #2563eb;
        }

        body {
            background: var(--bg-gradient);
            font-family: 'Inter', 'Segoe UI', sans-serif;
            color: var(--text-primary);
            min-height: 100vh;
            position: relative;
            transition: background 0.3s ease, color 0.3s ease;
        }

        /* Background Effects */
        .background-effects {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .cyber-grid {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(to right, rgba(37, 99, 235, 0.05) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(37, 99, 235, 0.05) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            animation: grid-pulse 4s ease-in-out infinite;
            transition: opacity 0.3s ease;
        }

        /* Light theme grid */
        [data-theme="light"] .cyber-grid {
            background-image: 
                linear-gradient(to right, rgba(67, 97, 238, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(67, 97, 238, 0.03) 1px, transparent 1px);
            opacity: 0.6;
        }

        .floating-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(40px);
            opacity: 0.4;
            animation: float-orb 15s ease-in-out infinite;
            box-shadow: 0 0 50px currentColor;
            transition: opacity 0.3s ease;
        }

        .orb-1 {
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.3) 0%, rgba(37, 99, 235, 0.1) 50%, transparent 70%);
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.25) 0%, rgba(14, 165, 233, 0.08) 50%, transparent 70%);
            top: 60%;
            right: 10%;
            animation-delay: 7s;
        }

        .orb-3 {
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.3) 0%, rgba(6, 182, 212, 0.1) 50%, transparent 70%);
            bottom: 20%;
            left: 20%;
            animation-delay: 14s;
        }

        /* Light theme orb adjustments */
        [data-theme="light"] .floating-orb {
            opacity: 0.2;
        }

        [data-theme="light"] .orb-1 {
            background: radial-gradient(circle, rgba(67, 97, 238, 0.15) 0%, rgba(67, 97, 238, 0.05) 50%, transparent 70%);
        }

        [data-theme="light"] .orb-2 {
            background: radial-gradient(circle, rgba(14, 165, 233, 0.12) 0%, rgba(14, 165, 233, 0.04) 50%, transparent 70%);
        }

        [data-theme="light"] .orb-3 {
            background: radial-gradient(circle, rgba(6, 182, 212, 0.15) 0%, rgba(6, 182, 212, 0.05) 50%, transparent 70%);
        }

        @keyframes float-orb {
            0%, 100% { transform: translate(0, 0) scale(1) rotate(0deg); }
            25% { transform: translate(30px, -20px) scale(1.05) rotate(90deg); }
            50% { transform: translate(-20px, 30px) scale(0.95) rotate(180deg); }
            75% { transform: translate(25px, 15px) scale(1.02) rotate(270deg); }
        }

        @keyframes grid-pulse {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.6; }
        }

        /* Forum Specific Styles */
        .forum-container {
            position: relative;
            z-index: 1;
            padding: 2rem 0;
        }

        .forum-title {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--text-primary);
            font-weight: 700;
            font-size: 2.5rem;
            background: linear-gradient(135deg, var(--modern-blue), var(--modern-purple));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .create-post-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease, border-color 0.3s ease;
        }

        .create-post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .filters-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            transition: background 0.3s ease, border-color 0.3s ease;
        }

        .forum-post {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease, border-color 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .forum-post *:not(.post-tag):not(.btn):not(.badge):not(.alert):not(.dropdown-menu):not(.dropdown-item) {
            background-color: transparent !important;
        }

        /* Ensure no white backgrounds override our theme */
        .forum-post .post-header,
        .forum-post .post-content {
            background-color: transparent !important;
        }

        /* Override any Bootstrap card styles */
        .card,
        .card-body,
        .card-header {
            background-color: transparent !important;
        }

        /* Ensure comment bubbles don't have white backgrounds */
        .comment-bubble {
            background-color: var(--surface-1) !important;
        }

        /* Override any potential white backgrounds */
        .bg-white {
            background-color: transparent !important;
        }

        [data-theme="light"] .bg-white {
            background-color: var(--bg-primary) !important;
        }

        .forum-post:hover {
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
        }

        .forum-post::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, 
                rgba(76, 201, 240, 0.02), 
                rgba(114, 9, 183, 0.02));
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: 1rem;
            pointer-events: none;
        }

        .forum-post:hover::before {
            opacity: 1;
        }

        /* Light theme specific adjustments */
        [data-theme="light"] .create-post-card:hover {
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
        }

        [data-theme="light"] .forum-post:hover {
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.08);
        }

        [data-theme="light"] .forum-post::before {
            background: linear-gradient(135deg, 
                rgba(67, 97, 238, 0.03), 
                rgba(139, 92, 246, 0.03));
        }

        .post-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: transparent;
            transition: border-color 0.3s ease, background 0.3s ease;
        }

        .post-content {
            padding: 1.5rem;
            background: transparent;
            transition: background 0.3s ease;
        }

        .post-footer {
            padding: 1rem 1.5rem;
            background: var(--surface-1);
            border-top: 1px solid var(--border-color);
            transition: background 0.3s ease, border-color 0.3s ease;
        }

        .post-tag {
            background: var(--modern-blue) !important;
            color: white !important;
            padding: 0.3rem 1rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            font-weight: 500;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            display: inline-block;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
        }

        .post-tag:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--border-glow);
            color: white !important;
        }

        /* Specific tag color variations */
        .post-tag.tag-discussion {
            background: var(--modern-blue) !important;
        }

        .post-tag.tag-announcement {
            background: var(--warning-color) !important;
        }

        .post-tag.tag-question {
            background: var(--modern-purple) !important;
        }

        .post-tag.tag-tutorial {
            background: var(--modern-teal) !important;
        }

        .post-tag.tag-resource {
            background: var(--success-color) !important;
        }

        .post-tag.tag-member_recruitment {
            background: #28a745 !important;
        }

        .post-tag.tag-bug_fixes {
            background: #dc3545 !important;
        }

        /* Post tags container styling */
        .post-tags {
            margin-bottom: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        /* Ensure tags maintain their styling in both themes */
        [data-theme="light"] .post-tag {
            background: var(--modern-blue) !important;
            color: white !important;
        }

        [data-theme="light"] .post-tag.tag-discussion {
            background: var(--modern-blue) !important;
        }

        [data-theme="light"] .post-tag.tag-announcement {
            background: var(--warning-color) !important;
        }

        [data-theme="light"] .post-tag.tag-question {
            background: var(--modern-purple) !important;
        }

        [data-theme="light"] .post-tag.tag-tutorial {
            background: var(--modern-teal) !important;
        }

        [data-theme="light"] .post-tag.tag-resource {
            background: var(--success-color) !important;
        }

        [data-theme="light"] .post-tag.tag-member_recruitment {
            background: #28a745 !important;
        }

        [data-theme="light"] .post-tag.tag-bug_fixes {
            background: #dc3545 !important;
        }
        
        .comment-section {
            background: var(--surface-1);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
            transition: background 0.3s ease;
        }

        .comment {
            background: var(--surface-2);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: background 0.3s ease;
        }

        /* Form Controls */
        .form-control {
            background: var(--surface-1);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 0.5rem;
            transition: background 0.3s ease, border-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-control:focus {
            background: var(--surface-2);
            border-color: var(--modern-blue);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.2rem var(--border-glow);
            outline: none;
        }

        .form-control::placeholder {
            color: var(--text-muted);
            transition: color 0.3s ease;
        }

        .form-select {
            background: var(--surface-1);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 0.5rem;
            transition: background 0.3s ease, border-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-select:focus {
            background: var(--surface-2);
            border-color: var(--modern-blue);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.2rem var(--border-glow);
            outline: none;
        }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--modern-blue), var(--modern-purple));
            border: none;
            border-radius: 0.5rem;
            padding: 0.5rem 1.5rem;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--border-glow);
            color: white;
        }

        .btn-secondary {
            background: var(--surface-1);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 0.5rem;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: var(--surface-2);
            color: var(--text-primary);
            transform: translateY(-1px);
        }

        .btn-outline-secondary {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .btn-outline-secondary:hover {
            background: var(--surface-1);
            color: var(--text-primary);
            border-color: var(--modern-blue);
        }

        .btn-link {
            color: var(--text-secondary);
            transition: all 0.3s ease;
        }

        .btn-link:hover {
            color: var(--text-primary);
        }

        /* Light theme text and link adjustments */
        [data-theme="light"] .text-dark {
            color: var(--text-primary) !important;
        }

        [data-theme="light"] .text-muted {
            color: var(--text-muted) !important;
        }

        [data-theme="light"] .text-decoration-none {
            color: var(--text-primary);
        }

        [data-theme="light"] .text-decoration-none:hover {
            color: var(--text-link);
        }

        [data-theme="light"] .alert-info {
            background-color: var(--surface-1);
            border-color: var(--border-color);
            color: var(--text-primary);
        }

        [data-theme="light"] .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-color: rgba(220, 53, 69, 0.2);
            color: #721c24;
        }

        /* Modal adjustments for light theme */
        [data-theme="light"] .modal-content {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
        }

        [data-theme="light"] .modal-header {
            border-bottom-color: var(--border-color);
        }

        [data-theme="light"] .modal-footer {
            border-top-color: var(--border-color);
        }

        [data-theme="light"] .modal-title {
            color: var(--text-primary);
        }

        [data-theme="light"] .modal-body {
            color: var(--text-primary);
        }

        /* Create post input styling */
        .create-post-input {
            background: var(--surface-1);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            border-radius: 2rem;
            padding: 1rem 1.5rem;
            transition: all 0.3s ease;
        }

        .create-post-input:hover {
            background: var(--surface-2);
            border-color: var(--modern-blue);
            color: var(--text-secondary);
        }

        /* Author avatar styling */
        .author-avatar {
            border: 2px solid var(--border-color);
            transition: border-color 0.3s ease;
        }

        /* Post meta and author styling */
        .post-author {
            display: flex;
            align-items: center;
        }

        .post-meta {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            width: 100%;
        }

        /* Attachment styling improvements */
        .attachments {
            margin-top: 1rem;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }

        .attachment-item {
            background: var(--surface-1);
            border-radius: 0.5rem;
            overflow: hidden;
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .attachment-item:hover {
            transform: translateY(-2px);
            background: var(--surface-2);
        }

        .attachment-img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 0.5rem;
        }

        .attachment-file {
            padding: 1rem;
            display: flex;
            align-items: center;
            color: var(--text-primary);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .attachment-file:hover {
            color: var(--text-link);
        }

        .attachment-file i {
            margin-right: 0.5rem;
            font-size: 1.2rem;
        }

        /* Post-specific element styling for theme support */
        .post-title {
            color: var(--text-primary);
            transition: color 0.3s ease;
        }

        .post-title:hover {
            color: var(--text-link);
        }

        .post-text {
            color: var(--text-primary);
            transition: color 0.3s ease;
        }

        .fw-bold {
            color: var(--text-primary) !important;
            transition: color 0.3s ease;
        }

        .small {
            color: var(--text-muted) !important;
            transition: color 0.3s ease;
        }

        /* Post footer button styling */
        .post-footer .btn-link {
            color: var(--text-muted);
            border: none !important;
            transition: all 0.3s ease;
        }

        .post-footer .btn-link:hover {
            color: var(--text-link);
            background: var(--surface-2);
        }

        .post-footer .btn-link.text-primary {
            color: var(--modern-blue) !important;
        }

        .post-footer .btn-link.text-primary:hover {
            color: var(--modern-blue) !important;
        }

        /* Reaction and comment count styling */
        .reaction-count {
            color: var(--text-muted);
            transition: color 0.3s ease;
        }

        .upvote-count {
            color: var(--text-muted);
            transition: color 0.3s ease;
        }

        /* Comment bubble styling */
        .comment-bubble {
            background: var(--surface-1);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .comment-author {
            color: var(--text-primary) !important;
            transition: color 0.3s ease;
        }

        .comment-content {
            color: var(--text-primary);
            transition: color 0.3s ease;
        }

        .comment-time {
            color: var(--text-muted) !important;
            transition: color 0.3s ease;
        }

        /* Dropdown menu styling */
        .dropdown-menu {
            background: var(--glass-bg);
            border: 1px solid var(--border-color);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .dropdown-item {
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background: var(--surface-1);
            color: var(--text-primary);
        }

        .dropdown-item.text-danger {
            color: #dc3545 !important;
        }

        .dropdown-item.text-danger:hover {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545 !important;
        }

        /* Light theme specific overrides */
        [data-theme="light"] .bg-light {
            background-color: var(--surface-1) !important;
        }

        [data-theme="light"] .text-primary {
            color: var(--text-primary) !important;
        }

        [data-theme="light"] .post-footer .btn-link.text-muted {
            color: var(--text-muted) !important;
        }

        [data-theme="light"] .post-footer .btn-link.text-primary {
            color: var(--modern-blue) !important;
        }

        [data-theme="light"] .post-footer .btn-link.fw-bold {
            color: var(--modern-blue) !important;
        }

        /* Bootstrap override for consistent theming */
        .text-dark {
            color: var(--text-primary) !important;
            transition: color 0.3s ease;
        }

        .text-muted {
            color: var(--text-muted) !important;
            transition: color 0.3s ease;
        }

        .text-secondary {
            color: var(--text-secondary) !important;
            transition: color 0.3s ease;
        }

        /* Specific styling for user names and meta info */
        .post-meta .fw-bold {
            color: var(--text-primary) !important;
        }

        .post-meta .text-muted {
            color: var(--text-muted) !important;
        }

        .post-meta .small {
            color: var(--text-muted) !important;
        }

        /* Header info styling */
        .header-name {
            color: var(--text-primary) !important;
        }

        .header-email {
            color: var(--text-secondary) !important;
        }

        /* Comment meta styling */
        .comment-meta .fw-bold {
            color: var(--text-primary) !important;
        }

        .comment-meta .comment-author {
            color: var(--text-primary) !important;
        }

        /* Action button text colors */
        .upvote-btn {
            color: var(--text-muted) !important;
            transition: color 0.3s ease;
        }

        .upvote-btn:hover {
            color: var(--text-link) !important;
        }

        .upvote-btn.text-primary {
            color: var(--modern-blue) !important;
        }

        .upvote-btn.fw-bold {
            color: var(--modern-blue) !important;
        }

        .toggle-comments-btn {
            color: var(--text-muted) !important;
            transition: color 0.3s ease;
        }

        .toggle-comments-btn:hover {
            color: var(--text-link) !important;
        }

        .toggle-comments-btn.text-primary {
            color: var(--modern-blue) !important;
        }

        .share-btn {
            color: var(--text-muted) !important;
            transition: color 0.3s ease;
        }

        .share-btn:hover {
            color: var(--text-link) !important;
        }

        /* Forum title styling enhancements */
        .forum-title {
            position: relative;
        }

        .forum-subtitle {
            font-size: 1.1rem;
            font-weight: 400;
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* PDF Preview Modal Styles */
        .pdf-preview-modal .modal-dialog {
            max-width: 90%;
            height: 90vh;
            margin: 1.75rem auto;
        }

        .pdf-preview-modal .modal-content {
            height: 100%;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
        }

        .pdf-preview-modal .modal-body {
            padding: 0;
            display: flex;
            flex-direction: column;
            height: calc(100% - 120px); /* Adjust for header and footer */
        }

        .pdf-preview-container {
            flex: 1;
            overflow: auto;
            background: var(--surface-1);
            border-radius: 0.5rem;
            margin: 1rem;
            position: relative;
        }

        #pdf-viewer {
            width: 100%;
            height: 100%;
            border: none;
        }

        .pdf-controls {
            display: flex;
            justify-content: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--surface-2);
            border-top: 1px solid var(--border-color);
        }

        .pdf-page-info {
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pdf-error {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: var(--text-primary);
            background: var(--glass-bg);
            padding: 2rem;
            border-radius: 0.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            max-width: 80%;
        }

        .loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .pdf-preview-modal .btn-close {
            filter: invert(var(--text-primary-invert, 0));
        }

        /* Ensure PDF preview modal works well in both themes */
        [data-theme="light"] .pdf-preview-modal .modal-content {
            background: var(--glass-bg);
        }

        [data-theme="light"] .pdf-preview-container {
            background: var(--surface-1);
        }

        [data-theme="light"] .pdf-controls {
            background: var(--surface-2);
        }

        [data-theme="light"] .pdf-error {
            background: var(--glass-bg);
        }

        /* Document Preview Modal Styles */
        .document-preview-modal .modal-dialog {
            max-width: 90%;
            height: 90vh;
            margin: 1.75rem auto;
        }

        .document-preview-modal .modal-content {
            height: 100%;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
        }

        .document-preview-modal .modal-body {
            padding: 0;
            display: flex;
            flex-direction: column;
            height: calc(100% - 120px); /* Adjust for header and footer */
        }

        .document-preview-container {
            flex: 1;
            overflow: auto;
            background: var(--surface-1);
            border-radius: 0.5rem;
            margin: 1rem;
            position: relative;
        }

        #pdf-viewer {
            width: 100%;
            height: 100%;
            border: none;
        }

        .text-viewer {
            width: 100%;
            height: 100%;
            padding: 1.5rem;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            color: var(--text-primary);
            background: transparent;
            border: none;
            overflow: auto;
            font-size: 14px;
            line-height: 1.5;
        }

        .document-icon {
            font-size: 5rem;
            color: var(--modern-blue);
        }

        .document-info {
            color: var(--text-primary);
            padding: 2rem;
        }

        .document-controls {
            display: flex;
            justify-content: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--surface-2);
            border-top: 1px solid var(--border-color);
        }

        .pdf-page-info {
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pdf-error, .document-error {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: var(--text-primary);
            background: var(--glass-bg);
            padding: 2rem;
            border-radius: 0.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            max-width: 80%;
        }

        .loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .document-preview-modal .btn-close {
            filter: invert(var(--text-primary-invert, 0));
        }

        /* File type specific icons */
        .doc-icon { color: #2b579a; } /* Word blue */
        .ppt-icon { color: #d24726; } /* PowerPoint orange */
        .txt-icon { color: #4caf50; } /* Green for text */
        .pdf-icon { color: #f44336; } /* Red for PDF */
        .file-icon { color: var(--modern-blue); } /* Default */

        /* Ensure document preview modal works well in both themes */
        [data-theme="light"] .document-preview-modal .modal-content {
            background: var(--glass-bg);
        }

        [data-theme="light"] .document-preview-container {
            background: var(--surface-1);
        }

        [data-theme="light"] .document-controls {
            background: var(--surface-2);
        }

        [data-theme="light"] .document-error {
            background: var(--glass-bg);
        }

        /* Comment form styling */
        .add-comment-form .position-relative {
            display: flex;
            align-items: center;
        }

        .add-comment-form textarea {
            padding: 0.75rem 3rem 0.75rem 1rem;
            resize: none;
            min-height: 40px;
            max-height: 120px;
            border-radius: 20px;
            line-height: 1.5;
            background: var(--surface-1);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .add-comment-form textarea:focus {
            background: var(--surface-2);
            border-color: var(--modern-blue);
            box-shadow: 0 0 0 0.2rem var(--border-glow);
        }

        .add-comment-form .btn-link {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--modern-blue);
            padding: 0.25rem 0.5rem;
            transition: all 0.3s ease;
        }

        .add-comment-form .btn-link:hover {
            color: var(--modern-purple);
            transform: translateY(-50%) scale(1.1);
        }

        /* Modal style fixes */
        .modal-content {
            background: var(--glass-bg); 
            backdrop-filter: blur(10px); 
            border: 1px solid var(--border-color);
        }
        
        .modal-header {
            border-bottom: 1px solid var(--border-color);
        }
        
        .modal-title {
            color: var(--text-primary);
        }
        
        .modal-body {
            color: var(--text-primary);
        }
        
        .modal-footer {
            border-top: 1px solid var(--border-color);
        }

        /* Note: The JS errors about MongoDB\BSON\UTCDateTime are not actual runtime errors, they're just TypeScript/linter errors 
           which don't impact the functionality. They're already properly handled in the PHP code. */
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        // Set worker path for PDF.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    </script>
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

    <div class="forum-container">
        <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

            <h1 class="forum-title" data-aos="fade-down">
                Research Forum
            </h1>
            <?php if ($sortOption === 'recommended' && isset($_SESSION['user_id']) && !$isPersonalized): ?>
            <p class="forum-subtitle text-center" style="color: var(--text-muted); margin-bottom: 2rem;">
                <small>Keep interacting with posts to get personalized recommendations!</small>
            </p>
            <?php endif; ?>

        <!-- Create Post Card -->
            <div class="create-post-card" data-aos="fade-up">
                <div class="create-post-header d-flex align-items-center">
                    <img src="<?= $_SESSION['profile_pic'] ?? 'uploads/profile_images/user_avater.png' ?>" alt="Your Avatar" class="author-avatar" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 15px;">
                    <a href="#" class="create-post-input text-decoration-none flex-grow-1 p-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#createPostModal">
                        What's on your mind, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>?
                    </a>
                </div>
                <div class="d-flex justify-content-center border-top pt-3 mt-3">
                    <a href="#" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#createPostModal">
                        <i class="bi bi-pencil-square me-2"></i>Create New Post
                    </a>
                </div>
            </div>

        <!-- Filters Card -->
            <div class="filters-card" data-aos="fade-up" data-aos-delay="100">
            <form method="GET" class="d-flex gap-2">
                <select name="sort" class="form-select" onchange="this.form.submit()">
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <option value="recommended" <?= $sortOption == 'recommended' ? 'selected' : '' ?>>
                        Recommended for You
                    </option>
                    <?php endif; ?>
                    <option value="newest" <?= $sortOption == 'newest' ? 'selected' : '' ?>>Newest</option>
                    <option value="upvotes" <?= $sortOption == 'upvotes' ? 'selected' : '' ?>>Most Upvoted</option>
                </select>
                
                <select name="tag" class="form-select" onchange="this.form.submit()">
                    <option value="">All Tags</option>
                    <?php foreach ($allTags as $tag): ?>
                        <option value="<?= $tag['name'] ?>" <?= $tagFilter == $tag['name'] ? 'selected' : '' ?>>
                            <?= ucfirst(str_replace('_', ' ', $tag['name'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <?php if (!empty($tagFilter) || $sortOption != 'newest'): ?>
                    <a href="view_posts.php" class="btn btn-outline-secondary">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div id="posts-container">
            <?php if (empty($allPosts)): ?>
                    <div class="alert alert-info fade-in" data-aos="fade-up">No posts found. Be the first to create a post!</div>
            <?php else: ?>
                    <?php foreach ($allPosts as $index => $post): ?>
                        <?php
                        // Prepare tracking metadata for forum posts
                        $trackingData = [
                            'title' => $post['title'],
                            'content_preview' => substr($post['content'], 0, 100),
                            'tags' => $post['tags'] ?? [],
                            'author' => $post['user_name'] ?? 'Unknown User',
                            'upvotes' => $post['upvotes'] ?? 0,
                            'comment_count' => count($post['comments'] ?? [])
                        ];
                        ?>
                        <div class="forum-post fade-in" 
                             id="post-<?= $post['_id'] ?>" 
                             data-aos="fade-up" 
                             data-aos-delay="<?= $index * 50 ?>"
                             data-item-type="forum_post" 
                             data-item-id="<?= $post['_id'] ?>"
                             data-tracking-metadata='<?= htmlspecialchars(json_encode($trackingData), ENT_QUOTES, 'UTF-8') ?>'>
                        <div class="post-header">
                            <div class="post-meta">
                                <div class="post-author">
                                    <img src="<?= $post['user_profile_pic'] ?? 'uploads/profile_images/user_avater.png' ?>" alt="Avatar" class="author-avatar">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($post['user_name'] ?? 'Unknown User') ?></div>
                                        <div class="text-muted small">
                                            <?php if (isset($post['created_at']) && !empty($post['created_at'])): ?>
                                                <?php
                                                    $dateTime = null;
                                                    if (is_object($post['created_at']) && method_exists($post['created_at'], 'toDateTime')) {
                                                        $dateTime = $post['created_at']->toDateTime();
                                                            $dateTime->setTimezone(new DateTimeZone('Asia/Dhaka')); // Set to Bangladesh timezone
                                                    } elseif (is_string($post['created_at']) || is_numeric($post['created_at'])) {
                                                        $dateTime = new DateTime('@' . (int)$post['created_at']);
                                                            $dateTime->setTimezone(new DateTimeZone('Asia/Dhaka')); // Set to Bangladesh timezone
                                                    }
                                                    
                                                        echo $dateTime ? $dateTime->format('M j, Y \a\t g:i a') : 'Unknown date';
                                                    
                                                    // Check if updated
                                                    if (isset($post['updated_at']) && !empty($post['updated_at'])) {
                                                        $updateDateTime = null;
                                                        if (is_object($post['updated_at']) && method_exists($post['updated_at'], 'toDateTime')) {
                                                            $updateDateTime = $post['updated_at']->toDateTime();
                                                                $updateDateTime->setTimezone(new DateTimeZone('Asia/Dhaka')); // Set to Bangladesh timezone
                                                        } elseif (is_string($post['updated_at']) || is_numeric($post['updated_at'])) {
                                                            $updateDateTime = new DateTime('@' . (int)$post['updated_at']);
                                                                $updateDateTime->setTimezone(new DateTimeZone('Asia/Dhaka')); // Set to Bangladesh timezone
                                                        }
                                                        
                                                        if ($dateTime && $updateDateTime && $updateDateTime->getTimestamp() > $dateTime->getTimestamp()) {
                                                            echo ' (edited)';
                                                        }
                                                    }
                                                ?>
                                            <?php else: ?>
                                                Unknown date
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (isset($_SESSION['user_id']) && isset($post['user_id']) && $_SESSION['user_id'] === $post['user_id']): ?>
                                    <div class="post-actions">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item edit-post-btn" href="edit_post.php?id=<?= $post['_id'] ?>">Edit</a></li>
                                                <li><a class="dropdown-item delete-post-btn" href="#" data-post-id="<?= $post['_id'] ?>">Delete</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="post-content">
                            <h3 class="post-title mb-3">
                                    <?= htmlspecialchars($post['title']) ?>
                            </h3>
                            
                            <div class="post-tags mb-3">
                                <?php 
                                // Tags are already converted in the mapping function
                                if (!empty($post['tags'])):
                                    foreach ($post['tags'] as $tag): 
                                ?>
                                        <span class="post-tag tag-<?= $tag ?>">
                                            <?= ucfirst(str_replace('_', ' ', $tag)) ?>
                                        </span>
                                <?php 
                                    endforeach;
                                endif; 
                                ?>
                            </div>
                            
                            <div class="post-text mb-3">
                                    <?= nl2br(htmlspecialchars($post['content'])) ?>
                            </div>
                            
                            <?php if (!empty($post['attachments'])): ?>
                                <div class="attachments">
                                    <?php foreach ($post['attachments'] as $attachment): ?>
                                        <?php 
                                        $isImage = strpos($attachment['file_type'], 'image/') === 0;
                                        $fileExt = pathinfo($attachment['original_name'], PATHINFO_EXTENSION);
                                        ?>
                                        
                                        <div class="attachment-item">
                                            <a href="<?= $attachment['file_path'] ?>" target="_blank" class="text-decoration-none">
                                                <?php if ($isImage): ?>
                                                    <img src="<?= $attachment['file_path'] ?>" alt="Attachment" class="attachment-img">
                                                <?php else: ?>
                                                    <div class="attachment-file">
                                                        <i class="bi bi-file-earmark"></i>
                                                        <span><?= htmlspecialchars($attachment['original_name']) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="post-footer">
                            <!-- Reaction counts display -->
                            <div class="d-flex align-items-center mb-2">
                                <div class="reaction-count">
                                    <i class="bi bi-hand-thumbs-up-fill text-primary"></i>
                                    <span class="upvote-count" data-post-id="<?= $post['_id'] ?>"><?= $post['upvotes'] ?? 0 ?></span>
                                </div>
                                <div class="ms-auto">
                                    <span class="text-muted"><?= count($post['comments'] ?? []) ?> comments</span>
                                </div>
                            </div>
                            
                            <!-- Divider -->
                            <hr class="my-1">
                            
                            <!-- Action buttons -->
                            <div class="d-flex justify-content-between">
                                <?php 
                                // All arrays are already converted in the mapping function
                                $isUpvoted = in_array($_SESSION['user_id'], $post['upvoted_by'] ?? []);
                                ?>
                                <button class="btn btn-link text-decoration-none flex-fill upvote-btn <?= $isUpvoted ? 'text-primary fw-bold' : 'text-muted' ?>" 
                                        data-post-id="<?= $post['_id'] ?>">
                                    <i class="bi bi-hand-thumbs-up me-1"></i> Like
                                </button>
                                
                                <button class="btn btn-link text-decoration-none flex-fill text-muted toggle-comments-btn" data-post-id="<?= $post['_id'] ?>">
                                    <i class="bi bi-chat-left-text me-1"></i> Comment
                                </button>
                                
                                    <button class="btn btn-link text-decoration-none flex-fill text-muted share-btn" 
                                            data-post-url="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/post_details.php?id=" . $post['_id'] ?>">
                                    <i class="bi bi-share me-1"></i> Share
                                </button>
                            </div>
                            
                            <div class="comment-section" id="comments-<?= $post['_id'] ?>" style="display: none;">
                                <hr class="my-2">
                                <div class="comments-container">
                                    <?php if (!empty($post['comments'])): ?>
                                        <?php foreach ($post['comments'] as $index => $comment): ?>
                                            <div class="comment" id="comment-<?= $post['_id'] ?>-<?= $index ?>">
                                                <div class="d-flex">
                                                    <img src="<?= isset($comment['user_profile_pic']) ? $comment['user_profile_pic'] : 'uploads/profile_images/user_avater.png' ?>" 
                                                         alt="Avatar" class="author-avatar" style="width: 32px; height: 32px;">
                                                    <div class="flex-grow-1">
                                                        <div class="comment-bubble p-2 bg-light rounded">
                                                                <div class="d-flex justify-content-between align-items-start">
                                                            <div class="comment-meta">
                                                                <div class="comment-author fw-bold"><?= htmlspecialchars($comment['user_name'] ?? 'Unknown') ?></div>
                                                                    </div>
                                                                <?php if (isset($_SESSION['user_id']) && isset($comment['user_id']) && $_SESSION['user_id'] === $comment['user_id']): ?>
                                                                    <div class="dropdown">
                                                                            <button class="btn btn-link btn-sm text-muted p-0" type="button" data-bs-toggle="dropdown">
                                                                                <i class="bi bi-three-dots-vertical"></i>
                                                                        </button>
                                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                                                <li>
                                                                                    <button class="dropdown-item edit-comment-btn" 
                                                                                data-post-id="<?= $post['_id'] ?>" 
                                                                                            data-comment-index="<?= $index ?>"
                                                                                            data-comment-text="<?= htmlspecialchars($comment['text'] ?? '') ?>">
                                                                                        <i class="bi bi-pencil me-2"></i>Edit
                                                                                    </button>
                                                                                </li>
                                                                                <li>
                                                                                    <button class="dropdown-item text-danger delete-comment-btn" 
                                                                                data-post-id="<?= $post['_id'] ?>" 
                                                                                            data-comment-index="<?= $index ?>">
                                                                                        <i class="bi bi-trash me-2"></i>Delete
                                                                                    </button>
                                                                                </li>
                                                                        </ul>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                                <div class="comment-content mt-1"><?= nl2br(htmlspecialchars($comment['text'] ?? '')) ?></div>
                                                        </div>
                                                        <div class="comment-actions small mt-1">
                                                            <span class="text-muted comment-time">
                                                                <?php if (isset($comment['time']) && !empty($comment['time'])): ?>
                                                                    <?php
                                                                        $commentTime = null;
                                                                        if (is_object($comment['time']) && method_exists($comment['time'], 'toDateTime')) {
                                                                            $commentTime = $comment['time']->toDateTime();
                                                                        } elseif (is_string($comment['time']) || is_numeric($comment['time'])) {
                                                                            $commentTime = new DateTime('@' . (int)$comment['time']);
                                                                        }
                                                                        
                                                                        echo $commentTime ? date('M j, Y \a\t g:i a', $commentTime->getTimestamp()) : 'Unknown time';
                                                                        
                                                                        if (isset($comment['edited']) && $comment['edited']) {
                                                                            echo ' (edited)';
                                                                        }
                                                                    ?>
                                                                <?php else: ?>
                                                                    Unknown time
                                                                <?php endif; ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted">No comments yet. Be the first to comment!</p>
                                    <?php endif; ?>
                                </div>
                                
                                <form class="add-comment-form mt-3 d-flex" data-post-id="<?= $post['_id'] ?>">
                                    <img src="<?= $_SESSION['profile_pic'] ?? 'uploads/profile_images/user_avater.png' ?>" 
                                         alt="Your Avatar" class="author-avatar" style="width: 32px; height: 32px;">
                                    <div class="flex-grow-1 ms-2">
                                        <div class="position-relative">
                                            <textarea class="form-control" style="padding: 0.75rem 3rem 0.75rem 1rem; resize: none; min-height: 40px; max-height: 120px; border-radius: 20px; line-height: 1.5; background: var(--surface-1); border: 1px solid var(--border-color); color: var(--text-primary);" placeholder="Write a comment..." required></textarea>
                                            <button type="submit" class="btn btn-link" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: var(--modern-blue); padding: 0.25rem 0.5rem;">
                                                <i class="bi bi-send-fill"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- First, I'll add the Create Post Modal to the page, before the Confirmation Modal -->
    <!-- Add this right before the confirmation modal around line 672 -->
    <!-- Create Post Modal -->
    <div class="modal fade" id="createPostModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="post-form" enctype="multipart/form-data">
                        <div class="form-section" data-aos="fade-up" data-aos-delay="100">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="form-section" data-aos="fade-up" data-aos-delay="200">
                            <label for="content" class="form-label">Content</label>
                            <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
                        </div>
                        
                        <div class="form-section" data-aos="fade-up" data-aos-delay="300">
                            <label class="form-label">Tags (select at least one)</label>
                            <div class="tags-container">
                                <?php foreach ($allTags as $tag): ?>
                                    <input type="checkbox" 
                                           class="tag-checkbox" 
                                           id="tag-<?= $tag['name'] ?>" 
                                           name="tags[]" 
                                           value="<?= $tag['name'] ?>">
                                    <label class="tag-label" 
                                           for="tag-<?= $tag['name'] ?>" 
                                           style="color: <?= $tag['color'] ?>; border-color: <?= $tag['color'] ?>;">
                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $tag['name']))) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="form-section" data-aos="fade-up" data-aos-delay="400">
                            <label for="attachments" class="form-label">Attachments (optional)</label>
                            <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
                            <div id="preview-container" class="mt-2"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submit-post-btn">Post</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background: var(--glass-bg); backdrop-filter: blur(10px); border: 1px solid var(--border-color);">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
                    <h5 class="modal-title" style="color: var(--text-primary);">Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: var(--text-primary) === '#ffffff' ? invert(1) : invert(0);"></button>
                </div>
                <div class="modal-body" style="color: var(--text-primary);">
                    <p id="confirmation-message">Are you sure you want to delete this?</p>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirm-delete-btn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'src/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
    // Initialize AOS
    AOS.init({
        duration: 800,
        once: true
    });
    
    // Debug flag - set to true to see console messages
    const DEBUG = true;
    
    function debug(message) {
        if (DEBUG) {
            console.log(message);
        }
    }
    
    // Initialize Bootstrap components
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    let deleteTarget = null;
    let deleteType = '';
    
    // Toggle comments visibility
    document.querySelectorAll('.toggle-comments-btn').forEach(btn => {
        debug('Initializing comment toggle for button: ' + btn.getAttribute('data-post-id'));
        btn.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const commentsSection = document.getElementById(`comments-${postId}`);
            
            if (commentsSection) {
                if (commentsSection.style.display === 'none') {
                    commentsSection.style.display = 'block';
                    this.classList.add('text-primary');
                    this.classList.remove('text-muted');
                } else {
                    commentsSection.style.display = 'none';
                    this.classList.add('text-muted');
                    this.classList.remove('text-primary');
                }
            } else {
                console.error(`Comments section not found for post ${postId}`);
            }
        });
    });
    
    // Handle upvotes
    document.querySelectorAll('.upvote-btn').forEach(btn => {
        debug('Initializing upvote for button: ' + btn.getAttribute('data-post-id'));
        btn.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const button = this;
            
            fetch('src/controller/update_votes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ postId: postId })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update UI
                    const countElements = document.querySelectorAll(`.upvote-count[data-post-id="${postId}"], .upvote-btn[data-post-id="${postId}"] .upvote-count`);
                    countElements.forEach(el => {
                        el.textContent = data.upvotes;
                    });
                    
                    if (data.upvoted) {
                        button.classList.add('text-primary', 'fw-bold');
                        button.classList.remove('text-muted');
                    } else {
                        button.classList.add('text-muted');
                        button.classList.remove('text-primary', 'fw-bold');
                    }
                } else {
                    throw new Error(data.message || 'Failed to update vote');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating vote: ' + error.message);
            });
        });
    });
    
    // Auto-resize textarea for comments
    document.querySelectorAll('.add-comment-form textarea').forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        
        // Focus event to expand textarea
        textarea.addEventListener('focus', function() {
            this.style.height = 'auto';
            this.style.height = Math.max(80, this.scrollHeight) + 'px';
        });
        
        // Blur event to collapse textarea if empty
        textarea.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                this.style.height = '40px';
            }
        });
    });
    
    // Add comment
    document.querySelectorAll('.add-comment-form').forEach(form => {
        debug('Initializing comment form for post: ' + form.getAttribute('data-post-id'));
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const postId = this.getAttribute('data-post-id');
            const textarea = this.querySelector('textarea');
            const submitButton = this.querySelector('button[type="submit"]');
            
            if (!textarea || !submitButton) {
                console.error('Required form elements not found');
                return;
            }
            
            const commentText = textarea.value.trim();
            if (!commentText) {
                alert('Please enter a comment');
                return;
            }
            
            // Disable form while submitting
            textarea.disabled = true;
            submitButton.disabled = true;
            
            fetch('src/controller/add_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    postId: postId,
                    comment: commentText
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json().catch(error => {
                    throw new Error('Invalid JSON response from server');
                });
            })
            .then(data => {
                if (data.success) {
                    // Reload the page to show the new comment
                    location.reload();
                } else {
                    throw new Error(data.message || 'Failed to add comment');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding comment: ' + error.message);
                // Re-enable form
                textarea.disabled = false;
                submitButton.disabled = false;
            });
        });
    });
    
    // Handle delete post button
    document.querySelectorAll('.delete-post-btn').forEach(btn => {
        debug('Initializing delete button for post: ' + btn.getAttribute('data-post-id'));
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            deleteTarget = this.getAttribute('data-post-id');
            deleteType = 'post';
            
            document.getElementById('confirmation-message').textContent = 
                'Are you sure you want to delete this post? This action cannot be undone.';
            confirmationModal.show();
        });
    });
    
    // Handle delete comment button
    document.querySelectorAll('.delete-comment-btn').forEach(btn => {
        debug('Initializing delete button for comment');
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            deleteTarget = {
                postId: this.getAttribute('data-post-id'),
                commentIndex: this.getAttribute('data-comment-index')
            };
            deleteType = 'comment';
            
            document.getElementById('confirmation-message').textContent = 
                'Are you sure you want to delete this comment? This action cannot be undone.';
            confirmationModal.show();
        });
    });
    
    // Handle confirm delete button
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (!deleteTarget || !deleteType) {
                console.error('Delete target or type not set');
                return;
            }
            
            let url = deleteType === 'post' ? 'src/controller/delete_post.php' : 'src/controller/delete_comment.php';
            let data = deleteType === 'post' ? { postId: deleteTarget } : deleteTarget;
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    if (deleteType === 'post') {
                        const postElement = document.getElementById(`post-${deleteTarget}`);
                        if (postElement) {
                            postElement.remove();
                        }
                    } else {
                        const commentElement = document.getElementById(
                            `comment-${deleteTarget.postId}-${deleteTarget.commentIndex}`
                        );
                        if (commentElement) {
                            commentElement.remove();
                        }
                    }
                    confirmationModal.hide();
                } else {
                    throw new Error(data.message || 'Failed to delete');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error: ' + error.message);
            });
        });
    }
    
    // Handle edit comment
    document.querySelectorAll('.edit-comment-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const postId = this.getAttribute('data-post-id');
            const commentIndex = this.getAttribute('data-comment-index');
            const commentText = this.getAttribute('data-comment-text');
            const commentElement = document.getElementById(`comment-${postId}-${commentIndex}`);
                const contentElement = commentElement.querySelector('.comment-content');
            
            if (!contentElement) return;
            
            // Create edit form
            const editForm = document.createElement('div');
            editForm.className = 'edit-form mt-2';
            editForm.innerHTML = `
                <textarea class="form-control mb-2">${commentText}</textarea>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-primary save-edit-btn">Save</button>
                    <button class="btn btn-sm btn-secondary cancel-edit-btn">Cancel</button>
                </div>
            `;
            
            // Store original content
            const originalContent = contentElement.innerHTML;
            
            // Replace content with edit form
            contentElement.innerHTML = '';
            contentElement.appendChild(editForm);
            
            // Focus textarea
            const textarea = editForm.querySelector('textarea');
            textarea.focus();
            
            // Handle save
            editForm.querySelector('.save-edit-btn').addEventListener('click', async () => {
            const newText = textarea.value.trim();
            if (!newText) {
                alert('Please enter a comment');
                return;
            }
            
                try {
                    const response = await fetch('src/controller/edit_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    postId: postId,
                    commentIndex: parseInt(commentIndex),
                    text: newText
                })
                    });
                    
                    const data = await response.json();
                if (data.success) {
                        contentElement.innerHTML = nl2br(newText);
                        
                        // Update the data-comment-text attribute
                        this.setAttribute('data-comment-text', newText);
                        
                        // Add edited indicator if not present
                        const timeElement = commentElement.querySelector('.comment-time');
                        if (timeElement && !timeElement.textContent.includes('(edited)')) {
                            timeElement.textContent += ' (edited)';
                    }
                } else {
                    throw new Error(data.message || 'Failed to update comment');
                }
                } catch (error) {
                console.error('Error:', error);
                alert('Error updating comment: ' + error.message);
                    contentElement.innerHTML = originalContent;
                }
            });
            
            // Handle cancel
            editForm.querySelector('.cancel-edit-btn').addEventListener('click', () => {
                contentElement.innerHTML = originalContent;
            });
        });
    });
    
    // Helper function to convert newlines to <br> tags
    function nl2br(str) {
        return str.replace(/\n/g, '<br>');
    }
    
    // Handle delete comment
    document.querySelectorAll('.delete-comment-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const postId = this.getAttribute('data-post-id');
            const commentIndex = this.getAttribute('data-comment-index');
            
            if (confirm('Are you sure you want to delete this comment? This action cannot be undone.')) {
                fetch('src/controller/delete_comment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        postId: postId,
                        commentIndex: parseInt(commentIndex)
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const commentElement = document.getElementById(`comment-${postId}-${commentIndex}`);
                        if (commentElement) {
                            commentElement.remove();
                        }
                    } else {
                        throw new Error(data.message || 'Failed to delete comment');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting comment: ' + error.message);
                });
            }
        });
    });
    
    debug('All event listeners initialized');
    
    // Make entire posts clickable
    document.querySelectorAll('.forum-post').forEach(post => {
        const postId = post.id.replace('post-', '');
        
        post.addEventListener('click', function(e) {
            // Don't navigate if clicking on interactive elements
            const interactiveElements = [
                'button', 'a', 'input', 'textarea', 'select', 
                '.btn', '.dropdown-toggle', '.dropdown-item',
                '.upvote-btn', '.toggle-comments-btn', '.share-btn',
                '.edit-post-btn', '.delete-post-btn', '.edit-comment-btn', '.delete-comment-btn'
            ];
            
            // Check if the clicked element or its parents are interactive
            let isInteractive = false;
            for (let selector of interactiveElements) {
                if (e.target.matches(selector) || e.target.closest(selector)) {
                    isInteractive = true;
                    break;
                }
            }
            
            // Also check if we're in a form or comment section that's being edited
            if (e.target.closest('.add-comment-form') || 
                e.target.closest('.edit-comment-form') || 
                e.target.closest('.comment-section')) {
                isInteractive = true;
            }
            
            // Navigate to post details if not clicking on interactive elements
            if (!isInteractive) {
                window.location.href = `post_details.php?id=${postId}`;
            }
        });
        
        // Add visual feedback for clickability
        post.addEventListener('mouseenter', function(e) {
            // Don't show pointer cursor on interactive elements
            if (!e.target.matches('button, a, input, textarea, select, .btn')) {
                this.style.cursor = 'pointer';
            }
        });
    });
    
    // Theme handling - Listen for theme changes from navbar
    document.addEventListener('themeChanged', function(e) {
        debug('Theme changed to: ' + e.detail.theme);
        
        // Update modal close button filter for theme
        const closeButton = document.querySelector('.modal .btn-close');
        if (closeButton) {
            if (e.detail.theme === 'light') {
                closeButton.style.filter = 'invert(0)';
            } else {
                closeButton.style.filter = 'invert(1)';
            }
        }
        
        // Force repaint for smooth transitions
        document.body.style.transform = 'translateZ(0)';
        setTimeout(() => {
            document.body.style.transform = '';
        }, 50);
    });
    
    // Initialize theme on page load
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
    const closeButton = document.querySelector('.modal .btn-close');
    if (closeButton) {
        if (currentTheme === 'light') {
            closeButton.style.filter = 'invert(0)';
        } else {
            closeButton.style.filter = 'invert(1)';
        }
    }
    
    // Handle share button clicks
    document.querySelectorAll('.share-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const postUrl = this.getAttribute('data-post-url');
            try {
                await navigator.clipboard.writeText(postUrl);
                // Show a temporary tooltip
                const tooltip = document.createElement('div');
                tooltip.textContent = 'Link copied!';
                tooltip.style.cssText = `
                    position: fixed;
                    background: rgba(0,0,0,0.8);
                    color: white;
                    padding: 8px 16px;
                    border-radius: 4px;
                    z-index: 1000;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                `;
                document.body.appendChild(tooltip);
                setTimeout(() => tooltip.remove(), 2000);
            } catch (err) {
                console.error('Failed to copy:', err);
                alert('Failed to copy link. Please try again.');
            }
        });
    });
    </script>
    
    <!-- Document Preview Modal -->
    <div class="modal fade document-preview-modal" id="documentPreviewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="documentPreviewTitle">Document Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- PDF Viewer Container -->
                    <div class="document-preview-container pdf-container">
                        <canvas id="pdf-viewer"></canvas>
                        <div class="loading-spinner d-none">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Text Viewer Container -->
                    <div class="document-preview-container text-container d-none">
                        <pre id="text-viewer" class="text-viewer"></pre>
                    </div>
                    
                    <!-- Document Info Container (for DOC, PPT, etc.) -->
                    <div class="document-preview-container doc-container d-none">
                        <div class="document-info text-center p-4">
                            <i class="bi bi-file-earmark-fill document-icon"></i>
                            <h4 class="mt-3" id="doc-name">Document Name</h4>
                            <p class="text-muted" id="doc-info">File information will appear here</p>
                            <div class="mt-4">
                                <a href="#" class="btn btn-primary" id="downloadDoc" download>
                                    <i class="bi bi-download"></i> Download Document
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- PDF Navigation Controls -->
                    <div class="document-controls pdf-controls d-none">
                        <button class="btn btn-primary" id="prevPage">
                            <i class="bi bi-chevron-left"></i> Previous
                        </button>
                        <div class="pdf-page-info">
                            Page <span id="currentPage">0</span> of <span id="totalPages">0</span>
                        </div>
                        <button class="btn btn-primary" id="nextPage">
                            Next <i class="bi bi-chevron-right"></i>
                        </button>
                        <a href="#" class="btn btn-secondary" id="downloadPdf" download>
                            <i class="bi bi-download"></i> Download
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Document Preview Functionality
    let pdfDoc = null;
    let pageNum = 1;
    let pageRendering = false;
    let pageNumPending = null;
    let scale = 1.5;
    const canvas = document.getElementById('pdf-viewer');
    const ctx = canvas ? canvas.getContext('2d') : null;
    const documentModal = new bootstrap.Modal(document.getElementById('documentPreviewModal'));
    
    // Show error message in document container
    function showDocumentError(message, containerSelector = '.pdf-container') {
        const container = document.querySelector(containerSelector);
        if (!container) return;
        
        const error = document.createElement('div');
        error.className = 'document-error';
        error.innerHTML = `
            <i class="bi bi-exclamation-circle text-danger fs-1"></i>
            <h4 class="mt-3">Error</h4>
            <p>${message}</p>
        `;
        container.appendChild(error);
    }

    function showLoading() {
        const spinner = document.querySelector('.loading-spinner');
        if (spinner) spinner.classList.remove('d-none');
    }

    function hideLoading() {
        const spinner = document.querySelector('.loading-spinner');
        if (spinner) spinner.classList.add('d-none');
    }

    // Reset all document containers
    function resetDocumentContainers() {
        // Hide all containers
        document.querySelectorAll('.document-preview-container').forEach(container => {
            container.classList.add('d-none');
        });
        
        // Hide PDF controls
        document.querySelector('.pdf-controls').classList.add('d-none');
        
        // Remove any existing error messages
        document.querySelectorAll('.document-error, .pdf-error').forEach(error => {
            error.remove();
        });
    }
    
    // Show the appropriate container based on file type
    function showDocumentContainer(fileType) {
        resetDocumentContainers();
        
        switch (fileType) {
            case 'pdf':
                document.querySelector('.pdf-container').classList.remove('d-none');
                document.querySelector('.pdf-controls').classList.remove('d-none');
                break;
            case 'txt':
                document.querySelector('.text-container').classList.remove('d-none');
                break;
            case 'doc':
            case 'docx':
            case 'ppt':
            case 'pptx':
                document.querySelector('.doc-container').classList.remove('d-none');
                
                // Set the appropriate icon based on file type
                const docIcon = document.querySelector('.document-icon');
                if (docIcon) {
                    docIcon.className = 'bi document-icon';
                    if (fileType === 'doc' || fileType === 'docx') {
                        docIcon.classList.add('bi-file-earmark-word', 'doc-icon');
                    } else if (fileType === 'ppt' || fileType === 'pptx') {
                        docIcon.classList.add('bi-file-earmark-ppt', 'ppt-icon');
                    } else if (fileType === 'txt') {
                        docIcon.classList.add('bi-file-earmark-text', 'txt-icon');
                    } else {
                        docIcon.classList.add('bi-file-earmark', 'file-icon');
                    }
                }
                break;
        }
    }
    
    // PDF Handling Functions
    async function renderPdfPage(num) {
        pageRendering = true;
        try {
            const page = await pdfDoc.getPage(num);
            const viewport = page.getViewport({ scale });
            if (canvas) {
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                
                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                
                await page.render(renderContext).promise;
                pageRendering = false;
                
                if (pageNumPending !== null) {
                    renderPdfPage(pageNumPending);
                    pageNumPending = null;
                }
                
                // Update page counters
                const currentPageEl = document.getElementById('currentPage');
                if (currentPageEl) {
                    currentPageEl.textContent = num;
                }
            }
        } catch (error) {
            console.error('Error rendering PDF page:', error);
            showDocumentError('Failed to render PDF page. Please try downloading the file instead.');
            hideLoading();
        }
    }

    function queueRenderPage(num) {
        if (pageRendering) {
            pageNumPending = num;
        } else {
            renderPdfPage(num);
        }
    }

    async function loadPdf(url) {
        try {
            showLoading();
            const loadingTask = pdfjsLib.getDocument(url);
            pdfDoc = await loadingTask.promise;
            const totalPagesEl = document.getElementById('totalPages');
            if (totalPagesEl) {
                totalPagesEl.textContent = pdfDoc.numPages;
            }
            renderPdfPage(1);
            hideLoading();
        } catch (error) {
            console.error('Error loading PDF:', error);
            showDocumentError('Failed to load PDF. The file might be corrupted or inaccessible.');
            hideLoading();
        }
    }
    
    // Text File Handling
    async function loadTextFile(url) {
        try {
            showLoading();
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const text = await response.text();
            const textViewer = document.getElementById('text-viewer');
            if (textViewer) {
                textViewer.textContent = text;
            }
            hideLoading();
        } catch (error) {
            console.error('Error loading text file:', error);
            showDocumentError('Failed to load text file. Please try downloading it instead.', '.text-container');
            hideLoading();
        }
    }
    
    // Load document based on file type
    function loadDocument(url, fileName, fileExt) {
        // Update modal title
        const titleEl = document.getElementById('documentPreviewTitle');
        if (titleEl) {
            titleEl.textContent = fileName;
        }
        
        // Reset state and show appropriate container
        showDocumentContainer(fileExt);
        
        // Handle different file types
        switch (fileExt) {
            case 'pdf':
                // Reset PDF viewer state
                pageNum = 1;
                pdfDoc = null;
                if (ctx) {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                }
                
                const currentPageEl = document.getElementById('currentPage');
                const totalPagesEl = document.getElementById('totalPages');
                if (currentPageEl) currentPageEl.textContent = '0';
                if (totalPagesEl) totalPagesEl.textContent = '0';
                
                // Update download link
                const downloadPdfBtn = document.getElementById('downloadPdf');
                if (downloadPdfBtn) {
                    downloadPdfBtn.href = url;
                    downloadPdfBtn.setAttribute('download', fileName);
                }
                
                loadPdf(url);
                break;
                
            case 'txt':
                loadTextFile(url);
                break;
                
            case 'doc':
            case 'docx':
            case 'ppt':
            case 'pptx':
                // Update document info
                const docNameEl = document.getElementById('doc-name');
                const docInfoEl = document.getElementById('doc-info');
                const downloadDocBtn = document.getElementById('downloadDoc');
                
                if (docNameEl) docNameEl.textContent = fileName;
                
                if (docInfoEl) {
                    let fileTypeText = 'Document';
                    if (fileExt === 'doc' || fileExt === 'docx') {
                        fileTypeText = 'Word Document';
                    } else if (fileExt === 'ppt' || fileExt === 'pptx') {
                        fileTypeText = 'PowerPoint Presentation';
                    }
                    docInfoEl.textContent = `${fileTypeText} - This file cannot be previewed. Please download to view.`;
                }
                
                if (downloadDocBtn) {
                    downloadDocBtn.href = url;
                    downloadDocBtn.setAttribute('download', fileName);
                }
                break;
        }
    }

    // Event listeners for PDF controls
    const prevPageBtn = document.getElementById('prevPage');
    if (prevPageBtn) {
        prevPageBtn.addEventListener('click', () => {
            if (pageNum <= 1) return;
            pageNum--;
            queueRenderPage(pageNum);
        });
    }
    
    const nextPageBtn = document.getElementById('nextPage');
    if (nextPageBtn) {
        nextPageBtn.addEventListener('click', () => {
            if (pageNum >= pdfDoc?.numPages) return;
            pageNum++;
            queueRenderPage(pageNum);
        });
    }

    // Handle attachment clicks for document files
    document.addEventListener('DOMContentLoaded', function() {
        // Find all attachment links across all posts
        document.querySelectorAll('.attachment-file, .attachment-item a').forEach(link => {
            link.addEventListener('click', function(e) {
                const filePath = this.getAttribute('href');
                if (!filePath) return;
                
                // Extract filename and extension
                let fileName = '';
                const fileNameElement = this.querySelector('span');
                if (fileNameElement) {
                    fileName = fileNameElement.textContent.trim();
                } else {
                    fileName = filePath.split('/').pop();
                }
                
                const fileExt = fileName.split('.').pop().toLowerCase();
                const supportedFormats = ['pdf', 'txt', 'doc', 'docx', 'ppt', 'pptx'];
                
                if (supportedFormats.includes(fileExt)) {
                    e.preventDefault();
                    
                    // Show modal and load document
                    documentModal.show();
                    loadDocument(filePath, fileName, fileExt);
                }
            });
        });
    });
        
    // Handle modal close
    const documentModalEl = document.getElementById('documentPreviewModal');
    if (documentModalEl) {
        documentModalEl.addEventListener('hidden.bs.modal', function () {
            // Clean up document viewer state
            pdfDoc = null;
            pageNum = 1;
            if (ctx) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }
            
            // Clear text viewer
            const textViewer = document.getElementById('text-viewer');
            if (textViewer) {
                textViewer.textContent = '';
            }
            
            // Remove any existing error messages
            document.querySelectorAll('.document-error, .pdf-error').forEach(error => {
                error.remove();
            });
        });
    }
    </script>

    <!-- Finally, I'll add the JavaScript to handle form submission in the modal -->
    <!-- Add this at the end of the document, before the closing </body> tag -->
    <script>
        // Add this to the end of the existing script section
        document.addEventListener('DOMContentLoaded', function() {
            // Create Post Modal functionality
            const createPostModal = new bootstrap.Modal(document.getElementById('createPostModal'));
            const form = document.getElementById('post-form');
            const fileInput = document.getElementById('attachments');
            const previewContainer = document.getElementById('preview-container');
            const submitButton = document.getElementById('submit-post-btn');
            const maxFileSize = 5 * 1024 * 1024; // 5MB
            let files = [];

            // Handle file input change
            fileInput?.addEventListener('change', function(e) {
                const selectedFiles = Array.from(e.target.files);
                
                // Clear preview if user selects new files
                if (selectedFiles.length > 0) {
                    previewContainer.innerHTML = '';
                    files = [];
                }
                
                selectedFiles.forEach(file => {
                    // Check file size
                    if (file.size > maxFileSize) {
                        alert(`File ${file.name} is too large. Maximum size is 5MB.`);
                        return;
                    }
                    
                    files.push(file);
                    
                    const previewItem = document.createElement('div');
                    previewItem.className = 'preview-item';
                    
                    if (file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = URL.createObjectURL(file);
                        previewItem.appendChild(img);
                    } else {
                        const icon = document.createElement('div');
                        icon.className = 'file-icon';
                        icon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-file-earmark" viewBox="0 0 16 16"><path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h-2z"/></svg>';
                        previewItem.appendChild(icon);
                    }
                    
                    const fileName = document.createElement('div');
                    fileName.className = 'file-name';
                    fileName.textContent = file.name;
                    previewItem.appendChild(fileName);
                    
                    const removeBtn = document.createElement('div');
                    removeBtn.className = 'remove-file';
                    removeBtn.textContent = '×';
                    removeBtn.addEventListener('click', function() {
                        files = files.filter(f => f !== file);
                        previewItem.remove();
                    });
                    previewItem.appendChild(removeBtn);
                    
                    previewContainer.appendChild(previewItem);
                });
            });

            // Handle form submission
            submitButton?.addEventListener('click', function() {
                // Check if form is valid
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                
                // Check if at least one tag is selected
                const selectedTags = document.querySelectorAll('#createPostModal input[name="tags[]"]:checked');
                if (selectedTags.length === 0) {
                    alert('Please select at least one tag for your post');
                    return;
                }
                
                // Create FormData object
                const formData = new FormData();
                formData.append('title', document.getElementById('title').value);
                formData.append('content', document.getElementById('content').value);
                
                // Add selected tags
                selectedTags.forEach(tag => {
                    formData.append('tags[]', tag.value);
                });
                
                // Add files
                files.forEach(file => {
                    formData.append('files[]', file);
                });
                
                // Submit form data
                fetch('src/controller/submit_post.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal and refresh page to show the new post
                        createPostModal.hide();
                        window.location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Unknown error occurred'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while submitting your post');
                });
            });
            
            // Reset form when modal is closed
            document.getElementById('createPostModal')?.addEventListener('hidden.bs.modal', function () {
                form.reset();
                previewContainer.innerHTML = '';
                files = [];
            });
        });
    </script>
</body>
</html>