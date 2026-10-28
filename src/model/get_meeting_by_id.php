<?php
error_reporting(0);
ini_set('display_errors', 0);

// CORS headers for cross-origin requests
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

session_start();
require_once 'db_connect.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'User not authenticated']);
        exit;
    }

    // Get meeting ID from URL parameter
    $meetingId = $_GET['id'] ?? null;
    
    if (!$meetingId) {
        echo json_encode(['success' => false, 'error' => 'Meeting ID is required']);
        exit;
    }

    // Connect to MongoDB
    $database = connectToDatabase();
    if (!$database) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit;
    }

    $collection = $database->project_meetings;
    
    // Find the meeting by meetingId
    $meeting = $collection->findOne(['meetingId' => $meetingId]);
    
    if (!$meeting) {
        echo json_encode(['success' => false, 'error' => 'Meeting not found']);
        exit;
    }

    // Convert MongoDB document to array for easier handling
    $meetingArray = $meeting->toArray();
    
    // Format the response to match expected structure
    $formattedMeeting = [
        'id' => $meetingArray['meetingId'],
        'title' => $meetingArray['title'],
        'description' => $meetingArray['description'] ?? '',
        'date' => $meetingArray['date'],
        'startTime' => $meetingArray['startTime'],
        'endTime' => $meetingArray['endTime'],
        'meetingLink' => $meetingArray['meetingLink'] ?? '',
        'googleMeetUrl' => $meetingArray['meetingLink'] ?? '', // Alias for compatibility
        'projectId' => isset($meetingArray['projectId']['$oid']) ? $meetingArray['projectId']['$oid'] : $meetingArray['projectId'],
        'createdBy' => isset($meetingArray['createdBy']['$oid']) ? $meetingArray['createdBy']['$oid'] : $meetingArray['createdBy'],
        'createdAt' => $meetingArray['createdAt']
    ];

    echo json_encode([
        'success' => true,
        'meeting' => $formattedMeeting
    ]);

} catch (Exception $e) {
    error_log("Error in get_meeting_by_id.php: " . $e->getMessage(), 3, "../../logs/meeting_errors.log");
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?> 