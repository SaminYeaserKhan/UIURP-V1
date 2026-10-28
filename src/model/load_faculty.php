<?php
require __DIR__ . '/../../vendor/autoload.php'; // Updated path to Composer's autoloader

$client = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
$collection = $client->uiurp->faculties;

$faculties = $collection->find([], [
    'projection' => [
        '_id' => 1,
        'name' => 1,
        'bio' => 1,
        'profile_image' => 1
    ]
])->toArray();

// Convert ObjectId to string
foreach ($faculties as &$faculty) {
    $faculty['_id'] = (string) $faculty['_id'];
}

echo json_encode($faculties);
?>
