<?php
// Start session and error handling
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include autoloader for MongoDB
require __DIR__ . '/vendor/autoload.php';

// Check if delete request is submitted
if (isset($_POST['delete_event']) && isset($_POST['event_id'])) {
    $eventIdToDelete = $_POST['event_id'];
    
    // Debug information
    error_log("Delete request received for event ID: " . $eventIdToDelete);
    
    // Connect to MongoDB
    $db = connectToMongoDB();
    if ($db) {
        try {
            // Convert string ID to MongoDB ObjectId
            $objectId = new MongoDB\BSON\ObjectId($eventIdToDelete);
            
            // Delete the event
            $collection = $db->events;
            $result = $collection->deleteOne(['_id' => $objectId]);
            
            if ($result->getDeletedCount() > 0) {
                // Redirect to events page with success message
                header('Location: events.php?deleted=true');
                exit;
            } else {
                // Double check if event exists
                $event = $collection->findOne(['_id' => $objectId]);
                if (!$event) {
                    // Event doesn't exist, likely already deleted
                    header('Location: events.php?deleted=true');
                    exit;
                } else {
                    $errorMessage = "Failed to delete the event.";
                }
            }
        } catch (Exception $e) {
            $errorMessage = "Error deleting event: " . $e->getMessage();
            error_log("MongoDB delete error: " . $e->getMessage());
        }
    } else {
        $errorMessage = "Failed to connect to the database.";
    }
}

// MongoDB connection function
function connectToMongoDB() {
    try {
        $mongoClient = new MongoDB\Client(require __DIR__ . '/src/config/mongo_uri.php');
        return $mongoClient->uiurp;
    } catch (Exception $e) {
        error_log("MongoDB connection failed: " . $e->getMessage());
        return null;
    }
}

// Initialize variables
$success = false;
$errorMessage = '';
$event = null;

// Check if event ID is provided
if (!isset($_GET['id'])) {
    header('Location: events.php');
    exit;
}

// Get event ID from URL
$eventId = $_GET['id'];

