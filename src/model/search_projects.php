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

$searchString = trim($data['searchString']);
if (empty($searchString)) {
    echo json_encode(['error' => 'Empty search string']);
    exit;
}

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

// Create regex pattern for case-insensitive search
$searchRegex = new MongoDB\BSON\Regex($searchString, 'i');

// Build search conditions - only use $elemMatch for array fields
$searchConditions = [
    ['title' => $searchRegex],
    ['abstract' => $searchRegex],
    ['description' => $searchRegex],
    ['field' => $searchRegex],
    ['supervisor' => $searchRegex]
];

// For array fields, use proper MongoDB operators
// Check if keywords field exists and is an array
$searchConditions[] = ['keywords' => ['$elemMatch' => ['$regex' => $searchString, '$options' => 'i']]];

// For members array, search in member names
$searchConditions[] = ['members.name' => $searchRegex];

// Search query
$cursor = $collection->find([
    '$and' => [
        ['$or' => $searchConditions],
        ['privacy' => 0] // Only public projects
    ]
], $options);

$projects = [];
$projectIds = [];

foreach ($cursor as $document) {
    // Handle different ObjectId formats based on typeMap
    $projectId = null;
    if (isset($document['_id'])) {
        if (is_array($document['_id']) && isset($document['_id']['$oid'])) {
            $projectId = (string) $document['_id']['$oid'];
        } elseif (is_object($document['_id'])) {
            $projectId = (string) $document['_id'];
        } else {
            $projectId = (string) $document['_id'];
        }
    }
    
    if ($projectId && !in_array($projectId, $projectIds)) {
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
        
        if (isset($document['estimatedCompletionDate'])) {
            if (is_object($document['estimatedCompletionDate']) && method_exists($document['estimatedCompletionDate'], 'toDateTime')) {
                $document['estimatedCompletionDate'] = $document['estimatedCompletionDate']->toDateTime()->format('c');
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