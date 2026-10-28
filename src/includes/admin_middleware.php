<?php
require_once __DIR__ . '/../model/admin_roles.php';

/**
 * Admin Middleware
 * Checks if the current user has admin privileges
 */
class AdminMiddleware {
    private $adminRoles;

    public function __construct() {
        $this->adminRoles = new AdminRoles();
    }

    /**
     * Check if current user is admin
     * @return bool
     */
    public function isAdmin() {
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            return false;
        }

        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        return $this->adminRoles->isAdmin($_SESSION['user_id']);
    }

    /**
     * Require admin access - redirect if not admin
     * @param string $redirectUrl (optional)
     */
    public function requireAdmin($redirectUrl = null) {
        if (!$this->isAdmin()) {
            if ($redirectUrl === null) {
                $redirectUrl = '/index.php';
            }
            
            $_SESSION['error'] = 'Access denied. Admin privileges required.';
            header("Location: " . $redirectUrl);
            exit();
        }
    }

    /**
     * Get admin status for JSON responses
     * @return array
     */
    public function getAdminStatus() {
        return [
            'isAdmin' => $this->isAdmin(),
            'userId' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null
        ];
    }
}

// Global function for easy access
function requireAdmin($redirectUrl = null) {
    $middleware = new AdminMiddleware();
    $middleware->requireAdmin($redirectUrl);
}

function isAdmin() {
    $middleware = new AdminMiddleware();
    return $middleware->isAdmin();
}
?>
