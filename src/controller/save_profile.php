<?php
require __DIR__ . '/../../vendor/autoload.php'; // Updated path to Composer's autoloader

$client = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
$collection = $client->uiurp->students;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resources = [];
    if (isset($_POST['resourceTitle'])) {
        for ($i = 0; $i < count($_POST['resourceTitle']); $i++) {
            $resources[] = [
                'title' => $_POST['resourceTitle'][$i],
                'description' => $_POST['resourceDescription'][$i],
                'links' => [
                    'github' => $_POST['resourceGithubLink'][$i],
                    'live_demo' => $_POST['resourceLiveDemoLink'][$i]
                ]
            ];
        }
    }

    $student = [
        'name' => $_POST['fullName'],
        'bio' => $_POST['bio'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'],
        'student_id' => uniqid('S'),
        'github' => $_POST['githubLink'] ?? '',
        'linkedin' => $_POST['linkedinLink'] ?? '',
        'resources' => $resources
    ];

    $result = $collection->insertOne($student);

    if ($result->getInsertedCount() === 1) {
        echo "Profile saved successfully!";
    } else {
        echo "Error saving profile.";
    }
}
?>