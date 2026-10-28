<?php
/**
 * MongoDB GridFS Upload Handler
 * 
 * Handles file uploads to MongoDB GridFS (cloud storage)
 * Files are stored in MongoDB Atlas and accessible from anywhere
 * 
 * Benefits:
 * - Works across different networks
 * - No local disk storage needed
 * - Automatic backups with MongoDB
 * - Real-time access for all users
 */

namespace UIURP\Model;

require_once __DIR__ . '/FileConfig.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Exception\Exception as MongoException;

class GridFSUploadHandler {
    
    private $db;
    private $bucket;
    
    /**
     * Initialize GridFS handler
     * 
     * @param MongoDB\Database $database MongoDB database instance
     */
    public function __construct($database) {
        $this->db = $database;
        
        // Create GridFS bucket (virtual filesystem in MongoDB)
        // Files stored in: fs.files (metadata) and fs.chunks (data)
        $this->bucket = $this->db->selectGridFSBucket([
            'bucketName' => 'fs',
            'chunkSizeBytes' => 261120, // 255KB chunks
        ]);
    }
    
    /**
     * Upload file to MongoDB GridFS
     * 
     * @param array $file $_FILES array element
     * @param array $metadata Additional metadata (projectId, uploadedBy, etc.)
     * @param array $options Upload options (requiredType, maxSize, etc.)
     * @return array ['success' => bool, 'message' => string, 'fileData' => array|null]
     */
    public function uploadToGridFS($file, $metadata = [], $options = []) {
        $response = [
            'success' => false,
            'message' => '',
            'fileData' => null
        ];
        
        try {
            // Validate file using FileConfig
            $requiredType = $options['requiredType'] ?? null;
            $maxSize = $options['maxSize'] ?? null;
            
            $validation = FileConfig::validateFile($file, $requiredType, $maxSize);
            
            if (!$validation['valid']) {
                $response['message'] = $validation['message'];
                return $response;
            }
            
            $fileInfo = $validation['fileInfo'];
            
            // Open file stream for upload
            $stream = fopen($file['tmp_name'], 'rb');
            if (!$stream) {
                throw new \Exception('Failed to open file for reading');
            }
            
            // Prepare metadata for GridFS
            $gridfsMetadata = [
                'contentType' => $fileInfo['mimeType'],
                'originalName' => $fileInfo['originalName'],
                'extension' => $fileInfo['extension'],
                'size' => $fileInfo['size'],
                'category' => $fileInfo['category'],
                'iconClass' => $fileInfo['iconClass'],
                'uploadDate' => new UTCDateTime(),
            ];
            
            // Add custom metadata
            if (isset($metadata['projectId'])) {
                $gridfsMetadata['projectId'] = $metadata['projectId'];
            }
            if (isset($metadata['uploadedBy'])) {
                $gridfsMetadata['uploadedBy'] = $metadata['uploadedBy'];
            }
            if (isset($metadata['uploadType'])) {
                $gridfsMetadata['uploadType'] = $metadata['uploadType'];
            }
            
            // Upload to GridFS
            $gridfsId = $this->bucket->uploadFromStream(
                $fileInfo['originalName'], // filename in GridFS
                $stream,
                ['metadata' => $gridfsMetadata]
            );
            
            fclose($stream);
            
            // Prepare response data
            $fileData = [
                'gridfs_id' => (string)$gridfsId,
                'name' => $fileInfo['originalName'],
                'size' => $fileInfo['size'],
                'type' => $fileInfo['mimeType'],
                'category' => $fileInfo['category'],
                'extension' => $fileInfo['extension'],
                'iconClass' => $fileInfo['iconClass'],
                'uploadedAt' => new UTCDateTime(),
                'storage' => 'gridfs' // Flag to indicate this is in GridFS
            ];
            
            $response['success'] = true;
            $response['message'] = 'File uploaded successfully to GridFS';
            $response['fileData'] = $fileData;
            
        } catch (MongoException $e) {
            $response['message'] = 'MongoDB error: ' . $e->getMessage();
            error_log('GridFS upload error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $response['message'] = 'Upload error: ' . $e->getMessage();
            error_log('GridFS upload error: ' . $e->getMessage());
        }
        
        return $response;
    }
    
    /**
     * Download file from GridFS
     * 
     * @param string $gridfsId GridFS file ID
     * @return resource|null File stream or null if not found
     */
    public function downloadFromGridFS($gridfsId) {
        try {
            $stream = $this->bucket->openDownloadStream(new ObjectId($gridfsId));
            return $stream;
        } catch (MongoException $e) {
            error_log('GridFS download error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get file metadata from GridFS
     * 
     * @param string $gridfsId GridFS file ID
     * @return array|null File metadata or null if not found
     */
    public function getFileInfo($gridfsId) {
        try {
            $file = $this->bucket->findOne(['_id' => new ObjectId($gridfsId)]);
            
            if (!$file) {
                return null;
            }
            
            // Convert to array format
            $metadata = $file->metadata ?? [];
            
            return [
                'gridfs_id' => (string)$file->_id,
                'filename' => $file->filename,
                'length' => $file->length,
                'chunkSize' => $file->chunkSize,
                'uploadDate' => $file->uploadDate,
                'contentType' => $metadata['contentType'] ?? 'application/octet-stream',
                'originalName' => $metadata['originalName'] ?? $file->filename,
                'extension' => $metadata['extension'] ?? '',
                'category' => $metadata['category'] ?? 'other',
                'iconClass' => $metadata['iconClass'] ?? 'bi-file-earmark',
                'projectId' => $metadata['projectId'] ?? null,
                'uploadedBy' => $metadata['uploadedBy'] ?? null,
            ];
        } catch (MongoException $e) {
            error_log('GridFS getFileInfo error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Delete file from GridFS
     * 
     * @param string $gridfsId GridFS file ID
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteFromGridFS($gridfsId) {
        $response = [
            'success' => false,
            'message' => ''
        ];
        
        try {
            $this->bucket->delete(new ObjectId($gridfsId));
            
            $response['success'] = true;
            $response['message'] = 'File deleted successfully from GridFS';
        } catch (MongoException $e) {
            $response['message'] = 'MongoDB error: ' . $e->getMessage();
            error_log('GridFS delete error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $response['message'] = 'Delete error: ' . $e->getMessage();
            error_log('GridFS delete error: ' . $e->getMessage());
        }
        
        return $response;
    }
    
    /**
     * Stream file from GridFS to browser
     * 
     * @param string $gridfsId GridFS file ID
     * @param string $disposition 'inline' or 'attachment'
     * @return bool Success status
     */
    public function streamToClient($gridfsId, $disposition = 'attachment') {
        try {
            // Get file info
            $fileInfo = $this->getFileInfo($gridfsId);
            
            if (!$fileInfo) {
                http_response_code(404);
                echo 'File not found';
                return false;
            }
            
            // Get file stream
            $stream = $this->downloadFromGridFS($gridfsId);
            
            if (!$stream) {
                http_response_code(500);
                echo 'Failed to open file stream';
                return false;
            }
            
            // Clear any previous output
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Set headers
            header('Content-Type: ' . $fileInfo['contentType']);
            header('Content-Length: ' . $fileInfo['length']);
            header('Content-Disposition: ' . $disposition . '; filename="' . $fileInfo['originalName'] . '"');
            header('Cache-Control: private, max-age=3600');
            header('X-Content-Type-Options: nosniff');
            
            // Stream file to browser
            while (!feof($stream)) {
                echo fread($stream, 8192);
                flush();
            }
            
            fclose($stream);
            return true;
            
        } catch (\Exception $e) {
            error_log('GridFS stream error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Error streaming file';
            return false;
        }
    }
    
    /**
     * Check if a file exists in GridFS
     * 
     * @param string $gridfsId GridFS file ID
     * @return bool
     */
    public function fileExists($gridfsId) {
        try {
            $file = $this->bucket->findOne(['_id' => new ObjectId($gridfsId)]);
            return $file !== null;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get total storage used in GridFS
     * 
     * @return array ['totalSize' => int, 'fileCount' => int, 'formatted' => string]
     */
    public function getStorageStats() {
        try {
            $files = $this->bucket->find();
            
            $totalSize = 0;
            $fileCount = 0;
            
            foreach ($files as $file) {
                $totalSize += $file->length;
                $fileCount++;
            }
            
            return [
                'totalSize' => $totalSize,
                'fileCount' => $fileCount,
                'formatted' => FileConfig::formatFileSize($totalSize)
            ];
        } catch (\Exception $e) {
            error_log('GridFS stats error: ' . $e->getMessage());
            return [
                'totalSize' => 0,
                'fileCount' => 0,
                'formatted' => '0 bytes'
            ];
        }
    }
}
