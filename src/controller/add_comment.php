<?php
require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../model/db_connect.php';

// Import MongoDB classes
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Driver\Exception\ConnectionException;

// Set headers
header('Content-Type: application/json');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to comment']);
    exit;
}

// Get database connection
$client = connectToDatabase();
$db = $client->uiurp;
$forumPostsCollection = $db->forum_posts;
$forumCollection = $db->forum;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get input data (support both form and JSON)
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data) {
        // JSON request
        $postId = $data['postId'] ?? $data['post_id'] ?? null;
        $comment = $data['comment'] ?? $data['text'] ?? null;
    } else {
        // Form request
        $postId = $_POST['post_id'] ?? $_POST['postId'] ?? null;
        $comment = $_POST['text'] ?? $_POST['comment'] ?? null;
    }

    // Validate input
    if (empty($postId) || empty($comment)) {
        echo json_encode(['success' => false, 'message' => 'Post ID and comment text are required']);
        exit;
    }

    try {
        // Get user info from session
        $userId = $_SESSION['user_id'] ?? null;
        $userName = $_SESSION['username'] ?? 'Anonymous';
        $userType = $_SESSION['user_type'] ?? 'unknown';

        // Create comment data
        $commentData = [
            'user_id' => $userId,
            'user_name' => $userName,
            'user_type' => $userType,
            'text' => $comment,
            'time' => new UTCDateTime(),
            'edited' => false
        ];

        // Try to identify which collection the post is in
        $post = $forumPostsCollection->findOne(['_id' => new ObjectId($postId)]);
        
        if ($post) {
            // Post is in the forum_posts collection
            $result = $forumPostsCollection->updateOne(
                ['_id' => new ObjectId($postId)],
                ['$push' => ['comments' => $commentData]]
            );
        } else {
            // Check if it's in the old forum collection
            $post = $forumCollection->findOne(['_id' => new ObjectId($postId)]);
            
            if ($post) {
                $result = $forumCollection->updateOne(
                    ['_id' => new ObjectId($postId)],
                    ['$push' => ['comments' => $commentData]]
                );
            } else {
                echo json_encode(['success' => false, 'message' => 'Post not found']);
                exit;
            }
        }

        if ($result->getModifiedCount() === 1) {
            // Return JSON response instead of redirecting
            echo json_encode([
                'success' => true,
                'message' => 'Comment added successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add comment. Post may not exist.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>