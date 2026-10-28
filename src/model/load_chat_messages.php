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
    'messages' => [],
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

// Optional parameters
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50; // Default to 50 messages
$before = isset($_GET['before']) ? $_GET['before'] : null; // Load messages before this timestamp
$after = isset($_GET['after']) ? $_GET['after'] : null; // Load messages after this timestamp

try {
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    $collection = $db->project_chat_messages;
    
    // Prepare filter
    $filter = ['projectId' => new ObjectId($projectId)];
    
    // If before timestamp is provided, add it to filter
    if ($before) {
        $filter['timestamp'] = ['$lt' => new MongoDB\BSON\UTCDateTime((int)($before * 1000))];
    }
    
    // If after timestamp is provided, add it to filter
    if ($after) {
        $filter['timestamp'] = ['$gt' => new MongoDB\BSON\UTCDateTime((int)($after * 1000))];
    }
    
    // Options for the query
    $options = [
        'sort' => ['timestamp' => -1], // Sort by timestamp descending (newest first)
        'limit' => $limit
    ];
    
    // Execute query
    $cursor = $collection->find($filter, $options);
    
    // Get project encryption key if needed
    $projectKey = null;
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
    
    // Load messages
    $messages = [];
    $userProfiles = []; // Cache for user profiles to avoid redundant lookups
    
    foreach ($cursor as $document) {
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
        
        // Check if we already have this user's profile data in the cache
        if ($senderId && !isset($userProfiles[$senderId]) && !$document['isSystemMessage']) {
            // Fetch user profile data
            if ($userType === 'faculty') {
                $faculty = $db->faculties->findOne(['_id' => new ObjectId($senderId)]);
                if ($faculty) {
                    $profileImage = $faculty['profile_picture'] ?? $faculty['profilePicture'] ?? $faculty['profile_image'] ?? $profileImage;
                    $userProfiles[$senderId] = [
                        'profileImage' => $profileImage,
                        'name' => $faculty['name'] ?? $faculty['full_name'] ?? $document['sender']['name']
                    ];
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
                    
                    $userProfiles[$senderId] = [
                        'profileImage' => $profileImage,
                        'name' => $student['name'] ?? $student['full_name'] ?? $document['sender']['name']
                    ];
                }
            }
        }
        
        // Get profile data from cache if available
        if ($senderId && isset($userProfiles[$senderId])) {
            $profileImage = $userProfiles[$senderId]['profileImage'];
            $senderName = $userProfiles[$senderId]['name'];
        } else {
            $senderName = $document['sender']['name'] ?? 'Unknown User';
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
                'name' => $senderName,
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
        
        $messages[] = $message;
    }
    
    // Reverse to get chronological order (oldest to newest)
    $messages = array_reverse($messages);
    
    $response['success'] = true;
    $response['messages'] = $messages;
    $response['currentUserId'] = $userId;
    
} catch (Exception $e) {
    $response['message'] = 'Error loading messages: ' . $e->getMessage();
}

echo json_encode($response);
?> 