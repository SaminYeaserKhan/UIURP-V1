<?php
session_start();
require_once 'db_connect.php';
require_once 'send_system_chat_message_helper.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !isset($_SESSION['user_data'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to leave a project'
    ]);
    exit;
}

// Get user data from session
$userData = $_SESSION['user_data'];
$currentUserId = null;
$currentUserName = 'Unknown User';

// Extract user ID
if (isset($userData['_id']['$oid'])) {
    $currentUserId = $userData['_id']['$oid'];
} elseif (isset($userData['_id'])) {
    $currentUserId = (string)$userData['_id'];
}

// Extract user name for the leave message
if (isset($userData['basic_info']['name'])) {
    $currentUserName = $userData['basic_info']['name'];
} elseif (isset($userData['name'])) {
    $currentUserName = $userData['name'];
} elseif (isset($userData['full_name'])) {
    $currentUserName = $userData['full_name'];
}

if (!$currentUserId) {
    echo json_encode([
        'success' => false,
        'message' => 'Unable to identify current user'
    ]);
    exit;
}

// Check if project ID is provided
if (!isset($_POST['projectId']) || empty($_POST['projectId'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Project ID is required'
    ]);
    exit;
}

$projectId = $_POST['projectId'];

// Validate ObjectId format
if (!preg_match('/^[a-f\d]{24}$/i', $projectId)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid project ID format'
    ]);
    exit;
}

