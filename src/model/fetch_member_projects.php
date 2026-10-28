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
    $projectsCollection = $db->projectsV2; // Updated to use projectsV2 collection
    
    // Get current user ID
    $userId = $_SESSION['user_id'];
    
    // Log debug info
    error_log("fetch_member_projects.php - User ID: " . $userId);
    
    // Fetch projects where the user is a member
    // Since many imported projects don't have createdBy field, we'll focus on finding member projects
    // and then filter out any where the user might be the creator
    $filter = [
        '$or' => [
            ['members' => ['$elemMatch' => ['userId' => $userId]]],  // String format
            ['members' => ['$elemMatch' => ['userId.$oid' => $userId]]],  // MongoDB ObjectId format
        ]
    ];
    
    error_log("fetch_member_projects.php - Filter: " . json_encode($filter));
    
    $options = [
        'sort' => ['updatedAt' => -1], // Updated to use updatedAt for consistency
        'limit' => 20 // Increased limit since we'll filter in PHP
    ];
    
    $projects = $projectsCollection->find($filter, $options);
    
    $projectsArray = [];
    foreach ($projects as $project) {
        // Check if user is the creator (if createdBy field exists)
        $isCreator = false;
        if (isset($project['createdBy'])) {
            if (is_array($project['createdBy']) && isset($project['createdBy']['$oid'])) {
                $isCreator = ($project['createdBy']['$oid'] === $userId);
            } else {
                $isCreator = ((string)$project['createdBy'] === $userId);
            }
        }
        
        // Only include if user is not the creator (or if createdBy field doesn't exist)
        if (!$isCreator) {
            // Add a flag to indicate user role
            $projectArray = (array) $project;
            $projectArray['userRole'] = 'member';
            $projectsArray[] = $projectArray;
        }
    }
    
    // Limit to 10 results after filtering
    $projectsArray = array_slice($projectsArray, 0, 10);
    
    error_log("fetch_member_projects.php - Found " . count($projectsArray) . " member projects");
    
    // Return as JSON
    header('Content-Type: application/json');
    echo json_encode($projectsArray);
    
} catch (Exception $e) {
    error_log("fetch_member_projects.php - Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch member projects', 'details' => $e->getMessage()]);
} 