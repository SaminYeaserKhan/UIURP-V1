<?php
require_once 'db_connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

// Start session to capture user data if available
session_start();

// Add debug logging
$log_path = __DIR__ . '/../../logs/delete_project_debug.log';
file_put_contents($log_path, date('Y-m-d H:i:s') . " - DELETE REQUEST: " . file_get_contents('php://input') . "\n", FILE_APPEND);
file_put_contents($log_path, date('Y-m-d H:i:s') . " - SESSION: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

// Set user ID (null if not logged in)
$userId = null;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
} elseif (isset($_SESSION['user_data']) && isset($_SESSION['user_data']['_id']) && isset($_SESSION['user_data']['_id']['$oid'])) {
    // Try to get ID from user_data if available
    $userId = $_SESSION['user_data']['_id']['$oid'];
} elseif (isset($_SESSION['user_data']) && isset($_SESSION['user_data']['_id'])) {
    // Fall back to string representation of ID if present
    $userId = (string)$_SESSION['user_data']['_id'];
}

file_put_contents($log_path, date('Y-m-d H:i:s') . " - UserId extracted: $userId\n", FILE_APPEND);

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);
file_put_contents($log_path, date('Y-m-d H:i:s') . " - Decoded data: " . print_r($data, true) . "\n", FILE_APPEND);

// Check if project ID is provided
if (empty($data['projectId'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Project ID not provided'
    ]);
    exit;
}

$projectId = $data['projectId'];
file_put_contents($log_path, date('Y-m-d H:i:s') . " - ProjectId to delete: $projectId\n", FILE_APPEND);

try {
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    $collection = $db->projectsV2;
    
    // Validate ObjectId format
    $validObjectId = false;
    try {
        if (is_string($projectId) && strlen($projectId) === 24 && ctype_xdigit($projectId)) {
            $validObjectId = true;
        }
    } catch (Exception $e) {
        file_put_contents($log_path, date('Y-m-d H:i:s') . " - Invalid ObjectId: " . $e->getMessage() . "\n", FILE_APPEND);
    }
    
    if (!$validObjectId) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid project ID format'
        ]);
        exit;
    }
    
    // Find the project by ID - temporarily remove permission check to debug
    $objectId = new ObjectId($projectId);
    file_put_contents($log_path, date('Y-m-d H:i:s') . " - Created ObjectId successfully\n", FILE_APPEND);
    
    // First, find project without permission check
    $project = $collection->findOne(['_id' => $objectId]);
    
    if (!$project) {
        file_put_contents($log_path, date('Y-m-d H:i:s') . " - Project not found with ID: $projectId\n", FILE_APPEND);
        echo json_encode([
            'success' => false,
            'message' => 'Project not found'
        ]);
        exit;
    }
    
    file_put_contents($log_path, date('Y-m-d H:i:s') . " - Project found, attempting to delete\n", FILE_APPEND);
    
    // Delete the project
    $result = $collection->deleteOne(['_id' => $objectId]);
    
    if ($result->getDeletedCount() === 1) {
        file_put_contents($log_path, date('Y-m-d H:i:s') . " - Project deleted successfully\n", FILE_APPEND);
        
        // If there was a cover image, delete it
        if (!empty($project['coverImage']['url']) && strpos($project['coverImage']['url'], '/storage/images/') === 0) {
            $imagePath = __DIR__ . '/../../' . ltrim($project['coverImage']['url'], '/');
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }
        
        // Delete project files
        if (!empty($project['files']) && is_array($project['files'])) {
            foreach ($project['files'] as $file) {
                if (!empty($file['path']) && strpos($file['path'], '/storage/files/') === 0) {
                    $filePath = __DIR__ . '/../../' . ltrim($file['path'], '/');
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }
            }
        }
        
        // Delete media files
        if (!empty($project['media']) && is_array($project['media'])) {
            foreach ($project['media'] as $media) {
                if (!empty($media['url']) && strpos($media['url'], '/storage/media/') === 0) {
                    $mediaPath = __DIR__ . '/../../' . ltrim($media['url'], '/');
                    if (file_exists($mediaPath)) {
                        @unlink($mediaPath);
                    }
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Project deleted successfully'
        ]);
    } else {
        file_put_contents($log_path, date('Y-m-d H:i:s') . " - Failed to delete project\n", FILE_APPEND);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete project'
        ]);
    }
} catch (Exception $e) {
    file_put_contents($log_path, date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting project: ' . $e->getMessage()
    ]);
}
?> 