<?php
require_once 'db_connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

header('Content-Type: application/json');

// Check if faculty ID is provided
if (!isset($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Faculty ID not provided'
    ]);
    exit;
}

$facultyId = $_GET['id'];

try {
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    $collection = $db->faculties;
    
    // Convert the ID to MongoDB ObjectId
    try {
        $objectId = new ObjectId($facultyId);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid Faculty ID format'
        ]);
        exit;
    }
    
    // Find the faculty by ID
    $faculty = $collection->findOne(['_id' => $objectId]);
    
    if ($faculty) {
        // Convert to array
        $facultyArray = json_decode(json_encode($faculty), true);
        
        echo json_encode([
            'success' => true,
            'faculty' => $facultyArray
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Faculty not found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving faculty data: ' . $e->getMessage()
    ]);
}
?> 