<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once 'db_connect.php';

use MongoDB\BSON\ObjectId;

session_start();
header('Content-Type: application/json');

// Etherpad API configuration
define('ETHERPAD_API_KEY', trim(file_get_contents(__DIR__ . '/../../etherpad/etherpad-lite/APIKEY.txt')));
define('ETHERPAD_BASE_URL', 'http://localhost:9001/api/1.2.15');

function callEtherpadAPI($method, $params = []) {
    $params['apikey'] = ETHERPAD_API_KEY;
    $url = ETHERPAD_BASE_URL . '/' . $method;
    
    error_log("Calling Etherpad API: " . $url);
    error_log("Params: " . print_r($params, true));
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded'
        ]
    ]);
    
    $response = curl_exec($ch);
    error_log("Raw API Response: " . $response);
    
    if (curl_errno($ch)) {
        throw new Exception('Curl error: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response: ' . $response);
    }
    
    return $decoded;
}

try {
    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        throw new Exception('User must be logged in');
    }

    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['projectId'])) {
        throw new Exception('Project ID is required');
    }

    $projectId = $data['projectId'];
    
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    
    // Check if pad already exists for this project
    $project = $db->projectsV2->findOne(['_id' => new ObjectId($projectId)]);
    
    if (!$project) {
        throw new Exception('Project not found');
    }

    // Create or get author for the current user
    $authorMapper = $_SESSION['user_id']; // Use user_id as author mapper
    $authorName = $_SESSION['username'];
    
    $authorResponse = callEtherpadAPI('createAuthorIfNotExistsFor', [
        'authorMapper' => $authorMapper,
        'name' => $authorName
    ]);
    
    if (!isset($authorResponse['code']) || $authorResponse['code'] !== 0) {
        throw new Exception('Failed to create/get author');
    }
    
    $authorId = $authorResponse['data']['authorID'];
    
    // Create or get group for the project
    $groupMapper = 'project_' . $projectId;
    $groupResponse = callEtherpadAPI('createGroupIfNotExistsFor', [
        'groupMapper' => $groupMapper
    ]);
    
    if (!isset($groupResponse['code']) || $groupResponse['code'] !== 0) {
        throw new Exception('Failed to create/get group');
    }
    
    $groupID = $groupResponse['data']['groupID'];
    
    // If pad ID doesn't exist, create a new one
    if (!isset($project['etherpadId'])) {
        // Create a new pad
        $padId = $groupMapper . '$' . time();
        
        // Test API connection first
        $testResponse = callEtherpadAPI('checkToken');
        if (!isset($testResponse['code']) || $testResponse['code'] !== 0) {
            error_log('API Test Response: ' . print_r($testResponse, true));
            throw new Exception('API authentication failed: ' . (isset($testResponse['message']) ? $testResponse['message'] : 'Unknown error'));
        }
        
        // Create group pad
        $response = callEtherpadAPI('createGroupPad', [
            'groupID' => $groupID,
            'padName' => time(),
            'text' => isset($project['title']) ? "# " . $project['title'] . "\n\nCollaborative paper writing space" : "New Paper"
        ]);
        
        if (!isset($response['code']) || $response['code'] !== 0) {
            error_log('Create Pad Response: ' . print_r($response, true));
            throw new Exception('Failed to create Etherpad: ' . (isset($response['message']) ? $response['message'] : 'Unknown error'));
        }
        
        $padId = $response['data']['padID'];
        
        // Update project with pad ID
        $db->projectsV2->updateOne(
            ['_id' => new ObjectId($projectId)],
            ['$set' => ['etherpadId' => $padId]]
        );
    } else {
        $padId = $project['etherpadId'];
    }

    // Create a session for the author that expires in 24 hours
    $validUntil = time() + (24 * 60 * 60); // 24 hours from now
    $sessionResponse = callEtherpadAPI('createSession', [
        'authorID' => $authorId,
        'groupID' => $groupID,
        'validUntil' => $validUntil
    ]);

    if (!isset($sessionResponse['code']) || $sessionResponse['code'] !== 0) {
        throw new Exception('Failed to create session');
    }

    $sessionID = $sessionResponse['data']['sessionID'];

    // Set a cookie with the session ID
    setcookie('sessionID', $sessionID, time() + (24 * 60 * 60), '/', '', true, true);
    
    // Generate a color based on the author ID
    $userColor = '#' . substr(md5($authorId), 0, 6);
    
    // Return pad info with session ID and embed URL with minimal parameters
    echo json_encode([
        'success' => true,
        'padId' => $padId,
        'padUrl' => 'http://localhost:9001/p/' . $padId,
        'embedUrl' => 'http://localhost:9001/p/' . $padId . 
                     '?userName=' . urlencode($authorName) .
                     '&userColor=' . urlencode($userColor) .
                     '&showAuthorColors=true' .
                     '&showAuthorshipColors=true',
        'sessionID' => $sessionID,
        'authorID' => $authorId,
        'authorName' => $authorName
    ]);
    
} catch (Exception $e) {
    error_log('Etherpad Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'api_key_length' => strlen(ETHERPAD_API_KEY),
            'base_url' => ETHERPAD_BASE_URL,
            'api_key_first_chars' => substr(ETHERPAD_API_KEY, 0, 8)
        ]
    ]);
} 