<?php
require_once 'db_connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once 'ProjectKeyManager.php';
require_once 'ChatEncryption.php';

use MongoDB\BSON\ObjectId;

// Start session to capture user data
session_start();

// Initialize response
$response = [
    'success' => false,
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
if (!isset($_POST['projectId'])) {
    $response['message'] = 'Project ID is required';
    echo json_encode($response);
    exit;
}

$projectId = $_POST['projectId'];
$action = $_POST['action'] ?? 'status';

try {
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    
    switch ($action) {
        case 'enable':
            // Enable encryption for project
            $result = ProjectKeyManager::initializeProjectEncryption($projectId, $db);
            $response = $result;
            break;
            
        case 'disable':
            // Disable encryption for project
            $result = ProjectKeyManager::disableProjectEncryption($projectId, $userId, $db);
            $response = $result;
            break;
            
        case 'rotate':
            // Rotate encryption key
            $result = ProjectKeyManager::rotateProjectKey($projectId, $userId, $db);
            $response = $result;
            break;
            
        case 'migrate':
            // Migrate existing messages to encrypted format
            $migratedCount = ChatEncryption::migrateMessagesToEncrypted($projectId, $db);
            $response = [
                'success' => true,
                'message' => "Migrated $migratedCount messages to encrypted format"
            ];
            break;
            
        case 'status':
        default:
            // Get encryption status
            $result = ProjectKeyManager::getEncryptionStatus($projectId, $db);
            $response = $result;
            break;
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error managing project encryption: ' . $e->getMessage();
    error_log("manage_project_encryption.php error: " . $e->getMessage());
}

echo json_encode($response);
?>
