<?php
require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Connect to MongoDB database
 *
 * @return MongoDB\Client
 */
function connectToDatabase() {
    // MongoDB connection URI
    $uri = require __DIR__ . '/../config/mongo_uri.php';
    
    // Create a new client and connect to the server
    $client = new MongoDB\Client($uri);
    
    return $client;
}
?> 