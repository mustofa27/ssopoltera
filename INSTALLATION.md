# Installation Guide

## Phase 1 - Core SSO Features Implementation

This guide covers the installation and configuration of the SSO system with Microsoft 365 integration.

## Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL/PostgreSQL
- Node.js & NPM
- MAMP (if on macOS)

## Installation Steps

### 1. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Laravel Socialite with Microsoft provider
composer require laravel/socialite socialiteproviders/microsoft

# Install Node dependencies
npm install
```

### 2. Configure Environment

Copy `.env.example` to `.env`:

```bash
cp .env.example .env
```

Update your `.env` file with the following configurations:

```env
# Microsoft OAuth Configuration
MICROSOFT_CLIENT_ID=your-azure-app-client-id
MICROSOFT_CLIENT_SECRET=your-azure-app-client-secret
MICROSOFT_REDIRECT_URI=http://localhost:8000/auth/microsoft/callback
MICROSOFT_TENANT_ID=common

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ssopoltera
DB_USERNAME=root
DB_PASSWORD=your-password

# Security Policy Configuration
SECURITY_LOCKOUT_MAX_ATTEMPTS=5
SECURITY_LOCKOUT_MINUTES=15
SECURITY_IDLE_TIMEOUT_MINUTES=120
SECURITY_IP_FILTER_ENABLED=false
SECURITY_IP_ENFORCE_ALLOWLIST=false
SECURITY_IP_ALLOWLIST=
SECURITY_IP_DENYLIST=
SECURITY_2STEP_MICROSOFT_ROLES=super-admin,admin
SECURITY_SSO_BACKCHANNEL_MAX_SKEW_SECONDS=300
SECURITY_SSO_AUTH_CODE_TTL_MINUTES=5
SECURITY_SSO_ACCESS_TOKEN_TTL_MINUTES=60
SECURITY_SSO_REFRESH_TOKEN_TTL_MINUTES=43200
SECURITY_PROFILE_SYNC_ENABLED=true
SECURITY_PROFILE_SYNC_TIMEOUT_SECONDS=10
SECURITY_PROFILE_SYNC_BATCH_LIMIT=200
MICROSOFT_GRAPH_BASE_URL=https://graph.microsoft.com/v1.0
SECURITY_PASSWORD_MIN_LENGTH=10
SECURITY_PASSWORD_REQUIRE_LETTERS=true
SECURITY_PASSWORD_REQUIRE_MIXED_CASE=true
SECURITY_PASSWORD_REQUIRE_NUMBERS=true
SECURITY_PASSWORD_REQUIRE_SYMBOLS=true
SECURITY_PASSWORD_EXPIRATION_DAYS=90
SECURITY_PASSWORD_HISTORY_COUNT=5
```

### 3. Generate Application Key

```bash
php artisan key:generate
```

### 4. Run Migrations

```bash
php artisan migrate
```

### 5. Seed Default Roles

```bash
php artisan db:seed --class=RoleSeeder
```

### 6. Create Admin User

Run the following command to create your first admin user:

```bash
php artisan tinker
```

Then execute:

```php
$user = \App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => bcrypt('password'),
    'is_active' => true,
]);

$adminRole = \App\Models\Role::where('slug', 'super-admin')->first();
$user->roles()->attach($adminRole->id);
```

### 7. Configure Microsoft Azure AD

1. Go to [Azure Portal](https://portal.azure.com)
2. Navigate to "Azure Active Directory" > "App registrations"
3. Click "New registration"
4. Fill in:
   - Name: Your SSO App Name
   - Supported account types: Your choice
   - Redirect URI: `http://localhost:8000/auth/microsoft/callback`
5. After creation, note the "Application (client) ID"
6. Go to "Certificates & secrets" > Create new client secret
7. Copy the secret value immediately
8. Update your `.env` file with these credentials

### 8. Update SocialiteProviders Configuration

Add to `config/services.php` (already done):

```php
'microsoft' => [
    'client_id' => env('MICROSOFT_CLIENT_ID'),
    'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
    'redirect' => env('MICROSOFT_REDIRECT_URI'),
    'tenant' => env('MICROSOFT_TENANT_ID', 'common'),
],
```

