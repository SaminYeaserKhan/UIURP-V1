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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles/style.css">
    <link rel="stylesheet" href="assets/styles/faculty_page.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap');
        
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
            --light-color: #f8fafc;
            --dark-color: #0f172a;
            --success-color: #06b6d4;
            --warning-color: #f59e0b;
            --modern-blue: #0ea5e9;
            --modern-purple: #8b5cf6;
            --modern-teal: #14b8a6;
            --modern-gray: #6b7280;
            --card-bg: rgba(15, 23, 42, 0.6);
            --glass-bg: rgba(22, 28, 45, 0.7);
            --border-glow: rgba(37, 99, 235, 0.5);
            --surface-1: rgba(30, 41, 59, 0.6);
            --surface-2: rgba(15, 23, 42, 0.8);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --text-muted: rgba(255, 255, 255, 0.5);
        }
        
        /* Light Theme Variables */
        [data-theme="light"] {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
            --light-color: #ffffff;
            --dark-color: #1e293b;
            --success-color: #06b6d4;
            --warning-color: #f59e0b;
            --modern-blue: #0ea5e9;
            --modern-purple: #8b5cf6;
            --modern-teal: #14b8a6;
            --modern-gray: #6b7280;
            --card-bg: rgba(255, 255, 255, 0.9);
            --glass-bg: rgba(255, 255, 255, 0.8);
            --border-glow: rgba(37, 99, 235, 0.3);
            --surface-1: rgba(248, 250, 252, 0.9);
            --surface-2: rgba(255, 255, 255, 0.95);
            --text-primary: #1e293b;
            --text-secondary: rgba(30, 41, 59, 0.7);
            --text-muted: rgba(30, 41, 59, 0.5);
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0a0d1a 0%, #1a1a2e 50%, #16213e 100%);
            font-family: 'Inter', 'Segoe UI', sans-serif;
            overflow-x: hidden;
            color: var(--text-primary);
            position: relative;
            min-height: 100vh;
            scroll-behavior: smooth;
            transition: background 0.4s ease-in-out, color 0.4s ease-in-out;
        }
        
        /* Light Theme Background */
        [data-theme="light"] body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #cbd5e1 100%);
        }
        

        /* Enhanced Background Effects */
        .background-effects {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        
        .floating-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(40px);
            opacity: 0.4;
            animation: float-orb 15s ease-in-out infinite;
            box-shadow: 0 0 50px currentColor;
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
        
        @keyframes float-orb {
            0%, 100% { transform: translate(0, 0) scale(1) rotate(0deg); }
            25% { transform: translate(30px, -20px) scale(1.05) rotate(90deg); }
            50% { transform: translate(-20px, 30px) scale(0.95) rotate(180deg); }
            75% { transform: translate(25px, 15px) scale(1.02) rotate(270deg); }
        }
        
        /* Dynamic Grid Background */
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
        }
        
        @keyframes grid-pulse {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.6; }
        }
        
        /* Enhanced Particles */
        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 0;
            pointer-events: none;
        }
        
        /* Neo Faculty Card Styles */
        .neo-faculty-card {
            position: relative;
            background: rgba(30, 41, 59, 0.95);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
            padding: 0;
            height: auto;
            min-height: 440px;
            width: 100%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3),
                        0 1px 3px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            cursor: pointer;
        }
        
        .neo-faculty-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2),
                        0 2px 6px rgba(0, 0, 0, 0.1);
            border-color: rgba(255, 255, 255, 0.12);
        }
        
        .card-border {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 16px;
            background: transparent;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
            z-index: 1;
        }
        
        .faculty-img-wrapper {
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .faculty-img-container {
            position: relative;
            overflow: hidden;
            border-radius: 16px 16px 0 0;
            height: 280px;
            width: 100%;
            background: #2d3748;
        }
        
        .faculty-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center top;
            transition: transform 0.3s ease;
        }
        
        .neo-faculty-card:hover .faculty-img-container img {
            transform: scale(1.02);
        }
        
        .faculty-specialty-badge {
            display: none !important;
        }
        
        .faculty-info {
            padding: 1.25rem;
            position: relative;
            z-index: 3;
            flex: 1;
            display: flex;
            flex-direction: column;
            background: rgba(30, 41, 59, 0.95);
            min-height: 140px;
        }
        
        .faculty-name {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #f7fafc;
            line-height: 1.3;
            letter-spacing: -0.02em;
            transition: color 0.3s ease;
        }
        
        .faculty-position {
            font-size: 0.85rem;
            color: #a0aec0;
            margin-bottom: 0.5rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .faculty-department {
            font-size: 0.8rem;
            color: #718096;
            margin-bottom: 1rem;
            font-weight: 400;
        }
        
        .faculty-department i {
            opacity: 0.7;
        }
        
        .faculty-quote {
            background: rgba(45, 55, 72, 0.5);
            border-left: 3px solid rgba(255, 255, 255, 0.2);
            border-radius: 0 8px 8px 0;
            padding: 12px 15px;
            font-style: italic;
            font-size: 0.85rem;
            color: #cbd5e0;
            margin-bottom: 0;
            margin-top: 1rem;
            position: relative;
            transition: all 0.3s ease;
            line-height: 1.4;
            flex: 1;
            min-height: 60px;
            display: flex;
            align-items: center;
        }
        
        .img-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, 
                        transparent 0%, 
                        transparent 60%,
                        rgba(30, 41, 59, 0.3) 100%);
            z-index: 2;
            transition: all 0.3s ease;
        }
        
        /* Enhanced Hero Section with Dynamic Background */
        .hero-section {
            background: linear-gradient(135deg, 
                rgba(15, 23, 42, 0.95) 0%, 
                rgba(30, 41, 59, 0.9) 50%,
                rgba(37, 99, 235, 0.8) 100%);
            min-height: 65vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 20px 0 140px;
            clip-path: ellipse(100% 100% at 50% 0%);
            transition: clip-path 0.3s ease-out;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="circuit" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M0,10 L10,10 L10,0 L20,0 M10,10 L10,20 M10,15 L20,15" stroke="rgba(255,255,255,0.05)" stroke-width="0.5" fill="none"/></pattern></defs><rect width="100" height="100" fill="url(%23circuit)"/></svg>');
            opacity: 0.6;
            z-index: 1;
        }
        
        .hero-section::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: 50%;
            transform: translateX(-50%);
            width: 120%;
            height: 150px;
            background: radial-gradient(ellipse 80% 100% at 50% 0%, 
                rgba(37, 99, 235, 0.3) 0%, 
                rgba(14, 165, 233, 0.2) 30%,
                rgba(6, 182, 212, 0.1) 60%,
                transparent 100%);
            border-radius: 50%;
            filter: blur(20px);
            z-index: 2;
            transition: all 0.3s ease-out;
        }
        
        /* Replace the old hero-image with background */
        .hero-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.3;
            z-index: 0;
            pointer-events: none;
        }
        
        /* Enhanced Overlay */
        .overlay {
            position: relative;
            text-align: center;
            color: white;
            background: linear-gradient(135deg, 
                rgba(15, 23, 42, 0.6) 0%, 
                rgba(30, 41, 59, 0.7) 100%);
            backdrop-filter: blur(15px);
            padding: 3rem 2rem;
            border-radius: 20px;
            border: 1px solid rgba(37, 99, 235, 0.2);
            width: 100%;
            max-width: 900px;
            z-index: 100;
            box-shadow: 
                0 15px 30px rgba(0, 0, 0, 0.2),
                0 0 20px rgba(37, 99, 235, 0.1);
            animation: fade-in-up 1s ease-out forwards;
                margin: -100px auto 0;
    pointer-events: auto;
    left: 0;
    right: 0;
    transform: translateX(0);
        }
        
        .overlay::before {
            content: '';
            position: absolute;
            top: -1px;
            left: -1px;
            right: -1px;
            bottom: -1px;
            background: linear-gradient(45deg, rgba(37, 99, 235, 0.3), transparent, rgba(14, 165, 233, 0.3));
            border-radius: inherit;
            z-index: -1;
            animation: gradient-rotate 6s linear infinite;
            pointer-events: none;
        }
        
        @keyframes gradient-rotate {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .overlay h1 {
            font-size: clamp(2.2rem, 6vw, 3.8rem);
            font-weight: 800;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #ffffff 0%, var(--modern-blue) 70%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 0 20px rgba(37, 99, 235, 0.3);
            animation: text-focus-in 1s cubic-bezier(0.55, 0.085, 0.68, 0.53) 0.2s both;
        }
        
        @keyframes text-focus-in {
            0% {
                filter: blur(12px);
                opacity: 0;
            }
            100% {
                filter: blur(0px);
                opacity: 1;
            }
        }
        
        .overlay h1::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, var(--modern-blue), var(--modern-teal));
            border-radius: 2px;
            box-shadow: 0 0 15px rgba(37, 99, 235, 0.6);
            animation: glow-pulse 2s ease-in-out infinite alternate;
        }
        
        @keyframes glow-pulse {
            from { box-shadow: 0 0 15px rgba(37, 99, 235, 0.6); }
            to { box-shadow: 0 0 25px rgba(20, 184, 166, 0.6); }
        }
        
        .overlay p {
            font-size: 1.2rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            color: var(--text-secondary);
            opacity: 0;
            animation: fade-in 1s ease forwards 0.5s;
        }
        
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fade-in-up {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Ultra Compact Search Container */
        .compact-search-container {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
            z-index: 101;
            pointer-events: auto;
        }
        
        .search-box-compact {
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid rgba(37, 99, 235, 0.3);
            border-radius: 40px;
            padding: 8px 8px 8px 20px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            box-shadow: none;
            position: relative;
            z-index: 102;
            pointer-events: auto;
        }
        
        .search-box-compact:hover,
        .search-box-compact:focus-within {
            border-color: var(--modern-blue);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
            transform: translateY(-1px);
        }
        
        .search-box-compact .d-flex {
            position: relative;
            z-index: 104;
            pointer-events: auto;
        }
        
        .search-icon-compact {
            color: var(--text-muted);
            margin-right: 15px;
            font-size: 1rem;
        }
        
        .search-input-compact {
            background: transparent;
            border: none;
            color: var(--text-primary);
            font-size: 1rem;
            width: 100%;
            padding: 12px 0;
            outline: none;
            position: relative;
            z-index: 103;
            pointer-events: auto;
        }
        
        .search-input-compact::placeholder {
            color: var(--text-muted);
        }
        
        .search-input-compact:focus {
            color: var(--modern-blue);
        }
        
        .search-btn-compact {
            background: linear-gradient(135deg, var(--modern-blue), var(--modern-teal));
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            color: white;
            margin-left: 10px;
            font-size: 0.9rem;
            position: relative;
            z-index: 103;
            pointer-events: auto;
            cursor: pointer;
        }
        
        .search-btn-compact:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
        }
        
        .toggle-btn-compact {
            background: transparent;
            border: 1px solid rgba(37, 99, 235, 0.3);
            border-radius: 25px;
            padding: 10px 20px;
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            z-index: 103;
            pointer-events: auto;
            cursor: pointer;
        }
        
        .toggle-btn-compact:hover {
            border-color: var(--modern-blue);
            color: var(--modern-blue);
            background: rgba(37, 99, 235, 0.05);
            transform: translateY(-2px);
        }
        
        .toggle-btn-compact.active {
            background: linear-gradient(135deg, var(--modern-blue), var(--modern-teal));
            border-color: var(--modern-blue);
            color: white;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
            transform: translateY(-2px);
        }
        
        .toggle-btn-compact.active:hover {
            background: linear-gradient(135deg, var(--modern-teal), var(--modern-blue));
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }
        
        /* Toggle buttons container */
        .compact-search-container .d-flex.gap-3 {
            position: relative;
            z-index: 103;
            pointer-events: auto;
        }
        
        /* Dynamic Background Layers */
        .hero-dynamic-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }
        
        .bg-layer-1 {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 120%;
            background: radial-gradient(circle at 20% 30%, 
                rgba(37, 99, 235, 0.15) 0%, 
                transparent 50%);
            transform-origin: center bottom;
            transition: transform 0.3s ease-out;
            pointer-events: none;
        }
        
        .bg-layer-2 {
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 110%;
            background: radial-gradient(circle at 80% 40%, 
                rgba(14, 165, 233, 0.12) 0%, 
                transparent 60%);
            transform-origin: center bottom;
            transition: transform 0.3s ease-out;
            pointer-events: none;
        }
        
        .bg-layer-3 {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 150%;
            height: 200px;
            background: linear-gradient(to top,
                rgba(37, 99, 235, 0.2) 0%,
                rgba(14, 165, 233, 0.1) 30%,
                transparent 70%);
            border-radius: 50%;
            filter: blur(30px);
            transform-origin: center bottom;
            transition: all 0.3s ease-out;
            pointer-events: none;
        }
        
        /* Container and column positioning */
        .hero-section .container {
            position: relative;
            z-index: 50;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            min-height: inherit;
            padding-top: 10px;
        }
        
        .hero-section .container .row {
            margin: 0;
            width: 100%;
            justify-content: center;
        }
        
        .hero-section .container .col-lg-10 {
            padding: 0 15px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .hero-section {
                min-height: 55vh;
                padding: 15px 0 100px;
            }
            
            .hero-section .container {
                padding: 0 20px;
                padding-top: 10px;
            }
            
            .overlay {
                padding: 2.5rem 1.5rem;
                width: 100%;
                max-width: 95%;
                margin: -50px auto 0;
            }
            
            .overlay h1 {
                font-size: clamp(1.8rem, 5vw, 3rem);
                margin-bottom: 1rem;
            }
        }
        
        @media (max-width: 768px) {
            .hero-section {
                min-height: 50vh;
                padding: 10px 0 90px;
                clip-path: ellipse(100% 90% at 50% 0%);
            }
            
            .hero-section .container {
                padding: 0 15px;
                padding-top: 5px;
            }
            
            .overlay {
                padding: 2rem 1.5rem;
                width: 100%;
                max-width: 100%;
                margin: -40px auto 0;
            }
            
            .overlay h1 {
                font-size: clamp(1.6rem, 4vw, 2.5rem);
                margin-bottom: 0.8rem;
            }
            
            .overlay p {
                font-size: 1rem;
                line-height: 1.4;
                margin-bottom: 1.5rem;
            }
            
            .search-input-compact {
                padding: 10px 0;
                font-size: 0.9rem;
            }
            
            .search-btn-compact {
                width: 35px;
                height: 35px;
                font-size: 0.8rem;
            }
            
            .toggle-btn-compact {
                padding: 8px 16px;
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 576px) {
            .hero-section {
                min-height: 45vh;
                padding: 5px 0 70px;
                clip-path: ellipse(100% 85% at 50% 0%);
            }
            
            .hero-section .container {
                padding: 0 10px;
                padding-top: 5px;
            }
            
            .overlay {
                padding: 1.5rem 1rem;
                width: 100%;
                max-width: 100%;
                margin: -30px auto 0;
            }
            
            .overlay h1 {
                font-size: clamp(1.4rem, 4vw, 2rem);
            }
            
            .overlay p {
                font-size: 0.9rem;
                margin-bottom: 1rem;
            }
            
            .search-box-compact {
                padding: 5px 5px 5px 14px;
            }
            
            .search-input-compact {
                font-size: 0.85rem;
                padding: 8px 0;
            }
            
            .search-btn-compact {
                width: 30px;
                height: 30px;
                font-size: 0.75rem;
            }
            
            .toggle-btn-compact {
                padding: 6px 14px;
                font-size: 0.8rem;
            }
        }

        /* Light theme overrides */
        [data-theme="light"] .neo-faculty-card {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(67, 97, 238, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        [data-theme="light"] .neo-faculty-card:hover {
            background: rgba(255, 255, 255, 0.98);
            border-color: rgba(67, 97, 238, 0.2);
            box-shadow: 0 8px 32px rgba(67, 97, 238, 0.12);
        }

        [data-theme="light"] .faculty-name {
            color: var(--text-primary);
        }

        [data-theme="light"] .faculty-position {
            color: var(--text-secondary);
        }

        [data-theme="light"] .faculty-quote {
            background: rgba(248, 250, 252, 0.8);
            border-left: 3px solid rgba(67, 97, 238, 0.3);
            color: var(--text-secondary);
        }

        [data-theme="light"] .faculty-info {
            background: rgba(255, 255, 255, 0.95);
        }

        [data-theme="light"] .faculty-specialty-badge {
            background: rgba(67, 97, 238, 0.1);
            border: 1px solid rgba(67, 97, 238, 0.2);
            color: var(--neo-primary);
        }

        [data-theme="light"] .neo-faculty-card:hover .faculty-specialty-badge {
            background: rgba(67, 97, 238, 0.15);
            border-color: rgba(67, 97, 238, 0.3);
            box-shadow: 0 4px 16px rgba(67, 97, 238, 0.2);
        }
        
        /* Additional theme-specific styles */
        [data-theme="light"] .hero-section {
            background: linear-gradient(135deg, 
                rgba(248, 250, 252, 0.95) 0%, 
                rgba(226, 232, 240, 0.9) 50%,
                rgba(56, 189, 248, 0.8) 100%);
        }
        
        [data-theme="light"] .overlay {
            background: linear-gradient(135deg, 
                rgba(255, 255, 255, 0.8) 0%, 
                rgba(240, 249, 255, 0.9) 100%);
            border: 1px solid rgba(37, 99, 235, 0.15);
            color: var(--text-primary);
        }
        
        [data-theme="light"] .search-box-compact {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(37, 99, 235, 0.2);
        }
        
        [data-theme="light"] .search-input-compact {
            color: var(--text-primary);
        }
        
        [data-theme="light"] .search-input-compact::placeholder {
            color: var(--text-muted);
        }
        
        [data-theme="light"] .cyber-grid {
            background-image: 
                linear-gradient(to right, rgba(37, 99, 235, 0.05) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(37, 99, 235, 0.05) 1px, transparent 1px);
        }
        
        [data-theme="light"] .floating-orb {
            opacity: 0.2;
        }
    </style>
</head>
  <body class="faculty-page">
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

      <!-- Enhanced Hero Section -->
      <section class="hero-section" id="hero-section">
          <!-- Dynamic Background Layers -->
          <div class="hero-dynamic-bg">
              <div class="bg-layer-1"></div>
              <div class="bg-layer-2"></div>
              <div class="bg-layer-3"></div>
    </div>

        <img src="assets/resources/faculty_hero.jpg" alt="Faculty Image" class="hero-image">
          
          <div class="container">
              <div class="row justify-content-center">
                  <div class="col-lg-10">
                      <div class="overlay" data-aos="fade-up" data-aos-duration="1000">
                          <h1 data-aos="fade-down" data-aos-duration="1000">Our Faculty</h1>
                          <p data-aos="fade-up" data-aos-delay="200">At the Head of the Class<br>UIU faculty are renowned leaders in their fields, extraordinary teachers, and dedicated mentors.</p>
                          
                          <!-- Enhanced Compact Search Bar -->
                          <div class="compact-search-container" data-aos="fade-up" data-aos-delay="300">
                              <div class="search-box-compact">
                                  <div class="d-flex align-items-center">
                                      <i class="fas fa-search search-icon-compact"></i>
                                      <input type="text" class="search-input-compact" placeholder="Search faculty by name, expertise, or department..." id="facultySearch" autocomplete="off">
                                      <button class="search-btn-compact" id="search-btn">
                                          <i class="fas fa-arrow-right"></i>
                                      </button>
                                  </div>
                              </div>
                              
                              <div class="text-center mt-3 d-flex justify-content-center gap-3 flex-wrap">
                                  <button class="toggle-btn-compact" id="toggle-alphabetical">
                                      <i class="fas fa-sort-alpha-down me-2"></i>Sort A-Z
                                  </button>
                                  <button class="toggle-btn-compact" id="toggle-department">
                                      <i class="fas fa-building me-2"></i>By Department
                                  </button>
                                  <button class="toggle-btn-compact" id="toggle-all-faculty">
                                      <i class="fas fa-users me-2"></i>Show All Faculty
                                  </button>
                              </div>
                          </div>
                </div>
            </div>
        </div>
    </div>
      </section>

    <!-- Faculty List Section -->
    <section class="faculty-section py-5">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="section-title">Meet Our Faculty</h2>
                <p class="section-subtitle">Explore the profiles of our distinguished faculty members</p>
            </div>
            <!-- Faculty cards will be loaded dynamically -->
            <div id="facultyList"></div>
        </div>
    </section>

    <?php include 'src/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
      <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
      <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script src="assets/scripts/faculty_loader.js"></script>
    
      <!-- Enhanced Faculty Page Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
          // Initialize theme FIRST before other components
          initializeTheme();
          
          // Initialize enhanced particles
          initializeEnhancedParticles();
          
          // Initialize enhanced AOS
          initializeEnhancedAOS();
          
          // Initialize scroll animations
          initializeScrollAnimations();
          
          // Initialize enhanced search functionality
          initializeEnhancedFacultySearch();
          
          // Initialize ripple effects
          initializeRippleEffects();
          
          // Initialize floating theme toggle
          initializeFloatingThemeToggle();
      });

      function initializeEnhancedParticles() {
          // Get current theme
          const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
          
          // Theme-specific particle configurations
          const particleConfig = {
              dark: {
                  colors: ["#2563eb", "#0ea5e9", "#06b6d4", "#3b82f6"],
                  strokeColor: "rgba(37, 99, 235, 0.3)",
                  lineColor: "#2563eb",
                  opacity: 0.3,
                  lineOpacity: 0.2
              },
              light: {
                  colors: ["#1e40af", "#0369a1", "#0891b2", "#1d4ed8"],
                  strokeColor: "rgba(30, 64, 175, 0.2)",
                  lineColor: "#1e40af",
                  opacity: 0.15,
                  lineOpacity: 0.1
              }
          };
          
          const config = particleConfig[currentTheme];
          
          particlesJS('particles-js', {
              "particles": {
                  "number": {
                      "value": 50,
                      "density": {
                          "enable": true,
                          "value_area": 1000
                      }
                  },
                  "color": {
                      "value": config.colors
                  },
                  "shape": {
                      "type": "circle",
                      "stroke": {
                          "width": 1,
                          "color": config.strokeColor
                      }
                  },
                  "opacity": {
                      "value": config.opacity,
                      "random": true,
                      "anim": {
                          "enable": true,
                          "speed": 1,
                          "opacity_min": config.opacity * 0.3,
                          "sync": false
                      }
                  },
                  "size": {
                      "value": 4,
                      "random": true,
                      "anim": {
                          "enable": true,
                          "speed": 2,
                          "size_min": 1,
                          "sync": false
                      }
                  },
                  "line_linked": {
                      "enable": true,
                      "distance": 150,
                      "color": config.lineColor,
                      "opacity": config.lineOpacity,
                      "width": 1.5
                  },
                  "move": {
                      "enable": true,
                      "speed": 1.5,
                      "direction": "none",
                      "random": true,
                      "straight": false,
                      "out_mode": "bounce",
                      "bounce": true
                  }
              },
              "interactivity": {
                  "detect_on": "window",
                  "events": {
                      "onhover": {
                          "enable": true,
                          "mode": "grab"
                      },
                      "onclick": {
                          "enable": true,
                          "mode": "push"
                      },
                      "resize": true
                  },
                  "modes": {
                      "grab": {
                          "distance": 200,
                          "line_linked": {
                              "opacity": config.lineOpacity * 3
                          }
                      },
                      "push": {
                          "particles_nb": 4
                      }
                  }
              },
              "retina_detect": true
          });
      }

      function initializeEnhancedAOS() {
          const isMobile = window.innerWidth <= 768;
          
          AOS.init({
              duration: isMobile ? 300 : 600,
              once: true,
              mirror: false,
              offset: 50,
              easing: 'ease-out',
              anchorPlacement: 'top-bottom',
              disable: isMobile ? 'mobile' : false,
              debounceDelay: 50,
              throttleDelay: 100
          });
      }

      function initializeScrollAnimations() {
          // Initialize hero background scroll effects
          initializeHeroScrollEffects();
          
          // Intersection Observer for fade-in-up animations
          const observerOptions = {
              threshold: 0.1,
              rootMargin: '0px 0px -50px 0px'
          };

          const observer = new IntersectionObserver((entries) => {
              entries.forEach(entry => {
                  if (entry.isIntersecting) {
                      entry.target.classList.add('visible');
                  }
              });
          }, observerOptions);

          // Observe all fade-in-up elements
          document.querySelectorAll('.fade-in-up').forEach(el => {
              observer.observe(el);
          });

          // Parallax effect for floating orbs
          let parallaxTicking = false;
          
          function updateParallax() {
              if (window.innerWidth > 768) {
                  const scrolled = window.pageYOffset;
                  
                  document.querySelectorAll('.floating-orb').forEach((orb, index) => {
                      const speed = 0.3 + (index * 0.1);
                      orb.style.transform = `translateY(${scrolled * speed}px)`;
                  });
              }
              parallaxTicking = false;
          }

          window.addEventListener('scroll', function() {
              if (!parallaxTicking) {
                  requestAnimationFrame(updateParallax);
                  parallaxTicking = true;
              }
          }, { passive: true });
      }

      function initializeHeroScrollEffects() {
          const heroSection = document.getElementById('hero-section');
          if (!heroSection) return;

          function updateHeroBackground(scrollY) {
              const heroHeight = heroSection.offsetHeight;
              const scrollProgress = Math.min(scrollY / (heroHeight * 0.8), 1);

              // Apply smooth transformations on desktop
              if (window.innerWidth > 768) {
                  const layers = heroSection.querySelectorAll('.bg-layer-1, .bg-layer-2, .bg-layer-3');
                  layers.forEach((layer, index) => {
                      const layerSpeed = (index + 1) * 0.1;
                      const translateY = scrollY * layerSpeed;
                      const scale = 1 - (scrollProgress * 0.2 * (index + 1) / layers.length);
                      
                      layer.style.transform = `translateY(${translateY}px) scale(${scale})`;
                  });
              }
          }

          let scrollTicking = false;

          function handleScroll() {
              if (!scrollTicking) {
                  requestAnimationFrame(() => {
                      updateHeroBackground(window.pageYOffset);
                      scrollTicking = false;
                  });
                  scrollTicking = true;
              }
          }

          window.addEventListener('scroll', handleScroll, { passive: true });
          
          // Initialize on load
          updateHeroBackground(window.pageYOffset);
      }
        
      function initializeEnhancedFacultySearch() {
          const searchInput = document.getElementById('facultySearch');
          const searchBtn = document.getElementById('search-btn');
          const toggleAlphabetical = document.getElementById('toggle-alphabetical');
          const toggleDepartment = document.getElementById('toggle-department');
          const toggleAllFaculty = document.getElementById('toggle-all-faculty');
          
          let searchTimeout;
          let currentSortMode = 'default';

          // Enhanced search with debouncing
          if (searchInput) {
              searchInput.addEventListener('input', function() {
                  clearTimeout(searchTimeout);
                  const query = this.value.trim();
                  
                  if (query.length > 2) {
                      searchTimeout = setTimeout(() => {
                          performFacultySearch(query);
                      }, 300);
                  } else if (query.length === 0) {
                      loadDefaultFaculty();
                  }
              });
          }

          // Enhanced search button with animation
          if (searchBtn) {
              searchBtn.addEventListener('click', function() {
                  const query = searchInput.value.trim();
                  if (query) {
                      // Add pulse animation
                      this.style.animation = 'pulse 0.6s ease-in-out';
                      setTimeout(() => {
                          this.style.animation = '';
                      }, 600);
                      
                      performFacultySearch(query);
                  }
              });
          }

          // Enhanced enter key support
          if (searchInput) {
              searchInput.addEventListener('keydown', function(e) {
                  if (e.key === 'Enter') {
                      e.preventDefault();
                      const query = this.value.trim();
                      if (query) {
                          performFacultySearch(query);
                      }
                  }
              });
          }

          // Toggle functionality
          if (toggleAlphabetical) {
              toggleAlphabetical.addEventListener('click', function() {
                  currentSortMode = currentSortMode === 'alphabetical' ? 'default' : 'alphabetical';
                  
                  this.style.transform = 'scale(0.95)';
                  setTimeout(() => {
                      this.innerHTML = currentSortMode === 'alphabetical' ? 
                          '<i class="fas fa-sort-alpha-up me-2"></i>Sort Z-A' : 
                          '<i class="fas fa-sort-alpha-down me-2"></i>Sort A-Z';
                      this.style.transform = 'scale(1)';
                  }, 150);
                  
                  resetButtonStates();
                  this.style.background = 'linear-gradient(135deg, var(--modern-blue), var(--modern-purple))';
                  this.style.color = 'white';
                  
                  if (searchInput) {
                      searchInput.value = '';
                  }
                  
                  loadFacultySorted(currentSortMode);
              });
          }

          if (toggleDepartment) {
              toggleDepartment.addEventListener('click', function() {
                  currentSortMode = currentSortMode === 'department' ? 'default' : 'department';
                  
                  this.style.transform = 'scale(0.95)';
                  setTimeout(() => {
                      this.innerHTML = currentSortMode === 'department' ? 
                          '<i class="fas fa-user me-2"></i>By Name' : 
                          '<i class="fas fa-building me-2"></i>By Department';
                      this.style.transform = 'scale(1)';
                  }, 150);
                  
                  resetButtonStates();
                  this.style.background = 'linear-gradient(135deg, var(--modern-blue), var(--modern-purple))';
                  this.style.color = 'white';
                  
                  if (searchInput) {
                      searchInput.value = '';
                  }
                  
                  loadFacultySorted(currentSortMode);
              });
          }

          if (toggleAllFaculty) {
              toggleAllFaculty.addEventListener('click', function() {
                  currentSortMode = 'default';
                  
                  this.style.transform = 'scale(0.95)';
                  setTimeout(() => {
                      this.style.transform = 'scale(1)';
                  }, 150);
                  
                  resetButtonStates();
                  this.style.background = 'linear-gradient(135deg, var(--modern-blue), var(--modern-purple))';
                  this.style.color = 'white';
                  
                  if (searchInput) {
                      searchInput.value = '';
                  }
                  
                  loadDefaultFaculty();
              });
          }

          function resetButtonStates() {
              [toggleAlphabetical, toggleDepartment, toggleAllFaculty].forEach(btn => {
                  if (btn) {
                      btn.style.background = '';
                      btn.style.color = '';
                  }
              });
          }

          function performFacultySearch(query) {
              // This will be handled by the existing faculty_loader.js
              // Just trigger the existing search functionality
              if (window.searchFaculty) {
                  window.searchFaculty(query);
              }
          }

          function loadDefaultFaculty() {
              // This will be handled by the existing faculty_loader.js
              if (window.loadAllFaculty) {
                  window.loadAllFaculty();
              }
          }

          function loadFacultySorted(sortMode) {
              // This will be handled by the existing faculty_loader.js
              if (window.sortFaculty) {
                  window.sortFaculty(sortMode);
              }
          }

          // Initialize with default faculty
          setTimeout(() => {
              loadDefaultFaculty();
          }, 100);
      }

      function initializeRippleEffects() {
          // Add ripple effect CSS
          const style = document.createElement('style');
          style.textContent = `
              @keyframes ripple-animation {
                  to {
                      transform: scale(4);
                      opacity: 0;
                  }
              }
              
              @keyframes pulse {
                  0% { transform: scale(1); }
                  50% { transform: scale(1.05); }
                  100% { transform: scale(1); }
              }
          `;
          document.head.appendChild(style);
      }

      // Theme Management System
      function initializeTheme() {
          // Get saved theme from localStorage or default to dark
          const savedTheme = localStorage.getItem('theme') || 'dark';
          
          // Apply theme to document
          document.documentElement.setAttribute('data-theme', savedTheme);
          
          // Listen for theme changes from navbar
          document.addEventListener('themeChanged', function(e) {
              // Reinitialize particles with new theme
              initializeEnhancedParticles();
              
              // Add transition effect
              document.body.style.transition = 'all 0.4s ease';
              setTimeout(() => {
                  document.body.style.transition = '';
              }, 400);
          });
      }

      // Initialize floating theme toggle button
      function initializeFloatingThemeToggle() {
          const themeToggleBtn = document.getElementById('floatingThemeToggle');
          
          if (themeToggleBtn) {
              // Set initial state
              updateThemeToggleAppearance();
              
              // Add click event
              themeToggleBtn.addEventListener('click', function() {
                  const currentTheme = document.documentElement.getAttribute('data-theme');
                  const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                  
                  // Save theme to localStorage
                  localStorage.setItem('theme', newTheme);
                  
                  // Update document attribute
                  document.documentElement.setAttribute('data-theme', newTheme);
                  
                  // Update toggle appearance
                  updateThemeToggleAppearance();
                  
                  // Notify other components about theme change
                  document.dispatchEvent(new CustomEvent('themeChanged', { 
                      detail: { theme: newTheme } 
                  }));
                  
                  // Add transition effect
                  this.classList.add('clicked');
                  setTimeout(() => {
                      this.classList.remove('clicked');
                  }, 400);
              });
          }
      }
      
      function updateThemeToggleAppearance() {
          const themeToggleBtn = document.getElementById('floatingThemeToggle');
          if (!themeToggleBtn) return;
          
          const currentTheme = document.documentElement.getAttribute('data-theme');
          
          if (currentTheme === 'light') {
              themeToggleBtn.classList.add('light-mode');
          } else {
              themeToggleBtn.classList.remove('light-mode');
          }
      }

      // Listen for theme changes from other pages/tabs
      window.addEventListener('storage', function(e) {
          if (e.key === 'theme') {
              const newTheme = e.newValue || 'dark';
              document.documentElement.setAttribute('data-theme', newTheme);
              
              // Update toggle appearance
              updateThemeToggleAppearance();
              
              // Reinitialize particles with new theme
              initializeEnhancedParticles();
          }
      });

      // Initialize enhanced interactions on page load
      window.addEventListener('load', function() {
          // Add entrance animation to hero
          const heroSection = document.querySelector('.hero-section');
          if (heroSection) {
              heroSection.style.opacity = '0';
              heroSection.style.transform = 'translateY(30px)';
              
              setTimeout(() => {
                  heroSection.style.transition = 'all 1s ease-out';
                  heroSection.style.opacity = '1';
                  heroSection.style.transform = 'translateY(0)';
              }, 100);
          }
        });
    </script>
    
    <!-- Include Global Meeting Notifications -->
    <?php include 'src/includes/global-meeting-notifications.php'; ?>
</body>
</html>