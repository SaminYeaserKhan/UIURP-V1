<?php
// Only run these functions if not already defined in the parent file (events.php)
if (!function_exists('connectToMongoDB')) {
// Function to connect to MongoDB
function connectToMongoDB() {
    try {
        $mongoClient = new MongoDB\Client(require __DIR__ . '/src/config/mongo_uri.php');
        return $mongoClient->uiurp;
    } catch (Exception $e) {
        error_log("MongoDB connection failed: " . $e->getMessage());
        return null;
        }
    }
}

if (!function_exists('getAllEventsFromMongoDB')) {
// Function to get all events
function getAllEventsFromMongoDB() {
    $db = connectToMongoDB();
    if (!$db) {
        return [];
    }
    
    try {
        $collection = $db->events;
        $cursor = $collection->find([], ['sort' => ['eventDate' => 1]]);
        $events = [];
        
        foreach ($cursor as $event) {
            // Convert MongoDB document to array
            $eventArray = json_decode(json_encode($event), true);
            
            // Helper function to convert MongoDB date - improved to handle more formats
            $convertDate = function($dateValue) {
                if (is_array($dateValue) && isset($dateValue['$date'])) {
                    // Format 1: MongoDB Extended JSON v2 - $date is a string (ISO date)
                    if (is_string($dateValue['$date'])) {
                        return $dateValue['$date'];
                    }
                    // Format 2: MongoDB Legacy Extended JSON - $date with $numberLong
                    elseif (is_array($dateValue['$date']) && isset($dateValue['$date']['$numberLong'])) {
                        return date('Y-m-d\TH:i:s\Z', intval($dateValue['$date']['$numberLong']) / 1000);
                    }
                    // Format 3: MongoDB Legacy - $date is numeric (timestamp in milliseconds)
                    elseif (is_numeric($dateValue['$date'])) {
                        return date('Y-m-d\TH:i:s\Z', $dateValue['$date'] / 1000);
                    }
                }
                return $dateValue;
            };
            
            // Convert MongoDB UTCDateTime objects to readable dates
            if (isset($eventArray['eventDate'])) {
                $eventArray['eventDate'] = $convertDate($eventArray['eventDate']);
                
                // Ensure we have a valid date for calendar display
                if (is_string($eventArray['eventDate'])) {
                    try {
                        $dateObj = new DateTime($eventArray['eventDate']);
                        $eventArray['datePart'] = $dateObj->format('Y-m-d');
                    } catch (Exception $e) {
                        error_log("Date parsing error: " . $e->getMessage());
                        continue;  // Skip this event if date parsing fails
                    }
                }
            }
            
            $events[] = $eventArray;
        }
        
        return $events;
    } catch (Exception $e) {
        error_log("Error fetching events: " . $e->getMessage());
        return [];
        }
    }
}

// Get all events data if not already fetched in events.php
if (!isset($events) || empty($events)) {
$allEvents = getAllEventsFromMongoDB();
} else {
    $allEvents = $events; // Use events already fetched in events.php
}

// Skip debug logging when included in other files

// Format events for calendar display
$calendarEvents = [];
foreach ($allEvents as $event) {
    if (isset($event['eventDate'])) {
        try {
            $date = new DateTime($event['eventDate']);
            $formattedDate = $date->format('Y-m-d');
            
            $eventForCalendar = [
                'title' => $event['title'],
                'date' => $formattedDate,
                'type' => $event['eventType'] ?? 'Event',
                'id' => isset($event['_id_string']) ? $event['_id_string'] : (string)$event['_id']
            ];
            $calendarEvents[] = $eventForCalendar;
            
            // Skip debug logging
        } catch (Exception $e) {
            error_log("Error formatting event date: " . $e->getMessage());
        }
    }
}

// Skip debug logging for calendar events
?>

<!-- Simple Calendar Overlay -->
<div id="calendarOverlay" class="calendar-overlay">
    <div class="calendar-overlay-bg"></div>
    <div class="calendar-overlay-content">
        <div class="calendar-header">
            <h3><i class="bi bi-calendar-week me-2"></i>Event Calendar</h3>
            <button class="close-calendar-btn">&times;</button>
        </div>
        <div class="calendar-container">
            <div id="fullCalendar"></div>
            <div id="eventsList" class="events-list">
                <h4><i class="bi bi-list-ul me-2"></i>Upcoming Events</h4>
                <div id="eventsListContent"></div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Calendar Styles -->
<style>
.calendar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    opacity: 0;
    transition: opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.calendar-overlay.active {
    opacity: 1;
}

.calendar-overlay-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle at center, rgba(10, 17, 33, 0.5) 0%, rgba(0, 0, 0, 0.7) 100%);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.calendar-overlay-content {
    position: relative;
    width: 80%;
    max-width: 900px;
    margin: 30px auto;
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.85) 0%, rgba(15, 23, 42, 0.95) 100%);
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.75), 
                0 0 0 1px rgba(255, 255, 255, 0.12),
                inset 0 0 0 1px rgba(255, 255, 255, 0.05),
                inset 0 0 30px rgba(76, 201, 240, 0.06);
    transform: translateY(50px) scale(0.95);
    opacity: 0;
    transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1), 
                opacity 0.5s cubic-bezier(0.16, 1, 0.3, 1),
                box-shadow 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    overflow: hidden;
    max-height: 85vh;
    filter: blur(5px);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    /* Add futuristic border glow */
    position: relative;
}

/* Light Theme Styles for Calendar Overlay */
[data-theme="light"] .calendar-overlay-bg {
    background: radial-gradient(circle at center, rgba(255, 255, 255, 0.5) 0%, rgba(240, 240, 250, 0.7) 100%);
}

[data-theme="light"] .calendar-overlay-content {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(240, 240, 250, 0.95) 100%);
    box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.2), 
                0 0 0 1px rgba(67, 97, 238, 0.1),
                inset 0 0 0 1px rgba(255, 255, 255, 0.8),
                inset 0 0 30px rgba(76, 201, 240, 0.06);
}

[data-theme="light"] .calendar-header h3 {
    background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

[data-theme="light"] .close-calendar-btn {
    background: rgba(0, 0, 0, 0.05);
    color: #1e293b;
    box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.08),
                0 4px 8px rgba(0, 0, 0, 0.05);
}

[data-theme="light"] .close-calendar-btn::after {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(245, 245, 250, 0.98) 100%);
}

[data-theme="light"] .close-calendar-btn:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 10px 20px -5px rgba(67, 97, 238, 0.3),
                inset 0 0 0 1px rgba(67, 97, 238, 0.2);
    color: rgba(30, 41, 59, 0.95);
}

[data-theme="light"] #fullCalendar {
    background: rgba(255, 255, 255, 0.6);
    box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.1), 
                inset 0 0 0 1px rgba(67, 97, 238, 0.08),
                inset 0 0 20px rgba(255, 255, 255, 0.5);
}

[data-theme="light"] #fullCalendar::before {
    background: linear-gradient(135deg, rgba(67, 97, 238, 0.05), rgba(114, 9, 183, 0.05));
}

[data-theme="light"] .events-list {
    background: rgba(255, 255, 255, 0.7);
    box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.1), 
                inset 0 0 0 1px rgba(67, 97, 238, 0.08),
                inset 0 0 20px rgba(255, 255, 255, 0.5);
    color: #1e293b;
}

[data-theme="light"] .events-list::before {
    background: linear-gradient(135deg, rgba(67, 97, 238, 0.05), rgba(114, 9, 183, 0.05));
}

[data-theme="light"] .events-list h4 {
    background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

[data-theme="light"] .calendar-header::after {
    background: linear-gradient(90deg, 
                transparent, 
                rgba(67, 97, 238, 0.3), 
                rgba(114, 9, 183, 0.3), 
                rgba(67, 97, 238, 0.3), 
                transparent);
}

[data-theme="light"] .event-list-item {
    background: rgba(67, 97, 238, 0.06);
    border-left: 4px solid;
    border-image: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%) 1;
    color: #1e293b;
}

