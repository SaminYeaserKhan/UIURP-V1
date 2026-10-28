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

// Validate input
if (empty($postId)) {
    echo json_encode(['success' => false, 'message' => 'Post ID is required']);
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
    
    // Verify the user owns the post or is an admin
    $userId = $_SESSION['user_id'];
    $isAdmin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    
    if (!$isAdmin && (!isset($post['user_id']) || $post['user_id'] !== $userId)) {
        echo json_encode(['success' => false, 'message' => 'You can only delete your own posts']);
        exit;
    }
    
    // Delete any attachments if they exist
    if (isset($post['attachments']) && !empty($post['attachments'])) {
        foreach ($post['attachments'] as $attachment) {
            $filePath = $attachment['file_path'] ?? '';
            if (!empty($filePath) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $filePath)) {
                unlink($_SERVER['DOCUMENT_ROOT'] . '/' . $filePath);
            }
        }
    }
    
    // Delete the post
    $result = $collection->deleteOne(['_id' => $postIdObj]);
    
    if ($result->getDeletedCount() > 0) {
        // Check if this was a form submission or API call
        if (!empty($_POST)) {
            // Redirect back to forum page
            $_SESSION['success'] = 'Post deleted successfully';
            header('Location: ../../view_posts.php');
            exit;
        } else {
            // Return JSON for API calls
            echo json_encode([
                'success' => true,
                'message' => 'Post deleted successfully'
            ]);
        }
    } else {
        if (!empty($_POST)) {
            $_SESSION['error'] = 'Failed to delete post';
            header('Location: ../../post_details.php?id=' . $postId);
            exit;
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete post'
            ]);
        }
    }
} catch (Exception $e) {
    if (!empty($_POST)) {
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
        header('Location: ../../view_posts.php');
        exit;
    } else {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} 