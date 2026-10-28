<?php
session_start();
require_once __DIR__ . '/src/includes/admin_middleware.php';

// Check if user is admin
requireAdmin();

// Include navbar
include __DIR__ . '/src/includes/navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - UIURP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/styles/admin-dashboard.css" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <!-- Admin Header -->
        <div class="admin-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="admin-title">
                            <i class="bi bi-shield-check"></i>
                            Admin Dashboard
                        </h1>
                        <p class="admin-subtitle">Manage users, content, and system settings</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="admin-stats-summary">
                            <div class="stat-item">
                                <span class="stat-number" id="totalUsers">-</span>
                                <span class="stat-label">Total Users</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number" id="totalProjects">-</span>
                                <span class="stat-label">Projects</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number" id="totalAdmins">-</span>
                                <span class="stat-label">Admins</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Navigation Tabs -->
        <div class="admin-nav">
            <div class="container-fluid">
                <ul class="nav nav-pills admin-nav-pills" id="adminTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="overview-tab" data-bs-toggle="pill" data-bs-target="#overview" type="button" role="tab">
                            <i class="bi bi-speedometer2"></i> Overview
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="users-tab" data-bs-toggle="pill" data-bs-target="#users" type="button" role="tab">
                            <i class="bi bi-people"></i> User Management
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="content-tab" data-bs-toggle="pill" data-bs-target="#content" type="button" role="tab">
                            <i class="bi bi-folder"></i> Content Management
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="settings-tab" data-bs-toggle="pill" data-bs-target="#settings" type="button" role="tab">
                            <i class="bi bi-gear"></i> Settings
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Admin Content -->
        <div class="admin-content">
            <div class="container-fluid">
                <div class="tab-content" id="adminTabContent">
                    <!-- Overview Tab -->
                    <div class="tab-pane fade show active" id="overview" role="tabpanel">
                        <div class="row">
                            <!-- Statistics Cards -->
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="bi bi-people-fill"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 id="statTotalUsers">-</h3>
                                        <p>Total Users</p>
                                        <small id="statUserBreakdown">-</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="bi bi-folder-fill"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 id="statTotalProjects">-</h3>
                                        <p>Total Projects</p>
                                        <small id="statProjectBreakdown">-</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="bi bi-calendar-event-fill"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 id="statTotalEvents">-</h3>
                                        <p>Events</p>
                                        <small>Total events created</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="bi bi-chat-square-text-fill"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 id="statTotalPosts">-</h3>
                                        <p>Forum Posts</p>
                                        <small>Total discussions</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="admin-card">
                                    <div class="card-header">
                                        <h5><i class="bi bi-clock-history"></i> Recent Activity</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="recentActivityList">
                                            <div class="text-center py-4">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="admin-card">
                                    <div class="card-header">
                                        <h5><i class="bi bi-shield-check"></i> Admin Actions</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="quick-actions">
                                            <button class="btn btn-primary w-100 mb-2" onclick="switchTab('users')">
                                                <i class="bi bi-person-plus"></i> Add Admin
                                            </button>
                                            <button class="btn btn-outline-primary w-100 mb-2" onclick="switchTab('content')">
                                                <i class="bi bi-folder-plus"></i> Manage Content
                                            </button>
                                            <button class="btn btn-outline-secondary w-100" onclick="refreshStatistics()">
                                                <i class="bi bi-arrow-clockwise"></i> Refresh Data
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Management Tab -->
                    <div class="tab-pane fade" id="users" role="tabpanel">
                        <div class="admin-card">
                            <div class="card-header">
                                <h5><i class="bi bi-people"></i> User Management</h5>
                                <div class="header-actions">
                                    <div class="search-box">
                                        <input type="text" class="form-control" id="userSearch" placeholder="Search users...">
                                        <i class="bi bi-search"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="user-stats">
                                            <span class="stat-item">
                                                <i class="bi bi-people-fill text-primary"></i>
                                                <span id="totalUsersCount">-</span> Total Users
                                            </span>
                                            <span class="stat-item">
                                                <i class="bi bi-shield-check text-warning"></i>
                                                <span id="adminUsersCount">-</span> Admins
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <button class="btn btn-outline-primary" onclick="adminDashboard.refreshUsers()">
                                            <i class="bi bi-arrow-clockwise"></i> Refresh
                                        </button>
                                    </div>
                                </div>
                                <div id="usersList">
                                    <div class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Management Tab -->
                    <div class="tab-pane fade" id="content" role="tabpanel">
                        <div class="admin-card">
                            <div class="card-header">
                                <h5><i class="bi bi-folder"></i> Content Management</h5>
                                <div class="header-actions">
                                    <select class="form-select" id="contentTypeSelect">
                                        <option value="projects">Projects</option>
                                        <option value="events">Events</option>
                                    </select>
                                    <div class="search-box">
                                        <input type="text" class="form-control" id="contentSearch" placeholder="Search content...">
                                        <i class="bi bi-search"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="contentList">
                                    <div class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Settings Tab -->
                    <div class="tab-pane fade" id="settings" role="tabpanel">
                        <div class="admin-card">
                            <div class="card-header">
                                <h5><i class="bi bi-gear"></i> System Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>System Information</h6>
                                        <p><strong>Version:</strong> UIURP v2.0</p>
                                        <p><strong>Database:</strong> MongoDB</p>
                                        <p><strong>Last Updated:</strong> <?= date('Y-m-d H:i:s') ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Quick Actions</h6>
                                        <button class="btn btn-outline-primary w-100 mb-2" onclick="refreshStatistics()">
                                            <i class="bi bi-arrow-clockwise"></i> Refresh Statistics
                                        </button>
                                        <button class="btn btn-outline-secondary w-100" onclick="exportData()">
                                            <i class="bi bi-download"></i> Export Data
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmTitle">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="confirmBody">
                    Are you sure you want to perform this action?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmAction">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="adminToast" class="toast" role="alert">
            <div class="toast-header">
                <i class="bi bi-shield-check text-primary me-2"></i>
                <strong class="me-auto">Admin Dashboard</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" id="toastMessage">
                <!-- Toast message will be inserted here -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin-dashboard.js"></script>
</body>
</html>
