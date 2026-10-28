<?php
/**
 * Meeting Notification System
 * Checks for meetings that are about to start and sends system messages to project chats
 */

// Add CORS headers to allow cross-origin requests
$allowedOrigins = [
    'http://localhost:3000',
    'http://localhost',
    'http://localhost:80',
    'http://localhost:8080',
    'http://127.0.0.1:3000',
    'http://127.0.0.1',
    'http://127.0.0.1:80'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:3000';

// Check if origin is allowed, default to localhost:3000 if not found
if (!in_array($origin, $allowedOrigins)) {
    $origin = 'http://localhost:3000';
}

header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'send_system_chat_message_helper.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

/**
 * Check for meetings scheduled today and currently happening, send notifications
 * This function looks for:
 * - Meetings starting in the next 5 minutes (urgent)
 * - Meetings currently happening (live)
 * - Meetings scheduled for today (daily reminder)
 * 
 * @param string|null $specificProjectId Optional: Only check meetings for a specific project
 * @param string $notificationType Type of notification: 'all', 'urgent', 'live', 'daily'
 * @return array Results of the notification process
 */
function checkAndSendMeetingNotifications($specificProjectId = null, $notificationType = 'all') {
    try {
        // Connect to MongoDB
        $client = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
        $db = $client->uiurp;
        $meetingsCollection = $db->project_meetings;
        $notificationsCollection = $db->meeting_notifications; // Track sent notifications
        
        $results = [
            'success' => true,
            'notifications_sent' => 0,
            'errors' => [],
            'processed_meetings' => [],
            'notification_types' => []
        ];
        
        // Get current time
        $now = new DateTime();
        $currentDate = $now->format('Y-m-d');
        $currentTime = $now->format('H:i');
        
        // Define different notification scenarios
        $scenarios = [];
        
        if ($notificationType === 'all' || $notificationType === 'urgent') {
            // Urgent: Meetings starting in the next 5 minutes
            $notificationTime = new DateTime();
            $notificationTime->add(new DateInterval('PT5M'));
            $notificationTimeStr = $notificationTime->format('H:i');
            
            $scenarios['urgent'] = [
                'query' => [
                    'date' => $currentDate,
                    'status' => ['$ne' => 'Cancelled'],
                    'startTime' => [
                        '$gte' => $currentTime,
                        '$lte' => $notificationTimeStr
                    ]
                ],
                'notification_key' => 'urgent_' . $currentDate
            ];
        }
        
        if ($notificationType === 'all' || $notificationType === 'live') {
            // Live: Meetings currently happening
            $scenarios['live'] = [
                'query' => [
                    'date' => $currentDate,
                    'status' => ['$ne' => 'Cancelled'],
                    'startTime' => ['$lte' => $currentTime],
                    'endTime' => ['$gte' => $currentTime]
                ],
                'notification_key' => 'live_' . $currentDate . '_' . substr($currentTime, 0, 2) // Hour-based for live meetings
            ];
        }
        
        if ($notificationType === 'all' || $notificationType === 'daily') {
            // Daily: All meetings scheduled for today (sent once in the morning)
            $scenarios['daily'] = [
                'query' => [
                    'date' => $currentDate,
                    'status' => ['$ne' => 'Cancelled']
                ],
                'notification_key' => 'daily_' . $currentDate
            ];
        }
        
        // Process each scenario
        foreach ($scenarios as $scenarioType => $scenario) {
            $query = $scenario['query'];
            
            // If specific project is provided, filter by project
            if ($specificProjectId) {
                $query['projectId'] = new ObjectId($specificProjectId);
            }
            
            // Find meetings for this scenario
            $cursor = $meetingsCollection->find($query);
            
            foreach ($cursor as $meeting) {
                $meetingId = (string)$meeting['_id'];
                $projectId = (string)$meeting['projectId'];
                
                // Check if we already sent this type of notification for this meeting
                $notificationKey = $scenario['notification_key'] . '_' . $meetingId;
                $existingNotification = $notificationsCollection->findOne([
                    'notificationKey' => $notificationKey
                ]);
                
                if ($existingNotification) {
                    // Already sent this type of notification
                    continue;
                }
                
                // Send appropriate notification based on scenario
                $messageResult = sendScenarioNotification($scenarioType, $meeting, $projectId);
                
                if ($messageResult['success']) {
                    // Record that we sent the notification
                    $notificationsCollection->insertOne([
                        'notificationKey' => $notificationKey,
                        'meetingId' => new ObjectId($meetingId),
                        'projectId' => new ObjectId($projectId),
                        'scenarioType' => $scenarioType,
                        'notificationDate' => $currentDate,
                        'notificationTime' => $currentTime,
                        'messageId' => $messageResult['messageId'] ?? null,
                        'sentAt' => new UTCDateTime(),
                        'meetingTitle' => $meeting['title'] ?? 'Meeting',
                        'meetingStartTime' => $meeting['startTime']
                    ]);
                    
                    $results['notifications_sent']++;
                    $results['processed_meetings'][] = [
                        'meetingId' => $meetingId,
                        'projectId' => $projectId,
                        'title' => $meeting['title'] ?? 'Meeting',
                        'startTime' => $meeting['startTime'],
                        'scenarioType' => $scenarioType,
                        'status' => 'notification_sent'
                    ];
                    
                    if (!isset($results['notification_types'][$scenarioType])) {
                        $results['notification_types'][$scenarioType] = 0;
                    }
                    $results['notification_types'][$scenarioType]++;
                    
                } else {
                    $results['errors'][] = [
                        'meetingId' => $meetingId,
                        'projectId' => $projectId,
                        'title' => $meeting['title'] ?? 'Meeting',
                        'scenarioType' => $scenarioType,
                        'error' => $messageResult['message'] ?? 'Unknown error'
                    ];
                }
            }
        }
        

        
        return $results;
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error checking meetings: ' . $e->getMessage(),
            'notifications_sent' => 0,
            'errors' => [],
            'processed_meetings' => []
        ];
    }
}

