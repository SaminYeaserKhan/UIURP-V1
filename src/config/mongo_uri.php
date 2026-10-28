<?php
/**
 * Returns the MongoDB connection string from MONGODB_URI or config/secrets.php,
 * so no file in the repository holds the password.
 *
 * Usage: new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
 */
require_once __DIR__ . '/secrets.php';

return uiurp_secret('MONGODB_URI', 'mongodb_uri');
