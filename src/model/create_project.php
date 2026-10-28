<?php
require_once 'db_connect.php';
require_once 'send_system_chat_message_helper.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/GridFSUploadHandler.php';
require_once __DIR__ . '/FileConfig.php';
require_once __DIR__ . '/RateLimiter.php';
require_once __DIR__ . '/ProjectKeyManager.php';
require_once __DIR__ . '/EncryptionConfig.php';
require_once __DIR__ . '/ChatEncryption.php';

// Import MongoDB BSON types
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;
use UIURP\Model\GridFSUploadHandler;
use UIURP\Model\FileConfig;
use UIURP\Model\RateLimiter;

// Start session to capture user data if available
session_start();

// Session logging removed

// Set user ID and username (null if not logged in)
$userId = null;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
} elseif (isset($_SESSION['user_data']) && isset($_SESSION['user_data']['_id']) && isset($_SESSION['user_data']['_id']['$oid'])) {
    // Try to get ID from user_data if available
    $userId = $_SESSION['user_data']['_id']['$oid'];
} elseif (isset($_SESSION['user_data']) && isset($_SESSION['user_data']['_id'])) {
    // Fall back to string representation of ID if present
    $userId = (string)$_SESSION['user_data']['_id'];
}

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 
           (isset($_SESSION['user_data']['name']) ? $_SESSION['user_data']['name'] : 'Anonymous User');

// User ID logging removed

// Set dates
if (!empty($_POST['createdAt'])) {
    $createdDate = new UTCDateTime(strtotime($_POST['createdAt']) * 1000);
} else {
    $createdDate = new UTCDateTime();
}

if (!empty($_POST['updatedAt'])) {
    $updatedDate = new UTCDateTime(strtotime($_POST['updatedAt']) * 1000);
} else {
    $updatedDate = new UTCDateTime();
}

// Validate required fields
if (empty($_POST['title']) || empty($_POST['abstract']) || empty($_POST['field'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Required fields are missing'
    ]);
    exit;
}

// Get members data
$members = [];
if (!empty($_POST['members'])) {
    $membersData = json_decode($_POST['members'], true);
    
    // Add current user as a member if logged in and not already included
    if ($userId !== null) {
        $userIncluded = false;
        foreach ($membersData as $member) {
            if (isset($member['userId']) && $member['userId']['$oid'] === $userId) {
                $userIncluded = true;
                break;
            }
        }
        
        if (!$userIncluded) {
            // Add current user as the first member
            array_unshift($membersData, [
                'userId' => ['$oid' => $userId],
                'name' => $username,
                'role' => 'Author',
                'contribution' => 100
            ]);
        }
    }
    
    $members = $membersData;
}

// Process keywords
$keywords = [];
if (!empty($_POST['keywords'])) {
    $keywords = json_decode($_POST['keywords'], true);
}

// Handle image upload
$coverImage = [
    'url' => '',
    'alt' => $_POST['title'],
    'fallback' => '/assets/resources/research_picture/pub_' . rand(1, 10) . '.jpg'
];

if (!empty($_FILES['coverImage']['tmp_name'])) {
    $uploadDir = __DIR__ . '/../../storage/images/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileExtension = pathinfo($_FILES['coverImage']['name'], PATHINFO_EXTENSION);
    $fileName = 'project_' . time() . '_' . uniqid() . '.' . $fileExtension;
    $targetFile = $uploadDir . $fileName;
    
    if (move_uploaded_file($_FILES['coverImage']['tmp_name'], $targetFile)) {
        $coverImage['url'] = '/storage/images/' . $fileName;
    }
}

// Process timeline data
$timeline = [];
if (!empty($_POST['timeline'])) {
    $timelineData = json_decode($_POST['timeline'], true);
    if (is_array($timelineData)) {
        // Ensure each timeline item has the required fields
        foreach ($timelineData as $item) {
            $timelineItem = [
                'title' => isset($item['title']) ? $item['title'] : '',
                'description' => isset($item['description']) ? $item['description'] : '',
                'date' => isset($item['date']) ? $item['date'] : '',
                'status' => isset($item['status']) ? $item['status'] : 'Planned',
                'assignedBy' => isset($item['assignedBy']) ? $item['assignedBy'] : '',
                'assignedTo' => isset($item['assignedTo']) ? $item['assignedTo'] : []
            ];
            
            // Ensure assignedTo is always an array
            if (!is_array($timelineItem['assignedTo'])) {
                $timelineItem['assignedTo'] = $timelineItem['assignedTo'] ? [$timelineItem['assignedTo']] : [];
            }
            
            $timeline[] = $timelineItem;
        }
    }
}

