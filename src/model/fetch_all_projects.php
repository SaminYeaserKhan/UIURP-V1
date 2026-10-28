<?php
require __DIR__ . '/../../vendor/autoload.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;

try {
    $mongoClient = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
    $db = $mongoClient->uiurp;
    $collection = $db->projectsV2;

    // Fetch all projects regardless of privacy setting
    $cursor = $collection->find([], ['limit' => $limit]);

    $projects = [];
    foreach ($cursor as $document) {
        $projects[] = $document;
    }

    header('Content-Type: application/json');
    echo json_encode($projects);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?> 