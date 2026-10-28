<?php
// Set up environment to avoid REQUEST_METHOD warning
$_SERVER['REQUEST_METHOD'] = 'GET';

session_start();
require_once 'src/model/user_preferences.php';

if (!isset($_SESSION['user_id'])) {
    echo "Please log in first\n";
    exit;
}

$userId = $_SESSION['user_id'];
echo "=== Resetting User Profile ===\n";
echo "User ID: $userId\n\n";

$userPreferences = new UserPreferences();

// Get current profile
$profile = $userPreferences->getUserProfile($userId);
if ($profile) {
    echo "Current profile found. Interests structure:\n";
    $interests = $profile['interests'] ?? [];
    if ($interests instanceof MongoDB\Model\BSONDocument) {
        $interests = iterator_to_array($interests);
    }
    echo "Type: " . gettype($interests) . "\n";
    echo "Count: " . count($interests) . "\n";
    if (is_array($interests)) {
        echo "Keys: " . json_encode(array_keys(array_slice($interests, 0, 5, true))) . "\n";
        echo "Values: " . json_encode(array_values(array_slice($interests, 0, 5, true))) . "\n";
    }
    echo "\n";
}

// Delete the current profile
try {
    $db = new MongoDB\Client("mongodb://localhost:27017");
    $profileCollection = $db->uiurp->user_preference_profiles;
    
    $result = $profileCollection->deleteOne(['user_id' => $userId]);
    echo "Profile deletion result: " . $result->getDeletedCount() . " documents deleted\n";
    
    // Also delete interaction history for a fresh start
    $interactionCollection = $db->uiurp->user_interactions;
    $result = $interactionCollection->deleteMany(['user_id' => $userId]);
    echo "Interaction deletion result: " . $result->getDeletedCount() . " interactions deleted\n";
    
    echo "\n✅ User profile and interactions reset successfully!\n";
    echo "Now you can start fresh - click on content to build new preferences.\n";
    
} catch (Exception $e) {
    echo "❌ Error resetting profile: " . $e->getMessage() . "\n";
}
?> 