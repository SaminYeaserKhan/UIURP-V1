<?php
require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../model/db_connect.php';
require_once __DIR__ . '/../model/GridFSUploadHandler.php';
require_once __DIR__ . '/../model/FileConfig.php';
require_once __DIR__ . '/../model/RateLimiter.php';

// Import MongoDB classes
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;
use UIURP\Model\GridFSUploadHandler;
use UIURP\Model\FileConfig;
use UIURP\Model\RateLimiter;

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to create a post']);
    exit;
}

// Connect to MongoDB
$client = connectToDatabase();
$db = $client->uiurp;
$collection = $db->forum_posts; // We'll use the new collection format

// Set up response header
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get user info from session
    $userId = $_SESSION['user_id'] ?? null;
    $userName = $_SESSION['username'] ?? 'Anonymous';
    $userType = $_SESSION['user_type'] ?? 'unknown';
    $userProfilePic = $_SESSION['profile_pic'] ?? 'uploads/profile_images/user_avater.png';

    try {
        // Basic post data
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $tags = $_POST['tags'] ?? [];

        // Validate input
        if (empty($title) || empty($content) || empty($tags)) {
            echo json_encode(['success' => false, 'message' => 'Title, content, and at least one tag are required']);
            exit;
        }

        // Initialize post data
        $post = [
            'user_id' => $userId,
            'user_name' => $userName,
            'user_type' => $userType,
            'user_profile_pic' => $userProfilePic,
            'title' => $title,
            'content' => $content,
            'tags' => $tags,
            'upvotes' => 0,
            'comments' => [],
            'created_at' => new UTCDateTime(),
            'updated_at' => new UTCDateTime(),
            'upvoted_by' => [],
            // Also include old format fields for compatibility
            'timestamp' => new UTCDateTime()
        ];

        // Handle file uploads using GridFS
        $attachments = [];
        if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
            $gridfsHandler = new GridFSUploadHandler($db);
            $rateLimiter = new RateLimiter($db);
            
            $fileCount = count($_FILES['files']['name']);
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['files']['error'][$i] === 0) {
                    $file = [
                        'name' => $_FILES['files']['name'][$i],
                        'type' => $_FILES['files']['type'][$i],
                        'tmp_name' => $_FILES['files']['tmp_name'][$i],
                        'error' => $_FILES['files']['error'][$i],
                        'size' => $_FILES['files']['size'][$i]
                    ];
                    
                    // Check rate limit
                    $rateCheck = $rateLimiter->checkUploadAllowed($userId, $file['size']);
                    if (!$rateCheck['allowed']) {
                        throw new Exception($rateCheck['message']);
                    }
                    
                    // Upload to GridFS
                    $metadata = [
                        'uploadedBy' => $userId,
                        'uploadType' => 'forum_attachment'
                    ];
                    
                    $uploadResult = $gridfsHandler->uploadToGridFS($file, $metadata);
                    
                    if ($uploadResult['success']) {
                        $uploadedFile = $uploadResult['fileData'];
                        $rateLimiter->logUpload($userId, $uploadedFile['size'], $uploadedFile['name']);
                        
                        $attachments[] = [
                            'original_name' => $uploadedFile['name'],
                            'gridfsId' => $uploadedFile['gridfs_id'],
                            'file_type' => $uploadedFile['type'],
                            'file_size' => $uploadedFile['size'],
                            'storage' => 'gridfs'
                        ];
                    }
                }
            }
        }
        
        // Add attachments to post data
        if (!empty($attachments)) {
            $post['attachments'] = $attachments;
        }

        // Insert post into database
        $result = $collection->insertOne($post);

        if ($result->getInsertedCount() === 1) {
            echo json_encode([
                'success' => true, 
                'message' => 'Post created successfully',
                'post_id' => (string)$result->getInsertedId()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create post']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>