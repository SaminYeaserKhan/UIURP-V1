<?php
/**
 * Project Key Management System
 * 
 * Manages encryption keys for project group chats
 * Handles key generation, rotation, and access control
 */

require_once 'ChatEncryption.php';

class ProjectKeyManager {
    
    /**
     * Initialize encryption for a project
     * 
     * @param string $projectId MongoDB ObjectId of the project
     * @param MongoDB\Database $db Database connection
     * @return array Response with success status and key info
     */
    public static function initializeProjectEncryption($projectId, $db) {
        try {
            // Check if project exists
            $project = $db->projectsV2->findOne(
                ['_id' => new MongoDB\BSON\ObjectId($projectId)],
                ['projection' => ['_id' => 1, 'encryptionKey' => 1, 'encryptionEnabled' => 1]]
            );
            
            if (!$project) {
                return [
                    'success' => false,
                    'message' => 'Project not found'
                ];
            }
            
            // Check if encryption is already enabled
            if (isset($project['encryptionEnabled']) && $project['encryptionEnabled']) {
                return [
                    'success' => true,
                    'message' => 'Encryption already enabled for this project',
                    'keyExists' => isset($project['encryptionKey'])
                ];
            }
            
            // Generate encryption key
            $encryptionKey = ChatEncryption::generateProjectKey();
            
            // Enable encryption for the project
            $result = $db->projectsV2->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($projectId)],
                [
                    '$set' => [
                        'encryptionKey' => $encryptionKey,
                        'encryptionEnabled' => true,
                        'encryptionEnabledAt' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            
            if ($result->getModifiedCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Encryption enabled for project',
                    'keyGenerated' => true
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to enable encryption'
                ];
            }
            
        } catch (Exception $e) {
            error_log("ProjectKeyManager::initializeProjectEncryption error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error initializing project encryption: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if user has access to project encryption key
     * 
     * @param string $projectId MongoDB ObjectId of the project
     * @param string $userId MongoDB ObjectId of the user
     * @param MongoDB\Database $db Database connection
     * @return bool True if user has access
     */
    public static function userHasKeyAccess($projectId, $userId, $db) {
        try {
            // Allow system access for system messages
            if ($userId === '000000000000000000000000') {
                return true;
            }
            
            // Get project with member information
            $project = $db->projectsV2->findOne(
                ['_id' => new MongoDB\BSON\ObjectId($projectId)],
                [
                    'projection' => [
                        'createdBy' => 1,
                        'members' => 1,
                        'supervisor' => 1,
                        'encryptionEnabled' => 1
                    ]
                ]
            );
            
            if (!$project) {
                return false;
            }
            
            // Check if encryption is enabled
            if (!isset($project['encryptionEnabled']) || !$project['encryptionEnabled']) {
                return false;
            }
            
            // Check if user is creator
            if (isset($project['createdBy']) && 
                (string)$project['createdBy'] === $userId) {
                return true;
            }
            
            // Check if user is supervisor
            if (isset($project['supervisor']['userId']) && 
                (string)$project['supervisor']['userId'] === $userId) {
                return true;
            }
            
            // Check if user is a member
            if (isset($project['members'])) {
                foreach ($project['members'] as $member) {
                    if (isset($member['userId']) && 
                        (string)$member['userId'] === $userId) {
                        return true;
                    }
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("ProjectKeyManager::userHasKeyAccess error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get project encryption key (with access control)
     * 
     * @param string $projectId MongoDB ObjectId of the project
     * @param string $userId MongoDB ObjectId of the user
     * @param MongoDB\Database $db Database connection
     * @return array Response with success status and key
     */
    public static function getProjectKey($projectId, $userId, $db) {
        try {
            // Check if user has access
            if (!self::userHasKeyAccess($projectId, $userId, $db)) {
                return [
                    'success' => false,
                    'message' => 'Access denied: You do not have permission to access this project\'s encryption key'
                ];
            }
            
            // Get the encryption key
            $key = ChatEncryption::getProjectKey($projectId, $db);
            
            return [
                'success' => true,
                'key' => $key,
                'message' => 'Key retrieved successfully'
            ];
            
        } catch (Exception $e) {
            error_log("ProjectKeyManager::getProjectKey error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error retrieving project key: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Rotate project encryption key
     * 
     * @param string $projectId MongoDB ObjectId of the project
     * @param string $userId MongoDB ObjectId of the user (must be creator or supervisor)
     * @param MongoDB\Database $db Database connection
     * @return array Response with success status
     */
    public static function rotateProjectKey($projectId, $userId, $db) {
        try {
            // Check if user has admin access (creator or supervisor)
            $project = $db->projectsV2->findOne(
                ['_id' => new MongoDB\BSON\ObjectId($projectId)],
                [
                    'projection' => [
                        'createdBy' => 1,
                        'supervisor' => 1,
                        'encryptionEnabled' => 1
                    ]
                ]
            );
            
            if (!$project) {
                return [
                    'success' => false,
                    'message' => 'Project not found'
                ];
            }
            
            // Check if encryption is enabled
            if (!isset($project['encryptionEnabled']) || !$project['encryptionEnabled']) {
                return [
                    'success' => false,
                    'message' => 'Encryption is not enabled for this project'
                ];
            }
            
            // Check if user is creator or supervisor
            $isCreator = isset($project['createdBy']) && (string)$project['createdBy'] === $userId;
            $isSupervisor = isset($project['supervisor']['userId']) && 
                           (string)$project['supervisor']['userId'] === $userId;
            
            if (!$isCreator && !$isSupervisor) {
                return [
                    'success' => false,
                    'message' => 'Access denied: Only project creators and supervisors can rotate encryption keys'
                ];
            }
            
            // Rotate the key
            $newKey = ChatEncryption::rotateProjectKey($projectId, $db);
            
            return [
                'success' => true,
                'message' => 'Encryption key rotated successfully',
                'newKey' => $newKey
            ];
            
        } catch (Exception $e) {
            error_log("ProjectKeyManager::rotateProjectKey error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error rotating project key: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Disable encryption for a project (admin only)
     * 
     * @param string $projectId MongoDB ObjectId of the project
     * @param string $userId MongoDB ObjectId of the user (must be creator)
     * @param MongoDB\Database $db Database connection
     * @return array Response with success status
     */
    public static function disableProjectEncryption($projectId, $userId, $db) {
        try {
            // Check if user is creator
            $project = $db->projectsV2->findOne(
                ['_id' => new MongoDB\BSON\ObjectId($projectId)],
                ['projection' => ['createdBy' => 1, 'encryptionEnabled' => 1]]
            );
            
            if (!$project) {
                return [
                    'success' => false,
                    'message' => 'Project not found'
                ];
            }
            
            if (!isset($project['createdBy']) || (string)$project['createdBy'] !== $userId) {
                return [
                    'success' => false,
                    'message' => 'Access denied: Only project creators can disable encryption'
                ];
            }
            
            // Disable encryption
            $result = $db->projectsV2->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($projectId)],
                [
                    '$set' => [
                        'encryptionEnabled' => false,
                        'encryptionDisabledAt' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            
            if ($result->getModifiedCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Encryption disabled for project'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to disable encryption'
                ];
            }
            
        } catch (Exception $e) {
            error_log("ProjectKeyManager::disableProjectEncryption error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error disabling project encryption: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get encryption status for a project
     * 
     * @param string $projectId MongoDB ObjectId of the project
     * @param MongoDB\Database $db Database connection
     * @return array Encryption status information
     */
    public static function getEncryptionStatus($projectId, $db) {
        try {
            $project = $db->projectsV2->findOne(
                ['_id' => new MongoDB\BSON\ObjectId($projectId)],
                [
                    'projection' => [
                        'encryptionEnabled' => 1,
                        'encryptionEnabledAt' => 1,
                        'encryptionDisabledAt' => 1,
                        'keyRotatedAt' => 1
                    ]
                ]
            );
            
            if (!$project) {
                return [
                    'success' => false,
                    'message' => 'Project not found'
                ];
            }
            
            return [
                'success' => true,
                'encryptionEnabled' => $project['encryptionEnabled'] ?? false,
                'enabledAt' => isset($project['encryptionEnabledAt']) ? 
                    $project['encryptionEnabledAt']->toDateTime()->format('Y-m-d H:i:s') : null,
                'disabledAt' => isset($project['encryptionDisabledAt']) ? 
                    $project['encryptionDisabledAt']->toDateTime()->format('Y-m-d H:i:s') : null,
                'lastKeyRotation' => isset($project['keyRotatedAt']) ? 
                    $project['keyRotatedAt']->toDateTime()->format('Y-m-d H:i:s') : null
            ];
            
        } catch (Exception $e) {
            error_log("ProjectKeyManager::getEncryptionStatus error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error getting encryption status: ' . $e->getMessage()
            ];
        }
    }
}
?>