/**
 * Send notification based on scenario type
 * 
 * @param string $scenarioType Type of scenario (urgent, live, daily)
 * @param object $meeting Meeting document from MongoDB
 * @param string $projectId Project ID
 * @return array Response from system message function
 */
function sendScenarioNotification($scenarioType, $meeting, $projectId) {
    $meetingTitle = $meeting['title'] ?? 'Team Meeting';
    $startTime = $meeting['startTime'];
    $endTime = $meeting['endTime'] ?? '';
    $meetingLink = $meeting['meetingLink'] ?? '';
    $date = $meeting['date'];
    
    switch ($scenarioType) {
        case 'urgent':
            // Meeting starting in 5 minutes
            return sendMeetingNotificationMessage($projectId, $meetingTitle, $startTime, $meetingLink, $date);
            
        case 'live':
            // Meeting currently happening
            return sendMeetingStartedMessage($projectId, $meetingTitle, $meetingLink);
            
        case 'daily':
            // Daily meeting reminder
            return sendDailyMeetingReminder($projectId, $meetingTitle, $startTime, $endTime, $meetingLink, $date);
            
        default:
            return ['success' => false, 'message' => 'Unknown scenario type'];
    }
}

/**
 * Send daily meeting reminder for all meetings scheduled today
 * 
 * @param string $projectId Project ID
 * @param string $meetingTitle Meeting title
 * @param string $startTime Start time
 * @param string $endTime End time
 * @param string $meetingLink Meeting link
 * @param string $date Meeting date
 * @return array Response from system message function
 */
