<?php
/**
 * File Configuration and Validation
 * 
 * Centralized configuration for file uploads including:
 * - Allowed file types and MIME types
 * - File size limits per category
 * - Upload directories
 * - Validation rules
 */

namespace UIURP\Model;

class FileConfig {
    
    // Maximum file sizes (in bytes)
    const MAX_FILE_SIZE_DEFAULT = 10485760; // 10MB
    const MAX_FILE_SIZE_DOCUMENT = 26214400; // 25MB
    const MAX_FILE_SIZE_IMAGE = 5242880;     // 5MB
    const MAX_FILE_SIZE_VIDEO = 104857600;   // 100MB
    const MAX_FILE_SIZE_ARCHIVE = 52428800;  // 50MB
    
    // Blacklisted file extensions (executable files - NEVER allow)
    private static $blacklistedExtensions = [
        'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js',
        'jar', 'class', 'sh', 'bash', 'app', 'dmg', 'deb', 'rpm',
        'msi', 'dll', 'so', 'dylib', 'sys', 'drv', 'cpl',
        'ps1', 'psm1', 'ws', 'wsf', 'hta', 'gadget'
    ];
    
    // Upload directories (relative to project root)
    const DIR_PROJECT_FILES = 'storage/files/';
    const DIR_MEDIA = 'storage/media/';
    const DIR_CHAT_FILES = 'uploads/chat_files/';
    const DIR_FORUM_ATTACHMENTS = 'uploads/forum_attachments/';
    const DIR_PROFILE_IMAGES = 'uploads/profile_images/';
    const DIR_LITERATURE = 'storage/files/literature review/';
    
