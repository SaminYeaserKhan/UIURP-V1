<!-- Project Chat Overlay -->
<div id="projectChatOverlay" class="project-chat-overlay">
    <!-- Chat Header with Close Button -->
    <div class="chat-header">
        <div class="chat-title">
            <span id="chatTitle">Project Chat</span>
            <div class="encryption-status" id="encryptionStatus" style="display: none;">
                <span class="encryption-indicator" id="encryptionIndicator">
                    <i class="bi bi-shield-lock"></i>
                    <span class="encryption-text">End-to-end encrypted</span>
                </span>
                <button class="encryption-settings-btn" id="encryptionSettingsBtn" title="Encryption settings">
                    <i class="bi bi-gear"></i>
                </button>
            </div>
        </div>
        <div class="chat-controls">
            <button id="pinChat" class="panel-control" title="Pin chat">
                <i class="bi bi-pin"></i>
            </button>
            <button id="minimizeLeftPanel" class="panel-control" title="Toggle groups">
                <i class="bi bi-layout-sidebar"></i>
            </button>
            <button id="minimizeRightPanel" class="panel-control" title="Toggle members">
                <i class="bi bi-people"></i>
            </button>
            <button id="closeChat" class="close-chat">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
    
    <!-- Chat Container with 3 sections -->
    <div class="chat-container">
        <!-- Left Panel - Chat Groups -->
        <div id="chatGroupsPanel" class="chat-groups-panel">
            <div class="panel-header">
                <h3>Projects</h3>
                <div class="panel-controls">
                    <button id="collapseProjects" class="panel-collapse-btn" title="Toggle projects panel">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <div class="loading-spinner chat-spinner" id="chatGroupsLoading" aria-label="Loading projects..."></div>
                </div>
            </div>
            <div class="groups-list" id="projectChatGroups">
                <!-- Project chat groups will be loaded here -->
            </div>
        </div>
        
        <!-- Center Panel - Chat Messages -->
        <div class="chat-messages-panel">
            <div class="messages-container">
                <!-- Messages will be loaded dynamically -->
                <div id="noMessagesPlaceholder" class="empty-messages-placeholder">
                    <i class="bi bi-chat-square-text"></i>
                    <p>No messages yet. Be the first to send a message!</p>
                </div>
            </div>
            
            <!-- Message Input Area -->
            <div class="message-input-area">
                <div class="input-container">
                    <label for="chatFileInput" class="attachment-btn" title="Attach file">
                        <i class="bi bi-paperclip"></i>
                        <input type="file" id="chatFileInput" style="display: none;">
                    </label>
                    <input type="text" placeholder="Type a message..." class="message-input" id="chatMessageInput">
                    <button class="emoji-btn" disabled title="Emojis coming soon">
                        <i class="bi bi-emoji-smile"></i>
                    </button>
                    <button class="send-btn" id="sendMessageBtn">
                        <i class="bi bi-send"></i>
                    </button>
                </div>
                <div id="sendingIndicator" class="sending-indicator">
                    <span>Sending</span>
                </div>
                <!-- File Preview Container -->
                <div id="filePreviewContainer" class="file-preview-container">
                    <div class="file-preview">
                        <div class="file-preview-icon">
                            <i class="bi bi-file-earmark"></i>
                        </div>
                        <div class="file-preview-info">
                            <span class="file-name">filename.ext</span>
                            <span class="file-size">0 KB</span>
                        </div>
                        <button class="remove-file" id="removeFileBtn">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Panel - Group Members -->
        <div id="groupMembersPanel" class="group-members-panel">
            <div class="panel-header">
                <h3>Members</h3>
                <div class="panel-controls">
                    <button id="collapseMembers" class="panel-collapse-btn" title="Toggle members panel">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    <div class="loading-spinner chat-spinner" id="membersPanelLoading" aria-label="Loading members..."></div>
                </div>
            </div>
            
            <!-- Supervisors Section -->
            <div class="members-section">
                <div class="section-header">
                    <h4>Supervisor</h4>
                </div>
                <div class="members-list" id="supervisorsList">
                    <!-- Supervisors will be loaded here dynamically -->
                    <div class="empty-members-placeholder">
                        <i class="bi bi-person-badge"></i>
                        <p>No supervisor assigned</p>
                    </div>
                </div>
            </div>
            
            <!-- Team Members Section -->
            <div class="members-section">
                <div class="section-header">
                    <h4>Team Members</h4>
                    <span class="member-count" id="teamMemberCount">0</span>
                </div>
                <div class="members-list" id="teamMembersList">
                    <!-- Team members will be loaded here dynamically -->
                    <div class="empty-members-placeholder">
                        <i class="bi bi-people"></i>
                        <p>No team members</p>
                    </div>
                </div>
            </div>
            
            <!-- Leave Project Section -->
            <div class="leave-project-section">
                <button id="leaveProjectBtn" class="leave-project-btn" title="Leave this project and group chat">
                    <i class="bi bi-box-arrow-left"></i>
                    <span>Leave Project</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Styling for Project Chat -->
<link rel="stylesheet" href="assets/styles/project-chat.css">

