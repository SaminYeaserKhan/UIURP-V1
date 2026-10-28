<?php
require __DIR__ . '/../../vendor/autoload.php'; // Updated path to Composer's autoloader

try {
    // Connect to MongoDB using the provided connection string
    $mongoClient = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
    $db = $mongoClient->uiurp; // Select the database
    $collection = $db->resources; // Select the 'resources' collection

    $name = $_GET['name'] ?? ''; // Get the 'name' parameter from the query string
    $image = $collection->findOne(['name' => $name]); // Query the collection for the document

    if ($image) {
        echo json_encode(['url' => $image['url']]); // Return the image URL if found
    } else {
        echo json_encode(['url' => null, 'error' => 'Image not found']); // Return an error if not found
    }
} catch (Exception $e) {
    echo json_encode(['url' => null, 'error' => $e->getMessage()]); // Handle exceptions and return the error message
}
?>
