# SSO Portal

Centralized Single Sign-On (SSO) platform for campus systems, built with Laravel (Blade + controllers), Microsoft 365 integration, OAuth provider endpoints, and security/compliance controls.

## Current Status

- Phase 1 (Core Essentials): ✅ Completed
- Phase 2 (Security & Compliance): ✅ Completed
- Ready for staging deployment validation

## Implemented Features

### Core Identity & Access
- Microsoft 365 login (`/auth/microsoft` and callback)
- Local password login (policy-controlled)
- User, role, and application management
- Organizational identity layer (department, program study, support unit, affiliations)
- RBAC with permission middleware

### Security & Compliance
- Audit logging for auth and management actions
- Security policies:
  - password complexity/expiration/history
  - account lockout
  - idle session timeout
  - IP allow/deny policy
  - Microsoft-only login policy by role
- Session management and revocation

### SSO Protocol & Integration
- OAuth provider endpoints for connected apps:
  - `GET /oauth/authorize`
  - `POST /oauth/token`
  - `GET|POST /oauth/userinfo`
- Token management UI (`/tokens`): view, revoke, update expiry, usage stats
- Single Logout (SLO):
  - Portal-initiated global logout
  - App-initiated backchannel logout (`POST /sso/backchannel/logout`)
  - Connected app logout callback notifications (`logout_uri`)

### Profile Synchronization
- Microsoft Graph profile sync (manual and bulk)
- Sync status tracking (`microsoft_synced_at`, `microsoft_sync_error`)
- Admin UI (`/profile-sync`) + CLI command (`php artisan profile-sync:microsoft`)

## Quick Start

1. Install dependencies

```bash
composer install
npm install
```

2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

3. Run database setup

```bash
php artisan migrate
php artisan db:seed --class=RoleSeeder
```

4. Build and run

```bash
npm run build
php artisan serve
```

5. Access
- Admin portal: `/admin`
- Integration endpoints: see `INTEGRATION_MANUAL.md`

## Deployment Notes (Staging)

- Set `APP_ENV=staging`, `APP_DEBUG=false`
- Use HTTPS and secure cookie/session settings
- Configure Microsoft credentials and Graph access in `.env`
- Run `php artisan optimize:clear` after deployment
- Validate OAuth + SLO + profile-sync flows end-to-end

## Documentation

- Setup and roadmap: `INSTALLATION.md`
- External app integration: `INTEGRATION_MANUAL.md`

## Tech Stack

- Laravel 12
- Blade + Controllers
- MySQL/PostgreSQL
- Microsoft 365 OAuth (Socialite)