<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Create backdrop element
    const backdrop = document.createElement('div');
    backdrop.classList.add('project-chat-backdrop');
    document.body.appendChild(backdrop);
    
    // Navbar fix for pinned chat - create a permanent scroll listener
    window.pinnedChatNavbarInterval = null;
    
    function setupPinnedNavbarFix() {
        if (window.pinnedChatNavbarInterval) {
            clearInterval(window.pinnedChatNavbarInterval);
        }
        
        window.pinnedChatNavbarInterval = setInterval(function() {
            if (document.body.classList.contains('chat-pinned')) {
                const navbars = document.querySelectorAll('.navbar, .navbar-fixed-top, .fixed-top, .sticky-top, header, nav, #header');
                navbars.forEach(navbar => {
                    // Skip elements that are children of the chat overlay
                    if (navbar.closest('.project-chat-overlay')) return;
                    
                    // Force fixed position at top
                    navbar.style.position = 'fixed';
                    navbar.style.top = '0';
                    navbar.style.zIndex = '1030';
                    navbar.style.transform = 'none';
                    
                    // Adjust width based on screen size
                    if (window.innerWidth <= 767) {
                        navbar.style.left = '0';
                        navbar.style.width = '100%';
                    } else if (window.innerWidth <= 991) {
                        navbar.style.left = '320px';
                        navbar.style.width = 'calc(100% - 320px)';
                    } else if (window.innerWidth <= 1200) {
                        navbar.style.left = '340px';
                        navbar.style.width = 'calc(100% - 340px)';
                    } else {
                        navbar.style.left = '380px';
                        navbar.style.width = 'calc(100% - 380px)';
                    }
                });
            } else {
                // Clear styles if not in pinned mode
                const navbars = document.querySelectorAll('.navbar, .navbar-fixed-top, .fixed-top, .sticky-top, header, nav, #header');
                navbars.forEach(navbar => {
                    // Skip elements that are children of the chat overlay
                    if (navbar.closest('.project-chat-overlay')) return;
                    
                    navbar.style.position = '';
                    navbar.style.top = '';
                    navbar.style.left = '';
                    navbar.style.width = '';
                    navbar.style.zIndex = '';
                    navbar.style.transform = '';
                });
            }
        }, 100); // Check every 100ms
    }
    
    // Set up the navbar fix on page load
    setupPinnedNavbarFix();
    
    // Setup MutationObserver to continuously monitor collapse buttons
    function monitorCollapseButtons() {
        const chatGroupsPanel = document.getElementById('chatGroupsPanel');
        const groupMembersPanel = document.getElementById('groupMembersPanel');
        const collapseProjects = document.getElementById('collapseProjects');
        const collapseMembers = document.getElementById('collapseMembers');
        
        if (chatGroupsPanel && collapseProjects) {
            // Create observer for left button
            const leftObserver = new MutationObserver(function() {
                if (chatGroupsPanel.classList.contains('collapsed')) {
                    // Force button back to correct position
                    setTimeout(function() {
                        fixCollapseButtonAlignment();
                    }, 0);
                }
            });
            
            // Observe both the button and its parent panel for changes
            leftObserver.observe(collapseProjects, { attributes: true, attributeFilter: ['style', 'class'] });
            leftObserver.observe(chatGroupsPanel, { attributes: true, attributeFilter: ['class'] });
        }
        
        if (groupMembersPanel && collapseMembers) {
            // Create observer for right button
            const rightObserver = new MutationObserver(function() {
                if (groupMembersPanel.classList.contains('collapsed')) {
                    // Force button back to correct position
                    setTimeout(function() {
                        fixCollapseButtonAlignment();
                    }, 0);
                }
            });
            
            // Observe both the button and its parent panel for changes
            rightObserver.observe(collapseMembers, { attributes: true, attributeFilter: ['style', 'class'] });
            rightObserver.observe(groupMembersPanel, { attributes: true, attributeFilter: ['class'] });
        }
        
        // Check and fix alignment repeatedly
        setInterval(function() {
            if ((chatGroupsPanel && chatGroupsPanel.classList.contains('collapsed')) ||
                (groupMembersPanel && groupMembersPanel.classList.contains('collapsed'))) {
                fixCollapseButtonAlignment();
            }
        }, 500);
    }
    
    // Start monitoring collapse buttons
    setTimeout(monitorCollapseButtons, 500);
    
    // Add a dedicated scroll event listener for pinned mode
    window.addEventListener('scroll', function() {
        if (document.body.classList.contains('chat-pinned')) {
            const navbars = document.querySelectorAll('.navbar, .navbar-fixed-top, .fixed-top, .sticky-top, header, nav, #header');
            navbars.forEach(navbar => {
                // Skip elements that are children of the chat overlay
                if (navbar.closest('.project-chat-overlay')) return;
                
                // Force navbar to stay at top
                navbar.style.position = 'fixed';
                navbar.style.top = '0';
            });
        }
    }, { passive: true });
    
    // Add ripple effect to buttons and clickable items
    function addRippleEffect() {
        const buttons = document.querySelectorAll('.panel-control, .close-chat, .send-btn, .attachment-btn, .emoji-btn, .group-item, .member-item');
        
        buttons.forEach(button => {
            if (button.getAttribute('data-has-ripple') === 'true') return;
            button.setAttribute('data-has-ripple', 'true');
            
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                ripple.classList.add('ripple-effect');
                ripple.style.width = ripple.style.height = Math.max(this.offsetWidth, this.offsetHeight) * 2 + 'px';
                
                const rect = this.getBoundingClientRect();
                ripple.style.left = (e.clientX - rect.left) + 'px';
                ripple.style.top = (e.clientY - rect.top) + 'px';
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 800);
            });
        });
    }
    
    // Call initially and whenever chat is opened
    setTimeout(addRippleEffect, 500);
    
    // Use the global function for updating unread chat notification count
    function loadUnreadMessageCount() {
        // Check if the global function exists
        if (typeof window.updateChatNotificationBadge === 'function') {
            window.updateChatNotificationBadge();
        } else {
            // Fallback if global function not available
            fetch('src/model/get_unread_messages.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.getElementById('chatNotificationBadge');
                        if (badge) {
                            if (data.count > 0) {
                                badge.textContent = data.count > 99 ? '99+' : data.count;
                                badge.style.display = 'flex';
                            } else {
                                badge.style.display = 'none';
                            }
                        }
                    }
                })
                .catch(error => console.error('Error fetching unread message count:', error));
        }
    }
    
    // Function to update the read timestamp when user views messages
    function updateReadTimestamp(projectId) {
        if (!projectId) return;
        
        const formData = new FormData();
        formData.append('projectId', projectId);
        
        fetch('src/model/update_chat_read.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update notification count after marking messages as read
                loadUnreadMessageCount();
            }
        })
        .catch(error => console.error('Error updating read timestamp:', error));
    }
    
    // Function to fetch a single message by ID and append it to the chat
    function fetchSingleMessage(messageId, projectId) {
        if (!messageId || !projectId || !messagesContainer) return;
        
        fetch(`src/model/get_single_message.php?id=${messageId}&projectId=${projectId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.message) {
                    // Check if no messages placeholder is showing
                    const noMessagesPlaceholder = document.getElementById('noMessagesPlaceholder');
                    if (noMessagesPlaceholder) {
                        noMessagesPlaceholder.style.display = 'none';
                    }
                    
                    // Check if message is already in the DOM
                    if (!document.querySelector(`[data-message-id="${data.message.id}"]`)) {
                        // Check if we need to add a date separator
                        const messageDate = new Date(data.message.timestamp * 1000).toLocaleDateString();
                        let dateSeparatorNeeded = true;
                        
                        // Check for an existing date separator for this day
                        const dateSeparators = messagesContainer.querySelectorAll('.date-separator');
                        dateSeparators.forEach(separator => {
                            if (separator.textContent === messageDate) {
                                dateSeparatorNeeded = false;
                            }
                        });
                        
                        // Add date separator if needed
                        if (dateSeparatorNeeded) {
                            const dateSeparator = document.createElement('div');
                            dateSeparator.className = 'date-separator';
                            dateSeparator.textContent = messageDate;
                            messagesContainer.appendChild(dateSeparator);
                        }
                        
                        // Append the message
                        appendSingleMessage(data.message, messagesContainer);
                        
                                // Smooth scroll to the bottom
        if (typeof smoothScrollToBottom === 'function') {
            smoothScrollToBottom(400);
        } else {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
                        
                        // No highlight animation for new messages
                        // Removed animation for static message display
                    }
                }
            })
            .catch(error => console.error('Error fetching message:', error));
    }

    // Ensure the chat overlay exists in the body for better positioning
    const chatOverlay = document.getElementById('projectChatOverlay');
    if (chatOverlay) {
        // Move the chat overlay to be a direct child of the body
        document.body.appendChild(chatOverlay);
        
        // Add isolation class to completely isolate the chat from page events
        chatOverlay.classList.add('event-isolation');
        
        // Set a specific z-index to ensure it's above everything else
        chatOverlay.style.zIndex = '99999';
    }
    
    // Chat-specific variables
    let currentProjectId = null;
    let currentProjectName = null;
    let lastMessageTimestamp = null;
    let pollingInterval = null;
    let isFirstLoad = true;
    
    // Elements (re-query after moving)
    const closeChat = document.getElementById('closeChat');
    const minimizeLeftPanel = document.getElementById('minimizeLeftPanel');
    const minimizeRightPanel = document.getElementById('minimizeRightPanel');
    const leftPanel = document.getElementById('chatGroupsPanel');
    const rightPanel = document.getElementById('groupMembersPanel');
    const messagesContainer = document.querySelector('.messages-container');
    const projectChatGroups = document.getElementById('projectChatGroups');
    const chatGroupsLoading = document.getElementById('chatGroupsLoading');
    
    // Track original scroll position
    let scrollPosition = 0;
    
    // Flag to track if chat is pinned
    let isChatPinned = localStorage.getItem('chatPinned') === 'true';
    
    // Original window event handlers
    let originalWheel = null;
    let originalScroll = null;

    // Function to save chat state
    function saveChatState() {
        localStorage.setItem('chatPinned', isChatPinned);
        // If we want to save more chat state in the future, add it here
    }
    
    // Enhanced function to fix collapse button alignment issues
    function fixCollapseButtonAlignment() {
        const chatGroupsPanel = document.getElementById('chatGroupsPanel');
        const groupMembersPanel = document.getElementById('groupMembersPanel');
        const collapseProjects = document.getElementById('collapseProjects');
        const collapseMembers = document.getElementById('collapseMembers');
        
        // Position relative to chat overlay for consistent positioning
        const chatOverlay = document.getElementById('projectChatOverlay');
        const chatRect = chatOverlay ? chatOverlay.getBoundingClientRect() : null;
        
        // Fix left panel collapse button with extreme force
        if (chatGroupsPanel && collapseProjects) {
            if (chatGroupsPanel.classList.contains('collapsed')) {
                // Completely reset the button first
                collapseProjects.removeAttribute('style');
                
                // Direct DOM styling with important flags for each property
                Object.assign(collapseProjects.style, {
                    position: 'fixed',
                    left: '5px',
                    top: '64px', // Fixed position below header
                    zIndex: '99999',
                    background: 'rgba(var(--primary-rgb), 0.2)',
                    borderRadius: '50%',
                    opacity: '1',
                    width: '28px',
                    height: '28px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    transform: 'none',
                    margin: '0',
                    padding: '0',
                    border: '1px solid rgba(var(--primary-rgb), 0.3)',
                    boxShadow: '0 2px 5px rgba(0, 0, 0, 0.2)',
                    minWidth: 'auto',
                    minHeight: 'auto',
                    lineHeight: '1',
                    outline: 'none',
                    transition: 'none'
                });
                
                // Force each style to have !important
                for (let prop in collapseProjects.style) {
                    if (collapseProjects.style[prop] && typeof collapseProjects.style[prop] === 'string') {
                        collapseProjects.style.setProperty(prop, collapseProjects.style[prop], 'important');
                    }
                }
                
                // Also apply using setAttribute for maximum compatibility
                collapseProjects.setAttribute('style', `
                    position: fixed !important;
                    left: 5px !important;
                    top: 64px !important;
                    z-index: 99999 !important;
                    background: rgba(var(--primary-rgb), 0.2) !important;
                    border-radius: 50% !important;
                    opacity: 1 !important;
                    width: 28px !important;
                    height: 28px !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    transform: none !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    border: 1px solid rgba(var(--primary-rgb), 0.3) !important;
                    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2) !important;
                    min-width: auto !important;
                    min-height: auto !important;
                    line-height: 1 !important;
                    outline: none !important;
                    transition: none !important;
                `);
                
                // Make sure icon is correct
                const leftIcon = collapseProjects.querySelector('i');
                if (leftIcon) {
                    leftIcon.className = 'bi bi-chevron-right';
                    leftIcon.style.cssText = `
                        font-size: 16px !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        color: var(--neo-primary) !important;
                    `;
                }
                
                // Force immediate render
                collapseProjects.offsetHeight;
            } else {
                collapseProjects.removeAttribute('style');
                
                // Restore default styles for expanded state
                Object.assign(collapseProjects.style, {
                    position: '',
                    left: '',
                    top: '',
                    zIndex: '',
                    background: '',
                    borderRadius: '',
                    opacity: '',
                    width: '',
                    height: '',
                    display: '',
                    alignItems: '',
                    justifyContent: '',
                    transform: '',
                    margin: '',
                    padding: '',
                    border: '',
                    boxShadow: '',
                    minWidth: '',
                    minHeight: '',
                    lineHeight: '',
                    outline: '',
                    transition: ''
                });
                
                // Make sure icon is correct
                const leftIcon = collapseProjects.querySelector('i');
                if (leftIcon) {
                    leftIcon.className = 'bi bi-chevron-left';
                    leftIcon.removeAttribute('style');
                }
            }
        }
        
        // Fix right panel collapse button with extreme force
        if (groupMembersPanel && collapseMembers) {
            if (groupMembersPanel.classList.contains('collapsed')) {
                // Completely reset the button first
                collapseMembers.removeAttribute('style');
                
                // Direct DOM styling with important flags for each property
                Object.assign(collapseMembers.style, {
                    position: 'fixed',
                    right: '5px',
                    top: '64px', // Fixed position below header
                    zIndex: '99999',
                    background: 'rgba(var(--primary-rgb), 0.2)',
                    borderRadius: '50%',
                    opacity: '1',
                    width: '28px',
                    height: '28px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    transform: 'none',
                    margin: '0',
                    padding: '0',
                    border: '1px solid rgba(var(--primary-rgb), 0.3)',
                    boxShadow: '0 2px 5px rgba(0, 0, 0, 0.2)',
                    minWidth: 'auto',
                    minHeight: 'auto',
                    lineHeight: '1',
                    outline: 'none',
                    transition: 'none'
                });
                
                // Force each style to have !important
                for (let prop in collapseMembers.style) {
                    if (collapseMembers.style[prop] && typeof collapseMembers.style[prop] === 'string') {
                        collapseMembers.style.setProperty(prop, collapseMembers.style[prop], 'important');
                    }
                }
                
                // Also apply using setAttribute for maximum compatibility
                collapseMembers.setAttribute('style', `
                    position: fixed !important;
                    right: 5px !important;
                    top: 64px !important;
                    z-index: 99999 !important;
                    background: rgba(var(--primary-rgb), 0.2) !important;
                    border-radius: 50% !important;
                    opacity: 1 !important;
                    width: 28px !important;
                    height: 28px !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    transform: none !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    border: 1px solid rgba(var(--primary-rgb), 0.3) !important;
                    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2) !important;
                    min-width: auto !important;
                    min-height: auto !important;
                    line-height: 1 !important;
                    outline: none !important;
                    transition: none !important;
                `);
                
                // Make sure icon is correct
                const rightIcon = collapseMembers.querySelector('i');
                if (rightIcon) {
                    rightIcon.className = 'bi bi-chevron-left';
                    rightIcon.style.cssText = `
                        font-size: 16px !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        color: var(--neo-primary) !important;
                    `;
                }
                
                // Force immediate render
                collapseMembers.offsetHeight;
            } else {
                collapseMembers.removeAttribute('style');
                
                // Restore default styles for expanded state
                Object.assign(collapseMembers.style, {
                    position: '',
                    right: '',
                    top: '',
                    zIndex: '',
                    background: '',
                    borderRadius: '',
                    opacity: '',
                    width: '',
                    height: '',
                    display: '',
                    alignItems: '',
                    justifyContent: '',
                    transform: '',
                    margin: '',
                    padding: '',
                    border: '',
                    boxShadow: '',
                    minWidth: '',
                    minHeight: '',
                    lineHeight: '',
                    outline: '',
                    transition: ''
                });
                
                // Make sure icon is correct
                const rightIcon = collapseMembers.querySelector('i');
                if (rightIcon) {
                    rightIcon.className = 'bi bi-chevron-right';
                    rightIcon.removeAttribute('style');
                }
            }
        }
    }
    
    // Function to disable scrolling completely
    function disableScroll() {
        // If chat is pinned, don't disable scrolling
        if (isChatPinned) return;
        
        // Store current scroll position
        scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
        
        // Add helper class to body and html
        document.body.classList.add('no-scroll');
        document.documentElement.classList.add('no-scroll');
        
        // Set body top position to retain scroll position visually
        document.body.style.top = `-${scrollPosition}px`;
        
        // Disable scroll snapping by setting global flag if it exists
        if (typeof window.allowFreeScroll !== 'undefined') {
            window.allowFreeScroll = true;
        }
        
        // Backup and remove original wheel/scroll handlers
        if (window.onwheel) {
            originalWheel = window.onwheel;
            window.onwheel = null;
        }
        if (window.onscroll) {
            originalScroll = window.onscroll;
            window.onscroll = null;
        }
        
        // Disable any existing scroll event listeners in index.php
        if (typeof window.isScrolling !== 'undefined') {
            window.isScrolling = true; // Prevent scroll handlers from executing
        }

        // Activate backdrop
        backdrop.classList.add('active');
        
        // Add event listeners directly to chat overlay to completely contain its events
        chatOverlay.addEventListener('wheel', stopPropagation, true);
        chatOverlay.addEventListener('scroll', stopPropagation, true);
        chatOverlay.addEventListener('touchmove', stopPropagation, true);
        chatOverlay.addEventListener('mousewheel', stopPropagation, true);
        chatOverlay.addEventListener('DOMMouseScroll', stopPropagation, true);
        
        // Force any document scroll handler to be passive
        window.addEventListener('scroll', preventScroll, { passive: false, capture: true });
    }

    // Function to enable scrolling
    function enableScroll() {
        // If chat is pinned, don't enable scrolling
        if (isChatPinned) return;
        
        // Remove helper classes
        document.body.classList.remove('no-scroll');
        document.documentElement.classList.remove('no-scroll');
        
        // Restore scroll position
        document.body.style.top = '';
        window.scrollTo(0, scrollPosition);
        
        // Restore scroll snapping if it was present
        if (typeof window.allowFreeScroll !== 'undefined') {
            window.allowFreeScroll = false;
        }
        
        // Restore original event handlers
        if (originalWheel) {
            window.onwheel = originalWheel;
        }
        if (originalScroll) {
            window.onscroll = originalScroll;
        }
        
        // Reset isScrolling in index.php if it exists
        if (typeof window.isScrolling !== 'undefined') {
            window.isScrolling = false;
        }

        // Hide backdrop
        backdrop.classList.remove('active');
        
        // Remove event listeners from chat overlay
        chatOverlay.removeEventListener('wheel', stopPropagation, true);
        chatOverlay.removeEventListener('scroll', stopPropagation, true);
        chatOverlay.removeEventListener('touchmove', stopPropagation, true);
        chatOverlay.removeEventListener('mousewheel', stopPropagation, true);
        chatOverlay.removeEventListener('DOMMouseScroll', stopPropagation, true);
        
        // Remove document scroll handler
        window.removeEventListener('scroll', preventScroll, { capture: true });
    }
    
    // Function to stop event propagation completely
    function stopPropagation(e) {
        // Only stop propagation if not in pinned mode
        if (!isChatPinned) {
            e.stopPropagation();
            e.stopImmediatePropagation();
        }
    }
    
    // Function to prevent scrolling
    function preventScroll(e) {
        if (chatOverlay.classList.contains('active') && !isChatPinned) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            return false;
        }
    }

    // Capture and cancel wheel events at document level
    document.addEventListener('wheel', function(e) {
        if (chatOverlay.classList.contains('active') && !isChatPinned) {
            // We need to let events within the overlay work for scrolling chat content
            if (!chatOverlay.contains(e.target)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            } else {
                // For events inside the overlay, prevent them from bubbling up to document
                // but still allow the default behavior for scrolling within the overlay
                e.stopPropagation();
            }
        }
    }, { passive: false, capture: true });

    // Prevent touch move events
    document.addEventListener('touchmove', function(e) {
        if (chatOverlay.classList.contains('active') && !isChatPinned) {
            if (!chatOverlay.contains(e.target)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            } else {
                e.stopPropagation();
            }
        }
    }, { passive: false, capture: true });
    
    // Completely override scroll event
    window.addEventListener('scroll', function(e) {
        if (chatOverlay.classList.contains('active') && !isChatPinned) {
            // If we're scrolling the window while chat is open, prevent it
            e.preventDefault();
            e.stopPropagation();
            window.scrollTo(0, scrollPosition);
            return false;
        }
    }, { passive: false, capture: true });

    // Prevent keyboard scroll events
    document.addEventListener('keydown', function(e) {
        if (chatOverlay.classList.contains('active') && !isChatPinned) {
            // Prevent space, page up/down, home, end, up/down arrow keys
            const scrollKeys = [32, 33, 34, 35, 36, 38, 40];
            if (scrollKeys.includes(e.keyCode)) {
                // Allow if focus is in an input element within the chat
                const isInputFocused = chatOverlay.contains(document.activeElement) && 
                    (document.activeElement.tagName === 'INPUT' || 
                     document.activeElement.tagName === 'TEXTAREA');
                
                if (!isInputFocused) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }
        }
    }, { passive: false, capture: true });
    
    // Add resize handle
    const resizeHandle = document.createElement('div');
    resizeHandle.className = 'resize-handle';
    chatOverlay.appendChild(resizeHandle);
    
    let isResizing = false;
    let originalWidth, originalHeight, originalX, originalY;
    
    resizeHandle.addEventListener('mousedown', function(e) {
        isResizing = true;
        originalWidth = chatOverlay.offsetWidth;
        originalHeight = chatOverlay.offsetHeight;
        originalX = e.clientX;
        originalY = e.clientY;
        e.preventDefault();
        e.stopPropagation();
    });
    
    document.addEventListener('mousemove', function(e) {
        if (!isResizing) return;
        
        const width = originalWidth + (e.clientX - originalX);
        const height = originalHeight + (e.clientY - originalY);
        
        if (width >= 400) chatOverlay.style.width = width + 'px';
        if (height >= 300) chatOverlay.style.height = height + 'px';
        
        e.preventDefault();
        e.stopPropagation();
    });
    
    document.addEventListener('mouseup', function(e) {
        if (isResizing) {
            isResizing = false;
            e.preventDefault();
            e.stopPropagation();
        }
    });
    
    // Allow backdrop to close chat when clicked
    backdrop.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // If chat is pinned, don't close on backdrop click
        if (isChatPinned) return;
        
        // Close the chat overlay
        chatOverlay.classList.remove('active');
        
        // Re-enable scrolling
        enableScroll();
        
        // Stop polling
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
        
        // Update notification badge immediately
        loadUnreadMessageCount();
    });
    
    // Isolate scroll events in scrollable containers
    const scrollableContainers = [
        messagesContainer, 
        document.querySelector('.groups-list'),
        document.querySelector('.members-list')
    ];
    
    scrollableContainers.forEach(container => {
        if (container) {
            container.addEventListener('wheel', function(e) {
                // Stop event from bubbling up to document
                e.stopPropagation();
            }, true);
            
            container.addEventListener('scroll', function(e) {
                // Stop event from bubbling up to document
                e.stopPropagation();
            }, true);
        }
    });
    
    // Chat button in the navbar
    const chatButton = document.getElementById('navChatButton');
    if (chatButton) {
        chatButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Reset display style first to ensure it's visible
            chatOverlay.style.display = '';
            chatOverlay.classList.add('active');
            
            // Load chat groups
            loadChatGroups();
            
            // Preload faculty data for better performance
            preloadFacultyData();
            
            // Update notification badge immediately
            loadUnreadMessageCount();
            
            // Apply ripple effects to buttons
            setTimeout(addRippleEffect, 300);
            
            // Check and apply saved chat state
            applyChatState();
            
            // If in pinned mode, update the visibility state
            if (isChatPinned) {
                localStorage.setItem('chatVisible', 'true');
                document.body.classList.add('chat-pinned');
            } else {
                disableScroll();
            }
        });
    }
    
    // Apply saved chat state
    function applyChatState() {
        // Fix any collapsed button alignment issues
        fixCollapseButtonAlignment();
        
        // Apply pinned state if needed
        if (isChatPinned) {
            chatOverlay.classList.add('pinned');
            document.body.classList.add('chat-pinned');
            
            // Update pin icon if exists
            const pinIcon = document.querySelector('#pinChat i');
            if (pinIcon) {
                pinIcon.classList.remove('bi-pin');
                pinIcon.classList.add('bi-pin-fill');
                document.getElementById('pinChat').setAttribute('title', 'Unpin chat');
            }
            
            // Find navbar and ensure it's properly positioned
            const navbars = document.querySelectorAll('.navbar, .navbar-fixed-top, .fixed-top, .sticky-top');
            navbars.forEach(navbar => {
                navbar.style.position = 'fixed';
                navbar.style.top = '0';
                navbar.style.left = '380px';
                navbar.style.width = 'calc(100% - 380px)';
                navbar.style.zIndex = '1030';
                
                // Adjust width based on screen size
                if (window.innerWidth <= 1200) {
                    navbar.style.left = '340px';
                    navbar.style.width = 'calc(100% - 340px)';
                }
                if (window.innerWidth <= 991) {
                    navbar.style.left = '320px';
                    navbar.style.width = 'calc(100% - 320px)';
                }
                if (window.innerWidth <= 767) {
                    navbar.style.left = '0';
                    navbar.style.width = '100%';
                }
            });
            
            // Add groups toggle button for pinned view if it doesn't exist
            const chatGroupsPanel = document.getElementById('chatGroupsPanel');
            if (chatGroupsPanel && !document.querySelector('.groups-toggle')) {
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'groups-toggle';
                toggleBtn.innerHTML = '<i class="bi bi-chevron-up"></i>';
                toggleBtn.title = 'Toggle projects panel';
                
                // Add event listener
                toggleBtn.addEventListener('click', function() {
                    chatGroupsPanel.classList.toggle('collapsed');
                    
                    // Update icon
                    const icon = this.querySelector('i');
                    if (chatGroupsPanel.classList.contains('collapsed')) {
                        icon.classList.remove('bi-chevron-up');
                        icon.classList.add('bi-chevron-down');
                    } else {
                        icon.classList.remove('bi-chevron-down');
                        icon.classList.add('bi-chevron-up');
                    }
                });
                
                chatGroupsPanel.appendChild(toggleBtn);
            }
        }
    }
    
    // Pin chat button
    const pinButton = document.getElementById('pinChat');
    if (pinButton) {
        pinButton.addEventListener('click', function() {
            // Toggle pinned state
            isChatPinned = !isChatPinned;
            
            // Save state to localStorage
            saveChatState();
            
            // Update UI
            chatOverlay.classList.toggle('pinned', isChatPinned);
            
            // Update icon
            const pinIcon = this.querySelector('i');
            if (pinIcon) {
                if (isChatPinned) {
                    pinIcon.classList.remove('bi-pin');
                    pinIcon.classList.add('bi-pin-fill');
                    this.setAttribute('title', 'Unpin chat');
                    
                    // Add groups toggle button for pinned view if it doesn't exist
                    const chatGroupsPanel = document.getElementById('chatGroupsPanel');
                    if (chatGroupsPanel && !document.querySelector('.groups-toggle')) {
                        const toggleBtn = document.createElement('button');
                        toggleBtn.className = 'groups-toggle';
                        toggleBtn.innerHTML = '<i class="bi bi-chevron-up"></i>';
                        toggleBtn.title = 'Toggle projects panel';
                        
                        // Add event listener
                        toggleBtn.addEventListener('click', function() {
                            chatGroupsPanel.classList.toggle('collapsed');
                            
                            // Update icon
                            const icon = this.querySelector('i');
                            if (chatGroupsPanel.classList.contains('collapsed')) {
                                icon.classList.remove('bi-chevron-up');
                                icon.classList.add('bi-chevron-down');
                            } else {
                                icon.classList.remove('bi-chevron-down');
                                icon.classList.add('bi-chevron-up');
                            }
                        });
                        
                        chatGroupsPanel.appendChild(toggleBtn);
                    }
                } else {
                    pinIcon.classList.remove('bi-pin-fill');
                    pinIcon.classList.add('bi-pin');
                    this.setAttribute('title', 'Pin chat');
                    
                    // Remove groups toggle if it exists
                    const toggleBtn = document.querySelector('.groups-toggle');
                    if (toggleBtn) {
                        toggleBtn.remove();
                    }
                    
                    // Ensure chat groups panel is not collapsed
                    const chatGroupsPanel = document.getElementById('chatGroupsPanel');
                    if (chatGroupsPanel) {
                        chatGroupsPanel.classList.remove('collapsed');
                    }
                }
            }
            
            // Toggle backdrop and update body for pinned layout
            if (isChatPinned) {
                backdrop.classList.remove('active');
                
                // Allow page scrolling when pinned
                document.body.classList.remove('no-scroll');
                document.documentElement.classList.remove('no-scroll');
                document.body.style.top = '';
                window.scrollTo(0, scrollPosition);
                
                // Re-enable scroll snapping in index.php
                if (typeof window.allowFreeScroll !== 'undefined') {
                    window.allowFreeScroll = false;
                }
                
                // Re-enable scroll handlers in index.php
                if (typeof window.isScrolling !== 'undefined') {
                    window.isScrolling = false;
                }
                
                // Restore original event handlers
                if (originalWheel) {
                    window.onwheel = originalWheel;
                }
                if (originalScroll) {
                    window.onscroll = originalScroll;
                }
                
                // Add chat-pinned class to body for layout adjustment
                document.body.classList.add('chat-pinned');
                
                // Fix navbar positioning when pinned
                const navbars = document.querySelectorAll('.navbar, .navbar-fixed-top, .fixed-top, .sticky-top');
                navbars.forEach(navbar => {
                    navbar.style.position = 'fixed';
                    navbar.style.top = '0';
                    navbar.style.left = '380px';
                    navbar.style.width = 'calc(100% - 380px)';
                    navbar.style.zIndex = '1030';
                    
                    // Adjust width based on screen size
                    if (window.innerWidth <= 1200) {
                        navbar.style.left = '340px';
                        navbar.style.width = 'calc(100% - 340px)';
                    }
                    if (window.innerWidth <= 991) {
                        navbar.style.left = '320px';
                        navbar.style.width = 'calc(100% - 320px)';
                    }
                    if (window.innerWidth <= 767) {
                        navbar.style.left = '0';
                        navbar.style.width = '100%';
                    }
                });
                
                // Remove event blocking
                window.removeEventListener('scroll', preventScroll, { capture: true });
            } else {
                backdrop.classList.add('active');
                disableScroll();
                
                // Remove chat-pinned class from body
                document.body.classList.remove('chat-pinned');
            }
            
            // Refresh layout
            setTimeout(() => {
                window.dispatchEvent(new Event('resize'));
                
                // Scroll messages to bottom
                if (messagesContainer) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            }, 300);
        });
    }
    
    // Close chat overlay
    if (closeChat) {
        closeChat.addEventListener('click', function() {
            // Force hide the chat overlay regardless of pinned state
            chatOverlay.classList.remove('active');
            chatOverlay.style.display = 'none';
            
            // Handle closing in pinned mode
            if (isChatPinned) {
                // Remove chat-pinned class from body
                document.body.classList.remove('chat-pinned');
                
                // Update localStorage to remember the chat is closed
                localStorage.setItem('chatVisible', 'false');
                
                // Reset any fixed elements that may have been adjusted for pinned mode
                const fixedElements = document.querySelectorAll('.navbar, .navbar-fixed-top, .sticky-top, .fixed-top, header, nav, #header');
                fixedElements.forEach(el => {
                    // Skip elements that are children of the chat overlay
                    if (el.closest('.project-chat-overlay')) return;
                    
                    // Reset all positioning and styles
                    el.style.position = '';
                    el.style.left = '';
                    el.style.width = '';
                    el.style.top = '';
                    el.style.right = '';
                    el.style.transform = '';
                    el.style.margin = '';
                    el.style.zIndex = '';
                });
                
                // Force reset the body padding and styles
                document.body.style.paddingLeft = '';
                document.body.style.width = '';
                document.body.style.overflow = '';
                document.body.style.position = '';
                
                // Force a layout recalculation
                setTimeout(() => {
                    window.dispatchEvent(new Event('resize'));
                }, 100);
            } else {
                // Re-enable scrolling for non-pinned mode
                enableScroll();
            }
            
            // Stop polling
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
            
            // Update notification badge immediately
            loadUnreadMessageCount();
        });
    }
    
    // Check for pinned state on page load and apply immediately if needed
    if (isChatPinned && chatOverlay) {
        // If we have a visible chat that should be pinned, show it right away
        if (localStorage.getItem('chatVisible') === 'true') {
            chatOverlay.classList.add('active');
            applyChatState();
            
            // Load chat content
            setTimeout(() => {
                loadChatGroups();
                preloadFacultyData();
            }, 500);
        }
    }
    
    // Before unload, save visibility state if pinned
    window.addEventListener('beforeunload', function() {
        if (isChatPinned && chatOverlay) {
            localStorage.setItem('chatVisible', chatOverlay.classList.contains('active'));
        }
    });
    
    // Message input handling
    const chatMessageInput = document.getElementById('chatMessageInput');
    const sendMessageBtn = document.getElementById('sendMessageBtn');
    const sendingIndicator = document.getElementById('sendingIndicator');
    const fileInput = document.getElementById('chatFileInput');
    const filePreviewContainer = document.getElementById('filePreviewContainer');
    const filePreviewIcon = document.querySelector('.file-preview-icon');
    const fileName = document.querySelector('.file-preview-info .file-name');
    const fileSize = document.querySelector('.file-preview-info .file-size');
    const removeFileBtn = document.getElementById('removeFileBtn');
    
    // Selected file reference
    let selectedFile = null;
    
    // File input change handler
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files.length > 0) {
                selectedFile = this.files[0];
                
                // Show file preview
                if (filePreviewContainer) {
                    filePreviewContainer.classList.add('active');
                    
                    // Update file info
                    if (fileName) {
                        fileName.textContent = selectedFile.name;
                    }
                    
                    if (fileSize) {
                        // Format file size
                        let formattedSize = '';
                        if (selectedFile.size < 1024) {
                            formattedSize = selectedFile.size + ' B';
                        } else if (selectedFile.size < 1024 * 1024) {
                            formattedSize = Math.round(selectedFile.size / 1024 * 10) / 10 + ' KB';
                        } else {
                            formattedSize = Math.round(selectedFile.size / (1024 * 1024) * 10) / 10 + ' MB';
                        }
                        
                        fileSize.textContent = formattedSize;
                    }
                    
                    // Update icon based on file type
                    if (filePreviewIcon) {
                        let iconClass = 'bi-file-earmark';
                        
                        // Check file type/extension
                        const fileType = selectedFile.type;
                        const fileName = selectedFile.name;
                        const extension = fileName.split('.').pop().toLowerCase();
                        
                        // Document types
                        if (fileType.includes('pdf')) {
                            iconClass = 'bi-file-earmark-pdf';
                        } else if (fileType.includes('word') || ['doc', 'docx'].includes(extension)) {
                            iconClass = 'bi-file-earmark-word';
                        } else if (fileType.includes('sheet') || ['xls', 'xlsx'].includes(extension)) {
                            iconClass = 'bi-file-earmark-excel';
                        } else if (fileType.includes('presentation') || ['ppt', 'pptx'].includes(extension)) {
                            iconClass = 'bi-file-earmark-slides';
                        } else if (fileType.includes('text')) {
                            iconClass = 'bi-file-earmark-text';
                        } else if (fileType.includes('image')) {
                            iconClass = 'bi-image';
                            
                            // For images, show a preview
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                filePreviewIcon.innerHTML = `<img src="${e.target.result}" alt="${selectedFile.name}">`;
                            };
                            reader.readAsDataURL(selectedFile);
                            
                            // Skip setting icon class since we've set HTML content
                            iconClass = null;
                        } else if (fileType.includes('video')) {
                            iconClass = 'bi-film';
                        } else if (fileType.includes('audio')) {
                            iconClass = 'bi-file-earmark-music';
                        } else if (['zip', 'rar', '7z', 'tar', 'gz'].includes(extension)) {
                            iconClass = 'bi-file-earmark-zip';
                        }
                        
                        // Update icon if we haven't replaced the HTML content
                        if (iconClass) {
                            filePreviewIcon.innerHTML = `<i class="${iconClass}"></i>`;
                        }
                    }
                }
            }
        });
    }
    
    // Remove file button click handler
    if (removeFileBtn) {
        removeFileBtn.addEventListener('click', function() {
            // Clear selected file
            selectedFile = null;
            
            // Reset file input
            if (fileInput) {
                fileInput.value = '';
            }
            
            // Hide file preview
            if (filePreviewContainer) {
                filePreviewContainer.classList.remove('active');
            }
            
            // Reset icon
            if (filePreviewIcon) {
                filePreviewIcon.innerHTML = '<i class="bi bi-file-earmark"></i>';
            }
        });
    }
    
    // Send message function
    function sendMessage() {
        if (!currentProjectId) {
            return;
        }
        
        // Check if we have a message or a file
        if (!chatMessageInput.value.trim() && !selectedFile) {
            return;
        }
        
        // Show sending indicator
        sendingIndicator.classList.add('active');
        
        // Get message text
        const messageText = chatMessageInput.value.trim();
        
        // Clear input
        chatMessageInput.value = '';
        
        // Create form data
        const formData = new FormData();
        formData.append('projectId', currentProjectId);
        formData.append('message', messageText);
        
        // Determine endpoint based on whether we have a file
        let endpoint = 'src/model/send_chat_message.php';
        
        // If we have a file, add it and change endpoint
        if (selectedFile) {
            formData.append('file', selectedFile);
            endpoint = 'src/model/upload_chat_file.php';
            
            // Clear selected file
            selectedFile = null;
            
            // Reset file input
            if (fileInput) {
                fileInput.value = '';
            }
            
            // Hide file preview
            if (filePreviewContainer) {
                filePreviewContainer.classList.remove('active');
            }
            
            // Reset icon
            if (filePreviewIcon) {
                filePreviewIcon.innerHTML = '<i class="bi bi-file-earmark"></i>';
            }
        }
        
        // Send message to server
        fetch(endpoint, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Hide sending indicator
            sendingIndicator.classList.remove('active');
            
            if (data.success) {
                // Instead of reloading all messages, just fetch the new message
                fetchSingleMessage(data.messageId, currentProjectId);
                
                // Mark this project's messages as read
                updateReadTimestamp(currentProjectId);
                
                // Update notification count across all projects
                loadUnreadMessageCount();
            } else {
                console.error('Failed to send message:', data.message);
                // Show error toast if available
                if (typeof showToast === 'function') {
                    showToast('error', 'Failed to send message', data.message);
                }
            }
        })
        .catch(error => {
            // Hide sending indicator
            sendingIndicator.classList.remove('active');
            console.error('Error sending message:', error);
        });
    }
    
    // Send button click
    if (sendMessageBtn) {
        sendMessageBtn.addEventListener('click', sendMessage);
    }
    
    // Enter key press in input
    if (chatMessageInput) {
        chatMessageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }
    
    // Toggle left panel
    if (minimizeLeftPanel) {
        minimizeLeftPanel.addEventListener('click', function() {
            const panel = document.getElementById('chatGroupsPanel');
            panel.classList.toggle('collapsed');
            
            // Update icon if needed
            const collapseProjectsBtn = document.getElementById('collapseProjects');
            if (collapseProjectsBtn) {
                const icon = collapseProjectsBtn.querySelector('i');
                if (panel.classList.contains('collapsed')) {
                    icon.classList.remove('bi-chevron-left');
                    icon.classList.add('bi-chevron-right');
                } else {
                    icon.classList.remove('bi-chevron-right');
                    icon.classList.add('bi-chevron-left');
                }
            }
        });
    }
    
    // Toggle right panel
    if (minimizeRightPanel) {
        minimizeRightPanel.addEventListener('click', function() {
            const panel = document.getElementById('groupMembersPanel');
            panel.classList.toggle('collapsed');
            
            // Update icon if needed
            const collapseMembersBtn = document.getElementById('collapseMembers');
            if (collapseMembersBtn) {
                const icon = collapseMembersBtn.querySelector('i');
                if (panel.classList.contains('collapsed')) {
                    icon.classList.remove('bi-chevron-right');
                    icon.classList.add('bi-chevron-left');
                } else {
                    icon.classList.remove('bi-chevron-left');
                    icon.classList.add('bi-chevron-right');
                }
            }
        });
    }
    
    // Collapsible panels functionality
    const collapseProjects = document.getElementById('collapseProjects');
    const collapseMembers = document.getElementById('collapseMembers');
    
    if (collapseProjects) {
        collapseProjects.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const panel = document.getElementById('chatGroupsPanel');
            panel.classList.toggle('collapsed');
            
            // Call alignment fix function with multiple attempts to ensure it works
            setTimeout(fixCollapseButtonAlignment, 0);
            setTimeout(fixCollapseButtonAlignment, 10);
            setTimeout(fixCollapseButtonAlignment, 50);
            setTimeout(fixCollapseButtonAlignment, 100);
        });
    }
    
    if (collapseMembers) {
        collapseMembers.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const panel = document.getElementById('groupMembersPanel');
            panel.classList.toggle('collapsed');
            
            // Call alignment fix function with multiple attempts to ensure it works
            setTimeout(fixCollapseButtonAlignment, 0);
            setTimeout(fixCollapseButtonAlignment, 10);
            setTimeout(fixCollapseButtonAlignment, 50);
            setTimeout(fixCollapseButtonAlignment, 100);
        });
    }
    
    // Mobile panel toggling
    if (window.innerWidth <= 991) {
        if (minimizeLeftPanel) {
            minimizeLeftPanel.addEventListener('click', function() {
                leftPanel.classList.toggle('active');
            });
        }
        
        if (minimizeRightPanel) {
            minimizeRightPanel.addEventListener('click', function() {
                rightPanel.classList.toggle('active');
            });
        }
    }
    
    // Adjust panels on window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 991) {
            if (leftPanel) leftPanel.classList.remove('minimized');
            if (rightPanel) rightPanel.classList.remove('minimized');
            if (leftPanel) leftPanel.classList.remove('active');
            if (rightPanel) rightPanel.classList.remove('active');
        }
        
        // Fix any collapsed button alignment issues on resize
        setTimeout(fixCollapseButtonAlignment, 100);
        
        // Update navbar positioning if chat is pinned
        if (isChatPinned && document.body.classList.contains('chat-pinned')) {
            const navbars = document.querySelectorAll('.navbar, .navbar-fixed-top, .fixed-top, .sticky-top');
            navbars.forEach(navbar => {
                navbar.style.position = 'fixed';
                navbar.style.top = '0';
                navbar.style.zIndex = '1030';
                
                // Adjust width based on screen size
                if (window.innerWidth <= 767) {
                    navbar.style.left = '0';
                    navbar.style.width = '100%';
                } else if (window.innerWidth <= 991) {
                    navbar.style.left = '320px';
                    navbar.style.width = 'calc(100% - 320px)';
                } else if (window.innerWidth <= 1200) {
                    navbar.style.left = '340px';
                    navbar.style.width = 'calc(100% - 340px)';
                } else {
                    navbar.style.left = '380px';
                    navbar.style.width = 'calc(100% - 380px)';
                }
            });
        }
    });
    
    // Function to load chat groups
    function loadChatGroups() {
        // Show loading spinner
        if (chatGroupsLoading) {
            chatGroupsLoading.classList.add('active');
        }
        
        fetch('src/model/fetch_chat_groups.php')
            .then(response => response.json())
            .then(data => {
                // Hide loading spinner
                if (chatGroupsLoading) {
                    chatGroupsLoading.classList.remove('active');
                }
                
                if (data.success && data.chatGroups && data.chatGroups.length > 0) {
                    displayChatGroups(data.chatGroups);
                    
                    // Load members for the first group by default
                    const firstGroupId = data.chatGroups[0].id.$oid || data.chatGroups[0].id;
                    loadGroupMembers(firstGroupId);
                } else {
                    displayEmptyChatGroups();
                    console.log('No chat groups found or error:', data.message);
                }
            })
            .catch(error => {
                // Hide loading spinner
                if (chatGroupsLoading) {
                    chatGroupsLoading.classList.remove('active');
                }
                
                displayEmptyChatGroups();
                console.error('Error fetching chat groups:', error);
            });
    }
    
    // Function to display chat groups
    function displayChatGroups(groups) {
        if (!projectChatGroups) return;
        
        projectChatGroups.innerHTML = '';
        
        groups.forEach((group, index) => {
            const groupEl = document.createElement('div');
            groupEl.className = `group-item ${index === 0 ? 'active' : ''}`;
            const groupId = group.id.$oid || group.id;
            groupEl.setAttribute('data-group-id', groupId);
            
            // Group avatar (use project image if available)
            let avatarContent = '';
            if (group.imageUrl) {
                avatarContent = `<img src="${group.imageUrl}" alt="${group.name}" onerror="this.onerror=null;this.src='assets/resources/project_avatar.png';">`;
            } else {
                avatarContent = `<i class="bi bi-chat-square-dots"></i>`;
            }
            
            groupEl.innerHTML = `
                <div class="group-avatar">
                    ${avatarContent}
                </div>
                <div class="group-info">
                    <div class="group-name">${group.name}</div>
                    <div class="group-last-msg">${group.lastMessage || 'No messages yet'}</div>
                </div>
                ${group.lastTime ? `<div class="group-last-time">${group.lastTime}</div>` : ''}
            `;
            
            groupEl.addEventListener('click', function() {
                // Remove active class from all groups
                document.querySelectorAll('.group-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                // Add active class to this group
                this.classList.add('active');
                
                // Get the group ID
                const groupId = this.getAttribute('data-group-id');
                
                // Set current project
                currentProjectId = groupId;
                currentProjectName = group.name;
                
                // Reset loading state
                isFirstLoad = true;
                lastMessageTimestamp = null;
                
                // Stop existing polling if any
                if (pollingInterval) {
                    clearInterval(pollingInterval);
                }
                
                // Update chat title
                const chatTitle = document.querySelector('.chat-title');
                if (chatTitle) {
                    chatTitle.textContent = group.name || 'Project Chat';
                }
                
                // Load chat messages for this group
                loadChatMessages(groupId);
                
                // Load members for this group
                loadGroupMembers(groupId);
                
                // Start polling for new messages
                pollingInterval = setInterval(() => {
                    if (currentProjectId === groupId) {
                        loadChatMessages(groupId, true);
                    }
                }, 10000); // Poll every 10 seconds
            });
            
            projectChatGroups.appendChild(groupEl);
            
            // If this is the first group and we don't have a current project, set it
            if (index === 0 && !currentProjectId) {
                currentProjectId = groupId;
                currentProjectName = group.name;
                
                // Update chat title
                const chatTitle = document.querySelector('.chat-title');
                if (chatTitle) {
                    chatTitle.textContent = group.name || 'Project Chat';
                }
                
                // Load messages for this group
                loadChatMessages(groupId);
                
                // Mark messages as read
                updateReadTimestamp(groupId);
                
                // Start polling for new messages with a higher frequency
                pollingInterval = setInterval(() => {
                    if (currentProjectId === groupId) {
                        loadChatMessages(groupId, true);
                        updateReadTimestamp(groupId);
                    }
                }, 3000); // Poll every 3 seconds for better real-time experience
            }
        });
    }
    
    // Function to load group members (supervisor and team members)
    function loadGroupMembers(projectId) {
        // Show loading spinner
        const membersPanelLoading = document.getElementById('membersPanelLoading');
        if (membersPanelLoading) {
            membersPanelLoading.classList.add('active');
        }
        
        // Get supervisor and team members lists
        const supervisorsList = document.getElementById('supervisorsList');
        const teamMembersList = document.getElementById('teamMembersList');
        const teamMemberCount = document.getElementById('teamMemberCount');
        
        if (!supervisorsList || !teamMembersList) return;
        
        // Clear previous members
        supervisorsList.innerHTML = '';
        teamMembersList.innerHTML = '';
        
        // Fetch project details to get supervisor and team members
        fetch(`src/model/get_project.php?id=${projectId}`)
            .then(response => response.json())
            .then(data => {
                // Hide loading spinner
                if (membersPanelLoading) {
                    membersPanelLoading.classList.remove('active');
                }
                
                if (data && data.project) {
                    const project = data.project;
                    
                    // Display supervisor
                    if (project.supervisor && project.supervisor.name) {
                        // Get supervisor data with profile image
                        getUserData(project.supervisor.userId, 'faculty')
                            .then(supervisorData => {
                                const profileImage = supervisorData?.profile_image || 
                                                    supervisorData?.profile_image_url || 
                                                    'assets/resources/user_avatar.png';
                                
                                const supervisorId = project.supervisor.userId.$oid || project.supervisor.userId;
                                
                                supervisorsList.innerHTML = `
                                    <a href="Faculty_Profile.php?id=${supervisorId}" class="member-item-link">
                                        <div class="member-item online">
                                            <div class="member-avatar">
                                                <img src="${profileImage}" alt="${project.supervisor.name}" 
                                                     onerror="this.onerror=null;this.src='assets/resources/user_avatar.png';">
                                                <span class="status-indicator"></span>
                                            </div>
                                            <div class="member-info">
                                                <div class="member-name">${project.supervisor.name}</div>
                                                <div class="member-role">${project.supervisor.role || 'Supervisor'}</div>
                                            </div>
                                        </div>
                                    </a>
                                `;
                            })
                            .catch(() => {
                                // Fallback if data fetch fails
                                const supervisorId = project.supervisor.userId.$oid || project.supervisor.userId;
                                
                                supervisorsList.innerHTML = `
                                    <a href="Faculty_Profile.php?id=${supervisorId}" class="member-item-link">
                                        <div class="member-item online">
                                            <div class="member-avatar">
                                                <img src="assets/resources/user_avatar.png" alt="${project.supervisor.name}">
                                                <span class="status-indicator"></span>
                                            </div>
                                            <div class="member-info">
                                                <div class="member-name">${project.supervisor.name}</div>
                                                <div class="member-role">${project.supervisor.role || 'Supervisor'}</div>
                                            </div>
                                        </div>
                                    </a>
                                `;
                            });
                    } else {
                        supervisorsList.innerHTML = `
                            <div class="empty-members-placeholder">
                                <i class="bi bi-person-badge"></i>
                                <p>No supervisor assigned</p>
                            </div>
                        `;
                    }
                    
                    // Display team members
                    if (project.members && project.members.length > 0) {
                        teamMembersList.innerHTML = '';
                        
                        // Update team member count
                        if (teamMemberCount) {
                            teamMemberCount.textContent = project.members.length;
                        }
                        
                        // Process each member and fetch their data
                        project.members.forEach(member => {
                            // Skip if no name
                            if (!member.name) return;
                            
                            const memberUserId = member.userId?.$oid || member.userId || '';
                            
                            // Temporary display while fetching data
                            const tempMemberEl = document.createElement('div');
                            tempMemberEl.className = 'member-item-link-wrapper';
                            tempMemberEl.setAttribute('data-user-id', memberUserId);
                            
                            let memberHTML = '';
                            if (memberUserId) {
                                memberHTML = `
                                    <a href="Student_Profile.php?id=${memberUserId}" class="member-item-link">
                                        <div class="member-item">
                                            <div class="member-avatar">
                                                <img src="assets/resources/user_avatar.png" alt="${member.name}">
                                                <span class="status-indicator"></span>
                                            </div>
                                            <div class="member-info">
                                                <div class="member-name">${member.name}</div>
                                                <div class="member-role">${member.role || 'Team Member'}</div>
                                            </div>
                                        </div>
                                    </a>
                                `;
                            } else {
                                memberHTML = `
                                    <div class="member-item">
                                        <div class="member-avatar">
                                            <img src="assets/resources/user_avatar.png" alt="${member.name}">
                                            <span class="status-indicator"></span>
                                        </div>
                                        <div class="member-info">
                                            <div class="member-name">${member.name}</div>
                                            <div class="member-role">${member.role || 'Team Member'}</div>
                                        </div>
                                    </div>
                                `;
                            }
                            
                            tempMemberEl.innerHTML = memberHTML;
                            teamMembersList.appendChild(tempMemberEl);
                            
                            // Try to get user data and update the element
                            if (member.userId) {
                                console.log(`Fetching data for member: ${member.name}, ID: ${member.userId.$oid || member.userId}`);
                                getUserData(member.userId, 'student')
                                    .then(userData => {
                                        if (userData) {
                                            console.log(`Got user data for: ${member.name}`, userData);
                                            const userIdStr = member.userId.$oid || member.userId;
                                            const userElement = teamMembersList.querySelector(`[data-user-id="${userIdStr}"]`);
                                            if (userElement) {
                                                console.log(`Found element for user: ${member.name}`);
                                                // Update profile image if available
                                                const profileImage = userData.profile_image || 
                                                                    userData.profile_image_url || 
                                                                    userData.basic_info?.profile_image_url ||
                                                                    'assets/resources/user_avatar.png';
                                                
                                                console.log(`Profile image path for ${member.name}: ${profileImage}`);
                                                
                                                const imgElement = userElement.querySelector('.member-avatar img');
                                                if (imgElement) {
                                                    imgElement.src = profileImage;
                                                    imgElement.onerror = function() {
                                                        console.log(`Error loading image for ${member.name}, using default`);
                                                        this.onerror = null;
                                                        this.src = 'assets/resources/user_avatar.png';
                                                    };
                                                }
                                                
                                                // Randomly assign online status for demo
                                                const isOnline = Math.random() < 0.3;
                                                if (isOnline) {
                                                    const memberItem = userElement.querySelector('.member-item');
                                                    if (memberItem) {
                                                        memberItem.classList.add('online');
                                                    }
                                                }
                                            } else {
                                                console.error(`Couldn't find element for user ID: ${userIdStr}`);
                                            }
                                        } else {
                                            console.log(`No user data found for: ${member.name}`);
                                        }
                                    })
                                    .catch(error => {
                                        console.error(`Error fetching data for ${member.name}:`, error);
                                    });
                            }
                        });
                    } else {
                        teamMembersList.innerHTML = `
                            <div class="empty-members-placeholder">
                                <i class="bi bi-people"></i>
                                <p>No team members</p>
                            </div>
                        `;
                        
                        // Update team member count to 0
                        if (teamMemberCount) {
                            teamMemberCount.textContent = '0';
                        }
                    }
                } else {
                    // Display empty states
                    supervisorsList.innerHTML = `
                        <div class="empty-members-placeholder">
                            <i class="bi bi-person-badge"></i>
                            <p>No supervisor assigned</p>
                        </div>
                    `;
                    
                    teamMembersList.innerHTML = `
                        <div class="empty-members-placeholder">
                            <i class="bi bi-people"></i>
                            <p>No team members</p>
                        </div>
                    `;
                    
                    // Update team member count to 0
                    if (teamMemberCount) {
                        teamMemberCount.textContent = '0';
                    }
                    
                    console.log('No project data found or error:', data?.message || 'Unknown error');
                }
            })
            .catch(error => {
                // Hide loading spinner
                if (membersPanelLoading) {
                    membersPanelLoading.classList.remove('active');
                }
                
                // Display empty states
                supervisorsList.innerHTML = `
                    <div class="empty-members-placeholder">
                        <i class="bi bi-person-badge"></i>
                        <p>Failed to load supervisor</p>
                    </div>
                `;
                
                teamMembersList.innerHTML = `
                    <div class="empty-members-placeholder">
                        <i class="bi bi-people"></i>
                        <p>Failed to load team members</p>
                    </div>
                `;
                
                console.error('Error fetching project members:', error);
            });
    }
    
    // Cache for user data to prevent redundant requests
    const userDataCache = {
        faculty: {},
        student: {}
    };
    
    // Function to get user data with caching
    async function getUserData(userId, userType = null) {
        if (!userId) return null;
        
        // Extract ObjectId if needed
        let userIdStr = userId;
        if (typeof userId === 'object' && userId.$oid) {
            userIdStr = userId.$oid;
        }
        
        // Check if we already have cached data for this user
        if (userType === 'faculty' && userDataCache.faculty[userIdStr]) {
            return userDataCache.faculty[userIdStr];
        }
        
        if (userType === 'student' && userDataCache.student[userIdStr]) {
            return userDataCache.student[userIdStr];
        }
        
        // If userType is not specified, check both caches
        if (!userType) {
            if (userDataCache.faculty[userIdStr]) {
                return userDataCache.faculty[userIdStr];
            }
            if (userDataCache.student[userIdStr]) {
                return userDataCache.student[userIdStr];
            }
        }
        
        // Try to fetch user data
        try {
            // First, check if we already have this type of data globally available
            if (userType === 'faculty' && window.facultyData) {
                const faculty = window.facultyData.find(f => 
                    (f._id && f._id.$oid === userIdStr) || (f._id === userIdStr)
                );
                if (faculty) {
                    userDataCache.faculty[userIdStr] = faculty;
                    return faculty;
                }
            }
            
            // Fetch user profile data
            let url, resultField;
            
            if (userType === 'faculty' || (!userType && !userDataCache.faculty[userIdStr])) {
                // Try faculty endpoint
                const response = await fetch(`src/model/get_faculty_data.php?id=${userIdStr}`);
                const data = await response.json();
                
                if (data && data.success && data.faculty) {
                    userDataCache.faculty[userIdStr] = data.faculty;
                    return data.faculty;
                }
            }
            
            if (userType === 'student' || (!userType && !userDataCache.student[userIdStr])) {
                // Try student endpoint
                const response = await fetch(`src/model/get_student_data.php?id=${userIdStr}`);
                const data = await response.json();
                
                if (data && data.success && data.student) {
                    userDataCache.student[userIdStr] = data.student;
                    return data.student;
                }
            }
            
            // If we get here, either both lookups failed or the specific one failed
            console.log(`User data not found for userId: ${userIdStr}, type: ${userType || 'any'}`);
            return null;
            
        } catch (error) {
            console.error('Error fetching user data:', error);
            return null;
        }
    }
    
    // Function to display empty state for chat groups
    function displayEmptyChatGroups() {
        if (!projectChatGroups) return;
        
        projectChatGroups.innerHTML = `
            <div class="empty-chat-groups">
                <i class="bi bi-chat-square-text"></i>
                <p>You don't have any project chat groups yet.<br>Join a project to start chatting.</p>
            </div>
        `;
    }

    // Handle clicking on members to open direct messages
    document.addEventListener('click', function(e) {
        // Find the closest member-item parent
        const memberItem = e.target.closest('.member-item');
        
        if (memberItem && memberItem.closest('.project-chat-overlay')) {
            // Don't execute this handler if the item is inside a link
            if (e.target.closest('.member-item-link')) {
                return;
            }
            
            // Get the member name
            const memberName = memberItem.querySelector('.member-name').textContent;
            
            // For now, just show a message - would implement direct messaging in the future
            console.log(`Open direct message with ${memberName}`);
            
            // Add visual feedback
            memberItem.classList.add('active');
            setTimeout(() => {
                memberItem.classList.remove('active');
            }, 300);
            
            // Future: Implement direct messaging feature
        }
    });

    // Handle leave project button click
    const leaveProjectBtn = document.getElementById('leaveProjectBtn');
    if (leaveProjectBtn) {
        leaveProjectBtn.addEventListener('click', function() {
            // Check if we have a current project selected
            if (!currentProjectId || !currentProjectName) {
                if (typeof showToast === 'function') {
                    showToast('error', 'Error', 'No project selected');
                }
                return;
            }
            
            // Show confirmation dialog
            const confirmMessage = `Are you sure you want to leave "${currentProjectName}"?\n\nThis will:\n• Remove you from the project team\n• Remove you from the group chat\n• You will lose access to all project discussions\n\nThis action cannot be undone.`;
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            // Disable the button to prevent multiple clicks
            this.disabled = true;
            const originalText = this.querySelector('span').textContent;
            this.querySelector('span').textContent = 'Leaving...';
            
            // Send leave request to server
            const formData = new FormData();
            formData.append('projectId', currentProjectId);
            
            fetch('src/model/leave_project.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    if (typeof showToast === 'function') {
                        showToast('success', 'Left Project', data.message || 'You have successfully left the project.');
                    } else {
                        alert('You have successfully left the project.');
                    }
                    
                    // Close the chat overlay
                    chatOverlay.classList.remove('active');
                    chatOverlay.style.display = 'none';
                    
                    // Handle closing in pinned mode
                    if (isChatPinned) {
                        document.body.classList.remove('chat-pinned');
                        localStorage.setItem('chatVisible', 'false');
                        
                        // Reset fixed elements
                        const fixedElements = document.querySelectorAll('.navbar, .navbar-fixed-top, .sticky-top, .fixed-top, header, nav, #header');
                        fixedElements.forEach(el => {
                            if (el.closest('.project-chat-overlay')) return;
                            el.style.position = '';
                            el.style.left = '';
                            el.style.width = '';
                            el.style.top = '';
                            el.style.right = '';
                            el.style.transform = '';
                            el.style.margin = '';
                            el.style.zIndex = '';
                        });
                        
                        document.body.style.paddingLeft = '';
                        document.body.style.width = '';
                        document.body.style.overflow = '';
                        document.body.style.position = '';
                        
                        setTimeout(() => {
                            window.dispatchEvent(new Event('resize'));
                        }, 100);
                    } else {
                        enableScroll();
                    }
                    
                    // Stop polling
                    if (pollingInterval) {
                        clearInterval(pollingInterval);
                        pollingInterval = null;
                    }
                    
                    // Reset current project variables
                    currentProjectId = null;
                    currentProjectName = null;
                    lastMessageTimestamp = null;
                    
                    // Update notification badge
                    loadUnreadMessageCount();
                    
                    // Refresh the page after a short delay to update the UI
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                    
                } else {
                    // Show error message
                    if (typeof showToast === 'function') {
                        showToast('error', 'Failed to Leave', data.message || 'Failed to leave the project. Please try again.');
                    } else {
                        alert(data.message || 'Failed to leave the project. Please try again.');
                    }
                    
                    // Re-enable the button
                    this.disabled = false;
                    this.querySelector('span').textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error leaving project:', error);
                
                // Show error message
                if (typeof showToast === 'function') {
                    showToast('error', 'Error', 'An error occurred while trying to leave the project. Please try again.');
                } else {
                    alert('An error occurred while trying to leave the project. Please try again.');
                }
                
                // Re-enable the button
                this.disabled = false;
                this.querySelector('span').textContent = originalText;
            });
        });
    }

    // Function to preload faculty data
    function preloadFacultyData() {
        // Check if we already have faculty data
        if (window.facultyData) return;
        
        console.log('Preloading faculty data...');
        fetch('src/model/load_faculty.php')
            .then(response => response.json())
            .then(data => {
                if (Array.isArray(data)) {
                    window.facultyData = data;
                    console.log(`Loaded ${data.length} faculty members`);
                    
                    // Cache faculty data by ID for quick lookup
                    data.forEach(faculty => {
                        if (faculty._id) {
                            const facultyId = faculty._id.$oid || faculty._id;
                            userDataCache.faculty[facultyId] = faculty;
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error preloading faculty data:', error);
            });
    }

    // Function to load chat messages
    function loadChatMessages(projectId, isPoll = false) {
        if (!projectId || !messagesContainer) return;
        
        // Don't show loading indicator for polling
        if (!isPoll) {
            // Show loading spinner
            const messageLoading = document.createElement('div');
            messageLoading.className = 'loading-spinner chat-spinner active text-center my-3';
            messageLoading.id = 'messageLoading';
            messageLoading.setAttribute('aria-label', 'Loading messages...');
            
            // Add simple text indicator
            const loadingText = document.createElement('div');
            loadingText.className = 'text-muted small mt-2';
            loadingText.textContent = 'Loading';
            loadingText.style.fontSize = '11px';
            loadingText.style.opacity = '0.7';
            messageLoading.appendChild(loadingText);
            
            // Only add loading indicator if it's the first load
            if (isFirstLoad) {
                messagesContainer.innerHTML = '';
                messagesContainer.appendChild(messageLoading);
            }
        }
        
        // Prepare query parameters
        let url = `src/model/load_chat_messages.php?projectId=${projectId}`;
        
        // If we have a last message timestamp and this is a poll, only get newer messages
        if (lastMessageTimestamp && isPoll) {
            url += `&after=${lastMessageTimestamp}`;
        }
        
        // Fetch messages
        fetch(url)
            .then(response => response.json())
            .then(data => {
                // Remove loading spinner if it exists
                const loadingEl = document.getElementById('messageLoading');
                if (loadingEl) {
                    loadingEl.remove();
                }
                
                if (data.success) {
                    // If this is a poll and we have new messages, append them
                    if (isPoll && data.messages.length > 0) {
                        appendMessages(data.messages);
                        // Update notification count when new messages are detected
                        loadUnreadMessageCount();
                        
                        // No highlighting for new messages - keep messages static
                    } 
                    // If this is the first load or not a poll, replace all messages
                    else if (!isPoll || isFirstLoad) {
                        displayMessages(data.messages);
                        isFirstLoad = false;
                    }
                    
                    // Update last message timestamp if we have messages
                    if (data.messages.length > 0) {
                        const lastMsg = data.messages[data.messages.length - 1];
                        lastMessageTimestamp = lastMsg.timestamp;
                    }
                    
                    // Focus the message input
                    if (!isPoll && chatMessageInput) {
                        chatMessageInput.focus();
                    }
                } else {
                    console.error('Failed to load messages:', data.message);
                    
                    // Show no messages placeholder if there's a problem
                    if (!isPoll && isFirstLoad) {
                        const noMessagesPlaceholder = document.getElementById('noMessagesPlaceholder');
                        if (noMessagesPlaceholder) {
                            noMessagesPlaceholder.style.display = 'flex';
                        }
                    }
                }
            })
            .catch(error => {
                // Remove loading spinner if it exists
                const loadingEl = document.getElementById('messageLoading');
                if (loadingEl) {
                    loadingEl.remove();
                }
                
                console.error('Error loading messages:', error);
            });
    }
    
    // Function to display messages
    function displayMessages(messages) {
        if (!messagesContainer) return;
        
        // Clear messages container
        messagesContainer.innerHTML = '';
        
        // If no messages, show placeholder
        if (!messages || messages.length === 0) {
            messagesContainer.innerHTML = `
                <div id="noMessagesPlaceholder" class="empty-messages-placeholder">
                    <i class="bi bi-chat-square-text"></i>
                    <p>No messages yet. Be the first to send a message!</p>
                </div>
            `;
            return;
        }
        
        // Hide no messages placeholder
        const noMessagesPlaceholder = document.getElementById('noMessagesPlaceholder');
        if (noMessagesPlaceholder) {
            noMessagesPlaceholder.style.display = 'none';
        }
        
        // Group messages by date
        let currentDate = null;
        
        messages.forEach(message => {
            // Format message timestamp to date
            const messageDate = new Date(message.timestamp * 1000).toLocaleDateString();
            
            // If this is a new date, add a date separator
            if (messageDate !== currentDate) {
                const dateSeparator = document.createElement('div');
                dateSeparator.className = 'date-separator';
                dateSeparator.textContent = messageDate;
                messagesContainer.appendChild(dateSeparator);
                currentDate = messageDate;
            }
            
            // Append message to container
            appendSingleMessage(message, messagesContainer);
        });
        
        // Smooth scroll to the bottom of the messages container
        smoothScrollToBottom(400);
    }
    
    // Smooth scroll function
    function smoothScrollToBottom(duration = 300) {
        if (!messagesContainer) return;
        
        const target = messagesContainer.scrollHeight;
        const start = messagesContainer.scrollTop;
        const distance = target - start;
        const startTime = performance.now();
        
        function scrollStep(timestamp) {
            const currentTime = timestamp - startTime;
            if (currentTime < duration) {
                const progress = Math.min(currentTime / duration, 1);
                // Easing function for smooth deceleration
                const easeOut = 1 - Math.pow(1 - progress, 3);
                messagesContainer.scrollTop = start + distance * easeOut;
                requestAnimationFrame(scrollStep);
            } else {
                messagesContainer.scrollTop = target;
            }
        }
        
        requestAnimationFrame(scrollStep);
    }
    
    // Function to append new messages
    function appendMessages(messages) {
        if (!messagesContainer || !messages || messages.length === 0) return;
        
        // Hide no messages placeholder if it exists
        const noMessagesPlaceholder = document.getElementById('noMessagesPlaceholder');
        if (noMessagesPlaceholder) {
            noMessagesPlaceholder.style.display = 'none';
        }
        
        // Check if user is at the bottom before adding new messages
        const isAtBottom = messagesContainer.scrollTop + messagesContainer.clientHeight >= messagesContainer.scrollHeight - 100;
        
        // Get last date in the current message list
        let lastDateEl = messagesContainer.querySelector('.date-separator:last-of-type');
        let lastDate = lastDateEl ? lastDateEl.textContent : null;
        
        // Keep track of messages we're adding
        const newMessageIds = [];
        
        messages.forEach(message => {
            // Skip if message already exists
            if (document.querySelector(`[data-message-id="${message.id}"]`)) {
                return;
            }
            
            newMessageIds.push(message.id);
            
            // Format message timestamp to date
            const messageDate = new Date(message.timestamp * 1000).toLocaleDateString();
            
            // If this is a new date, add a date separator
            if (messageDate !== lastDate) {
                const dateSeparator = document.createElement('div');
                dateSeparator.className = 'date-separator';
                dateSeparator.textContent = messageDate;
                messagesContainer.appendChild(dateSeparator);
                lastDate = messageDate;
            }
            
            // Append message to container
            appendSingleMessage(message, messagesContainer);
        });
        
        // Show a "new messages" indicator if the user is not at the bottom and new messages were added
        if (!isAtBottom && newMessageIds.length > 0) {
            // Check if we already have an indicator
            let newMsgIndicator = document.getElementById('newMessagesIndicator');
            if (!newMsgIndicator) {
                newMsgIndicator = document.createElement('div');
                newMsgIndicator.id = 'newMessagesIndicator';
                newMsgIndicator.className = 'new-messages-indicator';
                newMsgIndicator.innerHTML = `
                    <i class="bi bi-arrow-down-circle-fill"></i>
                    <span>New messages</span>
                `;
                newMsgIndicator.addEventListener('click', function() {
                    smoothScrollToBottom(500);
                    this.classList.remove('visible');
                });
                document.querySelector('.chat-messages-panel').appendChild(newMsgIndicator);
            }
            
            // Make it visible
            newMsgIndicator.classList.add('visible');
            
            // Show the count of new messages
            const msgSpan = newMsgIndicator.querySelector('span');
            if (msgSpan) {
                msgSpan.textContent = `${newMessageIds.length} new message${newMessageIds.length > 1 ? 's' : ''}`;
            }
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                if (newMsgIndicator) {
                    newMsgIndicator.classList.remove('visible');
                }
            }, 5000);
        } else if (isAtBottom) {
            // If user was at bottom, smooth scroll to show new messages
            smoothScrollToBottom(400);
            
            // Hide the indicator if it exists
            const newMsgIndicator = document.getElementById('newMessagesIndicator');
            if (newMsgIndicator) {
                newMsgIndicator.classList.remove('visible');
            }
        }
    }
    
    // Function to convert markdown links to HTML links
    function convertMarkdownLinksToHTML(text) {
        if (!text) return text;
        
        // Convert markdown links [text](url) to HTML links
        const markdownLinkRegex = /\[([^\]]+)\]\(([^)]+)\)/g;
        return text.replace(markdownLinkRegex, '<a href="$2" target="_blank" rel="noopener noreferrer" class="system-message-link">$1</a>');
    }

    // Function to append a single message
    function appendSingleMessage(message, container) {
        // Create message element
        const messageEl = document.createElement('div');
        messageEl.className = `message-item ${message.isCurrentUser ? 'my-message' : 'other-message'}`;
        messageEl.setAttribute('data-message-id', message.id);
        
        // System message has different styling
        if (message.isSystem) {
            messageEl.className = 'system-message';
            
            // Convert markdown links to HTML links
            const processedMessage = convertMarkdownLinksToHTML(message.message);
            
            messageEl.innerHTML = `
                <div class="system-text">
                    ${processedMessage}
                </div>
            `;
            container.appendChild(messageEl);
            return;
        }
        
        // Regular message
        let html = '';
        
        // Only add avatar for other users' messages
        if (!message.isCurrentUser) {
            html += `
                <div class="message-avatar">
                    <img src="${message.profileImage}" alt="${message.sender.name}" 
                         onerror="this.onerror=null;this.src='assets/resources/user_avatar.png';">
                </div>
            `;
        }
        
        // Add message content
        html += `
            <div class="message-content">
                <div class="message-header">
                    <span class="message-sender">${message.isCurrentUser ? 'Me' : message.sender.name}</span>
                    <span class="message-time">${message.formattedTime}</span>
                </div>
                <div class="message-text">
                    ${convertMarkdownLinksToHTML(message.message)}
                </div>
            `;
        
        // Add attachment if exists
        if (message.attachment) {
            const attachment = message.attachment;
            
            // Build secure download URLs (supports both GridFS and old file system)
            let downloadUrl, viewUrl;
            
            if (attachment.gridfsId) {
                // New system: GridFS (cloud storage - works from anywhere!)
                downloadUrl = '/download.php?gridfs_id=' + encodeURIComponent(attachment.gridfsId);
                viewUrl = '/download.php?gridfs_id=' + encodeURIComponent(attachment.gridfsId) + '&inline=1';
            } else if (attachment.filePath) {
                // Old system: local file storage (backward compatibility)
                const filePath = attachment.filePath.replace(/^\//, '');
                downloadUrl = '/download.php?file=' + encodeURIComponent(filePath);
                viewUrl = '/download.php?file=' + encodeURIComponent(filePath) + '&inline=1';
            } else {
                // Fallback
                downloadUrl = '#';
                viewUrl = '#';
            }
            
            // Image attachment shows preview
            if (attachment.fileCategory === 'image') {
                html += `
                    <div class="image-attachment">
                        <a href="${viewUrl}" target="_blank" title="Click to view full size">
                            <img src="${viewUrl}" alt="${attachment.fileName}" 
                                 onerror="this.onerror=null;this.style.display='none';this.parentElement.innerHTML='<div style=\'padding:20px;text-align:center;color:#888;\'><i class=\'bi bi-image\' style=\'font-size:48px;\'></i><br><small>Image not available</small></div>';">
                        </a>
                        <div class="image-attachment-actions">
                            <a href="${downloadUrl}" class="btn btn-sm btn-primary" download="${attachment.fileName}" title="Download ${attachment.fileName}">
                                <i class="bi bi-download"></i> Download
                            </a>
                        </div>
                    </div>
                `;
            } else {
                // Other file types
                html += `
                    <div class="message-attachment">
                        <div class="attachment-icon">
                            <i class="${attachment.iconClass || 'bi-file-earmark'}"></i>
                        </div>
                        <div class="attachment-details">
                            <div class="attachment-name">${attachment.fileName}</div>
                            <div class="attachment-meta">
                                <span class="attachment-size">${attachment.formattedSize || '0 B'}</span>
                                <span class="attachment-type">${attachment.fileExtension.toUpperCase()}</span>
                            </div>
                        </div>
                        <a href="${downloadUrl}" class="attachment-download" download="${attachment.fileName}" title="Download ${attachment.fileName}">
                            <i class="bi bi-download"></i>
                            <span>Download</span>
                        </a>
                    </div>
                `;
            }
        }
        
        html += '</div>'; // Close message-content
        
        messageEl.innerHTML = html;
        container.appendChild(messageEl);
    }
});

