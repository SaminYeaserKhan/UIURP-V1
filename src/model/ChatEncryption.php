<?php
/**
 * Chat Message Encryption Utility
 * 
 * Provides AES-256-GCM encryption for group chat messages
 * with project-specific keys and secure key management
 */

class ChatEncryption {
    
    private const CIPHER = 'aes-256-gcm';
    private const KEY_LENGTH = 32; // 256 bits
    private const IV_LENGTH = 12;  // 96 bits for GCM
    private const TAG_LENGTH = 16; // 128 bits
    
    /**
     * Generate a new encryption key for a project
     * 
     * @return string Base64 encoded encryption key
     */
    public static function generateProjectKey() {
        return base64_encode(random_bytes(self::KEY_LENGTH));
    }
    
    /**
     * Encrypt a message using project-specific key
     * 
     * @param string $message The message to encrypt
     * @param string $projectKey Base64 encoded project key
     * @return array Encrypted data with IV, tag, and ciphertext
     */
    public static function encryptMessage($message, $projectKey) {
        try {
            // Decode the project key
            $key = base64_decode($projectKey);
            if (strlen($key) !== self::KEY_LENGTH) {
                throw new Exception('Invalid project key length');
            }
            
            // Generate random IV
            $iv = random_bytes(self::IV_LENGTH);
            
            // Encrypt the message
            $ciphertext = openssl_encrypt(
                $message,
                self::CIPHER,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );
            
            if ($ciphertext === false) {
                throw new Exception('Encryption failed: ' . openssl_error_string());
            }
            
            return [
                'ciphertext' => base64_encode($ciphertext),
                'iv' => base64_encode($iv),
                'tag' => base64_encode($tag),
                'encrypted' => true
            ];
            
        } catch (Exception $e) {
            error_log("ChatEncryption::encryptMessage error: " . $e->getMessage());
            throw new Exception('Message encryption failed');
        }
    }
    
    /**
     * Decrypt a message using project-specific key
     * 
     * @param array $encryptedData Encrypted data with IV, tag, and ciphertext
     * @param string $projectKey Base64 encoded project key
     * @return string Decrypted message
     */
    public static function decryptMessage($encryptedData, $projectKey) {
        try {
            // Validate encrypted data structure
            if (!isset($encryptedData['ciphertext']) && !isset($encryptedData['message'])) {
                throw new Exception('Invalid encrypted data structure - no ciphertext or message field');
            }
            
            if (!isset($encryptedData['iv']) || !isset($encryptedData['tag'])) {
                throw new Exception('Invalid encrypted data structure - missing IV or tag');
            }
            
            // Decode the project key
            $key = base64_decode($projectKey);
            if (strlen($key) !== self::KEY_LENGTH) {
                throw new Exception('Invalid project key length');
            }
            
            // Decode encrypted components
            $ciphertext = base64_decode($encryptedData['ciphertext'] ?? $encryptedData['message']);
            $iv = base64_decode($encryptedData['iv']);
            $tag = base64_decode($encryptedData['tag']);
            
            // Decrypt the message
            $message = openssl_decrypt(
                $ciphertext,
                self::CIPHER,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );
            
            if ($message === false) {
                throw new Exception('Decryption failed: ' . openssl_error_string());
            }
            
            return $message;
            
        } catch (Exception $e) {
            error_log("ChatEncryption::decryptMessage error: " . $e->getMessage());
            throw new Exception('Message decryption failed');
        }
    }
    
    /**
     * Check if a message is encrypted
     * 
     * @param array $messageData Message data from database
     * @return bool True if message is encrypted
     */
    public static function isEncrypted($messageData) {
        return isset($messageData['encrypted']) && 
               ($messageData['encrypted'] === true || $messageData['encrypted'] === 1) &&
               isset($messageData['message']) &&
               isset($messageData['iv']) &&
               isset($messageData['tag']);
    }
    
