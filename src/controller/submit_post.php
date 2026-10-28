<?php
require __DIR__ . '/../../vendor/autoload.php'; // Updated path to Composer's autoloader

$mongoClient = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
$db = $mongoClient->uiurp;
$collection = $db->forum;

$data = json_decode(file_get_contents('php://input'), true);

error_log(print_r($data, true)); // Log the received data

if (isset($data['title']) && isset($data['content'])) {
    $post = [
        'title' => $data['title'],
        'content' => $data['content'],
        'upvotes' => 0,
        'downvotes' => 0,
        'views' => 0,
        'comments' => []
    ];
    $result = $collection->insertOne($post);
    echo json_encode(['success' => true, 'id' => $result->getInsertedId()]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
}
?>