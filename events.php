<?php
// Set secure session cookie parameters before session_start()
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => $cookieParams['lifetime'],
    'path' => $cookieParams['path'],
    'domain' => $cookieParams['domain'],
    'secure' => $secure, // Only send cookie over HTTPS
    'httponly' => true,  // Prevent JS access
    'samesite' => 'Strict' // Prevent CSRF
]);
session_start();

// Session idle timeout (30 seconds)
$timeout = 30; // 30 seconds
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    // Save current URL before logout
    $currentUrl = $_SERVER['REQUEST_URI'];
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1&redirect=' . urlencode($currentUrl));
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Show timeout message if redirected due to inactivity
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    echo '<div class="alert alert-warning text-center" style="margin: 20px;">Your session has expired due to inactivity. Please log in again.</div>';
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include autoloader for MongoDB
require __DIR__ . '/vendor/autoload.php';


// MongoDB connection function
function connectToMongoDB() {
    try {
        $mongoClient = new MongoDB\Client(require __DIR__ . '/src/config/mongo_uri.php');
        return $mongoClient->uiurp;
    } catch (Exception $e) {
        error_log("MongoDB connection failed:  " . $e->getMessage());
        return null;
    }
}

// Fetch events from MongoDB
function getEventsFromMongoDB() {
    $db = connectToMongoDB();
    if (!$db) {
        return [];
    }
    
    try {
        $collection = $db->events;
        $cursor = $collection->find([], ['sort' => ['eventDate' => 1]]);
        $events = [];
        
        foreach ($cursor as $event) {
            // Convert MongoDB document to array properly
            $eventArray = json_decode(json_encode($event), true);
            
            // Ensure _id is properly formatted
            if (isset($eventArray['_id']['$oid'])) {
                // Store both formats for compatibility
                $eventArray['_id_string'] = $eventArray['_id']['$oid'];
            } else {
                // If not in expected format, convert to string
                $eventArray['_id_string'] = (string)$eventArray['_id'];
            }
            
            // Helper function to convert MongoDB date
            $convertDate = function($dateValue) {
                if (is_array($dateValue) && isset($dateValue['$date'])) {
                    if (is_array($dateValue['$date']) && isset($dateValue['$date']['$numberLong'])) {
                        return date('Y-m-d\TH:i:s\Z', intval($dateValue['$date']['$numberLong']) / 1000);
                    } elseif (is_numeric($dateValue['$date'])) {
                        return date('Y-m-d\TH:i:s\Z', $dateValue['$date'] / 1000);
                    }
                }
                return $dateValue; // Return as-is if not a MongoDB date
            };
            
            // Convert MongoDB UTCDateTime objects to readable dates
            if (isset($eventArray['eventDate'])) {
                $eventArray['eventDate'] = $convertDate($eventArray['eventDate']);
            }
            if (isset($eventArray['createdAt'])) {
                $eventArray['createdAt'] = $convertDate($eventArray['createdAt']);
            }
            if (isset($eventArray['updatedAt'])) {
                $eventArray['updatedAt'] = $convertDate($eventArray['updatedAt']);
            }
            if (isset($eventArray['registration']['deadline'])) {
                $eventArray['registration']['deadline'] = $convertDate($eventArray['registration']['deadline']);
            }
            
            $events[] = $eventArray;
        }
        
        return $events;
    } catch (Exception $e) {
        error_log("Error fetching events: " . $e->getMessage());
        return [];
    }
}

// Get events data
$events = getEventsFromMongoDB();

// Handle filtering
$filteredEvents = $events;
$searchTerm = $_GET['search'] ?? '';
$eventType = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';

if ($searchTerm || $eventType || $status) {
    $filteredEvents = array_filter($events, function($event) use ($searchTerm, $eventType, $status) {
        $matchesSearch = !$searchTerm || 
            stripos($event['title'], $searchTerm) !== false || 
            stripos($event['description'], $searchTerm) !== false;
        
        $matchesType = !$eventType || $event['eventType'] === $eventType;
        $matchesStatus = !$status || $event['status'] === $status;
        
        return $matchesSearch && $matchesType && $matchesStatus;
    });
}

