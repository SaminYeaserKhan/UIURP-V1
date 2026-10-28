<?php
// No need for session_start() here since it's now called at the beginning of each page
// Fetch current user data if logged in
// Determine the base path for correct relative links
$current_path = $_SERVER['PHP_SELF'];
$path_parts = explode('/', $current_path);
$depth = count(array_filter($path_parts)) - 1;
$base_path = $depth > 1 ? str_repeat('../', $depth - 1) : '';

// Initialize global preference tracking
require_once __DIR__ . '/global-preference-tracker.php';

// Include admin middleware for admin functions
try {
    require_once __DIR__ . '/admin_middleware.php';
} catch (Exception $e) {
    // If admin middleware is not available, create a simple fallback
    if (!function_exists('isAdmin')) {
        function isAdmin() {
            return false;
        }
    }
}

?>

<!-- Modern Navbar with Fluid Animations -->
<!-- Include Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">

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

<nav class="neo-navbar">
    <!-- Animated Background Layer -->
    <div class="navbar-bg-layer"></div>
    <div class="navbar-glow-effect"></div>
    <div class="navbar-grid-overlay"></div>
    
    <div class="navbar-container">
        <!-- Logo with Animation -->
        <a href="index.php" class="navbar-logo">
            <div class="text-logo">UIURP</div>
            <div class="logo-particles"></div>
            <span class="logo-glow"></span>
        </a>

        <!-- Mobile Menu Toggle -->
        <div class="mobile-toggle">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <!-- Navigation Links -->
        <div class="navbar-menu">
            <div class="nav-list">
                <a href="index.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="bi bi-house"></i></span>
                    <span class="nav-text">Home</span>
                    <span class="nav-highlight"></span>
                </a>
                
                <a href="Research_page.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'Research_page.php' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="bi bi-journal-richtext"></i></span>
                    <span class="nav-text">Projects</span>
                    <span class="nav-highlight"></span>
                </a>
                
                <a href="Faculty_Page.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'Faculty_Page.php' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="bi bi-mortarboard"></i></span>
                    <span class="nav-text">Faculty</span>
                    <span class="nav-highlight"></span>
                </a>
                
                <a href="events.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="bi bi-calendar-event"></i></span>
                    <span class="nav-text">Events</span>
                    <span class="nav-highlight"></span>
                </a>
                
                <a href="view_posts.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'view_posts.php' ? 'active' : ''; ?>">
                    <span class="nav-icon"><i class="bi bi-chat-square-text"></i></span>
                    <span class="nav-text">Forum</span>
                    <span class="nav-highlight"></span>
                </a>

                <a href="#" id="navChatButton" class="nav-item chat-icon-only">
                    <span class="nav-icon"><i class="bi bi-chat-dots-fill" style="color: var(--neo-blue);"></i></span>
                    <?php if(isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                        <span class="nav-notification-badge" id="chatNotificationBadge" style="display: none;">0</span>
                    <?php endif; ?>
                    <span class="nav-highlight"></span>
                </a>
            </div>

            <!-- User Profile Section -->
            <div class="user-section">
                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                    <div class="user-profile">
                        <div class="user-trigger" id="userMenuTrigger">
                            <div class="user-avatar">
                                <img src="<?= htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/resources/user_avatar.png') ?>" alt="User">
                                <div class="avatar-status"></div>
                                <div class="avatar-glow"></div>
                            </div>
                            <span class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                            <i class="bi bi-chevron-down"></i>
                        </div>
                        
                        <div class="user-menu-dropdown">
                            <div class="dropdown-header">
                                <div class="header-avatar">
                                    <img src="<?= htmlspecialchars($_SESSION['profile_pic']) ?>" alt="User">
                                    <div class="header-avatar-glow"></div>
                                </div>
                                <div class="header-info">
                                    <p class="header-name"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></p>
                                    <p class="header-email"><?= htmlspecialchars($_SESSION['email'] ?? 'addyouremail@gmail.com')?></p>
                                </div>
                            </div>
                            
                            <div class="dropdown-content">
                                <!-- Profile Section -->
                                <a href="<?= $base_path ?>Student_Profile.php" class="menu-item">
                                    <i class="bi bi-person-circle"></i>
                                    <span>View Profile</span>
                                    <div class="menu-item-glow"></div>
                                </a>
                                
                                <!-- Projects Section -->
                                <div class="menu-section">
                                    <p class="menu-section-title">Projects</p>
                                    <a href="<?= $base_path ?>project_management.php" class="menu-item">
                                        <i class="bi bi-folder-fill"></i>
                                        <span>My Projects</span>
                                        <div class="menu-item-glow"></div>
                                    </a>
                                    <a href="<?= $base_path ?>project_management.php#new-project" class="menu-item">
                                        <i class="bi bi-plus-circle"></i>
                                        <span>Create New Project</span>
                                        <div class="menu-item-glow"></div>
                                    </a>
                                </div>
                                
                                <?php if (isAdmin()): ?>
                                <!-- Admin Section -->
                                <div class="menu-section">
                                    <p class="menu-section-title">Administration</p>
                                    <a href="<?= $base_path ?>admin_dashboard.php" class="menu-item admin-menu-item">
                                        <i class="bi bi-shield-check"></i>
                                        <span>Admin Dashboard</span>
                                        <div class="menu-item-glow"></div>
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <div class="menu-divider"></div>
                                
                                <!-- Theme Toggle -->
                                <div class="dropdown-theme-toggle">
                                    <span class="theme-label"><i class="bi bi-palette me-2"></i>Theme Preferences</span>
                                    <div class="theme-toggle-container">
                                        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                                            <div class="toggle-track">
                                                <div class="toggle-thumb">
                                                    <i class="bi bi-sun-fill sun-icon"></i>
                                                    <i class="bi bi-moon-fill moon-icon"></i>
                                                </div>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                                
                                
                                
                                <div class="menu-divider"></div>
                                <a href="<?= $base_path ?>logout.php" class="menu-item logout">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Logout</span>
                                    <div class="menu-item-glow"></div>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= $base_path ?>login.php" class="neo-login-button">
                        <span class="button-content">Signup</span>
                        <span class="button-icon"><i class="bi bi-person-circle"></i></span>
                        <span class="button-glow"></span>
                    </a>
                <?php endif; ?>
                </div>
        </div>
    </div>
    
    <!-- Include Project Chat Overlay -->
    <?php include_once('project_chat_overlay.php'); ?>
</nav>

<!-- Navbar Styling and Animations -->
<style>
:root {
    /* Modern Futuristic Color Scheme */
    --neo-primary: #4361ee;
    --neo-secondary: #3a0ca3;
    --neo-accent: #7209b7;
    --neo-blue: #4cc9f0;
    --neo-magenta: #f72585;
    --neo-light: #f8f9fa;
    --neo-dark: #121729;
    
    /* Theme Variables - Dark Mode (Default) */
    --bg-primary: #0f172a;
    --bg-secondary: #1e293b;
    --bg-tertiary: #334155;
    --text-primary: #f8fafc;
    --text-secondary: #cbd5e1;
    --text-muted: #64748b;
    --border-color: rgba(76, 201, 240, 0.1);
    --shadow-color: rgba(0, 0, 0, 0.3);
    
    /* UI Variables */
    --navbar-bg: rgba(18, 23, 41, 0.7);
    --navbar-height: 75px;
    --navbar-height-scroll: 65px;
    --navbar-transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    --navbar-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    --navbar-gradient: linear-gradient(135deg, #4361ee, #3a0ca3);
    --neo-gradient: linear-gradient(135deg, #4cc9f0, #7209b7);
    --border-radius: 12px;
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
    --shadow-color: rgba(0, 0, 0, 0.08);
    --navbar-bg: rgba(255, 255, 255, 0.95);
    --navbar-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

/* Light Theme Navbar Specific Styles */
[data-theme="light"] .navbar-bg-layer {
    background: var(--navbar-bg);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(67, 97, 238, 0.1);
}

[data-theme="light"] .neo-navbar.scrolled .navbar-bg-layer {
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

[data-theme="light"] .text-logo {
    background: linear-gradient(135deg, #4361ee, #3a0ca3);
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
}

[data-theme="light"] .nav-item {
    color: var(--text-primary);
}

[data-theme="light"] .nav-item:hover {
    color: var(--neo-primary);
}

[data-theme="light"] .nav-item.active {
    color: var(--neo-primary);
}

[data-theme="light"] .nav-highlight {
    background: var(--gradient-primary);
}

[data-theme="light"] .neo-login-button {
    background: var(--gradient-primary);
    color: white;
}

[data-theme="light"] .user-name {
    color: var(--text-primary);
}

[data-theme="light"] .user-menu-dropdown {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

[data-theme="light"] .menu-item {
    color: var(--text-primary);
}

[data-theme="light"] .menu-item:hover {
    background: var(--bg-secondary);
    color: var(--neo-primary);
}

/* Core Navbar Structure */
.neo-navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: var(--navbar-height);
    z-index: 1000;
    transition: var(--navbar-transition);
    background: transparent;
}

.navbar-bg-layer {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--navbar-bg);
    backdrop-filter: blur(10px);
    z-index: -1;
    transition: var(--navbar-transition);
    border-bottom: 1px solid rgba(76, 201, 240, 0.1);
}

.navbar-glow-effect {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: linear-gradient(to right, 
        transparent, 
        rgba(76, 201, 240, 0.3), 
        rgba(114, 9, 183, 0.3), 
        rgba(76, 201, 240, 0.3), 
        transparent);
    z-index: -1;
    box-shadow: 0 0 20px 3px rgba(76, 201, 240, 0.2);
}

.navbar-grid-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: linear-gradient(to right, rgba(76, 201, 240, 0.03) 1px, transparent 1px),
                      linear-gradient(to bottom, rgba(76, 201, 240, 0.03) 1px, transparent 1px);
    background-size: 30px 30px;
    z-index: -1;
    opacity: 0.5;
    pointer-events: none;
}

.neo-navbar.scrolled {
    height: var(--navbar-height-scroll);
    background: var(--neo-dark);
}

.neo-navbar.scrolled .navbar-bg-layer {
    background: rgba(18, 23, 41, 0.95);
    backdrop-filter: blur(15px);
    box-shadow: var(--navbar-shadow);
}

.navbar-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 24px;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* Logo Styling and Animations */
.navbar-logo {
    display: flex;
    align-items: center;
    position: relative;
    z-index: 10;
    text-decoration: none;
}

.text-logo {
    font-size: 2.2rem;
    font-weight: 700;
    letter-spacing: -0.5px;
    background: linear-gradient(135deg, #4cc9f0, #7209b7);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    transition: var(--navbar-transition);
    filter: drop-shadow(0 0 15px rgba(76, 201, 240, 0.3));
    position: relative;
    z-index: 2;
    text-transform: uppercase;
}

.neo-navbar.scrolled .text-logo {
    font-size: 1.8rem;
}

.logo-glow {
    position: absolute;
    width: 60px;
    height: 60px;
    background: radial-gradient(circle, rgba(76, 201, 240, 0.3), transparent 70%);
    border-radius: 50%;
    z-index: 1;
    opacity: 0;
    transform: scale(0.8);
    transition: opacity 0.5s ease, transform 0.5s ease;
    top: 50%;
    left: 40px;
    margin-top: -30px;
    animation: pulse-glow 3s infinite alternate;
}

@keyframes pulse-glow {
    0% { opacity: 0.3; transform: translate(-50%, -50%) scale(1); }
    50% { opacity: 0.6; transform: translate(-50%, -50%) scale(1.1); }
    100% { opacity: 0.3; transform: translate(-50%, -50%) scale(1); }
}

.navbar-logo:hover .logo-glow {
    opacity: 0.8;
    transform: scale(1.5);
}

.logo-particles {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    z-index: 1;
    pointer-events: none;
}

/* Navigation Menu Styling */
.navbar-menu {
    display: flex;
    align-items: center;
    height: 100%;
}

.nav-list {
    display: flex;
    align-items: center;
    height: 100%;
    margin-right: 20px;
}

.nav-item {
    position: relative;
    display: flex;
    align-items: center;
    height: 100%;
    padding: 0 20px;
    text-decoration: none;
    color: var(--neo-light);
    font-weight: 500;
    font-size: 16px;
    transition: var(--navbar-transition);
    overflow: hidden;
}

.nav-icon {
    margin-right: 8px;
    font-size: 18px;
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), color 0.3s ease;
    display: inline-block;
    opacity: 0.8;
    color: rgba(255, 255, 255, 0.7);
}

.nav-text {
    position: relative;
    overflow: hidden;
    transition: var(--navbar-transition);
}

.nav-highlight {
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 0;
    height: 3px;
    background: var(--neo-gradient);
    border-radius: 3px;
    transform: translateX(-50%);
    transition: width 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 0 15px rgba(76, 201, 240, 0.5);
}

/* Navigation Item Hover Effects */
.nav-item:hover, 
.nav-item.active {
    color: #ffffff;
}

.nav-item:hover .nav-icon,
.nav-item.active .nav-icon {
    transform: translateY(-3px) scale(1.1);
    opacity: 1;
    color: var(--neo-blue);
}

.nav-item:hover .nav-highlight,
.nav-item.active .nav-highlight {
    width: 30px;
}

.nav-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(76, 201, 240, 0.1), rgba(114, 9, 183, 0.1));
    z-index: -1;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.nav-item:hover::before,
.nav-item.active::before {
    opacity: 1;
}

.nav-item.active .nav-highlight {
    width: 40px;
    background: linear-gradient(to right, #4cc9f0, #f72585);
    animation: neon-glow 2s infinite alternate;
}

@keyframes neon-glow {
    0% { box-shadow: 0 0 5px rgba(76, 201, 240, 0.5), 0 0 10px rgba(76, 201, 240, 0.3); }
    100% { box-shadow: 0 0 10px rgba(76, 201, 240, 0.7), 0 0 20px rgba(76, 201, 240, 0.5); }
}

/* User Section Styling */
.user-section {
    position: relative;
    height: 100%;
    display: flex;
    align-items: center;
}

.user-profile {
    position: relative;
}

.user-trigger {
    display: flex;
    align-items: center;
    cursor: pointer;
    padding: 8px 16px;
    border-radius: 50px;
    background: rgba(30, 41, 59, 0.4);
    border: 1px solid rgba(76, 201, 240, 0.2);
    transition: all 0.3s ease;
    min-width: 120px;
    max-width: 250px;
}

.user-trigger:hover {
    background: rgba(30, 41, 59, 0.6);
    border-color: rgba(76, 201, 240, 0.4);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.user-avatar {
    position: relative;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 10px;
    border: 2px solid rgba(76, 201, 240, 0.5);
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-status {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 10px;
    height: 10px;
    background-color: #4cc9f0;
    border-radius: 50%;
    border: 2px solid rgba(30, 41, 59, 0.8);
    z-index: 1;
}

.avatar-glow {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle at center, rgba(76, 201, 240, 0.3), transparent 70%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.user-trigger:hover .avatar-glow {
    opacity: 1;
}

.user-name {
    font-weight: 500;
    color: rgba(255, 255, 255, 0.9);
    margin: 0 10px;
    font-size: 14px;
    transition: all 0.3s ease;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Light Mode Styles for User Trigger */
[data-theme="light"] .user-trigger {
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(67, 97, 238, 0.2);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

[data-theme="light"] .user-trigger:hover {
    background: rgba(255, 255, 255, 1);
    border-color: rgba(67, 97, 238, 0.4);
    box-shadow: 0 5px 15px rgba(67, 97, 238, 0.1);
}

[data-theme="light"] .user-name {
    color: rgba(30, 41, 59, 0.9);
}

[data-theme="light"] .user-trigger i {
    color: rgba(30, 41, 59, 0.6);
}

[data-theme="light"] .user-trigger:hover .user-name {
    color: rgba(67, 97, 238, 1);
}

[data-theme="light"] .user-trigger:hover i {
    color: rgba(67, 97, 238, 0.8);
}

/* Responsive Styles for User Trigger */
@media (max-width: 1200px) {
    .user-trigger {
        padding: 6px 12px;
        min-width: 100px;
    }
    
    .user-avatar {
        width: 32px;
        height: 32px;
        margin-right: 8px;
    }
    
    .user-name {
        font-size: 13px;
        margin: 0 6px;
    }
}

@media (max-width: 991px) {
    .user-trigger {
        width: 100%;
        padding: 12px 16px;
        justify-content: space-between;
        min-width: unset;
        max-width: unset;
    }
    
    .user-avatar {
        width: 36px;
        height: 36px;
        margin-right: 12px;
    }
    
    .user-name {
        font-size: 14px;
        margin: 0 10px;
        flex: 1;
    }
}

@media (max-width: 480px) {
    .user-trigger {
        padding: 10px 14px;
    }
    
    .user-avatar {
        width: 30px;
        height: 30px;
        margin-right: 10px;
    }
    
    .user-name {
        font-size: 13px;
        margin: 0 8px;
    }
}

/* Dropdown Menu Styling */
.user-menu-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 320px;
    background: rgba(18, 23, 41, 0.95);
    border-radius: 12px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
    opacity: 0;
    transform: translateY(10px);
    pointer-events: none;
    transition: all 0.3s cubic-bezier(0.17, 0.67, 0.83, 0.67);
    overflow: hidden;
    border: 1px solid rgba(76, 201, 240, 0.2);
    backdrop-filter: blur(15px);
    z-index: 100;
}

.user-profile.active .user-menu-dropdown {
    opacity: 1;
    transform: translateY(0);
    pointer-events: all;
}

.dropdown-header {
    padding: 20px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid rgba(76, 201, 240, 0.1);
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.5), rgba(22, 28, 45, 0.5));
    min-height: 80px;
}

.header-avatar {
    position: relative;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 15px;
    border: 2px solid rgba(76, 201, 240, 0.5);
    flex-shrink: 0;
}

.header-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.header-avatar-glow {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle at center, rgba(76, 201, 240, 0.3), transparent 70%);
    opacity: 0.5;
    animation: pulse-glow 3s infinite alternate;
}

.header-info {
    flex: 1;
    min-width: 0;
    overflow: hidden;
}

.header-name {
    font-weight: 600;
    color: #ffffff;
    margin: 0 0 5px;
    font-size: 16px;
    line-height: 1.3;
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
    max-height: 2.8em;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.header-email {
    color: rgba(255, 255, 255, 0.6);
    margin: 0;
    font-size: 13px;
    line-height: 1.2;
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
    max-height: 2.4em;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.dropdown-content {
    padding: 15px;
}

.menu-item {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    border-radius: 8px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    margin-bottom: 5px;
}

.menu-item i {
    font-size: 18px;
    margin-right: 12px;
    transition: all 0.3s ease;
    color: rgba(76, 201, 240, 0.8);
}

.menu-item span {
    flex: 1;
    font-size: 14px;
    transition: all 0.3s ease;
}

.menu-item-glow {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(76, 201, 240, 0.1), rgba(114, 9, 183, 0.1));
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: -1;
}

.menu-item:hover {
    color: #ffffff;
    transform: translateX(5px);
}

.menu-item:hover i {
    transform: scale(1.1);
    color: rgba(76, 201, 240, 1);
}

.menu-item:hover .menu-item-glow {
    opacity: 1;
}

.menu-section {
    margin: 15px 0;
}

.menu-section-title {
    font-size: 12px;
    font-weight: 600;
    color: rgba(76, 201, 240, 0.7);
    margin: 0 0 10px;
    padding: 0 15px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.menu-divider {
    height: 1px;
    background: linear-gradient(to right, 
        transparent, 
        rgba(76, 201, 240, 0.1), 
        rgba(114, 9, 183, 0.1), 
        rgba(76, 201, 240, 0.1), 
        transparent);
    margin: 10px 0;
}

/* Dropdown Theme Toggle Styling */
.dropdown-theme-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 15px;
    border-radius: 8px;
    margin: 5px 0;
}

.dropdown-theme-toggle .theme-label {
    font-size: 14px;
    font-weight: 500;
    color: var(--text-secondary);
}

.dropdown-theme-toggle .theme-toggle-container {
    margin-right: 0;
}

.logout {
    color: rgba(247, 37, 133, 0.8);
}

.logout i {
    color: rgba(247, 37, 133, 0.8);
}

.logout:hover {
    color: rgba(247, 37, 133, 1);
}

.logout:hover i {
    color: rgba(247, 37, 133, 1);
}

/* Neo Button Styling for Login */
.neo-login-button {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 25px;
    background: linear-gradient(135deg, rgba(76, 201, 240, 0.8), rgba(114, 9, 183, 0.8));
    color: white;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 500;
    font-size: 15px;
    transition: all 0.3s cubic-bezier(0.17, 0.67, 0.83, 0.67);
    border: 1px solid rgba(76, 201, 240, 0.5);
    overflow: hidden;
    z-index: 1;
}

.neo-login-button .button-content {
    position: relative;
    z-index: 2;
    margin-right: 8px;
}

.neo-login-button .button-icon {
    position: relative;
    z-index: 2;
    font-size: 18px;
    transition: all 0.3s ease;
}

.neo-login-button .button-glow {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(76, 201, 240, 0.5), rgba(114, 9, 183, 0.5));
    z-index: 0;
    opacity: 0;
    transition: opacity 0.3s ease, transform 0.3s ease;
    transform: scale(0.9);
}

.neo-login-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(76, 201, 240, 0.3);
    color: white;
}

.neo-login-button:hover .button-icon {
    transform: translateX(3px);
}

.neo-login-button:hover .button-glow {
    opacity: 1;
    transform: scale(1.1);
}

/* Mobile Toggle Button */
.mobile-toggle {
    display: none;
    flex-direction: column;
    justify-content: space-between;
    width: 30px;
    height: 20px;
    cursor: pointer;
    z-index: 100;
}

.mobile-toggle span {
    width: 100%;
    height: 2px;
    background: var(--neo-light);
    border-radius: 4px;
    transition: var(--navbar-transition);
    transform-origin: left;
    box-shadow: 0 0 5px rgba(76, 201, 240, 0.5);
}

.mobile-toggle.active span:nth-child(1) {
    transform: rotate(45deg);
    background: rgba(76, 201, 240, 1);
}

.mobile-toggle.active span:nth-child(2) {
    opacity: 0;
}

.mobile-toggle.active span:nth-child(3) {
    transform: rotate(-45deg);
    background: rgba(76, 201, 240, 1);
}

/* Responsive Styling */
@media (max-width: 991px) {
    .navbar-container {
        padding: 0 16px;
    }
    
    .mobile-toggle {
        display: flex;
    }
    
    .navbar-menu {
        position: fixed;
        top: 0;
        right: 0;
        width: 80%;
        max-width: 350px;
        height: 100vh;
        background: rgba(18, 23, 41, 0.95);
        backdrop-filter: blur(15px);
        flex-direction: column;
        align-items: flex-start;
        justify-content: flex-start;
        padding: 100px 30px 30px;
        box-shadow: -10px 0 30px rgba(0, 0, 0, 0.2);
        transform: translateX(100%);
        transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        z-index: 50;
        overflow-y: auto;
        border-left: 1px solid rgba(76, 201, 240, 0.1);
    }
    
    .navbar-menu.active {
        transform: translateX(0);
    }
    
    .nav-list {
        flex-direction: column;
        align-items: flex-start;
        width: 100%;
        height: auto;
        margin-right: 0;
        margin-bottom: 30px;
    }
    
    .nav-item {
        width: 100%;
        height: auto;
        padding: 15px 0;
        border-bottom: 1px solid rgba(76, 201, 240, 0.1);
    }
    
    .nav-highlight {
        bottom: -1px;
        height: 2px;
    }
    
    .user-section {
        width: 100%;
        height: auto;
    }
    
    .user-profile, 
    .neo-login-button {
        width: 100%;
    }
    
    .user-trigger {
        width: 100%;
        justify-content: space-between;
    }
    
    .user-menu-dropdown {
        position: static;
        width: 100%;
        margin-top: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
    }
    
    /* Animation for mobile menu items */
    .nav-item, 
    .user-profile, 
    .neo-login-button {
        opacity: 0;
        transform: translateX(20px);
    }
    
    .navbar-menu.active .nav-item, 
    .navbar-menu.active .user-profile, 
    .navbar-menu.active .neo-login-button {
        animation: fadeSlideIn 0.5s forwards;
    }
    
    .navbar-menu.active .nav-item:nth-child(1) { animation-delay: 0.1s; }
    .navbar-menu.active .nav-item:nth-child(2) { animation-delay: 0.15s; }
    .navbar-menu.active .nav-item:nth-child(3) { animation-delay: 0.2s; }
    .navbar-menu.active .nav-item:nth-child(4) { animation-delay: 0.25s; }
    .navbar-menu.active .user-profile,
    .navbar-menu.active .neo-login-button { animation-delay: 0.3s; }
    
    @keyframes fadeSlideIn {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    /* Menu overlay for mobile */
    .menu-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(3px);
        opacity: 0;
        transition: opacity 0.3s ease;
        pointer-events: none;
        z-index: 45;
    }
    
    .menu-overlay.active {
        opacity: 1;
        pointer-events: all;
    }
}

/* Theme Toggle Styles - Subtle & Minimal Design */
.theme-toggle-container {
    display: flex;
    align-items: center;
    margin-right: 1rem;
}

.theme-toggle {
    background: none;
    border: none;
    padding: 8px;
    cursor: pointer;
    position: relative;
    z-index: 10;
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    display: flex;
    align-items: center;
    justify-content: center;
}

.theme-toggle:hover {
    background: rgba(255, 255, 255, 0.05);
    transform: translateY(-1px);
}

.toggle-track {
    width: 44px;
    height: 22px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 11px;
    position: relative;
    transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    border: 1px solid rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
}

.toggle-thumb {
    width: 18px;
    height: 18px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 50%;
    position: absolute;
    top: 2px;
    left: 2px;
    transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
}

.toggle-thumb i {
    font-size: 10px;
    color: #64748b;
    transition: all 0.3s ease;
    font-weight: 600;
}

.sun-icon {
    opacity: 0;
    transform: rotate(-180deg) scale(0.8);
}

.moon-icon {
    opacity: 1;
    transform: rotate(0deg) scale(1);
    position: absolute;
}

/* Light theme toggle state - More refined transitions */
[data-theme="light"] .toggle-track {
    background: rgba(0, 0, 0, 0.06);
    border-color: rgba(0, 0, 0, 0.08);
}

[data-theme="light"] .toggle-thumb {
    transform: translateX(22px);
    background: rgba(59, 130, 246, 0.9);
    box-shadow: 0 2px 12px rgba(59, 130, 246, 0.3);
}

[data-theme="light"] .toggle-thumb i {
    color: white;
}

[data-theme="light"] .sun-icon {
    opacity: 1;
    transform: rotate(0deg) scale(1);
}

[data-theme="light"] .moon-icon {
    opacity: 0;
    transform: rotate(180deg) scale(0.8);
}

[data-theme="light"] .theme-toggle:hover {
    background: rgba(0, 0, 0, 0.04);
}

.theme-toggle:hover .toggle-track {
    background: rgba(255, 255, 255, 0.15);
    box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
}

[data-theme="light"] .theme-toggle:hover .toggle-track {
    background: rgba(0, 0, 0, 0.1);
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
}

/* Responsive adjustments - Maintain proportions */
@media (max-width: 991px) {
    .theme-toggle-container {
        margin-right: 0.5rem;
    }
    
    .theme-toggle {
        padding: 6px;
    }
    
    .toggle-track {
        width: 38px;
        height: 20px;
        border-radius: 10px;
    }
    
    .toggle-thumb {
        width: 16px;
        height: 16px;
    }
    
    .toggle-thumb i {
        font-size: 9px;
    }
    
    [data-theme="light"] .toggle-thumb {
        transform: translateX(18px);
    }
}

/* Debug toggle specific styles */
.debug-toggle .toggle-track {
    background: rgba(247, 37, 133, 0.1);
    border-color: rgba(247, 37, 133, 0.2);
}

.debug-toggle .toggle-track.active {
    background: rgba(76, 201, 240, 0.2);
    border-color: rgba(76, 201, 240, 0.4);
    box-shadow: 0 0 10px rgba(76, 201, 240, 0.2);
}

.debug-toggle .toggle-thumb {
    background: rgba(247, 37, 133, 0.8);
}

.debug-toggle .toggle-thumb.active {
    transform: translateX(22px);
    background: rgba(76, 201, 240, 0.8);
}

.debug-toggle .toggle-thumb i {
    font-size: 10px;
    color: rgba(255, 255, 255, 0.9);
}

.debug-toggle .toggle-thumb .bi-toggle-on {
    opacity: 0;
    transform: scale(0.8);
    position: absolute;
}

.debug-toggle .toggle-thumb .bi-toggle-off {
    opacity: 1;
    transform: scale(1);
    position: absolute;
}

.debug-toggle .toggle-thumb.active .bi-toggle-on {
    opacity: 1;
    transform: scale(1);
}

.debug-toggle .toggle-thumb.active .bi-toggle-off {
    opacity: 0;
    transform: scale(0.8);
}

[data-theme="light"] .debug-toggle .toggle-track {
    background: rgba(247, 37, 133, 0.1);
    border-color: rgba(247, 37, 133, 0.2);
}

[data-theme="light"] .debug-toggle .toggle-track.active {
    background: rgba(76, 201, 240, 0.2);
    border-color: rgba(76, 201, 240, 0.4);
}

[data-theme="light"] .debug-toggle .toggle-thumb {
    background: rgba(247, 37, 133, 0.8);
}

[data-theme="light"] .debug-toggle .toggle-thumb.active {
    transform: translateX(22px);
    background: rgba(76, 201, 240, 0.8);
}

/* Admin Menu Item Styling */
.admin-menu-item {
    background: linear-gradient(135deg, rgba(247, 37, 133, 0.1), rgba(114, 9, 183, 0.1));
    border: 1px solid rgba(247, 37, 133, 0.2);
    border-radius: 8px;
    margin: 5px 0;
    position: relative;
    overflow: hidden;
}

.admin-menu-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(247, 37, 133, 0.1), transparent);
    transition: left 0.5s ease;
}

.admin-menu-item:hover::before {
    left: 100%;
}

.admin-menu-item i {
    color: rgba(247, 37, 133, 0.8);
    animation: adminPulse 2s infinite;
}

@keyframes adminPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.admin-menu-item:hover {
    background: linear-gradient(135deg, rgba(247, 37, 133, 0.2), rgba(114, 9, 183, 0.2));
    border-color: rgba(247, 37, 133, 0.4);
    transform: translateX(5px) scale(1.02);
}

.admin-menu-item:hover i {
    color: rgba(247, 37, 133, 1);
    animation: none;
    transform: scale(1.2);
}

[data-theme="light"] .admin-menu-item {
    background: linear-gradient(135deg, rgba(247, 37, 133, 0.05), rgba(114, 9, 183, 0.05));
    border-color: rgba(247, 37, 133, 0.15);
}

[data-theme="light"] .admin-menu-item:hover {
    background: linear-gradient(135deg, rgba(247, 37, 133, 0.1), rgba(114, 9, 183, 0.1));
    border-color: rgba(247, 37, 133, 0.3);
}
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const navbar = document.querySelector('.neo-navbar');
    const mobileToggle = document.querySelector('.mobile-toggle');
    const navbarMenu = document.querySelector('.navbar-menu');
    const userTrigger = document.getElementById('userMenuTrigger');
    const userProfile = document.querySelector('.user-profile');
    
    // Create menu overlay
    const overlay = document.createElement('div');
    overlay.className = 'menu-overlay';
    document.body.appendChild(overlay);
    
    // Handle scroll
    let lastScrollY = window.scrollY;
    let isScrollingDown = false;
    
    // Initial state check
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    }
    
    // Scroll handler with animation frame for performance
    window.addEventListener('scroll', function() {
        requestAnimationFrame(() => {
            // Check scroll direction
            isScrollingDown = window.scrollY > lastScrollY;
            lastScrollY = window.scrollY;
            
            // Toggle scrolled class
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
            
            // Add subtle movement for more dynamic feel
            if (isScrollingDown && window.scrollY > 100) {
                navbar.style.transform = 'translateY(-5px)';
                setTimeout(() => {
                    navbar.style.transform = 'translateY(0)';
                }, 200);
            }
        });
    });
    
    // Mobile menu toggle
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            navbarMenu.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.style.overflow = navbarMenu.classList.contains('active') ? 'hidden' : '';
        });
    }
    
    // Overlay click to close menu
    overlay.addEventListener('click', function() {
        mobileToggle.classList.remove('active');
        navbarMenu.classList.remove('active');
        this.classList.remove('active');
        document.body.style.overflow = '';
    });
    
    // User dropdown toggle
    if (userTrigger) {
        userTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            userProfile.classList.toggle('active');
        });
    }
    
    // Close user dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (userProfile && !userProfile.contains(e.target)) {
            userProfile.classList.remove('active');
        }
    });
    
    // Create logo particles effect
    const createLogoParticles = () => {
        const logoContainer = document.querySelector('.logo-particles');
        if (!logoContainer) return;
        
        const canvas = document.createElement('canvas');
        canvas.width = 100;
        canvas.height = 60;
        canvas.style.position = 'absolute';
        canvas.style.top = '0';
        canvas.style.left = '0';
        canvas.style.width = '100%';
        canvas.style.height = '100%';
        canvas.style.pointerEvents = 'none';
        
        logoContainer.appendChild(canvas);
        
        const ctx = canvas.getContext('2d');
        const particles = [];
        
        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 1.5 + 0.5;
                this.speedX = Math.random() * 0.5 - 0.25;
                this.speedY = Math.random() * 0.5 - 0.25;
                this.color = Math.random() > 0.5 ? 
                    `rgba(76, 201, 240, ${Math.random() * 0.5 + 0.3})` : 
                    `rgba(114, 9, 183, ${Math.random() * 0.5 + 0.3})`;
            }
            
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                
                if (this.x < 0 || this.x > canvas.width) this.speedX *= -1;
                if (this.y < 0 || this.y > canvas.height) this.speedY *= -1;
            }
            
            draw() {
                ctx.fillStyle = this.color;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        }
        
        const initParticles = () => {
            for (let i = 0; i < 15; i++) {
                particles.push(new Particle());
            }
        };
        
        const animateParticles = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            for (let i = 0; i < particles.length; i++) {
                particles[i].update();
                particles[i].draw();
            }
            
            requestAnimationFrame(animateParticles);
        };
        
        initParticles();
        animateParticles();
    };
    
    // Initialize logo particles
    createLogoParticles();
    
    // Theme Toggle Functionality
    const themeToggle = document.getElementById('themeToggle');
    const htmlElement = document.documentElement;
    
    // Check for saved theme preference or default to 'dark'
    const currentTheme = localStorage.getItem('theme') || 'dark';
    htmlElement.setAttribute('data-theme', currentTheme);
    
    // Theme toggle event listener
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const currentTheme = htmlElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            htmlElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Dispatch custom event for theme change
            const themeChangeEvent = new CustomEvent('themeChanged', {
                detail: { theme: newTheme }
            });
            document.dispatchEvent(themeChangeEvent);
            
            // Add a subtle animation effect
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    }
    
    // Debug overlay toggle functionality
    const debugToggle = document.getElementById('debugOverlayToggle');
    
    // Check saved state on page load
    if (debugToggle) {
        const debugEnabled = localStorage.getItem('debugOverlayEnabled') === 'true';
        
        // Update toggle appearance based on current state
        updateDebugToggleAppearance(debugEnabled);
        
        // Add click event listener
        debugToggle.addEventListener('click', function() {
            const isEnabled = localStorage.getItem('debugOverlayEnabled') === 'true';
            const newState = !isEnabled;
            
            // Save state to localStorage
            localStorage.setItem('debugOverlayEnabled', newState);
            
            // Update toggle appearance
            updateDebugToggleAppearance(newState);
            
            // Dispatch custom event to toggle visibility
            document.dispatchEvent(new CustomEvent('toggleDebugOverlay'));
            
            // Add a subtle animation effect
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    }
    
    // Helper function to update debug toggle appearance
    function updateDebugToggleAppearance(isEnabled) {
        if (!debugToggle) return;
        
        const toggleThumb = debugToggle.querySelector('.toggle-thumb');
        const toggleTrack = debugToggle.querySelector('.toggle-track');
        
        if (isEnabled) {
            toggleThumb.classList.add('active');
            toggleTrack.classList.add('active');
        } else {
            toggleThumb.classList.remove('active');
            toggleTrack.classList.remove('active');
        }
    }
});
</script>

<!-- Include Notifications Initializer -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to update notification badge
    function updateChatNotificationBadge() {
        const badge = document.getElementById('chatNotificationBadge');
        if (!badge) return;
        
        fetch('src/model/get_unread_messages.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const oldCount = parseInt(badge.textContent) || 0;
                    const newCount = data.count || 0;
                    
                    // Update count and visibility
                    if (newCount > 0) {
                        badge.textContent = newCount > 99 ? '99+' : newCount;
                        badge.style.display = 'flex';
                        
                        // Add pulse animation if count increased
                        if (newCount > oldCount) {
                            badge.classList.add('pulse-animation');
                            setTimeout(() => {
                                badge.classList.remove('pulse-animation');
                            }, 1000);
                        }
                    } else {
                        badge.style.display = 'none';
                    }
                }
            })
            .catch(error => console.error('Error updating chat notification badge:', error));
    }
    
    // Update immediately when page loads
    updateChatNotificationBadge();
    
    // Update every 5 seconds
    setInterval(updateChatNotificationBadge, 5000);
    
    // Make the function available globally for other scripts to call
    window.updateChatNotificationBadge = updateChatNotificationBadge;
});
</script>
