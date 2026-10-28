<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once 'db_connect.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

header('Content-Type: application/json');

try {
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception('Invalid JSON data');
    }

    // Validate required fields
    if (!isset($data['projectId'])) {
        throw new Exception('Project ID is required');
    }

    // Connect to MongoDB
    $client = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
    $db = $client->uiurp;
    $collection = $db->literature_matrix;

    // Clean and prepare the data
    $cleanData = [
        'projectId' => new ObjectId($data['projectId']),
        'selectedTags' => array_map(function($tag) {
            return [
                'name' => (string)$tag['name'],
                'color' => (string)$tag['color']
            ];
        }, $data['selectedTags'] ?? []),
        'files' => array_map(function($file) {
            return [
                'name' => (string)$file['name'],
                'path' => (string)($file['path'] ?? ''),
                'uploadedAt' => (string)($file['uploadedAt'] ?? date('c')),
                'uploadedBy' => [
                    'userId' => (string)($file['uploadedBy']['userId'] ?? ''),
                    'name' => (string)($file['uploadedBy']['name'] ?? '')
                ],
                'data' => array_map('strval', (array)($file['data'] ?? [])),
                'lastEditedBy' => $file['lastEditedBy'] ? [
                    'userId' => (string)$file['lastEditedBy']['userId'],
                    'name' => (string)$file['lastEditedBy']['name'],
                    'timestamp' => (string)$file['lastEditedBy']['timestamp']
                ] : null
            ];
        }, $data['files'] ?? []),
        'updatedAt' => new UTCDateTime()
    ];

    // Update or insert the document
    $result = $collection->updateOne(
        ['projectId' => new ObjectId($data['projectId'])],
        ['$set' => $cleanData],
        ['upsert' => true]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Literature matrix saved successfully',
        'modifiedCount' => $result->getModifiedCount(),
        'upsertedCount' => $result->getUpsertedCount()
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?> 