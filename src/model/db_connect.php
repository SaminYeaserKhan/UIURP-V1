<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\Driver\Exception\ConnectionException;
use MongoDB\Driver\Exception\ConnectionTimeoutException;

/**
 * Connect to MongoDB database
 *
 * @return MongoDB\Client
 */
function connectToDatabase() {
    try {
        // Enable error reporting
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // MongoDB connection URI with timeout settings
        $uri = require __DIR__ . '/../config/mongo_uri.php';
        
        // Create a new client with options
        $client = new Client($uri, [
            'serverSelectionTimeoutMS' => 30000,
            'connectTimeoutMS' => 30000,
            'socketTimeoutMS' => 30000
        ]);
        
        // Test the connection
        $client->listDatabases();
        
        return $client;
    } catch (ConnectionTimeoutException $e) {
        error_log("MongoDB Connection Timeout: " . $e->getMessage());
        throw new Exception("Failed to connect to database. Please try again later.");
    } catch (Exception $e) {
        error_log("MongoDB Connection Error: " . $e->getMessage());
        throw new Exception("Database connection error. Please try again later.");
    }
} 