<?php
/**
 * API Endpoint for sending system messages to project chat
 * This file accepts POST requests with:
 * - projectId: The ID of the project
 * - message: The system message text
 *
 * Returns JSON response with success status and message
 */

// Include the system message helper
require_once 'send_system_chat_message_helper.php';

// Required headers
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'success' => false,
        'message' => 'Only POST method is allowed'
    ]);
    exit;
}

// Require logged in user
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to perform this action'
    ]);
    exit;
}

// Get parameters
$projectId = $_POST['projectId'] ?? null;
$message = $_POST['message'] ?? null;

// Validate parameters
if (empty($projectId) || empty($message)) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters: projectId and message'
    ]);
    exit;
}

// Call the helper function to send the system message
$result = sendSystemChatMessage($projectId, $message);

// Return the result
echo json_encode($result);
?> 