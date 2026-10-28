/**
 * Global Meeting Notification System
 * Runs automatically on all pages to check for meeting notifications
 */

class GlobalMeetingNotifications {
    constructor() {
        this.isRunning = false;
        this.checkInterval = null;
        this.lastCheck = null;
        this.checkIntervalSeconds = this.getRandomInterval(5, 10); // Check every 5-10 seconds
        this.projectId = null;
        
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initialize());
        } else {
            this.initialize();
        }
    }
    
    initialize() {
        // Try to get project ID from various sources
        this.detectProjectId();
        
        // Start the notification system
        this.startNotificationSystem();
        
        // Log initialization
        console.log('Global Meeting Notifications initialized', {
            projectId: this.projectId,
            checkInterval: this.checkIntervalSeconds + 's',
            page: window.location.pathname
        });
    }
    
    detectProjectId() {
        // Method 1: Try global projectId variable
        if (typeof projectId !== 'undefined' && projectId) {
            this.projectId = projectId;
            return;
        }
        
        // Method 2: Try to get from URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const urlProjectId = urlParams.get('projectId') || urlParams.get('id');
        if (urlProjectId) {
            this.projectId = urlProjectId;
            return;
        }
        
        // Method 3: Try to extract from page content or data attributes
        const projectElements = document.querySelectorAll('[data-project-id]');
        if (projectElements.length > 0) {
            this.projectId = projectElements[0].getAttribute('data-project-id');
            return;
        }
        
        // Method 4: Check if we're on a project-related page
        const path = window.location.pathname;
        if (path.includes('Project_details.php') || path.includes('project')) {
            // Try to extract project ID from page content
            const scripts = document.getElementsByTagName('script');
            for (let script of scripts) {
                const content = script.innerHTML;
                const match = content.match(/projectId\s*[=:]\s*['"]([^'"]+)['"]/);
                if (match) {
                    this.projectId = match[1];
                    return;
                }
            }
        }
        
        // If no project ID found, we'll check for all projects
        this.projectId = null;
    }
    
    getRandomInterval(min, max) {
        // Return a random number between min and max seconds
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }
    
    scheduleNextCheck() {
        // Clear any existing interval
        if (this.checkInterval) {
            clearTimeout(this.checkInterval);
        }
        
        // Schedule next check with randomized interval
        const nextInterval = this.getRandomInterval(5, 10) * 1000; // Convert to milliseconds
        this.checkInterval = setTimeout(() => {
            this.checkMeetingNotifications();
            // Schedule the next check
            this.scheduleNextCheck();
        }, nextInterval);
    }
    
    startNotificationSystem() {
        if (this.isRunning) return;
        
        this.isRunning = true;
        
        // Run initial check
        this.checkMeetingNotifications();
        
        // Set up periodic checks with randomized intervals
        this.scheduleNextCheck();
        
        // Also check when page becomes visible (user switches back to tab)
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.checkMeetingNotifications();
            }
        });
    }
    
    stopNotificationSystem() {
        if (this.checkInterval) {
            clearTimeout(this.checkInterval);
            this.checkInterval = null;
        }
        this.isRunning = false;
    }
    
    async checkMeetingNotifications() {
        // Prevent multiple simultaneous checks
        if (this.lastCheck && Date.now() - this.lastCheck < 3000) {
            return; // Skip if checked within last 3 seconds
        }
        
        this.lastCheck = Date.now();
        
        try {
            // Determine the appropriate notification URL
            let notificationUrl;
            if (window.location.port === '3000') {
                notificationUrl = `http://localhost/uiurp/src/model/send_meeting_notifications.php`;
            } else {
                notificationUrl = `src/model/send_meeting_notifications.php`;
            }
            
            // Prepare request data
            const requestData = {
                notificationType: 'all' // Check all types of notifications
            };
            
            // Include project ID if available
            if (this.projectId) {
                requestData.projectId = this.projectId;
            }
            
            const response = await fetch(notificationUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData),
                credentials: 'include'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.handleNotificationResults(data);
            } else {
                console.warn('Meeting notification check failed:', data.message);
            }
            
        } catch (error) {
            console.error('Error checking meeting notifications:', error);
        }
    }
    
    handleNotificationResults(data) {
        if (data.notifications_sent > 0) {
            console.log('Meeting notifications processed:', {
                total: data.notifications_sent,
                types: data.notification_types,
                meetings: data.processed_meetings
            });
            
            // Try to refresh chat if available
            this.refreshChatIfAvailable();
            
            // Show browser notification if supported and allowed
            this.showBrowserNotification(data);
            
            // Dispatch custom event for other scripts to listen to
            this.dispatchNotificationEvent(data);
        }
    }
    
    refreshChatIfAvailable() {
        // Try to refresh chat messages if the function exists
        if (typeof loadChatMessages === 'function') {
            setTimeout(() => {
                loadChatMessages();
            }, 1000);
        }
        
        // Also try to refresh project chat overlay if available
        if (typeof refreshProjectChat === 'function') {
            setTimeout(() => {
                refreshProjectChat();
            }, 1000);
        }
    }
    
    showBrowserNotification(data) {
        // Check if browser notifications are supported and allowed
        if ('Notification' in window && Notification.permission === 'granted') {
            const totalNotifications = data.notifications_sent;
            const types = data.notification_types;
            
            let title = 'Meeting Notification';
            let body = `${totalNotifications} meeting notification${totalNotifications > 1 ? 's' : ''} sent`;
            
            // Customize message based on notification types
            if (types.urgent) {
                title = 'Meeting Starting Soon!';
                body = `${types.urgent} meeting${types.urgent > 1 ? 's' : ''} starting in 5 minutes`;
            } else if (types.live) {
                title = 'Meeting Live Now!';
                body = `${types.live} meeting${types.live > 1 ? 's are' : ' is'} currently happening`;
            } else if (types.daily) {
                title = 'Meeting Reminder';
                body = `${types.daily} meeting${types.daily > 1 ? 's' : ''} scheduled for today`;
            }
            
            new Notification(title, {
                body: body,
                icon: '/assets/resources/UIURP.png', // Adjust path as needed
                tag: 'meeting-notification'
            });
        } else if ('Notification' in window && Notification.permission === 'default') {
            // Request permission for future notifications
            Notification.requestPermission();
        }
    }
    
    dispatchNotificationEvent(data) {
        // Dispatch custom event that other scripts can listen to
        const event = new CustomEvent('meetingNotificationsProcessed', {
            detail: {
                notificationsSent: data.notifications_sent,
                notificationTypes: data.notification_types,
                processedMeetings: data.processed_meetings,
                projectId: this.projectId
            }
        });
        
        document.dispatchEvent(event);
    }
    
    // Public method to manually trigger a check
    triggerCheck() {
        this.checkMeetingNotifications();
    }
    
    // Public method to update project ID
    setProjectId(projectId) {
        this.projectId = projectId;
        console.log('Project ID updated:', projectId);
    }
}

// Initialize the global notification system
const globalMeetingNotifications = new GlobalMeetingNotifications();

// Make it available globally for manual control
window.globalMeetingNotifications = globalMeetingNotifications;

// Listen for project ID updates from other scripts
document.addEventListener('projectIdChanged', (event) => {
    if (event.detail && event.detail.projectId) {
        globalMeetingNotifications.setProjectId(event.detail.projectId);
    }
});

// Export for modules if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = GlobalMeetingNotifications;
} 