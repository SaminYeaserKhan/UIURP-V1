<?php
require_once 'db_connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// We're importing classes directly without hints to avoid linter issues
use MongoDB\Client;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

// Start session to capture user data if available
session_start();

// Session logging removed

header('Content-Type: application/json');

// Remove session check
// if (!isset($_SESSION['user_id'])) {
//     echo json_encode(['success' => false, 'message' => 'User not logged in']);
//     exit;
// }

// Debug mode disabled
$DEBUG = false;

// Check if project ID is provided
if (!isset($_POST['project_id']) || empty($_POST['project_id'])) {
    echo json_encode(['success' => false, 'message' => 'Project ID is required']);
    exit;
}

$projectId = $_POST['project_id'];
// Get the user ID from session
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
// Default to a dummy ID if none found
if (!$userId) {
$userId = '000000000000000000000000';
}

// User ID logging removed

// Check if this is a timeline-only update
$isTimelineUpdate = isset($_POST['action']) && $_POST['action'] === 'update_timeline';

// Skip required field validation for timeline-only updates
if (!$isTimelineUpdate) {
    // Check required fields - with improved validation
    $requiredFields = ['title', 'field'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $missingFields[] = $field;
        }
    }
    
    // Special handling for privacy field - accepting '0', 0, '1', 1 as valid values
    // The strict check against '' is important since 0 == '' in PHP
    if (!isset($_POST['privacy']) || ($_POST['privacy'] === '' && $_POST['privacy'] !== '0' && $_POST['privacy'] !== 0)) {
        $missingFields[] = 'privacy';
        
        // Debug logging removed
    }
    
    if (!empty($missingFields)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missingFields)]);
        exit;
    }
}

