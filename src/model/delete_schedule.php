<?php
// Delete a schedule item
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

// Validate required fields
if (!isset($data['userId']) || empty($data['userId']) || !isset($data['scheduleId']) || empty($data['scheduleId'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User ID and schedule ID are required']);
    exit();
}

// Security check: Only allow users to delete their own schedules
$currentUserId = null;
if (isset($_SESSION['user_data']['_id'])) {
    $currentUserId = $_SESSION['user_data']['_id'];
    if (is_array($currentUserId)) {
        if (isset($currentUserId['$oid'])) {
            $currentUserId = $currentUserId['$oid'];
        }
    }
    
    if ((string)$currentUserId !== (string)$data['userId']) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'You can only delete schedules for your own profile']);
        exit();
    }
}

try {
    // Connect to MongoDB
    $client = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
    $db = $client->uiurp;
    $scheduleCollection = $db->schedule;
    
    // Convert IDs to ObjectId
    $scheduleId = convertToObjectId($data['scheduleId']);
    $userObjectId = convertToObjectId($data['userId']);
    
    if (!$scheduleId || !$userObjectId) {
        throw new Exception("Invalid ID format");
    }
    
    // Verify the schedule item exists and belongs to the user
    $existingSchedule = $scheduleCollection->findOne(['_id' => $scheduleId]);
    
    if (!$existingSchedule) {
        throw new Exception("Schedule item not found");
    }
    
    if ((string)$existingSchedule['userId'] !== (string)$userObjectId) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'You can only delete your own schedule items']);
        exit();
    }
    
    // Delete the schedule item
    $result = $scheduleCollection->deleteOne(['_id' => $scheduleId]);
    
    if ($result->getDeletedCount()) {
        // Return success response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Schedule item deleted successfully'
        ]);
    } else {
        throw new Exception("Failed to delete schedule item");
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} 