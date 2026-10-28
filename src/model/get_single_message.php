<?php
require_once 'db_connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once 'ChatEncryption.php';
require_once 'ProjectKeyManager.php';

use MongoDB\BSON\ObjectId;

// Start session to capture user data
session_start();

// Initialize response
$response = [
    'success' => false,
    'message' => null,
    'error' => ''
];

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user_data'])) {
    $response['error'] = 'User not logged in';
    echo json_encode($response);
    exit;
}

// Check if message ID is provided
if (!isset($_GET['id'])) {
    $response['error'] = 'Message ID is required';
    echo json_encode($response);
    exit;
}

// Set variables
$messageId = $_GET['id'];
$projectId = isset($_GET['projectId']) ? $_GET['projectId'] : null;

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
    $collection = $db->project_chat_messages;
    
    // Prepare filter
    $filter = ['_id' => new ObjectId($messageId)];
    if ($projectId) {
        $filter['projectId'] = new ObjectId($projectId);
    }
    
    // Execute query
    $document = $collection->findOne($filter);
    
    if ($document) {
        $senderId = null;
        $userType = null;
        
        if (isset($document['sender']['userId'])) {
            if (is_object($document['sender']['userId'])) {
                $senderId = (string)$document['sender']['userId'];
            } elseif (isset($document['sender']['userId']['$oid'])) {
                $senderId = $document['sender']['userId']['$oid'];
            } else {
                $senderId = (string)$document['sender']['userId'];
            }
            
            $userType = $document['sender']['userType'] ?? null;
        }
        
        $profileImage = 'assets/resources/user_avatar.png'; // Default image
        
        // Fetch user profile data
        if ($senderId && !$document['isSystemMessage']) {
            if ($userType === 'faculty') {
                $faculty = $db->faculties->findOne(['_id' => new ObjectId($senderId)]);
                if ($faculty) {
                    $profileImage = $faculty['profile_picture'] ?? $faculty['profilePicture'] ?? $faculty['profile_image'] ?? $profileImage;
                }
            } elseif ($userType === 'student') {
                $student = $db->students->findOne(['_id' => new ObjectId($senderId)]);
                if ($student) {
                    // Try multiple possible locations for profile image
                    $possibleImagePaths = [
                        $student['basic_info']['profile_image_url'] ?? null,
                        $student['profile_image'] ?? null,
                        $student['profile_image_url'] ?? null,
                        $student['basic_info']['profile_image'] ?? null
                    ];
                    
                    foreach ($possibleImagePaths as $imagePath) {
                        if (!empty($imagePath) && is_string($imagePath) && trim($imagePath) !== '') {
                            $profileImage = $imagePath;
                            break;
                        }
                    }
                }
            }
        }
        
        // Get project encryption key if needed
        $projectKey = null;
        if ($projectId) {
            $project = $db->projectsV2->findOne(
                ['_id' => new ObjectId($projectId)],
                ['projection' => ['encryptionEnabled' => 1]]
            );
            
            if ($project && isset($project['encryptionEnabled']) && $project['encryptionEnabled']) {
                $keyResult = ProjectKeyManager::getProjectKey($projectId, $userId, $db);
                if ($keyResult['success']) {
                    $projectKey = $keyResult['key'];
                }
            }
        }
        
        // Decrypt message if it's encrypted
        $messageText = $document['message'];
        $isEncrypted = false;
        
        if (ChatEncryption::isEncrypted($document) && $projectKey) {
            try {
                $encryptedData = [
                    'message' => $document['message'],
                    'iv' => $document['iv'] ?? '',
                    'tag' => $document['tag'] ?? ''
                ];
                $messageText = ChatEncryption::decryptMessage($encryptedData, $projectKey);
                $isEncrypted = true;
            } catch (Exception $e) {
                // If decryption fails, show error message
                error_log("Message decryption failed: " . $e->getMessage());
                $messageText = '[Encrypted message - unable to decrypt]';
                $isEncrypted = true;
            }
        }
        
        // Format message
        $message = [
            'id' => (string)$document['_id'],
            'message' => $messageText,
            'sender' => [
                'id' => $senderId,
                'name' => $document['sender']['name'] ?? 'Unknown User',
                'userType' => $userType
            ],
            'timestamp' => $document['timestamp']->toDateTime()->getTimestamp(),
            'formattedTime' => $document['timestamp']->toDateTime()->format('h:i A'),
            'formattedDate' => $document['timestamp']->toDateTime()->format('M d, Y'),
            'profileImage' => $profileImage,
            'isSystem' => $document['isSystemMessage'] ?? false,
            'isCurrentUser' => ($senderId === $userId),
            'isEncrypted' => $isEncrypted
        ];
        
        // Add single attachment if it exists
        if (isset($document['attachment'])) {
            $message['attachment'] = $document['attachment'];
            
            // Format file size for display
            if (isset($message['attachment']['fileSize'])) {
                $fileSize = $message['attachment']['fileSize'];
                
                if ($fileSize < 1024) {
                    $message['attachment']['formattedSize'] = $fileSize . ' B';
                } elseif ($fileSize < 1024 * 1024) {
                    $message['attachment']['formattedSize'] = round($fileSize / 1024, 1) . ' KB';
                } else {
                    $message['attachment']['formattedSize'] = round($fileSize / (1024 * 1024), 1) . ' MB';
                }
            }
        }
        
        // Add multiple attachments if they exist (legacy support)
        if (isset($document['attachments'])) {
            $message['attachments'] = $document['attachments'];
        }
        
        $response['success'] = true;
        $response['message'] = $message;
    } else {
        $response['error'] = 'Message not found';
    }
    
} catch (Exception $e) {
    $response['error'] = 'Error loading message: ' . $e->getMessage();
}

echo json_encode($response);
?> 