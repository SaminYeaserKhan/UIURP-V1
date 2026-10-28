<?php
// Get all meetings for a project
session_start();

// Disable error reporting to prevent HTML output from breaking JSON
error_reporting(0);
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
$data = json_decode(file_get_contents('php://input'), true);
$projectId = isset($data['projectId']) ? $data['projectId'] : null;

// Validate projectId
if (!$projectId) {
    echo json_encode(['success' => false, 'message' => 'Project ID is required']);
    exit();
}

try {
    // Connect to MongoDB
    $client = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
    $db = $client->uiurp;
    $meetingsCollection = $db->project_meetings;
    $studentsCollection = $db->students;
    $facultiesCollection = $db->faculties;
    
    // Convert projectId to ObjectId
    $projectObjectId = convertToObjectId($projectId);
    
    if (!$projectObjectId) {
        throw new Exception("Invalid project ID format");
    }
    
    // Find meetings for the project
    $cursor = $meetingsCollection->find(
        ['projectId' => $projectObjectId],
        ['sort' => ['date' => 1, 'startTime' => 1]]
    );
    
    $meetings = [];
    
    foreach ($cursor as $document) {
        // Get organizer info
        $organizerName = 'Unknown';
        if (isset($document['organizer'])) {
            $organizerId = (string)$document['organizer'];
            
            // Try to find in students first, then faculties
            $student = $studentsCollection->findOne(['_id' => convertToObjectId($organizerId)]);
            if ($student) {
                $organizerName = $student['name'] ?? ($student['basic_info']['name'] ?? 'Unknown Student');
            } else {
                $faculty = $facultiesCollection->findOne(['_id' => convertToObjectId($organizerId)]);
                if ($faculty) {
                    $organizerName = $faculty['name'] ?? ($faculty['basic_info']['name'] ?? 'Unknown Faculty');
                }
            }
        }
        
        // Extract meeting ID from meetingLink if it exists
        $meetingId = '';
        if (isset($document['meetingLink']) && !empty($document['meetingLink'])) {
            $meetingLink = $document['meetingLink'];
            // Extract the meeting ID from URL like: "...?joinMeeting=yza-ooez-egs"
            if (preg_match('/joinMeeting=([^&]+)/', $meetingLink, $matches)) {
                $meetingId = $matches[1];
            }
        }
        
        $meetings[] = [
            '_id' => (string)$document['_id'],
            'meetingId' => $meetingId,
            'projectId' => (string)$document['projectId'],
            'title' => $document['title'],
            'description' => isset($document['description']) ? $document['description'] : '',
            'date' => $document['date'],
            'startTime' => $document['startTime'],
            'endTime' => $document['endTime'],
            'meetingLink' => isset($document['meetingLink']) ? $document['meetingLink'] : '',
            'type' => isset($document['type']) ? $document['type'] : 'Project Meeting',
            'organizer' => $organizerName,
            'organizerId' => isset($document['organizer']) ? (string)$document['organizer'] : '',
            'attendees' => isset($document['attendees']) ? $document['attendees'] : [],
            'status' => isset($document['status']) ? $document['status'] : 'Scheduled',
            'createdAt' => isset($document['createdAt']) ? $document['createdAt']->toDateTime()->format('Y-m-d H:i:s') : '',
        ];
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'meetings' => $meetings
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} 