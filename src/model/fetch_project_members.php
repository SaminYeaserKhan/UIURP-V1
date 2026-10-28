<?php
require_once 'db_connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

// Start session to capture user data
session_start();

// Initialize response array
$response = [
    'success' => false,
    'members' => [],
    'supervisor' => null,
    'message' => ''
];

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user_data'])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit;
}

// Check if project ID is provided
if (!isset($_GET['projectId'])) {
    $response['message'] = 'Project ID is required';
    echo json_encode($response);
    exit;
}

// Set user ID
$userId = null;
if (isset($_SESSION['user_id'])) {
    // Convert MongoDB ObjectId to string if needed
    if (is_object($_SESSION['user_id']) && get_class($_SESSION['user_id']) === 'MongoDB\BSON\ObjectId') {
        $userId = (string)$_SESSION['user_id'];
    } else {
        $userId = $_SESSION['user_id'];
    }
} elseif (isset($_SESSION['user_data']) && isset($_SESSION['user_data']['_id']) && isset($_SESSION['user_data']['_id']['$oid'])) {
    $userId = $_SESSION['user_data']['_id']['$oid'];
} elseif (isset($_SESSION['user_data']) && isset($_SESSION['user_data']['_id'])) {
    $userId = (string)$_SESSION['user_data']['_id'];
}

// Get project ID
$projectId = $_GET['projectId'];

// Helper function to create profile link for users
function createProfileLink($name, $userId, $userType) {
    $profileUrl = '';
    $profileType = '';
    
    if ($userType === 'faculty') {
        $profileUrl = "Faculty_Profile.php?id=" . $userId;
        $profileType = 'faculty';
    } else {
        $profileUrl = "Student_Profile.php?id=" . $userId;
        $profileType = 'student';
    }
    
    return [
        'name' => $name,
        'profileUrl' => $profileUrl,
        'profileType' => $profileType
    ];
}

