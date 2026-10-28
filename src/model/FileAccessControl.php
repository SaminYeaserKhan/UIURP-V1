<?php
/**
 * File Access Control
 * 
 * Manages authorization for file access based on:
 * - User authentication
 * - Project membership
 * - File ownership
 * - File visibility settings
 */

namespace UIURP\Model;

use MongoDB\BSON\ObjectId;

class FileAccessControl {
    
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Check if user can access a file
     * 
     * @param string $filePath Relative file path
     * @param string $userId Current user ID
     * @return array ['authorized' => bool, 'message' => string, 'fileData' => array|null]
     */
    public function checkFileAccess($filePath, $userId) {
        $response = [
            'authorized' => false,
            'message' => '',
            'fileData' => null
        ];
        
        // Normalize file path
        $filePath = ltrim($filePath, '/');
        
        // Determine file location and check access
        if (strpos($filePath, 'storage/files/') === 0 || strpos($filePath, 'storage/media/') === 0) {
            // Project files
            return $this->checkProjectFileAccess($filePath, $userId);
        } elseif (strpos($filePath, 'uploads/chat_files/') === 0) {
            // Chat files
            return $this->checkChatFileAccess($filePath, $userId);
        } elseif (strpos($filePath, 'uploads/forum_attachments/') === 0) {
            // Forum attachments
            return $this->checkForumFileAccess($filePath, $userId);
        } elseif (strpos($filePath, 'uploads/profile_images/') === 0) {
            // Profile images - generally public
            $response['authorized'] = true;
            $response['message'] = 'Profile images are publicly accessible';
            return $response;
        } else {
            $response['message'] = 'Invalid file path or location';
            return $response;
        }
    }
    
    /**
     * Check access to project files (storage/files/ and storage/media/)
     */
    private function checkProjectFileAccess($filePath, $userId) {
        $response = [
            'authorized' => false,
            'message' => '',
            'fileData' => null
        ];
        
        try {
            // Find project that contains this file
            $project = $this->db->projectsV2->findOne([
                '$or' => [
                    ['files.path' => '/' . $filePath],
                    ['files.path' => $filePath],
                    ['media.url' => '/' . $filePath],
                    ['media.url' => $filePath]
                ]
            ]);
            
            if (!$project) {
                // Try literature review files
                $literatureMatrix = $this->db->literature_matrix->findOne([
                    'files.path' => ['$in' => [$filePath, '/' . $filePath]]
                ]);
                
                if ($literatureMatrix && isset($literatureMatrix['projectId'])) {
                    $project = $this->db->projectsV2->findOne([
                        '_id' => $literatureMatrix['projectId']
                    ]);
                }
            }
            
            if (!$project) {
                $response['message'] = 'File not found in any project';
                return $response;
            }
            
            // Check if user has access to the project
            if ($this->userHasProjectAccess($project, $userId)) {
                $response['authorized'] = true;
                $response['message'] = 'Access granted';
                $response['fileData'] = [
                    'projectId' => (string)$project['_id'],
                    'projectTitle' => $project['title'] ?? 'Untitled Project'
                ];
            } else {
                $response['message'] = 'You do not have permission to access this file';
            }
            
        } catch (\Exception $e) {
            $response['message'] = 'Error checking file access: ' . $e->getMessage();
            error_log('FileAccessControl Error: ' . $e->getMessage());
        }
        
        return $response;
    }
    
    /**
     * Check access to chat files
     */
    private function checkChatFileAccess($filePath, $userId) {
        $response = [
            'authorized' => false,
            'message' => '',
            'fileData' => null
        ];
        
        try {
            // Extract project ID from chat file path
            // Format: uploads/chat_files/{projectId}/{filename}
            $pathParts = explode('/', $filePath);
            if (count($pathParts) < 3) {
                $response['message'] = 'Invalid chat file path';
                return $response;
            }
            
            $projectId = $pathParts[2];
            
            // Get project
            $project = $this->db->projectsV2->findOne(['_id' => new ObjectId($projectId)]);
            
            if (!$project) {
                $response['message'] = 'Project not found';
                return $response;
            }
            
            // Check if user has access to the project
            if ($this->userHasProjectAccess($project, $userId)) {
                $response['authorized'] = true;
                $response['message'] = 'Access granted';
                $response['fileData'] = [
                    'projectId' => (string)$project['_id'],
                    'projectTitle' => $project['title'] ?? 'Untitled Project'
                ];
            } else {
                $response['message'] = 'You do not have permission to access this file';
            }
            
        } catch (\Exception $e) {
            $response['message'] = 'Error checking chat file access: ' . $e->getMessage();
            error_log('FileAccessControl Error: ' . $e->getMessage());
        }
        
        return $response;
    }
    
