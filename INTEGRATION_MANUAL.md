# Integration Manual for External Systems

This manual explains how another system can integrate with this SSO server for:
- User sign-in (OAuth Authorization Code flow)
- User profile retrieval (`userinfo`)
- Global logout propagation (backchannel SLO)

## 1) Prerequisites

Before integrating, register the external application in the SSO admin panel:
- Menu: Applications
- Required fields:
  - Name
  - Redirect URI
- Recommended fields:
  - Allowed Scopes
  - Logout Callback URI (for receiving SLO notifications)

After registration, keep these values secure:
- `client_id`
- `client_secret`

## 2) Base Endpoints

Use your staging domain in front of these paths:
- Authorization: `GET /oauth/authorize`
- Token Exchange: `POST /oauth/token`
- User Info: `GET /oauth/userinfo` (or `POST /oauth/userinfo`)
- Backchannel Logout (initiate from client app): `POST /sso/backchannel/logout`

## 3) Login Integration (OAuth Authorization Code)

### Step A — Redirect user to authorization endpoint

Redirect browser to:

`/oauth/authorize?response_type=code&client_id=YOUR_CLIENT_ID&redirect_uri=YOUR_REGISTERED_REDIRECT_URI&scope=openid%20profile%20email&state=RANDOM_CSRF_TOKEN`

Notes:
- `redirect_uri` must exactly match the value registered in SSO.
- User must already be authenticated in SSO and have access to your application.

Success response:
- Browser is redirected back to your `redirect_uri` with query params:
  - `code`
  - `state` (if provided)

Error response:
- Redirect with:
  - `error`
  - `error_description`
  - `state` (if provided)

### Step B — Exchange authorization code for token

Send a backend request:

```bash
curl -X POST "https://your-sso-domain/oauth/token" \
  -H "Accept: application/json" \
  -d "grant_type=authorization_code" \
  -d "code=AUTHORIZATION_CODE" \
  -d "client_id=YOUR_CLIENT_ID" \
  -d "client_secret=YOUR_CLIENT_SECRET" \
  -d "redirect_uri=YOUR_REGISTERED_REDIRECT_URI"
```

Expected success response:

```json
{
  "access_token": "...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "refresh_token": "...",
  "scope": "openid profile email"
}
```

Important:
- `refresh_token` is currently issued but no refresh endpoint is provided yet.
- When `access_token` expires, re-run authorization code flow.

### Step C — Fetch user profile

Use bearer token:

```bash
curl -X GET "https://your-sso-domain/oauth/userinfo" \
  -H "Authorization: Bearer ACCESS_TOKEN" \
  -H "Accept: application/json"
```

Typical response:

```json
{
  "sub": "123",
  "name": "User Name",
  "email": "user@example.com",
  "avatar": null,
  "department": "Faculty of Engineering",
  "job_title": "Lecturer",
  "user_type": "employee",
  "employee_type": "lecturer",
  "nip": "1987...",
  "nrp": null,
  "organization": {
    "department": "Faculty of Engineering",
    "program_study": "Informatics",
    "support_unit": null
  }
}
```

## 4) Backchannel SLO (Initiated by External App)

Use this when your app wants to trigger global logout in SSO and other connected apps.

Endpoint:
- `POST /sso/backchannel/logout`

Request body JSON:

```json
{
  "client_id": "YOUR_CLIENT_ID",
  "user_id": 123,
  "timestamp": 1710000000
}
```

`user_email` can be used instead of `user_id`.

### Signature requirement

You must send header `X-SSO-Signature` as:
- HMAC SHA-256 of the **raw request body string**
- Secret key: your `client_secret`

Pseudo-code:

```text
signature = HMAC_SHA256(rawJsonBody, client_secret)
```

Header example:

```text
X-SSO-Signature: <hex_hmac_signature>
```

Additional rule:
- `timestamp` must be within allowed skew window (configured in SSO).

Success response:

```json
{
  "message": "Single logout completed.",
  "data": {
    "revoked_sessions": 3,
    "notified_applications": 2,
    "failed_notifications": 0
  }
}
```

## 5) Receiving SSO Logout Callback in External App

If your app has `logout_uri` configured in SSO, it may receive callback payloads when global logout occurs.

Payload fields:
- `event`
- `user_id`
- `user_email`
- `application_slug`
- `occurred_at`
- `source`
- `method`

Headers:
- `X-SSO-Event: single_logout`
- `X-SSO-Signature: <hmac>`

Recommendation:
- Verify signature using your `client_secret`.
- Revoke local user session immediately after successful verification.
- Return HTTP 200 quickly.

## 6) Integration Checklist

- Register app and save `client_id/client_secret`
- Configure exact `redirect_uri`
- Configure `logout_uri` for SLO callback
- Implement authorization redirect
- Implement token exchange on backend only
- Store token securely and respect `expires_in`
- Implement `userinfo` mapping in local user model
- Implement backchannel logout request signing
- Implement logout callback verification and local session revocation

## 7) Security Recommendations

- Always use HTTPS in staging and production.
- Never expose `client_secret` in frontend/browser code.
- Rotate `client_secret` periodically.
- Validate `state` in auth flow to mitigate CSRF.
- Log integration failures with request correlation IDs.

## 8) Operational Notes

- This is a minimal OAuth provider implementation.
- Refresh-token grant is not available yet.
- If you need token refresh endpoint support, add it as next enhancement.
