<?php
/**
 * Enable Encryption for All Projects
 * 
 * This script enables encryption for all projects in the database
 */

require_once 'src/model/db_connect.php';
require_once 'src/model/ProjectKeyManager.php';
require_once __DIR__ . '/vendor/autoload.php';

use MongoDB\BSON\ObjectId;

echo "🔐 Enabling encryption for all projects...\n\n";

try {
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    
    // Get all projects
    $projects = $db->projectsV2->find(
        [],
        ['projection' => ['_id' => 1, 'title' => 1, 'encryptionEnabled' => 1]]
    );
    
    $totalProjects = 0;
    $alreadyEnabled = 0;
    $newlyEnabled = 0;
    $failed = 0;
    
    foreach ($projects as $project) {
        $totalProjects++;
        $projectId = (string)$project['_id'];
        $projectTitle = $project['title'] ?? 'Untitled';
        
        echo "📁 Processing: $projectTitle\n";
        
        // Check if already enabled
        if (isset($project['encryptionEnabled']) && $project['encryptionEnabled']) {
            echo "   ✅ Already enabled\n";
            $alreadyEnabled++;
            continue;
        }
        
        // Enable encryption
        $result = ProjectKeyManager::initializeProjectEncryption($projectId, $db);
        
        if ($result['success']) {
            echo "   🔐 Enabled successfully\n";
            $newlyEnabled++;
        } else {
            echo "   ❌ Failed: " . $result['message'] . "\n";
            $failed++;
        }
        
        echo "\n";
    }
    
    // Summary
    echo "📊 Summary:\n";
    echo "   Total projects: $totalProjects\n";
    echo "   Already enabled: $alreadyEnabled\n";
    echo "   Newly enabled: $newlyEnabled\n";
    echo "   Failed: $failed\n";
    
    if ($newlyEnabled > 0) {
        echo "\n🎉 Encryption has been enabled for $newlyEnabled projects!\n";
        echo "All new messages in these projects will now be encrypted.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
