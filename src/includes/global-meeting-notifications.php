<?php
/**
 * Global Meeting Notifications Include
 * Add this file to any page to enable automatic meeting notifications
 * 
 * Usage: include 'src/includes/global-meeting-notifications.php';
 */

// Determine the correct path to the JavaScript file
$base_path = '';
if (defined('INCLUDED_IN_PROJECT_DETAILS')) {
    $base_path = ''; // Already in root
} else {
    // Try to determine if we're in a subdirectory
    $current_dir = dirname($_SERVER['PHP_SELF']);
    if ($current_dir !== '/') {
        $depth = substr_count($current_dir, '/');
        $base_path = str_repeat('../', $depth);
    }
}

// Output the JavaScript include
echo '<script src="' . $base_path . 'assets/js/global-meeting-notifications.js?v=' . time() . '"></script>' . "\n";

// Add inline script to handle any page-specific setup
?>
<script>
// Global Meeting Notifications Setup
document.addEventListener('DOMContentLoaded', function() {
    // If we're on a page with a known project ID, update the notification system
    <?php if (isset($_GET['id']) && !empty($_GET['id'])): ?>
    if (window.globalMeetingNotifications) {
        window.globalMeetingNotifications.setProjectId('<?php echo htmlspecialchars($_GET['id']); ?>');
    }
    <?php endif; ?>
    
    <?php if (isset($_GET['projectId']) && !empty($_GET['projectId'])): ?>
    if (window.globalMeetingNotifications) {
        window.globalMeetingNotifications.setProjectId('<?php echo htmlspecialchars($_GET['projectId']); ?>');
    }
    <?php endif; ?>
    
    // Listen for meeting notifications and show console info
    document.addEventListener('meetingNotificationsProcessed', function(event) {
        console.log('Meeting notifications processed on page:', window.location.pathname, event.detail);
    });
});

// Function to manually trigger notification check (for debugging)
function checkMeetingNotifications() {
    if (window.globalMeetingNotifications) {
        window.globalMeetingNotifications.triggerCheck();
    } else {
        console.warn('Global meeting notifications not initialized');
    }
}
</script>
<?php
// Optional: Add notification permission request for better user experience
?>
<script>
// Request notification permissions on user interaction
document.addEventListener('click', function requestNotificationPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission().then(function(permission) {
            if (permission === 'granted') {
                console.log('Notification permission granted');
            }
        });
        // Remove this listener after first click
        document.removeEventListener('click', requestNotificationPermission);
    }
}, { once: true });
</script> 