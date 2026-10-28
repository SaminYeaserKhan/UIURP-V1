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

// Search all projects regardless of privacy setting
$cursor = $collection->find([
    '$or' => [
        ['title' => ['$in' => $regexArray]],
        ['keywords' => ['$in' => $regexArray]],
        ['abstract' => ['$in' => $regexArray]],
        ['field' => ['$in' => $regexArray]]
    ]
]);

$projects = [];
$projectIds = [];

foreach ($cursor as $document) {
    $projectId = (string) $document['_id'];
    if (!in_array($projectId, $projectIds)) {
        $projectIds[] = $projectId;
        $projects[] = $document;
    }
}

header('Content-Type: application/json');
echo json_encode($projects);
?> 