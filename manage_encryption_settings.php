<?php
/**
 * Encryption Settings Management
 * 
 * This script allows you to manage encryption settings for all projects
 */

require_once 'src/model/db_connect.php';
require_once 'src/model/ProjectKeyManager.php';
require_once 'src/model/EncryptionConfig.php';
require_once __DIR__ . '/vendor/autoload.php';

use MongoDB\BSON\ObjectId;

echo "🔐 Encryption Settings Management\n";
echo "================================\n\n";

// Show current configuration
echo "📋 Current Configuration:\n";
echo "   Auto-enable for new projects: " . (EncryptionConfig::isAutoEnableEnabled() ? 'YES' : 'NO') . "\n";
echo "   Auto-migrate existing messages: " . (EncryptionConfig::isAutoMigrationEnabled() ? 'YES' : 'NO') . "\n";
echo "   Key rotation interval: " . EncryptionConfig::getKeyRotationInterval() . " days\n";
echo "   Show encryption status: " . (EncryptionConfig::get('SHOW_ENCRYPTION_STATUS') ? 'YES' : 'NO') . "\n";
echo "   Allow disable encryption: " . (EncryptionConfig::get('ALLOW_DISABLE_ENCRYPTION') ? 'YES' : 'NO') . "\n";
echo "   Allow key rotation: " . (EncryptionConfig::get('ALLOW_KEY_ROTATION') ? 'YES' : 'NO') . "\n\n";

// Show project encryption status
try {
    $client = connectToDatabase();
    $db = $client->uiurp;
    
    $totalProjects = $db->projectsV2->countDocuments([]);
    $encryptedProjects = $db->projectsV2->countDocuments(['encryptionEnabled' => true]);
    $unencryptedProjects = $totalProjects - $encryptedProjects;
    
    echo "📊 Project Encryption Status:\n";
    echo "   Total projects: $totalProjects\n";
    echo "   Encrypted projects: $encryptedProjects\n";
    echo "   Unencrypted projects: $unencryptedProjects\n\n";
    
    if ($unencryptedProjects > 0) {
        echo "🔧 Available Actions:\n";
        echo "   1. Enable encryption for all projects\n";
        echo "   2. Enable encryption for specific project\n";
        echo "   3. Show unencrypted projects\n";
        echo "   4. Exit\n\n";
        
        $choice = readline("Enter your choice (1-4): ");
        
        switch ($choice) {
            case '1':
                echo "\n🔄 Enabling encryption for all projects...\n";
                $projects = $db->projectsV2->find(
                    ['encryptionEnabled' => ['$ne' => true]],
                    ['projection' => ['_id' => 1, 'title' => 1]]
                );
                
                $enabled = 0;
                $failed = 0;
                
                foreach ($projects as $project) {
                    $projectId = (string)$project['_id'];
                    $title = $project['title'] ?? 'Untitled';
                    
                    echo "   Processing: $title... ";
                    
                    $result = ProjectKeyManager::initializeProjectEncryption($projectId, $db);
                    if ($result['success']) {
                        echo "✅\n";
                        $enabled++;
                    } else {
                        echo "❌ (" . $result['message'] . ")\n";
                        $failed++;
                    }
                }
                
                echo "\n📊 Results:\n";
                echo "   Enabled: $enabled\n";
                echo "   Failed: $failed\n";
                break;
                
            case '2':
                $projectId = readline("Enter project ID: ");
                if (empty($projectId)) {
                    echo "❌ Project ID is required\n";
                    break;
                }
                
                echo "🔄 Enabling encryption for project $projectId...\n";
                $result = ProjectKeyManager::initializeProjectEncryption($projectId, $db);
                
                if ($result['success']) {
                    echo "✅ " . $result['message'] . "\n";
                } else {
                    echo "❌ " . $result['message'] . "\n";
                }
                break;
                
            case '3':
                echo "\n📋 Unencrypted Projects:\n";
                $projects = $db->projectsV2->find(
                    ['encryptionEnabled' => ['$ne' => true]],
                    ['projection' => ['_id' => 1, 'title' => 1, 'createdAt' => 1]],
                    ['sort' => ['createdAt' => -1], 'limit' => 10]
                );
                
                foreach ($projects as $project) {
                    $title = $project['title'] ?? 'Untitled';
                    $createdAt = $project['createdAt']->toDateTime()->format('Y-m-d');
                    echo "   - $title (Created: $createdAt)\n";
                }
                break;
                
            case '4':
                echo "👋 Goodbye!\n";
                break;
                
            default:
                echo "❌ Invalid choice\n";
        }
    } else {
        echo "✅ All projects already have encryption enabled!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";
?>
