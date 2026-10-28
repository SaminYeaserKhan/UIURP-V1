<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/db_connect.php';

use MongoDB\BSON\ObjectId;

session_start();

// Initialize response
$response = [
    'success' => false,
    'message' => ''
];

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

// Check if required data is provided
if (!isset($data['projectId']) || !isset($data['fileName'])) {
    $response['message'] = 'Missing required fields';
    echo json_encode($response);
    exit;
}

try {
    $projectId = $data['projectId'];
    $fileName = $data['fileName'];

    // Get literature matrix data
    $literatureMatrix = $db->literature_matrix->findOne(['projectId' => new ObjectId($projectId)]);
    if (!$literatureMatrix) {
        $response['message'] = 'Literature matrix not found';
        echo json_encode($response);
        exit;
    }

    // Find the file in the matrix
    $fileToDelete = null;
    foreach ($literatureMatrix['files'] as $file) {
        if ($file['name'] === $fileName) {
            $fileToDelete = $file;
            break;
        }
    }

    if (!$fileToDelete) {
        $response['message'] = 'File not found in literature matrix';
        echo json_encode($response);
        exit;
    }

    // Delete the physical file
    $filePath = __DIR__ . '/../../' . $fileToDelete['path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Update the database
    $result = $db->literature_matrix->updateOne(
        ['projectId' => new ObjectId($projectId)],
        ['$pull' => ['files' => ['name' => $fileName]]]
    );

    if ($result->getModifiedCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'File deleted successfully';
    } else {
        $response['message'] = 'Failed to update database';
    }

} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?> 