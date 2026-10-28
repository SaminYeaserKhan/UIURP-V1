<?php
require __DIR__ . '/../../vendor/autoload.php'; // Updated path to Composer's autoloader

$data = json_decode(file_get_contents('php://input'), true);
$keyword = $data['keyword'];

$mongoClient = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
$db = $mongoClient->uiurp;
$collection = $db->keywords;

$collection->updateOne(
    ['name' => $keyword],
    ['$inc' => ['frequency' => 1]],
    ['upsert' => true]
);

$cursor = $collection->find([], ['sort' => ['frequency' => -1], 'limit' => 10]);
$keywords = [];

foreach ($cursor as $document) {
    $keywords[] = ['name' => $document['name']];
}

header('Content-Type: application/json');
echo json_encode($keywords);
?>