    /**
     * Check access to forum attachments
     */
    private function checkForumFileAccess($filePath, $userId) {
        $response = [
            'authorized' => false,
            'message' => '',
            'fileData' => null
        ];
        
        try {
            // Find forum post that contains this file
            $post = $this->db->forum_posts->findOne([
                'attachments.file_path' => ['$in' => [$filePath, 'uploads/forum_attachments/' . basename($filePath)]]
            ]);
            
            if (!$post) {
                $response['message'] = 'Forum post not found';
                return $response;
            }
            
            // Forum posts are generally visible to all logged-in users
            // But you can add more specific permissions here if needed
            $response['authorized'] = true;
            $response['message'] = 'Access granted';
            $response['fileData'] = [
                'postId' => (string)$post['_id'],
                'postTitle' => $post['title'] ?? 'Forum Post'
            ];
            
        } catch (\Exception $e) {
            $response['message'] = 'Error checking forum file access: ' . $e->getMessage();
            error_log('FileAccessControl Error: ' . $e->getMessage());
        }
        
        return $response;
    }
    
    /**
     * Check if user has access to a project
     */
    private function userHasProjectAccess($project, $userId) {
        // Check if user is a member
        if (isset($project['members']) && is_array($project['members'])) {
            foreach ($project['members'] as $member) {
                $memberId = $this->extractUserId($member['userId'] ?? null);
                if ($memberId === $userId) {
                    return true;
                }
            }
        }
        
        // Check if user is supervisor
        if (isset($project['supervisor'])) {
            $supervisorId = $this->extractUserId($project['supervisor']['userId'] ?? $project['supervisor']);
            if ($supervisorId === $userId) {
                return true;
            }
        }
        
        // Check if user is creator
        if (isset($project['createdBy'])) {
            $creatorId = $this->extractUserId($project['createdBy']);
            if ($creatorId === $userId) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Extract user ID from various MongoDB formats
     */
    private function extractUserId($userIdField) {
        if (!$userIdField) {
            return null;
        }
        
        // Handle ObjectId
        if (is_object($userIdField) && $userIdField instanceof ObjectId) {
            return (string)$userIdField;
        }
        
        // Handle BSONDocument (access like array)
        if (is_object($userIdField) && get_class($userIdField) === 'MongoDB\Model\BSONDocument') {
            // BSONDocument can be accessed like an array
            if (isset($userIdField['$oid'])) {
                return $userIdField['$oid'];
            }
            // Try to convert the BSONDocument to array and extract
            $asArray = $userIdField->getArrayCopy();
            if (isset($asArray['$oid'])) {
                return $asArray['$oid'];
            }
            // If it's a simple BSONDocument wrapping an ObjectId
            if (isset($asArray[0]) && $asArray[0] instanceof ObjectId) {
                return (string)$asArray[0];
            }
        }
        
        // Handle array with $oid
        if (is_array($userIdField) && isset($userIdField['$oid'])) {
            return $userIdField['$oid'];
        }
        
        // Handle string
        if (is_string($userIdField)) {
            return $userIdField;
        }
        
        // Last resort: try to convert to string (may fail for complex objects)
        try {
            // For simple objects that can be cast to string
            if (method_exists($userIdField, '__toString')) {
                return (string)$userIdField;
            }
        } catch (\Exception $e) {
            error_log('Failed to extract user ID: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Log file access for audit trail
     */
    public function logFileAccess($userId, $filePath, $action = 'download', $success = true) {
        try {
            $this->db->file_access_log->insertOne([
                'userId' => new ObjectId($userId),
                'filePath' => $filePath,
                'action' => $action,
                'success' => $success,
                'timestamp' => new \MongoDB\BSON\UTCDateTime(),
                'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (\Exception $e) {
            error_log('Failed to log file access: ' . $e->getMessage());
        }
    }
}