// Encryption Settings Modal
const encryptionSettingsModal = document.createElement('div');
encryptionSettingsModal.id = 'encryptionSettingsModal';
encryptionSettingsModal.className = 'modal';
encryptionSettingsModal.style.display = 'none';
encryptionSettingsModal.innerHTML = `
    <div class="modal-content">
        <div class="modal-header">
            <h3>Encryption Settings</h3>
            <button class="close-modal" id="closeEncryptionModal">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="encryption-status-info">
                <div class="status-item">
                    <label>Encryption Status:</label>
                    <span id="encryptionStatusText">Checking...</span>
                </div>
                <div class="status-item">
                    <label>Key Generated:</label>
                    <span id="keyGeneratedText">-</span>
                </div>
                <div class="status-item">
                    <label>Last Rotation:</label>
                    <span id="lastRotationText">-</span>
                </div>
            </div>
            <div class="encryption-actions">
                <button id="enableEncryptionBtn" class="btn btn-primary" style="display: none;">
                    <i class="bi bi-shield-lock"></i> Enable Encryption
                </button>
                <button id="disableEncryptionBtn" class="btn btn-warning" style="display: none;">
                    <i class="bi bi-shield"></i> Disable Encryption
                </button>
                <button id="rotateKeyBtn" class="btn btn-secondary" style="display: none;">
                    <i class="bi bi-arrow-clockwise"></i> Rotate Key
                </button>
                <button id="migrateMessagesBtn" class="btn btn-info" style="display: none;">
                    <i class="bi bi-arrow-up-circle"></i> Migrate Messages
                </button>
            </div>
            <div class="encryption-warning">
                <i class="bi bi-exclamation-triangle"></i>
                <p>Only project creators and supervisors can manage encryption settings. Key rotation will make old messages unreadable without the previous key.</p>
            </div>
        </div>
    </div>
`;
document.body.appendChild(encryptionSettingsModal);