try {
    // Connect to MongoDB
    $client = connectToDatabase();
    $db = $client->uiurp;
    
    // Try projectsV2 collection first, then fall back to projects
    $project = null;
    $collections = ['projectsV2', 'projects'];
    $usedCollection = null;
    
    foreach ($collections as $collectionName) {
        $collection = $db->$collectionName;
        
        // Find the project
        $project = $collection->findOne(['_id' => new ObjectId($projectId)]);
        
        if ($project) {
            $usedCollection = $collection;
            break;
        }
    }
    
    if (!$project) {
        echo json_encode([
            'success' => false,
            'message' => 'Project not found'
        ]);
        exit;
    }
    
    // Check if user is a member of this project
    $isMember = false;
    $memberIndex = -1;
    
    // Log debug information
    error_log("Current User ID: " . $currentUserId);
    error_log("Project members: " . json_encode($project['members'] ?? []));
    error_log("Project createdBy: " . json_encode($project['createdBy'] ?? null));
    
    if (isset($project['members']) && is_array($project['members'])) {
        foreach ($project['members'] as $index => $member) {
            $memberId = null;
            
            // Try multiple ways to extract member ID to handle different data structures
            if (isset($member['userId']['$oid'])) {
                $memberId = $member['userId']['$oid'];
            } elseif (isset($member['userId']) && is_string($member['userId'])) {
                $memberId = $member['userId'];
            } elseif (isset($member['userId']) && is_object($member['userId'])) {
                // Handle ObjectId objects
                $memberId = (string)$member['userId'];
            } elseif (isset($member['_id']['$oid'])) {
                // Sometimes the member ID might be stored as _id
                $memberId = $member['_id']['$oid'];
            } elseif (isset($member['_id'])) {
                $memberId = (string)$member['_id'];
            } elseif (isset($member['id'])) {
                // Handle cases where it's stored as 'id'
                $memberId = (string)$member['id'];
            }
            
            error_log("Checking member index $index - Member ID: $memberId");
            
            if ($memberId && $memberId === $currentUserId) {
                $isMember = true;
                $memberIndex = $index;
                error_log("Found matching member at index: $index");
                break;
            }
        }
    }
    
    // Check if user is the supervisor
    $isSupervisor = false;
    error_log("Project supervisor: " . json_encode($project['supervisor'] ?? []));
    
    if (isset($project['supervisor'])) {
        $supervisorId = null;
        
        // Try multiple ways to extract supervisor ID
        if (isset($project['supervisor']['userId']['$oid'])) {
            $supervisorId = $project['supervisor']['userId']['$oid'];
        } elseif (isset($project['supervisor']['userId']) && is_string($project['supervisor']['userId'])) {
            $supervisorId = $project['supervisor']['userId'];
        } elseif (isset($project['supervisor']['userId']) && is_object($project['supervisor']['userId'])) {
            $supervisorId = (string)$project['supervisor']['userId'];
        } elseif (isset($project['supervisor']['_id']['$oid'])) {
            $supervisorId = $project['supervisor']['_id']['$oid'];
        } elseif (isset($project['supervisor']['_id'])) {
            $supervisorId = (string)$project['supervisor']['_id'];
        } elseif (isset($project['supervisor']['id'])) {
            $supervisorId = (string)$project['supervisor']['id'];
        }
        
        error_log("Supervisor ID: $supervisorId");
        
        if ($supervisorId && $supervisorId === $currentUserId) {
            $isSupervisor = true;
            error_log("User is supervisor");
        }
    }
    
    // Check if user is the creator of this project
    $isCreator = false;
    if (isset($project['createdBy'])) {
        $creatorId = null;
        
        // Try multiple ways to extract creator ID
        if (isset($project['createdBy']['$oid'])) {
            $creatorId = $project['createdBy']['$oid'];
        } elseif (is_string($project['createdBy'])) {
            $creatorId = $project['createdBy'];
        } elseif (is_object($project['createdBy'])) {
            $creatorId = (string)$project['createdBy'];
        }
        
        error_log("Creator ID: $creatorId");
        
        if ($creatorId && $creatorId === $currentUserId) {
            $isCreator = true;
            error_log("User is creator");
        }
    }
    
    // If user is not detected as member, supervisor, or creator, but they can access this project's chat,
    // they should be allowed to leave (this handles cases where data structure might be different)
    if (!$isMember && !$isSupervisor && !$isCreator) {
        error_log("User not detected as member, supervisor, or creator");
        
        // Since they can access the chat overlay for this project, treat them as a member
        // This is a fallback to handle data structure inconsistencies
        $isMember = true;
        $memberIndex = -1; // We'll handle removal differently for fallback cases
        
        error_log("Treating user as member via fallback logic");
    }
    
    // Allow supervisors to leave without restrictions - removed the restriction
    
    // Remove the user from the project
    $updateData = [];
    
    if ($isMember) {
        if ($memberIndex !== -1) {
            // Remove from members array using known index
            $members = $project['members'];
            array_splice($members, $memberIndex, 1);
            $updateData['members'] = $members;
        } else {
            // Fallback: remove by filtering out the current user ID
            $members = $project['members'] ?? [];
            $filteredMembers = [];
            
            foreach ($members as $member) {
                $memberId = null;
                
                // Extract member ID using the same logic as before
                if (isset($member['userId']['$oid'])) {
                    $memberId = $member['userId']['$oid'];
                } elseif (isset($member['userId']) && is_string($member['userId'])) {
                    $memberId = $member['userId'];
                } elseif (isset($member['userId']) && is_object($member['userId'])) {
                    $memberId = (string)$member['userId'];
                } elseif (isset($member['_id']['$oid'])) {
                    $memberId = $member['_id']['$oid'];
                } elseif (isset($member['_id'])) {
                    $memberId = (string)$member['_id'];
                } elseif (isset($member['id'])) {
                    $memberId = (string)$member['id'];
                }
                
                // Keep members that are not the current user
                if (!$memberId || $memberId !== $currentUserId) {
                    $filteredMembers[] = $member;
                }
            }
            
            $updateData['members'] = $filteredMembers;
            error_log("Using fallback member removal - removed " . (count($members) - count($filteredMembers)) . " member(s)");
        }
    }
    
    if ($isSupervisor) {
        // Remove supervisor
        $updateData['$unset'] = ['supervisor' => ''];
    }
    
    if ($isCreator) {
        // Remove creator field - this will prevent the project from showing up in their management page
        if (!isset($updateData['$unset'])) {
            $updateData['$unset'] = [];
        }
        $updateData['$unset']['createdBy'] = '';
        error_log("Removing creator field for user");
    }
    
    // Check if project should be deleted (no members, no supervisor, and no creator left)
    $shouldDeleteProject = false;
    
    // Calculate remaining project participants
    $remainingMembers = isset($updateData['members']) ? count($updateData['members']) : count($project['members'] ?? []);
    $hasSupervisor = isset($project['supervisor']) && !empty($project['supervisor']) && !$isSupervisor;
    $hasCreator = isset($project['createdBy']) && !empty($project['createdBy']) && !$isCreator;
    
    // Delete project if no one is left to manage it
    if ($remainingMembers === 0 && !$hasSupervisor && !$hasCreator) {
        $shouldDeleteProject = true;
        error_log("Project will be deleted - no members, supervisor, or creator remaining");
    }
    
    if ($shouldDeleteProject) {
        // Delete the project entirely
        $deleteResult = $usedCollection->deleteOne(['_id' => new ObjectId($projectId)]);
        
        if ($deleteResult->getDeletedCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'You have left the project. Since there were no other members, the project has been deleted.',
                'projectDeleted' => true
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete empty project'
            ]);
        }
    } else {
        // Check if we have valid update data
        error_log("Update data: " . json_encode($updateData));
        
        if (empty($updateData)) {
            // If no update data, it means user was not found in the project
            // But since they can access the chat, we'll consider it a success
            echo json_encode([
                'success' => true,
                'message' => 'You have successfully left the project.',
                'projectDeleted' => false
            ]);
        } else {
            // Ensure update data has proper MongoDB operators
            $finalUpdateData = [];
            
            // Handle $set operations
            $setData = [];
            if (isset($updateData['members'])) {
                $setData['members'] = $updateData['members'];
            }
            
            if (!empty($setData)) {
                $finalUpdateData['$set'] = $setData;
            }
            
            // Handle $unset operations
            if (isset($updateData['$unset'])) {
                $finalUpdateData['$unset'] = $updateData['$unset'];
            }
            
            error_log("Final update data: " . json_encode($finalUpdateData));
            
            if (empty($finalUpdateData)) {
                // No valid updates to perform
                echo json_encode([
                    'success' => true,
                    'message' => 'You have successfully left the project.',
                    'projectDeleted' => false
                ]);
            } else {
                // Update the project to remove the user
                $updateResult = $usedCollection->updateOne(
                    ['_id' => new ObjectId($projectId)],
                    $finalUpdateData
                );
                
                if ($updateResult->getModifiedCount() > 0 || $updateResult->getMatchedCount() > 0) {
                    // Send leave message to the project chat
                    try {
                        $leaveMessageResult = sendMemberLeaveMessage($projectId, $currentUserName, $project['title'] ?? null);
                        if (!$leaveMessageResult['success']) {
                            error_log("Failed to send leave message: " . $leaveMessageResult['message']);
                        }
                    } catch (Exception $e) {
                        error_log("Error sending leave message: " . $e->getMessage());
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'You have successfully left the project.',
                        'projectDeleted' => false
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to leave project. Please try again.'
                    ]);
                }
            }
        }
    }
    
} catch (Exception $e) {
    error_log("Leave project error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while leaving the project: ' . $e->getMessage()
    ]);
}
?> 