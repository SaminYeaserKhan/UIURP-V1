<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include MongoDB connection
require __DIR__ . '/../../vendor/autoload.php';

// Connect to MongoDB
try {
    $client = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
    $db = $client->uiurp;
    $studentsCollection = $db->students;
    $facultiesCollection = $db->faculties;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['userId']) || empty($input['userId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID is required']);
    exit();
}

$userId = $input['userId'];
$userType = $input['userType'] ?? null;

try {
    // Convert to ObjectId
    $objectId = new MongoDB\BSON\ObjectId($userId);
    
    // Check specific user type if provided
    if ($userType === 'student') {
        $student = $studentsCollection->findOne(['_id' => $objectId]);
        if ($student) {
            echo json_encode([
                'exists' => true,
                'type' => 'student',
                'url' => "Student_Profile.php?id=" . $userId
            ]);
            exit();
        }
    } elseif ($userType === 'faculty') {
        $faculty = $facultiesCollection->findOne(['_id' => $objectId]);
        if ($faculty) {
            echo json_encode([
                'exists' => true,
                'type' => 'faculty',
                'url' => "Faculty_Profile.php?id=" . $userId
            ]);
            exit();
        }
    } else {
        // Check both collections if type is not specified
        $student = $studentsCollection->findOne(['_id' => $objectId]);
        if ($student) {
            echo json_encode([
                'exists' => true,
                'type' => 'student',
                'url' => "Student_Profile.php?id=" . $userId
            ]);
            exit();
        }
        
        $faculty = $facultiesCollection->findOne(['_id' => $objectId]);
        if ($faculty) {
            echo json_encode([
                'exists' => true,
                'type' => 'faculty',
                'url' => "Faculty_Profile.php?id=" . $userId
            ]);
            exit();
        }
    }
    
    // Profile not found
    echo json_encode([
        'exists' => false,
        'type' => null,
        'url' => null
    ]);
    
} catch (Exception $e) {
    // Invalid ObjectId or other error
    echo json_encode([
        'exists' => false,
        'type' => null,
        'url' => null,
        'error' => 'Invalid user ID format'
    ]);
}
?> 