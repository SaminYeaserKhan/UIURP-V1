<?php
require_once '../../vendor/autoload.php';
require_once '../model/db_connect.php';

use MongoDB\BSON\ObjectId;

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check for both JSON and POST data
$data = [];
if (!empty($_POST)) {
    // Regular form submission
    $data = $_POST;
} else {
    // JSON API request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true) ?: [];
}

// Normalize field names - allow both post_id and postId
$postId = $data['postId'] ?? $data['post_id'] ?? null;

// Get comment identifier - could be either an index or ID
$commentId = $data['comment_id'] ?? null;
$commentIndex = isset($data['commentIndex']) || isset($data['comment_index']) 
    ? (int)($data['commentIndex'] ?? $data['comment_index']) 
    : null;

// Validate input
if (empty($postId)) {
    echo json_encode(['success' => false, 'message' => 'Post ID is required']);
    exit;
}

if ($commentIndex === null && empty($commentId)) {
    echo json_encode(['success' => false, 'message' => 'Comment identifier is required']);
    exit;
}

try {
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    $forumPostsCollection = $db->forum_posts;
    
    // Try to convert the ID to ObjectId
    $postIdObj = null;
    try {
        $postIdObj = new ObjectId($postId);
    } catch (Exception $e) {
        // If the ID is not a valid ObjectId, keep it as is (it might be a string ID)
        $postIdObj = $postId;
    }
    
    // Find post
    $post = $forumPostsCollection->findOne(['_id' => $postIdObj]);
    
    // If post doesn't exist in forum_posts, try the old forum collection
    if (!$post) {
        $forumCollection = $db->forum;
        $post = $forumCollection->findOne(['_id' => $postIdObj]);
        
        if (!$post) {
            echo json_encode(['success' => false, 'message' => 'Post not found']);
            exit;
        }
        
        // We found the post in the old collection, so we'll use that collection
        $collection = $forumCollection;
    } else {
        $collection = $forumPostsCollection;
    }
    
    // Convert BSON array to PHP array if needed
    $comments = $post['comments'];
    if ($comments instanceof MongoDB\Model\BSONArray) {
        $comments = $comments->getArrayCopy();
    }
    
    // Find the comment by ID if provided, or use index directly
    if (!empty($commentId) && $commentIndex === null) {
        // Find comment by ID
        $commentIndex = -1;
        foreach ($comments as $index => $comment) {
            if (isset($comment['_id']) && (string)$comment['_id'] === (string)$commentId) {
                $commentIndex = $index;
                break;
            }
        }
        
        if ($commentIndex === -1) {
            echo json_encode(['success' => false, 'message' => 'Comment not found']);
            exit;
        }
    }
    
    // Verify the comment exists
    if (!isset($comments[$commentIndex])) {
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
        exit;
    }
    
    $comment = $comments[$commentIndex];
    $userId = $_SESSION['user_id'];
    $isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    
    if (!$isAdmin && $comment['user_id'] !== $userId) {
        echo json_encode(['success' => false, 'message' => 'You can only delete your own comments']);
        exit;
    }
    
    // Remove the comment
    array_splice($comments, $commentIndex, 1);
    
    $result = $collection->updateOne(
        ['_id' => $postIdObj],
        ['$set' => ['comments' => $comments]]
    );
    
    if ($result->getModifiedCount() > 0) {
        // Check if this was a form submission or API call
        if (!empty($_POST)) {
            // Redirect back to post details page
            $_SESSION['success'] = 'Comment deleted successfully';
            header('Location: ../../post_details.php?id=' . $postId);
            exit;
        } else {
            // Return JSON for API calls
            echo json_encode([
                'success' => true,
                'message' => 'Comment deleted successfully'
            ]);
        }
    } else {
        if (!empty($_POST)) {
            $_SESSION['error'] = 'Failed to delete comment';
            header('Location: ../../post_details.php?id=' . $postId);
            exit;
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete comment'
            ]);
        }
    }
} catch (Exception $e) {
    if (!empty($_POST)) {
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
        header('Location: ../../post_details.php?id=' . $postId);
        exit;
    } else {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} 