// Connect to MongoDB
$db = connectToMongoDB();
if (!$db) {
    $errorMessage = "Failed to connect to the database.";
} else {
    try {
        // Convert string ID to MongoDB ObjectId
        $objectId = new MongoDB\BSON\ObjectId($eventId);
        
        // Find the event
        $collection = $db->events;
        $event = $collection->findOne(['_id' => $objectId]);
        
        if (!$event) {
            $errorMessage = "Event not found.";
        } else {
            // Convert MongoDB document to array
            $event = json_decode(json_encode($event), true);
            
            // Convert MongoDB dates to readable format
            if (isset($event['eventDate'])) {
                if (isset($event['eventDate']['$date'])) {
                    // Handle MongoDB date object format
                    if (is_string($event['eventDate']['$date'])) {
                        $event['eventDate'] = date('Y-m-d', strtotime($event['eventDate']['$date']));
                    } else if (is_array($event['eventDate']['$date']) && isset($event['eventDate']['$date']['$numberLong'])) {
                        $event['eventDate'] = date('Y-m-d', (int)$event['eventDate']['$date']['$numberLong'] / 1000);
                    } else {
                        // Timestamp in milliseconds
                        $event['eventDate'] = date('Y-m-d', (int)$event['eventDate']['$date'] / 1000);
                    }
                } else {
                    // Already processed date
                    $event['eventDate'] = date('Y-m-d', strtotime($event['eventDate']));
                }
            }
            
            // Convert registration deadline if exists
            if (isset($event['registration']['deadline'])) {
                if (isset($event['registration']['deadline']['$date'])) {
                    // Handle MongoDB date object
                    if (is_string($event['registration']['deadline']['$date'])) {
                        $event['registration']['deadline'] = date('Y-m-d', strtotime($event['registration']['deadline']['$date']));
                    } else if (is_array($event['registration']['deadline']['$date']) && isset($event['registration']['deadline']['$date']['$numberLong'])) {
                        $event['registration']['deadline'] = date('Y-m-d', (int)$event['registration']['deadline']['$date']['$numberLong'] / 1000);
                    } else {
                        // Timestamp in milliseconds
                        $event['registration']['deadline'] = date('Y-m-d', (int)$event['registration']['deadline']['$date'] / 1000);
                    }
                }
            }
        }
    } catch (Exception $e) {
        $errorMessage = "Error: " . $e->getMessage();
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_event'])) {
    try {
        // Debug info
        error_log("Processing event update for event ID: " . $eventId);
        
        // Get form data
        $title = $_POST['title'];
        $description = $_POST['description'];
        $eventType = $_POST['eventType'];
        $status = $_POST['status'];
        $eventDate = $_POST['eventDate'];
        $startTime = $_POST['startTime'];
        $endTime = $_POST['endTime'];
        $timeZone = $_POST['timeZone'];
        $organizer = $_POST['organizer'];
        
        // Location data
        $locationType = $_POST['locationType'];
        $locationData = [
            'type' => $locationType
        ];
        
        if ($locationType === 'Physical' || $locationType === 'Hybrid') {
            $locationData['room'] = $_POST['room'];
        }
        
        if ($locationType === 'Virtual' || $locationType === 'Hybrid') {
            $locationData['virtualPlatform'] = $_POST['virtualPlatform'];
            $locationData['joinLink'] = $_POST['joinLink'];
        }
        
        // Registration data
        $registrationRequired = isset($_POST['registrationRequired']) ? true : false;
        $registrationData = [
            'required' => $registrationRequired
        ];
        
        if ($registrationRequired) {
            $registrationData['link'] = $_POST['registrationLink'];
            if (!empty($_POST['registrationDeadline'])) {
                $registrationData['deadline'] = new MongoDB\BSON\UTCDateTime(strtotime($_POST['registrationDeadline']) * 1000);
            }
        }
        
        // Speakers data
        $speakers = [];
        if (!empty($_POST['speakerName'])) {
            foreach ($_POST['speakerName'] as $index => $name) {
                if (!empty($name)) {
                    $speaker = [
                        'name' => $name,
                        'affiliation' => $_POST['speakerAffiliation'][$index] ?? ''
                    ];
                    
                    if (!empty($_POST['speakerTopic'][$index])) {
                        $speaker['topic'] = $_POST['speakerTopic'][$index];
                    }
                    
                    $speakers[] = $speaker;
                }
            }
        }
        
        // Update event document
        $updateData = [
            'title' => $title,
            'description' => $description,
            'eventType' => $eventType,
            'status' => $status,
            'eventDate' => new MongoDB\BSON\UTCDateTime(strtotime($eventDate) * 1000),
            'startTime' => $startTime,
            'endTime' => $endTime,
            'timeZone' => $timeZone,
            'organizer' => $organizer,
            'location' => $locationData,
            'registration' => $registrationData,
            'speakers' => $speakers,
            'updatedAt' => new MongoDB\BSON\UTCDateTime(time() * 1000)
        ];
        
        // Preserve the createdBy field if it exists
        if (isset($event['createdBy'])) {
            $updateData['createdBy'] = $event['createdBy'];
        }
        
        // Preserve createdAt field if it exists
        if (isset($event['createdAt'])) {
            $updateData['createdAt'] = $event['createdAt'];
        }
        
        // Update in MongoDB
        $result = $collection->updateOne(
            ['_id' => $objectId],
            ['$set' => $updateData]
        );
        
        // Check if operation was acknowledged (success) or if any changes were actually made
        if ($result->isAcknowledged()) {
            // Log success for debugging
            error_log("Event updated successfully. Modified count: " . $result->getModifiedCount());
            
            // Redirect to events page with success message
            header('Location: events.php?updated=true');
            exit;
        } else {
            // Log error and throw exception
            error_log("Failed to update event. Operation not acknowledged.");
            throw new Exception("Failed to update the event. Please try again.");
        }
        
    } catch (Exception $e) {
        $errorMessage = "Error: " . $e->getMessage();
    }
}

// Get list of timezones
$timezones = DateTimeZone::listIdentifiers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - UIU Research Portal</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4c9af1 0%, #7209b7 100%);
            --secondary-gradient: linear-gradient(135deg, #f72585 0%, #4cc9f0 100%);
            --neo-primary: #4361ee;
            --neo-secondary: #3a0ca3;
            --neo-accent: #7209b7;
            --neo-blue: #4cc9f0;
            --neo-magenta: #f72585;
            --neo-light: #f8f9fa;
            --neo-dark: #0a1121;
            
            /* Theme Variables - Dark Mode (Default) */
            --bg-primary: #0a1121;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --bg-card: rgba(30, 41, 59, 0.8);
            --bg-glass: rgba(255, 255, 255, 0.1);
            --border-glass: rgba(255, 255, 255, 0.2);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.8);
            --text-muted: rgba(255, 255, 255, 0.6);
            --border-color: rgba(76, 201, 240, 0.1);
            --shadow-color: rgba(0, 0, 0, 0.3);
        }

        /* Light Theme Variables */
        [data-theme="light"] {
            --bg-primary: #f8fafc;
            --bg-secondary: #e2e8f0;
            --bg-tertiary: #cbd5e1;
            --bg-card: rgba(255, 255, 255, 0.9);
            --bg-glass: rgba(255, 255, 255, 0.8);
            --border-glass: rgba(67, 97, 238, 0.2);
            --text-primary: #1e293b;
            --text-secondary: rgba(30, 41, 59, 0.8);
            --text-muted: rgba(30, 41, 59, 0.6);
            --border-color: rgba(67, 97, 238, 0.15);
            --shadow-color: rgba(0, 0, 0, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        .main-container {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            position: relative;
            padding-bottom: 100px;
        }

        /* Background Elements */
        .bg-gradient {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 50%, rgba(114, 9, 183, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(247, 37, 133, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 40% 80%, rgba(76, 201, 240, 0.1) 0%, transparent 50%);
            z-index: -2;
        }

        .floating-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            background: var(--neo-blue);
            border-radius: 50%;
            opacity: 0.1;
            animation: float 20s infinite linear;
        }

        .particle:nth-child(1) { width: 2px; height: 2px; top: 20%; left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 3px; height: 3px; top: 60%; left: 70%; animation-delay: 5s; }
        .particle:nth-child(3) { width: 1px; height: 1px; top: 80%; left: 20%; animation-delay: 10s; }
        .particle:nth-child(4) { width: 4px; height: 4px; top: 40%; left: 90%; animation-delay: 15s; }
        .particle:nth-child(5) { width: 2px; height: 2px; top: 30%; left: 30%; animation-delay: 7s; }
        .particle:nth-child(6) { width: 3px; height: 3px; top: 70%; left: 60%; animation-delay: 12s; }

        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
            100% { transform: translateY(0px) rotate(360deg); }
        }

        /* Header Section */
        .page-header {
            padding: 8rem 0 3rem;
            text-align: center;
            position: relative;
            margin-top: 75px;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200px;
            background: var(--primary-gradient);
            top: -150px;
            left: -50%;
            transform: rotate(-2deg);
            opacity: 0.05;
            z-index: 0;
        }

        .page-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            display: inline-block;
        }

        .page-title::after {
            content: '';
            position: absolute;
            width: 80px;
            height: 4px;
            background: var(--neo-blue);
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 2px;
        }

        .page-subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 2rem auto 1rem;
            position: relative;
        }

        /* Form Card */
        .form-card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 3rem;
            transform: translateY(0);
            transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
            box-shadow: 0 10px 30px var(--shadow-color);
            animation: fadeIn 1s ease-out;
        }

        .form-card:hover {
            box-shadow: 0 15px 40px rgba(76, 201, 240, 0.15);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-title {
            color: var(--text-primary);
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            position: relative;
            font-size: 1.5rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            height: 3px;
            width: 50px;
            background: var(--neo-blue);
        }

        .form-section {
            margin-bottom: 2.5rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--border-color);
        }

        .form-section:last-child {
            margin-bottom: 1rem;
            padding-bottom: 0;
            border-bottom: none;
        }

        .form-label {
            color: var(--text-secondary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            background-color: var(--bg-glass);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            background-color: var(--bg-glass);
            border-color: var(--neo-blue);
            box-shadow: 0 0 0 0.2rem rgba(76, 201, 240, 0.25);
            color: var(--text-primary);
        }

        .form-control::placeholder {
            color: var(--text-muted);
        }

        .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-position: right 1rem center;
        }

        [data-theme="light"] .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%231e293b' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        }

        .form-check-label {
            color: var(--text-secondary);
        }

        .form-check-input {
            background-color: var(--bg-glass);
            border-color: var(--border-color);
        }

        .form-check-input:checked {
            background-color: var(--neo-blue);
            border-color: var(--neo-blue);
        }

        /* Fix for dark-themed dropdowns and form elements */
        select.form-select option {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
        }

        input[type="date"], input[type="time"] {
            color-scheme: auto;
        }

        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus,
        textarea:-webkit-autofill,
        textarea:-webkit-autofill:hover,
        textarea:-webkit-autofill:focus,
        select:-webkit-autofill,
        select:-webkit-autofill:hover,
        select:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--text-primary);
            -webkit-box-shadow: 0 0 0px 1000px var(--bg-secondary) inset;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Enhanced form styling */
        .form-control:focus, .form-select:focus {
            background-color: var(--bg-glass);
            border-color: var(--neo-blue);
            box-shadow: 0 0 0 0.2rem rgba(76, 201, 240, 0.25);
            color: var(--text-primary);
            outline: none;
        }

        .form-control, .form-select {
            transition: all 0.3s ease;
        }

        .form-select:hover, .form-control:hover {
            border-color: rgba(76, 201, 240, 0.5);
        }

        /* Form switch enhancements */
        .form-switch .form-check-input {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='rgba(255, 255, 255, 0.8)'/%3e%3c/svg%3e");
            height: 1.5em;
        }

        [data-theme="light"] .form-switch .form-check-input {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='rgba(30, 41, 59, 0.8)'/%3e%3c/svg%3e");
        }

        .form-switch .form-check-input:focus {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='rgba(255, 255, 255, 1)'/%3e%3c/svg%3e");
        }

        [data-theme="light"] .form-switch .form-check-input:focus {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='rgba(30, 41, 59, 1)'/%3e%3c/svg%3e");
        }

        .form-switch .form-check-input:checked {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e");
            background-position: right center;
        }

        /* Animation for form fields */
        .form-control:focus, .form-select:focus {
            transform: translateY(-2px);
        }

        /* Placeholder text */
        ::placeholder {
            color: var(--text-muted) !important;
            opacity: 0.7 !important;
        }

        .location-options, .registration-options, .speaker-fields {
            display: none;
        }

        .speakers-container {
            margin-top: 1rem;
        }

        .speaker-card {
            background: var(--bg-glass);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            transition: all 0.3s ease;
        }

        .speaker-card:hover {
            background: var(--bg-glass);
            opacity: 0.9;
        }

        .speaker-number {
            position: absolute;
            top: -10px;
            left: -10px;
            background: var(--secondary-gradient);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .remove-speaker {
            position: absolute;
            top: 10px;
            right: 10px;
            color: var(--text-muted);
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 5px;
        }

        .remove-speaker:hover {
            color: #dc3545;
        }

        .btn-add-speaker {
            background: rgba(76, 201, 240, 0.1);
            border: 1px dashed rgba(76, 201, 240, 0.5);
            color: var(--neo-blue);
            border-radius: 12px;
            padding: 0.75rem;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-add-speaker:hover {
            background: rgba(76, 201, 240, 0.2);
            border-color: var(--neo-blue);
            color: var(--neo-blue);
        }

        .btn-submit {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.6s ease;
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(76, 201, 240, 0.3);
        }

        .btn-cancel {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: var(--bg-glass);
            color: var(--text-primary);
        }

        .alert {
            border-radius: 12px;
            background: rgba(25, 135, 84, 0.1);
            border: 1px solid rgba(25, 135, 84, 0.2);
            color: #198754;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }

        /* Custom scrollbar styles */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-glass);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(76, 201, 240, 0.3);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(76, 201, 240, 0.5);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2.5rem;
            }
            
            .form-card {
                padding: 1.5rem;
            }
        }

        /* Page loader styles */
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--bg-primary);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        .loader-hidden {
            opacity: 0;
            visibility: hidden;
        }

        .loader-circle {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-top-color: var(--neo-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    
    <!-- Theme detector script - Must run before page rendering -->
    <script>
        (function() {
            // Get saved theme immediately to prevent flash
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                document.documentElement.setAttribute('data-theme', savedTheme);
            }
        })();
    </script>
</head>
<body>
    <!-- Page Loader -->
    <div class="page-loader">
        <div class="loader-circle"></div>
    </div>

    <!-- Background Elements -->
    <div class="bg-gradient"></div>
    <div class="floating-particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="main-container">
        <?php
        // Include navbar
        $navbar_path = 'src/includes/navbar.php';
        if (file_exists($navbar_path)) {
            include $navbar_path;
        } else {
            // Fallback navbar
            echo '<nav class="navbar navbar-expand-lg navbar-dark">
                <div class="container">
                    <a class="navbar-brand" href="index.php">UIU Research Portal</a>
                    <div class="navbar-nav ms-auto">
                        <a class="nav-link" href="index.php">Home</a>
                        <a class="nav-link" href="Research_page.php">Projects</a>
                        <a class="nav-link" href="Faculty_Page.php">Faculty</a>
                        <a class="nav-link active" href="events.php">Events</a>
                        <a class="nav-link" href="forum_index.php">Forum</a>
                    </div>
                </div>
            </nav>';
        }
        ?>

        <!-- Page Header -->
        <section class="page-header">
            <div class="container">
                <h1 class="page-title" data-aos="fade-up">Edit Event</h1>
                <p class="page-subtitle" data-aos="fade-up" data-aos-delay="100">
                    Update event details in the UIU research portal calendar
                </p>
            </div>
        </section>

        <!-- Event Form -->
        <section class="container">
            <?php if ($success): ?>
                <div class="alert alert-success mb-4" role="alert" data-aos="fade-up">
                    <i class="bi bi-check-circle-fill me-2"></i> Event updated successfully!
                    <div class="mt-2">
                        <a href="events.php" class="btn btn-sm btn-outline-success me-2">View All Events</a>
                        <a href="edit_event.php?id=<?= $eventId ?>" class="btn btn-sm btn-outline-success">Edit Again</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger mb-4" role="alert" data-aos="fade-up">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $errorMessage ?>
                </div>
            <?php endif; ?>

            <?php if ($event): ?>
            <div class="form-card" data-aos="fade-up">
                <form method="POST" action="" id="eventForm">
                    <!-- Basic Information -->
                    <div class="form-section">
                        <h3 class="section-title">Basic Information</h3>
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label for="title" class="form-label">Event Title *</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($event['title']) ?>" required>
                            </div>
                            <div class="col-md-12">
                                <label for="description" class="form-label">Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?= htmlspecialchars($event['description']) ?></textarea>
                            </div>
                            <div class="col-md-4">
                                <label for="eventType" class="form-label">Event Type *</label>
                                <select class="form-select" id="eventType" name="eventType" required>
                                    <option value="" disabled>Select event type</option>
                                    <option value="Conference" <?= $event['eventType'] === 'Conference' ? 'selected' : '' ?>>Conference</option>
                                    <option value="Workshop" <?= $event['eventType'] === 'Workshop' ? 'selected' : '' ?>>Workshop</option>
                                    <option value="Seminar" <?= $event['eventType'] === 'Seminar' ? 'selected' : '' ?>>Seminar</option>
                                    <option value="Webinar" <?= $event['eventType'] === 'Webinar' ? 'selected' : '' ?>>Webinar</option>
                                    <option value="Meeting" <?= $event['eventType'] === 'Meeting' ? 'selected' : '' ?>>Meeting</option>
                                    <option value="Symposium" <?= $event['eventType'] === 'Symposium' ? 'selected' : '' ?>>Symposium</option>
                                    <option value="Panel Discussion" <?= $event['eventType'] === 'Panel Discussion' ? 'selected' : '' ?>>Panel Discussion</option>
                                    <option value="Training" <?= $event['eventType'] === 'Training' ? 'selected' : '' ?>>Training</option>
                                    <option value="Other" <?= $event['eventType'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="" disabled>Select status</option>
                                    <option value="Scheduled" <?= $event['status'] === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                    <option value="Completed" <?= $event['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="Cancelled" <?= $event['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="organizer" class="form-label">Organizer *</label>
                                <input type="text" class="form-control" id="organizer" name="organizer" value="<?= htmlspecialchars($event['organizer']) ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Date and Time -->
                    <div class="form-section">
                        <h3 class="section-title">Date & Time</h3>
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label for="eventDate" class="form-label">Event Date *</label>
                                <input type="date" class="form-control" id="eventDate" name="eventDate" value="<?= htmlspecialchars($event['eventDate']) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="startTime" class="form-label">Start Time *</label>
                                <input type="time" class="form-control" id="startTime" name="startTime" value="<?= htmlspecialchars($event['startTime']) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="endTime" class="form-label">End Time *</label>
                                <input type="time" class="form-control" id="endTime" name="endTime" value="<?= htmlspecialchars($event['endTime']) ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label for="timeZone" class="form-label">Time Zone *</label>
                                <select class="form-select" id="timeZone" name="timeZone" required>
                                    <option value="" disabled>Select</option>
                                    <option value="GMT" <?= $event['timeZone'] === 'GMT' ? 'selected' : '' ?>>GMT</option>
                                    <option value="UTC" <?= $event['timeZone'] === 'UTC' ? 'selected' : '' ?>>UTC</option>
                                    <option value="EST" <?= $event['timeZone'] === 'EST' ? 'selected' : '' ?>>EST</option>
                                    <option value="CST" <?= $event['timeZone'] === 'CST' ? 'selected' : '' ?>>CST</option>
                                    <option value="PST" <?= $event['timeZone'] === 'PST' ? 'selected' : '' ?>>PST</option>
                                    <option value="IST" <?= $event['timeZone'] === 'IST' ? 'selected' : '' ?>>IST</option>
                                    <option value="JST" <?= $event['timeZone'] === 'JST' ? 'selected' : '' ?>>JST</option>
                                    <option value="AEST" <?= $event['timeZone'] === 'AEST' ? 'selected' : '' ?>>AEST</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Location Information -->
                    <div class="form-section">
                        <h3 class="section-title">Location</h3>
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label for="locationType" class="form-label">Location Type *</label>
                                <select class="form-select" id="locationType" name="locationType" required>
                                    <option value="" disabled>Select location type</option>
                                    <option value="Physical" <?= $event['location']['type'] === 'Physical' ? 'selected' : '' ?>>Physical</option>
                                    <option value="Virtual" <?= $event['location']['type'] === 'Virtual' ? 'selected' : '' ?>>Virtual</option>
                                    <option value="Hybrid" <?= $event['location']['type'] === 'Hybrid' ? 'selected' : '' ?>>Hybrid</option>
                                </select>
                            </div>
                            
                            <!-- Physical Location Options -->
                            <div class="col-md-12 location-options physical-options" style="display: <?= ($event['location']['type'] === 'Physical' || $event['location']['type'] === 'Hybrid') ? 'block' : 'none' ?>;">
                                <label for="room" class="form-label">Room/Location *</label>
                                <input type="text" class="form-control" id="room" name="room" value="<?= htmlspecialchars($event['location']['room'] ?? '') ?>" <?= ($event['location']['type'] === 'Physical' || $event['location']['type'] === 'Hybrid') ? 'required' : '' ?>>
                            </div>
                            
                            <!-- Virtual Location Options -->
                            <div class="col-md-6 location-options virtual-options" style="display: <?= ($event['location']['type'] === 'Virtual' || $event['location']['type'] === 'Hybrid') ? 'block' : 'none' ?>;">
                                <label for="virtualPlatform" class="form-label">Virtual Platform *</label>
                                <input type="text" class="form-control" id="virtualPlatform" name="virtualPlatform" value="<?= htmlspecialchars($event['location']['virtualPlatform'] ?? '') ?>" placeholder="Zoom, Google Meet, etc." <?= ($event['location']['type'] === 'Virtual' || $event['location']['type'] === 'Hybrid') ? 'required' : '' ?>>
                            </div>
                            <div class="col-md-6 location-options virtual-options" style="display: <?= ($event['location']['type'] === 'Virtual' || $event['location']['type'] === 'Hybrid') ? 'block' : 'none' ?>;">
                                <label for="joinLink" class="form-label">Join Link</label>
                                <input type="url" class="form-control" id="joinLink" name="joinLink" value="<?= htmlspecialchars($event['location']['joinLink'] ?? '') ?>" placeholder="https://">
                            </div>
                        </div>
                    </div>

                    <!-- Registration Information -->
                    <div class="form-section">
                        <h3 class="section-title">Registration</h3>
                        <div class="row g-4">
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="registrationRequired" name="registrationRequired" <?= $event['registration']['required'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="registrationRequired">Registration Required</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 registration-options" style="display: <?= $event['registration']['required'] ? 'block' : 'none' ?>;">
                                <label for="registrationLink" class="form-label">Registration Link *</label>
                                <input type="url" class="form-control" id="registrationLink" name="registrationLink" value="<?= htmlspecialchars($event['registration']['link'] ?? '') ?>" placeholder="https://" <?= $event['registration']['required'] ? 'required' : '' ?>>
                            </div>
                            <div class="col-md-6 registration-options" style="display: <?= $event['registration']['required'] ? 'block' : 'none' ?>;">
                                <label for="registrationDeadline" class="form-label">Registration Deadline</label>
                                <input type="date" class="form-control" id="registrationDeadline" name="registrationDeadline" value="<?= htmlspecialchars($event['registration']['deadline'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Speakers Information -->
                    <div class="form-section">
                        <h3 class="section-title">Speakers</h3>
                        <div class="speakers-container" id="speakersContainer">
                            <?php if (!empty($event['speakers'])): ?>
                                <?php foreach ($event['speakers'] as $index => $speaker): ?>
                                    <div class="speaker-card">
                                        <div class="speaker-number"><?= $index + 1 ?></div>
                                        <button type="button" class="remove-speaker" onclick="removeSpeaker(this)">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Name</label>
                                                <input type="text" class="form-control" name="speakerName[]" value="<?= htmlspecialchars($speaker['name']) ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Affiliation</label>
                                                <input type="text" class="form-control" name="speakerAffiliation[]" value="<?= htmlspecialchars($speaker['affiliation'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Topic</label>
                                                <input type="text" class="form-control" name="speakerTopic[]" value="<?= htmlspecialchars($speaker['topic'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-add-speaker" id="addSpeaker">
                            <i class="bi bi-plus-circle me-2"></i> Add Speaker
                        </button>
                    </div>

                    <!-- Form Actions -->
                    <div class="row g-4 mt-4">
                        <div class="col-md-12 text-center">
                            <button type="submit" class="btn btn-submit px-5 me-3">
                                <i class="bi bi-save me-2"></i> Update Event
                            </button>
                            <a href="events.php" class="btn btn-cancel me-3">
                                <i class="bi bi-x-circle me-2"></i> Cancel
                            </a>
                            <button type="button" class="btn btn-cancel" style="background: rgba(220, 53, 69, 0.1); border-color: #dc3545; color: #dc3545;" data-bs-toggle="modal" data-bs-target="#deleteEventModal">
                                <i class="bi bi-trash me-2"></i> Delete Event
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <?php else: ?>
                <div class="alert alert-danger" role="alert" data-aos="fade-up">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Event not found or you don't have permission to edit it.
                    <div class="mt-2">
                        <a href="events.php" class="btn btn-sm btn-outline-danger">Back to Events</a>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <?php
    // Include footer
    $footer_path = 'src/includes/footer.php';
    if (file_exists($footer_path)) {
        include $footer_path;
    }
    ?>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteEventModal" tabindex="-1" aria-labelledby="deleteEventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: var(--bg-card); border-color: var(--border-glass); border-radius: 15px;">
                <div class="modal-header" style="border-bottom-color: var(--border-glass);">
                    <h5 class="modal-title" id="deleteEventModalLabel" style="color: var(--text-primary);">Confirm Event Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
                </div>
                <div class="modal-body" style="color: var(--text-secondary);">
                    <p class="mb-2">Are you sure you want to delete this event?</p>
                    <p class="mb-0"><strong><?= htmlspecialchars($event['title'] ?? 'This event') ?></strong></p>
                    <p class="mt-3 text-danger"><i class="bi bi-exclamation-triangle me-2"></i>This action cannot be undone.</p>
                </div>
                <div class="modal-footer" style="border-top-color: var(--border-glass);">
                    <form method="POST" action="">
                        <input type="hidden" name="event_id" value="<?= htmlspecialchars($eventId) ?>">
                        <button type="button" class="btn btn-cancel me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_event" class="btn btn-cancel" style="background: rgba(220, 53, 69, 0.1); border-color: #dc3545; color: #dc3545;">
                            <i class="bi bi-trash me-2"></i>Delete Permanently
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Page loader
        window.addEventListener('load', () => {
            setTimeout(() => {
                document.querySelector('.page-loader').classList.add('loader-hidden');
            }, 500);
        });
        
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 100,
            easing: 'ease-out-cubic'
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            // Location Type Toggle
            const locationType = document.getElementById('locationType');
            const physicalOptions = document.querySelectorAll('.physical-options');
            const virtualOptions = document.querySelectorAll('.virtual-options');
            
            locationType.addEventListener('change', function() {
                const selectedType = this.value;
                
                // Hide all option groups first
                physicalOptions.forEach(el => el.style.display = 'none');
                virtualOptions.forEach(el => el.style.display = 'none');
                
                // Then show relevant ones based on selection
                if (selectedType === 'Physical' || selectedType === 'Hybrid') {
                    physicalOptions.forEach(el => el.style.display = 'block');
                    
                    // Set required attribute for physical fields
                    document.getElementById('room').setAttribute('required', '');
                } else {
                    document.getElementById('room').removeAttribute('required');
                }
                
                if (selectedType === 'Virtual' || selectedType === 'Hybrid') {
                    virtualOptions.forEach(el => el.style.display = 'block');
                    
                    // Set required attribute for virtual fields
                    document.getElementById('virtualPlatform').setAttribute('required', '');
                } else {
                    document.getElementById('virtualPlatform').removeAttribute('required');
                }
            });
            
            // Registration Toggle
            const registrationRequired = document.getElementById('registrationRequired');
            const registrationOptions = document.querySelectorAll('.registration-options');
            
            registrationRequired.addEventListener('change', function() {
                if (this.checked) {
                    registrationOptions.forEach(el => el.style.display = 'block');
                    document.getElementById('registrationLink').setAttribute('required', '');
                } else {
                    registrationOptions.forEach(el => el.style.display = 'none');
                    document.getElementById('registrationLink').removeAttribute('required');
                }
            });
            
            // Add Speaker Functionality
            const addSpeakerButton = document.getElementById('addSpeaker');
            const speakersContainer = document.getElementById('speakersContainer');
            let speakerCount = document.querySelectorAll('.speaker-card').length;
            
            addSpeakerButton.addEventListener('click', function() {
                speakerCount++;
                
                const speakerCard = document.createElement('div');
                speakerCard.classList.add('speaker-card');
                speakerCard.innerHTML = `
                    <div class="speaker-number">${speakerCount}</div>
                    <button type="button" class="remove-speaker" onclick="removeSpeaker(this)">
                        <i class="bi bi-x-circle"></i>
                    </button>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="speakerName[]" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Affiliation</label>
                            <input type="text" class="form-control" name="speakerAffiliation[]">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Topic</label>
                            <input type="text" class="form-control" name="speakerTopic[]">
                        </div>
                    </div>
                `;
                
                speakersContainer.appendChild(speakerCard);
                
                // Apply fade-in animation to the new speaker card
                setTimeout(() => {
                    speakerCard.style.opacity = '1';
                }, 10);
            });
            
            // Add the first speaker by default if none exist
            if (speakerCount === 0) {
                addSpeakerButton.click();
            }

            // Listen for theme changes from navbar
            document.addEventListener('themeChanged', function(e) {
                // You can add specific actions when theme changes if needed
                console.log('Theme changed to:', e.detail.theme);
                
                // Optionally refresh any components that might need manual updating
                AOS.refresh();
            });
        });
        
        // Function to remove speaker
        function removeSpeaker(button) {
            const speakerCard = button.closest('.speaker-card');
            speakerCard.style.opacity = '0';
            
            setTimeout(() => {
                speakerCard.remove();
                
                // Renumber remaining speakers
                const speakerNumbers = document.querySelectorAll('.speaker-number');
                speakerNumbers.forEach((number, index) => {
                    number.textContent = index + 1;
                });
            }, 300);
        }
    </script>
</body>
</html> 