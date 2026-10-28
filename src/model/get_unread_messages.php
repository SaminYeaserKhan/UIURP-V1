<?php
require_once 'db_connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

// Start session to capture user data
session_start();

// Initialize response
$response = [
    'success' => false,
    'count' => 0,
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

try {
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    
    // Get projects the user is part of
    $projectCollection = $db->projectsV2;
    $projectFilter = [
        '$or' => [
            ['createdBy' => $userId],
            ['createdBy.$oid' => $userId],
            ['members' => ['$elemMatch' => ['userId' => $userId]]],
            ['members' => ['$elemMatch' => ['userId.$oid' => $userId]]],
            ['members.userId' => $userId],
            ['members.userId.$oid' => $userId],
            ['supervisor.userId.$oid' => $userId],
            ['supervisor.userId' => $userId]
        ]
    ];
    
    $projects = $projectCollection->find($projectFilter, ['projection' => ['_id' => 1]]);
    $projectIds = [];
    
    foreach ($projects as $project) {
        $projectIds[] = $project['_id'];
    }
    
    // Get last read timestamp for each project
    $userReadCollection = $db->user_chat_read_timestamps;
    $unreadCount = 0;
    
    foreach ($projectIds as $projectId) {
        // Get last read timestamp by this user for this project
        $readRecord = $userReadCollection->findOne([
            'userId' => new ObjectId($userId),
            'projectId' => $projectId
        ]);
        
        $lastReadTimestamp = null;
        if ($readRecord && isset($readRecord['timestamp'])) {
            $lastReadTimestamp = $readRecord['timestamp'];
        }
        
        // Count messages newer than last read
        $messageFilter = ['projectId' => $projectId];
        if ($lastReadTimestamp) {
            $messageFilter['timestamp'] = ['$gt' => $lastReadTimestamp];
        }
        
        // Don't count user's own messages
        $messageFilter['sender.userId'] = ['$ne' => new ObjectId($userId)];
        
        $count = $db->project_chat_messages->count($messageFilter);
        $unreadCount += $count;
    }
    
    $response['success'] = true;
    $response['count'] = $unreadCount;
    
} catch (Exception $e) {
    $response['message'] = 'Error getting unread count: ' . $e->getMessage();
}

echo json_encode($response);
?> 