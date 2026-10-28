<?php
session_start();
require_once __DIR__ . '/../includes/admin_middleware.php';
require_once __DIR__ . '/admin_controller.php';

// Check if user is admin
requireAdmin();

// Set content type
header('Content-Type: application/json');

// Get the action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Initialize admin controller
$adminController = new AdminController();

// Route the request
switch ($action) {
    case 'get_statistics':
        $adminController->getSiteStatistics();
        break;
    case 'get_recent_activity':
        $adminController->getRecentActivity();
        break;
    case 'get_content_list':
        $adminController->manageContent();
        break;
    case 'get_all_users':
    case 'grant_admin':
    case 'revoke_admin':
    case 'get_admins':
    case 'search_users':
    case 'get_admin_history':
        $adminController->handleRoleManagement();
        break;
    case 'delete_project':
    case 'toggle_project_privacy':
    case 'delete_event':
        $adminController->manageContent();
        break;
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        break;
}
?>
