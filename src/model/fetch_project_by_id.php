<?php
require __DIR__ . '/../../vendor/autoload.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Validate project ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing project ID']);
    exit;
}

$projectId = $_GET['id'];

try {
    $mongoClient = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
    $db = $mongoClient->uiurp;
    $collection = $db->projectsV2;

    $options = [
        'typeMap' => [
            'root' => 'array',
            'document' => 'array',
            'array' => 'array'
        ]
    ];

    // Try to convert ID to MongoDB ObjectId
    try {
        $objectId = new MongoDB\BSON\ObjectId($projectId);
        $project = $collection->findOne(['_id' => $objectId], $options);
    } catch (Exception $e) {
        // If conversion fails, try as a string ID
        $project = $collection->findOne(['_id' => $projectId], $options);
    }

    if ($project) {
        // Format all date fields for consistent display
        formatDates($project);
        
        header('Content-Type: application/json');
        echo json_encode($project);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Project not found']);
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Function to recursively format all date fields in the document
 * @param array &$document The MongoDB document (passed by reference)
 */
function formatDates(&$document) {
    foreach ($document as $key => &$value) {
        if (is_object($value) && method_exists($value, 'toDateTime')) {
            // Convert MongoDB date objects to ISO string
            $value = $value->toDateTime()->format('c');
        } else if (is_array($value)) {
            // Recursively process nested arrays
            formatDates($value);
        }
    }
}
?> 