// Process supervisor
$supervisor = null;
if (!empty($_POST['supervisor'])) {
    $supervisorName = $_POST['supervisor'];
    $supervisorId = !empty($_POST['supervisorId']) ? $_POST['supervisorId'] : null;
    
    if ($supervisorId) {
        // Faculty supervisor with ID - fetch faculty details
        try {
            $facultyCollection = $db->faculties;
            $faculty = $facultyCollection->findOne(['_id' => new ObjectId($supervisorId)]);
            
            if ($faculty) {
                $supervisor = [
                    'userId' => ['$oid' => $supervisorId],
                    'name' => $faculty['name'],
                    'role' => 'Supervisor'
                ];
                
                // Add additional faculty info if available
                if (isset($faculty['email'])) {
                    $supervisor['email'] = $faculty['email'];
                }
                if (isset($faculty['department'])) {
                    $supervisor['department'] = $faculty['department'];
                }
            } else {
                // Faculty ID provided but not found - treat as custom supervisor
                $supervisor = [
                    'name' => $supervisorName,
                    'role' => 'Supervisor'
                ];
            }
        } catch (Exception $e) {
            // Error with faculty lookup - treat as custom supervisor
            error_log("Error looking up faculty supervisor: " . $e->getMessage());
            $supervisor = [
                'name' => $supervisorName,
                'role' => 'Supervisor'
            ];
        }
    } else {
        // Custom supervisor (no ID provided)
        $supervisor = [
            'name' => $supervisorName,
            'role' => 'Supervisor'
        ];
        
        // If user is logged in, add their ID as fallback
        if ($userId) {
            $supervisor['userId'] = ['$oid' => $userId];
        }
    }
} else if ($userId) {
    // No supervisor specified, use logged-in user as supervisor
    $supervisor = [
        'userId' => ['$oid' => $userId],
        'name' => $username,
        'role' => 'Supervisor'
    ];
}

// Process stats (randomly generated if not provided)
$stats = [
    'views' => !empty($_POST['viewsCount']) ? (int)$_POST['viewsCount'] : rand(10, 1000),
    'downloads' => !empty($_POST['downloadsCount']) ? (int)$_POST['downloadsCount'] : rand(10, 1000),
    'favorites' => !empty($_POST['favoritesCount']) ? (int)$_POST['favoritesCount'] : rand(10, 1000)
];

// Process references - format as an array of objects
$referencesArray = [];
if (!empty($_POST['references'])) {
    $referencesText = $_POST['references'];
    $referencesLines = explode("\n", $referencesText);
    
    foreach ($referencesLines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Split by pipes if they exist (for title | link format)
        if (strpos($line, '|') !== false) {
            list($title, $link) = array_map('trim', explode('|', $line, 2));
            $referencesArray[] = [
                'title' => $title,
                'link' => $link
            ];
        } else {
            // If no pipe, use the entire line as both title and link
            $referencesArray[] = [
                'title' => $line,
                'link' => $line
            ];
        }
    }
}

// Handle additional media uploads using GridFS - format each media item as {type, gridfsId, caption}
$media = [];
if (!empty($_FILES['mediaFiles']['name'][0])) {
    $gridfsHandler = new GridFSUploadHandler($db);
    $rateLimiter = new RateLimiter($db);
    
    $fileCount = count($_FILES['mediaFiles']['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($_FILES['mediaFiles']['error'][$i] === 0) {
            $file = [
                'name' => $_FILES['mediaFiles']['name'][$i],
                'type' => $_FILES['mediaFiles']['type'][$i],
                'tmp_name' => $_FILES['mediaFiles']['tmp_name'][$i],
                'error' => $_FILES['mediaFiles']['error'][$i],
                'size' => $_FILES['mediaFiles']['size'][$i]
            ];
            
            // Check rate limit
            $rateCheck = $rateLimiter->checkUploadAllowed($_SESSION['userid'], $file['size']);
            if (!$rateCheck['allowed']) {
                throw new Exception($rateCheck['message']);
            }
            
            // Upload to GridFS
            $metadata = [
                'uploadedBy' => $_SESSION['userid'],
                'uploadType' => 'project_media'
            ];
            
            $uploadResult = $gridfsHandler->uploadToGridFS($file, $metadata);
            
            if ($uploadResult['success']) {
                $uploadedFile = $uploadResult['fileData'];
                $rateLimiter->logUpload($_SESSION['userid'], $uploadedFile['size'], $uploadedFile['name']);
                
                $mediaType = strpos($file['type'], 'image/') === 0 ? 'image' : 'video';
                
                $media[] = [
                    'type' => $mediaType,
                    'gridfsId' => $uploadedFile['gridfs_id'],
                    'caption' => $file['name'],
                    'storage' => 'gridfs'
                ];
            }
        }
    }
}

