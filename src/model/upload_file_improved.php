<?php
/**
 * Improved File Upload Endpoint
 * 
 * Example implementation using the new FileUploadHandler
 * Can be used as a template for updating other upload endpoints
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/FileUploadHandler.php';
require_once __DIR__ . '/FileConfig.php';

use UIURP\Model\FileUploadHandler;
use UIURP\Model\FileConfig;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

session_start();

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'files' => []
];

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit;
}

// Get user ID
$userId = null;
if (is_object($_SESSION['user_id']) && get_class($_SESSION['user_id']) === 'MongoDB\BSON\ObjectId') {
    $userId = (string)$_SESSION['user_id'];
} else {
    $userId = $_SESSION['user_id'];
}

// Check required parameters
if (!isset($_POST['projectId']) || !isset($_FILES['files'])) {
    $response['message'] = 'Missing required fields: projectId and files';
    echo json_encode($response);
    exit;
}

$projectId = $_POST['projectId'];
$uploadType = $_POST['uploadType'] ?? 'project'; // project, chat, media, etc.

try {
    // Connect to database
    $client = connectToDatabase();
    $db = $client->uiurp;
    
    // Verify project exists and user has access
    $project = $db->projectsV2->findOne(['_id' => new ObjectId($projectId)]);
    
    if (!$project) {
        $response['message'] = 'Project not found';
        echo json_encode($response);
        exit;
    }
    
    // Check if user has access to project (member, supervisor, or creator)
    $hasAccess = false;
    
    // Check members
    if (isset($project['members']) && is_array($project['members'])) {
        foreach ($project['members'] as $member) {
            $memberId = null;
            if (isset($member['userId'])) {
                if (is_object($member['userId']) && $member['userId'] instanceof ObjectId) {
                    $memberId = (string)$member['userId'];
                } elseif (is_array($member['userId']) && isset($member['userId']['$oid'])) {
                    $memberId = $member['userId']['$oid'];
                } else {
                    $memberId = (string)$member['userId'];
                }
                
                if ($memberId === $userId) {
                    $hasAccess = true;
                    break;
                }
            }
        }
    }
    
    // Check supervisor
    if (!$hasAccess && isset($project['supervisor']['userId'])) {
        $supervisorId = null;
        if (is_object($project['supervisor']['userId']) && $project['supervisor']['userId'] instanceof ObjectId) {
            $supervisorId = (string)$project['supervisor']['userId'];
        } elseif (is_array($project['supervisor']['userId']) && isset($project['supervisor']['userId']['$oid'])) {
            $supervisorId = $project['supervisor']['userId']['$oid'];
        } else {
            $supervisorId = (string)$project['supervisor']['userId'];
        }
        
        if ($supervisorId === $userId) {
            $hasAccess = true;
        }
    }
    
    // Check creator
    if (!$hasAccess && isset($project['createdBy'])) {
        $creatorId = null;
        if (is_object($project['createdBy']) && $project['createdBy'] instanceof ObjectId) {
            $creatorId = (string)$project['createdBy'];
        } elseif (is_array($project['createdBy']) && isset($project['createdBy']['$oid'])) {
            $creatorId = $project['createdBy']['$oid'];
        } else {
            $creatorId = (string)$project['createdBy'];
        }
        
        if ($creatorId === $userId) {
            $hasAccess = true;
        }
    }
    
    if (!$hasAccess) {
        $response['message'] = 'You do not have permission to upload files to this project';
        echo json_encode($response);
        exit;
    }
    
    // Initialize upload handler
    $uploadHandler = new FileUploadHandler();
    
    // Determine upload directory and options based on type
    $uploadDir = FileConfig::DIR_PROJECT_FILES;
    $options = ['prefix' => 'file'];
    
    switch ($uploadType) {
        case 'media':
            $uploadDir = FileConfig::DIR_MEDIA;
            $options = [
                'prefix' => 'media',
                'requiredType' => null // Allow images and videos
            ];
            break;
        case 'chat':
            $uploadDir = FileConfig::DIR_CHAT_FILES . $projectId . '/';
            $options = ['prefix' => 'chat_file'];
            break;
        case 'literature':
            $uploadDir = FileConfig::DIR_LITERATURE . $projectId . '/';
            $options = [
                'prefix' => 'lit',
                'requiredType' => 'document',
                'maxSize' => FileConfig::MAX_FILE_SIZE_DOCUMENT
            ];
            break;
        default:
            $uploadDir = FileConfig::DIR_PROJECT_FILES;
            $options = ['prefix' => 'file'];
    }
    
    // Handle upload (single or multiple files)
    $files = $_FILES['files'];
    
    if (is_array($files['name'])) {
        // Multiple files
        $uploadResult = $uploadHandler->handleMultipleUploads($files, $uploadDir, $options);
    } else {
        // Single file
        $uploadResult = $uploadHandler->handleUpload($files, $uploadDir, $options);
        if ($uploadResult['success']) {
            $uploadResult['files'] = [$uploadResult['fileData']];
        }
    }
    
    if ($uploadResult['success']) {
        // Update project with new files
        $filesToAdd = [];
        
        foreach ($uploadResult['files'] as $fileData) {
            if ($uploadType === 'media') {
                // Add to media array
                $filesToAdd[] = [
                    'type' => in_array($fileData['category'], ['image', 'video']) ? $fileData['category'] : 'image',
                    'url' => $fileData['path'],
                    'caption' => $fileData['name']
                ];
            } else {
                // Add to files array
                $filesToAdd[] = [
                    'name' => $fileData['name'],
                    'path' => $fileData['path'],
                    'type' => $fileData['type'],
                    'size' => $fileData['size'],
                    'uploadedAt' => $fileData['uploadedAt']
                ];
            }
        }
        
        // Update project in database
        $updateField = $uploadType === 'media' ? 'media' : 'files';
        $db->projectsV2->updateOne(
            ['_id' => new ObjectId($projectId)],
            [
                '$push' => [$updateField => ['$each' => $filesToAdd]],
                '$set' => ['updatedAt' => new UTCDateTime()]
            ]
        );
        
        $response['success'] = true;
        $response['message'] = $uploadResult['message'];
        $response['files'] = $uploadResult['files'];
    } else {
        $response['message'] = $uploadResult['message'];
        if (isset($uploadResult['errors'])) {
            $response['errors'] = $uploadResult['errors'];
        }
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('File upload error: ' . $e->getMessage());
}

echo json_encode($response);
?>
