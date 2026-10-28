<?php
// Get all schedules for project members and supervisor
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Content-Type: application/json');
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
$data = json_decode(file_get_contents('php://input'), true);
$projectId = isset($data['projectId']) ? $data['projectId'] : null;

// Validate projectId
if (!$projectId) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Project ID is required']);
    exit();
}

try {
    // Connect to MongoDB
    $client = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
    $db = $client->uiurp;
    $projectsCollection = $db->projectsV2;
    $scheduleCollection = $db->schedule;
    
    // Convert projectId to ObjectId
    $projectObjectId = convertToObjectId($projectId);
    
    if (!$projectObjectId) {
        throw new Exception("Invalid project ID format");
    }
    
    // Find the project
    $project = $projectsCollection->findOne(['_id' => $projectObjectId]);
    
    if (!$project) {
        throw new Exception("Project not found");
    }
    
    // Collect all user IDs (members + supervisor)
    $userIds = [];
    $memberInfo = [];
    
    // Add team members
    if (isset($project['members']) && is_array($project['members'])) {
        foreach ($project['members'] as $member) {
            if (isset($member['userId'])) {
                $userId = null;
                if (is_array($member['userId']) && isset($member['userId']['$oid'])) {
                    $userId = $member['userId']['$oid'];
                } else {
                    $userId = (string)$member['userId'];
                }
                
                if ($userId) {
                    $userIds[] = convertToObjectId($userId);
                    $memberInfo[$userId] = [
                        'name' => $member['name'] ?? 'Unknown',
                        'role' => $member['role'] ?? 'Team Member',
                        'type' => 'student'
                    ];
                }
            }
        }
    }
    
    // Add supervisor
    if (isset($project['supervisor']) && isset($project['supervisor']['userId'])) {
        $supervisorId = null;
        if (is_array($project['supervisor']['userId']) && isset($project['supervisor']['userId']['$oid'])) {
            $supervisorId = $project['supervisor']['userId']['$oid'];
        } else {
            $supervisorId = (string)$project['supervisor']['userId'];
        }
        
        if ($supervisorId) {
            $userIds[] = convertToObjectId($supervisorId);
            $memberInfo[$supervisorId] = [
                'name' => $project['supervisor']['name'] ?? 'Unknown',
                'role' => 'Supervisor',
                'type' => 'faculty'
            ];
        }
    }
    
    // Remove null values and duplicates
    $userIds = array_filter(array_unique($userIds, SORT_REGULAR));
    
    if (empty($userIds)) {
        throw new Exception("No members found for this project");
    }
    
    // Fetch schedules for all users
    $cursor = $scheduleCollection->find(['userId' => ['$in' => $userIds]]);
    $allSchedules = [];
    
    foreach ($cursor as $document) {
        $userId = (string)$document['userId'];
        if (!isset($allSchedules[$userId])) {
            $allSchedules[$userId] = [];
        }
        
        $allSchedules[$userId][] = [
            '_id' => (string)$document['_id'],
            'title' => $document['title'],
            'day' => $document['day'],
            'type' => $document['type'],
            'startTime' => $document['startTime'],
            'endTime' => $document['endTime'],
            'location' => isset($document['location']) ? $document['location'] : '',
            'description' => isset($document['description']) ? $document['description'] : '',
        ];
    }
    
    // Combine member info with their schedules
    $memberSchedules = [];
    foreach ($memberInfo as $userId => $info) {
        $memberSchedules[] = [
            'userId' => $userId,
            'name' => $info['name'],
            'role' => $info['role'],
            'type' => $info['type'],
            'schedule' => isset($allSchedules[$userId]) ? $allSchedules[$userId] : []
        ];
    }
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'memberSchedules' => $memberSchedules,
        'projectInfo' => [
            'title' => $project['title'] ?? 'Unknown Project',
            'totalMembers' => count($memberSchedules)
        ]
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} 