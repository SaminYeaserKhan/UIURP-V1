<?php
require __DIR__ . '/../../vendor/autoload.php'; // Updated path to Composer's autoloader

$data = json_decode(file_get_contents('php://input'), true);
$projectId = $_GET['id'];
$newPrivacy = $data['privacy'];

$mongoClient = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
$db = $mongoClient->uiurp;
$collection = $db->projects;

$updateResult = $collection->updateOne(
    ['_id' => new MongoDB\BSON\ObjectId($projectId)],
    ['$set' => ['privacy' => $newPrivacy]]
);

$updatedProject = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($projectId)]);

header('Content-Type: application/json');
echo json_encode($updatedProject);
?>