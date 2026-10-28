<?php
/**
 * Centralized File Upload Handler
 * 
 * Provides consistent file upload handling across the application with:
 * - Validation using FileConfig
 * - Unique filename generation
 * - Directory management
 * - Error handling
 */

namespace UIURP\Model;

require_once __DIR__ . '/FileConfig.php';

use MongoDB\BSON\UTCDateTime;

class FileUploadHandler {
    
    private $uploadBaseDir;
    
    public function __construct($baseDir = null) {
        $this->uploadBaseDir = $baseDir ?? __DIR__ . '/../../';
    }
    
    /**
     * Handle single file upload
     * 
     * @param array $file $_FILES array element
     * @param string $uploadDir Upload directory (relative to base)
     * @param array $options Options: requiredType, maxSize, prefix
     * @return array ['success' => bool, 'message' => string, 'fileData' => array|null]
     */
    public function handleUpload($file, $uploadDir, $options = []) {
        $response = [
            'success' => false,
            'message' => '',
            'fileData' => null
        ];
        
        try {
            // Validate file
            $requiredType = $options['requiredType'] ?? null;
            $maxSize = $options['maxSize'] ?? null;
            
            $validation = FileConfig::validateFile($file, $requiredType, $maxSize);
            
            if (!$validation['valid']) {
                $response['message'] = $validation['message'];
                return $response;
            }
            
            $fileInfo = $validation['fileInfo'];
            
            // Generate unique filename
            $prefix = $options['prefix'] ?? 'file';
            $uniqueFileName = FileConfig::generateUniqueFileName($fileInfo['originalName'], $prefix);
            
            // Ensure upload directory exists
            $fullUploadDir = $this->uploadBaseDir . $uploadDir;
            FileConfig::ensureDirectoryExists($fullUploadDir);
            
            // Move uploaded file
            $targetPath = $fullUploadDir . $uniqueFileName;
            
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                $error = error_get_last();
                throw new \Exception('Failed to move uploaded file: ' . ($error['message'] ?? 'Unknown error'));
            }
            
            // Prepare file data
            $relativePath = $uploadDir . $uniqueFileName;
            
            $fileData = [
                'name' => $fileInfo['originalName'],
                'storedName' => $uniqueFileName,
                'path' => '/' . $relativePath,
                'size' => $fileInfo['size'],
                'type' => $fileInfo['mimeType'],
                'category' => $fileInfo['category'],
                'extension' => $fileInfo['extension'],
                'iconClass' => $fileInfo['iconClass'],
                'uploadedAt' => new UTCDateTime()
            ];
            
            $response['success'] = true;
            $response['message'] = 'File uploaded successfully';
            $response['fileData'] = $fileData;
            
        } catch (\Exception $e) {
            $response['message'] = 'Upload error: ' . $e->getMessage();
            error_log('FileUploadHandler Error: ' . $e->getMessage());
        }
        
        return $response;
    }
    
    /**
     * Handle multiple file uploads
     * 
     * @param array $files $_FILES array for multiple files
     * @param string $uploadDir Upload directory
     * @param array $options Upload options
     * @return array ['success' => bool, 'message' => string, 'files' => array, 'errors' => array]
     */
    public function handleMultipleUploads($files, $uploadDir, $options = []) {
        $response = [
            'success' => true,
            'message' => '',
            'files' => [],
            'errors' => []
        ];
        
        $fileCount = count($files['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            // Reorganize file array for single file processing
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            $result = $this->handleUpload($file, $uploadDir, $options);
            
            if ($result['success']) {
                $response['files'][] = $result['fileData'];
            } else {
                $response['errors'][] = [
                    'fileName' => $file['name'],
                    'message' => $result['message']
                ];
            }
        }
        
        // If any file failed, mark as partial success
        if (!empty($response['errors'])) {
            $response['success'] = !empty($response['files']); // Success if at least one file uploaded
            $response['message'] = count($response['files']) . ' file(s) uploaded successfully, ' . 
                                   count($response['errors']) . ' failed';
        } else {
            $response['message'] = count($response['files']) . ' file(s) uploaded successfully';
        }
        
        return $response;
    }
    
    /**
     * Delete a file
     * 
     * @param string $filePath Relative file path
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteFile($filePath) {
        $response = [
            'success' => false,
            'message' => ''
        ];
        
        try {
            // Remove leading slash if present
            $filePath = ltrim($filePath, '/');
            
            // Construct absolute path
            $absolutePath = $this->uploadBaseDir . $filePath;
            
            // Security check: ensure file is within allowed directories
            $realPath = realpath($absolutePath);
            $baseRealPath = realpath($this->uploadBaseDir);
            
            if (!$realPath || strpos($realPath, $baseRealPath) !== 0) {
                $response['message'] = 'Invalid file path';
                return $response;
            }
            
            // Check if file exists
            if (!file_exists($absolutePath)) {
                $response['message'] = 'File not found';
                return $response;
            }
            
            // Delete file
            if (unlink($absolutePath)) {
                $response['success'] = true;
                $response['message'] = 'File deleted successfully';
            } else {
                $response['message'] = 'Failed to delete file';
            }
            
        } catch (\Exception $e) {
            $response['message'] = 'Delete error: ' . $e->getMessage();
            error_log('FileUploadHandler Delete Error: ' . $e->getMessage());
        }
        
        return $response;
    }
    
    /**
     * Get file information
     * 
     * @param string $filePath Relative file path
     * @return array ['exists' => bool, 'info' => array|null]
     */
    public function getFileInfo($filePath) {
        $filePath = ltrim($filePath, '/');
        $absolutePath = $this->uploadBaseDir . $filePath;
        
        if (!file_exists($absolutePath)) {
            return ['exists' => false, 'info' => null];
        }
        
        $info = [
            'name' => basename($absolutePath),
            'size' => filesize($absolutePath),
            'extension' => pathinfo($absolutePath, PATHINFO_EXTENSION),
            'modified' => filemtime($absolutePath),
            'path' => $filePath
        ];
        
        return ['exists' => true, 'info' => $info];
    }
}
