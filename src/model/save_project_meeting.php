<?php
// Save a project meeting
session_start();

// Enable error logging for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/meeting_errors.log');

// Disable display errors but keep logging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// CORS headers for cross-origin requests
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Ensure clean JSON output
header('Content-Type: application/json');

// Log the start of the request
error_log("=== Save Meeting Request Started ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Raw Input: " . file_get_contents('php://input'));

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Include MongoDB connection
require_once __DIR__ . '/../../vendor/autoload.php';

// Helper function to safely convert various ObjectId formats
function convertToObjectId($id) {
    if (empty($id)) {
        return null;
    }
    
    try {
        // If it's already a MongoDB ObjectId object
        if (is_object($id) && $id instanceof MongoDB\BSON\ObjectId) {
            return $id;
        }
        
        // If it's an array (serialized ObjectId format)
        if (is_array($id)) {
            if (isset($id['$oid'])) {
                return new MongoDB\BSON\ObjectId($id['$oid']);
            }
            if (isset($id['oid'])) {
                return new MongoDB\BSON\ObjectId($id['oid']);
            }
        }
        
        // If it's a string representation
        if (is_string($id)) {
            $id = trim($id);
            if (strlen($id) === 24 && ctype_xdigit($id)) {
                return new MongoDB\BSON\ObjectId($id);
            }
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("Error converting ID to ObjectId: " . $e->getMessage());
        return null;
    }
}

// Get JSON data from POST request
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// Log the parsed data
error_log("Parsed JSON data: " . print_r($data, true));

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON parsing error: " . json_last_error_msg());
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data: ' . json_last_error_msg()]);
    exit();
}

