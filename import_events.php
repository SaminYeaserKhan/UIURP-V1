<?php
require __DIR__ . '/vendor/autoload.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Connect to MongoDB
    $mongoClient = new MongoDB\Client(require __DIR__ . '/src/config/mongo_uri.php');
    $db = $mongoClient->uiurp;
    $collection = $db->events;
    
    echo "<h2>UIU Events Import Script</h2>";
    echo "<p>Importing events from JSON file...</p>";
    
    // Read the JSON file
    $jsonFile = 'assets/json/uiurp.events.json';
    if (!file_exists($jsonFile)) {
        die("Error: JSON file not found at $jsonFile");
    }
    
    $jsonContent = file_get_contents($jsonFile);
    $events = json_decode($jsonContent, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Error: Invalid JSON format - " . json_last_error_msg());
    }
    
    echo "<p>Found " . count($events) . " events in JSON file</p>";
    
    // Clear existing events (optional - remove this if you want to keep existing events)
    $deleteResult = $collection->deleteMany([]);
    echo "<p>Cleared " . $deleteResult->getDeletedCount() . " existing events</p>";
    
    // Insert new events
    if (!empty($events)) {
        // Convert date strings to MongoDB date objects
        foreach ($events as &$event) {
            if (isset($event['eventDate'])) {
                $event['eventDate'] = new MongoDB\BSON\UTCDateTime(strtotime($event['eventDate']) * 1000);
            }
            if (isset($event['createdAt'])) {
                $event['createdAt'] = new MongoDB\BSON\UTCDateTime(strtotime($event['createdAt']) * 1000);
            }
            if (isset($event['updatedAt'])) {
                $event['updatedAt'] = new MongoDB\BSON\UTCDateTime(strtotime($event['updatedAt']) * 1000);
            }
            if (isset($event['registration']['deadline'])) {
                $event['registration']['deadline'] = new MongoDB\BSON\UTCDateTime(strtotime($event['registration']['deadline']) * 1000);
            }
        }
        
        $insertResult = $collection->insertMany($events);
        echo "<p>Successfully inserted " . $insertResult->getInsertedCount() . " events</p>";
        
        // Display inserted events
        echo "<h3>Imported Events:</h3>";
        echo "<ul>";
        foreach ($events as $event) {
            echo "<li>" . htmlspecialchars($event['title']) . " - " . htmlspecialchars($event['eventType']) . " (" . htmlspecialchars($event['status']) . ")</li>";
        }
        echo "</ul>";
        
    } else {
        echo "<p>No events found in JSON file</p>";
    }
    
    echo "<p><strong>Import completed successfully!</strong></p>";
    echo "<p><a href='events.php'>View Events Page</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p>Import failed: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?> 