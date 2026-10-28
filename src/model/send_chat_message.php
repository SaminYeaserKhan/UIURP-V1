<?php
require_once 'db_connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once 'ChatEncryption.php';
require_once 'ProjectKeyManager.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

// Start session to capture user data
session_start();

// Initialize response
$response = [
    'success' => false,
    'messageId' => null,
    'message' => ''
];

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user_data'])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit;
}

// Check if required data is provided
if (!isset($_POST['projectId']) || !isset($_POST['message']) || trim($_POST['message']) === '') {
    $response['message'] = 'Missing required fields';
    echo json_encode($response);
    exit;
}

$projectId = $_POST['projectId'];
$messageText = trim($_POST['message']);
$userType = isset($_POST['userType']) ? $_POST['userType'] : null;
$userName = isset($_POST['userName']) ? $_POST['userName'] : null;

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
    
    // If userName and userType aren't provided, try to get them from session or database
    if (!$userName || !$userType) {
        // Check if user is faculty
        $faculty = $db->faculties->findOne(['_id' => new ObjectId($userId)]);
        if ($faculty) {
            $userType = 'faculty';
            if (!$userName) {
                $userName = $faculty['name'] ?? $faculty['full_name'] ?? 'Faculty User';
            }
        } else {
            // Check if user is student
            $student = $db->students->findOne(['_id' => new ObjectId($userId)]);
            if ($student) {
                $userType = 'student';
                if (!$userName) {
                    // Check all possible locations for student name
                    $possibleNameFields = [
                        $student['name'] ?? null,
                        $student['full_name'] ?? null,
                        $student['basic_info']['name'] ?? null,
                        $student['basic_info']['full_name'] ?? null
                    ];
                    
                    foreach ($possibleNameFields as $nameField) {
                        if (!empty($nameField) && is_string($nameField)) {
                            $userName = $nameField;
                            break;
                        }
                    }
                    
                    // If no name found in any field, use default
                    if (empty($userName)) {
                        $userName = 'Student User';
                    }
                }
            } else {
                $userType = 'unknown';
                if (!$userName) {
                    $userName = 'Unknown User';
                }
            }
        }
    }
    
    // Current UTC timestamp
    $currentTime = new UTCDateTime();
    
    // Check if project has encryption enabled
    $project = $db->projectsV2->findOne(
        ['_id' => new ObjectId($projectId)],
        ['projection' => ['encryptionEnabled' => 1]]
    );
    
    $isEncrypted = false;
    $encryptedData = null;
    
    // Encrypt message if project has encryption enabled
    if ($project && isset($project['encryptionEnabled']) && $project['encryptionEnabled']) {
        try {
            // Get project encryption key
            $keyResult = ProjectKeyManager::getProjectKey($projectId, $userId, $db);
            if ($keyResult['success']) {
                $encryptedData = ChatEncryption::encryptMessage($messageText, $keyResult['key']);
                $isEncrypted = true;
            } else {
                // If key access fails, log error but continue with unencrypted message
                error_log("Failed to get project key for encryption: " . $keyResult['message']);
            }
        } catch (Exception $e) {
            // If encryption fails, log error but continue with unencrypted message
            error_log("Message encryption failed: " . $e->getMessage());
        }
    }
    
    // Create message document
    $messageDoc = [
        'projectId' => new ObjectId($projectId),
        'sender' => [
            'userId' => new ObjectId($userId),
            'name' => $userName,
            'userType' => $userType
        ],
        'message' => $isEncrypted ? $encryptedData['ciphertext'] : $messageText,
        'timestamp' => $currentTime,
        'isSystemMessage' => false,
        'encrypted' => $isEncrypted
    ];
    
    // Add encryption metadata if message is encrypted
    if ($isEncrypted && $encryptedData) {
        $messageDoc['iv'] = $encryptedData['iv'];
        $messageDoc['tag'] = $encryptedData['tag'];
    }
    
    // Insert message
    $result = $db->project_chat_messages->insertOne($messageDoc);
    
    // Update project's lastUpdated timestamp
    $db->projectsV2->updateOne(
        ['_id' => new ObjectId($projectId)],
        ['$set' => ['updatedAt' => $currentTime]]
    );
    
    if ($result->getInsertedCount()) {
        $response['success'] = true;
        $response['message'] = 'Message sent successfully';
        $response['messageId'] = (string)$result->getInsertedId();
        
        // Mark message as read for the sender
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
    } else {
        $response['message'] = 'Failed to send message';
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?> 