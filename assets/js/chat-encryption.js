/**
 * Client-side Chat Encryption Utilities
 * 
 * Provides encryption/decryption for real-time chat messages
 * Uses Web Crypto API for secure client-side encryption
 */

class ChatEncryption {
    
    constructor() {
        this.algorithm = 'AES-GCM';
        this.keyLength = 256;
        this.ivLength = 12; // 96 bits for GCM
    }
    
    /**
     * Generate a random encryption key
     * @returns {Promise<CryptoKey>} Generated encryption key
     */
    async generateKey() {
        return await window.crypto.subtle.generateKey(
            {
                name: this.algorithm,
                length: this.keyLength
            },
            true, // extractable
            ['encrypt', 'decrypt']
        );
    }
    
    /**
     * Import a key from base64 string
     * @param {string} base64Key Base64 encoded key
     * @returns {Promise<CryptoKey>} Imported encryption key
     */
    async importKey(base64Key) {
        const keyData = this.base64ToArrayBuffer(base64Key);
        return await window.crypto.subtle.importKey(
            'raw',
            keyData,
            { name: this.algorithm },
            false,
            ['encrypt', 'decrypt']
        );
    }
    
    /**
     * Export a key to base64 string
     * @param {CryptoKey} key Encryption key
     * @returns {Promise<string>} Base64 encoded key
     */
    async exportKey(key) {
        const keyData = await window.crypto.subtle.exportKey('raw', key);
        return this.arrayBufferToBase64(keyData);
    }
    
    /**
     * Encrypt a message
     * @param {string} message Message to encrypt
     * @param {CryptoKey} key Encryption key
     * @returns {Promise<Object>} Encrypted data with IV and ciphertext
     */
    async encryptMessage(message, key) {
        try {
            const iv = window.crypto.getRandomValues(new Uint8Array(this.ivLength));
            const encodedMessage = new TextEncoder().encode(message);
            
            const ciphertext = await window.crypto.subtle.encrypt(
                {
                    name: this.algorithm,
                    iv: iv
                },
                key,
                encodedMessage
            );
            
            return {
                ciphertext: this.arrayBufferToBase64(ciphertext),
                iv: this.arrayBufferToBase64(iv),
                encrypted: true
            };
        } catch (error) {
            console.error('Encryption failed:', error);
            throw new Error('Message encryption failed');
        }
    }
    
    /**
     * Decrypt a message
     * @param {Object} encryptedData Encrypted data with IV and ciphertext
     * @param {CryptoKey} key Decryption key
     * @returns {Promise<string>} Decrypted message
     */
    async decryptMessage(encryptedData, key) {
        try {
            const iv = this.base64ToArrayBuffer(encryptedData.iv);
            const ciphertext = this.base64ToArrayBuffer(encryptedData.ciphertext);
            
            const decryptedData = await window.crypto.subtle.decrypt(
                {
                    name: this.algorithm,
                    iv: iv
                },
                key,
                ciphertext
            );
            
            return new TextDecoder().decode(decryptedData);
        } catch (error) {
            console.error('Decryption failed:', error);
            throw new Error('Message decryption failed');
        }
    }
    
    /**
     * Check if a message is encrypted
     * @param {Object} messageData Message data
     * @returns {boolean} True if message is encrypted
     */
    isEncrypted(messageData) {
        return messageData.encrypted === true && 
               messageData.ciphertext && 
               messageData.iv;
    }
    
    /**
     * Convert ArrayBuffer to base64 string
     * @param {ArrayBuffer} buffer ArrayBuffer to convert
     * @returns {string} Base64 encoded string
     */
    arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary);
    }
    
    /**
     * Convert base64 string to ArrayBuffer
     * @param {string} base64 Base64 encoded string
     * @returns {ArrayBuffer} ArrayBuffer
     */
    base64ToArrayBuffer(base64) {
        const binary = atob(base64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes.buffer;
    }
}

/**
 * Chat Encryption Manager
 * 
 * Manages encryption for chat messages with project-specific keys
 */
class ChatEncryptionManager {
    
    constructor() {
        this.encryption = new ChatEncryption();
        this.projectKeys = new Map(); // Cache for project keys
        this.currentProjectId = null;
    }
    
    /**
     * Set the current project ID
     * @param {string} projectId Project ID
     */
    setCurrentProject(projectId) {
        this.currentProjectId = projectId;
    }
    
