# Hybrid Authentication Setup

## Overview
This SSO system uses a **hybrid authentication approach**:
- **Primary**: Microsoft 365 SSO (recommended for all users)
- **Emergency**: Email/password login (for break-glass admin access)

## Login Methods

### 1. Microsoft 365 SSO (Recommended)
- Visit: `http://localhost:8000/auth/microsoft`
- Or click "Sign in with Microsoft 365" on the login page
- Users are auto-created on first login
- Password field is NULL for SSO users
- Profile auto-syncs from Microsoft

### 2. Emergency Admin Access
- Visit: `http://localhost:8000/admin/login`
- Use email/password form (below the Microsoft button)
- For break-glass accounts only
- Password is hashed/stored for these accounts

## User Types

### SSO Users (Recommended)
```php
User::create([
    'name' => 'John Doe',
    'email' => 'john@company.com',
    'microsoft_id' => '12345-67890',
    'password' => null, // No password needed
    'is_active' => true,
]);
```

### Emergency Admin Users
```php
User::create([
    'name' => 'Emergency Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('very-secure-password'),
    'microsoft_id' => null, // No Microsoft ID
    'is_active' => true,
]);
```

## Creating Break-Glass Admin Account

After running migrations and seeders:

```bash
php artisan tinker
```

Then:
```php
$admin = \App\Models\User::create([
    'name' => 'Emergency Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('ChangeMeImmediately2024!'),
    'is_active' => true,
    'email_verified_at' => now(),
]);

$role = \App\Models\Role::where('slug', 'super-admin')->first();
$admin->roles()->attach($role->id);

echo "Emergency admin created!\n";
echo "Email: admin@example.com\n";
echo "CHANGE THE PASSWORD IMMEDIATELY!\n";
exit
```

## Security Best Practices

### For SSO Users
✅ Require MFA in Azure AD (recommended)
✅ Use conditional access policies
✅ Auto-disable when removed from Azure AD
✅ Regular access reviews

### For Emergency Admins
✅ Use extremely strong passwords (20+ characters)
✅ Store passwords in secure vault (1Password, LastPass, etc.)
✅ Limit to 1-2 accounts maximum
✅ Change passwords quarterly
✅ Monitor login attempts
✅ Use only when Microsoft OAuth is unavailable

## Login Page Features

The custom login page shows:
1. **Large blue button**: "Sign in with Microsoft 365" (primary CTA)
2. **Divider**: "Or use emergency admin access"
3. **Email field**: Labeled "Email (Emergency Admin Access)"
4. **Password field**: With reveal/hide toggle
5. **Remember me**: Standard checkbox

## Authentication Flow

### Microsoft SSO Flow
```
User → /auth/microsoft 
    → Microsoft login page 
    → Consent (first time)
    → /auth/microsoft/callback
    → Create/update user
    → Assign default role (if new)
    → Login → /admin
```

### Password Flow
```
User → /admin/login
    → Enter email/password
    → Validate credentials
    → Check is_active status
    → Login → /admin
```

## Active User Enforcement

The system automatically:
- Checks `is_active` status on every request
- Logs out inactive users immediately
- Shows error message: "Your account has been deactivated"
- Applies to both SSO and password logins

## Disabling Users

When you deactivate a user:
```php
$user->update(['is_active' => false]);
```

Result:
- ✅ Immediately logged out on next request
- ✅ Cannot login via password
- ✅ Cannot login via Microsoft SSO
- ✅ All active sessions invalidated

## Migration Notes

The password column is now **nullable**:
```php
$table->string('password')->nullable(); // Nullable for SSO users
```

This allows:
- SSO users to have `password = null`
- Emergency admins to have hashed passwords
- System validates which auth method to use

## Troubleshooting

### "Password required" error for SSO users
**Solution**: Ensure password column is nullable in migration

### Cannot login with Microsoft
**Solution**: 
1. Check Azure AD configuration
2. Verify redirect URI matches exactly
3. Check client ID/secret in .env
4. Ensure user's Microsoft account has access

### Emergency admin can't login
**Solution**:
1. Verify password was set (not null)
2. Check is_active = true
3. Verify email matches exactly
4. Password might need reset

### Want to convert password user to SSO
```php
$user = User::where('email', 'user@company.com')->first();
$user->update([
    'password' => null,
    'microsoft_id' => '...' // Will be set on first Microsoft login
]);
```

### Want to add emergency password to SSO user
```php
$user = User::where('email', 'user@company.com')->first();
$user->update([
    'password' => bcrypt('strong-password-here')
]);
// User can now login both ways
```

## Recommended Setup

1. **Production Environment**:
   - 99% of users: Microsoft SSO only
   - 1-2 accounts: Emergency admin with password
   - Store emergency passwords in company vault
   - Document emergency access procedures

2. **Development Environment**:
   - Both methods available for testing
   - Test accounts with passwords for quick access
   - Regular users still use Microsoft SSO

3. **Monitoring**:
   - Alert on emergency admin logins (Phase 2)
   - Track authentication methods used
   - Regular review of accounts with passwords

## Future Enhancements (Phase 2+)

- Audit logging for all login attempts
- Alert admins when emergency access is used
- Time-based access for emergency accounts
- IP restrictions for password logins
- Failed login attempt tracking
- Account lockout policies

---

**Current Status**: Hybrid authentication fully implemented ✅
**Login Page**: Custom Filament page with Microsoft button
**Password Column**: Nullable for flexibility
