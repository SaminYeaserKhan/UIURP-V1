<?php
// CORS headers for cross-origin requests
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

require_once 'db_connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

// Comment out session-related code
// session_start();
// $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Check if project ID is provided
if (!isset($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Project ID not provided'
    ]);
    exit;
}

$projectId = $_GET['id'];

try {
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    
    // Try projectsV2 collection first, then fall back to projects if needed
    $project = null;
    $collections = ['projectsV2', 'projects'];
    
    foreach ($collections as $collectionName) {
        $collection = $db->$collectionName;
        
        // Find the project by ID without any permission checks
        $filter = ['_id' => new ObjectId($projectId)];
        
        // Find the project with all fields
        $options = [
            'projection' => [
                'title' => 1,
                'abstract' => 1,
                'description' => 1,
                'coverImage' => 1,
                'field' => 1,
                'keywords' => 1,
                'institution' => 1,
                'createdAt' => 1,
                'updatedAt' => 1,
                'estimatedCompletionDate' => 1,
                'privacy' => 1,
                'members' => 1,
                'timeline' => 1,
                'supervisor' => 1,
                'links' => 1,
                'files' => 1,
                'media' => 1,
                'references' => 1,
                'stats' => 1,
                'comments' => 1
            ]
        ];
        
        $project = $collection->findOne($filter, $options);
        
        if ($project) {
            // Initialize stats if they don't exist
            if (!isset($project['stats']) || !is_array($project['stats'])) {
                $project['stats'] = ['views' => 1, 'downloads' => 0, 'favorites' => 0];
                
                // Update the project with initial stats
                $collection->updateOne(
                    ['_id' => new ObjectId($projectId)],
                    ['$set' => ['stats' => $project['stats']]]
                );
            } else {
                // Increment view count if stats field exists
                $collection->updateOne(
                    ['_id' => new ObjectId($projectId)],
                    ['$inc' => ['stats.views' => 1]]
                );
                // Update the local project object to reflect the incremented view count
                $project['stats']['views'] = intval($project['stats']['views']) + 1;
            }
            
            // Convert MongoDB UTCDateTime objects to proper format for JavaScript
            if (isset($project['createdAt']) && is_object($project['createdAt'])) {
                if (method_exists($project['createdAt'], 'toDateTime')) {
                    $project['createdAt'] = ['$date' => $project['createdAt']->toDateTime()->format('c')];
                }
            }
            
            if (isset($project['updatedAt']) && is_object($project['updatedAt'])) {
                if (method_exists($project['updatedAt'], 'toDateTime')) {
                    $project['updatedAt'] = ['$date' => $project['updatedAt']->toDateTime()->format('c')];
                }
            }
            
            if (isset($project['estimatedCompletionDate']) && is_object($project['estimatedCompletionDate'])) {
                if (method_exists($project['estimatedCompletionDate'], 'toDateTime')) {
                    $project['estimatedCompletionDate'] = ['$date' => $project['estimatedCompletionDate']->toDateTime()->format('c')];
                }
            }
            
            // Project found, no need to check other collections
            break;
        }
    }
    
    if ($project) {
        echo json_encode([
            'success' => true,
            'project' => $project
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Project not found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving project: ' . $e->getMessage()
    ]);
}
?> 