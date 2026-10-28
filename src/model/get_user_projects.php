<?php
require_once 'db_connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

// Start session to capture user data if available
session_start();

// Set user ID and get user information (null if not logged in)
$userId = null;
$userName = null;
$userType = null;
$debugInfo = ['session_data' => []];

// Try multiple methods to get user ID
if (isset($_SESSION['user_id'])) {
    // Convert MongoDB ObjectId to string if needed
    if (is_object($_SESSION['user_id']) && get_class($_SESSION['user_id']) === 'MongoDB\BSON\ObjectId') {
        $userId = (string)$_SESSION['user_id'];
    } else {
        $userId = $_SESSION['user_id'];
    }
    $debugInfo['session_data']['user_id_method'] = 'direct_user_id';
    $debugInfo['session_data']['user_id'] = $userId;
} elseif (isset($_SESSION['user_data']) && isset($_SESSION['user_data']['_id']) && isset($_SESSION['user_data']['_id']['$oid'])) {
    // Try to get ID from user_data if available
    $userId = $_SESSION['user_data']['_id']['$oid'];
    $debugInfo['session_data']['user_id_method'] = 'user_data_oid';
    $debugInfo['session_data']['user_id'] = $userId;
} elseif (isset($_SESSION['user_data']) && isset($_SESSION['user_data']['_id'])) {
    // Fall back to string representation of ID if present
    $userId = (string)$_SESSION['user_data']['_id'];
    $debugInfo['session_data']['user_id_method'] = 'user_data_string';
    $debugInfo['session_data']['user_id'] = $userId;
}

// Get user type and name for additional matching
if (isset($_SESSION['user_type'])) {
    $userType = $_SESSION['user_type'];
    $debugInfo['session_data']['user_type'] = $userType;
}

if (isset($_SESSION['username'])) {
    $userName = $_SESSION['username'];
    $debugInfo['session_data']['username'] = $userName;
}

// Store session debug info
$debugInfo['session_data']['logged_in'] = isset($_SESSION['logged_in']) ? $_SESSION['logged_in'] : false;
$debugInfo['session_data']['has_user_data'] = isset($_SESSION['user_data']);

$isLoggedIn = ($userId !== null);

try {
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    $collection = $db->projectsV2;
    
    // Define query based on user login status
    if ($isLoggedIn) {
        // For logged-in users, show only their projects
        // Note: Many imported projects don't have createdBy field, so we focus on members array and supervisor
        $orConditions = [
            // Creator matches
            ['createdBy' => $userId],  // Direct creator ID field (for new projects)
            ['createdBy.$oid' => $userId],  // MongoDB ObjectId format (for new projects)
            
            // Team member matches
            ['members' => ['$elemMatch' => ['userId' => $userId]]],  // String format
            ['members' => ['$elemMatch' => ['userId.$oid' => $userId]]],  // MongoDB ObjectId format
            ['members.userId' => $userId],  // Direct string match in members array
            ['members.userId.$oid' => $userId],  // Direct ObjectId match in members array
            
            // Supervisor matches - comprehensive patterns
            ['supervisor.userId.$oid' => $userId],  // MongoDB ObjectId format
            ['supervisor.userId' => $userId],  // String format
            ['supervisor' => $userId],  // Direct supervisor field (for simple projects)
            ['supervisor.$oid' => $userId],  // Direct supervisor ObjectId format
        ];
        
        // Add name-based matching for faculty users
        if ($userType === 'faculty' && $userName) {
            $orConditions[] = ['supervisor.name' => $userName];  // Exact name match
            $orConditions[] = ['supervisor' => $userName];  // Direct supervisor name
            $orConditions[] = ['supervisor' => ['$regex' => preg_quote($userName), '$options' => 'i']];  // Case-insensitive name match
        }
        
        $filter = ['$or' => $orConditions];
        
        $debugInfo['filter'] = $filter;
        $debugInfo['userId'] = $userId;
        $debugInfo['userName'] = $userName;
        $debugInfo['userType'] = $userType;
        
        if ($userId === 'admin' || (isset($_SESSION['user_data']['role']) && $_SESSION['user_data']['role'] === 'admin')) {
            // If user is admin, no filter (show all projects)
            $filter = [];
            $debugInfo['admin_override'] = true;
        }
    } else {
        // For non-logged in users, show recent public projects
        $filter = ['privacy' => 0]; // Only public projects
        $debugInfo['public_only'] = true;
    }
    
    $options = [
        'sort' => ['updatedAt' => -1],
        'limit' => 50  // Limit results to prevent excessive loading
    ];
    
    $cursor = $collection->find($filter, $options);
    $projects = [];
    
    foreach ($cursor as $project) {
        $projects[] = $project;
    }
    
    $debugInfo['project_count'] = count($projects);
    $debugInfo['filter_used'] = $filter;
    
    echo json_encode([
        'success' => true,
        'projects' => $projects,
        'isLoggedIn' => $isLoggedIn,
        'debug' => $debugInfo
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving projects: ' . $e->getMessage()
    ]);
}
?> 