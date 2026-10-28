<?php
require_once 'db_connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once 'ChatEncryption.php';
require_once 'ProjectKeyManager.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

/**
 * Send a system message to a project's group chat
 * This is an internal helper function that doesn't require API keys
 *
 * @param string $projectId The project ID to send the message to
 * @param string $messageText The message content
 * @return array Response with success status and message
 */
function sendSystemChatMessage($projectId, $messageText) {
    try {
        // Connect to MongoDB
        $client = connectToDatabase();
        $db = $client->uiurp;
        
        // Validate inputs
        if (empty($projectId) || empty(trim($messageText))) {
            return [
                'success' => false,
                'message' => 'Missing required fields'
            ];
        }
        
        // Validate ObjectId format
        if (!preg_match('/^[a-f\d]{24}$/i', $projectId)) {
            return [
                'success' => false,
                'message' => 'Invalid project ID format'
            ];
        }
        
        // Check if project exists
        $project = $db->projectsV2->findOne(['_id' => new ObjectId($projectId)]);
        if (!$project) {
            // Try the older projects collection as fallback
            $project = $db->projects->findOne(['_id' => new ObjectId($projectId)]);
            if (!$project) {
                return [
                    'success' => false,
                    'message' => 'Project not found'
                ];
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
        
        // Encrypt system message if project has encryption enabled
        if ($project && isset($project['encryptionEnabled']) && $project['encryptionEnabled']) {
            try {
                // Get project encryption key (use a system user ID for key access)
                $systemUserId = '000000000000000000000000'; // Special system user ID
                $keyResult = ProjectKeyManager::getProjectKey($projectId, $systemUserId, $db);
                if ($keyResult['success']) {
                    $encryptedData = ChatEncryption::encryptMessage(trim($messageText), $keyResult['key']);
                    $isEncrypted = true;
                } else {
                    // If key access fails, log error but continue with unencrypted message
                    error_log("Failed to get project key for system message encryption: " . $keyResult['message']);
                }
            } catch (Exception $e) {
                // If encryption fails, log error but continue with unencrypted message
                error_log("System message encryption failed: " . $e->getMessage());
            }
        }
        
        // Create system message document
        $messageDoc = [
            'projectId' => new ObjectId($projectId),
            'sender' => [
                'name' => 'System',
                'userType' => 'system'
            ],
            'message' => $isEncrypted ? $encryptedData['ciphertext'] : trim($messageText),
            'timestamp' => $currentTime,
            'isSystemMessage' => true,
            'encrypted' => $isEncrypted
        ];
        
        // Add encryption metadata if message is encrypted
        if ($isEncrypted && $encryptedData) {
            $messageDoc['iv'] = $encryptedData['iv'];
            $messageDoc['tag'] = $encryptedData['tag'];
        }
        
        // Insert message
        $result = $db->project_chat_messages->insertOne($messageDoc);
        
        if ($result->getInsertedCount()) {
            // Update project's lastUpdated timestamp
            $db->projectsV2->updateOne(
                ['_id' => new ObjectId($projectId)],
                ['$set' => ['updatedAt' => $currentTime]]
            );
            
            return [
                'success' => true,
                'message' => 'System message sent successfully',
                'messageId' => (string)$result->getInsertedId()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to send system message'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

/**
 * Send a welcome message to a newly created project
 *
 * @param string $projectId The project ID
 * @param string $projectTitle The project title
 * @param string $creatorName The name of the project creator
 * @return array Response with success status and message
 */
function sendProjectWelcomeMessage($projectId, $projectTitle = null, $creatorName = null) {
    // Get project details if not provided
    if (!$projectTitle || !$creatorName) {
        try {
            $client = connectToDatabase();
            $db = $client->uiurp;
            
            $project = $db->projectsV2->findOne(['_id' => new ObjectId($projectId)]);
            if (!$project) {
                $project = $db->projects->findOne(['_id' => new ObjectId($projectId)]);
            }
            
            if ($project) {
                if (!$projectTitle) {
                    $projectTitle = $project['title'] ?? 'this project';
                }
                
                if (!$creatorName) {
                    // Try to get creator name from supervisor or members
                    if (isset($project['supervisor']['name'])) {
                        $creatorName = $project['supervisor']['name'];
                    } elseif (isset($project['members']) && is_array($project['members']) && count($project['members']) > 0) {
                        $creatorName = $project['members'][0]['name'] ?? 'Project Creator';
                    } else {
                        $creatorName = 'Project Creator';
                    }
                }
            }
        } catch (Exception $e) {
            $projectTitle = $projectTitle ?? 'this project';
            $creatorName = $creatorName ?? 'Project Creator';
        }
    }
    
    $welcomeMessage = "🎉 Welcome to \"$projectTitle\"! This project has been created by $creatorName. Use this chat to collaborate, share ideas, and track progress. Let's build something amazing together! 🚀";
    
    return sendSystemChatMessage($projectId, $welcomeMessage);
}

/**
 * Send a member leave message to the project chat
 *
 * @param string $projectId The project ID
 * @param string $memberName The name of the member who left
 * @param string $projectTitle Optional project title for context
 * @return array Response with success status and message
 */
function sendMemberLeaveMessage($projectId, $memberName, $projectTitle = null) {
    // Get project title if not provided
    if (!$projectTitle) {
        try {
            $client = connectToDatabase();
            $db = $client->uiurp;
            
            $project = $db->projectsV2->findOne(['_id' => new ObjectId($projectId)]);
            if (!$project) {
                $project = $db->projects->findOne(['_id' => new ObjectId($projectId)]);
            }
            
            if ($project) {
                $projectTitle = $project['title'] ?? 'the project';
            } else {
                $projectTitle = 'the project';
            }
        } catch (Exception $e) {
            $projectTitle = 'the project';
        }
    }
    
    $leaveMessage = "👋 $memberName has left $projectTitle. We appreciate their contributions and wish them well in their future endeavors!";
    
    return sendSystemChatMessage($projectId, $leaveMessage);
}

/**
 * Send a member join message to the project chat
 *
 * @param string $projectId The project ID
 * @param string $memberName The name of the new member
 * @param string $projectTitle Optional project title for context
 * @return array Response with success status and message
 */
function sendMemberJoinMessage($projectId, $memberName, $projectTitle = null) {
    // Get project title if not provided
    if (!$projectTitle) {
        try {
            $client = connectToDatabase();
            $db = $client->uiurp;
            
            $project = $db->projectsV2->findOne(['_id' => new ObjectId($projectId)]);
            if (!$project) {
                $project = $db->projects->findOne(['_id' => new ObjectId($projectId)]);
            }
            
            if ($project) {
                $projectTitle = $project['title'] ?? 'the project';
            } else {
                $projectTitle = 'the project';
            }
        } catch (Exception $e) {
            $projectTitle = 'the project';
        }
    }
    
    $joinMessage = "🎉 Welcome $memberName to $projectTitle! We're excited to have you on the team. Feel free to introduce yourself and let's collaborate! 💫";
    
    return sendSystemChatMessage($projectId, $joinMessage);
}

/**
 * Send a meeting notification message to the project chat
 *
 * @param string $projectId The project ID
 * @param string $meetingTitle The meeting title
 * @param string $startTime The meeting start time (H:i format)
 * @param string $meetingLink The meeting link URL
 * @param string $date Optional meeting date for context
 * @return array Response with success status and message
 */
function sendMeetingNotificationMessage($projectId, $meetingTitle, $startTime, $meetingLink = '', $date = null) {
    // Format the time for display
    $startDateTime = DateTime::createFromFormat('H:i', $startTime);
    $formattedTime = $startDateTime ? $startDateTime->format('g:i A') : $startTime;
    
    // Format date if provided
    $dateText = '';
    if ($date) {
        $dateObj = DateTime::createFromFormat('Y-m-d', $date);
        if ($dateObj) {
            $dateText = ' on ' . $dateObj->format('M j, Y');
        }
    }
    
    // Create the notification message with proper formatting
    $messageText = "🔔 **Meeting Starting Soon!** 📅\n\n";
    $messageText .= "**\"" . htmlspecialchars($meetingTitle) . "\"** starts at **$formattedTime**$dateText (in 5 minutes)\n\n";
    
    if (!empty($meetingLink)) {
        // Create a clickable link for the meeting
        $messageText .= "🔗 **[Join Meeting]($meetingLink)**\n\n";
    }
    
    $messageText .= "⏰ Please prepare to join the meeting. Access is available 5 minutes before the scheduled time.";
    
    return sendSystemChatMessage($projectId, $messageText);
}

/**
 * Send a meeting started message to the project chat
 *
 * @param string $projectId The project ID
 * @param string $meetingTitle The meeting title
 * @param string $meetingLink The meeting link URL
 * @return array Response with success status and message
 */
function sendMeetingStartedMessage($projectId, $meetingTitle, $meetingLink = '') {
    // Create the meeting started message
    $messageText = "🚀 **Meeting is Now Live!** 🎯\n\n";
    $messageText .= "**\"" . htmlspecialchars($meetingTitle) . "\"** has started!\n\n";
    
    if (!empty($meetingLink)) {
        $messageText .= "🔗 **[Join Now]($meetingLink)**\n\n";
    }
    
    $messageText .= "📞 Don't keep the team waiting - join the meeting now!";
    
    return sendSystemChatMessage($projectId, $messageText);
}

/**
 * Send a meeting reminder message to the project chat
 *
 * @param string $projectId The project ID
 * @param string $meetingTitle The meeting title
 * @param string $startTime The meeting start time
 * @param int $minutesUntil Minutes until the meeting starts
 * @param string $meetingLink Optional meeting link
 * @return array Response with success status and message
 */
function sendMeetingReminderMessage($projectId, $meetingTitle, $startTime, $minutesUntil, $meetingLink = '') {
    // Format the time for display
    $startDateTime = DateTime::createFromFormat('H:i', $startTime);
    $formattedTime = $startDateTime ? $startDateTime->format('g:i A') : $startTime;
    
    // Create reminder message based on time remaining
    if ($minutesUntil <= 5) {
        $urgencyIcon = "🔔";
        $urgencyText = "Starting Very Soon!";
    } elseif ($minutesUntil <= 15) {
        $urgencyIcon = "⏰";
        $urgencyText = "Starting Soon!";
    } else {
        $urgencyIcon = "📅";
        $urgencyText = "Upcoming Meeting";
    }
    
    $messageText = "$urgencyIcon **Meeting $urgencyText** 📋\n\n";
    $messageText .= "**\"" . htmlspecialchars($meetingTitle) . "\"** starts at **$formattedTime** ";
    $messageText .= $minutesUntil == 1 ? "(in 1 minute)" : "(in $minutesUntil minutes)";
    $messageText .= "\n\n";
    
    if (!empty($meetingLink)) {
        $messageText .= "🔗 **[Meeting Link]($meetingLink)**\n\n";
    }
    
    if ($minutesUntil <= 5) {
        $messageText .= "🚨 **Get ready to join now!**";
    } elseif ($minutesUntil <= 15) {
        $messageText .= "📝 **Please prepare for the meeting.**";
    } else {
        $messageText .= "📋 **Meeting scheduled - mark your calendar!**";
    }
    
    return sendSystemChatMessage($projectId, $messageText);
}
?> 