function sendDailyMeetingReminder($projectId, $meetingTitle, $startTime, $endTime, $meetingLink, $date) {
    // Format times for display
    $startDateTime = DateTime::createFromFormat('H:i', $startTime);
    $formattedStartTime = $startDateTime ? $startDateTime->format('g:i A') : $startTime;
    
    $endDateTime = DateTime::createFromFormat('H:i', $endTime);
    $formattedEndTime = $endDateTime ? $endDateTime->format('g:i A') : $endTime;
    
    // Format date
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    $formattedDate = $dateObj ? $dateObj->format('l, M j, Y') : $date;
    
    // Determine if meeting is today
    $today = new DateTime();
    $isToday = $date === $today->format('Y-m-d');
    
    // Create message based on timing
    if ($isToday) {
        $messageText = "📅 **Meeting Scheduled Today!** 🗓️\n\n";
        $messageText .= "**\"" . htmlspecialchars($meetingTitle) . "\"**\n";
        $messageText .= "⏰ **Time:** $formattedStartTime";
        if ($endTime) {
            $messageText .= " - $formattedEndTime";
        }
        $messageText .= "\n📆 **Date:** $formattedDate\n\n";
        
        if (!empty($meetingLink)) {
            $messageText .= "🔗 **[Meeting Link]($meetingLink)**\n\n";
        }
        
        $messageText .= "📝 **Reminder:** Don't forget about your meeting today! You'll receive another notification 5 minutes before it starts.";
    } else {
        $messageText = "📅 **Upcoming Meeting Reminder** 🗓️\n\n";
        $messageText .= "**\"" . htmlspecialchars($meetingTitle) . "\"**\n";
        $messageText .= "⏰ **Time:** $formattedStartTime";
        if ($endTime) {
            $messageText .= " - $formattedEndTime";
        }
        $messageText .= "\n📆 **Date:** $formattedDate\n\n";
        
        if (!empty($meetingLink)) {
            $messageText .= "🔗 **[Meeting Link]($meetingLink)**\n\n";
        }
        
        $messageText .= "📝 **Note:** Meeting scheduled for today. Mark your calendar!";
    }
    
    return sendSystemChatMessage($projectId, $messageText);
}

/**
 * API endpoint handling for meeting notifications
 * Can be called via GET or POST, or from command line
 */
if (isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST')) {
    // Set JSON header
    header('Content-Type: application/json');
    
    // Check if user is logged in (optional for system processes)
    session_start();
    
    // Get parameters
    $projectId = null;
    $notificationType = 'all';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $projectId = $data['projectId'] ?? null;
        $notificationType = $data['notificationType'] ?? 'all';
    } else {
        $projectId = $_GET['projectId'] ?? null;
        $notificationType = $_GET['notificationType'] ?? 'all';
    }
    
    // Run the notification check
    $results = checkAndSendMeetingNotifications($projectId, $notificationType);
    
    // Return results
    echo json_encode($results);
    exit;
}

/**
 * Clean up old notification records (older than 7 days)
 * This prevents the notifications collection from growing indefinitely
 */
function cleanupOldNotifications() {
    try {
        $client = new MongoDB\Client(require __DIR__ . '/../config/mongo_uri.php');
        $db = $client->uiurp;
        $notificationsCollection = $db->meeting_notifications;
        
        // Delete notifications older than 7 days
        $cutoffDate = new DateTime();
        $cutoffDate->sub(new DateInterval('P7D'));
        $cutoffDateStr = $cutoffDate->format('Y-m-d');
        
        $result = $notificationsCollection->deleteMany([
            'notificationDate' => ['$lt' => $cutoffDateStr]
        ]);
        
        return [
            'success' => true,
            'deleted_count' => $result->getDeletedCount()
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error cleaning up notifications: ' . $e->getMessage()
        ];
    }
}

// If called directly with cleanup parameter, run cleanup
if (isset($_GET['cleanup']) && $_GET['cleanup'] === 'true') {
    header('Content-Type: application/json');
    echo json_encode(cleanupOldNotifications());
    exit;
}
?> 