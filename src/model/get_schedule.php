<?php
// Get schedule data for a user
session_start();

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
            // Sometimes it might be nested differently
            if (isset($id['oid'])) {
                return new MongoDB\BSON\ObjectId($id['oid']);
            }
        }
        
        // If it's a string representation
        if (is_string($id)) {
            // Remove any whitespace
            $id = trim($id);
            // Check if it's a valid 24-character hex string
            if (strlen($id) === 24 && ctype_xdigit($id)) {
                return new MongoDB\BSON\ObjectId($id);
            }
        }
        
        // If we can't convert it, log for debugging and return null
        error_log("Unable to convert ID to ObjectId: " . print_r($id, true));
        return null;
        
    } catch (Exception $e) {
        error_log("Error converting ID to ObjectId: " . $e->getMessage() . " | ID: " . print_r($id, true));
        return null;
    }
}

// Get JSON data from POST request
$data = json_decode(file_get_contents('php://input'), true);
$userId = isset($data['userId']) ? $data['userId'] : null;

// Validate userId
if (!$userId) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

try {
    // Connect to MongoDB
    $client = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
    $db = $client->uiurp;
    $scheduleCollection = $db->schedule;
    
    // Convert userId to ObjectId
    $userObjectId = convertToObjectId($userId);
    
    if (!$userObjectId) {
        throw new Exception("Invalid user ID format");
    }
    
    // Find schedule items for the user
    $cursor = $scheduleCollection->find(['userId' => $userObjectId]);
    $scheduleItems = [];
    
    foreach ($cursor as $document) {
        $scheduleItems[] = [
            '_id' => (string)$document['_id'],
            'userId' => (string)$document['userId'],
            'title' => $document['title'],
            'day' => $document['day'],
            'type' => $document['type'],
            'startTime' => $document['startTime'],
            'endTime' => $document['endTime'],
            'location' => isset($document['location']) ? $document['location'] : '',
            'description' => isset($document['description']) ? $document['description'] : '',
        ];
    }
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'schedule' => $scheduleItems
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} 