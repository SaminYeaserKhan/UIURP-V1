<?php
require __DIR__ . '/../../vendor/autoload.php'; // Updated path to Composer's autoloader

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$mongoClient = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
$db = $mongoClient->uiurp;
$collection = $db->faqs;

$cursor = $collection->find();
$faqs = [];

foreach ($cursor as $document) {
    $faqs[] = [
        'question' => $document['question'],
        'answer' => $document['answer']
    ];
}


echo json_encode($faqs);
?>