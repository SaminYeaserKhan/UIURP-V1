<?php
/**
 * Encryption Configuration
 * 
 * Centralized configuration for chat encryption settings
 */

class EncryptionConfig {
    
    /**
     * Whether to automatically enable encryption for new projects
     * Set to true to enable encryption by default for all new projects
     * Set to false to require manual enabling per project
     */
    const AUTO_ENABLE_FOR_NEW_PROJECTS = true;
    
    /**
     * Whether to automatically migrate existing messages when encryption is enabled
     * Set to true to automatically encrypt existing messages
     * Set to false to leave existing messages unencrypted
     */
    const AUTO_MIGRATE_EXISTING_MESSAGES = true;
    
    /**
     * Default encryption algorithm
     */
    const DEFAULT_ALGORITHM = 'aes-256-gcm';
    
    /**
     * Key rotation interval in days (0 = no automatic rotation)
     * Set to 0 to disable automatic key rotation
     */
    const KEY_ROTATION_INTERVAL_DAYS = 0;
    
    /**
     * Whether to show encryption status in chat UI
     */
    const SHOW_ENCRYPTION_STATUS = true;
    
    /**
     * Whether to allow users to disable encryption (project creators/supervisors only)
     */
    const ALLOW_DISABLE_ENCRYPTION = true;
    
    /**
     * Whether to allow key rotation (project creators/supervisors only)
     */
    const ALLOW_KEY_ROTATION = true;
    
    /**
     * Get configuration value
     * 
     * @param string $key Configuration key
     * @return mixed Configuration value
     */
    public static function get($key) {
        $reflection = new ReflectionClass(self::class);
        return $reflection->getConstant($key);
    }
    
    /**
     * Check if auto-enable is enabled
     * 
     * @return bool True if auto-enable is enabled
     */
    public static function isAutoEnableEnabled() {
        return self::get('AUTO_ENABLE_FOR_NEW_PROJECTS');
    }
    
    /**
     * Check if auto-migration is enabled
     * 
     * @return bool True if auto-migration is enabled
     */
    public static function isAutoMigrationEnabled() {
        return self::get('AUTO_MIGRATE_EXISTING_MESSAGES');
    }
    
    /**
     * Get key rotation interval in days
     * 
     * @return int Key rotation interval in days
     */
    public static function getKeyRotationInterval() {
        return self::get('KEY_ROTATION_INTERVAL_DAYS');
    }
}
?>