try {
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    
    // Try to find the project in either collection
    $project = null;
    $projectCollection = null;
    $collections = ['projectsV2', 'projects'];
    
    foreach ($collections as $collectionName) {
        $collection = $db->$collectionName;
        
        // Check if project exists in this collection
        $projectCheck = $collection->findOne([
            '_id' => new \MongoDB\BSON\ObjectId($projectId)
        ]);
        
        if ($projectCheck) {
            $project = $projectCheck;
            $projectCollection = $collection;
            break;
        }
    }
    
    if (!$project || !$projectCollection) {
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        exit;
    }
    
    // Process timeline data if this is a timeline update
    if ($isTimelineUpdate) {
        // Process timeline data
        $timeline = [];
        if (isset($_POST['timeline']) && !empty($_POST['timeline'])) {
            $timelineData = json_decode($_POST['timeline'], true);
            if (is_array($timelineData)) {
                foreach ($timelineData as $item) {
                    if (isset($item['title']) && !empty($item['title']) && isset($item['date'])) {
                        $timeline[] = [
                            'title' => $item['title'],
                            'description' => isset($item['description']) ? $item['description'] : '',
                            'date' => $item['date'],
                            'status' => isset($item['status']) && !empty($item['status']) ? $item['status'] : 'Planned',
                            'assignedBy' => isset($item['assignedBy']) ? $item['assignedBy'] : '',
                            'assignedTo' => isset($item['assignedTo']) ? $item['assignedTo'] : ''
                        ];
                    }
                }
            }
        }
        
        // Update only the timeline field
        $updateResult = $projectCollection->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($projectId)],
            ['$set' => ['timeline' => $timeline, 'updatedAt' => new \MongoDB\BSON\UTCDateTime(time() * 1000)]]
        );
        
        if ($updateResult->getModifiedCount() > 0 || $updateResult->getMatchedCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Timeline updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update timeline']);
        }
        
        exit; // Stop here for timeline-only updates
    }
    
    // Parse privacy value correctly
    $privacyValue = 0; // Default to public
    if (isset($_POST['privacy'])) {
        // Handle different types of inputs for privacy
        if ($_POST['privacy'] === '0' || $_POST['privacy'] === 0) {
            $privacyValue = 0;
        } elseif ($_POST['privacy'] === '1' || $_POST['privacy'] === 1) {
            $privacyValue = 1;
        } else {
            // If it's neither 0 nor 1, default to 0 (public)
            $privacyValue = 0;
        }
    }
    
    // Process team members
    $members = [];
    if (isset($_POST['team_members']) && !empty($_POST['team_members'])) {
        $membersData = json_decode($_POST['team_members'], true);
        if (is_array($membersData)) {
            foreach ($membersData as $member) {
                if (isset($member['name']) && !empty($member['name'])) {
                    $members[] = [
                        'name' => $member['name'],
                        'role' => isset($member['role']) && !empty($member['role']) ? $member['role'] : 'Author',
                        'contribution' => isset($member['contribution']) ? (int)$member['contribution'] : 0,
                        'userId' => isset($member['userId']) ? $member['userId'] : null
                    ];
                }
            }
        }
    }
    
    // Process supervisor
    $supervisor = [];
    if (isset($_POST['supervisor']) && !empty($_POST['supervisor'])) {
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
        }
    } elseif (isset($project['supervisor'])) {
        // Keep existing supervisor if no new one provided
        $supervisor = $project['supervisor'];
    }
    
    // Process timeline data
    $timeline = [];
    if (isset($_POST['timeline']) && !empty($_POST['timeline'])) {
        $timelineData = json_decode($_POST['timeline'], true);
        if (is_array($timelineData)) {
            foreach ($timelineData as $item) {
                if (isset($item['title']) && !empty($item['title']) && isset($item['date'])) {
                    $timeline[] = [
                        'title' => $item['title'],
                        'description' => isset($item['description']) ? $item['description'] : '',
                        'date' => $item['date'],
                        'status' => isset($item['status']) && !empty($item['status']) ? $item['status'] : 'Planned',
                        'assignedBy' => isset($item['assignedBy']) ? $item['assignedBy'] : '',
                        'assignedTo' => isset($item['assignedTo']) ? $item['assignedTo'] : ''
                    ];
                }
            }
        }
    }
    
    // Process keywords
    $keywords = [];
    if (isset($_POST['keywords']) && !empty($_POST['keywords'])) {
        $keywordsData = json_decode($_POST['keywords'], true);
        if (is_array($keywordsData)) {
            $keywords = $keywordsData;
        }
    }
    
    // Process references
    $references = [];
    if (isset($_POST['references']) && !empty($_POST['references'])) {
        try {
            // Try to parse the references as JSON first (new format)
            $referencesData = json_decode($_POST['references'], true);
            
            if (is_array($referencesData)) {
                $references = $referencesData;
                
                // Log successful parsing for debugging
                if ($DEBUG) {
                    $logData = date('Y-m-d H:i:s') . ' - References parsed as JSON: ' . print_r($references, true) . "\n";
                    file_put_contents($logFile, $logData, FILE_APPEND);
                }
            } else {
                // Fall back to parsing as text if JSON decode fails
                $referencesText = $_POST['references'];
                $referencesLines = explode("\n", $referencesText);
                
                foreach ($referencesLines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    
                    // Split by pipes if they exist (for title | link format)
                    if (strpos($line, '|') !== false) {
                        list($title, $link) = array_map('trim', explode('|', $line, 2));
                        $references[] = [
                            'title' => $title,
                            'link' => $link
                        ];
                    } else {
                        // If no pipe, use the entire line as title and empty link
                        $references[] = [
                            'title' => $line,
                            'link' => ''
                        ];
                    }
                }
                
                // Log fallback to text parsing
                if ($DEBUG) {
                    $logData = date('Y-m-d H:i:s') . ' - References parsed as text: ' . print_r($references, true) . "\n";
                    file_put_contents($logFile, $logData, FILE_APPEND);
                }
            }
        } catch (Exception $e) {
            // Log the error and use existing references if available
            if ($DEBUG) {
                $logData = date('Y-m-d H:i:s') . ' - Error parsing references: ' . $e->getMessage() . "\n";
                file_put_contents($logFile, $logData, FILE_APPEND);
            }
            
            $references = isset($project['references']) ? $project['references'] : [];
        }
    } elseif (isset($project['references'])) {
        $references = $project['references'];
    }
    
    // Log the final references data being saved
    if ($DEBUG) {
        $logData = date('Y-m-d H:i:s') . ' - Final references data: ' . print_r($references, true) . "\n";
        file_put_contents($logFile, $logData, FILE_APPEND);
    }
    
    // Process stats
    $stats = isset($project['stats']) ? $project['stats'] : [];
    if (isset($_POST['views'])) {
        $stats['views'] = (int)$_POST['views'];
    }
    if (isset($_POST['downloads'])) {
        $stats['downloads'] = (int)$_POST['downloads'];
    }
    if (isset($_POST['favorites'])) {
        $stats['favorites'] = (int)$_POST['favorites'];
    }
    
    // Process comments
    $comments = [];
    if (isset($_POST['comments']) && !empty($_POST['comments'])) {
        $commentsData = json_decode($_POST['comments'], true);
        if (is_array($commentsData)) {
            $comments = $commentsData;
        }
    } elseif (isset($project['comments'])) {
        $comments = $project['comments'];
    }
    
    // Handle cover image
    $coverImage = [];
    if (isset($project['coverImage'])) {
        $coverImage = $project['coverImage'];
    }
    
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/projects/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = basename($_FILES['cover_image']['name']);
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = uniqid('project_') . '.' . $fileExt;
        $uploadPath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadPath)) {
            // Delete old image if exists
            if (isset($coverImage['path']) && file_exists($coverImage['path'])) {
                unlink($coverImage['path']);
            }
            
            $coverImage = [
                'url' => 'uploads/projects/' . $newFileName,
                'path' => $uploadPath,
                'name' => $fileName
            ];
        } else {
            // Failed to move uploaded file
            echo json_encode(['success' => false, 'message' => 'Failed to upload cover image']);
            exit;
        }
    }
    
    // Handle project files uploads
    $files = isset($project['files']) ? $project['files'] : [];
    if (isset($_FILES['project_files']) && !empty($_FILES['project_files']['name'][0])) {
        $uploadDir = __DIR__ . '/../../storage/files/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileCount = count($_FILES['project_files']['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['project_files']['error'][$i] === 0) {
                $filename = $_FILES['project_files']['name'][$i];
                $fileType = $_FILES['project_files']['type'][$i];
                $fileSize = $_FILES['project_files']['size'][$i];
                $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
                $newFilename = 'file_' . time() . '_' . uniqid() . '.' . $fileExtension;
                $targetFile = $uploadDir . $newFilename;
                
                if (move_uploaded_file($_FILES['project_files']['tmp_name'][$i], $targetFile)) {
                    $files[] = [
                        'name' => $filename,
                        'path' => '/storage/files/' . $newFilename,
                        'type' => $fileType,
                        'size' => $fileSize,
                        'uploadedAt' => new \MongoDB\BSON\UTCDateTime(time() * 1000)
                    ];
                }
            }
        }
    }
    
    // Handle media file uploads
    $media = isset($project['media']) ? $project['media'] : [];
    if (isset($_FILES['project_media']) && !empty($_FILES['project_media']['name'][0])) {
        $uploadDir = __DIR__ . '/../../storage/media/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileCount = count($_FILES['project_media']['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['project_media']['error'][$i] === 0) {
                $filename = $_FILES['project_media']['name'][$i];
                $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
                $newFilename = 'media_' . time() . '_' . uniqid() . '.' . $fileExtension;
                $targetFile = $uploadDir . $newFilename;
                
                if (move_uploaded_file($_FILES['project_media']['tmp_name'][$i], $targetFile)) {
                    $mediaType = strpos($_FILES['project_media']['type'][$i], 'image/') === 0 ? 'image' : 'video';
                    
                    $media[] = [
                        'type' => $mediaType,
                        'url' => '/storage/media/' . $newFilename,
                        'caption' => $filename
                    ];
                }
            }
        }
    }
    
    // Get date values
    $createdAt = isset($project['createdAt']) ? $project['createdAt'] : new \MongoDB\BSON\UTCDateTime(time() * 1000);
    if (isset($_POST['created_date']) && !empty($_POST['created_date'])) {
        $createdTime = strtotime($_POST['created_date']);
        if ($createdTime) {
            $createdAt = new \MongoDB\BSON\UTCDateTime($createdTime * 1000);
        }
    }
    
    // Preserve the original creator ID
    // This is important for filtering projects by creator on the project_management.php page
    $createdBy = isset($project['createdBy']) ? $project['createdBy'] : null;
    
        $updatedAt = new \MongoDB\BSON\UTCDateTime(time() * 1000);
    if (isset($_POST['updated_date']) && !empty($_POST['updated_date'])) {
        $updatedTime = strtotime($_POST['updated_date']);
        if ($updatedTime) {
            $updatedAt = new \MongoDB\BSON\UTCDateTime($updatedTime * 1000);
        }
    }

    // Process estimated completion date
    $estimatedCompletionDate = isset($project['estimatedCompletionDate']) ? $project['estimatedCompletionDate'] : null;
    if (isset($_POST['estimatedCompletionDate']) && !empty($_POST['estimatedCompletionDate'])) {
        $estimatedTime = strtotime($_POST['estimatedCompletionDate']);
        if ($estimatedTime) {
            $estimatedCompletionDate = new \MongoDB\BSON\UTCDateTime($estimatedTime * 1000);
        }
    }

    // Update project - use fully-qualified class names to avoid linter issues
    $updateResult = $projectCollection->updateOne(
        ['_id' => new \MongoDB\BSON\ObjectId($projectId)],
        [
            '$set' => [
                'title' => $_POST['title'],
                'abstract' => isset($_POST['abstract']) ? $_POST['abstract'] : '',
                'description' => isset($_POST['description']) ? $_POST['description'] : '',
                'field' => $_POST['field'],
                'institution' => isset($_POST['institution']) ? $_POST['institution'] : 'United International University',
                'privacy' => $privacyValue,
                'members' => $members,
                'supervisor' => $supervisor,
                'timeline' => $timeline,
                'keywords' => $keywords,
                'references' => $references,
                'stats' => $stats,
                'comments' => $comments,
                'links' => [
                    'github' => isset($_POST['github_url']) ? $_POST['github_url'] : '',
                    'website' => isset($_POST['project_url']) ? $_POST['project_url'] : '',
                    'paper' => isset($_POST['paper_url']) ? $_POST['paper_url'] : '',
                    'doi' => isset($_POST['doi']) ? $_POST['doi'] : '',
                    'youtube' => isset($_POST['youtube_url']) ? $_POST['youtube_url'] : ''
                ],
                'coverImage' => $coverImage,
                'files' => $files,
                'media' => $media,
                'createdAt' => $createdAt,
                'updatedAt' => $updatedAt,
                'estimatedCompletionDate' => $estimatedCompletionDate,
                'createdBy' => $createdBy
            ]
        ]
    );
    
    if ($updateResult->getModifiedCount() > 0 || $updateResult->getMatchedCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Project updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update project']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?> 