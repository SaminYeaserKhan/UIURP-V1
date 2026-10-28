<?php
require __DIR__ . '/../../vendor/autoload.php'; // Updated path to Composer's autoloader

error_reporting(E_ALL);
ini_set('display_errors', 1);

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;

$mongoClient = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
$db = $mongoClient->uiurp;
$collection = $db->projectsV2;

$options = [
    'limit' => $limit,
    'typeMap' => [
        'root' => 'array',
        'document' => 'array',
        'array' => 'array'
    ]
];

$cursor = $collection->find(['privacy' => 0], $options);

$projects = [];
foreach ($cursor as $document) {
    // Manually convert dates to ISO strings for consistent display
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
    
    $projects[] = $document;
}

header('Content-Type: application/json');
echo json_encode($projects);
?>