// Validate required fields
$requiredFields = ['projectId', 'title', 'date', 'startTime', 'endTime'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

// Get current user ID
$currentUserId = null;
if (isset($_SESSION['user_data']['_id'])) {
    $currentUserId = $_SESSION['user_data']['_id'];
    if (is_array($currentUserId)) {
        if (isset($currentUserId['$oid'])) {
            $currentUserId = $currentUserId['$oid'];
        }
    }
}

if (!$currentUserId) {
    error_log("Authentication error - no current user ID");
    error_log("Session data: " . print_r($_SESSION, true));
    echo json_encode(['success' => false, 'message' => 'User authentication error']);
    exit();
}

error_log("Current user ID: " . $currentUserId);

try {
    error_log("Attempting MongoDB connection...");
    
    // Connect to MongoDB
    $client = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
    $db = $client->uiurp;
    $meetingsCollection = $db->project_meetings;
    $projectsCollection = $db->projectsV2;
    
    error_log("MongoDB connection successful");
    
    // Convert projectId to ObjectId
    $projectObjectId = convertToObjectId($data['projectId']);
    
    if (!$projectObjectId) {
        throw new Exception("Invalid project ID format");
    }
    
    // Verify project exists and user has access
    $project = $projectsCollection->findOne(['_id' => $projectObjectId]);
    
    if (!$project) {
        throw new Exception("Project not found");
    }
    
    // Check if user is part of the project (member or supervisor)
    $hasAccess = false;
    
    // Check if user is a member
    if (isset($project['members']) && is_array($project['members'])) {
        foreach ($project['members'] as $member) {
            if (isset($member['userId'])) {
                $memberId = null;
                
                // Handle different userId formats
                if (is_object($member['userId']) && $member['userId'] instanceof MongoDB\BSON\ObjectId) {
                    $memberId = (string)$member['userId'];
                } elseif (is_array($member['userId']) && isset($member['userId']['$oid'])) {
                    $memberId = $member['userId']['$oid'];
                } elseif (is_string($member['userId'])) {
                    $memberId = $member['userId'];
                } else {
                    // Handle BSONDocument or other MongoDB objects
                    try {
                        // BSONDocument can be accessed like an array
                        if (isset($member['userId']['$oid'])) {
                            $memberId = $member['userId']['$oid'];
                        } else {
                            // Try to convert to string
                            $memberId = (string)$member['userId'];
                        }
                    } catch (Exception $e) {
                        error_log("Error accessing member userId: " . $e->getMessage());
                        $memberId = (string)$member['userId'];
                    }
                }
                
                error_log("Checking member userId: " . print_r($member['userId'], true) . " -> " . $memberId);
                
                if ($memberId === $currentUserId) {
                    $hasAccess = true;
                    break;
                }
            }
        }
    }
    
    // Check if user is supervisor
    if (!$hasAccess && isset($project['supervisor']) && isset($project['supervisor']['userId'])) {
        $supervisorId = null;
        
        // Handle different supervisor userId formats
        if (is_object($project['supervisor']['userId']) && $project['supervisor']['userId'] instanceof MongoDB\BSON\ObjectId) {
            $supervisorId = (string)$project['supervisor']['userId'];
        } elseif (is_array($project['supervisor']['userId']) && isset($project['supervisor']['userId']['$oid'])) {
            $supervisorId = $project['supervisor']['userId']['$oid'];
        } elseif (is_string($project['supervisor']['userId'])) {
            $supervisorId = $project['supervisor']['userId'];
        } else {
            // Handle BSONDocument or other MongoDB objects
            try {
                // BSONDocument can be accessed like an array
                if (isset($project['supervisor']['userId']['$oid'])) {
                    $supervisorId = $project['supervisor']['userId']['$oid'];
                } else {
                    // Try to convert to string
                    $supervisorId = (string)$project['supervisor']['userId'];
                }
            } catch (Exception $e) {
                error_log("Error converting supervisor userId: " . $e->getMessage());
                $supervisorId = (string)$project['supervisor']['userId'];
            }
        }
        
        error_log("Checking supervisor userId: " . print_r($project['supervisor']['userId'], true) . " -> " . $supervisorId);
        
        if ($supervisorId === $currentUserId) {
            $hasAccess = true;
        }
    }
    
    if (!$hasAccess) {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to create meetings for this project']);
        exit();
    }
    
    // Prepare meeting data
    $meetingData = [
        'projectId' => $projectObjectId,
        'title' => $data['title'],
        'description' => isset($data['description']) ? $data['description'] : '',
        'date' => $data['date'],
        'startTime' => $data['startTime'],
        'endTime' => $data['endTime'],
        'meetingLink' => isset($data['meetingLink']) ? $data['meetingLink'] : '',
        'type' => isset($data['type']) ? $data['type'] : 'Project Meeting',
        'organizer' => convertToObjectId($currentUserId),
        'attendees' => isset($data['attendees']) ? $data['attendees'] : [],
        'status' => 'Scheduled',
        'createdAt' => new MongoDB\BSON\UTCDateTime(),
        'updatedAt' => new MongoDB\BSON\UTCDateTime()
    ];
    
    // Insert the meeting
    $result = $meetingsCollection->insertOne($meetingData);
    
    if ($result->getInsertedCount()) {
        // Check if we should send a system message to the chat
        $shouldSendMessage = false;
        $messageType = '';
        
        // Parse meeting date and time
        $meetingDateTime = DateTime::createFromFormat('Y-m-d H:i', $data['date'] . ' ' . $data['startTime']);
        $currentDateTime = new DateTime();
        
        if ($meetingDateTime) {
            // Calculate time difference in minutes
            $timeDifference = ($meetingDateTime->getTimestamp() - $currentDateTime->getTimestamp()) / 60;
            
            // Send message if meeting is within the next 60 minutes or if it's starting soon
            if ($timeDifference >= -5 && $timeDifference <= 60) {
                $shouldSendMessage = true;
                
                if ($timeDifference <= 5 && $timeDifference >= -5) {
                    $messageType = 'starting_now';
                } elseif ($timeDifference <= 30) {
                    $messageType = 'starting_soon';
                } else {
                    $messageType = 'scheduled';
                }
            }
        }
        
        // Send system message if conditions are met
        if ($shouldSendMessage) {
            // Include the system message helper
            require_once __DIR__ . '/send_system_chat_message_helper.php';
            
            try {
                // Get organizer name for the message
                $organizerName = 'A team member';
                
                // Try to get the organizer's name from the database
                $usersCollection = $db->students;
                $facultyCollection = $db->faculties;
                
                $organizer = $usersCollection->findOne(['_id' => convertToObjectId($currentUserId)]);
                if (!$organizer) {
                    $organizer = $facultyCollection->findOne(['_id' => convertToObjectId($currentUserId)]);
                }
                
                if ($organizer && isset($organizer['name'])) {
                    $organizerName = $organizer['name'];
                }
                
                // Create appropriate system message based on timing
                $messageText = "";
                $meetingLink = $data['meetingLink'] ?? '';
                
                switch ($messageType) {
                    case 'starting_now':
                        $messageText = "🚀 **Meeting Starting Now!** 🎯\n\n";
                        $messageText .= "**\"" . htmlspecialchars($data['title']) . "\"** scheduled by $organizerName is starting now!\n\n";
                        if (!empty($meetingLink)) {
                            $messageText .= "🔗 **[Join Meeting]($meetingLink)**\n\n";
                        }
                        $messageText .= "📞 Don't keep the team waiting - join the meeting now!";
                        break;
                        
                    case 'starting_soon':
                        $timeUntil = max(1, round($timeDifference));
                        $messageText = "🔔 **Meeting Starting Soon!** 📅\n\n";
                        $messageText .= "**\"" . htmlspecialchars($data['title']) . "\"** scheduled by $organizerName starts in $timeUntil minute" . ($timeUntil > 1 ? 's' : '') . "!\n\n";
                        if (!empty($meetingLink)) {
                            $messageText .= "🔗 **[Join Meeting]($meetingLink)**\n\n";
                        }
                        $messageText .= "⏰ Please prepare to join the meeting. Access is available now.";
                        break;
                        
                    case 'scheduled':
                        $messageText = "📅 **New Meeting Scheduled!** 🎯\n\n";
                        $messageText .= "$organizerName has scheduled **\"" . htmlspecialchars($data['title']) . "\"**\n\n";
                        $messageText .= "📍 **Time:** " . $meetingDateTime->format('g:i A') . " on " . $meetingDateTime->format('M j, Y') . "\n";
                        if (!empty($data['description'])) {
                            $messageText .= "📝 **Description:** " . htmlspecialchars($data['description']) . "\n";
                        }
                        if (!empty($meetingLink)) {
                            $messageText .= "🔗 **[Meeting Link]($meetingLink)**\n\n";
                        }
                        $messageText .= "\n📋 **Mark your calendar and be ready to collaborate!**";
                        break;
                }
                
                // Send the system message
                $messageResult = sendSystemChatMessage($data['projectId'], $messageText);
                error_log("System message sent for meeting creation: " . print_r($messageResult, true));
                
            } catch (Exception $e) {
                error_log("Error sending system message for meeting: " . $e->getMessage());
                // Don't fail the meeting creation if system message fails
            }
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Meeting created successfully',
            'meetingId' => (string)$result->getInsertedId()
        ]);
    } else {
        throw new Exception("Failed to create meeting");
    }
    
} catch (Exception $e) {
    error_log("Exception in save_project_meeting.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

error_log("=== Save Meeting Request Completed ==="); 