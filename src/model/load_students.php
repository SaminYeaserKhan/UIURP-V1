<?php
require __DIR__ . '/../../vendor/autoload.php';

try {
    $client = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
    $collection = $client->uiurp->students;

    // Fetch all students with only necessary fields
    $students = $collection->find([], [
        'projection' => [
            '_id' => 1,
            'basic_info.name' => 1,
            'academic_info.student_id' => 1,
            'name' => 1, // Fallback for old structure
            'student_id' => 1 // Fallback for old structure
        ]
    ]);

    $studentsArray = [];
    foreach ($students as $student) {
        $studentData = [
            'id' => (string)$student['_id'],
            'name' => $student['basic_info']['name'] ?? $student['name'] ?? 'Unknown',
            'student_id' => $student['academic_info']['student_id'] ?? $student['student_id'] ?? 'N/A'
        ];
        $studentsArray[] = $studentData;
    }

    // Sort students by name
    usort($studentsArray, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });

    header('Content-Type: application/json');
    // Return just the students array for consistency with the dropdown expectations
    echo json_encode($studentsArray);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([]);
}
?> 