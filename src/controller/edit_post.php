<?php
// Set higher upload limits directly in PHP
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../model/db_connect.php';
require_once __DIR__ . '/../model/GridFSUploadHandler.php';
require_once __DIR__ . '/../model/FileConfig.php';
require_once __DIR__ . '/../model/RateLimiter.php';

// Import MongoDB classes
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONArray;
use UIURP\Model\GridFSUploadHandler;
use UIURP\Model\FileConfig;
use UIURP\Model\RateLimiter;
use MongoDB\Model\BSONDocument;

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set headers
header('Content-Type: application/json');

// Start debug logging
$debugLog = fopen(__DIR__ . "/../../logs/attachment_debug.log", "a");
function debug_log($message) {
    global $debugLog;
    fwrite($debugLog, "[" . date('Y-m-d H:i:s') . "] " . $message . "\n");
}

debug_log("Edit post request started");
debug_log("PHP upload_max_filesize: " . ini_get('upload_max_filesize'));
debug_log("PHP post_max_size: " . ini_get('post_max_size'));
debug_log("PHP memory_limit: " . ini_get('memory_limit'));

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to edit a post']);
    exit;
}

// Get database connection
$client = connectToDatabase();
$db = $client->uiurp;
$forumPostsCollection = $db->forum_posts;
$forumCollection = $db->forum;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get input data
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            // Try to get form data if JSON parsing failed
            $data = $_POST;
        }
        
        debug_log("Request data: " . print_r($data, true));
        debug_log("FILES data: " . print_r($_FILES, true));
        
        $postId = $data['postId'] ?? null;
        $title = $data['title'] ?? null;
        $content = $data['content'] ?? null;
        $tags = $data['tags'] ?? [];
        
        // Validate input
        if (empty($postId) || empty($title) || empty($content) || empty($tags)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }
        
        // Try to find the post in forum_posts collection first
        $post = $forumPostsCollection->findOne(['_id' => new ObjectId($postId)]);
        $collectionToUse = $forumPostsCollection;
        
        // If not found, check the old forum collection
        if (!$post) {
            $post = $forumCollection->findOne(['_id' => new ObjectId($postId)]);
            $collectionToUse = $forumCollection;
            
            if (!$post) {
                echo json_encode(['success' => false, 'message' => 'Post not found']);
                exit;
            }
        }
        
        // Check if user is the post owner
        if (!isset($post['user_id']) || $post['user_id'] !== $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'You can only edit your own posts']);
            exit;
        }
        
        // Handle file uploads if present
        $attachments = $post['attachments'] ?? [];
        
        // Debug log original attachments
        debug_log("Original attachments: " . print_r($attachments, true));
        
        // Convert MongoDB\Model\BSONArray to PHP array if needed
        if ($attachments instanceof MongoDB\Model\BSONArray) {
            $attachments = $attachments->getArrayCopy();
            debug_log("Converted BSON attachments to PHP array");
            
            // Also convert each BSON document to PHP array
            foreach ($attachments as $key => $attachment) {
                if ($attachment instanceof MongoDB\Model\BSONDocument) {
                    $attachments[$key] = $attachment->getArrayCopy();
                }
            }
            
            debug_log("Converted BSON documents to PHP arrays: " . print_r($attachments, true));
        }
        
        // Initialize attachments as empty array if it's not already an array
        if (!is_array($attachments)) {
            $attachments = [];
        }
        
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
        
        // Handle attachment deletions if any
        if (isset($data['delete_attachments']) && is_array($data['delete_attachments'])) {
            debug_log("Delete attachments array: " . print_r($data['delete_attachments'], true));
            debug_log("Delete attachments array types: ");
            foreach ($data['delete_attachments'] as $idx => $val) {
                debug_log("  Index {$idx}: Value '{$val}' type: " . gettype($val));
            }
            
            debug_log("Current attachments array type: " . (is_array($attachments) ? 'Array' : get_class($attachments)));
            debug_log("Current attachments array: ");
            foreach ($attachments as $idx => $att) {
                $attType = is_array($att) ? 'Array' : (is_object($att) ? get_class($att) : gettype($att));
                $fileName = '';
                
                if (is_array($att) && isset($att['original_name'])) {
                    $fileName = $att['original_name'];
                } elseif ($att instanceof BSONDocument && isset($att['original_name'])) {
                    $fileName = $att['original_name'];
                } else {
                    $fileName = 'Unknown';
                }
                
                debug_log("  Index {$idx} (type: " . gettype($idx) . "): {$fileName} (attachment type: {$attType})");
            }
            
            // For each attachment, we'll check if we're keeping it or not
            $keepAttachments = [];
            
            // Loop through each attachment and decide if it should be kept
            foreach ($attachments as $index => $attachment) {
                $indexStr = (string)$index;
                debug_log("Checking attachment {$index} (as string: {$indexStr})");
                
                // Get attachment data regardless of type
                $attData = is_array($attachment) ? $attachment : 
                          ($attachment instanceof BSONDocument ? $attachment->getArrayCopy() : []);
                
                $originalName = isset($attData['original_name']) ? $attData['original_name'] : 'Unknown';
                $filePath = isset($attData['file_path']) ? $attData['file_path'] : null;
                
                $shouldDelete = false;
                // Check if this index is in the delete_attachments array
                foreach ($data['delete_attachments'] as $delIdx) {
                    $delIdxStr = (string)$delIdx;
                    debug_log("  Comparing delete index '{$delIdxStr}' with attachment index '{$indexStr}'");
                    
                    if ($delIdxStr === $indexStr) { // Convert both to strings for consistent comparison
                        debug_log("  MATCH FOUND: Will delete attachment {$index} - {$originalName}");
                        $shouldDelete = true;
                        break;
                    } else {
                        debug_log("  No match between '{$delIdxStr}' and '{$indexStr}'");
                    }
                }
                
                if ($shouldDelete) {
                    debug_log("Deleting attachment {$index}: {$originalName}");
                    // Delete the file from server if we have a file path
                    if ($filePath) {
                        $serverPath = '../../' . $filePath;
                        if (file_exists($serverPath)) {
                            unlink($serverPath);
                            debug_log("Deleted file: {$serverPath}");
                        } else {
                            debug_log("File not found for deletion: {$serverPath}");
                        }
                    }
                } else {
                    debug_log("Keeping attachment {$index}: {$originalName}");
                    $keepAttachments[] = $attachment; // Keep original format
                }
            }
            
            debug_log("Kept " . count($keepAttachments) . " attachments out of " . count($attachments));
            $attachments = $keepAttachments;
        }
        
        // Set up update data
        $updateData = [
            'title' => $title,
            'content' => $content,
            'tags' => $tags,
            'attachments' => $attachments,
            'updated_at' => new UTCDateTime()
        ];
        
        // If it's an old post in the forum collection, also update timestamp for compatibility
        if ($collectionToUse === $forumCollection) {
            $updateData['timestamp'] = new UTCDateTime();
        }
        
        // Debug log final update data
        debug_log("Final update data: " . json_encode(['title' => $title, 'tags' => $tags, 'attachment_count' => count($attachments)]));
        
        // Update post
        $result = $collectionToUse->updateOne(
            ['_id' => new ObjectId($postId)],
            ['$set' => $updateData]
        );
        
        if ($result->getModifiedCount() === 1) {
            debug_log("Post updated successfully");
            echo json_encode(['success' => true, 'message' => 'Post updated successfully']);
        } else {
            debug_log("No changes made to post");
            echo json_encode(['success' => false, 'message' => 'No changes were made to the post']);
        }
    } catch (Exception $e) {
        debug_log("Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    debug_log("Invalid request method");
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

// Close debug log
fclose($debugLog); 