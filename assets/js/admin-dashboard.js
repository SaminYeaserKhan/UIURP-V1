/**
 * Admin Dashboard JavaScript
 * Handles all admin dashboard functionality
 */

class AdminDashboard {
    constructor() {
        this.currentTab = 'overview';
        this.selectedUserId = null;
        this.currentPage = 1;
        this.itemsPerPage = 20;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadStatistics();
        this.loadRecentActivity();
        this.loadAdmins();
    }

    bindEvents() {
        // Tab switching
        document.querySelectorAll('[data-bs-toggle="pill"]').forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.currentTab = e.target.getAttribute('data-bs-target').replace('#', '');
                this.handleTabSwitch(this.currentTab);
            });
        });

        // Search functionality
        const userSearch = document.getElementById('userSearch');
        if (userSearch) {
            userSearch.addEventListener('input', this.debounce((e) => {
                this.searchUsers(e.target.value);
            }, 300));
        }

        const contentSearch = document.getElementById('contentSearch');
        if (contentSearch) {
            contentSearch.addEventListener('input', this.debounce((e) => {
                this.searchContent(e.target.value);
            }, 300));
        }

        const userSearchInput = document.getElementById('userSearchInput');
        if (userSearchInput) {
            userSearchInput.addEventListener('input', this.debounce((e) => {
                this.searchUsersForAdmin(e.target.value);
            }, 300));
        }

        // Content type change
        const contentTypeSelect = document.getElementById('contentTypeSelect');
        if (contentTypeSelect) {
            contentTypeSelect.addEventListener('change', (e) => {
                this.loadContent(e.target.value);
            });
        }

        // Modal events
        this.bindModalEvents();
    }

    bindModalEvents() {
        // Add admin modal
        const addAdminModal = document.getElementById('addAdminModal');
        if (addAdminModal) {
            addAdminModal.addEventListener('hidden.bs.modal', () => {
                this.clearAddAdminForm();
            });
        }

        // Confirmation modal
        const confirmModal = document.getElementById('confirmModal');
        if (confirmModal) {
            confirmModal.addEventListener('hidden.bs.modal', () => {
                this.clearConfirmationModal();
            });
        }
    }

    handleTabSwitch(tab) {
        switch (tab) {
            case 'overview':
                this.loadStatistics();
                this.loadRecentActivity();
                break;
            case 'users':
                this.loadUsers();
                break;
            case 'content':
                this.loadContent('projects');
                break;
            case 'settings':
                // Settings tab doesn't need dynamic loading
                break;
        }
    }

    // Statistics and Overview
    async loadStatistics() {
        try {
            const response = await fetch('src/controller/admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_statistics'
            });

            const data = await response.json();
            
            if (data.success) {
                this.updateStatistics(data.statistics);
            } else {
                this.showToast('Error loading statistics: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error loading statistics:', error);
            this.showToast('Error loading statistics', 'error');
        }
    }

    updateStatistics(stats) {
        // Update header stats
        document.getElementById('totalUsers').textContent = stats.users.total;
        document.getElementById('totalProjects').textContent = stats.projects.total;
        document.getElementById('totalAdmins').textContent = stats.users.admins;

        // Update overview cards
        document.getElementById('statTotalUsers').textContent = stats.users.total;
        document.getElementById('statUserBreakdown').textContent = 
            `${stats.users.students} students, ${stats.users.faculty} faculty`;
        
        document.getElementById('statTotalProjects').textContent = stats.projects.total;
        document.getElementById('statProjectBreakdown').textContent = 
            `${stats.projects.public} public, ${stats.projects.private} private`;
        
        document.getElementById('statTotalEvents').textContent = stats.content.events;
        document.getElementById('statTotalPosts').textContent = stats.content.forum_posts;
    }

    async loadRecentActivity() {
        try {
            const response = await fetch('src/controller/admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_recent_activity'
            });

            const data = await response.json();
            
            if (data.success) {
                this.displayRecentActivity(data.activities);
            } else {
                this.showToast('Error loading recent activity: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error loading recent activity:', error);
            this.showToast('Error loading recent activity', 'error');
        }
    }

    displayRecentActivity(activities) {
        const container = document.getElementById('recentActivityList');
        
        if (activities.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-4">No recent activity</p>';
            return;
        }

        const html = activities.map(activity => `
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="bi bi-${this.getActivityIcon(activity.type)}"></i>
                </div>
                <div class="activity-content">
                    <h6 class="activity-title">${this.escapeHtml(activity.title)}</h6>
                    <p class="activity-meta">
                        ${this.formatDate(activity.date)} • 
                        User: ${this.escapeHtml(activity.userId)}
                        ${activity.privacy !== undefined ? ` • ${activity.privacy === 0 ? 'Public' : 'Private'}` : ''}
                    </p>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    getActivityIcon(type) {
        const icons = {
            'project_created': 'folder-plus',
            'event_created': 'calendar-plus',
            'user_created': 'person-plus',
            'admin_granted': 'shield-check'
        };
        return icons[type] || 'circle';
    }

    // User Management
    async loadUsers() {
        const container = document.getElementById('usersList');
        container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

        try {
            const response = await fetch('src/controller/admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_all_users'
            });

            const data = await response.json();
            
            if (data.success) {
                this.displayUsers(data.users);
            } else {
                this.showToast('Error loading users: ' + data.message, 'error');
                container.innerHTML = '<p class="text-muted text-center py-4">Error loading users</p>';
            }
        } catch (error) {
            console.error('Error loading users:', error);
            this.showToast('Error loading users', 'error');
            container.innerHTML = '<p class="text-muted text-center py-4">Error loading users</p>';
        }
    }

    async searchUsers(searchTerm) {
        if (!searchTerm.trim()) {
            this.loadUsers();
            return;
        }

        try {
            const response = await fetch('src/controller/admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=search_users&searchTerm=${encodeURIComponent(searchTerm)}`
            });

            const data = await response.json();
            
            if (data.success) {
                this.displayUsers(data.users);
            } else {
                this.showToast('Error searching users: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error searching users:', error);
            this.showToast('Error searching users', 'error');
        }
    }

    displayUsers(users) {
        const container = document.getElementById('usersList');
        
        if (users.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-4">No users found</p>';
            return;
        }

        // Update user counts
        const totalUsers = users.length;
        const adminUsers = users.filter(user => user.role === 'admin').length;
        
        document.getElementById('totalUsersCount').textContent = totalUsers;
        document.getElementById('adminUsersCount').textContent = adminUsers;

        const html = users.map(user => `
            <div class="user-item">
                <div class="user-avatar">
                    ${user.name.charAt(0).toUpperCase()}
                </div>
                <div class="user-info">
                    <h6 class="user-name">${this.escapeHtml(user.name)}</h6>
                    <p class="user-email">${this.escapeHtml(user.email)}</p>
                    <div class="user-meta">
                        <span class="user-type ${user.type}">${user.type}</span>
                        <span class="user-identifier">${this.escapeHtml(user.identifier)}</span>
                        ${user.role === 'admin' ? '<span class="admin-badge">ADMIN</span>' : ''}
                    </div>
                </div>
                <div class="user-actions">
                    ${user.role === 'admin' ? 
                        `<button class="btn btn-sm btn-outline-warning me-2" onclick="adminDashboard.toggleAdmin('${user.id}', '${user.name}', false)">
                            <i class="bi bi-shield-x"></i> Remove Admin
                        </button>` :
                        `<button class="btn btn-sm btn-outline-primary me-2" onclick="adminDashboard.toggleAdmin('${user.id}', '${user.name}', true)">
                            <i class="bi bi-shield-plus"></i> Make Admin
                        </button>`
                    }
                    <button class="btn btn-sm btn-outline-danger" onclick="adminDashboard.deleteUser('${user.id}', '${user.type}', '${user.name}')">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    // Content Management
    async loadContent(contentType = 'projects') {
        const container = document.getElementById('contentList');
        container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

        try {
            const response = await fetch('src/controller/admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_content_list&contentType=${contentType}&page=${this.currentPage}&limit=${this.itemsPerPage}`
            });

            const data = await response.json();
            
            if (data.success) {
                this.displayContent(data.items, contentType);
            } else {
                this.showToast('Error loading content: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error loading content:', error);
            this.showToast('Error loading content', 'error');
        }
    }

    displayContent(items, contentType) {
        const container = document.getElementById('contentList');
        
        if (items.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-4">No content found</p>';
            return;
        }

        const html = items.map(item => `
            <div class="content-item">
                <div class="content-icon">
                    <i class="bi bi-${contentType === 'projects' ? 'folder' : 'calendar-event'}"></i>
                </div>
                <div class="content-info">
                    <h6 class="content-title">${this.escapeHtml(item.title)}</h6>
                    <p class="content-meta">
                        Created: ${this.formatDate(item.createdAt)} • 
                        ${item.privacy !== undefined ? `Status: ${item.privacy === 0 ? 'Public' : 'Private'}` : ''}
                    </p>
                </div>
                <div class="content-actions">
                    ${contentType === 'projects' ? `
                        <button class="btn btn-sm btn-outline-warning me-2" onclick="adminDashboard.togglePrivacy('${item._id}')">
                            <i class="bi bi-eye${item.privacy === 0 ? '-slash' : ''}"></i>
                        </button>
                    ` : ''}
                    <button class="btn btn-sm btn-outline-danger" onclick="adminDashboard.deleteContent('${item._id}', '${contentType}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    async searchContent(searchTerm) {
        const contentType = document.getElementById('contentTypeSelect').value;
        
        try {
            const response = await fetch('src/controller/admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_content_list&contentType=${contentType}&page=1&limit=${this.itemsPerPage}&search=${encodeURIComponent(searchTerm)}`
            });

            const data = await response.json();
            
            if (data.success) {
                this.displayContent(data.items, contentType);
            } else {
                this.showToast('Error searching content: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error searching content:', error);
            this.showToast('Error searching content', 'error');
        }
    }

    // Admin Role Management
    async loadAdmins() {
        const container = document.getElementById('adminsList');
        container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

        try {
            const response = await fetch('src/controller/admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_admins'
            });

            const data = await response.json();
            
            if (data.success) {
                this.displayAdmins(data.admins);
            } else {
                this.showToast('Error loading admins: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error loading admins:', error);
            this.showToast('Error loading admins', 'error');
        }
    }

    displayAdmins(admins) {
        const container = document.getElementById('adminsList');
        
        if (admins.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-4">No admins found</p>';
            return;
        }

        const html = admins.map(admin => `
            <div class="admin-item">
                <div class="user-avatar">
                    ${admin.userDetails.name.charAt(0).toUpperCase()}
                </div>
                <div class="user-info">
                    <h6 class="user-name">${this.escapeHtml(admin.userDetails.name)}</h6>
                    <p class="user-email">${this.escapeHtml(admin.userDetails.email)}</p>
                    <span class="admin-role">${admin.role}</span>
                </div>
                <div class="user-actions">
                    <button class="btn btn-sm btn-outline-danger" onclick="adminDashboard.revokeAdmin('${admin.userId}')">
                        <i class="bi bi-shield-x"></i> Revoke
                    </button>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    async searchUsersForAdmin(searchTerm) {
        if (!searchTerm.trim()) {
            document.getElementById('userSearchResults').innerHTML = '';
            return;
        }

        try {
            const response = await fetch('src/controller/admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=search_users&searchTerm=${encodeURIComponent(searchTerm)}`
            });

            const data = await response.json();
            
            if (data.success) {
                this.displayUserSearchResults(data.users);
            } else {
                this.showToast('Error searching users: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error searching users:', error);
            this.showToast('Error searching users', 'error');
        }
    }

    displayUserSearchResults(users) {
        const container = document.getElementById('userSearchResults');
        
        if (users.length === 0) {
            container.innerHTML = '<p class="text-muted">No users found</p>';
            return;
        }

        const html = users.map(user => `
            <div class="search-result-item" onclick="adminDashboard.selectUser('${user.userId}', '${this.escapeHtml(user.name)}', '${this.escapeHtml(user.email)}')">
                <h6 class="mb-1">${this.escapeHtml(user.name)}</h6>
                <small class="text-muted">${this.escapeHtml(user.email)} • ${user.type}</small>
            </div>
        `).join('');

        container.innerHTML = `<div class="search-results">${html}</div>`;
    }

    selectUser(userId, name, email) {
        this.selectedUserId = userId;
        document.getElementById('userSearchInput').value = name;
        document.getElementById('userSearchResults').innerHTML = '';
        this.showToast(`Selected user: ${name}`, 'success');
    }

    // Actions
    async toggleAdmin(userId, userName, makeAdmin) {
        const action = makeAdmin ? 'grant' : 'revoke';
        const actionText = makeAdmin ? 'grant admin privileges to' : 'revoke admin privileges from';
        
        this.showConfirmation(
            makeAdmin ? 'Grant Admin Access' : 'Revoke Admin Access',
            `Are you sure you want to ${actionText} ${userName}?`,
            () => this.performToggleAdmin(userId, makeAdmin)
        );
    }

    async performToggleAdmin(userId, makeAdmin) {
        try {
            const action = makeAdmin ? 'grant_admin' : 'revoke_admin';
            const response = await fetch('src/controller/admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${action}&userId=${userId}&role=admin`
            });

            const data = await response.json();
            
            if (data.success) {
                const message = makeAdmin ? 'Admin privileges granted successfully' : 'Admin privileges revoked successfully';
                this.showToast(message, 'success');
                this.loadUsers(); // Refresh user list
            } else {
                const errorMessage = makeAdmin ? 'Error granting admin role' : 'Error revoking admin role';
                this.showToast(`${errorMessage}: ${data.message}`, 'error');
            }
        } catch (error) {
            console.error('Error toggling admin role:', error);
            this.showToast('Error updating admin privileges', 'error');
        }
    }

    async deleteUser(userId, userType, userName) {
        this.showConfirmation(
            'Delete User Account',
            `Are you sure you want to delete ${userName}'s ${userType} account? This action cannot be undone and will permanently remove all user data.`,
            () => this.performDeleteUser(userId, userType)
        );
    }

    async performDeleteUser(userId, userType) {
        try {
            const response = await fetch('src/controller/admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete_user&userId=${userId}&userType=${userType}`
            });

            const data = await response.json();
            
            if (data.success) {
                this.showToast('User account deleted successfully', 'success');
                this.loadUsers(); // Refresh user list
                this.loadAdmins(); // Refresh admin list
            } else {
                this.showToast('Error deleting user: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error deleting user:', error);
            this.showToast('Error deleting user', 'error');
        }
    }

    async revokeAdmin(userId) {
        this.showConfirmation(
            'Revoke Admin Role',
            'Are you sure you want to revoke admin privileges from this user?',
            () => this.performRevokeAdmin(userId)
        );
    }

    async performRevokeAdmin(userId) {
        try {
            const response = await fetch('src/controller/admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=revoke_admin&userId=${userId}`
            });

            const data = await response.json();
            
            if (data.success) {
                this.showToast('Admin role revoked successfully', 'success');
                this.loadUsers(); // Refresh user list
                this.loadAdmins(); // Refresh admin list
            } else {
                this.showToast('Error revoking admin role: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error revoking admin role:', error);
            this.showToast('Error revoking admin role', 'error');
        }
    }

    async togglePrivacy(contentId) {
        try {
            const response = await fetch('src/controller/admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggle_project_privacy&projectId=${contentId}`
            });

            const data = await response.json();
            
            if (data.success) {
                this.showToast('Project privacy updated successfully', 'success');
                this.loadContent('projects');
            } else {
                this.showToast('Error updating privacy: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error toggling privacy:', error);
            this.showToast('Error updating privacy', 'error');
        }
    }

    async deleteContent(contentId, contentType) {
        this.showConfirmation(
            'Delete Content',
            `Are you sure you want to delete this ${contentType}? This action cannot be undone.`,
            () => this.performDeleteContent(contentId, contentType)
        );
    }

    async performDeleteContent(contentId, contentType) {
        const action = contentType === 'projects' ? 'delete_project' : 'delete_event';
        const paramName = contentType === 'projects' ? 'projectId' : 'eventId';

        try {
            const response = await fetch('src/controller/admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${action}&${paramName}=${contentId}`
            });

            const data = await response.json();
            
            if (data.success) {
                this.showToast(`${contentType} deleted successfully`, 'success');
                this.loadContent(contentType);
            } else {
                this.showToast('Error deleting content: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error deleting content:', error);
            this.showToast('Error deleting content', 'error');
        }
    }

    // Utility Functions
    showConfirmation(title, message, callback) {
        document.getElementById('confirmTitle').textContent = title;
        document.getElementById('confirmBody').textContent = message;
        
        const confirmButton = document.getElementById('confirmAction');
        confirmButton.onclick = () => {
            callback();
            bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
        };
        
        new bootstrap.Modal(document.getElementById('confirmModal')).show();
    }

    clearConfirmationModal() {
        document.getElementById('confirmTitle').textContent = 'Confirm Action';
        document.getElementById('confirmBody').textContent = 'Are you sure you want to perform this action?';
        document.getElementById('confirmAction').onclick = null;
    }

    clearAddAdminForm() {
        document.getElementById('userSearchInput').value = '';
        document.getElementById('userSearchResults').innerHTML = '';
        document.getElementById('adminRole').value = 'admin';
        this.selectedUserId = null;
    }

    showToast(message, type = 'info') {
        const toast = document.getElementById('adminToast');
        const toastMessage = document.getElementById('toastMessage');
        
        toastMessage.textContent = message;
        
        // Update toast appearance based on type
        const toastHeader = toast.querySelector('.toast-header');
        const icon = toastHeader.querySelector('i');
        
        switch (type) {
            case 'success':
                icon.className = 'bi bi-check-circle text-success me-2';
                break;
            case 'error':
                icon.className = 'bi bi-exclamation-circle text-danger me-2';
                break;
            case 'warning':
                icon.className = 'bi bi-exclamation-triangle text-warning me-2';
                break;
            default:
                icon.className = 'bi bi-shield-check text-primary me-2';
        }
        
        new bootstrap.Toast(toast).show();
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Public methods for global access
    switchTab(tabName) {
        const tab = document.querySelector(`[data-bs-target="#${tabName}"]`);
        if (tab) {
            tab.click();
        }
    }

    refreshStatistics() {
        this.loadStatistics();
        this.loadRecentActivity();
        this.showToast('Statistics refreshed', 'success');
    }

    refreshUsers() {
        this.loadUsers();
        this.showToast('User list refreshed', 'success');
    }

    exportData() {
        this.showToast('Export feature coming soon', 'info');
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.adminDashboard = new AdminDashboard();
});

// Global functions for onclick handlers
function switchTab(tabName) {
    if (window.adminDashboard) {
        window.adminDashboard.switchTab(tabName);
    }
}

function refreshStatistics() {
    if (window.adminDashboard) {
        window.adminDashboard.refreshStatistics();
    }
}

function refreshUsers() {
    if (window.adminDashboard) {
        window.adminDashboard.refreshUsers();
    }
}

function exportData() {
    if (window.adminDashboard) {
        window.adminDashboard.exportData();
    }
}

function addAdmin() {
    if (window.adminDashboard) {
        window.adminDashboard.grantAdmin();
    }
}
