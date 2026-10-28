<?php
/**
 * Reads secret settings (the database connection string, API keys) from the environment
 * or from config/secrets.php, so none of them live in the repository.
 *
 * Copy config/secrets.example.php to config/secrets.php and fill it in; git ignores it.
 */
if (!function_exists('uiurp_secret')) {
    function uiurp_secret(string $envName, string $configKey, bool $required = true): string
    {
        $value = getenv($envName);
        if ($value !== false && $value !== '') {
            return $value;
        }

        static $config = null;
        if ($config === null) {
            $file = __DIR__ . '/../../config/secrets.php';
            $config = is_file($file) ? (require $file) : [];
        }
        if (!empty($config[$configKey])) {
            return $config[$configKey];
        }

        if ($required) {
            throw new RuntimeException(
                "Missing setting '{$configKey}'. Set the {$envName} environment variable, or copy "
                . 'config/secrets.example.php to config/secrets.php and fill it in.'
            );
        }
        return '';
    }
}
