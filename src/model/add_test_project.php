<?php
require_once 'db_connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

header('Content-Type: application/json');

try {
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    
    // Create a test project ID
    $projectId = new ObjectId();
    
    // Create a sample project
    $project = [
        '_id' => $projectId,
        'title' => 'Test Project',
        'abstract' => 'This is a test project for testing edit functionality',
        'description' => 'A more detailed description of the test project.',
        'field' => 'Testing',
        'institution' => 'United International University',
        'privacy' => 0,
        'createdAt' => new UTCDateTime(time() * 1000),
        'updatedAt' => new UTCDateTime(time() * 1000),
        'keywords' => ['test', 'sample', 'edit'],
        'members' => [
            [
                'name' => 'Test User',
                'role' => 'Author',
                'contribution' => 100
            ]
        ],
        'supervisor' => [
            'name' => 'Test Supervisor',
            'role' => 'Supervisor'
        ],
        'links' => [
            'github' => 'https://github.com/example/test-project',
            'website' => 'https://example.com/test-project'
        ],
        'coverImage' => [
            'url' => '/assets/images/Research_Card_Placeholder.png',
            'alt' => 'Test Project'
        ],
        'stats' => [
            'views' => 0,
            'downloads' => 0,
            'favorites' => 0
        ]
    ];
    
    // Try to add to projectsV2 collection
    $resultV2 = $db->projectsV2->insertOne($project);
    
    // Make a copy of the project to insert into the projects collection
    $projectCopy = $project;
    $resultProjects = $db->projects->insertOne($projectCopy);
    
    echo json_encode([
        'success' => true,
        'projectId' => (string)$projectId,
        'insertedToV2' => $resultV2->getInsertedCount() > 0,
        'insertedToProjects' => $resultProjects->getInsertedCount() > 0,
        'message' => 'Test project added successfully'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error adding test project: ' . $e->getMessage()
    ]);
}
?> 