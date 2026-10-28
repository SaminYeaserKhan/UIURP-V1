<?php
require_once 'db_connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once 'ProjectKeyManager.php';

use MongoDB\BSON\ObjectId;

// Start session to capture user data
session_start();

// Initialize response
$response = [
    'success' => false,
    'key' => null,
    'message' => ''
];

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user_data'])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit;
}

// Set user ID
$userId = null;
if (isset($_SESSION['user_id'])) {
    // Convert MongoDB ObjectId to string if needed
    if (is_object($_SESSION['user_id']) && get_class($_SESSION['user_id']) === 'MongoDB\BSON\ObjectId') {
        $userId = (string)$_SESSION['user_id'];
    } else {
        $userId = $_SESSION['user_id'];
    }
} elseif (isset($_SESSION['user_data']) && isset($_SESSION['user_data']['_id']) && isset($_SESSION['user_data']['_id']['$oid'])) {
    $userId = $_SESSION['user_data']['_id']['$oid'];
} elseif (isset($_SESSION['user_data']) && isset($_SESSION['user_data']['_id'])) {
    $userId = (string)$_SESSION['user_data']['_id'];
}

// Check if project ID is provided
if (!isset($_GET['projectId'])) {
    $response['message'] = 'Project ID is required';
    echo json_encode($response);
    exit;
}

$projectId = $_GET['projectId'];

try {
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    
    // Get project encryption key
    $keyResult = ProjectKeyManager::getProjectKey($projectId, $userId, $db);
    
    if ($keyResult['success']) {
        $response['success'] = true;
        $response['key'] = $keyResult['key'];
        $response['message'] = 'Key retrieved successfully';
    } else {
        $response['message'] = $keyResult['message'];
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error retrieving project key: ' . $e->getMessage();
    error_log("get_project_encryption_key.php error: " . $e->getMessage());
}

echo json_encode($response);
?>
