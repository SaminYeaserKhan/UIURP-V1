<?php
require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/model/db_connect.php';

// Import MongoDB classes
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

// Start timer
$startTime = microtime(true);
echo "Starting forum data migration...\n";

// Connect to MongoDB
$client = connectToDatabase();
$db = $client->uiurp;
$forumCollection = $db->forum;
$forumPostsCollection = $db->forum_posts;

// Count posts before migration
$oldCount = $forumCollection->countDocuments();
$newCount = $forumPostsCollection->countDocuments();

echo "Found {$oldCount} posts in old forum collection\n";
echo "Found {$newCount} posts in new forum_posts collection\n";

// 1. First ensure all posts in forum_posts have required fields
echo "\nNormalizing forum_posts collection...\n";
$newPosts = $forumPostsCollection->find()->toArray();
$updatedNewPosts = 0;

foreach ($newPosts as $post) {
    $updates = [];
    
    // Check for required fields
    if (!isset($post['user_id'])) {
        $updates['user_id'] = $post['user_id'] ?? null;
    }
    
    if (!isset($post['user_name'])) {
        $updates['user_name'] = $post['user_name'] ?? 'Unknown User';
    }
    
    if (!isset($post['upvoted_by'])) {
        $updates['upvoted_by'] = [];
    }
    
    if (!isset($post['upvotes']) && isset($post['upvoted_by'])) {
        $updates['upvotes'] = count($post['upvoted_by']);
    } elseif (!isset($post['upvotes'])) {
        $updates['upvotes'] = 0;
    }
    
    if (!isset($post['created_at'])) {
        $updates['created_at'] = $post['timestamp'] ?? new UTCDateTime();
    }
    
    if (!isset($post['updated_at'])) {
        $updates['updated_at'] = $post['timestamp'] ?? new UTCDateTime();
    }
    
    if (!isset($post['comments'])) {
        $updates['comments'] = [];
    } else {
        // Normalize comments
        $needsCommentUpdate = false;
        $normalizedComments = $post['comments'];
        
        foreach ($normalizedComments as $index => $comment) {
            if (!isset($comment['user_id'])) {
                $normalizedComments[$index]['user_id'] = null;
                $needsCommentUpdate = true;
            }
            
            if (!isset($comment['user_name'])) {
                $normalizedComments[$index]['user_name'] = $comment['user'] ?? 'Unknown';
                $needsCommentUpdate = true;
            }
            
            if (!isset($comment['edited'])) {
                $normalizedComments[$index]['edited'] = false;
                $needsCommentUpdate = true;
            }
        }
        
        if ($needsCommentUpdate) {
            $updates['comments'] = $normalizedComments;
        }
    }
    
    if (!empty($updates)) {
        $forumPostsCollection->updateOne(
            ['_id' => $post['_id']],
            ['$set' => $updates]
        );
        $updatedNewPosts++;
    }
}

echo "Updated {$updatedNewPosts} posts in forum_posts collection\n";

// 2. Migrate old forum posts to the new collection
echo "\nMigrating posts from forum to forum_posts...\n";
$oldPosts = $forumCollection->find()->toArray();
$migratedCount = 0;

foreach ($oldPosts as $oldPost) {
    // Skip if this post already exists in the new collection
    $existingPost = $forumPostsCollection->findOne(['_id' => $oldPost['_id']]);
    if ($existingPost) {
        continue;
    }
    
    // Map the old post to the new format
    $newPost = [
        '_id' => $oldPost['_id'],
        'user_id' => $oldPost['user_id'] ?? null,
        'user_name' => $oldPost['user_name'] ?? $oldPost['user'] ?? 'Unknown User',
        'user_profile_pic' => $oldPost['user_profile_pic'] ?? 'uploads/profile_images/user_avater.png',
        'title' => $oldPost['title'] ?? 'Untitled Post',
        'content' => $oldPost['content'] ?? '',
        'tags' => $oldPost['tags'] ?? ['discussion'],
        'upvotes' => $oldPost['upvotes'] ?? 0,
        'upvoted_by' => $oldPost['upvoted_by'] ?? [],
        'created_at' => $oldPost['timestamp'] ?? new UTCDateTime(),
        'updated_at' => $oldPost['timestamp'] ?? new UTCDateTime(),
        'timestamp' => $oldPost['timestamp'] ?? new UTCDateTime(), // Keep for backward compatibility
    ];
    
    // Normalize comments if they exist
    if (isset($oldPost['comments']) && is_array($oldPost['comments'])) {
        $normalizedComments = [];
        
        foreach ($oldPost['comments'] as $comment) {
            $normalizedComment = [
                'user_id' => $comment['user_id'] ?? null,
                'user_name' => $comment['user_name'] ?? $comment['user'] ?? 'Unknown',
                'user_type' => $comment['user_type'] ?? 'unknown',
                'text' => $comment['text'] ?? '',
                'time' => $comment['time'] ?? new UTCDateTime(),
                'edited' => $comment['edited'] ?? false
            ];
            $normalizedComments[] = $normalizedComment;
        }
        
        $newPost['comments'] = $normalizedComments;
    } else {
        $newPost['comments'] = [];
    }
    
    // Add attachments if they exist
    if (isset($oldPost['attachments']) && is_array($oldPost['attachments'])) {
        $newPost['attachments'] = $oldPost['attachments'];
    }
    
    // Insert the normalized post into the new collection
    try {
        $forumPostsCollection->insertOne($newPost);
        $migratedCount++;
    } catch (Exception $e) {
        echo "Error migrating post {$oldPost['_id']}: " . $e->getMessage() . "\n";
    }
}

echo "Migrated {$migratedCount} posts from forum to forum_posts collection\n";

// 3. Create tag collection if it doesn't exist
echo "\nEnsuring forum tags exist...\n";
$tagCollection = $db->forum_tags;
$tagCount = $tagCollection->countDocuments();

if ($tagCount === 0) {
    echo "Creating default tags...\n";
    
    $defaultTags = [
        ['name' => 'member_recruitment', 'color' => '#28a745'],
        ['name' => 'bug_fixes', 'color' => '#dc3545'],
        ['name' => 'discussion', 'color' => '#007bff'],
        ['name' => 'tutorial', 'color' => '#17a2b8'],
        ['name' => 'announcement', 'color' => '#ffc107'],
        ['name' => 'question', 'color' => '#6f42c1'],
        ['name' => 'resource', 'color' => '#fd7e14']
    ];
    
    $tagCollection->insertMany($defaultTags);
    echo "Created " . count($defaultTags) . " default tags\n";
} else {
    echo "Found {$tagCount} existing tags\n";
}

// 4. Create directory for forum attachments if it doesn't exist
if (!is_dir('uploads/forum_attachments')) {
    echo "\nCreating forum attachments directory...\n";
    mkdir('uploads/forum_attachments', 0755, true);
    echo "Created uploads/forum_attachments directory\n";
}

// Calculate execution time
$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

echo "\nMigration completed in {$executionTime} seconds\n";
echo "Final count: " . $forumPostsCollection->countDocuments() . " posts in forum_posts collection\n";
?> 