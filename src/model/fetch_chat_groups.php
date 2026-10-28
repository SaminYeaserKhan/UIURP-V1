<?php
require_once 'db_connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once 'ChatEncryption.php';
require_once 'ProjectKeyManager.php';

use MongoDB\BSON\ObjectId;

// Start session to capture user data
session_start();

// Initialize response array
$response = [
    'success' => false,
    'chatGroups' => [],
    'message' => '',
];

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
    $collection = $db->projectsV2;
    
    // Define query to find all projects where user is involved
    $filter = [
        '$or' => [
            ['createdBy' => $userId],  // Direct creator ID field
            ['createdBy.$oid' => $userId],  // MongoDB ObjectId format
            ['members' => ['$elemMatch' => ['userId' => $userId]]],  // String format
            ['members' => ['$elemMatch' => ['userId.$oid' => $userId]]],  // MongoDB ObjectId format
            ['members.userId' => $userId],  // Direct string match in members array
            ['members.userId.$oid' => $userId],  // Direct ObjectId match in members array
            ['supervisor.userId.$oid' => $userId],  // MongoDB ObjectId format
            ['supervisor.userId' => $userId]  // String format
        ]
    ];
    
    $options = [
        'sort' => ['updatedAt' => -1], // Sort by most recently updated
        'projection' => [
            '_id' => 1,
            'title' => 1,
            'members' => 1,
            'supervisor' => 1,
            'coverImage' => 1
        ]
    ];
    
    $cursor = $collection->find($filter, $options);
    $chatGroups = [];
    
    // Get chat messages collection for fetching latest messages
    $messagesCollection = $db->project_chat_messages;
    
    foreach ($cursor as $project) {
        // Get the latest message for this project
        $latestMessage = $messagesCollection->findOne(
            ['projectId' => $project['_id']],
            [
                'sort' => ['timestamp' => -1],
                'projection' => [
                    'message' => 1,
                    'sender' => 1,
                    'timestamp' => 1,
                    'isSystemMessage' => 1,
                    'attachment' => 1,
                    'encrypted' => 1,
                    'iv' => 1,
                    'tag' => 1
                ]
            ]
        );
        
        $lastMessage = 'No messages yet';
        $lastSender = '';
        $lastTime = '';
        
        if ($latestMessage) {
            
            // Decrypt message if it's encrypted
            $messageText = $latestMessage['message'];
            if (ChatEncryption::isEncrypted($latestMessage)) {
                try {
                    // Get project encryption key
                    $keyResult = ProjectKeyManager::getProjectKey((string)$project['_id'], $userId, $db);
                    if ($keyResult['success']) {
                        $encryptedData = [
                            'message' => $latestMessage['message'],
                            'iv' => $latestMessage['iv'] ?? '',
                            'tag' => $latestMessage['tag'] ?? ''
                        ];
                        $messageText = ChatEncryption::decryptMessage($encryptedData, $keyResult['key']);
                    } else {
                        $messageText = '[Encrypted message]';
                    }
                } catch (Exception $e) {
                    $messageText = '[Encrypted message]';
                }
            }
            
            // Format the last message
            if (isset($latestMessage['isSystemMessage']) && $latestMessage['isSystemMessage']) {
                // For system messages, just show the message without sender name
                $lastMessage = $messageText;
                $lastSender = 'System';
            } elseif (isset($latestMessage['attachment'])) {
                // For file attachments, show a nice preview
                $fileName = $latestMessage['attachment']['fileName'] ?? 'file';
                $senderName = $latestMessage['sender']['name'] ?? 'Someone';
                $lastMessage = "$senderName shared: $fileName";
                $lastSender = $senderName;
            } else {
                // Regular message
                $lastMessage = $messageText;
                $lastSender = $latestMessage['sender']['name'] ?? 'Someone';
            }
            
            
            // Truncate message if too long
            if (strlen($lastMessage) > 50) {
                $lastMessage = substr($lastMessage, 0, 47) . '...';
            }
            
            // Format timestamp
            if (isset($latestMessage['timestamp'])) {
                $timestamp = $latestMessage['timestamp'];
                if (is_object($timestamp) && method_exists($timestamp, 'toDateTime')) {
                    $dateTime = $timestamp->toDateTime();
                    $now = new DateTime();
                    $diff = $now->diff($dateTime);
                    
                    if ($diff->days == 0) {
                        // Today - show time
                        $lastTime = $dateTime->format('H:i');
                    } elseif ($diff->days == 1) {
                        // Yesterday
                        $lastTime = 'Yesterday';
                    } elseif ($diff->days < 7) {
                        // This week - show day name
                        $lastTime = $dateTime->format('D');
                    } else {
                        // Older - show date
                        $lastTime = $dateTime->format('M j');
                    }
                }
            }
        }
        
        // Create a chat group object for each project
        $group = [
            'id' => $project['_id'],
            'name' => $project['title'],
            'lastMessage' => $lastMessage,
            'lastSender' => $lastSender,
            'lastTime' => $lastTime,
            'imageUrl' => isset($project['coverImage']) && isset($project['coverImage']['url']) ? 
                $project['coverImage']['url'] : null,
        ];
        
        $chatGroups[] = $group;
    }
    
    $response['success'] = true;
    $response['chatGroups'] = $chatGroups;
    echo json_encode($response);
    
} catch (Exception $e) {
    $errorMsg = "Error fetching chat groups: " . $e->getMessage();
    
    $response['message'] = $errorMsg;
    echo json_encode($response);
}
?> 