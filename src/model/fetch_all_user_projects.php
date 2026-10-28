<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit();
}

// Include MongoDB connection
require __DIR__ . '/../../vendor/autoload.php';

try {
    // Connect to MongoDB
    $client = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
    $db = $client->uiurp;
    $projectsCollection = $db->projectsV2;
    
    // Get current user ID
    $userId = $_SESSION['user_id'];
    
    // Convert MongoDB ObjectId to string if needed
    if (is_object($userId) && get_class($userId) === 'MongoDB\BSON\ObjectId') {
        $userId = (string)$userId;
    }
    
    // Fetch all projects where the user is involved (either as creator or member)
    // Note: Many imported projects don't have createdBy field, so we focus on members array and supervisor
    $filter = [
        '$or' => [
            ['createdBy' => $userId],  // Direct creator ID field (for new projects)
            ['createdBy.$oid' => $userId],  // MongoDB ObjectId format (for new projects)
            ['members' => ['$elemMatch' => ['userId' => $userId]]],  // String format
            ['members' => ['$elemMatch' => ['userId.$oid' => $userId]]],  // MongoDB ObjectId format
            ['members.userId' => $userId],  // Direct string match in members array
            ['members.userId.$oid' => $userId],  // Direct ObjectId match in members array
            ['supervisor.userId.$oid' => $userId],  // MongoDB ObjectId format
            ['supervisor.userId' => $userId]  // String format
        ]
    ];
    
    $options = [
        'sort' => ['updatedAt' => -1]
        // Remove limit to get all projects first, then we'll randomly select 3
    ];
    
    $projects = $projectsCollection->find($filter, $options);
    
    $projectsArray = [];
    foreach ($projects as $project) {
        // Determine if user is owner or member
        $isOwner = false;
        
        // Check createdBy field (if it exists)
        if (isset($project['createdBy'])) {
            if (is_array($project['createdBy']) && isset($project['createdBy']['$oid'])) {
                $isOwner = ($project['createdBy']['$oid'] === $userId);
            } else {
                $isOwner = ((string)$project['createdBy'] === $userId);
            }
        }
        
        // Check supervisor field
        if (!$isOwner && isset($project['supervisor']['userId'])) {
            if (is_array($project['supervisor']['userId']) && isset($project['supervisor']['userId']['$oid'])) {
                $isOwner = ($project['supervisor']['userId']['$oid'] === $userId);
            } else {
                $isOwner = ((string)$project['supervisor']['userId'] === $userId);
            }
        }
        
        $projectArray = (array) $project;
        $projectArray['userRole'] = $isOwner ? 'owner' : 'member';
        $projectsArray[] = $projectArray;
    }
    
    // Optional: Limit projects display - currently disabled to show all projects
    /*
    // Sort by most recent and select 6 projects if we have more than 6
    if (count($projectsArray) > 6) {
        $originalCount = count($projectsArray);
        // Sort by updatedAt (most recent first) and take the first 6 elements
        usort($projectsArray, function($a, $b) {
            $dateA = $a['updatedAt'] ?? $a['createdAt'] ?? 0;
            $dateB = $b['updatedAt'] ?? $b['createdAt'] ?? 0;
            
            // Handle MongoDB date format
            if (is_array($dateA) && isset($dateA['$date'])) {
                $dateA = is_array($dateA['$date']) && isset($dateA['$date']['$numberLong']) 
                    ? intval($dateA['$date']['$numberLong']) / 1000 
                    : strtotime($dateA['$date']);
            }
            if (is_array($dateB) && isset($dateB['$date'])) {
                $dateB = is_array($dateB['$date']) && isset($dateB['$date']['$numberLong']) 
                    ? intval($dateB['$date']['$numberLong']) / 1000 
                    : strtotime($dateB['$date']);
            }
            
            return $dateB <=> $dateA; // Descending order (newest first)
        });
        $projectsArray = array_slice($projectsArray, 0, 6);
    }
    */
    
    // Return as JSON
    header('Content-Type: application/json');
    echo json_encode($projectsArray);
    
} catch (Exception $e) {
    error_log("fetch_all_user_projects.php - Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch projects', 'details' => $e->getMessage()]);
} 