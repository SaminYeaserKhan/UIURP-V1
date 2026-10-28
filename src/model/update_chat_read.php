<?php
require_once 'db_connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

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

// Check if project ID is provided
if (!isset($_POST['projectId'])) {
    $response['message'] = 'Project ID is required';
    echo json_encode($response);
    exit;
}

$projectId = $_POST['projectId'];

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

try {
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    
    // Current timestamp
    $currentTime = new UTCDateTime();
    
    // Update or insert read timestamp
    $db->user_chat_read_timestamps->updateOne(
        [
            'userId' => new ObjectId($userId),
            'projectId' => new ObjectId($projectId)
        ],
        [
            '$set' => ['timestamp' => $currentTime]
        ],
        ['upsert' => true]
    );
    
    $response['success'] = true;
    $response['message'] = 'Read timestamp updated';
    
} catch (Exception $e) {
    $response['message'] = 'Error updating read timestamp: ' . $e->getMessage();
}

echo json_encode($response);
?> 