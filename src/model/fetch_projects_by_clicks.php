<?php
require_once 'db_connect.php';
require_once 'user_preferences.php';
session_start();

header('Content-Type: application/json');

try {
    // Get user ID from session
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        // If no user is logged in, return regular project list
        $client = connectToDatabase();
        $collection = $client->uiurp->projectsV2;
        $cursor = $collection->find(
            ['privacy' => 0],
            ['sort' => ['stats.views' => -1]]
        );
        echo json_encode(iterator_to_array($cursor));
        exit;
    }

    // Get user preferences
    $preferences = new UserPreferences();
    
    // Get user's project interactions
    $interactions = $preferences->getUserInteractions($userId);
    
    // Count clicks per project
    $projectClicks = [];
    foreach ($interactions as $interaction) {
        if ($interaction['interaction_type'] === 'click' && $interaction['item_type'] === 'project') {
            $projectId = $interaction['item_id'];
            if (!isset($projectClicks[$projectId])) {
                $projectClicks[$projectId] = 0;
            }
            $projectClicks[$projectId]++;
        }
    }

    // Connect to MongoDB
    $client = connectToDatabase();
    $collection = $client->uiurp->projectsV2;

    // Get all public projects
    $cursor = $collection->find(['privacy' => 0]);
    $projects = iterator_to_array($cursor);

    // Sort projects based on user clicks
    usort($projects, function($a, $b) use ($projectClicks) {
        $aId = (string)$a['_id'];
        $bId = (string)$b['_id'];
        
        $aClicks = $projectClicks[$aId] ?? 0;
        $bClicks = $projectClicks[$bId] ?? 0;
        
        if ($aClicks === $bClicks) {
            // If clicks are equal, sort by views
            return ($b['stats']['views'] ?? 0) - ($a['stats']['views'] ?? 0);
        }
        
        return $bClicks - $aClicks;
    });

    echo json_encode($projects);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 