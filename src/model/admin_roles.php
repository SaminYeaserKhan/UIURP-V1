<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/db_connect.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

/**
 * Admin Roles Management Model
 * Handles admin role assignment, checking, and management
 */
class AdminRoles {
    private $client;
    private $db;
    private $adminRolesCollection;
    private $loginInfoCollection;
    private $studentsCollection;
    private $facultiesCollection;

    public function __construct() {
        $this->client = connectToDatabase();
        $this->db = $this->client->uiurp;
        $this->adminRolesCollection = $this->db->admin_roles;
        $this->loginInfoCollection = $this->db->login_info;
        $this->studentsCollection = $this->db->students;
        $this->facultiesCollection = $this->db->faculties;
    }

    /**
     * Check if a user has admin privileges
     * @param string $userId
     * @return bool
     */
    public function isAdmin($userId) {
        try {
            $adminRole = $this->adminRolesCollection->findOne([
                'userId' => $userId,
                'isActive' => true
            ]);
            
            return $adminRole !== null;
        } catch (Exception $e) {
            error_log("Error checking admin status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check whether any active admin exists yet
     * Returns true when the database can't be checked, so callers fail closed.
     * @return bool
     */
    public function hasActiveAdmin() {
        try {
            return $this->adminRolesCollection->countDocuments(['isActive' => true]) > 0;
        } catch (Exception $e) {
            error_log("Error counting admins: " . $e->getMessage());
            return true;
        }
    }

    /**
     * Grant admin privileges to a user
     * @param string $userId
     * @param string $grantedBy
     * @param string $role (optional, defaults to 'admin')
     * @return array
     */
    public function grantAdminRole($userId, $grantedBy, $role = 'admin') {
        try {
            // Check if user already has admin role
            $existingRole = $this->adminRolesCollection->findOne([
                'userId' => $userId
            ]);

            if ($existingRole) {
                // Update existing role to active
                $result = $this->adminRolesCollection->updateOne(
                    ['userId' => $userId],
                    [
                        '$set' => [
                            'isActive' => true,
                            'role' => $role,
                            'grantedBy' => $grantedBy,
                            'grantedAt' => new UTCDateTime(),
                            'updatedAt' => new UTCDateTime()
                        ]
                    ]
                );
            } else {
                // Create new admin role
                $adminRole = [
                    'userId' => $userId,
                    'role' => $role,
                    'isActive' => true,
                    'grantedBy' => $grantedBy,
                    'grantedAt' => new UTCDateTime(),
                    'createdAt' => new UTCDateTime(),
                    'updatedAt' => new UTCDateTime()
                ];

                $result = $this->adminRolesCollection->insertOne($adminRole);
            }

            if ($existingRole) {
                // Update operation
                if ($result->getModifiedCount() > 0) {
                    return [
                        'success' => true,
                        'message' => 'Admin role granted successfully'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Failed to grant admin role'
                    ];
                }
            } else {
                // Insert operation
                if ($result->getInsertedCount() > 0) {
                    return [
                        'success' => true,
                        'message' => 'Admin role granted successfully'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Failed to grant admin role'
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Error granting admin role: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error granting admin role: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Revoke admin privileges from a user
     * @param string $userId
     * @param string $revokedBy
     * @return array
     */
    public function revokeAdminRole($userId, $revokedBy) {
        try {
            $result = $this->adminRolesCollection->updateOne(
                ['userId' => $userId],
                [
                    '$set' => [
                        'isActive' => false,
                        'revokedBy' => $revokedBy,
                        'revokedAt' => new UTCDateTime(),
                        'updatedAt' => new UTCDateTime()
                    ]
                ]
            );

            if ($result->getModifiedCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Admin role revoked successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Admin role not found or already revoked'
                ];
            }
        } catch (Exception $e) {
            error_log("Error revoking admin role: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error revoking admin role: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all admin users with their details
     * @return array
     */
    public function getAllAdmins() {
        try {
            $admins = $this->adminRolesCollection->find([
                'isActive' => true
            ])->toArray();

            $adminDetails = [];
            foreach ($admins as $admin) {
                $userDetails = $this->getUserDetails($admin['userId']);
                if ($userDetails) {
                    $adminDetails[] = [
                        'userId' => $admin['userId'],
                        'role' => $admin['role'],
                        'grantedAt' => $admin['grantedAt']->toDateTime()->format('Y-m-d H:i:s'),
                        'grantedBy' => $admin['grantedBy'],
                        'userDetails' => $userDetails
                    ];
                }
            }

            return [
                'success' => true,
                'admins' => $adminDetails
            ];
        } catch (Exception $e) {
            error_log("Error fetching admin users: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error fetching admin users: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if a user is admin by their actual user ID (student/faculty ID)
     * @param string $actualUserId
     * @return bool
     */
    public function isAdminByActualId($actualUserId) {
        try {
            $adminRole = $this->adminRolesCollection->findOne([
                'userId' => $actualUserId,
                'isActive' => true
            ]);
            
            return $adminRole !== null;
        } catch (Exception $e) {
            error_log("Error checking admin status by actual ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user details by userId
     * @param string $userId
     * @return array|null
     */
    private function getUserDetails($userId) {
        try {
            // First check login_info to get user type
            $loginInfo = $this->loginInfoCollection->findOne([
                '_id' => new ObjectId($userId)
            ]);

            if (!$loginInfo) {
                return null;
            }

            $userType = $loginInfo['type'];
            $userDetails = null;
            $actualUserId = $loginInfo['id']; // This is the ID of the actual student/faculty record

            if ($userType === 'student') {
                $student = $this->studentsCollection->findOne([
                    '_id' => new ObjectId($actualUserId)
                ]);
                if ($student) {
                    $userDetails = [
                        'name' => $student['basic_info']['name'] ?? 'Unknown',
                        'email' => $student['contact_info']['primary_email'] ?? 'N/A',
                        'type' => 'student',
                        'studentId' => $student['academic_info']['student_id'] ?? 'N/A'
                    ];
                }
            } elseif ($userType === 'faculty') {
                $faculty = $this->facultiesCollection->findOne([
                    '_id' => new ObjectId($actualUserId)
                ]);
                if ($faculty) {
                    $userDetails = [
                        'name' => $faculty['name'] ?? 'Unknown',
                        'email' => $faculty['email'] ?? 'N/A',
                        'type' => 'faculty',
                        'department' => $faculty['department'] ?? 'N/A'
                    ];
                }
            }

            return $userDetails;
        } catch (Exception $e) {
            error_log("Error fetching user details: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get admin role history for a user
     * @param string $userId
     * @return array
     */
    public function getAdminHistory($userId) {
        try {
            $history = $this->adminRolesCollection->find(
                ['userId' => $userId],
                ['sort' => ['createdAt' => -1]]
            )->toArray();

            $historyArray = [];
            foreach ($history as $record) {
                $historyArray[] = [
                    'role' => $record['role'],
                    'isActive' => $record['isActive'],
                    'grantedAt' => isset($record['grantedAt']) ? $record['grantedAt']->toDateTime()->format('Y-m-d H:i:s') : null,
                    'grantedBy' => $record['grantedBy'] ?? null,
                    'revokedAt' => isset($record['revokedAt']) ? $record['revokedAt']->toDateTime()->format('Y-m-d H:i:s') : null,
                    'revokedBy' => $record['revokedBy'] ?? null,
                    'createdAt' => $record['createdAt']->toDateTime()->format('Y-m-d H:i:s')
                ];
            }

            return [
                'success' => true,
                'history' => $historyArray
            ];
        } catch (Exception $e) {
            error_log("Error fetching admin history: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error fetching admin history: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Search users for admin role assignment
     * @param string $searchTerm
     * @return array
     */
    public function searchUsersForAdmin($searchTerm) {
        try {
            $users = [];
            
            // Search in students
            $studentCursor = $this->studentsCollection->find([
                '$or' => [
                    ['basic_info.name' => ['$regex' => $searchTerm, '$options' => 'i']],
                    ['contact_info.primary_email' => ['$regex' => $searchTerm, '$options' => 'i']],
                    ['academic_info.student_id' => ['$regex' => $searchTerm, '$options' => 'i']]
                ]
            ], [
                'projection' => [
                    '_id' => 1,
                    'basic_info.name' => 1,
                    'contact_info.primary_email' => 1,
                    'academic_info.student_id' => 1
                ]
            ]);

            foreach ($studentCursor as $student) {
                $users[] = [
                    'userId' => (string)$student['_id'],
                    'name' => $student['basic_info']['name'] ?? 'Unknown',
                    'email' => $student['contact_info']['primary_email'] ?? 'N/A',
                    'type' => 'student',
                    'identifier' => $student['academic_info']['student_id'] ?? 'N/A'
                ];
            }

            // Search in faculty
            $facultyCursor = $this->facultiesCollection->find([
                '$or' => [
                    ['name' => ['$regex' => $searchTerm, '$options' => 'i']],
                    ['email' => ['$regex' => $searchTerm, '$options' => 'i']],
                    ['department' => ['$regex' => $searchTerm, '$options' => 'i']]
                ]
            ], [
                'projection' => [
                    '_id' => 1,
                    'name' => 1,
                    'email' => 1,
                    'department' => 1
                ]
            ]);

            foreach ($facultyCursor as $faculty) {
                $users[] = [
                    'userId' => (string)$faculty['_id'],
                    'name' => $faculty['name'] ?? 'Unknown',
                    'email' => $faculty['email'] ?? 'N/A',
                    'type' => 'faculty',
                    'identifier' => $faculty['department'] ?? 'N/A'
                ];
            }

            return [
                'success' => true,
                'users' => $users
            ];
        } catch (Exception $e) {
            error_log("Error searching users: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error searching users: ' . $e->getMessage()
            ];
        }
    }
}
?>