[data-theme="light"] .event-list-item:hover {
    background: rgba(67, 97, 238, 0.1);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

[data-theme="light"] .event-list-item.highlighted {
    background: rgba(67, 97, 238, 0.15);
    border-left-color: #4361ee;
    box-shadow: 0 10px 30px rgba(67, 97, 238, 0.3), 
                0 0 0 2px rgba(67, 97, 238, 0.2);
}

[data-theme="light"] .event-list-item-title {
    color: #1e293b;
}

[data-theme="light"] .event-list-item-date {
    color: rgba(30, 41, 59, 0.7);
}

[data-theme="light"] .event-list-item-type {
    color: rgba(67, 97, 238, 0.9);
}

[data-theme="light"] .no-events {
    color: rgba(30, 41, 59, 0.6);
}

[data-theme="light"] #fullCalendar .fc-toolbar {
    background: rgba(255, 255, 255, 0.7);
}

[data-theme="light"] #fullCalendar .fc-button {
    background: rgba(67, 97, 238, 0.08) !important;
    border-color: rgba(67, 97, 238, 0.15) !important;
    color: #1e293b !important;
}

[data-theme="light"] #fullCalendar .fc-button:hover {
    background: rgba(67, 97, 238, 0.12) !important;
}

[data-theme="light"] #fullCalendar .fc-button-active {
    background: linear-gradient(135deg, rgba(67, 97, 238, 0.3) 0%, rgba(58, 12, 163, 0.3) 100%) !important;
    box-shadow: 0 5px 15px -3px rgba(67, 97, 238, 0.2) !important;
    color: #1e293b !important;
}

[data-theme="light"] #fullCalendar .fc-toolbar-title {
    background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

[data-theme="light"] #fullCalendar .fc-today-button {
    background: linear-gradient(135deg, rgba(67, 97, 238, 0.2) 0%, rgba(58, 12, 163, 0.2) 100%) !important;
}

[data-theme="light"] #fullCalendar .fc-col-header-cell {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 240, 250, 0.9) 100%) !important;
}

[data-theme="light"] #fullCalendar .fc-col-header-cell-cushion {
    color: #1e293b;
    background: linear-gradient(135deg, rgba(67, 97, 238, 0.08) 0%, rgba(58, 12, 163, 0.04) 100%);
    text-shadow: none;
}

[data-theme="light"] #fullCalendar .fc-daygrid-day,
[data-theme="light"] #fullCalendar .fc-scrollgrid-sync-table td,
[data-theme="light"] #fullCalendar .fc-daygrid-day.fc-day {
    background: #ffffff !important;
    border: 1px solid rgba(67, 97, 238, 0.05) !important;
}

[data-theme="light"] #fullCalendar .fc-daygrid-day-frame,
[data-theme="light"] #fullCalendar .fc-scrollgrid-sync-inner {
    background: #ffffff !important;
}

[data-theme="light"] #fullCalendar .fc-daygrid-day-bg {
    background: transparent !important;
}

[data-theme="light"] #fullCalendar .fc-daygrid-day:hover,
[data-theme="light"] #fullCalendar .fc-daygrid-day.fc-day:hover {
    background: #ffffff !important;
    box-shadow: 0 2px 10px rgba(67, 97, 238, 0.08);
}

[data-theme="light"] #fullCalendar .fc-day-other,
[data-theme="light"] #fullCalendar .fc-day-other .fc-daygrid-day-frame,
[data-theme="light"] #fullCalendar .fc-day-other.fc-day {
    background: #f8fafc !important;
}

[data-theme="light"] #fullCalendar .fc-day-today,
[data-theme="light"] #fullCalendar .fc-day-today .fc-daygrid-day-frame {
    background: #ffffff !important;
    box-shadow: 0 0 15px rgba(67, 97, 238, 0.15);
    border: 1px solid rgba(67, 97, 238, 0.15) !important;
}

[data-theme="light"] #fullCalendar .fc-daygrid-day-number {
    color: #1e293b;
    background: rgba(67, 97, 238, 0.06);
}

[data-theme="light"] #fullCalendar .fc-day-today .fc-daygrid-day-number {
    background: linear-gradient(135deg, rgba(67, 97, 238, 0.7) 0%, rgba(58, 12, 163, 0.7) 100%);
    box-shadow: 0 3px 15px rgba(67, 97, 238, 0.4), 
                0 0 0 2px rgba(67, 97, 238, 0.1),
                inset 0 0 10px rgba(255, 255, 255, 0.3);
    color: white;
}

[data-theme="light"] #fullCalendar .fc-daygrid-more-link {
    color: #4361ee;
    background: rgba(67, 97, 238, 0.08);
}

[data-theme="light"] #fullCalendar .fc-daygrid-more-link:hover {
    background: rgba(67, 97, 238, 0.15);
    color: #3a0ca3;
}

[data-theme="light"] #fullCalendar .fc-day-other {
    background: rgba(30, 41, 59, 0.15);
}

[data-theme="light"] .fc-popover {
    background: rgba(255, 255, 255, 0.98) !important;
    border: 1px solid rgba(67, 97, 238, 0.2) !important;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15) !important;
}

[data-theme="light"] .fc-popover .fc-popover-header {
    background: linear-gradient(135deg, rgba(67, 97, 238, 0.1) 0%, rgba(58, 12, 163, 0.1) 100%) !important;
}

[data-theme="light"] .fc-popover .fc-popover-title {
    color: #1e293b !important;
}

/* Hide scrollbars for WebKit browsers */
.calendar-overlay-content::-webkit-scrollbar {
    display: none;
}

/* Calendar Container Layout */
.calendar-container {
    display: flex;
    gap: 20px;
    align-items: stretch;
    width: 100%;
    height: 600px;
    min-height: 600px;
    max-height: 600px;
}

/* Events List Styling */
.events-list {
    flex: 0 0 280px;
    background: rgba(30, 41, 59, 0.4);
    border-radius: 18px;
    padding: 20px;
    color: #fff;
    box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.4), 
                inset 0 0 0 1px rgba(255, 255, 255, 0.08),
                inset 0 0 20px rgba(0, 0, 0, 0.2);
    transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
    position: relative;
    z-index: 1;
    height: 600px;
    min-height: 600px;
    max-height: 600px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    /* Hide scrollbars but keep functionality */
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.events-list::-webkit-scrollbar {
    display: none;
}

.events-list::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(135deg, rgba(76, 201, 240, 0.1), rgba(114, 9, 183, 0.1));
    border-radius: 22px;
    z-index: -1;
    filter: blur(10px);
    opacity: 0.7;
}

.events-list h4 {
    margin: 0 0 20px 0;
    font-size: 1.3rem;
    font-weight: 700;
    background: linear-gradient(135deg, #4cc9f0 0%, #7209b7 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    display: flex;
    align-items: center;
    position: relative;
    text-shadow: 0 0 30px rgba(76, 201, 240, 0.3);
    padding-bottom: 10px;
    flex-shrink: 0;
}

#eventsListContent {
    flex: 1;
    overflow-y: auto;
    /* Hide scrollbars but keep functionality */
    scrollbar-width: none;
    -ms-overflow-style: none;
}

#eventsListContent::-webkit-scrollbar {
    display: none;
}

.events-list h4::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 2px;
    background: linear-gradient(90deg, #4cc9f0, #7209b7);
    border-radius: 2px;
}

.events-list h4 i {
    animation: pulseIcon 2s infinite ease-in-out;
}

