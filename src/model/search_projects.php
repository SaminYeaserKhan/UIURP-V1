<?php
require __DIR__ . '/../../vendor/autoload.php'; // Updated path to Composer's autoloader

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$data = json_decode(file_get_contents('php://input'), true);

// Validate input data
if (!isset($data['searchString']) || !is_string($data['searchString'])) {
    echo json_encode(['error' => 'Invalid or missing searchString']);
    exit;
}

$searchString = $data['searchString'];
$searchWords = explode(' ', $searchString);

$mongoClient = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
$db = $mongoClient->uiurp;
$collection = $db->projectsV2;

$regexArray = [];
foreach ($searchWords as $word) {
    $regexArray[] = new MongoDB\BSON\Regex($word, 'i');
}

$options = [
    'typeMap' => [
        'root' => 'array', 
        'document' => 'array', 
        'array' => 'array'
    ]
];

$cursor = $collection->find([
    '$and' => [
        ['$or' => [
            ['title' => ['$in' => $regexArray]],
            ['keywords' => ['$in' => $regexArray]],
            ['abstract' => ['$in' => $regexArray]],
            ['field' => ['$in' => $regexArray]]
        ]],
        ['privacy' => 0]
    ]
], $options);

$projects = [];
$projectIds = [];

foreach ($cursor as $document) {
    $projectId = (string) $document['_id']['$oid'];
    
    if (!in_array($projectId, $projectIds)) {
        // Format date fields
        if (isset($document['createdAt'])) {
            if (is_object($document['createdAt']) && method_exists($document['createdAt'], 'toDateTime')) {
                $document['createdAt'] = $document['createdAt']->toDateTime()->format('c');
            }
        }
        
        if (isset($document['updatedAt'])) {
            if (is_object($document['updatedAt']) && method_exists($document['updatedAt'], 'toDateTime')) {
                $document['updatedAt'] = $document['updatedAt']->toDateTime()->format('c');
            }
        }
        
        // Also handle dates in nested objects like timeline entries, file uploads, etc.
        if (isset($document['timeline']) && is_array($document['timeline'])) {
            foreach ($document['timeline'] as &$item) {
                if (isset($item['date']) && is_object($item['date']) && method_exists($item['date'], 'toDateTime')) {
                    $item['date'] = $item['date']->toDateTime()->format('c');
                }
            }
        }
        
        if (isset($document['files']) && is_array($document['files'])) {
            foreach ($document['files'] as &$file) {
                if (isset($file['uploadedAt']) && is_object($file['uploadedAt']) && method_exists($file['uploadedAt'], 'toDateTime')) {
                    $file['uploadedAt'] = $file['uploadedAt']->toDateTime()->format('c');
                }
            }
        }
        
        $projectIds[] = $projectId;
        $projects[] = $document;
    }
}

echo json_encode($projects);
?>