    /**
     * Get project encryption key
     * @param {string} projectId Project ID
     * @returns {Promise<CryptoKey|null>} Project encryption key or null
     */
    async getProjectKey(projectId) {
        // Check cache first
        if (this.projectKeys.has(projectId)) {
            return this.projectKeys.get(projectId);
        }
        
        try {
            // Fetch key from server
            const response = await fetch(`src/model/get_project_encryption_key.php?projectId=${projectId}`);
            const data = await response.json();
            
            if (data.success && data.key) {
                const key = await this.encryption.importKey(data.key);
                this.projectKeys.set(projectId, key);
                return key;
            }
            
            return null;
        } catch (error) {
            console.error('Failed to get project key:', error);
            return null;
        }
    }
    
    /**
     * Encrypt a message for the current project
     * @param {string} message Message to encrypt
     * @returns {Promise<Object>} Encrypted message data
     */
    async encryptMessage(message) {
        if (!this.currentProjectId) {
            throw new Error('No current project set');
        }
        
        const key = await this.getProjectKey(this.currentProjectId);
        if (!key) {
            throw new Error('No encryption key available for project');
        }
        
        return await this.encryption.encryptMessage(message, key);
    }
    
    /**
     * Decrypt a message from the current project
     * @param {Object} messageData Message data with encryption info
     * @returns {Promise<string>} Decrypted message
     */
    async decryptMessage(messageData) {
        if (!this.encryption.isEncrypted(messageData)) {
            return messageData.message;
        }
        
        if (!this.currentProjectId) {
            throw new Error('No current project set');
        }
        
        const key = await this.getProjectKey(this.currentProjectId);
        if (!key) {
            throw new Error('No encryption key available for project');
        }
        
        return await this.encryption.decryptMessage(messageData, key);
    }
    
    /**
     * Check if encryption is available for the current project
     * @returns {Promise<boolean>} True if encryption is available
     */
    async isEncryptionAvailable() {
        if (!this.currentProjectId) {
            return false;
        }
        
        const key = await this.getProjectKey(this.currentProjectId);
        return key !== null;
    }
    
    /**
     * Clear project key cache
     * @param {string} projectId Project ID to clear (optional)
     */
    clearKeyCache(projectId = null) {
        if (projectId) {
            this.projectKeys.delete(projectId);
        } else {
            this.projectKeys.clear();
        }
    }
}

// Global encryption manager instance
window.chatEncryptionManager = new ChatEncryptionManager();

/**
 * Enhanced chat message sending with encryption
 */
async function sendEncryptedMessage(projectId, messageText) {
    try {
        // Set current project
        window.chatEncryptionManager.setCurrentProject(projectId);
        
        // Check if encryption is available
        const isEncryptionAvailable = await window.chatEncryptionManager.isEncryptionAvailable();
        
        if (isEncryptionAvailable) {
            // Encrypt message on client side
            const encryptedData = await window.chatEncryptionManager.encryptMessage(messageText);
            
            // Send encrypted message to server
            const formData = new FormData();
            formData.append('projectId', projectId);
            formData.append('message', encryptedData.ciphertext);
            formData.append('iv', encryptedData.iv);
            formData.append('encrypted', 'true');
            
            const response = await fetch('src/model/send_chat_message.php', {
                method: 'POST',
                body: formData
            });
            
            return await response.json();
        } else {
            // Send unencrypted message
            const formData = new FormData();
            formData.append('projectId', projectId);
            formData.append('message', messageText);
            
            const response = await fetch('src/model/send_chat_message.php', {
                method: 'POST',
                body: formData
            });
            
            return await response.json();
        }
    } catch (error) {
        console.error('Failed to send encrypted message:', error);
        return {
            success: false,
            message: 'Failed to send message: ' + error.message
        };
    }
}

/**
 * Enhanced message display with decryption
 */
async function displayEncryptedMessage(messageElement, messageData) {
    try {
        // Check if message is encrypted
        if (messageData.isEncrypted && window.chatEncryptionManager.currentProjectId) {
            // Decrypt message
            const decryptedMessage = await window.chatEncryptionManager.decryptMessage(messageData);
            messageElement.textContent = decryptedMessage;
            
            // Add encryption indicator
            if (!messageElement.querySelector('.encryption-indicator')) {
                const indicator = document.createElement('span');
                indicator.className = 'encryption-indicator';
                indicator.innerHTML = ' 🔒';
                indicator.title = 'End-to-end encrypted message';
                messageElement.appendChild(indicator);
            }
        } else {
            // Display unencrypted message
            messageElement.textContent = messageData.message;
        }
    } catch (error) {
        console.error('Failed to decrypt message:', error);
        messageElement.textContent = '[Encrypted message - unable to decrypt]';
        messageElement.className += ' decryption-error';
    }
}

// Export for use in other scripts
window.ChatEncryption = ChatEncryption;
window.ChatEncryptionManager = ChatEncryptionManager;
window.sendEncryptedMessage = sendEncryptedMessage;
window.displayEncryptedMessage = displayEncryptedMessage;
