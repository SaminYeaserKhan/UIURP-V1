<?php
require_once 'db_connect.php';
require_once 'send_system_chat_message_helper.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// Import MongoDB BSON types
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

// Start session to capture user data if available
session_start();

// Debug logging removed
function logDebug($message) {
    // Function kept but logging disabled
    return;
}

// Set headers to ensure proper JSON response
header('Content-Type: application/json');

try {
    // Set user ID and username (null if not logged in)
    $userId = null;
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    } elseif (isset($_SESSION['user_data']) && isset($_SESSION['user_data']['_id']) && isset($_SESSION['user_data']['_id']['$oid'])) {
        $userId = $_SESSION['user_data']['_id']['$oid'];
    } elseif (isset($_SESSION['user_data']) && isset($_SESSION['user_data']['_id'])) {
        $userId = (string)$_SESSION['user_data']['_id'];
    }

    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 
               (isset($_SESSION['user_data']['basic_info']['name']) ? $_SESSION['user_data']['basic_info']['name'] : 
                (isset($_SESSION['user_data']['name']) ? $_SESSION['user_data']['name'] : 'Anonymous User'));

    logDebug("UserId extracted: $userId, Username: $username");

    // Validate required fields
    if (empty($_POST['title']) || empty($_POST['abstract']) || empty($_POST['field'])) {
        $missing = [];
        if (empty($_POST['title'])) $missing[] = 'title';
        if (empty($_POST['abstract'])) $missing[] = 'abstract';
        if (empty($_POST['field'])) $missing[] = 'field';
        
        logDebug("Missing required fields: " . implode(', ', $missing));
        echo json_encode([
            'success' => false,
            'message' => 'Required fields are missing: ' . implode(', ', $missing)
        ]);
        exit;
    }

    // Process members
    $members = [];
    if (!empty($_POST['members'])) {
        $membersData = json_decode($_POST['members'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logDebug("JSON error in members data: " . json_last_error_msg());
            echo json_encode([
                'success' => false,
                'message' => 'Invalid members data format'
            ]);
            exit;
        }
        
        // Add current user as a member if logged in and not already included
        if ($userId !== null) {
            $userIncluded = false;
            foreach ($membersData as $member) {
                if (isset($member['userId']) && 
                    ((isset($member['userId']['$oid']) && $member['userId']['$oid'] === $userId) || 
                     $member['userId'] === $userId)) {
                    $userIncluded = true;
                    break;
                }
            }
            
            if (!$userIncluded) {
                array_unshift($membersData, [
                    'userId' => ['$oid' => $userId],
                    'name' => $username,
                    'role' => 'Author',
                    'contribution' => 100
                ]);
            }
        }
        
        $members = $membersData;
    } else {
        // If no members provided but user is logged in, add them
        if ($userId !== null) {
            $members = [[
                'userId' => ['$oid' => $userId],
                'name' => $username,
                'role' => 'Author',
                'contribution' => 100
            ]];
        }
    }

    logDebug("Processed members: " . print_r($members, true));

    // Set dates
    $createdDate = new UTCDateTime();
    $updatedDate = new UTCDateTime();

    // Process other data with defaults
    $keywords = [];
    if (!empty($_POST['keywords'])) {
        $keywords = json_decode($_POST['keywords'], true) ?: [];
    }

    $timeline = [];
    if (!empty($_POST['timeline'])) {
        $timeline = json_decode($_POST['timeline'], true) ?: [];
    }

    $links = [];
    if (!empty($_POST['links'])) {
        $links = json_decode($_POST['links'], true) ?: [];
    }

    // Process supervisor information
    $supervisor = null;
    if (!empty($_POST['supervisor'])) {
        $supervisorName = $_POST['supervisor'];
        $supervisorId = !empty($_POST['supervisorId']) ? $_POST['supervisorId'] : null;
        
        logDebug("Processing supervisor - Name: $supervisorName, ID: $supervisorId");
        
        if ($supervisorId) {
            // Faculty supervisor with ID - fetch faculty details
            try {
                $client = connectToDatabase();
                $db = $client->uiurp;
                $facultyCollection = $db->faculties;
                $faculty = $facultyCollection->findOne(['_id' => new ObjectId($supervisorId)]);
                
                if ($faculty) {
                    $supervisor = [
                        'userId' => ['$oid' => $supervisorId],
                        'name' => $faculty['name'],
                        'role' => 'Supervisor'
                    ];
                    
                    // Add additional faculty info if available
                    if (isset($faculty['email'])) {
                        $supervisor['email'] = $faculty['email'];
                    }
                    if (isset($faculty['department'])) {
                        $supervisor['department'] = $faculty['department'];
                    }
                    
                    logDebug("Found faculty supervisor: " . print_r($supervisor, true));
                } else {
                    // Faculty ID provided but not found - treat as custom supervisor
                    $supervisor = [
                        'name' => $supervisorName,
                        'role' => 'Supervisor'
                    ];
                    logDebug("Faculty ID not found, using custom supervisor");
                }
            } catch (Exception $e) {
                // Error with faculty lookup - treat as custom supervisor
                logDebug("Error looking up faculty supervisor: " . $e->getMessage());
                $supervisor = [
                    'name' => $supervisorName,
                    'role' => 'Supervisor'
                ];
            }
        } else {
            // Custom supervisor (no ID provided)
            $supervisor = [
                'name' => $supervisorName,
                'role' => 'Supervisor'
            ];
            logDebug("Using custom supervisor without ID");
        }
    } else if ($userId) {
        // No supervisor specified, use logged-in user as supervisor
        $supervisor = [
            'userId' => ['$oid' => $userId],
            'name' => $username,
            'role' => 'Creator/Supervisor'
        ];
        logDebug("No supervisor specified, using current user as supervisor");
    }

    logDebug("Final supervisor data: " . print_r($supervisor, true));

    // Create basic project document
    $project = [
        'title' => $_POST['title'],
        'abstract' => $_POST['abstract'],
        'description' => $_POST['description'] ?? '',
        'field' => $_POST['field'],
        'keywords' => $keywords,
        'institution' => $_POST['institution'] ?? 'United International University',
        'createdAt' => $createdDate,
        'updatedAt' => $updatedDate,
        'privacy' => (int) ($_POST['privacy'] ?? 0),
        'members' => $members,
        'timeline' => $timeline,
        'supervisor' => $supervisor,
        'links' => $links,
        'stats' => [
            'views' => 0,
            'downloads' => 0,
            'favorites' => 0
        ],
        'comments' => [],
        'createdBy' => $userId
    ];

    logDebug("Project document to insert: " . print_r($project, true));

    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    $collection = $db->projectsV2;
    
    logDebug("Connected to MongoDB successfully");
    
    // Insert the project
    $result = $collection->insertOne($project);
    
    logDebug("Insert result: " . print_r($result, true));
    
    if ($result->getInsertedCount() === 1) {
        $projectId = (string) $result->getInsertedId();
        
        // Send welcome messages to project chat
        try {
            $projectTitle = $_POST['title'];
            $creatorName = $username;
            
            logDebug("Sending welcome messages for project: $projectId");
            
            $welcomeResults = [];
            
            // Send different welcome messages for supervisor and team members
            if ($supervisor) {
                $supervisorName = $supervisor['name'];
                $supervisorWelcomeMessage = "🎓 Welcome $supervisorName as the supervisor of \"$projectTitle\"! Your guidance and expertise will be invaluable to the team's success. Thank you for leading this research journey! 👨‍🏫";
                $supervisorResult = sendSystemChatMessage($projectId, $supervisorWelcomeMessage);
                $welcomeResults[] = $supervisorResult['success'] ? 'Supervisor welcome message sent' : 'Supervisor welcome failed: ' . $supervisorResult['message'];
                logDebug("Supervisor welcome result: " . print_r($supervisorResult, true));
            }
            
            // Send welcome messages for each team member
            if (!empty($members)) {
                foreach ($members as $member) {
                    if (isset($member['name'])) {
                        $memberName = $member['name'];
                        $memberRole = $member['role'] ?? 'Team Member';
                        $memberWelcomeMessage = "🎉 Welcome $memberName to \"$projectTitle\"! As a $memberRole, you're now part of an exciting research journey. Let's collaborate, innovate, and create something amazing together! 🚀";
                        $memberResult = sendSystemChatMessage($projectId, $memberWelcomeMessage);
                        $welcomeResults[] = $memberResult['success'] ? "Team member ($memberName) welcome message sent" : "Member welcome failed for $memberName: " . $memberResult['message'];
                        logDebug("Member welcome result for $memberName: " . print_r($memberResult, true));
                    }
                }
            }
            
            // Send a general project creation message
            $generalWelcomeMessage = "🎉 Welcome to \"$projectTitle\"! This project has been created by $creatorName. Use this chat to collaborate, share ideas, and track progress. Let's build something amazing together! 🚀";
            $generalResult = sendSystemChatMessage($projectId, $generalWelcomeMessage);
            $welcomeResults[] = $generalResult['success'] ? 'General creation message sent' : 'General welcome failed: ' . $generalResult['message'];
            logDebug("General welcome result: " . print_r($generalResult, true));
            
            logDebug("Welcome messages result: " . print_r($welcomeResults, true));
            
        } catch (Exception $e) {
            logDebug("Error sending welcome messages for project $projectId: " . $e->getMessage());
        }
        
        $response = [
            'success' => true,
            'message' => 'Project created successfully',
            'projectId' => $projectId
        ];
        logDebug("SUCCESS: " . print_r($response, true));
        echo json_encode($response);
    } else {
        $response = [
            'success' => false,
            'message' => 'Failed to create project - insert count was ' . $result->getInsertedCount()
        ];
        logDebug("FAILURE: " . print_r($response, true));
        echo json_encode($response);
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error creating project: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ];
    logDebug("EXCEPTION: " . print_r($response, true));
    echo json_encode($response);
}
?> 