/* Event List Items */
.event-list-item {
    background: rgba(76, 201, 240, 0.1);
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 12px;
    border-left: 4px solid;
    border-image: linear-gradient(135deg, #4cc9f0 0%, #7209b7 100%) 1;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    cursor: pointer;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
}

.event-list-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(76, 201, 240, 0.05) 0%, rgba(114, 9, 183, 0.05) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.event-list-item:hover {
    transform: translateX(5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    background: rgba(76, 201, 240, 0.15);
}

.event-list-item:hover::before {
    opacity: 1;
}

.event-list-item.highlighted {
    background: rgba(76, 201, 240, 0.25);
    border-left-color: #4cc9f0;
    transform: translateX(8px) scale(1.02);
    box-shadow: 0 10px 30px rgba(76, 201, 240, 0.4), 
                0 0 0 2px rgba(76, 201, 240, 0.3);
    animation: highlightPulse 2s infinite alternate;
}

.event-list-item.highlighted::before {
    opacity: 1;
    background: linear-gradient(135deg, rgba(76, 201, 240, 0.15) 0%, rgba(114, 9, 183, 0.15) 100%);
}

@keyframes highlightPulse {
    0% { box-shadow: 0 10px 30px rgba(76, 201, 240, 0.4), 0 0 0 2px rgba(76, 201, 240, 0.3); }
    100% { box-shadow: 0 10px 35px rgba(76, 201, 240, 0.6), 0 0 0 3px rgba(76, 201, 240, 0.5); }
}

.event-list-item-title {
    font-weight: 600;
    font-size: 1rem;
    color: #fff;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.event-list-item-title::before {
    content: '';
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4cc9f0 0%, #7209b7 100%);
    flex-shrink: 0;
    box-shadow: 0 0 8px rgba(76, 201, 240, 0.5);
}

.event-list-item-date {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.event-list-item-type {
    font-size: 0.75rem;
    color: rgba(76, 201, 240, 0.9);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
}

.no-events {
    text-align: center;
    color: rgba(255, 255, 255, 0.6);
    font-style: italic;
    margin-top: 40px;
    padding: 20px;
}

.calendar-overlay.active .calendar-overlay-content {
    transform: translateY(0) scale(1);
    opacity: 1;
    filter: blur(0);
    box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.7), 
                0 0 0 1px rgba(255, 255, 255, 0.15),
                inset 0 0 0 1px rgba(255, 255, 255, 0.08);
}

.calendar-overlay-content::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 24px;
    padding: 1.5px;
    background: linear-gradient(135deg, rgba(76, 201, 240, 0.4), rgba(114, 9, 183, 0.4), rgba(76, 201, 240, 0), rgba(114, 9, 183, 0), rgba(76, 201, 240, 0.4));
    background-size: 300% 300%;
    animation: borderGlow 8s linear infinite;
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: destination-out;
    mask-composite: exclude;
    pointer-events: none;
}

@keyframes borderGlow {
    0% { background-position: 0% 0%; }
    50% { background-position: 100% 100%; }
    100% { background-position: 0% 0%; }
}

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 12px;
    position: relative;
}

.calendar-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, 
                transparent, 
                rgba(76, 201, 240, 0.5), 
                rgba(114, 9, 183, 0.5), 
                rgba(76, 201, 240, 0.5), 
                transparent);
    filter: blur(0.5px);
}

.calendar-header h3 {
    color: #fff;
    margin: 0;
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: -0.5px;
    background: linear-gradient(135deg, #4cc9f0 0%, #7209b7 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    display: flex;
    align-items: center;
    position: relative;
    text-shadow: 0 0 30px rgba(76, 201, 240, 0.3);
}

.calendar-header h3::after {
    content: '';
    position: absolute;
    bottom: -6px;
    left: 0;
    width: 40px;
    height: 3px;
    background: linear-gradient(90deg, #4cc9f0, #7209b7);
    border-radius: 3px;
}

.calendar-header h3 i {
    position: relative;
    z-index: 1;
    animation: pulseIcon 2s infinite ease-in-out;
}

@keyframes pulseIcon {
    0%, 100% { transform: scale(1); filter: brightness(1); }
    50% { transform: scale(1.1); filter: brightness(1.2) drop-shadow(0 0 3px rgba(76, 201, 240, 0.6)); }
}

.close-calendar-btn {
    background: rgba(255, 255, 255, 0.08);
    border: none;
    color: #fff;
    font-size: 26px;
    cursor: pointer;
    width: 46px;
    height: 46px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.4s cubic-bezier(0.19, 1, 0.22, 1);
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.12),
                0 4px 8px rgba(0, 0, 0, 0.3);
}

.close-calendar-btn::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(135deg, rgba(76, 201, 240, 0.5) 0%, rgba(114, 9, 183, 0.5) 50%, rgba(76, 201, 240, 0) 100%);
    border-radius: 50%;
    z-index: -1;
    opacity: 0;
    transition: opacity 0.3s ease;
    filter: blur(2px);
}

.close-calendar-btn::after {
    content: '';
    position: absolute;
    inset: 1px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.95) 0%, rgba(15, 23, 42, 0.98) 100%);
    z-index: -1;
}

.close-calendar-btn:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 10px 20px -5px rgba(76, 201, 240, 0.4),
                inset 0 0 0 1px rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.95);
}

.close-calendar-btn:hover::before {
    opacity: 1;
    animation: spinGlow 2s infinite linear;
}

@keyframes spinGlow {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Enhanced FullCalendar Custom Styling */
#fullCalendar {
    flex: 1;
    background: rgba(30, 41, 59, 0.4);
    border-radius: 18px;
    padding: 15px 15px 20px; 
    color: #fff;
    box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.4), 
                inset 0 0 0 1px rgba(255, 255, 255, 0.08),
                inset 0 0 20px rgba(0, 0, 0, 0.2);
    transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
    height: 600px;
    min-height: 600px;
    max-height: 600px;
    overflow-y: auto;
    position: relative;
    z-index: 1;
    /* Hide scrollbars but keep functionality */
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* Internet Explorer 10+ */
}

/* Hide scrollbars for WebKit browsers in fullCalendar */
#fullCalendar::-webkit-scrollbar {
    display: none;
}

/* Ensure consistent display for view containers */
#fullCalendar .fc-view-harness {
    min-height: auto !important; /* Allow natural height */
    height: auto !important;
    margin-bottom: 15px !important;
}

/* Allow table to expand properly */
#fullCalendar table,
#fullCalendar .fc-scrollgrid-sync-table {
    height: auto !important;
    width: 100% !important;
}

/* Ensure cells can expand to fit content */
#fullCalendar .fc-scrollgrid-sync-table > tbody > tr {
    height: auto !important;
    min-height: 100px !important;
}

/* Make sure the last row is fully visible */
#fullCalendar .fc-scrollgrid-sync-table > tbody > tr:last-child {
    height: auto !important;
    min-height: 100px !important;
}

/* Make day frames fill cell height dynamically */
#fullCalendar .fc-scrollgrid-sync-table > tbody > tr > td {
    height: auto !important;
    min-height: 100px !important;
    vertical-align: top;
    padding-bottom: 5px;
}

/* Ensure proper cell sizing and day cell body */
#fullCalendar .fc-daygrid-day-frame {
    min-height: 100px !important;
    height: auto !important;
    padding: 4px;
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
}

/* Apply proper height to day cells */
#fullCalendar .fc-daygrid-day {
    height: auto !important;
    min-height: 100px !important;
}

/* Calendar overlay animation for mobile */
@media (max-width: 768px) {
    .calendar-overlay-content {
        width: 95%;
        margin: 10px auto;
        padding: 12px;
        max-height: 90vh;
        overflow: hidden;
    }
    
    .calendar-container {
        flex-direction: column;
        gap: 15px;
        height: auto;
        max-height: none;
    }
    
    .events-list {
        flex: none;
        width: 100%;
        height: 300px;
        min-height: 300px;
        max-height: 300px;
        order: 2;
    }
    
    #fullCalendar {
        padding: 8px;
        height: 400px;
        min-height: 400px;
        max-height: 400px;
        order: 1;
    }
    
    #fullCalendar .fc-toolbar {
        margin-bottom: 1rem !important;
    }
    
    #fullCalendar .fc-toolbar-title {
        font-size: 1.1rem !important;
    }
    
    .calendar-header h3 {
        font-size: 1.2rem;
    }
    
    #fullCalendar .fc-daygrid-day,
    #fullCalendar .fc-daygrid-day-frame {
        min-height: 70px !important;
    }
    
    /* Stack toolbar on smaller screens */
    #fullCalendar .fc-toolbar {
        flex-wrap: wrap;
    }
    
    #fullCalendar .fc-toolbar-chunk {
        margin: 5px 0;
        flex: 1 0 100%;
        display: flex;
        justify-content: center;
    }
    
    /* Ensure buttons fit nicely on small screens */
    #fullCalendar .fc-toolbar-chunk:first-child {
        order: 2;
    }
    
    #fullCalendar .fc-toolbar-chunk:nth-child(2) {
        order: 1;
    }
    
    #fullCalendar .fc-toolbar-chunk:last-child {
        order: 3;
    }
}