    /**
     * Get or create project encryption key
     * 
     * @param string $projectId MongoDB ObjectId of the project
     * @param MongoDB\Database $db Database connection
     * @return string Base64 encoded project key
     */
    public static function getProjectKey($projectId, $db) {
        try {
            // Check if project has an encryption key
            $project = $db->projectsV2->findOne(
                ['_id' => new MongoDB\BSON\ObjectId($projectId)],
                ['projection' => ['encryptionKey' => 1]]
            );
            
            if ($project && isset($project['encryptionKey'])) {
                return $project['encryptionKey'];
            }
            
            // Generate new key for project
            $newKey = self::generateProjectKey();
            
            // Store key in project document
            $db->projectsV2->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($projectId)],
                ['$set' => ['encryptionKey' => $newKey]]
            );
            
            return $newKey;
            
        } catch (Exception $e) {
            error_log("ChatEncryption::getProjectKey error: " . $e->getMessage());
            throw new Exception('Failed to get project encryption key');
        }
    }
    
    /**
     * Rotate project encryption key (for enhanced security)
     * 
     * @param string $projectId MongoDB ObjectId of the project
     * @param MongoDB\Database $db Database connection
     * @return string New base64 encoded project key
     */
    public static function rotateProjectKey($projectId, $db) {
        try {
            // Generate new key
            $newKey = self::generateProjectKey();
            
            // Store old key for migration purposes
            $oldKey = self::getProjectKey($projectId, $db);
            
            // Update project with new key
            $db->projectsV2->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($projectId)],
                [
                    '$set' => [
                        'encryptionKey' => $newKey,
                        'previousEncryptionKey' => $oldKey,
                        'keyRotatedAt' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            
            return $newKey;
            
        } catch (Exception $e) {
            error_log("ChatEncryption::rotateProjectKey error: " . $e->getMessage());
            throw new Exception('Failed to rotate project encryption key');
        }
    }
    
    /**
     * Migrate old unencrypted messages to encrypted format
     * 
     * @param string $projectId MongoDB ObjectId of the project
     * @param MongoDB\Database $db Database connection
     * @return int Number of messages migrated
     */
    public static function migrateMessagesToEncrypted($projectId, $db) {
        try {
            $projectKey = self::getProjectKey($projectId, $db);
            $migratedCount = 0;
            
            // Find all unencrypted messages for this project
            $messages = $db->project_chat_messages->find([
                'projectId' => new MongoDB\BSON\ObjectId($projectId),
                '$or' => [
                    ['encrypted' => ['$ne' => true]],
                    ['encrypted' => ['$exists' => false]]
                ]
            ]);
            
            foreach ($messages as $message) {
                // Skip system messages
                if (isset($message['isSystemMessage']) && $message['isSystemMessage']) {
                    continue;
                }
                
                // Encrypt the message
                $encryptedData = self::encryptMessage($message['message'], $projectKey);
                
                // Update the message with encrypted data
                $db->project_chat_messages->updateOne(
                    ['_id' => $message['_id']],
                    [
                        '$set' => [
                            'message' => $encryptedData['ciphertext'],
                            'encrypted' => true,
                            'iv' => $encryptedData['iv'],
                            'tag' => $encryptedData['tag']
                        ]
                    ]
                );
                
                $migratedCount++;
            }
            
            return $migratedCount;
            
        } catch (Exception $e) {
            error_log("ChatEncryption::migrateMessagesToEncrypted error: " . $e->getMessage());
            throw new Exception('Failed to migrate messages to encrypted format');
        }
    }
    
    /**
     * Validate encryption configuration
     * 
     * @return bool True if encryption is properly configured
     */
    public static function validateConfiguration() {
        // Check if OpenSSL is available
        if (!extension_loaded('openssl')) {
            return false;
        }
        
        // Check if required cipher is available
        $ciphers = openssl_get_cipher_methods();
        if (!in_array(self::CIPHER, $ciphers)) {
            return false;
        }
        
        return true;
    }
}
?>
