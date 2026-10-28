<?php
require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/model/db_connect.php';
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

// Redirect to login if not logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

// Get available tags
$client = connectToDatabase();
$db = $client->uiurp;
$tagCollection = $db->forum_tags;

// If tags collection doesn't exist, create it with default tags
$tagCount = $tagCollection->countDocuments();
if ($tagCount === 0) {
    $defaultTags = [
        ['name' => 'member_recruitment', 'color' => '#28a745'],
        ['name' => 'bug_fixes', 'color' => '#dc3545'],
        ['name' => 'discussion', 'color' => '#007bff'],
        ['name' => 'tutorial', 'color' => '#17a2b8'],
        ['name' => 'announcement', 'color' => '#ffc107'],
        ['name' => 'question', 'color' => '#6f42c1'],
        ['name' => 'resource', 'color' => '#fd7e14']
    ];
    $tagCollection->insertMany($defaultTags);
}

$tags = $tagCollection->find()->toArray();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Forum Post</title>
    
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

        ::-webkit-scrollbar {
            display: none; /* Hide scrollbar for Chrome, Safari and Opera */
        }

        * {
            -ms-overflow-style: none;  /* Hide scrollbar for IE and Edge */
            scrollbar-width: none;  /* Hide scrollbar for Firefox */
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

                 /* Main Container */
         .container {
             position: relative;
             z-index: 1;
             margin-top: 90px;
             padding-bottom: 20px;
             height: calc(100vh - 90px);
             overflow-y: auto;
         }

        /* Card styling */
        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease, border-color 0.3s ease;
        }

        .card-header {
            background: linear-gradient(135deg, var(--modern-blue), var(--modern-purple)) !important;
            border-bottom: 1px solid var(--border-color);
            color: white !important;
            padding: 1.5rem;
            transition: background 0.3s ease, border-color 0.3s ease;
        }

                 .card-body {
             background: transparent;
             padding: 1.5rem;
             transition: background 0.3s ease;
         }

        /* Light theme card adjustments */
        [data-theme="light"] .card {
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        }

        /* Tag styling */
        .tag-checkbox {
            display: none;
        }

        .tag-label {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin: 0.3rem;
            border-radius: 2rem;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid;
            background: transparent;
            position: relative;
            overflow: hidden;
        }

        .tag-label::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .tag-label:hover::before {
            left: 100%;
        }

        .tag-label:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

                 .tag-checkbox:checked + .tag-label {
             background: currentColor !important;
             color: white !important;
             transform: translateY(-2px);
             box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
             border-color: currentColor !important;
         }

         /* Ensure specific tag colors are preserved when selected */
         .tag-checkbox:checked + .tag-label[style*="#28a745"] {
             background: #28a745 !important;
         }
         
         .tag-checkbox:checked + .tag-label[style*="#dc3545"] {
             background: #dc3545 !important;
         }
         
         .tag-checkbox:checked + .tag-label[style*="#007bff"] {
             background: #007bff !important;
         }
         
         .tag-checkbox:checked + .tag-label[style*="#17a2b8"] {
             background: #17a2b8 !important;
         }
         
         .tag-checkbox:checked + .tag-label[style*="#ffc107"] {
             background: #ffc107 !important;
             color: #212529 !important; /* Dark text for yellow background */
         }
         
         .tag-checkbox:checked + .tag-label[style*="#6f42c1"] {
             background: #6f42c1 !important;
         }
         
         .tag-checkbox:checked + .tag-label[style*="#fd7e14"] {
             background: #fd7e14 !important;
         }

        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

                 /* Form styling */
         .form-section {
             margin-bottom: 1.5rem;
         }

        .form-label {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 0.75rem;
            display: block;
            transition: color 0.3s ease;
        }

        .form-control {
            background: var(--surface-1);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 0.75rem;
            padding: 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: var(--surface-2);
            border-color: var(--modern-blue);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.2rem var(--border-glow);
            outline: none;
            transform: translateY(-1px);
        }

                 .form-control::placeholder {
             color: var(--text-muted);
             transition: color 0.3s ease;
         }

         /* Custom file input styling */
         .form-control[type="file"] {
             position: relative;
             background: var(--surface-1);
             border: 2px dashed var(--border-color);
             color: var(--text-secondary);
             padding: 1.5rem;
             text-align: center;
             cursor: pointer;
             transition: all 0.3s ease;
         }

         .form-control[type="file"]:hover {
             border-color: var(--modern-blue);
             background: var(--surface-2);
             transform: translateY(-1px);
         }

         .form-control[type="file"]:focus {
             border-color: var(--modern-blue);
             background: var(--surface-2);
             box-shadow: 0 0 0 0.2rem var(--border-glow);
         }

         /* Style the file input button */
         .form-control[type="file"]::file-selector-button {
             background: linear-gradient(135deg, var(--modern-blue), var(--modern-purple));
             color: white;
             border: none;
             border-radius: 0.5rem;
             padding: 0.5rem 1rem;
             margin-right: 1rem;
             cursor: pointer;
             font-weight: 500;
             transition: all 0.3s ease;
         }

         .form-control[type="file"]::file-selector-button:hover {
             transform: translateY(-1px);
             box-shadow: 0 4px 12px var(--border-glow);
         }

         /* Firefox file input styling */
         .form-control[type="file"]::-moz-file-upload-button {
             background: linear-gradient(135deg, var(--modern-blue), var(--modern-purple));
             color: white;
             border: none;
             border-radius: 0.5rem;
             padding: 0.5rem 1rem;
             margin-right: 1rem;
             cursor: pointer;
             font-weight: 500;
             transition: all 0.3s ease;
         }

         .form-control[type="file"]::-moz-file-upload-button:hover {
             transform: translateY(-1px);
             box-shadow: 0 4px 12px var(--border-glow);
         }

        /* Button styling */
        .btn-primary {
            background: linear-gradient(135deg, var(--modern-blue), var(--modern-purple));
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem 2rem;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px var(--border-glow);
            color: white;
        }

        .btn-secondary {
            background: var(--surface-1);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 0.75rem;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: var(--surface-2);
            color: var(--text-primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

                 /* Preview container styling */
         #preview-container {
             margin-top: 1rem;
             display: grid;
             grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
             gap: 0.75rem;
             max-height: 150px;
             overflow-y: auto;
         }

        .preview-item {
            position: relative;
            background: var(--surface-1);
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .preview-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

                 .preview-item img {
             width: 100%;
             height: 80px;
             object-fit: cover;
             border-radius: 0.5rem;
         }

        .preview-item .remove-file {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }

        .preview-item .remove-file:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.5);
        }

        .file-name {
            font-size: 0.8rem;
            padding: 0.5rem;
            color: var(--text-secondary);
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            text-align: center;
            transition: color 0.3s ease;
        }

                 .file-icon {
             display: flex;
             align-items: center;
             justify-content: center;
             height: 60px;
             color: var(--text-muted);
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

        /* Typography */
        h1, h2, h3, h4, h5, h6 {
            color: var(--text-primary);
            transition: color 0.3s ease;
        }

        p {
            color: var(--text-primary);
            transition: color 0.3s ease;
        }

        /* Bootstrap overrides */
        .text-white {
            color: white !important;
        }

        .mb-0 {
            margin-bottom: 0 !important;
        }
    </style>
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
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card fade-in" data-aos="fade-up">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Create New Post</h5>
                    </div>
                    <div class="card-body">
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
                                    <?php foreach ($tags as $tag): ?>
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
                            
                            <div class="d-flex justify-content-between" data-aos="fade-up" data-aos-delay="500">
                                <a href="view_posts.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Post</button>
                            </div>
                        </form>
                    </div>
                </div>
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

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('post-form');
            const fileInput = document.getElementById('attachments');
            const previewContainer = document.getElementById('preview-container');
            const maxFileSize = 5 * 1024 * 1024; // 5MB
            let files = [];

            // Handle file input change
            fileInput.addEventListener('change', function(e) {
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
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Check if at least one tag is selected
                const selectedTags = document.querySelectorAll('input[name="tags[]"]:checked');
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
                        window.location.href = 'view_posts.php';
                    } else {
                        alert('Error: ' + (data.message || 'Unknown error occurred'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while submitting your post');
                });
            });
            
            // Theme handling - Listen for theme changes from navbar
            document.addEventListener('themeChanged', function(e) {
                console.log('Theme changed to: ' + e.detail.theme);
                
                // Force repaint for smooth transitions
                document.body.style.transform = 'translateZ(0)';
                setTimeout(() => {
                    document.body.style.transform = '';
                }, 50);
            });
        });
    </script>
</body>
</html>