### 9. Register Socialite Provider

Add to `config/app.php` in the 'providers' array:

```php
\SocialiteProviders\Manager\ServiceProvider::class,
```

Add to your event listeners in `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        \SocialiteProviders\Microsoft\MicrosoftExtendSocialite::class.'@handle',
    ],
];
```

### 10. Build Frontend Assets

```bash
npm run build
```

### 11. Start Development Server

```bash
php artisan serve
```

Visit `http://localhost:8000/admin` to access the admin panel.

## Features Implemented

### ✅ Phase 1 - Core Essentials

1. **Microsoft 365 Authentication** 
   - OAuth 2.0/OpenID Connect integration
   - Automatic user creation from Microsoft profile
   - Profile synchronization (name, email, avatar, department, job title)

2. **User Management**
   - Create, view, update, and deactivate users
   - User profile management
   - Track last login
   - Assign roles and applications
   - Active/Inactive status

3. **Role Management**
   - Predefined roles: Super Admin, Admin, User
   - Custom role creation
   - Permission-based access control
   - System roles protection

4. **Application Management**
   - Register applications with OAuth credentials
   - Auto-generate client ID and secret
   - Configure redirect URIs
   - Manage allowed scopes
   - Grant user access to applications

5. **Session Management**
   - Track active SSO sessions
   - View session details (IP, user agent, timestamps)
   - Revoke sessions individually or in bulk
   - Auto-cleanup expired sessions
   - Session expiration tracking

6. **Role-Based Access Control (RBAC)**
   - Middleware for role and permission checks
   - Policy-based authorization
   - Resource-level access control
   - Protected system resources

## Default Permissions

### Super Admin
- manage_users
- manage_roles
- manage_applications
- manage_sessions
- view_audit_logs
- manage_settings

### Admin
- manage_users
- manage_applications
- view_audit_logs

### User
- view_profile
- update_profile

## Accessing the System

1. **Admin Panel**: `http://localhost:8000/admin`
2. **Microsoft Login**: `http://localhost:8000/auth/microsoft`
3. **Logout**: POST to `http://localhost:8000/logout`
4. **Backchannel SLO**: POST `http://localhost:8000/sso/backchannel/logout` (HMAC-signed by client secret)
5. **OAuth Authorize**: GET `http://localhost:8000/oauth/authorize`
6. **OAuth Token**: POST `http://localhost:8000/oauth/token`
7. **OAuth Userinfo**: GET/POST `http://localhost:8000/oauth/userinfo` (Bearer token)

## Development Roadmap

## External Application Login Flow (OAuth Authorization Code)

1. Redirect user to `/oauth/authorize` with `response_type=code`, `client_id`, `redirect_uri`, `scope`, and `state`.
2. SSO validates user session + application access and redirects back with `code`.
3. Exchange `code` at `/oauth/token` using `client_id`, `client_secret`, and `redirect_uri`.
4. Use returned bearer token to call `/oauth/userinfo` and fetch user claims.

The application redirect URI must match exactly the registered redirect URI in the application settings.

## Profile Synchronization

- Manual sync (admin UI): `/profile-sync`
- Manual sync (CLI): `php artisan profile-sync:microsoft`
- Automation: schedule `php artisan profile-sync:microsoft` in cron

### Phase 1 - Core Essentials (Current - In Progress)
**Status**: Features 1-6 ✅ Completed

1. ✅ **Microsoft 365 Authentication** 
   - OAuth 2.0/OpenID Connect integration
   - Automatic user creation from Microsoft profile
   - Profile synchronization (name, email, avatar, department, job title)

2. ✅ **User Management** 
   - Create, update, deactivate users
   - User profile management
   - Track last login
   - Assign roles and applications
   - Active/Inactive status

3. ✅ **Role-Based Access Control (RBAC)** 
   - Predefined roles: Super Admin, Admin, User
   - Custom role creation
   - Permission-based access control
   - System roles protection

4. ✅ **Application Management** 
   - Register and configure applications
   - Auto-generate client ID and secret
   - Configure redirect URIs
   - Manage allowed scopes
   - Role-based access assignment
   - Client secret retrieval and regeneration
   - Secure credential storage