    /**
     * Allowed file types with their MIME types and extensions
     */
    private static $allowedTypes = [
        'document' => [
            'extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'odt', 'rtf'],
            'mime_types' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'text/csv',
                'application/vnd.oasis.opendocument.text',
                'application/rtf'
            ],
            'max_size' => self::MAX_FILE_SIZE_DOCUMENT,
            'icon_class' => 'bi-file-earmark-text'
        ],
        'image' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp'],
            'mime_types' => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/svg+xml',
                'image/webp',
                'image/bmp'
            ],
            'max_size' => self::MAX_FILE_SIZE_IMAGE,
            'icon_class' => 'bi-image'
        ],
        'video' => [
            'extensions' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'],
            'mime_types' => [
                'video/mp4',
                'video/x-msvideo',
                'video/quicktime',
                'video/x-ms-wmv',
                'video/x-flv',
                'video/webm',
                'video/x-matroska'
            ],
            'max_size' => self::MAX_FILE_SIZE_VIDEO,
            'icon_class' => 'bi-film'
        ],
        'audio' => [
            'extensions' => ['mp3', 'wav', 'ogg', 'flac', 'aac', 'm4a'],
            'mime_types' => [
                'audio/mpeg',
                'audio/wav',
                'audio/ogg',
                'audio/flac',
                'audio/aac',
                'audio/x-m4a'
            ],
            'max_size' => self::MAX_FILE_SIZE_DEFAULT,
            'icon_class' => 'bi-file-earmark-music'
        ],
        'archive' => [
            'extensions' => ['zip', 'rar', '7z', 'tar', 'gz'],
            'mime_types' => [
                'application/zip',
                'application/x-rar-compressed',
                'application/x-7z-compressed',
                'application/x-tar',
                'application/gzip'
            ],
            'max_size' => self::MAX_FILE_SIZE_ARCHIVE,
            'icon_class' => 'bi-file-earmark-zip'
        ]
    ];
    
    /**
     * Validate file upload
     * 
     * @param array $file $_FILES array element
     * @param string|null $requiredType Specific file type category (document, image, etc.)
     * @param int|null $customMaxSize Custom max size in bytes
     * @return array ['valid' => bool, 'message' => string, 'fileInfo' => array|null]
     */
    public static function validateFile($file, $requiredType = null, $customMaxSize = null) {
        $response = [
            'valid' => false,
            'message' => '',
            'fileInfo' => null
        ];
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $response['message'] = 'No file was uploaded or file upload failed';
            return $response;
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $response['message'] = self::getUploadErrorMessage($file['error']);
            return $response;
        }
        
        // Get file extension
        $fileName = $file['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // SECURITY: Check blacklisted extensions (executable files)
        if (in_array($fileExtension, self::$blacklistedExtensions)) {
            $response['message'] = "Executable files are not allowed for security reasons. Extension: $fileExtension";
            error_log("Blocked executable file upload attempt: $fileName");
            return $response;
        }
        
        // Detect MIME type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        // SECURITY: Additional MIME type checks for executables
        $dangerousMimeTypes = [
            'application/x-msdownload', 'application/x-msdos-program',
            'application/x-executable', 'application/x-sharedlib',
            'application/x-java-archive', 'application/java-archive'
        ];
        
        if (in_array($mimeType, $dangerousMimeTypes)) {
            $response['message'] = "Executable files are not allowed for security reasons. Type: $mimeType";
            error_log("Blocked executable file upload attempt by MIME: $fileName ($mimeType)");
            return $response;
        }
        
        // Find file category
        $fileCategory = self::detectFileCategory($fileExtension, $mimeType);
        
        if (!$fileCategory) {
            $response['message'] = "File type not allowed. Extension: $fileExtension, MIME: $mimeType";
            return $response;
        }
        
        // SECURITY: Sanitize SVG files (remove JavaScript)
        if ($fileExtension === 'svg') {
            self::sanitizeSVG($file['tmp_name']);
        }
        
        // Check if specific type is required
        if ($requiredType && $fileCategory !== $requiredType) {
            $response['message'] = "Invalid file type. Expected $requiredType, got $fileCategory";
            return $response;
        }
        
        // Get max file size
        $maxSize = $customMaxSize ?? self::$allowedTypes[$fileCategory]['max_size'];
        
        // Validate file size
        if ($file['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / (1024 * 1024), 2);
            $fileSizeMB = round($file['size'] / (1024 * 1024), 2);
            $response['message'] = "File size ($fileSizeMB MB) exceeds maximum allowed size ($maxSizeMB MB)";
            return $response;
        }
        
        // Validate file size is not zero
        if ($file['size'] === 0) {
            $response['message'] = 'File is empty';
            return $response;
        }
        
        // All validations passed
        $response['valid'] = true;
        $response['message'] = 'File validation successful';
        $response['fileInfo'] = [
            'originalName' => $fileName,
            'extension' => $fileExtension,
            'mimeType' => $mimeType,
            'size' => $file['size'],
            'category' => $fileCategory,
            'iconClass' => self::getIconClass($fileCategory, $fileExtension)
        ];
        
        return $response;
    }
    
    /**
     * Detect file category from extension and MIME type
     */
    private static function detectFileCategory($extension, $mimeType) {
        foreach (self::$allowedTypes as $category => $config) {
            if (in_array($extension, $config['extensions']) && 
                in_array($mimeType, $config['mime_types'])) {
                return $category;
            }
        }
        return null;
    }
    
    /**
     * Get appropriate icon class for file
     */
    public static function getIconClass($category, $extension = null) {
        // Specific icons for common document types
        if ($category === 'document') {
            switch ($extension) {
                case 'pdf':
                    return 'bi-file-earmark-pdf';
                case 'doc':
                case 'docx':
                    return 'bi-file-earmark-word';
                case 'xls':
                case 'xlsx':
                    return 'bi-file-earmark-excel';
                case 'ppt':
                case 'pptx':
                    return 'bi-file-earmark-slides';
                default:
                    return 'bi-file-earmark-text';
            }
        }
        
        return self::$allowedTypes[$category]['icon_class'] ?? 'bi-file-earmark';
    }
    
    /**
     * Generate unique filename
     */
    public static function generateUniqueFileName($originalName, $prefix = 'file') {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        return $prefix . '_' . uniqid() . '_' . time() . '.' . $extension;
    }
    
    /**
     * Get upload error message
     */
    private static function getUploadErrorMessage($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds PHP upload_max_filesize (' . ini_get('upload_max_filesize') . ')',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds the form MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by PHP extension'
        ];
        
        return $errors[$errorCode] ?? 'Unknown upload error';
    }
    
    /**
     * Get all allowed file types for a category
     */
    public static function getAllowedTypes($category = null) {
        if ($category) {
            return self::$allowedTypes[$category] ?? null;
        }
        return self::$allowedTypes;
    }
    
    /**
     * Format file size for display
     */
    public static function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
    
    /**
     * Ensure upload directory exists with proper permissions
     */
    public static function ensureDirectoryExists($directory) {
        if (!file_exists($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new \Exception('Failed to create upload directory: ' . $directory);
            }
        }
        
        if (!is_writable($directory)) {
            throw new \Exception('Upload directory is not writable: ' . $directory);
        }
        
        return true;
    }
    
    /**
     * Sanitize SVG files to remove JavaScript and event handlers
     * SECURITY: Prevents XSS attacks via SVG files
     */
    private static function sanitizeSVG($filePath) {
        try {
            $content = file_get_contents($filePath);
            
            if ($content === false) {
                return; // Can't read file, skip sanitization
            }
            
            // Remove script tags
            $content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $content);
            
            // Remove event handlers (onclick, onload, etc.)
            $content = preg_replace('/\son\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
            
            // Remove javascript: protocol
            $content = preg_replace('/javascript:/i', '', $content);
            
            // Remove data: URIs that might contain scripts
            $content = preg_replace('/data:text\/html[^"\'>\s]*/i', '', $content);
            
            // Write sanitized content back
            file_put_contents($filePath, $content);
            
            error_log("SVG file sanitized successfully: $filePath");
        } catch (\Exception $e) {
            error_log("Failed to sanitize SVG: " . $e->getMessage());
            // Don't fail the upload, just log the error
        }
    }
    
    /**
     * Get user-friendly file size and type disclaimer text
     */
    public static function getUploadDisclaimer($uploadType = 'default') {
        $disclaimers = [
            'document' => "Allowed: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, CSV (Max 25MB)",
            'image' => "Allowed: JPG, PNG, GIF, SVG, WEBP, BMP (Max 5MB)",
            'video' => "Allowed: MP4, AVI, MOV, WMV, WEBM, MKV (Max 100MB)",
            'archive' => "Allowed: ZIP, RAR, 7Z, TAR, GZ (Max 50MB)",
            'default' => "Allowed: Documents, Images, Videos, Audio, Archives (Max 10MB)",
            'all' => "Allowed file types: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, CSV, JPG, PNG, GIF, SVG, WEBP, MP4, AVI, MOV, MP3, WAV, ZIP, RAR, 7Z. Max sizes: Documents 25MB, Images 5MB, Videos 100MB, Archives 50MB. Executable files (.exe, .bat, .sh, etc.) are blocked for security."
        ];
        
        return $disclaimers[$uploadType] ?? $disclaimers['default'];
    }
}