/* Extra small devices */
@media (max-width: 576px) {
    .calendar-overlay-content {
        width: 98%;
        padding: 10px;
        margin: 5px auto;
    }
    
    #fullCalendar {
        padding: 5px;
    }
    
    /* Adjust cell sizes for very small screens */
    #fullCalendar .fc-col-header-cell-cushion {
        padding: 4px;
        font-size: 0.7rem;
    }
    
    #fullCalendar .fc-daygrid-day-number {
        min-width: 22px;
        height: 22px;
        font-size: 0.7rem;
    }
    
    #fullCalendar .fc-daygrid-day,
    #fullCalendar .fc-daygrid-day-frame {
        min-height: 60px !important;
    }
}

/* Ensure consistent spacing for month view */
#fullCalendar .fc-dayGridMonth-view .fc-daygrid {
    height: 100% !important;
}

#fullCalendar::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(135deg, rgba(76, 201, 240, 0.1), rgba(114, 9, 183, 0.1));
    border-radius: 22px;
    z-index: -1;
    filter: blur(10px);
    opacity: 0.7;
}

/* Remove all table borders and grid lines */
#fullCalendar table,
#fullCalendar .fc-scrollgrid,
#fullCalendar td,
#fullCalendar th,
#fullCalendar .fc-theme-standard td,
#fullCalendar .fc-theme-standard th {
    border: none !important;
}

/* Add spacing between cells */
#fullCalendar .fc-scrollgrid {
    border-collapse: separate !important;
    border-spacing: 2px !important;
}

/* Ensure all calendar elements have transparent backgrounds */
#fullCalendar div,
#fullCalendar table,
#fullCalendar thead,
#fullCalendar tbody,
#fullCalendar tr,
#fullCalendar th,
#fullCalendar td {
    background-color: transparent !important;
}

/* Re-apply specific background styling to elements that need it */
#fullCalendar .fc-daygrid-day {
    background: rgba(30, 41, 59, 0.3) !important;
}

#fullCalendar .fc-col-header-cell {
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(58, 12, 163, 0.2) 100%) !important;
}

#fullCalendar .fc-day-today {
    background: rgba(76, 201, 240, 0.1) !important;
}

/* Add hover effect to calendar */
#fullCalendar:hover {
    box-shadow: 0 15px 40px -5px rgba(0, 0, 0, 0.4), 
                inset 0 0 0 1px rgba(255, 255, 255, 0.1);
    transform: translateY(-5px);
}

/* Calendar toolbar */
#fullCalendar .fc-toolbar {
    margin-bottom: 2rem !important;
    padding: 0 0.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(30, 41, 59, 0.3);
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 1.5rem !important;
}

#fullCalendar .fc-toolbar .fc-toolbar-ltr {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

#fullCalendar .fc-toolbar-chunk {
    display: flex;
    gap: 12px;
    align-items: center;
}

#fullCalendar .fc-toolbar-chunk:first-child {
    flex: 0 0 auto;
}

#fullCalendar .fc-toolbar-chunk:nth-child(2) {
    flex: 1;
    justify-content: center;
}

#fullCalendar .fc-toolbar-chunk:last-child {
    flex: 0 0 auto;
}

/* Calendar buttons */
#fullCalendar .fc-button-group {
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    border-radius: 12px;
    overflow: hidden;
    background: transparent !important;
}

#fullCalendar .fc-button {
    background: rgba(76, 201, 240, 0.1) !important;
    border-color: rgba(76, 201, 240, 0.2) !important;
    color: #fff !important;
    padding: 8px 16px !important;
    font-weight: 500 !important;
    letter-spacing: 0.3px !important;
    text-transform: uppercase !important;
    font-size: 0.8rem !important;
    transition: all 0.3s ease !important;
    border: none !important;
    box-shadow: none !important;
}

#fullCalendar .fc-button:focus {
    box-shadow: 0 0 0 0.2rem rgba(76, 201, 240, 0.15) !important;
}

#fullCalendar .fc-button:hover {
    background: rgba(76, 201, 240, 0.2) !important;
    transform: translateY(-2px);
}

#fullCalendar .fc-button-active {
    background: linear-gradient(135deg, rgba(76, 201, 240, 0.4) 0%, rgba(114, 9, 183, 0.4) 100%) !important;
    box-shadow: 0 5px 15px -3px rgba(76, 201, 240, 0.3) !important;
    transform: translateY(-2px);
}

#fullCalendar .fc-today-button {
    background: linear-gradient(135deg, rgba(76, 201, 240, 0.3) 0%, rgba(114, 9, 183, 0.3) 100%) !important;
    background-size: 200% 100% !important;
    border-radius: 10px !important;
    position: relative;
    overflow: hidden;
    border: none !important;
    transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
}

#fullCalendar .fc-today-button::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: all 0.6s ease;
}

#fullCalendar .fc-today-button:hover {
    box-shadow: 0 7px 15px -3px rgba(76, 201, 240, 0.4) !important;
    transform: translateY(-3px);
    animation: glow 2s infinite;
}

#fullCalendar .fc-today-button:hover::after {
    left: 100%;
}

/* Today's date styling */
#fullCalendar .fc-toolbar-title {
    color: #fff;
    font-weight: 700;
    font-size: 1.5rem !important;
    letter-spacing: -0.5px;
    background: linear-gradient(135deg, #4cc9f0 0%, #7209b7 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Calendar header styling - FIXED DAYS HEADER */
#fullCalendar .fc-col-header {
    margin-bottom: 10px;
}

/* Fix white background in days of week header */
#fullCalendar .fc-scroller-harness,
#fullCalendar .fc-scroller,
#fullCalendar .fc-col-header-inner {
    background: transparent !important;
    border: none !important;
    overflow: visible !important;
}

#fullCalendar .fc-col-header-cell {
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(58, 12, 163, 0.2) 100%);
    border: none !important;
    height: 40px;
}

#fullCalendar .fc-scrollgrid {
    border: none !important;
}

#fullCalendar .fc-col-header-cell-cushion {
    color: rgba(255, 255, 255, 0.95);
    font-weight: 700;
    font-size: 0.95rem;
    text-decoration: none !important;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    padding: 12px;
    border-radius: 8px;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    display: block;
    text-align: center;
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, rgba(58, 12, 163, 0.2) 0%, rgba(76, 201, 240, 0.1) 100%);
    text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
    margin: 0 2px;
}

#fullCalendar .fc-col-header-cell-cushion:after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 2px;
    background: linear-gradient(90deg, rgba(76, 201, 240, 0.6) 0%, rgba(114, 9, 183, 0.6) 100%);
    transition: all 0.3s ease;
}

#fullCalendar .fc-col-header-cell:hover .fc-col-header-cell-cushion:after {
    width: 80%;
}

/* Day grid styling - FIXED DATE CELLS */
#fullCalendar .fc-daygrid-body {
    border-radius: 10px;
    overflow: hidden;
}

#fullCalendar .fc-daygrid-day {
    background: rgba(30, 41, 59, 0.3);
    border: none !important;
    transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    height: 130px !important; /* Increased height */
    margin: 3px;
    border-radius: 12px;
    position: relative;
    overflow: visible;
}

/* Fix for bottom row cells to ensure they have same height */
#fullCalendar .fc-daygrid-body {
    min-height: 100% !important;
}

#fullCalendar .fc-daygrid-body-balanced {
    height: auto !important;
}

/* Fix the layout of day cells */
#fullCalendar .fc-daygrid-day-frame {
    height: 100%;
    min-height: 120px;
    display: flex;
    flex-direction: column;
}

#fullCalendar .fc-daygrid-day::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, 
                rgba(76, 201, 240, 0.03) 0%, 
                rgba(114, 9, 183, 0.03) 100%);
    opacity: 0;
    transition: opacity 0.4s ease;
}

#fullCalendar .fc-daygrid-day:hover {
    background: rgba(30, 41, 59, 0.4) !important;
    transform: translateY(-1px);
    box-shadow: 0 5px 10px -2px rgba(0, 0, 0, 0.1);
}