// Encryption functionality
let currentProjectEncryptionStatus = null;

// Check encryption status for current project
async function checkEncryptionStatus(projectId) {
    if (!projectId) return;
    
    try {
        const response = await fetch(`src/model/manage_project_encryption.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `projectId=${projectId}&action=status`
        });
        
        const data = await response.json();
        currentProjectEncryptionStatus = data;
        
        // Update UI
        updateEncryptionUI(data);
        
    } catch (error) {
        console.error('Failed to check encryption status:', error);
    }
}

// Update encryption UI elements
function updateEncryptionUI(status) {
    const encryptionStatus = document.getElementById('encryptionStatus');
    const encryptionIndicator = document.getElementById('encryptionIndicator');
    
    if (status.success && status.encryptionEnabled) {
        encryptionStatus.style.display = 'flex';
        encryptionIndicator.innerHTML = '<i class="bi bi-shield-lock"></i><span class="encryption-text">End-to-end encrypted</span>';
    } else {
        encryptionStatus.style.display = 'none';
    }
}

// Show encryption settings modal
function showEncryptionSettings() {
    if (!currentProjectId) return;
    
    const modal = document.getElementById('encryptionSettingsModal');
    const statusText = document.getElementById('encryptionStatusText');
    const keyGeneratedText = document.getElementById('keyGeneratedText');
    const lastRotationText = document.getElementById('lastRotationText');
    
    // Update status info
    if (currentProjectEncryptionStatus && currentProjectEncryptionStatus.success) {
        statusText.textContent = currentProjectEncryptionStatus.encryptionEnabled ? 'Enabled' : 'Disabled';
        keyGeneratedText.textContent = currentProjectEncryptionStatus.enabledAt || '-';
        lastRotationText.textContent = currentProjectEncryptionStatus.lastKeyRotation || '-';
        
        // Show/hide action buttons
        const enableBtn = document.getElementById('enableEncryptionBtn');
        const disableBtn = document.getElementById('disableEncryptionBtn');
        const rotateBtn = document.getElementById('rotateKeyBtn');
        const migrateBtn = document.getElementById('migrateMessagesBtn');
        
        if (currentProjectEncryptionStatus.encryptionEnabled) {
            disableBtn.style.display = 'inline-block';
            rotateBtn.style.display = 'inline-block';
            migrateBtn.style.display = 'inline-block';
            enableBtn.style.display = 'none';
        } else {
            enableBtn.style.display = 'inline-block';
            disableBtn.style.display = 'none';
            rotateBtn.style.display = 'none';
            migrateBtn.style.display = 'none';
        }
    } else {
        statusText.textContent = 'Error checking status';
    }
    
    modal.style.display = 'block';
}

// Hide encryption settings modal
function hideEncryptionSettings() {
    const modal = document.getElementById('encryptionSettingsModal');
    modal.style.display = 'none';
}

// Handle encryption actions
async function handleEncryptionAction(action) {
    if (!currentProjectId) return;
    
    try {
        const response = await fetch(`src/model/manage_project_encryption.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `projectId=${currentProjectId}&action=${action}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            // Refresh encryption status
            await checkEncryptionStatus(currentProjectId);
            // Refresh messages if encryption was enabled/disabled
            if (action === 'enable' || action === 'disable') {
                loadChatMessages(currentProjectId);
            }
        } else {
            alert('Error: ' + data.message);
        }
        
    } catch (error) {
        console.error('Failed to perform encryption action:', error);
        alert('Error performing action: ' + error.message);
    }
}

// Event listeners for encryption
document.addEventListener('DOMContentLoaded', function() {
    // Encryption settings button
    const encryptionSettingsBtn = document.getElementById('encryptionSettingsBtn');
    if (encryptionSettingsBtn) {
        encryptionSettingsBtn.addEventListener('click', showEncryptionSettings);
    }
    
    // Close modal button
    const closeEncryptionModal = document.getElementById('closeEncryptionModal');
    if (closeEncryptionModal) {
        closeEncryptionModal.addEventListener('click', hideEncryptionSettings);
    }
    
    // Encryption action buttons
    const enableEncryptionBtn = document.getElementById('enableEncryptionBtn');
    const disableEncryptionBtn = document.getElementById('disableEncryptionBtn');
    const rotateKeyBtn = document.getElementById('rotateKeyBtn');
    const migrateMessagesBtn = document.getElementById('migrateMessagesBtn');
    
    if (enableEncryptionBtn) {
        enableEncryptionBtn.addEventListener('click', () => handleEncryptionAction('enable'));
    }
    if (disableEncryptionBtn) {
        disableEncryptionBtn.addEventListener('click', () => handleEncryptionAction('disable'));
    }
    if (rotateKeyBtn) {
        rotateKeyBtn.addEventListener('click', () => handleEncryptionAction('rotate'));
    }
    if (migrateMessagesBtn) {
        migrateMessagesBtn.addEventListener('click', () => handleEncryptionAction('migrate'));
    }
    
    // Close modal when clicking outside
    const modal = document.getElementById('encryptionSettingsModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                hideEncryptionSettings();
            }
        });
    }
});

// Update the existing loadChatMessages function to check encryption status
const originalLoadChatMessages = loadChatMessages;
loadChatMessages = function(projectId, isPolling = false) {
    // Check encryption status when loading messages
    if (!isPolling) {
        checkEncryptionStatus(projectId);
    }
    
    // Call original function
    return originalLoadChatMessages(projectId, isPolling);
};
</script> 