// Get unique event types and statuses for filters
$eventTypes = array_unique(array_column($events, 'eventType'));
$statuses = array_unique(array_column($events, 'status'));

// Function to check if the current user is the creator of an event
function isEventCreator($event) {
    // If user is not logged in, they can't be the creator
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Check if the event has a createdBy field and if it matches the current user's ID
    return isset($event['createdBy']) && $event['createdBy'] === $_SESSION['user_id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Events - UIU Research Portal</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css" rel="stylesheet">

    <link rel="stylesheet" href="assets/styles/events.css">
    
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
            <div class="container fade-in">
                <h1 class="page-title" data-aos="fade-up">University Events</h1>
                <p class="page-subtitle" data-aos="fade-up" data-aos-delay="100">
                    Discover upcoming conferences, seminars, workshops, and academic events at UIU
                </p>
            </div>
        </section>

        <!-- Filters Section -->
        <section class="container">
            <?php if (isset($_GET['deleted']) && $_GET['deleted'] === 'true'): ?>
            <div class="alert alert-success mb-4 fade-in alert-dismissible" role="alert" data-aos="fade-up">
                <i class="bi bi-check-circle-fill me-2"></i> Event has been deleted successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <div class="alert-countdown"></div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['created']) && $_GET['created'] === 'true'): ?>
            <div class="alert alert-success mb-4 fade-in alert-dismissible" role="alert" data-aos="fade-up">
                <i class="bi bi-check-circle-fill me-2"></i> Event has been created successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <div class="alert-countdown"></div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['updated']) && $_GET['updated'] === 'true'): ?>
            <div class="alert alert-success mb-4 fade-in alert-dismissible" role="alert" data-aos="fade-up">
                <i class="bi bi-check-circle-fill me-2"></i> Event has been updated successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <div class="alert-countdown"></div>
            </div>
            <?php endif; ?>
            
            <div class="filter-section slide-up" data-aos="fade-up" data-aos-delay="200">
                <div class="filter-header mb-3">
                    <h4 class="filter-title">Find Events</h4>
                    <p class="filter-subtitle">Use filters to find events that match your interests</p>
                </div>
                
                <form method="GET" action="">
                    <div class="row g-3 align-items-center">
                        <div class="col-xl-4 col-lg-4 col-md-6">
                            <div class="input-group filter-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input 
                                    type="text" 
                                    name="search" 
                                    class="form-control filter-input" 
                                    placeholder="Search events by title or description..." 
                                    value="<?= htmlspecialchars($searchTerm) ?>"
                                >
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-lg-3 col-md-6">
                            <div class="input-group filter-group">
                                <span class="input-group-text">
                                    <i class="bi bi-tag"></i>
                                </span>
                                <select name="type" class="form-control filter-select">
                                    <option value="">All Types</option>
                                    <?php foreach ($eventTypes as $type): ?>
                                        <option value="<?= htmlspecialchars($type) ?>" <?= $eventType === $type ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($type) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-lg-3 col-md-6">
                            <div class="input-group filter-group">
                                <span class="input-group-text">
                                    <i class="bi bi-flag"></i>
                                </span>
                                <select name="status" class="form-control filter-select">
                                    <option value="">All Status</option>
                                    <?php foreach ($statuses as $statusOption): ?>
                                        <option value="<?= htmlspecialchars($statusOption) ?>" <?= $status === $statusOption ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($statusOption) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-xl-2 col-lg-2 col-md-6">
                            <div class="buttons-wrapper d-flex justify-content-end">
                                <button type="submit" class="filter-btn ripple me-2" title="Apply Filters">
                                    <i class="bi bi-funnel"></i>
                                </button>
                                <button type="button" class="filter-btn view-calendar-btn ripple me-2" title="View Calendar">
                                    <i class="bi bi-calendar-week"></i>
                                </button>
                                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'faculty'): ?>
                                <a href="create_event.php" class="create-btn ripple" title="Create New Event">
                                    <i class="bi bi-plus-lg"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($searchTerm || $eventType || $status): ?>
                    <div class="active-filters mt-3">
                        <div class="d-flex align-items-center flex-wrap">
                            <span class="active-filter-label me-2">Active filters:</span>
                            
                            <?php if ($searchTerm): ?>
                            <span class="active-filter-badge">
                                <i class="bi bi-search me-1"></i>
                                <span class="filter-badge-text">"<?= htmlspecialchars($searchTerm) ?>"</span>
                                <a href="?<?= http_build_query(array_merge($_GET, ['search' => ''])) ?>" class="filter-remove">
                                    <i class="bi bi-x"></i>
                                </a>
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($eventType): ?>
                            <span class="active-filter-badge">
                                <i class="bi bi-tag me-1"></i>
                                <span class="filter-badge-text"><?= htmlspecialchars($eventType) ?></span>
                                <a href="?<?= http_build_query(array_merge($_GET, ['type' => ''])) ?>" class="filter-remove">
                                    <i class="bi bi-x"></i>
                                </a>
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($status): ?>
                            <span class="active-filter-badge">
                                <i class="bi bi-flag me-1"></i>
                                <span class="filter-badge-text"><?= htmlspecialchars($status) ?></span>
                                <a href="?<?= http_build_query(array_merge($_GET, ['status' => ''])) ?>" class="filter-remove">
                                    <i class="bi bi-x"></i>
                                </a>
                            </span>
                            <?php endif; ?>
                            
                            <a href="events.php" class="active-filter-clear ms-2">
                                <i class="bi bi-trash me-1"></i>Clear all
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </section>

        <!-- Events Grid -->
        <section class="container" id="events-container">
            <?php if (empty($filteredEvents)): ?>
                <div class="no-events" data-aos="fade-up">
                    <i class="bi bi-calendar-x"></i>
                    <h3>No Events Found</h3>
                    <p>No events match your current filters. Try adjusting your search criteria.</p>
                </div>
            <?php else: ?>
                <div class="events-grid">
                    <?php foreach ($filteredEvents as $index => $event): 
                        $eventDate = new DateTime($event['eventDate']);
                        $registrationDeadline = !empty($event['registration']['deadline']) 
                            ? new DateTime($event['registration']['deadline']) 
                            : null;
                        
                        // Determine button states
                        $hasRegistration = $event['registration']['required'] && !empty($event['registration']['link']);
                        $hasJoinLink = $event['location']['type'] === 'Virtual' && !empty($event['location']['joinLink']);
                    ?>
                        <?php
                        // Prepare tracking metadata for events
                        $trackingData = [
                            'title' => $event['title'],
                            'eventType' => $event['eventType'],
                            'organizer' => $event['organizer'],
                            'status' => $event['status'],
                            'tags' => [$event['eventType'], $event['status'], 'Event'],
                            'speakers' => !empty($event['speakers']) ? array_column($event['speakers'], 'name') : []
                        ];
                        ?>
                        <div class="event-card" 
                             data-aos="fade-up" 
                             data-aos-delay="<?= 50 + ($index * 50) ?>"
                             data-item-type="event" 
                             data-item-id="<?= $event['_id_string'] ?? $event['_id'] ?>"
                             data-tracking-metadata='<?= htmlspecialchars(json_encode($trackingData), ENT_QUOTES, 'UTF-8') ?>'>
                            <!-- Card Header -->
                            <div class="card-header">
                                <div class="event-header">
                                    <div class="event-title-container">
                                        <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                                        <div class="d-flex align-items-center">
                                            <span class="event-type me-2"><?= htmlspecialchars($event['eventType']) ?></span>
                                            <?php if (isEventCreator($event) || isset($_SESSION['admin'])): ?>
                                                <a href="edit_event.php?id=<?= $event['_id_string'] ?? $event['_id'] ?>" class="event-action-btn edit-btn me-1" title="Edit Event">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <a href="#" class="event-action-btn delete-btn" title="Delete Event" 
                                                   data-bs-toggle="modal" data-bs-target="#deleteModal<?= $event['_id_string'] ?? $event['_id'] ?>">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="event-status status-<?= strtolower($event['status']) ?>">
                                        <?= htmlspecialchars($event['status']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Scrollable Content Area -->
                            <div class="card-scroll-content">
                                <div class="event-content">
                                    <p class="event-description">
                                        <?= htmlspecialchars($event['description']) ?>
                                    </p>

                                    <div class="event-details">
                                        <div class="event-detail">
                                            <i class="bi bi-calendar-event"></i>
                                            <span class="event-detail-text"><?= $eventDate->format('F j, Y') ?></span>
                                        </div>
                                        <div class="event-detail">
                                            <i class="bi bi-clock"></i>
                                            <span class="event-detail-text"><?= htmlspecialchars($event['startTime']) ?> - <?= htmlspecialchars($event['endTime']) ?> (<?= htmlspecialchars($event['timeZone']) ?>)</span>
                                        </div>
                                        <div class="event-detail">
                                            <i class="bi bi-geo-alt"></i>
                                            <span class="event-detail-text">
                                                <?php if ($event['location']['type'] === 'Virtual'): ?>
                                                    Virtual - <?= htmlspecialchars($event['location']['virtualPlatform'] ?? 'Online') ?>
                                                <?php elseif ($event['location']['type'] === 'Physical'): ?>
                                                    <?= htmlspecialchars($event['location']['room'] ?? 'On Campus') ?>
                                                <?php else: ?>
                                                    Hybrid - <?= htmlspecialchars($event['location']['room'] ?? 'Multiple Locations') ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <div class="event-detail">
                                            <i class="bi bi-building"></i>
                                            <span class="event-detail-text"><?= htmlspecialchars($event['organizer']) ?></span>
                                        </div>
                                    </div>

                                    <div class="speakers-section">
                                        <div class="speakers-title">Featured Speakers:</div>
                                        <div class="speakers-container">
                                            <?php if (!empty($event['speakers'])): ?>
                                                <?php foreach ($event['speakers'] as $speaker): ?>
                                                    <div class="speaker-item">
                                                        <div class="speaker-name"><?= htmlspecialchars($speaker['name']) ?></div>
                                                        <div class="speaker-affiliation"><?= htmlspecialchars($speaker['affiliation']) ?></div>
                                                        <?php if (!empty($speaker['topic'])): ?>
                                                            <div class="speaker-topic"><?= htmlspecialchars($speaker['topic']) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="no-speakers">No featured speakers for this event</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Card Footer -->
                            <div class="card-footer">
                                <div class="event-actions">
                                    <div class="btn-row">
                                        <?php if ($hasRegistration): ?>
                                            <a href="<?= htmlspecialchars($event['registration']['link']) ?>" 
                                               target="_blank" 
                                               class="btn-primary ripple">
                                                <i class="bi bi-calendar-plus me-2"></i>Register
                                            </a>
                                        <?php elseif ($hasJoinLink): ?>
                                            <a href="<?= htmlspecialchars($event['location']['joinLink']) ?>" 
                                               target="_blank" 
                                               class="btn-primary ripple">
                                                <i class="bi bi-camera-video me-2"></i>Join Event
                                            </a>
                                        <?php else: ?>
                                            <span class="btn-primary btn-disabled">
                                                <i class="bi bi-calendar-x me-2"></i>No Registration
                                            </span>
                                        <?php endif; ?>
                                        
                                        <a href="#" 
                                           class="btn-outline ripple" 
                                           data-bs-toggle="modal" 
                                           data-bs-target="#detailsModal<?= $event['_id_string'] ?? $event['_id'] ?>">
                                            <i class="bi bi-info-circle me-2"></i>Details
                                        </a>
                                    </div>
                                    
                                    <div class="registration-info">
                                        <?php if ($registrationDeadline): ?>
                                            Registration deadline: <?= $registrationDeadline->format('M j, Y') ?>
                                        <?php else: ?>
                                            &nbsp; <!-- Empty space to maintain consistent height -->
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Back to Top Button -->
        <div class="back-to-top" id="backToTop">
            <i class="bi bi-arrow-up"></i>
        </div>
    </div>

    <?php
    // Include calendar overlay
    include 'calendar-overlay.php';
    
    // Include footer
    $footer_path = 'src/includes/footer.php';
    if (file_exists($footer_path)) {
        include $footer_path;
    }
    ?>
    
    <!-- Delete Modals -->
    <?php foreach ($filteredEvents as $event): 
        $eventId = $event['_id_string'] ?? $event['_id'];
    ?>
    <div class="modal fade" id="deleteModal<?= $eventId ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $eventId ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: var(--bg-card); border-color: var(--border-glass); border-radius: 15px;">
                <div class="modal-header" style="border-bottom-color: var(--border-glass);">
                    <h5 class="modal-title" id="deleteModalLabel<?= $eventId ?>" style="color: var(--text-primary);">Confirm Event Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
                </div>
                <div class="modal-body" style="color: var(--text-secondary);">
                    <p class="mb-2">Are you sure you want to delete this event?</p>
                    <p class="mb-0"><strong><?= htmlspecialchars($event['title']) ?></strong></p>
                    <p class="mt-3 text-danger"><i class="bi bi-exclamation-triangle me-2"></i>This action cannot be undone.</p>
                </div>
                <div class="modal-footer" style="border-top-color: var(--border-glass);">
                    <form method="POST" action="edit_event.php">
                        <input type="hidden" name="event_id" value="<?= $eventId ?>">
                        <button type="button" class="btn btn-cancel me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_event" class="btn btn-cancel" style="background: rgba(220, 53, 69, 0.1); border-color: #dc3545; color: #dc3545;">
                            <i class="bi bi-trash me-2"></i>Delete Permanently
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal<?= $eventId ?>" tabindex="-1" aria-labelledby="detailsModalLabel<?= $eventId ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="background: var(--bg-secondary); border-color: var(--border-glass); border-radius: 15px;">
                <div class="modal-header" style="border-bottom-color: var(--border-glass); background: var(--bg-primary);">
                    <h5 class="modal-title" id="detailsModalLabel<?= $eventId ?>" style="color: var(--text-primary);">
                        <?= htmlspecialchars($event['title']) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
                </div>
                <div class="modal-body" style="color: var(--text-secondary); background: var(--bg-secondary);">
                    <!-- Event Type and Status -->
                    <div class="d-flex align-items-center mb-4">
                        <span class="event-type me-2"><?= htmlspecialchars($event['eventType']) ?></span>
                        <span class="event-status status-<?= strtolower($event['status']) ?>">
                            <?= htmlspecialchars($event['status']) ?>
                        </span>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-2">Description</h6>
                        <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                    </div>

                    <!-- Event Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Event Information</h6>
                            <div class="event-details">
                                <div class="event-detail">
                                    <i class="bi bi-calendar-event"></i>
                                    <span class="event-detail-text">
                                        <?= (new DateTime($event['eventDate']))->format('F j, Y') ?>
                                    </span>
                                </div>
                                <div class="event-detail">
                                    <i class="bi bi-clock"></i>
                                    <span class="event-detail-text">
                                        <?= htmlspecialchars($event['startTime']) ?> - <?= htmlspecialchars($event['endTime']) ?>
                                        <br>(<?= htmlspecialchars($event['timeZone']) ?>)
                                    </span>
                                </div>
                                <div class="event-detail">
                                    <i class="bi bi-geo-alt"></i>
                                    <span class="event-detail-text">
                                        <?php if ($event['location']['type'] === 'Virtual'): ?>
                                            Virtual Event - <?= htmlspecialchars($event['location']['virtualPlatform'] ?? 'Online Platform') ?>
                                            <?php if (!empty($event['location']['joinLink'])): ?>
                                                <br><a href="<?= htmlspecialchars($event['location']['joinLink']) ?>" target="_blank" class="text-primary">Join Link</a>
                                            <?php endif; ?>
                                        <?php elseif ($event['location']['type'] === 'Physical'): ?>
                                            <?= htmlspecialchars($event['location']['room'] ?? 'On Campus') ?>
                                            <?php if (!empty($event['location']['address'])): ?>
                                                <br><?= htmlspecialchars($event['location']['address']) ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            Hybrid Event
                                            <br><?= htmlspecialchars($event['location']['room'] ?? 'Multiple Locations') ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="event-detail">
                                    <i class="bi bi-building"></i>
                                    <span class="event-detail-text">
                                        <?= htmlspecialchars($event['organizer']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Registration Details</h6>
                            <?php if ($event['registration']['required']): ?>
                                <div class="event-detail mb-2">
                                    <i class="bi bi-calendar-check"></i>
                                    <span class="event-detail-text">Registration Required</span>
                                </div>
                                <?php if (!empty($event['registration']['deadline'])): ?>
                                    <div class="event-detail mb-2">
                                        <i class="bi bi-calendar-x"></i>
                                        <span class="event-detail-text">
                                            Deadline: <?= (new DateTime($event['registration']['deadline']))->format('F j, Y') ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($event['registration']['fee'])): ?>
                                    <div class="event-detail mb-2">
                                        <i class="bi bi-cash"></i>
                                        <span class="event-detail-text">
                                            Registration Fee: <?= htmlspecialchars($event['registration']['fee']) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($event['registration']['link'])): ?>
                                    <div class="mt-3">
                                        <a href="<?= htmlspecialchars($event['registration']['link']) ?>" 
                                           target="_blank" 
                                           class="btn btn-primary btn-sm">
                                            <i class="bi bi-calendar-plus me-2"></i>Register Now
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="event-detail">
                                    <i class="bi bi-info-circle"></i>
                                    <span class="event-detail-text">No registration required</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Speakers Section -->
                    <?php if (!empty($event['speakers'])): ?>
                        <div class="speakers-section mt-4">
                            <h6 class="text-primary mb-3">Featured Speakers</h6>
                            <div class="row">
                                <?php foreach ($event['speakers'] as $speaker): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="speaker-item">
                                            <div class="speaker-name"><?= htmlspecialchars($speaker['name']) ?></div>
                                            <div class="speaker-affiliation"><?= htmlspecialchars($speaker['affiliation']) ?></div>
                                            <?php if (!empty($speaker['topic'])): ?>
                                                <div class="speaker-topic"><?= htmlspecialchars($speaker['topic']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Additional Information -->
                    <?php if (!empty($event['additionalInfo'])): ?>
                        <div class="mt-4">
                            <h6 class="text-primary mb-2">Additional Information</h6>
                            <p><?= nl2br(htmlspecialchars($event['additionalInfo'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer" style="border-top-color: var(--border-glass); background: var(--bg-primary);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <?php if ($event['registration']['required'] && !empty($event['registration']['link'])): ?>
                        <a href="<?= htmlspecialchars($event['registration']['link']) ?>" 
                           target="_blank" 
                           class="btn btn-primary">
                            <i class="bi bi-calendar-plus me-2"></i>Register Now
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js"></script>
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

        // Auto-hide alert messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-success');
            
            if (alerts.length > 0) {
                alerts.forEach(alert => {
                    // Add transition for smooth fade-out
                    alert.style.transition = 'opacity 0.7s ease, transform 0.7s ease';
                    
                    // Auto-dismiss timer
                    setTimeout(() => {
                        // Start fade out with slight upward movement
                        alert.style.opacity = '0';
                        alert.style.transform = 'translateY(-15px)';
                        
                        // Remove from DOM after fade completes
                        setTimeout(() => {
                            // Use Bootstrap's alert dismiss if available
                            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                            if (bsAlert) {
                                bsAlert.close();
                            } else {
                                alert.remove();
                            }
                        }, 700);
                    }, 5000);
                    
                    // Add click handler for manual close with animation
                    const closeBtn = alert.querySelector('.btn-close');
                    if (closeBtn) {
                        closeBtn.addEventListener('click', function() {
                            alert.style.opacity = '0';
                            alert.style.transform = 'translateY(-15px)';
                            
                            setTimeout(() => {
                                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                                if (bsAlert) {
                                    bsAlert.close();
                                } else {
                                    alert.remove();
                                }
                            }, 700);
                        });
                    }
                });
            }
            
            // Remove alerts from URL parameters after loading
            if (window.history.replaceState && (
                window.location.href.includes('deleted=true') || 
                window.location.href.includes('updated=true') || 
                window.location.href.includes('created=true')
            )) {
                const cleanUrl = window.location.href.split('?')[0];
                window.history.replaceState({}, document.title, cleanUrl);
            }
        });

        // Auto-submit form on filter change
        document.querySelectorAll('.filter-select').forEach(select => {
            select.addEventListener('change', function() {
                let filterContainer = this.closest('.filter-section');
                if (filterContainer) {
                    filterContainer.classList.add('submitting');
                }
                setTimeout(() => {
                    this.closest('form').submit();
                }, 300);
            });
        });
        
        // Filter input focus effects
        document.querySelectorAll('.filter-input, .filter-select').forEach(input => {
            input.addEventListener('focus', function() {
                this.closest('.filter-group').classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.closest('.filter-group').classList.remove('focused');
            });
        });
        
        // Add ripple effect to filter buttons
        document.querySelectorAll('.ripple').forEach(button => {
            button.addEventListener('click', function(e) {
                const x = e.clientX - this.getBoundingClientRect().left;
                const y = e.clientY - this.getBoundingClientRect().top;
                
                const rippleEffect = document.createElement('span');
                rippleEffect.classList.add('ripple-effect');
                rippleEffect.style.left = x + 'px';
                rippleEffect.style.top = y + 'px';
                
                this.appendChild(rippleEffect);
                
                setTimeout(() => {
                    rippleEffect.remove();
                }, 600);
            });
        });
        
        // Back to top button functionality
        const backToTopButton = document.getElementById('backToTop');
        
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTopButton.classList.add('visible');
            } else {
                backToTopButton.classList.remove('visible');
            }
        });
        
        backToTopButton.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Staggered card animation on scroll
        const eventCards = document.querySelectorAll('.event-card');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                    }, index * 100);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        eventCards.forEach(card => {
            card.classList.add('fade-in');
            observer.observe(card);
        });
        
        // Add animation for filter section on page load
        document.addEventListener('DOMContentLoaded', () => {
            const filterSection = document.querySelector('.filter-section');
            if (filterSection) {
                setTimeout(() => {
                    filterSection.classList.add('filter-visible');
                }, 300);
            }
            
            // Handle filter badge animations
            document.querySelectorAll('.active-filter-badge').forEach(badge => {
                badge.addEventListener('mouseenter', function() {
                    this.classList.add('badge-hover');
                });
                
                badge.addEventListener('mouseleave', function() {
                    this.classList.remove('badge-hover');
                });
            });
        });

        // Listen for theme changes from navbar
        document.addEventListener('themeChanged', function(e) {
            console.log('Theme changed to:', e.detail.theme);
            AOS.refresh();
        });
    </script>
</body>
</html> 