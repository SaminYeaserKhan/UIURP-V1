# Admin System Documentation

## Overview

The UIURP Admin System provides comprehensive administrative capabilities for managing users, content, and system settings. The admin role is an addon on top of existing student and faculty roles, stored in a separate MongoDB collection.

## Features

### 🔐 Admin Role Management
- **Role Assignment**: Grant or revoke admin privileges to/from users
- **Role Hierarchy**: Support for different admin levels (admin, super_admin)
- **Role History**: Track admin role changes and assignments
- **User Search**: Find users by name, email, or ID for admin assignment

### 📊 Admin Dashboard
- **Overview**: System statistics and recent activity
- **User Management**: View and manage all system users
- **Content Management**: Manage projects, events, and other content
- **Admin Roles**: Manage admin users and their privileges
- **Settings**: System configuration and quick actions

### 🛡️ Security Features
- **Access Control**: Admin-only access to administrative functions
- **Session Management**: Secure admin session handling
- **Role Verification**: Middleware to verify admin privileges
- **Audit Trail**: Track admin actions and changes

## File Structure

```
src/
├── model/
│   └── admin_roles.php              # Admin role management model
├── controller/
│   ├── admin_controller.php         # Main admin controller
│   └── admin_api.php               # API endpoints for admin functions
├── includes/
│   └── admin_middleware.php        # Admin access control middleware
└── assets/
    ├── styles/
    │   └── admin-dashboard.css     # Admin dashboard styling
    └── js/
        └── admin-dashboard.js      # Admin dashboard functionality

admin_dashboard.php                  # Main admin dashboard page
init_admin.php                      # Initialize first admin user
```

## Database Collections

### admin_roles
Stores admin role assignments and history:
```json
{
  "_id": "ObjectId",
  "userId": "string",              // Reference to user ID
  "role": "string",                // admin, super_admin
  "isActive": "boolean",           // Whether role is currently active
  "grantedBy": "string",           // User ID who granted the role
  "grantedAt": "UTCDateTime",      // When role was granted
  "revokedBy": "string",           // User ID who revoked the role (optional)
  "revokedAt": "UTCDateTime",      // When role was revoked (optional)
  "createdAt": "UTCDateTime",      // Record creation time
  "updatedAt": "UTCDateTime"       // Last update time
}
```

## Installation & Setup

### 1. Initialize First Admin User

1. Navigate to `init_admin.php` in your browser
2. Select a user from the list (students or faculty)
3. Click "Grant Admin Privileges"
4. The selected user will become the first admin

### 2. Access Admin Dashboard

1. Login as an admin user
2. Click on your profile dropdown in the navbar
3. Select "Admin Dashboard" from the Administration section
4. You'll be redirected to the admin dashboard

## Usage Guide

### Admin Dashboard Navigation

The admin dashboard is organized into five main sections:

#### 1. Overview Tab
- **System Statistics**: Total users, projects, events, forum posts
- **Recent Activity**: Latest system activities and changes
- **Quick Actions**: Common administrative tasks

#### 2. User Management Tab
- **User Search**: Find users by name, email, or ID
- **User List**: View all system users with their details
- **User Actions**: Grant admin privileges to users

#### 3. Content Management Tab
- **Content Types**: Switch between projects and events
- **Content Search**: Find specific content items
- **Content Actions**: Delete content, toggle privacy settings

#### 4. Admin Roles Tab
- **Admin List**: View all current admin users
- **Add Admin**: Grant admin privileges to new users
- **Revoke Admin**: Remove admin privileges from users
- **Role Information**: View admin privileges and capabilities

#### 5. Settings Tab
- **System Information**: Version, database info, last update
- **Quick Actions**: Refresh statistics, export data

### Managing Admin Users

#### Granting Admin Privileges
1. Go to the "Admin Roles" tab
2. Click "Add Admin" button
3. Search for the user by name or email
4. Select the user from search results
5. Choose admin role level (admin or super_admin)
6. Click "Add Admin" to confirm

