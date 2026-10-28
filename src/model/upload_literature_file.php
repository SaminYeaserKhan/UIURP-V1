<?php
// Set upload limits at runtime
ini_set('upload_max_filesize', '25M');
ini_set('post_max_size', '26M');
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');

require_once __DIR__ . '/../../vendor/autoload.php';
require_once 'db_connect.php';
require_once __DIR__ . '/GridFSUploadHandler.php';
require_once __DIR__ . '/FileConfig.php';
require_once __DIR__ . '/RateLimiter.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use UIURP\Model\GridFSUploadHandler;
use UIURP\Model\FileConfig;
use UIURP\Model\RateLimiter;

session_start();

// Log current PHP settings
error_log('Current PHP settings:');
error_log('upload_max_filesize: ' . ini_get('upload_max_filesize'));
error_log('post_max_size: ' . ini_get('post_max_size'));
error_log('memory_limit: ' . ini_get('memory_limit'));

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'filePath' => null,
    'debug_info' => [
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'memory_limit' => ini_get('memory_limit')
    ]
];

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit;
}

// Check if required data is provided
if (!isset($_POST['projectId']) || !isset($_FILES['file'])) {
    $response['message'] = 'Missing required fields';
    echo json_encode($response);
    exit;
}

try {
    // Connect to database
    $client = connectToDatabase();
    $db = $client->uiurp;
    
    $projectId = $_POST['projectId'];
    $userId = $_POST['userId'];
    $userName = $_POST['userName'];
    $file = $_FILES['file'];
    
    // Check rate limiting
    $rateLimiter = new RateLimiter($db);
    $rateCheck = $rateLimiter->checkUploadAllowed($userId, $file['size']);
    
    if (!$rateCheck['allowed']) {
        $response['message'] = $rateCheck['message'];
        echo json_encode($response);
        exit;
    }

    // Check for upload errors with detailed messages
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
            $max_size = ini_get('upload_max_filesize');
            $response['message'] = "File exceeds PHP's upload_max_filesize ($max_size)";
            echo json_encode($response);
            exit;
        case UPLOAD_ERR_FORM_SIZE:
            $response['message'] = 'File exceeds the form MAX_FILE_SIZE';
            echo json_encode($response);
            exit;
        case UPLOAD_ERR_PARTIAL:
            $response['message'] = 'File was only partially uploaded';
            echo json_encode($response);
            exit;
        case UPLOAD_ERR_NO_FILE:
            $response['message'] = 'No file was uploaded';
            echo json_encode($response);
            exit;
        case UPLOAD_ERR_NO_TMP_DIR:
            $response['message'] = 'Missing temporary folder';
            echo json_encode($response);
            exit;
        case UPLOAD_ERR_CANT_WRITE:
            $response['message'] = 'Failed to write file to disk';
            echo json_encode($response);
            exit;
        case UPLOAD_ERR_EXTENSION:
            $response['message'] = 'File upload stopped by extension';
            echo json_encode($response);
            exit;
        default:
            $response['message'] = 'Unknown upload error';
            echo json_encode($response);
            exit;
    }

    // Initialize GridFS upload handler
    $gridfsHandler = new GridFSUploadHandler($db);
    
    // Upload options (literature files are PDFs only)
    $options = [
        'requiredType' => 'document',
        'maxSize' => FileConfig::MAX_FILE_SIZE_DOCUMENT
    ];
    
    // Metadata for GridFS
    $metadata = [
        'projectId' => $projectId,
        'uploadedBy' => $userId,
        'uploadType' => 'literature',
        'userName' => $userName
    ];
    
    // Upload to GridFS (MongoDB cloud storage)
    $uploadResult = $gridfsHandler->uploadToGridFS($file, $metadata, $options);
    
    if (!$uploadResult['success']) {
        $response['message'] = $uploadResult['message'];
        echo json_encode($response);
        exit;
    }
    
    // Get uploaded file data from GridFS
    $uploadedFile = $uploadResult['fileData'];
    
    // Log upload for rate limiting
    $rateLimiter->logUpload($userId, $uploadedFile['size'], $uploadedFile['name']);
    
    $response['success'] = true;
    $response['message'] = 'File uploaded successfully to cloud storage';
    $response['gridfsId'] = $uploadedFile['gridfs_id'];
    $response['fileName'] = $uploadedFile['name'];
    $response['fileSize'] = $uploadedFile['size'];

} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('File upload error: ' . $e->getMessage());
}

echo json_encode($response);
?> 