<?php
require_once '../../vendor/autoload.php';
require_once '../model/db_connect.php';

// Import MongoDB classes
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate input
if (!isset($data['postId']) || empty($data['postId'])) {
    echo json_encode(['success' => false, 'message' => 'Post ID is required']);
    exit;
}

if (!isset($data['commentIndex']) && $data['commentIndex'] !== 0) {
    echo json_encode(['success' => false, 'message' => 'Comment index is required']);
    exit;
}

if (!isset($data['text']) || trim($data['text']) === '') {
    echo json_encode(['success' => false, 'message' => 'Comment text is required']);
    exit;
}

try {
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    $forumPostsCollection = $db->forum_posts;
    
    // Try to convert the ID to ObjectId
    $postId = null;
    try {
        $postId = new ObjectId($data['postId']);
    } catch (Exception $e) {
        // If the ID is not a valid ObjectId, keep it as is (it might be a string ID)
        $postId = $data['postId'];
    }
    
    // Find post
    $post = $forumPostsCollection->findOne(['_id' => $postId]);
    
    // If post doesn't exist in forum_posts, try the old forum collection
    if (!$post) {
        $forumCollection = $db->forum;
        $post = $forumCollection->findOne(['_id' => $postId]);
        
        if (!$post) {
            echo json_encode(['success' => false, 'message' => 'Post not found']);
            exit;
        }
        
        // We found the post in the old collection, so we'll use that collection
        $collection = $forumCollection;
    } else {
        $collection = $forumPostsCollection;
    }
    
    // Verify the user owns the comment or is an admin
    $commentIndex = (int) $data['commentIndex'];
    if (!isset($post['comments'][$commentIndex])) {
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
        exit;
    }
    
    $comment = $post['comments'][$commentIndex];
    $userId = $_SESSION['user_id'];
    $isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    
    if (!$isAdmin && (!isset($comment['user_id']) || $comment['user_id'] !== $userId)) {
        echo json_encode(['success' => false, 'message' => 'You can only edit your own comments']);
        exit;
    }
    
    // Update the comment
    $comments = $post['comments'];
    $comments[$commentIndex]['text'] = $data['text'];
    $comments[$commentIndex]['edited'] = true;
    
    $result = $collection->updateOne(
        ['_id' => $postId],
        ['$set' => ['comments' => $comments]]
    );
    
    if ($result->getModifiedCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Comment updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No changes were made or failed to update comment'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 