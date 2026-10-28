<?php
require_once __DIR__ . '/../model/admin_roles.php';
require_once __DIR__ . '/../includes/admin_middleware.php';
require_once __DIR__ . '/../model/db_connect.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

/**
 * Admin Controller
 * Handles all admin-related operations
 */
class AdminController {
    private $adminRoles;
    private $client;
    private $db;

    public function __construct() {
        $this->adminRoles = new AdminRoles();
        $this->client = connectToDatabase();
        $this->db = $this->client->uiurp;
    }

    /**
     * Handle admin role management requests
     */
    public function handleRoleManagement() {
        // Check if this is a direct call (for testing) or HTTP request
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $action = $_POST['action'] ?? '';
        $currentUserId = $_SESSION['user_id'] ?? '';

        switch ($action) {
            case 'grant_admin':
                $this->grantAdminRole();
                break;
            case 'revoke_admin':
                $this->revokeAdminRole();
                break;
            case 'get_admins':
                $this->getAllAdmins();
                break;
            case 'search_users':
                $this->searchUsers();
                break;
            case 'get_admin_history':
                $this->getAdminHistory();
                break;
            case 'get_all_users':
                $this->getAllUsers();
                break;
            case 'delete_user':
                $this->deleteUser();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }

    /**
     * Grant admin role to a user
     */
    private function grantAdminRole() {
        $userId = $_POST['userId'] ?? '';
        $role = $_POST['role'] ?? 'admin';
        $currentUserId = $_SESSION['user_id'] ?? '';

        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            return;
        }

        $result = $this->adminRoles->grantAdminRole($userId, $currentUserId, $role);
        echo json_encode($result);
    }

    /**
     * Revoke admin role from a user
     */
    private function revokeAdminRole() {
        $userId = $_POST['userId'] ?? '';
        $currentUserId = $_SESSION['user_id'] ?? '';

        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            return;
        }

