<?php
require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/model/db_connect.php';

// Import MongoDB classes
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;

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

$postId = $_GET['id'];

// Get database connection
$client = connectToDatabase();
$db = $client->uiurp;
$collection = $db->forum_posts;
$tagCollection = $db->forum_tags;

// Get the post
$post = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($postId)]);

// Check if post exists and belongs to the current user
if (!$post || $post['user_id'] !== $_SESSION['user_id']) {
    header('Location: view_posts.php');
    exit();
}

// Convert BSON arrays to PHP arrays
if (isset($post['tags']) && $post['tags'] instanceof MongoDB\Model\BSONArray) {
    $post['tags'] = $post['tags']->getArrayCopy();
}

// Convert attachments from BSON to PHP arrays if needed
if (isset($post['attachments'])) {
    if ($post['attachments'] instanceof MongoDB\Model\BSONArray) {
        $post['attachments'] = $post['attachments']->getArrayCopy();
        
        // Also convert each attachment document
        foreach ($post['attachments'] as $key => $attachment) {
            if ($attachment instanceof MongoDB\Model\BSONDocument) {
                $post['attachments'][$key] = $attachment->getArrayCopy();
            }
        }
    }
}

// Get all available tags
$tags = $tagCollection->find()->toArray();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post</title>
    
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

        /* Main Container */
        .container {
            position: relative;
            z-index: 1;
            margin-top: 90px;
            padding-bottom: 20px;
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

        /* Current attachments styling */
        .current-attachments {
            margin-top: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            max-height: 250px;
            overflow-y: auto;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            background: var(--surface-1);
            min-height: 120px;
        }

        .current-attachments:empty::before {
            content: "No current attachments";
            color: var(--text-muted);
            font-style: italic;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100px;
        }

        /* Custom scrollbar for attachments */
        .current-attachments::-webkit-scrollbar {
            width: 8px;
        }

        .current-attachments::-webkit-scrollbar-track {
            background: var(--surface-2);
            border-radius: 4px;
        }

        .current-attachments::-webkit-scrollbar-thumb {
            background: var(--modern-blue);
            border-radius: 4px;
            transition: background 0.3s ease;
        }

        .current-attachments::-webkit-scrollbar-thumb:hover {
            background: var(--modern-purple);
        }

        /* Firefox scrollbar */
        .current-attachments {
            scrollbar-width: thin;
            scrollbar-color: var(--modern-blue) var(--surface-2);
        }

        .attachment-item {
            position: relative;
            background: var(--card-bg);
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            width: 140px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .attachment-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: var(--modern-blue);
        }

        .attachment-content {
            position: relative;
            width: 100%;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--surface-2);
        }

        .attachment-item img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            display: block;
        }

        .attachment-item .remove-attachment {
            position: absolute;
            top: 4px;
            right: 4px;
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(220, 53, 69, 0.5);
            z-index: 15;
            border: 2px solid white;
            line-height: 1;
        }

        .attachment-item .remove-attachment:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.7);
            background: linear-gradient(135deg, #e74c3c, #dc3545);
        }

        [data-theme="light"] .attachment-item .remove-attachment {
            border: 2px solid var(--bg-primary);
            box-shadow: 0 2px 10px rgba(220, 53, 69, 0.3);
        }

        [data-theme="light"] .attachment-item .remove-attachment:hover {
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.5);
        }

        .file-name {
            font-size: 0.75rem;
            padding: 0.5rem;
            color: var(--text-secondary);
            background: var(--surface-1);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            text-align: center;
            transition: color 0.3s ease;
            border-top: 1px solid var(--border-color);
        }

        .file-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            height: 100px;
            color: var(--text-muted);
            transition: color 0.3s ease;
            background: var(--surface-2);
        }

        .file-icon svg {
            margin-bottom: 0.25rem;
        }

        .file-icon .file-type {
            font-size: 0.6rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: var(--text-muted);
        }

        /* Light theme adjustments */
        [data-theme="light"] .attachment-item {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        [data-theme="light"] .attachment-item:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        /* Preview container styling */
        #preview-container {
            margin-top: 1rem;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
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
            height: 100px;
            object-fit: cover;
            border-radius: 0.5rem;
        }
        
        /* Video styling in previews */
        .preview-item video {
            width: 100%;
            height: 100px;
            object-fit: contain;
            border-radius: 0.5rem;
            background-color: #000;
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
            right: 30px;
            font-weight: bold;
            z-index: 10;
        }

        .preview-item .remove-file {
            position: absolute;
            top: 4px;
            right: 4px;
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(220, 53, 69, 0.5);
            z-index: 15;
            border: 2px solid white;
            line-height: 1;
        }

        .preview-item .remove-file:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.7);
            background: linear-gradient(135deg, #e74c3c, #dc3545);
        }

        [data-theme="light"] .preview-item .remove-file {
            border: 2px solid var(--bg-primary);
            box-shadow: 0 2px 10px rgba(220, 53, 69, 0.3);
        }

        [data-theme="light"] .preview-item .remove-file:hover {
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.5);
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
                        <h5 class="mb-0">Edit Post</h5>
                    </div>
                    <div class="card-body">
                        <form id="edit-post-form" enctype="multipart/form-data">
                            <input type="hidden" id="postId" name="postId" value="<?= $postId ?>">
                            
                            <div class="form-section" data-aos="fade-up" data-aos-delay="100">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($post['title']) ?>" required>
                            </div>
                            
                            <div class="form-section" data-aos="fade-up" data-aos-delay="200">
                                <label for="content" class="form-label">Content</label>
                                <textarea class="form-control" id="content" name="content" rows="4" required><?= htmlspecialchars($post['content']) ?></textarea>
                            </div>
                            
                            <div class="form-section" data-aos="fade-up" data-aos-delay="300">
                                <label class="form-label">Tags (select at least one)</label>
                                <div class="tags-container">
                                    <?php foreach ($tags as $tag): ?>
                                        <input type="checkbox" 
                                               class="tag-checkbox" 
                                               id="tag-<?= $tag['name'] ?>" 
                                               name="tags[]" 
                                               value="<?= $tag['name'] ?>"
                                               <?= in_array($tag['name'], $post['tags']) ? 'checked' : '' ?>>
                                        <label class="tag-label" 
                                               for="tag-<?= $tag['name'] ?>" 
                                               style="color: <?= $tag['color'] ?>; border-color: <?= $tag['color'] ?>;">
                                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $tag['name']))) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($post['attachments'])): ?>
                                <div class="form-section" data-aos="fade-up" data-aos-delay="400">
                                    <label class="form-label">Current Attachments</label>
                                    <div class="current-attachments">
                                        <?php foreach ($post['attachments'] as $index => $attachment): ?>
                                            <?php 
                                                $isImage = strpos($attachment['file_type'], 'image/') === 0;
                                                $isVideo = strpos($attachment['file_type'], 'video/') === 0;
                                                $fileExt = strtoupper(pathinfo($attachment['original_name'], PATHINFO_EXTENSION));
                                            ?>
                                            <div class="attachment-item" data-index="<?= (string)$index ?>" data-debug="Index type: <?= gettype($index) ?>" data-original-name="<?= htmlspecialchars($attachment['original_name']) ?>">
                                                <div class="attachment-content">
                                                    <?php if ($isImage): ?>
                                                        <img src="<?= $attachment['file_path'] ?>" alt="Attachment">
                                                    <?php elseif ($isVideo): ?>
                                                        <video src="<?= $attachment['file_path'] ?>" controls muted style="max-width: 100%; max-height: 120px;"></video>
                                                        <div class="file-format"><?= $fileExt ?></div>
                                                    <?php else: ?>
                                                        <div class="file-icon">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-file-earmark" viewBox="0 0 16 16">
                                                                <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h-2z"/>
                                                            </svg>
                                                            <div class="file-type"><?= $fileExt ?></div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="file-name"><?= htmlspecialchars($attachment['original_name']) ?></div>
                                                <div class="remove-attachment">×</div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-section" data-aos="fade-up" data-aos-delay="500">
                                <label for="attachments" class="form-label">Add New Attachments (optional)</label>
                                <input type="file" class="form-control" id="attachments" name="files[]" multiple>
                                <div id="preview-container" class="mt-2"></div>
                            </div>
                            
                            <div class="d-flex justify-content-between" data-aos="fade-up" data-aos-delay="600">
                                <a href="view_posts.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
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
            const form = document.getElementById('edit-post-form');
            const fileInput = document.getElementById('attachments');
            const previewContainer = document.getElementById('preview-container');
            const maxFileSize = 100 * 1024 * 1024; // 100MB
            let files = [];
            let deleteAttachments = [];
            
            // Handle file input change
            fileInput.addEventListener('change', function(e) {
                const selectedFiles = Array.from(e.target.files);
                
                // Clear preview
                previewContainer.innerHTML = '';
                files = [];
                
                selectedFiles.forEach(file => {
                    // Check file size
                    if (file.size > maxFileSize) {
                        alert(`File ${file.name} is too large. Maximum size is 100MB.`);
                        return;
                    }
                    
                    files.push(file);
                    
                    const previewItem = document.createElement('div');
                    previewItem.className = 'preview-item';
                    
                    if (file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = URL.createObjectURL(file);
                        previewItem.appendChild(img);
                    } else if (file.type.startsWith('video/')) {
                        const video = document.createElement('video');
                        video.src = URL.createObjectURL(file);
                        video.controls = true;
                        video.muted = true;
                        video.style.maxWidth = '100%';
                        video.style.maxHeight = '150px';
                        previewItem.appendChild(video);
                        
                        // Add video format indicator
                        const format = document.createElement('div');
                        format.className = 'file-format';
                        format.textContent = file.type.split('/')[1].toUpperCase();
                        previewItem.appendChild(format);
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
            
            // Handle remove attachment button click
            document.querySelectorAll('.remove-attachment').forEach(button => {
                button.addEventListener('click', function() {
                    const attachmentItem = this.closest('.attachment-item');
                    const index = attachmentItem.getAttribute('data-index');
                    
                    // Add to delete list - ensure consistent string format
                    deleteAttachments.push(String(index));
                    
                    // Hide from UI
                    attachmentItem.remove();
                    
                    console.log('Marked attachment for deletion:', index);
                    console.log('Current deleteAttachments array:', deleteAttachments);
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
                formData.append('postId', document.getElementById('postId').value);
                formData.append('title', document.getElementById('title').value);
                formData.append('content', document.getElementById('content').value);
                
                // Add selected tags
                selectedTags.forEach(tag => {
                    formData.append('tags[]', tag.value);
                });
                
                // Add files to be deleted
                if (deleteAttachments.length > 0) {
                    console.log('Submitting deleteAttachments:', deleteAttachments);
                    deleteAttachments.forEach(index => {
                        formData.append('delete_attachments[]', index);
                    });
                }
                
                // For debugging - log the formData contents
                console.log('FormData entries:');
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }
                
                // Add new files
                files.forEach(file => {
                    formData.append('files[]', file);
                });
                
                // Submit form data
                fetch('src/controller/edit_post.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'post_details.php?id=' + document.getElementById('postId').value;
                    } else {
                        alert('Error: ' + (data.message || 'Unknown error occurred'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating your post');
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