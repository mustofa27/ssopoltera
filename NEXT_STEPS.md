# Next Steps - Manual Actions Required

## Summary
Phase 1 of the SSO system has been implemented successfully! All code files have been created and configured. Now you need to run several commands to set up the system.

## Step-by-Step Instructions

### 1. Install Laravel Socialite with Microsoft Provider
Run this command to install the required OAuth packages:
```bash
composer require laravel/socialite socialiteproviders/microsoft
```

### 2. Run Database Migrations
This will create all necessary tables:
```bash
php artisan migrate
```

Tables that will be created:
- Modified `users` table (added Microsoft fields)
- `roles` table
- `role_user` pivot table
- `applications` table
- `application_user` pivot table
- `sso_sessions` table

### 3. Seed Default Roles
This creates the three system roles (Super Admin, Admin, User):
```bash
php artisan db:seed --class=RoleSeeder
```

### 4. Configure Microsoft Azure AD

#### A. Create Azure AD Application
1. Go to [Azure Portal](https://portal.azure.com)
2. Navigate to **Azure Active Directory** > **App registrations**
3. Click **"New registration"**
4. Fill in the form:
   - **Name**: SSO Portal (or your preferred name)
   - **Supported account types**: Choose based on your needs
   - **Redirect URI**: Select "Web" and enter:
     ```
     http://localhost:8000/auth/microsoft/callback
     ```
5. Click **"Register"**

#### B. Get Client ID and Secret
1. After registration, copy the **Application (client) ID**
2. Go to **"Certificates & secrets"**
3. Click **"New client secret"**
4. Add a description and choose expiry period
5. Click **"Add"**
6. **Important**: Copy the secret **Value** immediately (it won't be shown again!)

#### C. Configure API Permissions
1. Go to **"API permissions"**
2. Click **"Add a permission"**
3. Select **"Microsoft Graph"**
4. Select **"Delegated permissions"**
5. Add these permissions:
   - `openid`
   - `profile`
   - `email`
   - `User.Read`
6. Click **"Add permissions"**
7. Click **"Grant admin consent"** (if you have admin rights)

### 5. Update .env File
Copy your `.env.example` to `.env` if you haven't already:
```bash
cp .env.example .env
```

Then update these values in your `.env`:
```env
APP_NAME="SSO Portal"

# Microsoft OAuth Configuration
MICROSOFT_CLIENT_ID=your-application-client-id-from-azure
MICROSOFT_CLIENT_SECRET=your-client-secret-value-from-azure
MICROSOFT_REDIRECT_URI=http://localhost:8000/auth/microsoft/callback
MICROSOFT_TENANT_ID=common

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ssopoltera
DB_USERNAME=root
DB_PASSWORD=root
```

### 6. Generate Application Key
If you haven't already:
```bash
php artisan key:generate
```

### 7. Create Your First Admin User

**Important**: This creates an emergency admin account with password access.

Option A - Via Tinker (Recommended):
```bash
php artisan tinker
```

Then paste this code:
```php
$user = \App\Models\User::create([
    'name' => 'Emergency Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('ChangeMeImmediately2024!'),
    'is_active' => true,
    'email_verified_at' => now(),
]);

$role = \App\Models\Role::where('slug', 'super-admin')->first();
$user->roles()->attach($role->id);

echo "Emergency admin created!\n";
echo "Email: admin@example.com\n";
echo "Password: ChangeMeImmediately2024!\n";
echo "CHANGE THIS PASSWORD IMMEDIATELY!\n";
exit
```

**Security Note**: This creates a "break-glass" emergency admin account. In production:
- Use an extremely strong password (20+ characters)
- Store password in secure vault (1Password, etc.)
- Limit to 1-2 accounts
- Primary access should be via Microsoft SSO

Option B - Via Database:
You can also insert directly into your database using your preferred SQL client.

### 8. Clear Cache (Optional but Recommended)
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 9. Start Development Server
```bash
php artisan serve
```

### 10. Access the Application

#### Primary Method: Microsoft 365 SSO (Recommended)
Visit: `http://localhost:8000/auth/microsoft`
- Login with your Microsoft 365 account
- User will be auto-created on first login
- Automatically assigned "User" role
- Admins can then promote users to Admin/Super Admin roles

Or use the admin panel login page:
Visit: `http://localhost:8000/admin/login`
- Click the blue "Sign in with Microsoft 365" button

#### Emergency Access: Email/Password
Visit: `http://localhost:8000/admin/login`
- Use the email/password form below the Microsoft button
- Email: `admin@example.com`
### Login Methods

**Hybrid Authentication System**: This system supports both Microsoft SSO (primary) and emergency password access.

See [AUTHENTICATION.md](AUTHENTICATION.md) for complete authentication documentation.

- Password: `ChangeMeImmediately2024!` (or what you set)
- **CHANGE THIS PASSWORD IMMEDIATELY after first login**

## What You Can Do Now

Once everything is set up:

1. **User Management** (`/admin/users`)
   - View all users
   - Edit user details
   - Assign roles to users
   - Grant application access
   - Activate/deactivate users

2. **Role Management** (`/admin/roles`)
   - View existing roles
   - Create custom roles
   - Configure permissions
   - See user counts per role

3. **Application Management** (`/admin/applications`)
   - Register new applications
   - View OAuth credentials
   - Configure redirect URIs
   - Grant user access

4. **Session Management** (`/admin/sso-sessions`)
   - View active sessions
   - Monitor user activity
   - Revoke sessions
   - Clean up expired sessions

## Troubleshooting

### Issue: "Class not found" errors
**Solution**: Run `composer dump-autoload` then try again

### Issue: Migration fails
**Solution**: 
1. Check database connection in `.env`
2. Ensure database exists: `CREATE DATABASE ssopoltera;`
3. Run migrations again

### Issue: Cannot login to admin panel
**Solution**: 
1. Ensure you created an admin user
2. Check user has `super-admin` role attached
3. Verify password is correct

### Issue: Microsoft login redirects to error page
**Solution**:
1. Verify Azure AD redirect URI matches exactly

### Issue: SSO user shows password required error
**Solution**: Ensure password column is nullable in migration. Run `php artisan migrate:fresh` if needed.

### Issue: Want to add emergency password to existing SSO user
**Solution**: 
```bash
php artisan tinker
```
Then:
```php
$user = \App\Models\User::where('email', 'user@company.com')->first();
$user->update(['password' => bcrypt('strong-password')]);
exit
```
2. Check client ID and secret are correct
3. Ensure API permissions are granted in Azure
4. Check if client secret has expired

### Issue: User created but cannot access admin panel
**Solution**: User needs appropriate role with permissions. Assign at least "Admin" role to access the panel.

## File Structure Created

```
app/
├── Http/
│   ├── Controllers/Auth/
│   │   ├── LoginController.php
│   │   └── MicrosoftAuthController.php
│   ├── Controllers/
│   │   ├── OAuthController.php
│   │   ├── SsoBackchannelLogoutController.php
│   │   ├── TokenController.php
│   │   └── ProfileSyncController.php
│   └── Middleware/
│       ├── CheckRole.php
│       ├── CheckPermission.php
│       ├── EnforceIpPolicy.php
│       ├── EnsureSessionNotIdle.php
│       └── EnsurePasswordNotExpired.php
├── Models/
│   ├── User.php (updated)
│   ├── Role.php
│   ├── Application.php
│   ├── SsoSession.php
│   └── OAuthAuthorizationCode.php
├── Support/
│   ├── AuditLogger.php
│   ├── SingleLogoutService.php
│   └── MicrosoftProfileSyncService.php
└── Providers/
   └── AppServiceProvider.php

database/
├── migrations/
│   ├── 2024_03_10_000001_add_microsoft_fields_to_users_table.php
│   ├── 2024_03_10_000002_create_roles_table.php
│   ├── 2024_03_10_000003_create_role_user_table.php
│   ├── 2024_03_10_000004_create_applications_table.php
│   ├── 2024_03_10_000005_create_application_user_table.php
│   └── 2024_03_10_000006_create_sso_sessions_table.php
└── seeders/
    └── RoleSeeder.php
```

## Testing the System

### Test Microsoft Login:
1. Visit `/auth/microsoft`
2. Login with your Microsoft 365 account
3. Should redirect to `/admin`
4. User should be auto-created

### Test User Management:
1. Login to admin panel
2. Go to Users section
3. Create/edit users
4. Assign roles and applications

### Test Application Registration:
1. Go to Applications section
2. Create new application
3. Note the auto-generated Client ID and Secret
4. Assign users to the application

### Test Session Management:
1. Login with multiple users/browsers
2. View active sessions
3. Try revoking a session
4. User should be logged out

## Security Reminders

- [ ] Change default admin password immediately
- [ ] Store Azure client secret securely
- [ ] Use HTTPS in production
- [ ] Set appropriate session timeouts
- [ ] Review and update permissions regularly
- [ ] Enable MFA for admin accounts (Phase 3)
- [ ] Regular security audits (Phase 2)

## What's Next?

After Phase 1 is working:
- **Phase 2**: Audit logging, security policies, single sign-out
- **Phase 3**: Multi-factor authentication, alerts, activity dashboard
- **Phase 4**: Group sync, reporting, API management
- **Phase 5**: Advanced features and integrations

---

Need help? Check:
- [INSTALLATION.md](INSTALLATION.md) - Detailed installation guide
- [README.md](README.md) - Project overview
- [INTEGRATION_MANUAL.md](INTEGRATION_MANUAL.md) - External integration guide
- Laravel Docs: https://laravel.com/docs
- Azure AD Docs: https://docs.microsoft.com/azure/active-directory/