        $result = $this->adminRoles->revokeAdminRole($userId, $currentUserId);
        echo json_encode($result);
    }

    /**
     * Get all admin users
     */
    private function getAllAdmins() {
        $result = $this->adminRoles->getAllAdmins();
        echo json_encode($result);
    }

    /**
     * Search users for admin assignment
     */
    private function searchUsers() {
        $searchTerm = $_POST['searchTerm'] ?? '';
        
        if (empty($searchTerm)) {
            echo json_encode(['success' => false, 'message' => 'Search term is required']);
            return;
        }

        $result = $this->adminRoles->searchUsersForAdmin($searchTerm);
        echo json_encode($result);
    }

    /**
     * Get admin history for a user
     */
    private function getAdminHistory() {
        $userId = $_POST['userId'] ?? '';
        
        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            return;
        }

        $result = $this->adminRoles->getAdminHistory($userId);
        echo json_encode($result);
    }

    /**
     * Get site statistics for admin dashboard
     */
    public function getSiteStatistics() {
        try {
            // Get total users
            $totalStudents = $this->db->students->countDocuments([]);
            $totalFaculty = $this->db->faculties->countDocuments([]);
            $totalUsers = $totalStudents + $totalFaculty;

            // Get total projects
            $totalProjects = $this->db->projectsV2->countDocuments([]);
            $publicProjects = $this->db->projectsV2->countDocuments(['privacy' => 0]);
            $privateProjects = $this->db->projectsV2->countDocuments(['privacy' => 1]);

            // Get recent projects (last 30 days)
            $thirtyDaysAgo = new UTCDateTime((time() - 30 * 24 * 60 * 60) * 1000);
            $recentProjects = $this->db->projectsV2->countDocuments([
                'createdAt' => ['$gte' => $thirtyDaysAgo]
            ]);

            // Get total events
            $totalEvents = $this->db->events->countDocuments([]);

            // Get total forum posts
            $totalPosts = $this->db->forum_posts->countDocuments([]);

            // Get active admin count
            $activeAdmins = $this->db->admin_roles->countDocuments(['isActive' => true]);

            $statistics = [
                'users' => [
                    'total' => $totalUsers,
                    'students' => $totalStudents,
                    'faculty' => $totalFaculty,
                    'admins' => $activeAdmins
                ],
                'projects' => [
                    'total' => $totalProjects,
                    'public' => $publicProjects,
                    'private' => $privateProjects,
                    'recent' => $recentProjects
                ],
                'content' => [
                    'events' => $totalEvents,
                    'forum_posts' => $totalPosts
                ]
            ];

            echo json_encode([
                'success' => true,
                'statistics' => $statistics
            ]);

        } catch (Exception $e) {
            error_log("Error getting site statistics: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching statistics: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get recent activity for admin dashboard
     */
    public function getRecentActivity() {
        try {
            $activities = [];

            // Get recent projects
            $recentProjects = $this->db->projectsV2->find(
                [],
                [
                    'sort' => ['createdAt' => -1],
                    'limit' => 5,
                    'projection' => [
                        'title' => 1,
                        'createdAt' => 1,
                        'createdBy' => 1,
                        'privacy' => 1
                    ]
                ]
            )->toArray();

            foreach ($recentProjects as $project) {
                $activities[] = [
                    'type' => 'project_created',
                    'title' => $project['title'],
                    'date' => $project['createdAt']->toDateTime()->format('Y-m-d H:i:s'),
                    'userId' => $project['createdBy'] ?? 'Unknown',
                    'privacy' => $project['privacy']
                ];
            }

            // Get recent events
            $recentEvents = $this->db->events->find(
                [],
                [
                    'sort' => ['createdAt' => -1],
                    'limit' => 3,
                    'projection' => [
                        'title' => 1,
                        'createdAt' => 1,
                        'createdBy' => 1
                    ]
                ]
            )->toArray();

            foreach ($recentEvents as $event) {
                $activities[] = [
                    'type' => 'event_created',
                    'title' => $event['title'],
                    'date' => $event['createdAt']->toDateTime()->format('Y-m-d H:i:s'),
                    'userId' => $event['createdBy'] ?? 'Unknown'
                ];
            }

            // Sort activities by date
            usort($activities, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            echo json_encode([
                'success' => true,
                'activities' => array_slice($activities, 0, 10)
            ]);

        } catch (Exception $e) {
            error_log("Error getting recent activity: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching recent activity: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get all users for management
     */
    public function getAllUsers() {
        try {
            $users = [];
            
            // Get all students
            $students = $this->db->students->find([], [
                'projection' => [
                    '_id' => 1,
                    'basic_info.name' => 1,
                    'contact_info.primary_email' => 1,
                    'academic_info.student_id' => 1,
                    'platform_settings.user_role' => 1
                ]
            ])->toArray();

            foreach ($students as $student) {
                $isAdmin = $this->adminRoles->isAdminByActualId((string)$student['_id']);
                $users[] = [
                    'id' => (string)$student['_id'],
                    'name' => $student['basic_info']['name'] ?? 'Unknown',
                    'email' => $student['contact_info']['primary_email'] ?? 'N/A',
                    'type' => 'student',
                    'identifier' => $student['academic_info']['student_id'] ?? 'N/A',
                    'role' => $isAdmin ? 'admin' : 'student'
                ];
            }

            // Get all faculty
            $faculty = $this->db->faculties->find([], [
                'projection' => [
                    '_id' => 1,
                    'name' => 1,
                    'email' => 1,
                    'department' => 1
                ]
            ])->toArray();

            foreach ($faculty as $facultyMember) {
                $isAdmin = $this->adminRoles->isAdminByActualId((string)$facultyMember['_id']);
                $users[] = [
                    'id' => (string)$facultyMember['_id'],
                    'name' => $facultyMember['name'] ?? 'Unknown',
                    'email' => $facultyMember['email'] ?? 'N/A',
                    'type' => 'faculty',
                    'identifier' => $facultyMember['department'] ?? 'N/A',
                    'role' => $isAdmin ? 'admin' : 'faculty'
                ];
            }

            // Sort users by name
            usort($users, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            echo json_encode([
                'success' => true,
                'users' => $users
            ]);

        } catch (Exception $e) {
            error_log("Error getting all users: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching users: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete a user account
     */
    public function deleteUser() {
        $userId = $_POST['userId'] ?? '';
        $userType = $_POST['userType'] ?? '';

        if (empty($userId) || empty($userType)) {
            echo json_encode(['success' => false, 'message' => 'User ID and type are required']);
            return;
        }

        try {
            $deleted = false;
            
            if ($userType === 'student') {
                $result = $this->db->students->deleteOne(['_id' => new ObjectId($userId)]);
                $deleted = $result->getDeletedCount() > 0;
            } elseif ($userType === 'faculty') {
                $result = $this->db->faculties->deleteOne(['_id' => new ObjectId($userId)]);
                $deleted = $result->getDeletedCount() > 0;
            }

            if ($deleted) {
                // Also delete from login_info
                $this->db->login_info->deleteOne(['id' => $userId]);
                // Remove admin role if exists
                $this->db->admin_roles->deleteOne(['userId' => $userId]);
                
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }

        } catch (Exception $e) {
            error_log("Error deleting user: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()]);
        }
    }

    /**
     * Manage content (projects, events, etc.)
     */
    public function manageContent() {
        // Check if this is a direct call (for testing) or HTTP request
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $action = $_POST['action'] ?? '';
        $contentType = $_POST['contentType'] ?? '';

        switch ($action) {
            case 'delete_project':
                $this->deleteProject();
                break;
            case 'toggle_project_privacy':
                $this->toggleProjectPrivacy();
                break;
            case 'delete_event':
                $this->deleteEvent();
                break;
            case 'get_content_list':
                $this->getContentList();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }

    /**
     * Delete a project
     */
    private function deleteProject() {
        $projectId = $_POST['projectId'] ?? '';

        if (empty($projectId)) {
            echo json_encode(['success' => false, 'message' => 'Project ID is required']);
            return;
        }

        try {
            $result = $this->db->projectsV2->deleteOne(['_id' => new ObjectId($projectId)]);
            
            if ($result->getDeletedCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Project deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Project not found']);
            }
        } catch (Exception $e) {
            error_log("Error deleting project: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error deleting project: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle project privacy
     */
    private function toggleProjectPrivacy() {
        $projectId = $_POST['projectId'] ?? '';

        if (empty($projectId)) {
            echo json_encode(['success' => false, 'message' => 'Project ID is required']);
            return;
        }

        try {
            $project = $this->db->projectsV2->findOne(['_id' => new ObjectId($projectId)]);
            
            if (!$project) {
                echo json_encode(['success' => false, 'message' => 'Project not found']);
                return;
            }

            $newPrivacy = $project['privacy'] == 0 ? 1 : 0;
            
            $result = $this->db->projectsV2->updateOne(
                ['_id' => new ObjectId($projectId)],
                [
                    '$set' => [
                        'privacy' => $newPrivacy,
                        'updatedAt' => new UTCDateTime()
                    ]
                ]
            );

            if ($result->getModifiedCount() > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Project privacy updated successfully',
                    'newPrivacy' => $newPrivacy
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update project privacy']);
            }
        } catch (Exception $e) {
            error_log("Error toggling project privacy: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error updating project privacy: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete an event
     */
    private function deleteEvent() {
        $eventId = $_POST['eventId'] ?? '';

        if (empty($eventId)) {
            echo json_encode(['success' => false, 'message' => 'Event ID is required']);
            return;
        }

        try {
            $result = $this->db->events->deleteOne(['_id' => new ObjectId($eventId)]);
            
            if ($result->getDeletedCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Event not found']);
            }
        } catch (Exception $e) {
            error_log("Error deleting event: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error deleting event: ' . $e->getMessage()]);
        }
    }

    /**
     * Get content list for management
     */
    private function getContentList() {
        $contentType = $_POST['contentType'] ?? 'projects';
        $page = (int)($_POST['page'] ?? 1);
        $limit = (int)($_POST['limit'] ?? 20);
        $search = $_POST['search'] ?? '';

        try {
            $collection = null;
            $filter = [];

            switch ($contentType) {
                case 'projects':
                    $collection = $this->db->projectsV2;
                    if (!empty($search)) {
                        $filter['$or'] = [
                            ['title' => ['$regex' => $search, '$options' => 'i']],
                            ['abstract' => ['$regex' => $search, '$options' => 'i']],
                            ['field' => ['$regex' => $search, '$options' => 'i']]
                        ];
                    }
                    break;
                case 'events':
                    $collection = $this->db->events;
                    if (!empty($search)) {
                        $filter['$or'] = [
                            ['title' => ['$regex' => $search, '$options' => 'i']],
                            ['description' => ['$regex' => $search, '$options' => 'i']]
                        ];
                    }
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid content type']);
                    return;
            }

            $skip = ($page - 1) * $limit;
            
            $items = $collection->find(
                $filter,
                [
                    'sort' => ['createdAt' => -1],
                    'skip' => $skip,
                    'limit' => $limit
                ]
            )->toArray();

            $total = $collection->countDocuments($filter);

            echo json_encode([
                'success' => true,
                'items' => $items,
                'pagination' => [
                    'currentPage' => $page,
                    'totalPages' => ceil($total / $limit),
                    'totalItems' => $total,
                    'itemsPerPage' => $limit
                ]
            ]);

        } catch (Exception $e) {
            error_log("Error getting content list: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error fetching content: ' . $e->getMessage()]);
        }
    }
}
?>
