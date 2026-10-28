<?php
// Prevent direct access to this file
if (!defined('INCLUDED_IN_PROJECT_DETAILS') && !defined('INCLUDED_IN_EDIT_PROJECT')) {
    header("Location: index.php");
    exit();
}

// Access to project data is through the JavaScript in Project_details.php
?>

<style>
    /* Timeline Editor Modal - Dark Mode (Default) */
    #timelineEditorModal .modal-content {
        background-color: #1a1f2c;
        border: 1px solid rgba(82, 140, 255, 0.15);
        border-radius: 15px;
        box-shadow: 0 0 30px rgba(0, 89, 255, 0.15);
    }
    
    #timelineEditorModal .modal-header {
        background-color: #161b26;
        border-bottom: 1px solid rgba(82, 140, 255, 0.15);
        padding: 15px 20px;
        border-radius: 15px 15px 0 0;
    }
    
    #timelineEditorModal .modal-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #3d5afe, #2979ff, #3d5afe);
        border-radius: 15px 15px 0 0;
        opacity: 0.7;
    }
    
    #timelineEditorModal .modal-footer {
        border-top: 1px solid rgba(82, 140, 255, 0.15);
        background-color: #161b26;
        border-radius: 0 0 15px 15px;
    }
    
    #timelineEditorModal .modal-title {
        color: #ffffff;
        font-weight: 600;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    #timelineEditorModal .timeline-items-container {
        max-height: 400px;
        overflow-y: auto;
        padding-right: 5px;
    }
    
    #timelineEditorModal .timeline-item-card {
        background-color: #242a38;
        border: 1px solid rgba(82, 140, 255, 0.15);
        transition: all 0.3s ease;
    }
    
    #timelineEditorModal .timeline-item-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 89, 255, 0.15);
        border-color: rgba(82, 140, 255, 0.3);
    }
    
    #timelineEditorModal .card-title {
        color: #ffffff;
        font-size: 1rem;
    }
    
    #timelineEditorModal .card-text {
        color: #a0a8bd;
    }
    
    #timelineEditorModal .assignment-info {
        color: #8e97ad;
    }
    
    #timelineEditorModal .badge.bg-success {
        background-color: #00897b !important;
    }
    
    #timelineEditorModal .badge.bg-primary {
        background-color: #2962ff !important;
    }
    
    #timelineEditorModal .badge.bg-info {
        background-color: #0288d1 !important;
    }
    
    #timelineEditorModal .badge.bg-danger {
        background-color: #d32f2f !important;
    }
    
    #timelineEditorModal .btn-outline-primary {
        color: #2979ff;
        border-color: #2979ff;
    }
    
    #timelineEditorModal .btn-outline-primary:hover {
        background-color: #2979ff;
        color: #ffffff;
    }
    
    #timelineEditorModal .btn-outline-danger {
        color: #ff5252;
        border-color: #ff5252;
    }
    
    #timelineEditorModal .btn-outline-danger:hover {
        background-color: #ff5252;
        color: #ffffff;
    }
    
    #timelineEditorModal .btn-primary {
        background: linear-gradient(135deg, #3d5afe, #2979ff);
        border: none;
        box-shadow: 0 4px 8px rgba(41, 121, 255, 0.25);
    }
    
    #timelineEditorModal .btn-primary:hover {
        background: linear-gradient(135deg, #2979ff, #3d5afe);
        box-shadow: 0 6px 12px rgba(41, 121, 255, 0.35);
        transform: translateY(-1px);
    }
    
    /* Close button styling - Red color */
    #timelineEditorModal .btn-close {
        filter: invert(22%) sepia(95%) saturate(7426%) hue-rotate(353deg) brightness(103%) contrast(118%);
        opacity: 0.8;
    }
    
    #timelineEditorModal .btn-close:hover {
        filter: invert(17%) sepia(100%) saturate(6827%) hue-rotate(0deg) brightness(104%) contrast(121%);
        opacity: 1;
    }
    
    /* Date input calendar icon styling - Blue color */
    #timelineEditorModal input[type="date"] {
        color-scheme: dark;
        position: relative;
    }
    
    #timelineEditorModal input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(42%) sepia(93%) saturate(2613%) hue-rotate(209deg) brightness(103%) contrast(101%);
        cursor: pointer;
        opacity: 0.8;
    }
    
    #timelineEditorModal input[type="date"]::-webkit-calendar-picker-indicator:hover {
        filter: invert(27%) sepia(100%) saturate(2878%) hue-rotate(217deg) brightness(104%) contrast(97%);
        opacity: 1;
    }
    
    /* Ensure Add New Item button text is white */
    #timelineEditorModal #addTimelineItemBtn {
        color: #ffffff !important;
    }
    
    #timelineEditorModal .btn-outline-secondary {
        color: #a0a8bd;
        border-color: #4e5569;
    }
    
    #timelineEditorModal .btn-outline-secondary:hover {
        background-color: #4e5569;
        color: #ffffff;
        border-color: #4e5569;
    }
    
    #editTimelineItemForm {
        background-color: #1e2433;
        border: 1px solid rgba(82, 140, 255, 0.15);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    }
    
    #editTimelineItemForm .card-header {
        background-color: #181e2a;
        border-bottom: 1px solid rgba(82, 140, 255, 0.15);
    }
    
    #timelineEditorModal .form-label {
        color: #a0a8bd;
        font-weight: 500;
        margin-bottom: 6px;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
    }
    
    #timelineEditorModal .form-label i {
        margin-right: 6px;
        color: #3d5afe;
    }
    
    #timelineEditorModal .form-control,
    #timelineEditorModal .form-select {
        background-color: #242a38;
        border: 1px solid #313846;
        color: #e0e0e0;
        border-radius: 8px;
        padding: 10px 12px;
    }
    
    #timelineEditorModal .form-control:focus,
    #timelineEditorModal .form-select:focus {
        background-color: #263040;
        border-color: #3d5afe;
        box-shadow: 0 0 0 3px rgba(61, 90, 254, 0.15);
        color: #ffffff;
    }
    
    #timelineEditorModal .form-control::placeholder {
        color: #6c7693;
        opacity: 0.7;
    }
    
    #timelineEditorModal select[multiple] {
        height: auto;
        min-height: 120px;
        padding: 8px;
    }
    
    #timelineEditorModal select[multiple] option {
        padding: 8px 12px;
        margin-bottom: 3px;
        border-radius: 4px;
        background-color: #242a38;
        color: #e0e0e0;
    }
    
    #timelineEditorModal select[multiple] option:hover {
        background-color: #313846;
    }
    
    #timelineEditorModal select[multiple] option:checked {
        background-color: #2979ff !important;
        color: #ffffff;
    }
    
    #timelineEditorModal .form-text {
        color: #8e97ad;
        font-size: 0.8rem;
        margin-top: 5px;
    }
    
    #timelineEditorModal .form-text i {
        color: #3d5afe;
        margin-right: 4px;
    }
    
    #timelineEditorModal .alert-info {
        background-color: rgba(3, 169, 244, 0.1);
        border-color: rgba(3, 169, 244, 0.2);
        color: #81d4fa;
    }
    
    /* Timeline Editor Modal - Light Mode */
    [data-theme="light"] #timelineEditorModal .modal-content {
        background-color: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.1);
        box-shadow: 0 0 30px rgba(0, 0, 0, 0.08);
    }
    
    [data-theme="light"] #timelineEditorModal .modal-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }
    
    [data-theme="light"] #timelineEditorModal .modal-footer {
        border-top: 1px solid rgba(0, 0, 0, 0.1);
        background-color: #f8f9fa;
    }
    
    [data-theme="light"] #timelineEditorModal .modal-title {
        color: #212529;
    }
    
    [data-theme="light"] #timelineEditorModal .timeline-item-card {
        background-color: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.1);
    }
    
    [data-theme="light"] #timelineEditorModal .timeline-item-card:hover {
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    }
    
    [data-theme="light"] #timelineEditorModal .card-title {
        color: #212529;
    }
    
    [data-theme="light"] #timelineEditorModal .card-text {
        color: #6c757d;
    }
    
    [data-theme="light"] #timelineEditorModal .assignment-info {
        color: #6c757d;
    }
    
    [data-theme="light"] #editTimelineItemForm {
        background-color: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.1);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
    }
    
    [data-theme="light"] #editTimelineItemForm .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }
    
    [data-theme="light"] #timelineEditorModal .form-label {
        color: #495057;
    }
    
    [data-theme="light"] #timelineEditorModal .form-control,
    [data-theme="light"] #timelineEditorModal .form-select {
        background-color: #ffffff;
        border: 1px solid #ced4da;
        color: #212529;
    }
    
    [data-theme="light"] #timelineEditorModal .form-control:focus,
    [data-theme="light"] #timelineEditorModal .form-select:focus {
        background-color: #ffffff;
        border-color: #2979ff;
        box-shadow: 0 0 0 3px rgba(41, 121, 255, 0.15);
        color: #212529;
    }
    
    [data-theme="light"] #timelineEditorModal .form-control::placeholder {
        color: #adb5bd;
    }
    
    [data-theme="light"] #timelineEditorModal select[multiple] option {
        background-color: #ffffff;
        color: #212529;
    }
    
    [data-theme="light"] #timelineEditorModal select[multiple] option:hover {
        background-color: #f8f9fa;
    }
    
    [data-theme="light"] #timelineEditorModal .form-text {
        color: #6c757d;
    }
    
    [data-theme="light"] #timelineEditorModal .alert-info {
        background-color: #e3f2fd;
        border-color: #bbdefb;
        color: #0d47a1;
    }
    
    /* Light mode - Close button styling - Red color */
    [data-theme="light"] #timelineEditorModal .btn-close {
        filter: invert(22%) sepia(95%) saturate(7426%) hue-rotate(353deg) brightness(103%) contrast(118%);
        opacity: 0.8;
    }
    
    [data-theme="light"] #timelineEditorModal .btn-close:hover {
        filter: invert(17%) sepia(100%) saturate(6827%) hue-rotate(0deg) brightness(104%) contrast(121%);
        opacity: 1;
    }
    
    /* Light mode - Date input calendar icon styling - Blue color */
    [data-theme="light"] #timelineEditorModal input[type="date"] {
        color-scheme: light;
    }
    
    [data-theme="light"] #timelineEditorModal input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(42%) sepia(93%) saturate(2613%) hue-rotate(209deg) brightness(103%) contrast(101%);
        cursor: pointer;
        opacity: 0.8;
    }
    
    [data-theme="light"] #timelineEditorModal input[type="date"]::-webkit-calendar-picker-indicator:hover {
        filter: invert(27%) sepia(100%) saturate(2878%) hue-rotate(217deg) brightness(104%) contrast(97%);
        opacity: 1;
    }
    
    /* Light mode - Ensure Add New Item button text is white */
    [data-theme="light"] #timelineEditorModal #addTimelineItemBtn {
        color: #ffffff !important;
    }