5. ✅ **Session Management** 
   - Track active SSO sessions
   - View session details (IP, user agent, timestamps)
   - Revoke sessions individually or in bulk
   - Auto-cleanup expired sessions
   - Session expiration tracking

6. ✅ **Organizational Identity Layer**
   - Manage master data for departments, program studies, and support units
   - Map users to their academic or organizational affiliation
   - Support identity claims for students, lecturers, and employees
   - Provide organization-based access context for connected applications
   - Keep academic and HR systems as source-of-truth through synchronization

### Phase 2 - Security & Compliance (High Priority)
**Status**: Features 7-11 ✅ Completed

7. ✅ **Audit Logging** 
   - Track all authentication and access events
   - Monitor user/role/application/session changes
   - Maintain audit trail for compliance
   - Filter and search audit logs

8. ✅ **Security Policies** 
   - Password rules (complexity, expiration, history)
   - Account lockout after failed attempts
   - Session timeout configuration
   - Two-step verification policy (Microsoft sign-in enforcement by role)
   - IP whitelisting/blacklisting

9. ✅ **Single Sign-Out (SLO)** 
   - Centralized logout functionality
   - Revoke all user sessions
   - Notify connected applications
   - Clear user context across all apps

10. ✅ **Token Management** 
   - View active access tokens
   - Revoke specific tokens
   - Set token expiration times
   - Monitor token usage

11. ✅ **Profile Synchronization** 
    - Sync user data from Microsoft 365/Azure AD
    - Automated profile updates
    - Department and organizational structure
    - Job title and location data

### Phase 3 - Enhanced Security (Important)
**Status**: ⏳ Planned

12. **Multi-Factor Authentication (MFA)** 
    - Microsoft Authenticator integration
    - TOTP (Time-based One-Time Password) support
    - SMS-based verification
    - Biometric authentication
    - Backup codes

13. **Alerts & Notifications** 
    - Security incident notifications
    - Suspicious login attempts
    - Permission changes alerts
    - Email notifications
    - Dashboard notifications

14. **Activity Dashboard** 
    - Real-time monitoring interface
    - User activity tracking
    - Login/logout events
    - Application access history
    - Failed authentication attempts

15. **Conditional Access** 
    - Location-based rules
    - Device-based rules
    - Time-based restrictions
    - Risk-based policies
    - Automatic enforcement

### Phase 4 - Management & User Experience
**Status**: ⏳ Planned

16. **Group Synchronization** 
    - Sync Azure AD groups to SSO system
    - Automatic group membership updates
    - Nested group support
    - Group-based application access
    - Dynamic group rules

17. **Reporting System** 
    - Compliance reports
    - Usage analytics
    - Authentication reports
    - Activity summaries
    - Scheduled report generation
    - Export to CSV/PDF

18. **API Management** 
    - Manage API keys for integrations
    - Scoped API access
    - Rate limiting
    - API usage statistics
    - Revoke API keys

19. **Self-Service Portal** 
    - User profile management
    - Password change functionality
    - MFA device management
    - Application preferences
    - Session management

### Phase 5 - Advanced Features
**Status**: ⏳ Planned

20. **Consent Management** 
    - Application permission controls
    - User consent tracking
    - Permission approval workflows
    - Revoke application access
    - Scope-based permissions

21. **Directory Integration** 
    - Full organizational structure sync
    - Manager relationships
    - Department hierarchies
    - Location mapping
    - Custom attributes

## Troubleshooting

### Issue: Cannot access admin panel
- Ensure you've created an admin user with super-admin role
- Check if migrations ran successfully
- Verify database connection

### Issue: Microsoft login not working
- Verify Azure AD configuration
- Check redirect URI matches exactly
- Ensure client secret hasn't expired

### Issue: Permission denied errors
- Check user has appropriate roles
- Verify roles have necessary permissions
- Check policy configurations

## Support

For issues or questions, refer to:
- Laravel Documentation: https://laravel.com/docs
- Filament Documentation: https://filamentphp.com/docs
- Microsoft Identity Platform: https://docs.microsoft.com/azure/active-directory/

## Integration Reference

- External systems integration manual: `INTEGRATION_MANUAL.md`
