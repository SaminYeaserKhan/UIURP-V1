<?php
require __DIR__ . '/../../vendor/autoload.php'; // Updated path to Composer's autoloader

$memberId = $_GET['id'];

$mongoClient = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
$db = $mongoClient->uiurp;
$collection = $db->students;

$member = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($memberId)]);

header('Content-Type: application/json');
echo json_encode($member);
?>