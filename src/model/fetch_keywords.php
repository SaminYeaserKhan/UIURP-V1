<?php
require __DIR__ . '/../../vendor/autoload.php'; // Updated path to Composer's autoloader

header('Content-Type: application/json');

$mongoClient = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
$db = $mongoClient->uiurp;
$collection = $db->keywords;

$cursor = $collection->find([], ['sort' => ['frequency' => -1], 'limit' => 10]);
$keywords = [];

foreach ($cursor as $document) {
    $keywords[] = ['name' => $document['name']];
}

echo json_encode($keywords);
?>