#fullCalendar .fc-daygrid-day:hover::after {
    opacity: 1;
}

#fullCalendar .fc-day-today {
    background: rgba(76, 201, 240, 0.08) !important;
    box-shadow: 0 0 15px rgba(76, 201, 240, 0.1);
    position: relative;
    z-index: 1;
}

#fullCalendar .fc-day-today::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, 
                rgba(76, 201, 240, 0.05) 0%, 
                rgba(114, 9, 183, 0.05) 100%);
    z-index: -1;
    animation: shimmer 3s infinite linear;
    background-size: 200% 200%;
}

/* FIXED DATE NUMBER STYLING */
#fullCalendar .fc-daygrid-day-top {
    justify-content: center;
    padding: 5px 0;
}

#fullCalendar .fc-daygrid-day-number {
    color: #fff;
    font-weight: 500;
    padding: 5px;
    text-decoration: none !important;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    background: rgba(76, 201, 240, 0.1);
    border-radius: 50%;
    min-width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 3px 0 0 3px;
    font-size: 0.9rem;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
}

#fullCalendar .fc-daygrid-day-number::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 200%;
    height: 100%;
    background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.1), 
                transparent);
    transition: 0.6s ease;
    opacity: 0;
}

#fullCalendar .fc-daygrid-day:hover .fc-daygrid-day-number::after {
    left: 100%;
    opacity: 1;
}

#fullCalendar .fc-day-today .fc-daygrid-day-number {
    font-weight: 700;
    background: linear-gradient(135deg, rgba(76, 201, 240, 0.7) 0%, rgba(114, 9, 183, 0.7) 100%);
    background-size: 200% 100%;
    box-shadow: 0 3px 15px rgba(76, 201, 240, 0.6), 
                0 0 0 2px rgba(76, 201, 240, 0.2),
                inset 0 0 10px rgba(255, 255, 255, 0.15);
    animation: pulse 2s infinite cubic-bezier(0.45, 0.05, 0.55, 0.95), 
               shimmer 3s infinite;
    position: relative;
    color: rgba(255, 255, 255, 0.95);
    transform: scale(1.1);
    z-index: 2;
    margin: 1px 0 0 1px;
}

#fullCalendar .fc-day-today .fc-daygrid-day-number::before {
    content: '';
    position: absolute;
    top: -3px;
    left: -3px;
    right: -3px;
    bottom: -3px;
    background: linear-gradient(135deg, 
                rgba(76, 201, 240, 0.4) 0%, 
                rgba(114, 9, 183, 0.4) 100%);
    border-radius: 50%;
    z-index: -1;
    opacity: 0.7;
    filter: blur(5px);
    animation: glow 3s infinite alternate;
}

#fullCalendar .fc-day-today .fc-daygrid-day-number::after {
    background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.2), 
                transparent);
    opacity: 1;
    animation: shimmer 2s infinite;
}

/* Fix for proper cell sizing and day cell body */
#fullCalendar .fc-daygrid-day-frame {
    min-height: 100%;
    height: 100%;
    padding: 4px;
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
}

/* Fix the spacing of day top (date number container) */
#fullCalendar .fc-daygrid-day-top {
    justify-content: center;
    padding: 3px 0;
    margin-bottom: 2px;
}

/* Ensure proper layout for event container */
#fullCalendar .fc-daygrid-day-events {
    overflow: hidden;
}

/* Force table to use full height */
#fullCalendar table,
#fullCalendar .fc-scrollgrid-sync-table {
    height: 100% !important;
}

/* Ensure ALL rows have consistent height, especially the bottom row */
#fullCalendar .fc-scrollgrid-sync-table > tbody > tr {
    height: 130px !important;
}

/* Specific fix for last row to ensure proper spacing */
#fullCalendar .fc-scrollgrid-sync-table > tbody > tr:last-child {
    height: 130px !important;
    margin-bottom: 10px;
}

/* Make day frames fill cell height consistently */
#fullCalendar .fc-scrollgrid-sync-table > tbody > tr > td {
    height: 130px !important;
    vertical-align: top;
    padding-bottom: 5px;
}

#fullCalendar .fc-daygrid-day-events {
    padding: 0;
    margin: 5px 2px 0;
    min-height: 20px;
    overflow: visible;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    width: calc(100% - 4px);
}

/* Improve event container spacing */
#fullCalendar .fc-daygrid-event-harness {
    margin-bottom: 4px;
    margin-right: 0;
    margin-left: 0;
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box;
    padding: 0 2px;
}

/* FIXED EVENT STYLING */
#fullCalendar .fc-event {
    background: linear-gradient(135deg, rgba(114, 9, 183, 0.85) 0%, rgba(76, 201, 240, 0.85) 100%);
    background-size: 200% 100%;
    border: none;
    border-radius: 8px;
    color: #fff;
    cursor: pointer;
    font-size: 0.76rem;
    padding: 5px 8px;
    margin-bottom: 3px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25), 
                0 0 0 1px rgba(255, 255, 255, 0.12),
                inset 0 0 5px rgba(255, 255, 255, 0.1);
    transition: all 0.45s cubic-bezier(0.16, 1, 0.3, 1), 
               background-position 0.8s ease, 
               box-shadow 0.4s ease;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: flex;
    align-items: center;
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    letter-spacing: 0.2px;
    font-weight: 500;
    width: 100% !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
    box-sizing: border-box;
    position: relative;
    /* Prevent text overflow */
    min-width: 0;
}

#fullCalendar .fc-event::before {
    content: '';
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #fff;
    margin-right: 5px;
    flex-shrink: 0;
    opacity: 0.9;
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), 
               box-shadow 0.4s ease, 
               background-color 0.4s ease;
    box-shadow: 0 0 6px rgba(255, 255, 255, 0.6);
    position: relative;
}

/* Style for event title to prevent clipping */
#fullCalendar .fc-event-title {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
    padding-right: 2px;
}

#fullCalendar .fc-event::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s ease;
    pointer-events: none;
}

#fullCalendar .fc-event:hover {
    transform: translateY(-4px) scale(1.08);
    box-shadow: 0 8px 15px rgba(114, 9, 183, 0.5), 
               0 0 0 1px rgba(255, 255, 255, 0.2);
    background-position: 100% 0;
    z-index: 5;
}

#fullCalendar .fc-event:hover::before {
    transform: scale(1.3);
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.9), 0 0 20px rgba(255, 255, 255, 0.3);
    background-color: rgba(255, 255, 255, 0.95);
}

#fullCalendar .fc-event:hover::after {
    transform: translateX(100%);
}

#fullCalendar .fc-event.highlighted {
    background: linear-gradient(135deg, rgba(76, 201, 240, 0.95) 0%, rgba(114, 9, 183, 0.95) 100%);
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 12px 25px rgba(76, 201, 240, 0.6), 
                0 0 0 3px rgba(255, 255, 255, 0.4),
                0 0 15px rgba(76, 201, 240, 0.8);
    z-index: 10;
    animation: calendarEventPulse 2s infinite alternate;
}

#fullCalendar .fc-event.highlighted::before {
    transform: scale(1.5);
    box-shadow: 0 0 15px rgba(255, 255, 255, 1), 0 0 25px rgba(255, 255, 255, 0.5);
    background-color: rgba(255, 255, 255, 1);
}

#fullCalendar .fc-event.highlighted::after {
    transform: translateX(100%);
    opacity: 1;
}

@keyframes calendarEventPulse {
    0% { 
        box-shadow: 0 12px 25px rgba(76, 201, 240, 0.6), 
                    0 0 0 3px rgba(255, 255, 255, 0.4),
                    0 0 15px rgba(76, 201, 240, 0.8);
    }
    100% { 
        box-shadow: 0 15px 30px rgba(76, 201, 240, 0.8), 
                    0 0 0 4px rgba(255, 255, 255, 0.6),
                    0 0 20px rgba(76, 201, 240, 1);
    }
}

/* Fix event title and time display */
#fullCalendar .fc-event-title,
#fullCalendar .fc-event-time {
    font-size: 0.76rem;
    line-height: 1.2;
    font-weight: 500;
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Add a slight shadow to the text for better visibility */
#fullCalendar .fc-event-main {
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    width: 100%;
}

