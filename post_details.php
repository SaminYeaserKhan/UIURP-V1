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
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Driver\Exception\ConnectionException;
use MongoDB\Driver\Exception\ConnectionTimeoutException;

session_start();

// Redirect if not logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

// Check if post ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: view_posts.php');
    exit();
}

try {
    $client = connectToDatabase();
    $db = $client->uiurp;
    $forumPostsCollection = $db->forum_posts;
    
    // Try to convert the ID to ObjectId
    $postId = null;
    try {
        $postId = new \MongoDB\BSON\ObjectId($_GET['id']);
    } catch (Exception $e) {
        // If the ID is not a valid ObjectId, keep it as is
        $postId = $_GET['id'];
    }
    
    // Find post
    $post = $forumPostsCollection->findOne(['_id' => $postId]);
    
    // If post doesn't exist in forum_posts, try the old forum collection
    if (!$post) {
        $forumCollection = $db->forum;
        $post = $forumCollection->findOne(['_id' => $postId]);
        
        if (!$post) {
            $_SESSION['error'] = 'Post not found';
            header('Location: view_posts.php');
            exit();
        }
    }
    
    // Convert BSON arrays to PHP arrays
    if (isset($post['tags']) && $post['tags'] instanceof MongoDB\Model\BSONArray) {
        $post['tags'] = $post['tags']->getArrayCopy();
    }
    
    if (isset($post['upvoted_by']) && $post['upvoted_by'] instanceof MongoDB\Model\BSONArray) {
        $post['upvoted_by'] = $post['upvoted_by']->getArrayCopy();
    }
    
    if (isset($post['comments']) && $post['comments'] instanceof MongoDB\Model\BSONArray) {
        $post['comments'] = $post['comments']->getArrayCopy();
    }
    
    if (isset($post['attachments']) && $post['attachments'] instanceof MongoDB\Model\BSONArray) {
        $post['attachments'] = $post['attachments']->getArrayCopy();
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Error loading post: ' . $e->getMessage();
    header('Location: view_posts.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title']) ?> - Research Forum</title>
    
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

        /* Post Details Specific Styles */
        .container {
            max-width: 900px;
            position: relative;
            z-index: 1;
        }

        .post-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease, border-color 0.3s ease;
        }

        .post-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: transparent;
            transition: border-color 0.3s ease, background 0.3s ease;
        }

        .post-content {
            padding: 2rem 1.5rem;
            background: transparent;
            transition: background 0.3s ease;
        }

        .post-footer {
            padding: 1rem 1.5rem;
            background: var(--surface-1);
            border-top: 1px solid var(--border-color);
            transition: background 0.3s ease, border-color 0.3s ease;
        }

        /* Light theme specific adjustments */
        [data-theme="light"] .post-card {
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        }

        .author-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            margin-right: 1rem;
            object-fit: cover;
            border: 2px solid var(--border-color);
            transition: border-color 0.3s ease;
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
        
        .comment-section {
            margin-top: 2rem;
            background: var(--surface-1);
            border-radius: 1rem;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            transition: background 0.3s ease, border-color 0.3s ease;
        }

        .comment {
            background: var(--surface-2);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .comment:hover {
            transform: translateY(-2px);
        }

        .comment-bubble {
            background: var(--surface-1);
            border-radius: 1rem;
            padding: 1rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .share-link {
            background: var(--surface-1);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .share-link:hover {
            background: var(--surface-2);
            border-color: var(--modern-blue);
            color: var(--text-primary);
        }

        .attachments {
            margin-top: 1.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .attachment-item {
            background: var(--surface-1);
            border-radius: 0.5rem;
            overflow: hidden;
            transition: transform 0.3s ease, background 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .attachment-item:hover {
            transform: translateY(-2px);
            background: var(--surface-2);
            border-color: var(--modern-blue);
        }

        .attachment-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 0.5rem 0.5rem 0 0;
            border-bottom: 1px solid var(--border-color);
            transition: border-color 0.3s ease;
        }

        .attachment-file {
            padding: 1rem;
            display: flex;
            align-items: center;
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.3s ease;
            gap: 0.5rem;
        }

        .attachment-file:hover {
            color: var(--text-link);
            background: var(--surface-2);
        }

        .attachment-file i {
            font-size: 1.2rem;
            color: var(--modern-blue);
        }

        .attachment-file span {
            max-width: calc(100% - 2rem);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Video attachment styles */
        .video-container {
            width: 100%;
            position: relative;
            overflow: hidden;
            border-radius: 0.5rem;
        }
        
        .video-player {
            width: 100%;
            display: block;
            border-radius: 0.5rem;
            max-height: 250px;
            background: rgba(0,0,0,0.2);
        }
        
        .video-info {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.6);
            border-radius: 4px;
            padding: 4px 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .video-format {
            color: white;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .download-btn {
            color: white;
            font-size: 0.9rem;
        }
        
        .download-btn:hover {
            color: var(--modern-blue);
        }
        
        /* File format indicator */
        .file-format {
            background: var(--modern-blue);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.7rem;
            position: absolute;
            top: 5px;
            right: 5px;
            font-weight: bold;
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

        /* Button styling */
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

        .btn-link {
            color: var(--text-secondary);
            transition: all 0.3s ease;
        }

        .btn-link:hover {
            color: var(--text-primary);
        }

        /* Text and content styling */
        .content-text {
            color: var(--text-primary);
            line-height: 1.6;
            transition: color 0.3s ease;
        }

        /* Bootstrap overrides for consistent theming */
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

        /* Dropdown styling */
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

        /* Alert styling */
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        [data-theme="light"] .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-color: rgba(220, 53, 69, 0.2);
            color: #721c24;
        }

        /* Vote button styling */
        .vote-btn {
            color: var(--text-muted) !important;
            transition: color 0.3s ease;
        }

        .vote-btn:hover {
            color: var(--text-link) !important;
        }

        .vote-btn.bi-hand-thumbs-up-fill {
            color: var(--modern-blue) !important;
        }

        /* Comment author and meta styling */
        .comment .fw-bold {
            color: var(--text-primary) !important;
        }

        .comment .small {
            color: var(--text-muted) !important;
        }

        /* Ensure proper theme inheritance */
        * {
            color: inherit;
        }

        h1, h2, h3, h4, h5, h6 {
            color: var(--text-primary);
            transition: color 0.3s ease;
        }

        p {
            color: var(--text-primary);
            transition: color 0.3s ease;
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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

    <div class="container mt-5">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger fade-in">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="post-card fade-in" data-aos="fade-up">
            <div class="post-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="d-flex">
                        <img src="<?= $post['user_profile_pic'] ?? 'uploads/profile_images/user_avater.png' ?>" 
                             alt="Avatar" class="author-avatar">
                        <div>
                            <h5 class="mb-1"><?= htmlspecialchars($post['user_name'] ?? 'Unknown User') ?></h5>
                            <p class="text-muted mb-0">
                                    <?php
                                        $dateTime = null;
                                        if (is_object($post['created_at']) && method_exists($post['created_at'], 'toDateTime')) {
                                            $dateTime = $post['created_at']->toDateTime();
                                        $dateTime->setTimezone(new DateTimeZone('Asia/Dhaka'));
                                        } elseif (is_string($post['created_at']) || is_numeric($post['created_at'])) {
                                            $dateTime = new DateTime('@' . (int)$post['created_at']);
                                        $dateTime->setTimezone(new DateTimeZone('Asia/Dhaka'));
                                    }
                                    echo $dateTime ? $dateTime->format('F j, Y \a\t g:i a') : 'Unknown date';
                                    
                                    if (isset($post['updated_at']) && $post['updated_at'] != $post['created_at']) {
                                                echo ' (edited)';
                                        }
                                    ?>
                            </p>
                            </div>
                        </div>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id']): ?>
                        <div class="dropdown">
                            <button class="btn btn-link text-muted" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="edit_post.php?id=<?= $post['_id'] ?>">
                                        <i class="bi bi-pencil me-2"></i> Edit
                                    </a>
                                </li>
                                <li>
                                    <form action="src/controller/delete_post.php" method="POST" class="d-inline">
                                        <input type="hidden" name="post_id" value="<?= $post['_id'] ?>">
                                        <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete this post?')">
                                            <i class="bi bi-trash me-2"></i> Delete
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="post-content">
                <h2 class="mb-4"><?= htmlspecialchars($post['title']) ?></h2>
                <div class="mb-3">
                    <?php foreach ($post['tags'] as $tag): ?>
                        <span class="post-tag"><?= htmlspecialchars($tag) ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="content-text">
                    <?= nl2br(htmlspecialchars($post['content'])) ?>
                </div>
                
                <?php if (!empty($post['attachments'])): ?>
                    <div class="attachments">
                        <?php foreach ($post['attachments'] as $attachment): ?>
                            <?php 
                                $fileExtension = strtolower(pathinfo($attachment['original_name'], PATHINFO_EXTENSION));
                                $fileType = $attachment['file_type'] ?? '';
                                $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif']) || strpos($fileType, 'image/') === 0;
                                $isVideo = in_array($fileExtension, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'wmv', 'flv', 'mkv']) || strpos($fileType, 'video/') === 0;
                            ?>
                            <div class="attachment-item">
                                    <?php if ($isImage): ?>
                                    <a href="<?= htmlspecialchars($attachment['file_path']) ?>" target="_blank">
                                        <img src="<?= htmlspecialchars($attachment['file_path']) ?>" alt="Attachment" class="attachment-img">
                                    </a>
                                    <?php elseif ($isVideo): ?>
                                    <div class="video-container">
                                        <video controls class="video-player">
                                            <source src="<?= htmlspecialchars($attachment['file_path']) ?>" type="<?= htmlspecialchars($fileType) ?>">
                                            Your browser does not support the video tag.
                                        </video>
                                        <div class="video-info">
                                            <span class="video-format"><?= strtoupper($fileExtension) ?></span>
                                            <a href="<?= htmlspecialchars($attachment['file_path']) ?>" download class="download-btn">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <a href="<?= htmlspecialchars($attachment['file_path']) ?>" class="attachment-file" download>
                                            <i class="bi bi-file-earmark me-2"></i>
                                        <span class="text-truncate"><?= htmlspecialchars($attachment['original_name']) ?></span>
                                </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="post-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-3">
                        <button class="btn btn-link text-decoration-none text-muted vote-btn" data-post-id="<?= $post['_id'] ?>" data-vote-type="upvote">
                            <i class="bi bi-hand-thumbs-up<?= in_array($_SESSION['user_id'], $post['upvoted_by'] ?? []) ? '-fill' : '' ?>"></i>
                            <span class="upvote-count"><?= count($post['upvoted_by'] ?? []) ?></span>
                    </button>
                        <button class="btn btn-link text-decoration-none text-muted toggle-comments-btn" data-post-id="<?= $post['_id'] ?>">
                            <i class="bi bi-chat-left-text"></i>
                            <span class="comment-count"><?= count($post['comments'] ?? []) ?></span>
                    </button>
                        <button class="btn btn-link text-decoration-none text-muted share-btn" 
                                data-post-url="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>">
                            <i class="bi bi-share"></i> Share
                    </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comments Section -->
        <div class="comment-section fade-in" data-aos="fade-up" data-aos-delay="100">
            <h3 class="mb-4">Comments</h3>
            
            <!-- Add Comment Form -->
            <form action="src/controller/add_comment.php" method="POST" class="mb-4">
                <input type="hidden" name="post_id" value="<?= $post['_id'] ?>">
                <div class="d-flex gap-2">
                    <img src="<?= $_SESSION['profile_pic'] ?? 'uploads/profile_images/user_avater.png' ?>" 
                         alt="Your Avatar" class="author-avatar" style="width: 32px; height: 32px;">
                                <div class="flex-grow-1">
                                    <textarea name="text" class="form-control mb-2" rows="3" placeholder="Write a comment..." required></textarea>
            <button type="submit" class="btn btn-primary">Post Comment</button>
                                                </div>
                                        </div>
            </form>

            <!-- Comments List -->
            <div class="comments-list">
                <?php if (!empty($post['comments'])): ?>
                    <?php foreach (array_reverse($post['comments']) as $comment): ?>
                        <div class="comment fade-in" data-aos="fade-up" data-aos-delay="150">
                            <div class="d-flex mb-2">
                                <img src="<?= $comment['user_profile_pic'] ?? 'uploads/profile_images/user_avater.png' ?>" 
                                     alt="Avatar" class="author-avatar" style="width: 32px; height: 32px;">
                                <div class="ms-2">
                                    <h6 class="mb-0"><?= htmlspecialchars($comment['user_name'] ?? 'Unknown User') ?></h6>
                                    <small class="text-muted">
                                                <?php
                                        $commentDateTime = null;
                                        if (isset($comment['time'])) {
                                                    if (is_object($comment['time']) && method_exists($comment['time'], 'toDateTime')) {
                                                $commentDateTime = $comment['time']->toDateTime();
                                                $commentDateTime->setTimezone(new DateTimeZone('Asia/Dhaka'));
                                                    } elseif (is_string($comment['time']) || is_numeric($comment['time'])) {
                                                $commentDateTime = new DateTime('@' . (int)$comment['time']);
                                                $commentDateTime->setTimezone(new DateTimeZone('Asia/Dhaka'));
                                                    }
                                        }
                                        echo $commentDateTime ? $commentDateTime->format('F j, Y \a\t g:i a') : 'Unknown date';
                                                    
                                                    if (isset($comment['edited']) && $comment['edited']) {
                                                        echo ' (edited)';
                                                    }
                                                ?>
                                    </small>
                                    </div>
                                <?php if (isset($_SESSION['user_id']) && isset($comment['user_id']) && $_SESSION['user_id'] == $comment['user_id']): ?>
                                    <div class="dropdown ms-auto">
                                        <button class="btn btn-link text-muted p-0" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button class="dropdown-item edit-comment-btn" 
                                                        data-comment-id="<?= $comment['_id'] ?? '' ?>"
                                                        data-comment-content="<?= htmlspecialchars($comment['text'] ?? '') ?>">
                                                    <i class="bi bi-pencil me-2"></i> Edit
                                                </button>
                                            </li>
                                            <li>
                                                <form action="src/controller/delete_comment.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="post_id" value="<?= $post['_id'] ?>">
                                                    <input type="hidden" name="comment_id" value="<?= $comment['_id'] ?? '' ?>">
                                                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete this comment?')">
                                                        <i class="bi bi-trash me-2"></i> Delete
                                                    </button>
                                    </form>
                                            </li>
                                        </ul>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="comment-bubble">
                                <?= nl2br(htmlspecialchars($comment['text'] ?? '')) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No comments yet. Be the first to comment!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true
        });

        // Handle share button clicks
        document.querySelectorAll('.share-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const postUrl = this.getAttribute('data-post-url');
                try {
                    await navigator.clipboard.writeText(postUrl);
                    
                    // Show success tooltip
                    const tooltip = document.createElement('div');
                    tooltip.textContent = 'Link copied!';
                    tooltip.style.cssText = `
                        position: fixed;
                        background: var(--glass-bg);
                        color: var(--text-primary);
                        padding: 8px 16px;
                        border-radius: 4px;
                        z-index: 1000;
                        left: 50%;
                        transform: translateX(-50%);
                        bottom: 20px;
                        backdrop-filter: blur(10px);
                        border: 1px solid rgba(255, 255, 255, 0.1);
                        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                        animation: fadeInUp 0.3s ease-out;
                    `;
                    document.body.appendChild(tooltip);
                    
                    setTimeout(() => {
                        tooltip.style.animation = 'fadeOutDown 0.3s ease-out';
                        setTimeout(() => tooltip.remove(), 300);
                    }, 2000);
                } catch (err) {
                    console.error('Failed to copy:', err);
                }
            });
        });
        
        // Handle comment editing
        document.querySelectorAll('.edit-comment-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const commentId = this.getAttribute('data-comment-id');
                const content = this.getAttribute('data-comment-content');
                const commentBubble = this.closest('.comment').querySelector('.comment-bubble');
                
                const textarea = document.createElement('textarea');
                textarea.className = 'form-control mb-2';
                textarea.value = content;
                
                const saveBtn = document.createElement('button');
                saveBtn.className = 'btn btn-primary btn-sm me-2';
                saveBtn.textContent = 'Save';
                
                const cancelBtn = document.createElement('button');
                cancelBtn.className = 'btn btn-secondary btn-sm';
                cancelBtn.textContent = 'Cancel';
                
                const buttonsDiv = document.createElement('div');
                buttonsDiv.appendChild(saveBtn);
                buttonsDiv.appendChild(cancelBtn);
                
                const originalContent = commentBubble.innerHTML;
                commentBubble.innerHTML = '';
                commentBubble.appendChild(textarea);
                commentBubble.appendChild(buttonsDiv);
                
                saveBtn.addEventListener('click', async () => {
                    const formData = new FormData();
                    formData.append('comment_id', commentId);
                    formData.append('post_id', '<?= $post['_id'] ?>');
                    formData.append('content', textarea.value);
                    
                    try {
                        const response = await fetch('src/controller/edit_comment.php', {
                method: 'POST',
                            body: formData
                        });
                        
                        if (response.ok) {
                            commentBubble.innerHTML = textarea.value.replace(/\n/g, '<br>');
                            this.setAttribute('data-comment-content', textarea.value);
                    } else {
                            throw new Error('Failed to update comment');
                        }
                    } catch (error) {
                console.error('Error:', error);
                        commentBubble.innerHTML = originalContent;
                    }
                });
                
                cancelBtn.addEventListener('click', () => {
                    commentBubble.innerHTML = originalContent;
                });
            });
        });
        
        // Handle voting
        document.querySelectorAll('.vote-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const postId = this.getAttribute('data-post-id');
                const voteType = this.getAttribute('data-vote-type');
                const icon = this.querySelector('i');
                const countSpan = this.querySelector('.upvote-count');
                
                try {
                    const response = await fetch('src/controller/update_votes.php', {
                    method: 'POST',
                    headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `post_id=${postId}&vote_type=${voteType}`
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        icon.className = `bi bi-hand-thumbs-up${data.hasVoted ? '-fill' : ''}`;
                        countSpan.textContent = data.voteCount;
                    }
                } catch (error) {
                console.error('Error:', error);
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
    document.querySelectorAll('.attachment-file').forEach(link => {
        link.addEventListener('click', async function(e) {
            const filePath = this.getAttribute('href');
            if (!filePath) return;
            
            const fileName = this.querySelector('span')?.textContent || filePath.split('/').pop();
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
</body>
</html> 