try {
    // Convert projectId to ObjectId if needed
    $projectObjectId = null;
    
    // Check if the projectId is already in ObjectId format (with $oid)
    if (is_array($projectId) && isset($projectId['$oid'])) {
        $projectObjectId = new ObjectId($projectId['$oid']);
    } else {
        $projectObjectId = new ObjectId($projectId);
    }
    
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    $projectsCollection = $db->projectsV2;
    $studentsCollection = $db->students;
    $facultiesCollection = $db->faculties;
    
    // Find the project by ID
    $project = $projectsCollection->findOne(['_id' => $projectObjectId]);
    
    if (!$project) {
        $response['message'] = 'Project not found';
        echo json_encode($response);
        exit;
    }
    
    // Initialize members array
    $members = [];
    
    // Process team members
    if (isset($project['members']) && is_array($project['members'])) {
        foreach ($project['members'] as $member) {
            $memberInfo = [
                'name' => $member['name'] ?? 'Unknown',
                'role' => $member['role'] ?? 'Team Member',
                'contribution' => $member['contribution'] ?? null,
                'userId' => null,
                'profileUrl' => null,
                'profileType' => 'student',
                'online' => false // Placeholder for online status
            ];
            
            // Extract member ID if available
            if (isset($member['userId'])) {
                $memberId = null;
                if (is_array($member['userId']) && isset($member['userId']['$oid'])) {
                    $memberId = $member['userId']['$oid'];
                } else {
                    $memberId = (string)$member['userId'];
                }
                
                $memberInfo['userId'] = $memberId;
                
                // Check if this is the current user
                if ($memberId === $userId) {
                    $memberInfo['online'] = true;
                    $memberInfo['isCurrentUser'] = true;
                }
                
                // Create profile link
                $profileInfo = createProfileLink($memberInfo['name'], $memberId, 'student');
                $memberInfo['profileUrl'] = $profileInfo['profileUrl'];
                $memberInfo['profileType'] = $profileInfo['profileType'];
                
                // Try to get additional info from students collection
                try {
                    $studentInfo = $studentsCollection->findOne(['_id' => new ObjectId($memberId)]);
                    if ($studentInfo) {
                        // Add profile picture if available
                        $profilePic = null;
                        
                        // Try multiple possible locations for profile image
                        $possibleImagePaths = [
                            $studentInfo['basic_info']['profile_image_url'] ?? null,
                            $studentInfo['profile_image'] ?? null,
                            $studentInfo['profile_image_url'] ?? null,
                            $studentInfo['basic_info']['profile_image'] ?? null
                        ];
                        
                        foreach ($possibleImagePaths as $imagePath) {
                            if (!empty($imagePath) && is_string($imagePath) && trim($imagePath) !== '') {
                                $profilePic = $imagePath;
                                break;
                            }
                        }
                        
                        if ($profilePic) {
                            $memberInfo['profilePic'] = $profilePic;
                        }
                        
                        // Add additional details if needed
                        if (isset($studentInfo['email'])) {
                            $memberInfo['email'] = $studentInfo['email'];
                        } elseif (isset($studentInfo['contact_info']['primary_email'])) {
                            $memberInfo['email'] = $studentInfo['contact_info']['primary_email'];
                        }
                        
                        // Get department if available
                        if (isset($studentInfo['academic_info']['department'])) {
                            $memberInfo['department'] = $studentInfo['academic_info']['department'];
                        } elseif (isset($studentInfo['department'])) {
                            $memberInfo['department'] = $studentInfo['department'];
                        }
                    }
                } catch (Exception $e) {
                    // Just continue if we can't find student info
                }
            }
            
            $members[] = $memberInfo;
        }
    }
    
    // Process supervisor information
    $supervisor = null;
    if (isset($project['supervisor'])) {
        $supervisorInfo = [
            'name' => isset($project['supervisor']['name']) ? $project['supervisor']['name'] : 
                     (is_string($project['supervisor']) ? $project['supervisor'] : 'Unknown'),
            'role' => 'Supervisor',
            'userId' => null,
            'profileUrl' => null,
            'profileType' => 'faculty',
            'online' => false // Placeholder for online status
        ];
        
        // Extract supervisor ID if available
        if (isset($project['supervisor']['userId'])) {
            $supervisorId = null;
            if (is_array($project['supervisor']['userId']) && isset($project['supervisor']['userId']['$oid'])) {
                $supervisorId = $project['supervisor']['userId']['$oid'];
            } else {
                $supervisorId = (string)$project['supervisor']['userId'];
            }
            
            $supervisorInfo['userId'] = $supervisorId;
            
            // Check if this is the current user
            if ($supervisorId === $userId) {
                $supervisorInfo['online'] = true;
                $supervisorInfo['isCurrentUser'] = true;
            }
            
            // Create profile link
            $profileInfo = createProfileLink($supervisorInfo['name'], $supervisorId, 'faculty');
            $supervisorInfo['profileUrl'] = $profileInfo['profileUrl'];
            $supervisorInfo['profileType'] = $profileInfo['profileType'];
            
            // Try to get additional info from faculties collection
            try {
                $facultyInfo = $facultiesCollection->findOne(['_id' => new ObjectId($supervisorId)]);
                if ($facultyInfo) {
                    // Add profile picture if available
                    if (isset($facultyInfo['profile_picture'])) {
                        $supervisorInfo['profilePic'] = $facultyInfo['profile_picture'];
                    } else if (isset($facultyInfo['profilePicture'])) {
                        $supervisorInfo['profilePic'] = $facultyInfo['profilePicture'];
                    } else if (isset($facultyInfo['profile_image'])) {
                        $supervisorInfo['profilePic'] = $facultyInfo['profile_image'];
                    }
                    
                    // Add department if available
                    if (isset($facultyInfo['department'])) {
                        $supervisorInfo['department'] = $facultyInfo['department'];
                    }
                    
                    // Add additional details if needed
                    if (isset($facultyInfo['email'])) {
                        $supervisorInfo['email'] = $facultyInfo['email'];
                    }
                }
            } catch (Exception $e) {
                // Just continue if we can't find faculty info
            }
        }
        
        $supervisor = $supervisorInfo;
    }
    
    // Transform results to match the response format
    $response['success'] = true;
    $response['members'] = $members;
    $response['supervisor'] = $supervisor;
    $response['projectName'] = $project['title'] ?? 'Unnamed Project';
    echo json_encode($response);
    
} catch (Exception $e) {
    $errorMsg = "Error fetching project members: " . $e->getMessage();
    $response['message'] = $errorMsg;
    echo json_encode($response);
}
?> 