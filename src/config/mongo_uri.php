<?php
/**
 * Returns the MongoDB connection string, so no file in the repository holds the password.
 *
 * Reads the MONGODB_URI environment variable, or else config/database.php
 * (copy config/database.example.php and fill it in; it is ignored by git).
 *
 * Usage: new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
 */
if (!function_exists('uiurp_mongo_uri')) {
    function uiurp_mongo_uri(): string
    {
        $uri = getenv('MONGODB_URI');
        if ($uri !== false && $uri !== '') {
            return $uri;
        }

        $localConfig = __DIR__ . '/../../config/database.php';
        if (is_file($localConfig)) {
            $config = require $localConfig;
            if (!empty($config['mongodb_uri'])) {
                return $config['mongodb_uri'];
            }
        }

        throw new RuntimeException(
            'No database connection string. Set MONGODB_URI, or copy config/database.example.php '
            . 'to config/database.php and fill it in.'
        );
    }
}

return uiurp_mongo_uri();
