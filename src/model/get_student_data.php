<?php
require_once 'db_connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

header('Content-Type: application/json');

// Check if student ID is provided
if (!isset($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Student ID not provided'
    ]);
    exit;
}

$studentId = $_GET['id'];

try {
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    $collection = $db->students;
    
    // Convert the ID to MongoDB ObjectId
    try {
        $objectId = new ObjectId($studentId);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid Student ID format'
        ]);
        exit;
    }
    
    // Find the student by ID
    $student = $collection->findOne(['_id' => $objectId]);
    
    if ($student) {
        // Convert to array
        $studentArray = json_decode(json_encode($student), true);
        
        echo json_encode([
            'success' => true,
            'student' => $studentArray
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Student not found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving student data: ' . $e->getMessage()
    ]);
}
?> 