</style>

<!-- Timeline Editor Modal -->
<div class="modal fade" id="timelineEditorModal" tabindex="-1" aria-labelledby="timelineEditorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="timelineEditorModalLabel">
                    <i class="bi bi-calendar3"></i> Edit Project Timeline
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="timeline-items-container">
                    <!-- Timeline items will be loaded here -->
                </div>
                
                <div id="timelineEditorControls" class="mt-4 mb-3 d-flex justify-content-between">
                    <button type="button" id="addTimelineItemBtn" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Add New Item
                    </button>
                </div>
                
                <!-- Form for editing a single timeline item -->
                <div id="editTimelineItemForm" class="card mb-4" style="display: none;">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span id="timelineItemFormTitle">Add Timeline Item</span>
                        <button type="button" class="btn-close" id="closeTimelineItemFormBtn"></button>
                    </div>
                    <div class="card-body">
                        <input type="hidden" id="timelineItemIndex">
                        
                        <div class="mb-3">
                            <label for="timelineItemTitle" class="form-label">
                                <i class="bi bi-type-h1"></i> Title*
                            </label>
                            <input type="text" class="form-control" id="timelineItemTitle" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="timelineItemDate" class="form-label">
                                <i class="bi bi-calendar-event"></i> Date*
                            </label>
                            <input type="date" class="form-control" id="timelineItemDate" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="timelineItemStatus" class="form-label">
                                <i class="bi bi-flag"></i> Status
                            </label>
                            <select class="form-select" id="timelineItemStatus">
                                <option value="Planned">Planned</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                                <option value="Delayed">Delayed</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="timelineItemDescription" class="form-label">
                                <i class="bi bi-text-paragraph"></i> Description
                            </label>
                            <textarea class="form-control" id="timelineItemDescription" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3" id="assignedByFormGroup">
                            <label for="timelineItemAssignedBy" class="form-label">
                                <i class="bi bi-person"></i> Assigned By
                            </label>
                            <select class="form-select" id="timelineItemAssignedBy">
                                <option value="">Select member (optional)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="assignedToFormGroup">
                            <label for="timelineItemAssignedTo" class="form-label">
                                <i class="bi bi-people"></i> Assigned To
                            </label>
                            <select class="form-select" id="timelineItemAssignedTo" multiple size="3">
                                <option value="">Select members (optional)</option>
                            </select>
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i> Hold Ctrl/Cmd to select multiple
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-outline-secondary me-2" id="cancelTimelineItemBtn">
                                Cancel
                            </button>
                            <button type="button" class="btn btn-primary" id="saveTimelineItemBtn">
                                <i class="bi bi-save me-1"></i> Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveAllTimelineBtn">
                    <i class="bi bi-save me-1"></i> Save All Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Timeline Editor JavaScript -->
