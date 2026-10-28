<?php
require __DIR__ . '/../../vendor/autoload.php'; // Updated path to Composer's autoloader

$mongoClient = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
$db = $mongoClient->uiurp;
$collection = $db->forum;

$forums = $collection->find()->toArray();

header('Content-Type: application/json');
if (empty($forums)) {
    echo json_encode(['message' => 'No forums found']);
} else {
    echo json_encode($forums);
}
?>



