<?php
require __DIR__ . '/../../vendor/autoload.php'; // Updated path to Composer's autoloader

try {
    $client = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
    $collection = $client->uiurp->students;

    if (isset($_GET['name'])) {
        $name = $_GET['name'];
        $student = $collection->findOne(['name' => $name], [
            'projection' => [
                'name' => 1,
                'bio' => 1,
                'email' => 1,
                'github' => 1,
                'linkedin' => 1,
                'projects' => 1,
                'skills' => 1,
                'learning_materials' => 1
            ]
        ]);

        if ($student) {
            echo json_encode($student);
        } else {
            echo json_encode(['error' => 'Student not found']);
        }
    } else {
        echo json_encode(['error' => 'No student specified']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>