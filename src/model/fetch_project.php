<?php
require __DIR__ . '/../../vendor/autoload.php'; // Updated path to Composer's autoloader

$projectId = $_GET['id'];

$mongoClient = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
$db = $mongoClient->uiurp;
$collection = $db->projects;

$project = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($projectId)]);

header('Content-Type: application/json');
echo json_encode($project);
?>