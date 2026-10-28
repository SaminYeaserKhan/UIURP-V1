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

// Get POST data (json)
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate input
if (!isset($data['postId']) || empty($data['postId'])) {
    echo json_encode(['success' => false, 'message' => 'Post ID is required']);
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
    
    // Get current user ID
    $userId = $_SESSION['user_id'];
    
    // Initialize arrays if they don't exist
    if (!isset($post['upvoted_by'])) {
        $post['upvoted_by'] = [];
    }
    
    // Convert BSON array to PHP array if needed
    $upvotedBy = $post['upvoted_by'];
    if ($upvotedBy instanceof MongoDB\Model\BSONArray) {
        $upvotedBy = $upvotedBy->getArrayCopy();
    }
    
    // Check if user has already upvoted
    $upvotedIndex = array_search($userId, $upvotedBy);
    $alreadyUpvoted = $upvotedIndex !== false;
    
    if ($alreadyUpvoted) {
        // Remove upvote
        array_splice($upvotedBy, $upvotedIndex, 1);
        
        $newUpvotes = count($upvotedBy);
        
        $collection->updateOne(
            ['_id' => $postId],
            [
                '$set' => [
                    'upvotes' => $newUpvotes,
                    'upvoted_by' => $upvotedBy
                ]
            ]
        );
        
        echo json_encode([
            'success' => true,
            'upvoted' => false,
            'upvotes' => $newUpvotes
        ]);
    } else {
        // Add upvote
        $upvotedBy[] = $userId;
        
        $newUpvotes = count($upvotedBy);
        
        $collection->updateOne(
            ['_id' => $postId],
            [
                '$set' => [
                    'upvotes' => $newUpvotes,
                    'upvoted_by' => $upvotedBy
                ]
            ]
        );
        
        echo json_encode([
            'success' => true,
            'upvoted' => true,
            'upvotes' => $newUpvotes
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>