<script>
    // Define utility fallback functions if not already defined
    if (typeof showToast !== 'function') {
        window.showToast = function(title, message, type) {
            // Default implementation using alert if not defined in parent
            alert(title + ': ' + message);
        };
    }
    
    if (typeof renderTimeline !== 'function') {
        window.renderTimeline = function(project) {
            // Default implementation - try to find the parent page's implementation
            if (typeof window.renderProjectTimeline === 'function') {
                window.renderProjectTimeline(project);
            } 
            // Otherwise do nothing - timeline will be updated next time page refreshes
        };
    }
    
    // This script will be initialized after the main project data is loaded
    function initializeTimelineEditor() {
        // Elements
        const timelineEditorModal = document.getElementById('timelineEditorModal');
        const timelineItemsContainer = document.querySelector('.timeline-items-container');
        const addTimelineItemBtn = document.getElementById('addTimelineItemBtn');
        const editTimelineItemForm = document.getElementById('editTimelineItemForm');
        const closeTimelineItemFormBtn = document.getElementById('closeTimelineItemFormBtn');
        const cancelTimelineItemBtn = document.getElementById('cancelTimelineItemBtn');
        const saveTimelineItemBtn = document.getElementById('saveTimelineItemBtn');
        const saveAllTimelineBtn = document.getElementById('saveAllTimelineBtn');
        
        // Form elements
        const timelineItemFormTitle = document.getElementById('timelineItemFormTitle');
        const timelineItemIndex = document.getElementById('timelineItemIndex');
        const timelineItemTitle = document.getElementById('timelineItemTitle');
        const timelineItemDate = document.getElementById('timelineItemDate');
        const timelineItemStatus = document.getElementById('timelineItemStatus');
        const timelineItemDescription = document.getElementById('timelineItemDescription');
        const timelineItemAssignedBy = document.getElementById('timelineItemAssignedBy');
        const timelineItemAssignedTo = document.getElementById('timelineItemAssignedTo');
        
        // Store timeline data locally for editing
        let timelineItems = [];
        let projectId = '';
        
        // Format date from various possible MongoDB formats to YYYY-MM-DD
        function formatDateForInput(mongoDate) {
            try {
                if (!mongoDate) return '';
                
                let date;
                
                // Case 1: Object with $date property (MongoDB BSON Date)
                if (mongoDate.$date) {
                    if (typeof mongoDate.$date === 'string') {
                        date = new Date(mongoDate.$date);
                    } else if (typeof mongoDate.$date === 'number') {
                        date = new Date(mongoDate.$date);
                    } else {
                        return '';
                    }
                } 
                // Case 2: Direct ISO string
                else if (typeof mongoDate === 'string') {
                    date = new Date(mongoDate);
                }
                // Case 3: Regular date object
                else if (mongoDate instanceof Date) {
                    date = mongoDate;
                }
                // Case 4: Try to parse as string
                else {
                    date = new Date(mongoDate);
                }
                
                // Check if date is valid
                if (isNaN(date.getTime())) {
                    console.warn('Invalid date:', mongoDate);
                    return '';
                }
                
                // Format as YYYY-MM-DD for input type="date"
                return date.toISOString().split('T')[0];
            } catch (error) {
                console.error('Error formatting date:', error, mongoDate);
                return '';
            }
        }
        
        // Initialize the timeline editor with project data
        function loadTimelineData(project) {
            // Store project ID for saving - handle different MongoDB ID formats
            if (project._id) {
                if (typeof project._id === 'string') {
                    projectId = project._id;
                } else if (project._id.$oid) {
                    projectId = project._id.$oid;
                } else if (typeof project._id.toString === 'function') {
                    projectId = project._id.toString();
                }
            }
            
            console.log('Timeline editor initialized with project ID:', projectId);
            
            // Clone the timeline data to avoid modifying the original
            timelineItems = project.timeline ? JSON.parse(JSON.stringify(project.timeline)) : [];
            
            // Sort timeline items by date
            timelineItems.sort((a, b) => {
                const dateA = new Date(a.date).getTime() || 0;
                const dateB = new Date(b.date).getTime() || 0;
                return dateA - dateB;
            });
            
            // Render timeline items in the editor
            renderTimelineItems();
        }
        
        // Render timeline items in the editor
        function renderTimelineItems() {
            if (!timelineItems || timelineItems.length === 0) {
                timelineItemsContainer.innerHTML = `
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        No timeline items yet. Click "Add New Item" to create your first timeline item.
                    </div>
                `;
                return;
            }
            
            let html = '<div class="timeline-items-list">';
            
            timelineItems.forEach((item, index) => {
                // Format date for display
                const date = formatDateForInput(item.date);
                const displayDate = new Date(date).toLocaleDateString('en-US', {
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric'
                });
                
                // Determine status badge class
                let statusBadgeClass = 'bg-secondary';
                if (item.status) {
                    const statusLower = item.status.toLowerCase();
                    if (statusLower.includes('completed')) {
                        statusBadgeClass = 'bg-success';
                    } else if (statusLower.includes('progress')) {
                        statusBadgeClass = 'bg-primary';
                    } else if (statusLower.includes('delayed')) {
                        statusBadgeClass = 'bg-danger';
                    } else if (statusLower.includes('planned')) {
                        statusBadgeClass = 'bg-info';
                    }
                }
                
                // Get assigned by name
                let assignedByName = '';
                if (item.assignedBy) {
                    // Handle new format (object with id and name)
                    if (typeof item.assignedBy === 'object' && item.assignedBy !== null) {
                        assignedByName = item.assignedBy.name || '';
                    } 
                    // Handle legacy format (string)
                    else {
                        const assignedBy = item.assignedBy;
                        const assignedByMember = findMemberById(assignedBy);
                        assignedByName = assignedByMember ? assignedByMember.name : assignedBy;
                    }
                }
                
                // Get assigned to names
                let assignedToNames = [];
                if (item.assignedTo) {
                    if (Array.isArray(item.assignedTo)) {
                        // Map to names, handling both new and legacy formats
                        assignedToNames = item.assignedTo.map(assignee => {
                            // Handle new format (object with id and name)
                            if (typeof assignee === 'object' && assignee !== null) {
                                return assignee.name || 'Unknown';
                            }
                            // Handle legacy format (string ID or name)
                            const member = findMemberById(assignee);
                            return member ? member.name : assignee;
                        });
                    } else {
                        // Handle single assignee case (backward compatibility)
                        const member = findMemberById(item.assignedTo);
                        assignedToNames = [member ? member.name : item.assignedTo];
                    }
                }
                
                html += `
                    <div class="timeline-item-card card mb-3" data-index="${index}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title mb-1">${item.title}</h5>
                                    <div class="text-muted small">
                                        <i class="bi bi-calendar me-1"></i>${displayDate}
                                    </div>
                                </div>
                                <div>
                                    <span class="badge ${statusBadgeClass} me-2">${item.status || 'Planned'}</span>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary edit-timeline-item" data-index="${index}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger delete-timeline-item" data-index="${index}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            ${item.description ? `<p class="card-text mt-2">${item.description}</p>` : ''}
                            
                            <div class="assignment-info small mt-2">
                                ${assignedByName ? `<div><i class="bi bi-person-check me-1"></i>Assigned by: ${assignedByName}</div>` : ''}
                                ${assignedToNames.length > 0 ? `
                                    <div><i class="bi bi-people me-1"></i>Assigned to: ${assignedToNames.join(', ')}</div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            timelineItemsContainer.innerHTML = html;
            
            // Add event listeners
            document.querySelectorAll('.edit-timeline-item').forEach(button => {
                button.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    editTimelineItem(index);
                });
            });
            
            document.querySelectorAll('.delete-timeline-item').forEach(button => {
                button.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    deleteTimelineItem(index);
                });
            });
        }
        
        // Open the edit form for a timeline item
        function editTimelineItem(index) {
            const item = timelineItems[index];
            
            // Update form title
            timelineItemFormTitle.textContent = 'Edit Timeline Item';
            
            // Populate form fields
            timelineItemIndex.value = index;
            timelineItemTitle.value = item.title || '';
            timelineItemDate.value = formatDateForInput(item.date);
            timelineItemStatus.value = item.status || 'Planned';
            timelineItemDescription.value = item.description || '';
            
            // Populate assignment dropdowns with project members
            populateAssignmentDropdowns();
            
            // Set assignment values
            let assignedByValue = '';
            
            // Handle new format (object with id and name)
            if (item.assignedBy && typeof item.assignedBy === 'object' && item.assignedBy.id) {
                assignedByValue = item.assignedBy.id;
            } 
            // Handle legacy format (string ID or name)
            else if (item.assignedBy) {
                assignedByValue = item.assignedBy;
            }
            
            document.getElementById('timelineItemAssignedBy').value = assignedByValue;
            
            // Handle multiple assignees for "Assigned To" (with backward compatibility)
            const assignedToSelect = document.getElementById('timelineItemAssignedTo');
            
            // Reset selections
            Array.from(assignedToSelect.options).forEach(option => {
                option.selected = false;
            });
            
            // Set selected values
            if (item.assignedTo && Array.isArray(item.assignedTo)) {
                // Extract IDs from objects if in new format
                const assignedToIds = item.assignedTo.map(assignee => 
                    (assignee && typeof assignee === 'object' && assignee.id) ? assignee.id : assignee
                );
                
                // Select the appropriate options in the dropdown
                Array.from(assignedToSelect.options).forEach(option => {
                    if (option.value) {
                        option.selected = assignedToIds.includes(option.value);
                    }
                });
            }
            
            // Show the form
            editTimelineItemForm.style.display = 'block';
            
            // Scroll to the form
            editTimelineItemForm.scrollIntoView({ behavior: 'smooth' });
        }
        
        // Add a new timeline item
        function addTimelineItem() {
            // Clear form fields
            timelineItemFormTitle.textContent = 'Add New Timeline Item';
            timelineItemIndex.value = '';
            timelineItemTitle.value = '';
            timelineItemDate.value = new Date().toISOString().split('T')[0]; // Today's date
            timelineItemStatus.value = 'Planned';
            timelineItemDescription.value = '';
            
            // Populate assignment dropdowns
            populateAssignmentDropdowns();
            
            // Reset selections in dropdowns
            timelineItemAssignedBy.value = '';
            
            Array.from(timelineItemAssignedTo.options).forEach(option => {
                option.selected = false;
            });
            
            // Show the form
            editTimelineItemForm.style.display = 'block';
            
            // Scroll to the form
            editTimelineItemForm.scrollIntoView({ behavior: 'smooth' });
        }
        
        // Delete a timeline item
        function deleteTimelineItem(index) {
            if (confirm('Are you sure you want to delete this timeline item?')) {
                timelineItems.splice(index, 1);
                renderTimelineItems();
                
                // Hide form if currently editing the deleted item
                if (timelineItemIndex.value == index) {
                    editTimelineItemForm.style.display = 'none';
                }
            }
        }
        
        // Save current timeline item
        function saveTimelineItem() {
            // Validate required fields
            if (!timelineItemTitle.value) {
                alert('Please enter a title for the timeline item.');
                timelineItemTitle.focus();
                return;
            }
            
            if (!timelineItemDate.value) {
                alert('Please enter a date for the timeline item.');
                timelineItemDate.focus();
                return;
            }
            
            // Prepare timeline item data
            const item = {
                title: timelineItemTitle.value,
                description: timelineItemDescription.value,
                date: timelineItemDate.value,
                status: timelineItemStatus.value,
            };
            
            // Handle assignedBy - get both ID and name
            if (timelineItemAssignedBy.value) {
                const selectedOption = timelineItemAssignedBy.options[
                    timelineItemAssignedBy.selectedIndex
                ];
                item.assignedBy = {
                    id: timelineItemAssignedBy.value,
                    name: selectedOption.text.replace(/ \(.*\)$/, ''), // Remove role from text
                    type: selectedOption.text.toLowerCase().includes('supervisor') ? 'faculty' : 'student'
                };
            }
            
            // Handle assignedTo - get multiple selected values with both ID and name
            const assignedTo = Array.from(timelineItemAssignedTo.selectedOptions)
                .filter(option => option.value !== '')
                .map(option => ({
                    id: option.value,
                    name: option.text.replace(/ \(.*\)$/, ''), // Remove role from text
                    type: 'student' // Assuming assignedTo are only students
                }));
                
            if (assignedTo.length > 0) {
                item.assignedTo = assignedTo;
            }
            
            // Add or update the item
            const index = timelineItemIndex.value !== '' ? parseInt(timelineItemIndex.value) : -1;
            if (index >= 0) {
                timelineItems[index] = item;
            } else {
                timelineItems.push(item);
            }
            
            // Update the UI
            renderTimelineItems();
            
            // Hide the form
            editTimelineItemForm.style.display = 'none';
        }
        
        // Save all timeline changes to the server
        function saveAllTimelineChanges() {
            // Show loading state on button
            const saveButton = document.getElementById('saveAllTimelineBtn');
            const originalText = saveButton.innerHTML;
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Saving...';
            
            // Log what's happening
            console.log('Starting to save timeline changes');
            console.log('Project ID:', projectId);
            console.log('Timeline items:', timelineItems);
            
            // Validate project ID
            if (!projectId) {
                console.error('Error: Project ID is missing or invalid');
                saveButton.disabled = false;
                saveButton.innerHTML = originalText;
                
                if (typeof showToast === 'function') {
                    showToast('Error', 'Project ID is missing or invalid', 'error');
                } else {
                    alert('Error: Project ID is missing or invalid');
                }
                return;
            }
            
            // Sort timeline items by date before saving
            timelineItems.sort((a, b) => {
                const dateA = new Date(formatDateForInput(a.date)).getTime() || 0;
                const dateB = new Date(formatDateForInput(b.date)).getTime() || 0;
                return dateA - dateB;
            });
            
            // Prepare data for submission
            const data = new FormData();
            data.append('project_id', projectId);
            data.append('timeline', JSON.stringify(timelineItems));
            data.append('action', 'update_timeline'); // Specify that we're updating the timeline
            
            // Log the request
            console.log('Sending request to update_project.php');
            console.log('Request data:', {
                project_id: projectId,
                action: 'update_timeline',
                timeline_count: timelineItems.length
            });
            
            // Determine correct path to update_project.php
            let basePath = '';
            if (window.location.pathname.includes('/Project_details.php')) {
                // If we're on the project details page, use relative path
                basePath = 'src/model/update_project.php';
            } else {
                // Try to build path based on current URL depth
                const pathParts = window.location.pathname.split('/');
                if (pathParts.length > 2) {
                    // We're in a subdirectory, adjust path accordingly
                    basePath = '../src/model/update_project.php';
                } else {
                    // We're at root level
                    basePath = 'src/model/update_project.php';
                }
            }
            
            console.log('Using path for update_project.php:', basePath);
            
            // Send to server
            fetch(basePath, {
                method: 'POST',
                body: data
            })
            .then(response => {
                console.log('Response received:', response.status);
                if (!response.ok) {
                    throw new Error(`Server responded with status ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                // Log the result
                console.log('Response data:', result);
                
                // Restore button state
                saveButton.disabled = false;
                saveButton.innerHTML = originalText;
                
                if (result.success) {
                    // Fetch current user info first
                    fetch('src/model/get_current_user.php')
                    .then(response => response.json())
                    .then(userData => {
                        // Get user name from response or use fallback
                        let currentUserName = 'Someone';
                        if (userData && userData.success && userData.isLoggedIn && userData.user) {
                            currentUserName = userData.user.name || 'Someone';
                        }
                        
                        // Send system message with the user name
                        const systemMessageData = new FormData();
                        systemMessageData.append('projectId', projectId);
                        systemMessageData.append('message', `${currentUserName} updated the project timeline.`);
                        
                        // Send the system message
                        return fetch('src/model/send_system_chat_message.php', {
                            method: 'POST',
                            body: systemMessageData
                        });
                    })
                    .then(response => response.json())
                    .then(msgResult => {
                        console.log('System message result:', msgResult);
                    })
                    .catch(error => {
                        console.error('Error sending system message:', error);
                    });
                    
                    // Close modal
                    const bsModal = bootstrap.Modal.getInstance(timelineEditorModal);
                    bsModal.hide();
                    
                    // Show success message using alert or existing toast system
                    if (typeof showToast === 'function') {
                        showToast('Success', 'Timeline updated successfully', 'success');
                    } else {
                        // Fallback to alert if showToast is not available
                        alert('Timeline updated successfully');
                    }
                    
                    // Update project data in memory
                    if (window.currentProject) {
                        window.currentProject.timeline = JSON.parse(JSON.stringify(timelineItems));
                        
                        // Try to render timeline if the function exists
                        if (typeof renderTimeline === 'function') {
                            renderTimeline(window.currentProject);
                        } else if (typeof window.renderProjectTimeline === 'function') {
                            window.renderProjectTimeline(window.currentProject);
                        }
                    }
                } else {
                    // Show error message
                    console.error('Error saving timeline:', result.message);
                    if (typeof showToast === 'function') {
                        showToast('Error', result.message || 'Failed to update timeline', 'error');
                    } else {
                        // Fallback to alert
                        alert('Error: ' + (result.message || 'Failed to update timeline'));
                    }
                }
            })
            .catch(error => {
                // Log the error
                console.error('Error saving timeline:', error);
                
                // Restore button state
                saveButton.disabled = false;
                saveButton.innerHTML = originalText;
                
                // Try to provide a better error message
                let errorMessage = 'An error occurred while updating the timeline';
                
                // Check if we can get a more specific error
                if (error.message) {
                    errorMessage += ': ' + error.message;
                }
                
                // Show error message
                if (typeof showToast === 'function') {
                    showToast('Error', errorMessage, 'error');
                } else {
                    // Fallback to alert
                    alert('Error: ' + errorMessage);
                }
            });
        }
        
        // Helper function to populate assignment dropdowns with project members
        function populateAssignmentDropdowns() {
            const assignedBySelect = timelineItemAssignedBy;
            const assignedToSelect = timelineItemAssignedTo;
            
            if (!assignedBySelect || !assignedToSelect) return;
            
            // Clear existing options (except the first default option)
            assignedBySelect.innerHTML = '<option value="">Select member (optional)</option>';
            assignedToSelect.innerHTML = '<option value="">Select members (optional)</option>';
            
            // Get current project members from the main page if available
            if (window.currentProject && window.currentProject.members) {
                const members = window.currentProject.members;
                
                // Add all team members to "Assigned By" dropdown, but only students to "Assigned To"
                members.forEach(member => {
                    if (member.name && member.name.trim()) {
                        // Use student ID if available, otherwise use member name
                        const optionValue = (member.userId && member.userId.$oid) ? member.userId.$oid : member.name;
                        const optionText = `${member.name}${member.role ? ' (' + member.role + ')' : ''}`;
                        
                        // Add all members to "Assigned By" dropdown
                        const assignedByOption = new Option(optionText, optionValue);
                        assignedBySelect.add(assignedByOption);
                        
                        // Check if the member is a student (not supervisor/faculty) for "Assigned To"
                        const isStudent = !member.role || 
                                        (member.role.toLowerCase() !== 'supervisor' && 
                                        member.role.toLowerCase() !== 'faculty' &&
                                        member.role.toLowerCase() !== 'creator/supervisor');
                        
                        // Only add students to "Assigned To" dropdown
                        if (isStudent) {
                            const assignedToOption = new Option(optionText, optionValue);
                            assignedToSelect.add(assignedToOption);
                        }
                    }
                });
                
                // Add supervisor to "Assigned By" dropdown if exists
                if (window.currentProject.supervisor) {
                    const supervisor = window.currentProject.supervisor;
                    const supervisorName = typeof supervisor === 'object' ? supervisor.name : supervisor;
                    const supervisorId = typeof supervisor === 'object' && supervisor.id ? supervisor.id : supervisorName;
                    
                    if (supervisorName && supervisorName.trim()) {
                        const supervisorText = `${supervisorName} (Supervisor)`;
                        const supervisorByOption = new Option(supervisorText, supervisorId);
                        assignedBySelect.add(supervisorByOption);
                    }
                }
            }
        }
        
        // Helper function to find member by ID or name
        function findMemberById(id) {
            if (!id || !window.currentProject || !window.currentProject.members) return null;
            
            // Check current project members
            const members = window.currentProject.members;
            
            // First try to find by user ID
            let member = members.find(m => 
                (m.userId && m.userId.$oid === id) || 
                (m.userId && typeof m.userId === 'string' && m.userId === id)
            );
            
            // If not found by ID, try by name
            if (!member) {
                member = members.find(m => m.name === id);
            }
            
            return member;
        }
        
        // Event listeners
        addTimelineItemBtn.addEventListener('click', addTimelineItem);
        closeTimelineItemFormBtn.addEventListener('click', () => {
            editTimelineItemForm.style.display = 'none';
        });
        cancelTimelineItemBtn.addEventListener('click', () => {
            editTimelineItemForm.style.display = 'none';
        });
        saveTimelineItemBtn.addEventListener('click', saveTimelineItem);
        saveAllTimelineBtn.addEventListener('click', saveAllTimelineChanges);
        
        // Modal events
        timelineEditorModal.addEventListener('shown.bs.modal', function() {
            // Ensure we have latest data when modal opens
            if (window.currentProject) {
                loadTimelineData(window.currentProject);
            }
        });
        
        // Make timeline items sortable with drag and drop (optional enhancement)
        // This would require additional jQuery UI or SortableJS library
        
        // Return public methods for external access
        return {
            loadTimelineData: loadTimelineData
        };
    }
    
    // Initialize the editor when page loads
    let timelineEditor;
    document.addEventListener('DOMContentLoaded', function() {
        timelineEditor = initializeTimelineEditor();
        
        // Expose to window for access from main script
        window.timelineEditor = timelineEditor;
    });
</script> 