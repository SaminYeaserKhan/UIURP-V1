<?php
/**
 * Initialize Admin User Script
 * This script helps set up the first admin user for the system
 */

require_once __DIR__ . '/src/model/admin_roles.php';
require_once __DIR__ . '/src/model/db_connect.php';
require_once __DIR__ . '/src/includes/admin_middleware.php';

use MongoDB\BSON\ObjectId;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Anyone may use this page only until the first admin exists (first-time setup).
// After that, only signed-in admins can open it or grant admin roles.
if ((new AdminRoles())->hasActiveAdmin()) {
    requireAdmin();
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['userId'] ?? '';
    $adminRole = new AdminRoles();
    
    if (empty($userId)) {
        $response = [
            'success' => false,
            'message' => 'User ID is required'
        ];
    } else {
        // Grant admin role to the specified user
        $result = $adminRole->grantAdminRole($userId, 'system', 'super_admin');
        $response = $result;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Get all users for selection
$client = connectToDatabase();
$db = $client->uiurp;

// Get students
$students = $db->students->find([], [
    'projection' => [
        '_id' => 1,
        'basic_info.name' => 1,
        'contact_info.primary_email' => 1,
        'academic_info.student_id' => 1
    ]
])->toArray();

// Get faculty
$faculty = $db->faculties->find([], [
    'projection' => [
        '_id' => 1,
        'name' => 1,
        'email' => 1,
        'department' => 1
    ]
])->toArray();

// Combine users
$allUsers = [];

foreach ($students as $student) {
    $allUsers[] = [
        'id' => (string)$student['_id'],
        'name' => $student['basic_info']['name'] ?? 'Unknown',
        'email' => $student['contact_info']['primary_email'] ?? 'N/A',
        'type' => 'student',
        'identifier' => $student['academic_info']['student_id'] ?? 'N/A'
    ];
}

foreach ($faculty as $facultyMember) {
    $allUsers[] = [
        'id' => (string)$facultyMember['_id'],
        'name' => $facultyMember['name'] ?? 'Unknown',
        'email' => $facultyMember['email'] ?? 'N/A',
        'type' => 'faculty',
        'identifier' => $facultyMember['department'] ?? 'N/A'
    ];
}

// Sort by name
usort($allUsers, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialize Admin User - UIURP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .init-container {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            padding: 2rem;
            max-width: 600px;
            width: 100%;
            margin: 1rem;
        }
        
        .init-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .init-header h1 {
            color: #1e293b;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .init-header p {
            color: #64748b;
            margin: 0;
        }
        
        .user-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .user-item:hover {
            border-color: #3b82f6;
            background: #f8fafc;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .user-item.selected {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-weight: 500;
            margin: 0 0 0.25rem 0;
            color: #1e293b;
        }
        
        .user-email {
            font-size: 0.875rem;
            color: #64748b;
            margin: 0;
        }
        
        .user-type {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .user-type.student {
            background: #dcfce7;
            color: #166534;
        }
        
        .user-type.faculty {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .alert {
            border-radius: 0.5rem;
            border: none;
        }
        
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
    </style>
</head>
<body>
    <div class="init-container">
        <div class="init-header">
            <h1><i class="bi bi-shield-check text-primary"></i> Initialize Admin User</h1>
            <p>Select a user to grant admin privileges. This will be the first admin user in the system.</p>
        </div>
        
        <div id="alertContainer"></div>
        
        <form id="initAdminForm">
            <div class="mb-4">
                <label class="form-label fw-semibold">Select User to Make Admin:</label>
                <div id="usersList">
                    <?php foreach ($allUsers as $user): ?>
                    <div class="user-item" data-user-id="<?= htmlspecialchars($user['id']) ?>">
                        <div class="user-avatar">
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        </div>
                        <div class="user-info">
                            <h6 class="user-name"><?= htmlspecialchars($user['name']) ?></h6>
                            <p class="user-email"><?= htmlspecialchars($user['email']) ?></p>
                            <span class="user-type <?= $user['type'] ?>"><?= $user['type'] ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-primary" id="initAdminBtn" disabled>
                    <i class="bi bi-shield-check"></i> Grant Admin Privileges
                </button>
            </div>
        </form>
        
        <div class="text-center mt-4">
            <small class="text-muted">
                <i class="bi bi-info-circle"></i>
                After initialization, you can manage admin users from the admin dashboard.
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedUserId = null;
        
        // Handle user selection
        document.querySelectorAll('.user-item').forEach(item => {
            item.addEventListener('click', function() {
                // Remove previous selection
                document.querySelectorAll('.user-item').forEach(i => i.classList.remove('selected'));
                
                // Add selection to clicked item
                this.classList.add('selected');
                
                // Store selected user ID
                selectedUserId = this.getAttribute('data-user-id');
                
                // Enable submit button
                document.getElementById('initAdminBtn').disabled = false;
            });
        });
        
        // Handle form submission
        document.getElementById('initAdminForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!selectedUserId) {
                showAlert('Please select a user first.', 'warning');
                return;
            }
            
            const submitBtn = document.getElementById('initAdminBtn');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Granting Admin Privileges...';
            
            try {
                const response = await fetch('init_admin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `userId=${selectedUserId}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('Admin privileges granted successfully! You can now access the admin dashboard.', 'success');
                    submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Admin Privileges Granted';
                    
                    // Redirect to admin dashboard after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'admin_dashboard.php';
                    }, 2000);
                } else {
                    showAlert('Error: ' + data.message, 'danger');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
        
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            const alertId = 'alert-' + Date.now();
            
            const alertHtml = `
                <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'}"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            alertContainer.innerHTML = alertHtml;
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = document.getElementById(alertId);
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }
    </script>
</body>
</html>
