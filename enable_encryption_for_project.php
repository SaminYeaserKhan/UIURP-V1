<?php
/**
 * Enable Encryption for Specific Project
 * 
 * This script enables encryption for a specific project ID
 */

require_once 'src/model/db_connect.php';
require_once 'src/model/ProjectKeyManager.php';
require_once __DIR__ . '/vendor/autoload.php';

use MongoDB\BSON\ObjectId;

// Get project ID from command line or set it here
$projectId = $argv[1] ?? '685551a4793d650a9900a57f'; // Your project ID from the message

echo "Enabling encryption for project: $projectId\n";

try {
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    
    // Check if project exists
    $project = $db->projectsV2->findOne(
        ['_id' => new ObjectId($projectId)],
        ['projection' => ['_id' => 1, 'title' => 1, 'encryptionEnabled' => 1, 'encryptionKey' => 1]]
    );
    
    if (!$project) {
        echo "❌ Project not found!\n";
        exit(1);
    }
    
    echo "✅ Project found: " . ($project['title'] ?? 'Untitled') . "\n";
    
    // Check current encryption status
    if (isset($project['encryptionEnabled']) && $project['encryptionEnabled']) {
        echo "ℹ️  Encryption is already enabled for this project\n";
        if (isset($project['encryptionKey'])) {
            echo "✅ Encryption key exists\n";
        } else {
            echo "⚠️  Encryption enabled but no key found\n";
        }
    } else {
        echo "🔐 Enabling encryption...\n";
        
        // Enable encryption
        $result = ProjectKeyManager::initializeProjectEncryption($projectId, $db);
        
        if ($result['success']) {
            echo "✅ " . $result['message'] . "\n";
            if (isset($result['keyGenerated']) && $result['keyGenerated']) {
                echo "✅ Encryption key generated\n";
            }
        } else {
            echo "❌ Failed to enable encryption: " . $result['message'] . "\n";
            exit(1);
        }
    }
    
    // Verify encryption is enabled
    $updatedProject = $db->projectsV2->findOne(
        ['_id' => new ObjectId($projectId)],
        ['projection' => ['encryptionEnabled' => 1, 'encryptionKey' => 1, 'encryptionEnabledAt' => 1]]
    );
    
    if ($updatedProject && $updatedProject['encryptionEnabled']) {
        echo "✅ Encryption is now enabled for this project\n";
        echo "🔑 Encryption key: " . (isset($updatedProject['encryptionKey']) ? 'Generated' : 'Missing') . "\n";
        if (isset($updatedProject['encryptionEnabledAt'])) {
            echo "📅 Enabled at: " . $updatedProject['encryptionEnabledAt']->toDateTime()->format('Y-m-d H:i:s') . "\n";
        }
    } else {
        echo "❌ Encryption verification failed\n";
    }
    
    echo "\n🎉 Next steps:\n";
    echo "1. Send a new message in the group chat\n";
    echo "2. The message should now be encrypted in the database\n";
    echo "3. You can also migrate existing unencrypted messages using the chat settings\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