// Handle project files uploads using GridFS - format as {name, gridfsId, type, size, uploadedAt}
$files = [];
if (!empty($_FILES['projectFiles']['name'][0])) {
    $gridfsHandler = new GridFSUploadHandler($db);
    $rateLimiter = new RateLimiter($db);
    
    $fileCount = count($_FILES['projectFiles']['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($_FILES['projectFiles']['error'][$i] === 0) {
            $file = [
                'name' => $_FILES['projectFiles']['name'][$i],
                'type' => $_FILES['projectFiles']['type'][$i],
                'tmp_name' => $_FILES['projectFiles']['tmp_name'][$i],
                'error' => $_FILES['projectFiles']['error'][$i],
                'size' => $_FILES['projectFiles']['size'][$i]
            ];
            
            // Check rate limit
            $rateCheck = $rateLimiter->checkUploadAllowed($_SESSION['userid'], $file['size']);
            if (!$rateCheck['allowed']) {
                throw new Exception($rateCheck['message']);
            }
            
            // Upload to GridFS
            $metadata = [
                'uploadedBy' => $_SESSION['userid'],
                'uploadType' => 'project_file'
            ];
            
            $uploadResult = $gridfsHandler->uploadToGridFS($file, $metadata);
            
            if ($uploadResult['success']) {
                $uploadedFile = $uploadResult['fileData'];
                $rateLimiter->logUpload($_SESSION['userid'], $uploadedFile['size'], $uploadedFile['name']);
                
                $files[] = [
                    'name' => $uploadedFile['name'],
                    'gridfsId' => $uploadedFile['gridfs_id'],
                    'type' => $uploadedFile['type'],
                    'size' => $uploadedFile['size'],
                    'uploadedAt' => new UTCDateTime(),
                    'storage' => 'gridfs'
                ];
            }
        }
    }
}

// Process links data
$links = [
    'github' => $_POST['github'] ?? '',
    'website' => $_POST['website'] ?? '',
    'paper' => $_POST['paper'] ?? '',
    'doi' => $_POST['doi'] ?? '',
    'youtube' => $_POST['youtube'] ?? ''
];

// If links data was passed as JSON, use that instead
if (!empty($_POST['links'])) {
    $linksData = json_decode($_POST['links'], true);
    if (is_array($linksData)) {
        $links = $linksData;
    }
}

// Process estimated completion date
$estimatedCompletionDate = null;
if (isset($_POST['estimatedCompletionDate']) && !empty($_POST['estimatedCompletionDate'])) {
    $estimatedTime = strtotime($_POST['estimatedCompletionDate']);
    if ($estimatedTime) {
        $estimatedCompletionDate = new UTCDateTime($estimatedTime * 1000);
    }
}

// Create project document
$project = [
    'title' => $_POST['title'],
    'abstract' => $_POST['abstract'],
    'description' => $_POST['description'] ?? '',
    'coverImage' => $coverImage,
    'field' => $_POST['field'],
    'keywords' => $keywords,
    'institution' => $_POST['institution'] ?? 'United International University',
    'createdAt' => $createdDate,
    'updatedAt' => $updatedDate,
    'estimatedCompletionDate' => $estimatedCompletionDate,
    'privacy' => (int) ($_POST['privacy'] ?? 0),
    'members' => $members,
    'timeline' => $timeline,
    'supervisor' => $supervisor,
    'links' => $links,
    'files' => $files,
    'media' => $media,
    'references' => $referencesArray,
    'stats' => $stats,
    'comments' => [], // Initialize with empty array
    'createdBy' => $userId // Store the creator's user ID
];

try {
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    $collection = $db->projectsV2;
    
    // Insert the project
    $result = $collection->insertOne($project);
    
    if ($result->getInsertedCount() === 1) {
        $projectId = (string) $result->getInsertedId();
        
        // Automatically enable encryption for new projects (if configured)
        if (EncryptionConfig::isAutoEnableEnabled()) {
            try {
                $encryptionResult = ProjectKeyManager::initializeProjectEncryption($projectId, $db);
                if ($encryptionResult['success']) {
                    error_log("Encryption enabled for new project $projectId");
                    
                    // Auto-migrate existing messages if configured
                    if (EncryptionConfig::isAutoMigrationEnabled()) {
                        try {
                            $migratedCount = ChatEncryption::migrateMessagesToEncrypted($projectId, $db);
                            if ($migratedCount > 0) {
                                error_log("Auto-migrated $migratedCount messages to encrypted format for project $projectId");
                            }
                        } catch (Exception $e) {
                            error_log("Error auto-migrating messages for project $projectId: " . $e->getMessage());
                        }
                    }
                } else {
                    error_log("Failed to enable encryption for project $projectId: " . $encryptionResult['message']);
                }
            } catch (Exception $e) {
                error_log("Error enabling encryption for project $projectId: " . $e->getMessage());
            }
        }
        
        // Send welcome message to the project chat
        try {
            $projectTitle = $_POST['title'];
            $creatorName = $username;
            
            $welcomeResult = sendProjectWelcomeMessage($projectId, $projectTitle, $creatorName);
            if (!$welcomeResult['success']) {
                error_log("Failed to send welcome message for project $projectId: " . $welcomeResult['message']);
            }
        } catch (Exception $e) {
            error_log("Error sending welcome message for project $projectId: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Project created successfully',
            'projectId' => $projectId
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create project'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error creating project: ' . $e->getMessage()
    ]);
}
?> 