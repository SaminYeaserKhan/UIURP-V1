<?php
session_start();

// Check if user is admin (you may want to implement proper admin authentication)
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

require_once __DIR__ . '/../model/user_preferences.php';
require_once __DIR__ . '/../model/recommendation_engine.php';

$preferences = new UserPreferences();
$recommendationEngine = new RecommendationEngine();

// Handle admin actions
$action = $_GET['action'] ?? '';
$message = '';

switch ($action) {
    case 'clean_old_data':
        $days = intval($_GET['days'] ?? 90);
        $deleted = $preferences->cleanOldData($days);
        $message = "Cleaned $deleted old interaction records (older than $days days)";
        break;
        
    case 'get_user_stats':
        $userId = $_GET['user_id'] ?? '';
        if ($userId) {
            $userProfile = $preferences->getUserProfile($userId);
            $userInteractions = $preferences->getUserInteractions($userId, 50);
            $userStats = [
                'profile' => $userProfile,
                'recent_interactions' => $userInteractions
            ];
        }
        break;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preference System Admin - UIU Research Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #1e293b, #334155);
            color: white;
            padding: 2rem 0;
        }
        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .interaction-list {
            max-height: 400px;
            overflow-y: auto;
        }
        .badge-interaction {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <h1><i class="bi bi-gear"></i> Preference System Administration</h1>
            <p class="mb-0">Manage user preference tracking and recommendations</p>
        </div>
    </div>

    <div class="container mt-4">
        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- System Overview -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="bi bi-people fs-1 text-primary"></i>
                        <h5 class="card-title mt-2">Active Users</h5>
                        <p class="card-text fs-4 fw-bold" id="active-users">Loading...</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="bi bi-activity fs-1 text-success"></i>
                        <h5 class="card-title mt-2">Total Interactions</h5>
                        <p class="card-text fs-4 fw-bold" id="total-interactions">Loading...</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="bi bi-search fs-1 text-info"></i>
                        <h5 class="card-title mt-2">Search Queries</h5>
                        <p class="card-text fs-4 fw-bold" id="search-queries">Loading...</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="bi bi-star fs-1 text-warning"></i>
                        <h5 class="card-title mt-2">Recommendations</h5>
                        <p class="card-text fs-4 fw-bold" id="recommendations">Active</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Actions -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-tools"></i> System Maintenance</h5>
                    </div>
                    <div class="card-body">
                        <h6>Clean Old Data</h6>
                        <p class="text-muted small">Remove interaction data older than specified days</p>
                        <form method="get" class="d-flex gap-2">
                            <input type="hidden" name="action" value="clean_old_data">
                            <input type="number" name="days" value="90" min="1" max="365" class="form-control" style="max-width: 100px;">
                            <button type="submit" class="btn btn-warning">Clean Data</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-person-check"></i> User Analysis</h5>
                    </div>
                    <div class="card-body">
                        <h6>Get User Statistics</h6>
                        <p class="text-muted small">View detailed user preference data</p>
                        <form method="get" class="d-flex gap-2">
                            <input type="hidden" name="action" value="get_user_stats">
                            <input type="text" name="user_id" placeholder="User ID" class="form-control" required>
                            <button type="submit" class="btn btn-info">Analyze</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Statistics -->
        <?php if (isset($userStats)): ?>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-person-badge"></i> User Profile</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($userStats['profile']): ?>
                        <p><strong>User ID:</strong> <?= htmlspecialchars($userStats['profile']['user_id']) ?></p>
                        <p><strong>Activity Score:</strong> <?= $userStats['profile']['activity_score'] ?? 0 ?></p>
                        <p><strong>Profile Created:</strong> 
                            <?php 
                            if (isset($userStats['profile']['created_at'])) {
                                echo $userStats['profile']['created_at']->toDateTime()->format('Y-m-d H:i:s');
                            } else {
                                echo 'Unknown';
                            }
                            ?>
                        </p>
                        
                        <h6 class="mt-3">Interests:</h6>
                        <?php if (isset($userStats['profile']['interests']) && is_array($userStats['profile']['interests'])): ?>
                            <?php foreach ($userStats['profile']['interests'] as $interest => $score): ?>
                            <span class="badge bg-primary me-1"><?= htmlspecialchars($interest) ?> (<?= $score ?>)</span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No interests recorded</p>
                        <?php endif; ?>

                        <h6 class="mt-3">Preferred Categories:</h6>
                        <?php if (isset($userStats['profile']['preferred_categories']) && is_array($userStats['profile']['preferred_categories'])): ?>
                            <?php foreach ($userStats['profile']['preferred_categories'] as $category => $score): ?>
                            <span class="badge bg-success me-1"><?= htmlspecialchars($category) ?> (<?= $score ?>)</span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No category preferences</p>
                        <?php endif; ?>
                        <?php else: ?>
                        <p class="text-warning">User profile not found</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-activity"></i> Recent Interactions</h5>
                    </div>
                    <div class="card-body">
                        <div class="interaction-list">
                            <?php if (!empty($userStats['recent_interactions'])): ?>
                            <?php foreach ($userStats['recent_interactions'] as $interaction): ?>
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <span class="badge badge-interaction bg-<?= $interaction['interaction_type'] === 'view' ? 'secondary' : ($interaction['interaction_type'] === 'click' ? 'primary' : 'info') ?>">
                                        <?= htmlspecialchars($interaction['interaction_type']) ?>
                                    </span>
                                    <span class="ms-2"><?= htmlspecialchars($interaction['item_type']) ?></span>
                                </div>
                                <small class="text-muted">
                                    <?php 
                                    if (isset($interaction['timestamp'])) {
                                        echo $interaction['timestamp']->toDateTime()->format('M j, H:i');
                                    } else {
                                        echo 'Unknown time';
                                    }
                                    ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <p class="text-muted">No recent interactions found</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Popular Content -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-trending-up"></i> Popular Content (This Week)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row" id="popular-content">
                            <div class="col-12 text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading popular content...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load dashboard statistics
        document.addEventListener('DOMContentLoaded', function() {
            // Simulate loading statistics (you can implement actual API calls)
            setTimeout(() => {
                document.getElementById('active-users').textContent = Math.floor(Math.random() * 500) + 100;
                document.getElementById('total-interactions').textContent = (Math.floor(Math.random() * 10000) + 5000).toLocaleString();
                document.getElementById('search-queries').textContent = Math.floor(Math.random() * 1000) + 200;
            }, 1000);

            // Load popular content (simulate)
            setTimeout(() => {
                const popularContent = document.getElementById('popular-content');
                popularContent.innerHTML = `
                    <div class="col-md-3">
                        <h6>Projects</h6>
                        <ul class="list-unstyled">
                            <li><small>AI Research Project</small> <span class="badge bg-primary">42 views</span></li>
                            <li><small>Web Development Study</small> <span class="badge bg-primary">38 views</span></li>
                        </ul>
                    </div>
                    <div class="col-md-3">
                        <h6>Events</h6>
                        <ul class="list-unstyled">
                            <li><small>Tech Conference 2024</small> <span class="badge bg-success">28 views</span></li>
                            <li><small>Research Symposium</small> <span class="badge bg-success">22 views</span></li>
                        </ul>
                    </div>
                    <div class="col-md-3">
                        <h6>Forum Posts</h6>
                        <ul class="list-unstyled">
                            <li><small>Best Practices Discussion</small> <span class="badge bg-info">35 views</span></li>
                            <li><small>Project Collaboration</small> <span class="badge bg-info">31 views</span></li>
                        </ul>
                    </div>
                    <div class="col-md-3">
                        <h6>Faculty</h6>
                        <ul class="list-unstyled">
                            <li><small>Dr. Smith Profile</small> <span class="badge bg-warning">18 views</span></li>
                            <li><small>Prof. Johnson Page</small> <span class="badge bg-warning">15 views</span></li>
                        </ul>
                    </div>
                `;
            }, 1500);
        });
    </script>
</body>
</html> 