#### Revoking Admin Privileges
1. Go to the "Admin Roles" tab
2. Find the admin user in the list
3. Click "Revoke" button next to their name
4. Confirm the action in the popup

### Managing Content

#### Projects Management
1. Go to "Content Management" tab
2. Select "Projects" from the dropdown
3. Use search to find specific projects
4. Actions available:
   - **Toggle Privacy**: Switch between public/private
   - **Delete**: Remove project from system

#### Events Management
1. Go to "Content Management" tab
2. Select "Events" from the dropdown
3. Use search to find specific events
4. Actions available:
   - **Delete**: Remove event from system

## API Endpoints

### Admin Role Management
- `POST /src/controller/admin_api.php`
  - `action=grant_admin` - Grant admin role to user
  - `action=revoke_admin` - Revoke admin role from user
  - `action=get_admins` - Get list of all admin users
  - `action=search_users` - Search users for admin assignment
  - `action=get_admin_history` - Get admin role history for user

### Content Management
- `POST /src/controller/admin_api.php`
  - `action=get_content_list` - Get list of content (projects/events)
  - `action=delete_project` - Delete a project
  - `action=toggle_project_privacy` - Toggle project privacy
  - `action=delete_event` - Delete an event

### Statistics
- `POST /src/controller/admin_api.php`
  - `action=get_statistics` - Get system statistics
  - `action=get_recent_activity` - Get recent system activity

## Security Considerations

### Access Control
- All admin functions require valid admin session
- Admin middleware checks user privileges on each request
- Non-admin users are redirected away from admin pages

### Data Protection
- Admin actions are logged for audit purposes
- Sensitive operations require confirmation
- User data is properly escaped and validated

### Session Management
- Admin sessions follow same security as regular user sessions
- Session regeneration on login
- Proper logout handling

## Customization

### Adding New Admin Features
1. Add new methods to `AdminController` class
2. Create corresponding API endpoints in `admin_api.php`
3. Add UI components to admin dashboard
4. Update JavaScript handlers in `admin-dashboard.js`

### Styling Customization
- Modify `assets/styles/admin-dashboard.css` for visual changes
- Admin dashboard uses CSS custom properties for theming
- Supports both light and dark themes

### Database Schema Extensions
- Add new fields to `admin_roles` collection as needed
- Update `AdminRoles` model for new functionality
- Consider migration scripts for existing data

## Troubleshooting

### Common Issues

#### "Access denied" Error
- Ensure user has admin privileges
- Check if admin role is active in database
- Verify session is valid

#### Admin Dashboard Not Loading
- Check browser console for JavaScript errors
- Verify API endpoints are accessible
- Ensure MongoDB connection is working

#### User Search Not Working
- Check if user exists in students/faculty collections
- Verify search parameters are correct
- Check MongoDB query performance

### Debug Mode
- Enable error reporting in PHP
- Check browser developer tools
- Review server error logs
- Use MongoDB Compass to inspect collections

## Future Enhancements

### Planned Features
- **Bulk Operations**: Select multiple items for batch actions
- **Advanced Filtering**: Filter users/content by multiple criteria
- **Export/Import**: Export data in various formats
- **Audit Logs**: Detailed logging of all admin actions
- **Role Permissions**: Granular permissions for different admin levels
- **Email Notifications**: Notify users of admin actions
- **System Monitoring**: Real-time system health monitoring

### Integration Opportunities
- **LDAP Integration**: Connect with institutional directory
- **SSO Support**: Single sign-on for admin users
- **API Access**: RESTful API for external integrations
- **Webhook Support**: Real-time notifications for admin actions

## Support

For technical support or questions about the admin system:
1. Check this documentation first
2. Review error logs and console output
3. Test with a fresh admin user account
4. Contact system administrator

## Changelog

### Version 1.0.0
- Initial admin system implementation
- Basic admin role management
- Admin dashboard with overview, users, content, and roles
- Security middleware and access control
- Admin initialization script
