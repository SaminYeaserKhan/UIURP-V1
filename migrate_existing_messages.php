<?php
/**
 * Migrate Existing Messages to Encrypted Format
 * 
 * This script migrates existing unencrypted messages to encrypted format
 */

require_once 'src/model/db_connect.php';
require_once 'src/model/ChatEncryption.php';
require_once __DIR__ . '/vendor/autoload.php';

use MongoDB\BSON\ObjectId;

// Get project ID from command line or set it here
$projectId = $argv[1] ?? '685551a4793d650a9900a57f'; // Your project ID

echo "Migrating existing messages for project: $projectId\n";

try {
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    
    // Check if project has encryption enabled
    $project = $db->projectsV2->findOne(
        ['_id' => new ObjectId($projectId)],
        ['projection' => ['encryptionEnabled' => 1, 'encryptionKey' => 1]]
    );
    
    if (!$project) {
        echo "❌ Project not found!\n";
        exit(1);
    }
    
    if (!isset($project['encryptionEnabled']) || !$project['encryptionEnabled']) {
        echo "❌ Encryption is not enabled for this project. Please enable encryption first.\n";
        exit(1);
    }
    
    echo "✅ Project has encryption enabled\n";
    
    // Count unencrypted messages
    $unencryptedCount = $db->project_chat_messages->countDocuments([
        'projectId' => new ObjectId($projectId),
        '$or' => [
            ['encrypted' => ['$ne' => true]],
            ['encrypted' => ['$exists' => false]]
        ]
    ]);
    
    echo "📊 Found $unencryptedCount unencrypted messages\n";
    
    if ($unencryptedCount == 0) {
        echo "✅ No unencrypted messages to migrate\n";
        exit(0);
    }
    
    // Migrate messages
    echo "🔄 Migrating messages to encrypted format...\n";
    $migratedCount = ChatEncryption::migrateMessagesToEncrypted($projectId, $db);
    
    echo "✅ Successfully migrated $migratedCount messages\n";
    
    // Verify migration
    $remainingUnencrypted = $db->project_chat_messages->countDocuments([
        'projectId' => new ObjectId($projectId),
        '$or' => [
            ['encrypted' => ['$ne' => true]],
            ['encrypted' => ['$exists' => false]]
        ]
    ]);
    
    if ($remainingUnencrypted == 0) {
        echo "✅ All messages have been successfully migrated to encrypted format\n";
    } else {
        echo "⚠️  $remainingUnencrypted messages still unencrypted (likely system messages)\n";
    }
    
    // Show sample of migrated messages
    $sampleMessages = $db->project_chat_messages->find(
        ['projectId' => new ObjectId($projectId)],
        ['projection' => ['message' => 1, 'encrypted' => 1, 'sender.name' => 1, 'timestamp' => 1]],
        ['limit' => 3, 'sort' => ['timestamp' => -1]]
    );
    
    echo "\n📝 Sample of migrated messages:\n";
    foreach ($sampleMessages as $msg) {
        $isEncrypted = isset($msg['encrypted']) && $msg['encrypted'];
        $messagePreview = $isEncrypted ? '[ENCRYPTED]' : '[UNENCRYPTED]';
        $sender = $msg['sender']['name'] ?? 'Unknown';
        $timestamp = $msg['timestamp']->toDateTime()->format('Y-m-d H:i:s');
        echo "- $sender: $messagePreview ($timestamp)\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
