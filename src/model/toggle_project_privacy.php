<?php
require __DIR__ . '/../../vendor/autoload.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Validate input
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['projectId']) || empty($data['projectId'])) {
    echo json_encode(['success' => false, 'error' => 'Missing project ID']);
    exit;
}

$projectId = $data['projectId'];

try {
    $mongoClient = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
    $db = $mongoClient->uiurp;
    $collection = $db->projectsV2;

    // Get current project to check its privacy status
    try {
        $objectId = new MongoDB\BSON\ObjectId($projectId);
        $project = $collection->findOne(['_id' => $objectId]);
    } catch (Exception $e) {
        // If conversion fails, try as a string ID
        $project = $collection->findOne(['_id' => $projectId]);
    }

    if (!$project) {
        echo json_encode(['success' => false, 'error' => 'Project not found']);
        exit;
    }

    // Toggle privacy: 0 (public) <-> 1 (private)
    $currentPrivacy = isset($project['privacy']) ? (int)$project['privacy'] : 1; // Default to private if not set
    $newPrivacy = $currentPrivacy === 0 ? 1 : 0;

    // Update the project privacy
    $result = $collection->updateOne(
        ['_id' => $project['_id']],
        ['$set' => ['privacy' => $newPrivacy]]
    );

    if ($result->getModifiedCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Privacy setting updated successfully',
            'newPrivacy' => $newPrivacy,
            'isPublic' => $newPrivacy === 0
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update privacy setting']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 