/* Apply proper width constraints to event content */
#fullCalendar .fc-event-main-frame {
    width: 100%;
    overflow: hidden;
    display: flex;
    flex-direction: row;
    align-items: center;
}

/* Improve the display of multiple events */
#fullCalendar .fc-daygrid-day-bottom {
    padding-top: 1px;
    padding-bottom: 1px;
}

/* Fix for event list display in popover */
.fc-popover .fc-daygrid-event {
    margin: 4px 0 !important;
}

/* Create more consistent event coloring */
#fullCalendar .fc-event.fc-daygrid-event {
    background-size: 300% 100%;
    animation: gradientShift 8s infinite alternate linear;
}

@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    100% { background-position: 100% 50%; }
}

/* FIXED MORE LINK STYLING */
#fullCalendar .fc-daygrid-more-link {
    color: rgba(76, 201, 240, 1);
    font-size: 0.75rem;
    font-weight: 600;
    background: rgba(76, 201, 240, 0.15);
    border-radius: 20px;
    padding: 3px 8px;
    margin: 3px auto;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    text-align: center;
    display: block;
    width: fit-content;
    max-width: 90%;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    text-decoration: none !important;
    position: relative;
    overflow: hidden;
    white-space: nowrap;
}

#fullCalendar .fc-daygrid-more-link::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s ease;
}

#fullCalendar .fc-daygrid-more-link:hover {
    background: rgba(76, 201, 240, 0.25);
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

#fullCalendar .fc-daygrid-more-link:hover::after {
    transform: translateX(100%);
}

/* FIXED OTHER MONTH DATE STYLING */
#fullCalendar .fc-day-other {
    background: rgba(30, 41, 59, 0.15);
}

#fullCalendar .fc-day-other .fc-daygrid-day-number {
    opacity: 0.5;
    background: transparent;
}

/* Event dot styling for list view */
#fullCalendar .fc-list-event-dot {
    border-color: #7209b7;
}

/* List view styling */
#fullCalendar .fc-list {
    border-radius: 10px;
    overflow: hidden;
}

#fullCalendar .fc-list-day-cushion {
    background: rgba(76, 201, 240, 0.15) !important;
}

#fullCalendar .fc-list-event:hover td {
    background: rgba(76, 201, 240, 0.1) !important;
}

/* FIXED POPOVERS FOR EVENT CLICK */
.fc-popover {
    background: rgba(30, 41, 59, 0.95) !important;
    border: 1px solid rgba(76, 201, 240, 0.3) !important;
    border-radius: 16px !important;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6) !important;
    backdrop-filter: blur(15px);
    max-width: 300px !important;
    width: 280px !important;
    overflow: hidden !important;
    z-index: 1000 !important;
    /* Hide scrollbars but keep functionality */
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* Internet Explorer 10+ */
}

/* Hide scrollbars for WebKit browsers in popovers */
.fc-popover::-webkit-scrollbar {
    display: none;
}

/* Fix popover positioning */
.fc-popover.fc-more-popover {
    margin-top: 10px !important;
}

.fc-popover .fc-popover-header {
    background: linear-gradient(135deg, rgba(76, 201, 240, 0.3) 0%, rgba(114, 9, 183, 0.3) 100%) !important;
    padding: 8px !important;
    border-top-left-radius: 10px !important;
    border-top-right-radius: 10px !important;
}

.fc-popover .fc-popover-title {
    color: white !important;
    font-weight: 600 !important;
}

.fc-popover .fc-popover-body {
    padding: 12px !important;
    max-height: 300px !important;
    overflow-y: auto !important;
    /* Hide scrollbars but keep functionality */
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* Internet Explorer 10+ */
}

/* Hide scrollbars for WebKit browsers in popover body */
.fc-popover .fc-popover-body::-webkit-scrollbar {
    display: none;
}

.fc-popover .fc-daygrid-event-harness {
    margin-bottom: 8px !important;
    width: 100% !important;
}

.fc-popover .fc-event {
    width: calc(100% - 6px) !important;
    margin-left: 3px !important;
    margin-right: 3px !important;
}

.fc-popover .fc-popover-close {
    color: white !important;
    opacity: 0.8 !important;
    font-size: 1.2rem !important;
    padding: 5px !important;
}

.fc-popover .fc-popover-close:hover {
    opacity: 1 !important;
}

/* Animation keyframes for calendar effects */
@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.03); opacity: 0.9; }
    100% { transform: scale(1); opacity: 1; }
}

@keyframes float {
    0% { transform: translateY(0) rotate(0deg); }
    25% { transform: translateY(-8px) rotate(1deg); }
    75% { transform: translateY(4px) rotate(-1deg); }
    100% { transform: translateY(0) rotate(0deg); }
}

@keyframes glow {
    0% { box-shadow: 0 0 5px rgba(76, 201, 240, 0.3); }
    50% { box-shadow: 0 0 15px rgba(76, 201, 240, 0.6); }
    100% { box-shadow: 0 0 5px rgba(76, 201, 240, 0.3); }
}

@keyframes shimmer {
    0% { background-position: -100% 0; }
    100% { background-position: 200% 0; }
}
</style>

