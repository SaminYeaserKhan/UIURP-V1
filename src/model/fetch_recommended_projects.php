<?php
session_start();
header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/recommendation_engine.php';
require_once __DIR__ . '/user_preferences.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        // Return fallback projects for non-logged-in users
        require_once __DIR__ . '/fetch_projects.php';
        exit();
    }

    $userId = $_SESSION['user_id'];
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

    // Initialize recommendation engine
    $recommendationEngine = new RecommendationEngine();
    
    // Get personalized project recommendations
    $projects = $recommendationEngine->getRecommendedProjects($userId, $limit);
    
    // Format projects for frontend consumption
    $formattedProjects = [];
    foreach ($projects as $project) {
        $formattedProject = [
            '_id' => $project['_id'],
            'title' => $project['title'] ?? 'Untitled Project',
            'description' => $project['description'] ?? '',
            'abstract' => $project['abstract'] ?? $project['description'] ?? '',
            'createdAt' => $project['createdAt'] ?? null,
            'members' => $project['members'] ?? [],
            'supervisor' => $project['supervisor'] ?? 'Not specified',
            'privacy' => $project['privacy'] ?? 0,
            'field' => $project['field'] ?? 'Research',
            'category' => $project['category'] ?? 'Research',
            'keywords' => $project['keywords'] ?? $project['tags'] ?? [],
            'coverImage' => $project['coverImage'] ?? null,
            'relevance_score' => $project['relevance_score'] ?? 0,
            'is_recommended' => isset($project['relevance_score']) && $project['relevance_score'] > 0
        ];
        $formattedProjects[] = $formattedProject;
    }
    
    // Check if we got personalized results
    $hasPersonalizedResults = false;
    foreach ($formattedProjects as $project) {
        if ($project['relevance_score'] > 0) {
            $hasPersonalizedResults = true;
            break;
        }
    }
    
    // Add metadata for frontend
    $response = [
        'projects' => $formattedProjects,
        'is_personalized' => $hasPersonalizedResults,
        'total_count' => count($formattedProjects),
        'user_id' => $userId
    ];
    
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in fetch_recommended_projects.php: " . $e->getMessage());
    
    // Fallback to regular projects on error
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch personalized recommendations',
        'projects' => [],
        'is_personalized' => false,
        'fallback' => true
    ]);
}
?> 