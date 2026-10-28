/**
 * Download Helper
 * 
 * JavaScript helper functions for secure file downloads
 * using the download proxy system
 */

const DownloadHelper = {
    /**
     * Download a file through the secure proxy
     * @param {string} filePath - Relative file path (e.g., 'storage/files/file.pdf')
     * @param {boolean} inline - Whether to display inline (true) or download (false)
     */
    downloadFile: function(filePath, inline = false) {
        // Remove leading slash if present
        filePath = filePath.replace(/^\//, '');
        
        // Build URL
        let url = '/download.php?file=' + encodeURIComponent(filePath);
        if (inline) {
            url += '&inline=1';
        }
        
        // Navigate to download URL
        window.location.href = url;
    },
    
    /**
     * Download file using fetch API for better error handling
     * @param {string} filePath - Relative file path
     * @param {boolean} inline - Whether to display inline
     * @returns {Promise}
     */
    downloadFileAsync: async function(filePath, inline = false) {
        try {
            // Remove leading slash if present
            filePath = filePath.replace(/^\//, '');
            
            // Build URL
            let url = '/download.php?file=' + encodeURIComponent(filePath);
            if (inline) {
                url += '&inline=1';
            }
            
            // Fetch file
            const response = await fetch(url);
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(errorText || 'Download failed: ' + response.statusText);
            }
            
            // Get filename from Content-Disposition header or use basename
            let fileName = filePath.split('/').pop();
            const contentDisposition = response.headers.get('Content-Disposition');
            if (contentDisposition) {
                const matches = /filename="?([^"]+)"?/i.exec(contentDisposition);
                if (matches && matches[1]) {
                    fileName = matches[1];
                }
            }
            
            // Create blob and download
            const blob = await response.blob();
            const blobUrl = window.URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = blobUrl;
            a.download = fileName;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            
            // Clean up
            window.URL.revokeObjectURL(blobUrl);
            
            return { success: true, message: 'Download started' };
            
        } catch (error) {
            console.error('Download error:', error);
            return { success: false, message: error.message };
        }
    },
    
    /**
     * Open file in new tab (for PDFs, images, etc.)
     * @param {string} filePath - Relative file path
     */
    viewFile: function(filePath) {
        // Remove leading slash if present
        filePath = filePath.replace(/^\//, '');
        
        // Build URL with inline parameter
        const url = '/download.php?file=' + encodeURIComponent(filePath) + '&inline=1';
        
        // Open in new tab
        window.open(url, '_blank');
    },
    
    /**
     * Get secure download URL for a file
     * @param {string} filePath - Relative file path
     * @param {boolean} inline - Whether to display inline
     * @returns {string} - Full download URL
     */
    getDownloadUrl: function(filePath, inline = false) {
        // Remove leading slash if present
        filePath = filePath.replace(/^\//, '');
        
        // Build URL
        let url = '/download.php?file=' + encodeURIComponent(filePath);
        if (inline) {
            url += '&inline=1';
        }
        
        return url;
    },
    
    /**
     * Update all direct file links on the page to use download proxy
     * Call this after dynamically loading content
     */
    updateFileLinks: function() {
        // Find all links to storage/ or uploads/
        const links = document.querySelectorAll('a[href*="storage/"], a[href*="uploads/"]');
        
        links.forEach(link => {
            const href = link.getAttribute('href');
            
            // Skip if already using download.php
            if (href.includes('download.php')) {
                return;
            }
            
            // Extract file path
            let filePath = href;
            if (filePath.startsWith('/')) {
                filePath = filePath.substring(1);
            }
            
            // Check if it's a storage or uploads path
            if (filePath.startsWith('storage/') || filePath.startsWith('uploads/')) {
                // Update link to use download proxy
                const newHref = '/download.php?file=' + encodeURIComponent(filePath);
                link.setAttribute('href', newHref);
                
                // Add data attribute to track original path
                link.setAttribute('data-original-path', filePath);
            }
        });
    },
    
    /**
     * Format file size for display
     * @param {number} bytes - File size in bytes
     * @returns {string} - Formatted file size
     */
    formatFileSize: function(bytes) {
        if (bytes >= 1073741824) {
            return (bytes / 1073741824).toFixed(2) + ' GB';
        } else if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + ' MB';
        } else if (bytes >= 1024) {
            return (bytes / 1024).toFixed(2) + ' KB';
        } else {
            return bytes + ' bytes';
        }
    },
    
    /**
     * Get appropriate icon class for file type
     * @param {string} fileType - File MIME type or extension
     * @returns {string} - Bootstrap icon class
     */
    getFileIcon: function(fileType) {
        const type = fileType.toLowerCase();
        
        // Document types
        if (type.includes('pdf') || type === 'pdf') {
            return 'bi-file-earmark-pdf';
        }
        if (type.includes('word') || type === 'doc' || type === 'docx') {
            return 'bi-file-earmark-word';
        }
        if (type.includes('excel') || type === 'xls' || type === 'xlsx') {
            return 'bi-file-earmark-excel';
        }
        if (type.includes('powerpoint') || type === 'ppt' || type === 'pptx') {
            return 'bi-file-earmark-slides';
        }
        
        // Media types
        if (type.includes('image') || ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'].includes(type)) {
            return 'bi-image';
        }
        if (type.includes('video') || ['mp4', 'avi', 'mov', 'webm'].includes(type)) {
            return 'bi-film';
        }
        if (type.includes('audio') || ['mp3', 'wav', 'ogg'].includes(type)) {
            return 'bi-file-earmark-music';
        }
        
        // Archive types
        if (['zip', 'rar', '7z', 'tar', 'gz'].includes(type)) {
            return 'bi-file-earmark-zip';
        }
        
        // Text types
        if (type.includes('text') || type === 'txt') {
            return 'bi-file-earmark-text';
        }
        
        // Default
        return 'bi-file-earmark';
    }
};

// Make globally available
if (typeof window !== 'undefined') {
    window.DownloadHelper = DownloadHelper;
}

// Auto-update links on page load
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', function() {
        // Automatically update file links on page load
        // Uncomment the line below to enable automatic link updates
        // DownloadHelper.updateFileLinks();
    });
}