<!-- Enhanced Calendar JavaScript -->
<script>
// Use a self-executing function to avoid variable collisions
(function() {
    // Calendar events data from PHP
    const calendarEventsData = <?php echo json_encode($calendarEvents); ?>;
    
    // Format events for FullCalendar with enhanced data
    const formattedEvents = calendarEventsData.map(event => ({
        title: event.title,
        start: event.date,
        id: event.id,
        allDay: true,
        eventType: event.type || 'Event',
        // Add random colors for different event types to simulate variety
        backgroundColor: getEventColor(event.type),
        borderColor: getEventColor(event.type),
    }));
    
    // Function to get color based on event type
    function getEventColor(type) {
        const colors = {
            'Conference': 'rgba(114, 9, 183, 0.9)',
            'Workshop': 'rgba(76, 201, 240, 0.9)',
            'Seminar': 'rgba(247, 37, 133, 0.9)',
            'Meeting': 'rgba(67, 97, 238, 0.9)',
            'Webinar': 'rgba(58, 12, 163, 0.9)'
        };
        
        return colors[type] || 'rgba(114, 9, 183, 0.9)';
    }
    
    // Initialize calendar when DOM is fully loaded
    function initializeCalendar() {
    const calendarEl = document.getElementById('fullCalendar');
        const calendarOverlay = document.getElementById('calendarOverlay');
        
        if (!calendarEl) {
            console.error('Calendar element not found');
            return;
        }
        
        // Initialize FullCalendar with enhanced options
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next',
            center: 'title',
            right: 'today'
        },
        events: formattedEvents,
        eventClick: function(info) {
            // Prevent default navigation
            info.jsEvent.preventDefault();
            
            // Highlight the clicked calendar event
            highlightCalendarEvent(info.el);
            
            // Highlight and scroll to corresponding event in the list
            highlightAndScrollToEventListItem(info.event.id);
        },
        height: window.innerWidth <= 768 ? 400 : 600, // Responsive height
        contentHeight: 'auto', // Let content dictate height within the container
        aspectRatio: 1.35, // Wider aspect ratio for better display
        expandRows: false, // Don't expand rows, allow scrolling instead
        // Set sizing to adapt to container
        stickyHeaderDates: false,
            // Add animation to day cells
            dayCellDidMount: function(info) {
                // Add subtle animation delay based on date for staggered effect
                const day = info.date.getDate();
                info.el.style.animationDelay = (day * 0.02) + 's';
                info.el.style.animationDuration = '0.5s';
                info.el.classList.add('calendar-cell-animate');
            },
            // Add animation when events are rendered
            eventDidMount: function(info) {
                info.el.style.opacity = '0';
                info.el.style.transform = 'translateY(10px)';
                
                // Add data attribute for event ID
                info.el.setAttribute('data-event-id', info.event.id);
                
                setTimeout(() => {
                    info.el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    info.el.style.opacity = '1';
                    info.el.style.transform = 'translateY(0)';
                }, 100 + Math.random() * 400); // Staggered animation
            },
        windowResize: function(view) {
            // Update layout on window resize
            calendar.updateSize();
        },
        datesSet: function(info) {
            // Update events list when calendar view changes
            populateEventsList();
        },
        themeSystem: 'standard'
        });
        
        // Enhanced view calendar button functionality with animations
    document.querySelectorAll('.view-calendar-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
                
                // Show overlay with animation
                calendarOverlay.style.display = 'block';
                
                // Trigger reflow for animation to work properly
                void calendarOverlay.offsetWidth;
                
                // Add active class for animation
                calendarOverlay.classList.add('active');
            
                    // Ensure calendar is rendered and events are visible
        setTimeout(() => {
            calendar.render();
            calendar.updateSize(); // Force size update
                
                // Populate events list
                populateEventsList();
                
                // Add particle effects
                createParticles();
        }, 100);
            
            document.body.style.overflow = 'hidden';
        });
    });
    
    // Handle resize events
    window.addEventListener('resize', function() {
        if (calendarOverlay.classList.contains('active')) {
            calendar.updateSize();
        }
    });
    
        // Enhanced close calendar overlay with animations
    document.querySelector('.close-calendar-btn').addEventListener('click', function() {
            closeCalendarOverlay();
    });
    
    // Close calendar when clicking background
    document.querySelector('.calendar-overlay-bg').addEventListener('click', function() {
            closeCalendarOverlay();
        });
        
        // Function to close calendar overlay with animation
        function closeCalendarOverlay() {
            calendarOverlay.classList.remove('active');
            
            // Wait for animation to finish before hiding
            setTimeout(() => {
                calendarOverlay.style.display = 'none';
        document.body.style.overflow = 'auto';
                
                // Remove any particles
                document.querySelectorAll('.calendar-particle').forEach(particle => {
                    particle.remove();
    });
            }, 400);
        }
        
        // Add escape key support
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && calendarOverlay.classList.contains('active')) {
                closeCalendarOverlay();
            }
        });
        
        // Function to populate the events list
        function populateEventsList() {
            const eventsListContent = document.getElementById('eventsListContent');
            if (!eventsListContent) return;
            
            // Sort events by date (upcoming first)
            const currentDate = new Date();
            currentDate.setHours(0, 0, 0, 0); // Reset to start of day for proper comparison
            
            const upcomingEvents = formattedEvents
                .filter(event => {
                    const eventDate = new Date(event.start);
                    eventDate.setHours(0, 0, 0, 0);
                    return eventDate >= currentDate;
                })
                .sort((a, b) => new Date(a.start) - new Date(b.start))
                .slice(0, 10); // Show max 10 upcoming events
            
            if (upcomingEvents.length === 0) {
                eventsListContent.innerHTML = '<div class="no-events">No upcoming events</div>';
                return;
            }
            
            const eventsHTML = upcomingEvents.map(event => {
                const eventDate = new Date(event.start);
                const formattedDate = eventDate.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
                
                return `
                    <div class="event-list-item" data-event-id="${event.id}" data-event-date="${event.start}" onclick="navigateToEventDate('${event.id}', '${event.start}')">
                        <div class="event-list-item-title">${event.title}</div>
                        <div class="event-list-item-date">
                            <i class="bi bi-calendar3"></i>
                            ${formattedDate}
                        </div>
                        <div class="event-list-item-type">${event.eventType || 'Event'}</div>
                    </div>
                `;
            }).join('');
            
            eventsListContent.innerHTML = eventsHTML;
        }
        
        // Function to navigate to event date in calendar
        function navigateToEventDate(eventId, eventDate) {
            console.log('Navigating to event:', eventId, eventDate);
            
            // Navigate calendar to the event date
            const date = new Date(eventDate);
            calendar.gotoDate(date);
            
            // Highlight the event list item
            highlightEventListItem(eventId);
            
            // Wait for calendar to render and update, then highlight and scroll to the calendar event
            // Use multiple attempts with increasing delays to ensure the event is found and calendar is rendered
            
            // First attempt after calendar navigation
            setTimeout(() => {
                highlightAndScrollToCalendarEvent(eventId);
            }, 500);
            
            // Second attempt with longer delay
            setTimeout(() => {
                const event = document.querySelector(`.fc-event[data-event-id="${eventId}"]`);
                if (event && !event.classList.contains('highlighted')) {
                    console.log('Second attempt to highlight and scroll to event');
                    highlightAndScrollToCalendarEvent(eventId);
                }
            }, 1000);
            
            // Final attempt with even longer delay
            setTimeout(() => {
                const event = document.querySelector(`.fc-event[data-event-id="${eventId}"]`);
                if (event) {
                    if (!event.classList.contains('highlighted')) {
                        console.log('Final attempt to highlight event');
                        clearAllHighlights();
                        event.classList.add('highlighted');
                    }
                    // Force scroll regardless of highlight status
                    console.log('Final attempt to scroll to event');
                    scrollToCalendarEvent(event);
                }
            }, 1500);
        }
        
        // Function to highlight and scroll to event list item
        function highlightAndScrollToEventListItem(eventId) {
            console.log('Highlighting event list item:', eventId);
            
            // Clear all previous highlights
            clearAllHighlights();
            
            // Find the event list item
            const eventItem = document.querySelector(`.event-list-item[data-event-id="${eventId}"]`);
            console.log('Found event item:', eventItem);
            
            if (eventItem) {
                // Highlight the selected item
                eventItem.classList.add('highlighted');
                
                // Scroll to the highlighted item in the events list
                const eventsListContent = document.getElementById('eventsListContent');
                if (eventsListContent) {
                    const itemTop = eventItem.offsetTop;
                    const containerHeight = eventsListContent.clientHeight;
                    const itemHeight = eventItem.clientHeight;
                    
                    eventsListContent.scrollTo({
                        top: itemTop - (containerHeight / 2) + (itemHeight / 2),
                        behavior: 'smooth'
                    });
                }
            } else {
                console.log('Event list item not found for ID:', eventId);
            }
        }
        
        // Function to highlight event list item (for internal use)
        function highlightEventListItem(eventId) {
            // Find and highlight the event list item
            const eventItem = document.querySelector(`.event-list-item[data-event-id="${eventId}"]`);
            if (eventItem) {
                eventItem.classList.add('highlighted');
            }
        }
        
        // Function to highlight and scroll to calendar event
        function highlightAndScrollToCalendarEvent(eventId) {
            console.log('Highlighting calendar event:', eventId);
            
            // Clear all previous highlights
            clearAllHighlights();
            
            // Find the calendar event by data attribute
            const calendarEvent = document.querySelector(`.fc-event[data-event-id="${eventId}"]`);
            console.log('Found calendar event:', calendarEvent);
            
            if (calendarEvent) {
                // Highlight the calendar event
                calendarEvent.classList.add('highlighted');
                
                // Scroll the fullCalendar container to show the event
                const fullCalendarContainer = document.getElementById('fullCalendar');
                if (fullCalendarContainer) {
                    // Get the event's position relative to the calendar container
                    const containerRect = fullCalendarContainer.getBoundingClientRect();
                    const eventRect = calendarEvent.getBoundingClientRect();
                    
                    // Calculate if the event is outside the visible area
                    const eventTop = eventRect.top - containerRect.top + fullCalendarContainer.scrollTop;
                    const eventBottom = eventTop + eventRect.height;
                    const containerScrollTop = fullCalendarContainer.scrollTop;
                    const containerHeight = fullCalendarContainer.clientHeight;
                    const visibleTop = containerScrollTop;
                    const visibleBottom = containerScrollTop + containerHeight;
                    
                    // Check if event is outside visible area
                    if (eventTop < visibleTop || eventBottom > visibleBottom) {
                        // Calculate the scroll position to center the event
                        const scrollToPosition = eventTop - (containerHeight / 2) + (eventRect.height / 2);
                        
                        fullCalendarContainer.scrollTo({
                            top: Math.max(0, scrollToPosition),
                            behavior: 'smooth'
                        });
                    }
                    
                    // Also use the browser's scrollIntoView as a fallback
                    setTimeout(() => {
                        // Try multiple scrolling approaches
                        scrollToCalendarEvent(calendarEvent);
                    }, 100);
                }
            } else {
                console.log('Calendar event not found for ID:', eventId);
                
                // If event not found immediately, try again after a longer delay
                setTimeout(() => {
                    const retryEvent = document.querySelector(`.fc-event[data-event-id="${eventId}"]`);
                    if (retryEvent) {
                        console.log('Found calendar event on retry:', retryEvent);
                        retryEvent.classList.add('highlighted');
                        
                        // Scroll to the event
                        scrollToCalendarEvent(retryEvent);
                    } else {
                        console.log('Calendar event still not found after retry');
                    }
                }, 1000);
            }
        }
        
        // Function to highlight calendar event element
        function highlightCalendarEvent(eventElement) {
            // Clear all previous highlights first
            clearAllHighlights();
            
            // Add highlight to selected event
            eventElement.classList.add('highlighted');
        }
        
        // Function to clear all highlights
        function clearAllHighlights() {
            document.querySelectorAll('.fc-event.highlighted').forEach(event => {
                event.classList.remove('highlighted');
            });
            document.querySelectorAll('.event-list-item.highlighted').forEach(item => {
                item.classList.remove('highlighted');
            });
        }
        
        // Helper function to scroll to a calendar event with multiple approaches
        function scrollToCalendarEvent(eventElement) {
            if (!eventElement) return;
            
            console.log('Scrolling to calendar event:', eventElement);
            
            // Find the main FullCalendar scrollable container
            const fullCalendarContainer = document.getElementById('fullCalendar');
            
            if (fullCalendarContainer) {
                // Get current scroll position and container dimensions
                const containerRect = fullCalendarContainer.getBoundingClientRect();
                const eventRect = eventElement.getBoundingClientRect();
                const currentScrollTop = fullCalendarContainer.scrollTop;
                
                // Calculate event position relative to the container
                const eventRelativeTop = eventRect.top - containerRect.top + currentScrollTop;
                const containerHeight = fullCalendarContainer.clientHeight;
                const eventHeight = eventRect.height;
                
                // Check if event is outside visible area
                const visibleTop = currentScrollTop;
                const visibleBottom = currentScrollTop + containerHeight;
                
                console.log('Container height:', containerHeight);
                console.log('Current scroll top:', currentScrollTop);
                console.log('Event relative top:', eventRelativeTop);
                console.log('Event height:', eventHeight);
                console.log('Visible area:', visibleTop, 'to', visibleBottom);
                
                // Calculate target scroll position to center the event
                let targetScrollTop;
                
                // Always try to scroll to the event for better visibility
                targetScrollTop = eventRelativeTop - (containerHeight / 2) + (eventHeight / 2);
                
                // Ensure we don't scroll beyond the limits
                const maxScrollTop = fullCalendarContainer.scrollHeight - containerHeight;
                targetScrollTop = Math.max(0, Math.min(targetScrollTop, maxScrollTop));
                
                console.log('Target scroll position:', targetScrollTop);
                console.log('Max scroll top:', maxScrollTop);
                console.log('Full calendar scroll height:', fullCalendarContainer.scrollHeight);
                
                // Always perform the scroll to ensure event is centered
                fullCalendarContainer.scrollTo({
                    top: targetScrollTop,
                    behavior: 'smooth'
                });
                
                console.log('Scrolling to position:', targetScrollTop);
                
                // Also try to scroll using a different approach as backup
                setTimeout(() => {
                    const newScrollTop = fullCalendarContainer.scrollTop;
                    console.log('Current scroll position after scroll attempt:', newScrollTop);
                    
                    if (Math.abs(newScrollTop - targetScrollTop) > 10) {
                        console.log('Scroll didn\'t reach target, trying alternative method');
                        
                        // Try direct manipulation
                        fullCalendarContainer.scrollTop = targetScrollTop;
                        
                        // Try scrollBy as alternative
                        const scrollDiff = targetScrollTop - newScrollTop;
                        if (Math.abs(scrollDiff) > 0) {
                            fullCalendarContainer.scrollBy({
                                top: scrollDiff,
                                behavior: 'smooth'
                            });
                        }
                    }
                }, 300);
            }
            
            // Also try native scrollIntoView as backup
            setTimeout(() => {
                eventElement.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center',
                    inline: 'nearest'
                });
            }, 200);
        }
        
        // Add click handler to calendar container to clear highlights when clicking empty space
        document.addEventListener('click', function(e) {
            if (e.target.closest('#fullCalendar') && 
                !e.target.closest('.fc-event') && 
                !e.target.closest('.event-list-item')) {
                clearAllHighlights();
            }
        });
        
        // Make functions globally accessible for onclick handlers
        window.navigateToEventDate = navigateToEventDate;
        window.clearAllHighlights = clearAllHighlights;
        window.scrollToCalendarEvent = scrollToCalendarEvent;
        window.highlightCalendarEvent = highlightCalendarEvent;
        window.highlightEventListItem = highlightEventListItem;
        
        // Create particle effects for the calendar
        function createParticles() {
            const container = document.querySelector('.calendar-overlay-content');
            if (!container) return;
            
            // Remove existing particles
            document.querySelectorAll('.calendar-particle').forEach(particle => {
                particle.remove();
            });
            
            // Create new particles with enhanced animation
            for (let i = 0; i < 30; i++) {
                const particle = document.createElement('div');
                particle.classList.add('calendar-particle');
                
                // Random styling with more variety
                const size = Math.random() * 6 + 1;
                const posX = Math.random() * 100;
                const posY = Math.random() * 100;
                const delay = Math.random() * 5;
                const duration = Math.random() * 15 + 10;
                
                // Random particle type (circle or square or star)
                const particleType = Math.floor(Math.random() * 3);
                let particleShape = 'border-radius: 50%;';
                
                if (particleType === 1) {
                    // Square particle
                    particleShape = 'border-radius: 2px; transform: rotate(45deg);';
                } else if (particleType === 2) {
                    // Star-like particle
                    particleShape = `
                        clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
                        transform: scale(${Math.random() * 0.5 + 0.5});
                    `;
                }
                
                // Random color between blue and purple gradient
                const hue = Math.floor(Math.random() * 60) + 220; // 220-280 range (blue to purple)
                const saturation = Math.floor(Math.random() * 40) + 60; // 60-100%
                const lightness = Math.floor(Math.random() * 30) + 60; // 60-90%
                
                // Apply enhanced styles
                particle.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    background: hsla(${hue}, ${saturation}%, ${lightness}%, ${Math.random() * 0.5 + 0.2});
                    ${particleShape}
                    top: ${posY}%;
                    left: ${posX}%;
                    pointer-events: none;
                    opacity: ${Math.random() * 0.6 + 0.3};
                    animation: float ${duration}s ease-in-out ${delay}s infinite;
                    filter: blur(${Math.random()}px);
                    box-shadow: 0 0 ${Math.floor(Math.random() * 8) + 2}px hsla(${hue}, ${saturation}%, ${lightness}%, 0.5);
                `;
                
                container.appendChild(particle);
            }
        }
    }
    
    // Check if the DOM is already loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeCalendar);
    } else {
        // DOM already loaded, run the function now
        initializeCalendar();
    }
})(); // End of self-executing function
</script>

<!-- Add a script to ensure proper calendar sizing -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Create a resize observer to adjust calendar size when its container changes
    if (typeof ResizeObserver !== 'undefined') {
        const calendarContainer = document.getElementById('fullCalendar');
        if (calendarContainer) {
            const resizeObserver = new ResizeObserver(entries => {
                // When container size changes, dispatch resize event to update calendar
                window.dispatchEvent(new Event('resize'));
            });
            
            // Start observing the calendar container
            resizeObserver.observe(calendarContainer);
        }
    }
    
    // Add extra handling for calendar overlay size
    const calendarOverlay = document.getElementById('calendarOverlay');
    if (calendarOverlay) {
        calendarOverlay.addEventListener('transitionend', function() {
            if (calendarOverlay.classList.contains('active')) {
                window.dispatchEvent(new Event('resize'));
            }
        });
    }
}); // End of DOMContentLoaded event listener
</script> 