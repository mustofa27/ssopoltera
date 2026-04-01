<?php

return [
    'account_lockout' => [
        'max_attempts' => (int) env('SECURITY_LOCKOUT_MAX_ATTEMPTS', 5),
        'lockout_minutes' => (int) env('SECURITY_LOCKOUT_MINUTES', 15),
    ],

    'session_idle_timeout_minutes' => (int) env('SECURITY_IDLE_TIMEOUT_MINUTES', (int) env('SESSION_LIFETIME', 120)),

    'ip_filter' => [
        'enabled' => (bool) env('SECURITY_IP_FILTER_ENABLED', false),
        'enforce_allowlist' => (bool) env('SECURITY_IP_ENFORCE_ALLOWLIST', false),
        'allowlist' => array_values(array_filter(array_map('trim', explode(',', (string) env('SECURITY_IP_ALLOWLIST', ''))))),
        'denylist' => array_values(array_filter(array_map('trim', explode(',', (string) env('SECURITY_IP_DENYLIST', ''))))),
    ],

    'two_step_policy' => [
        'enforce_microsoft_login_for_roles' => array_values(array_filter(array_map('trim', explode(',', (string) env('SECURITY_2STEP_MICROSOFT_ROLES', 'super-admin,admin'))))),
    ],

    'sso' => [
        'backchannel_max_skew_seconds' => (int) env('SECURITY_SSO_BACKCHANNEL_MAX_SKEW_SECONDS', 300),
        'authorization_code_ttl_minutes' => (int) env('SECURITY_SSO_AUTH_CODE_TTL_MINUTES', 5),
        'access_token_ttl_minutes' => (int) env('SECURITY_SSO_ACCESS_TOKEN_TTL_MINUTES', 60),
        'refresh_token_ttl_minutes' => (int) env('SECURITY_SSO_REFRESH_TOKEN_TTL_MINUTES', 43200),
    ],

    'profile_sync' => [
        'enabled' => (bool) env('SECURITY_PROFILE_SYNC_ENABLED', true),
        'import_enabled' => (bool) env('SECURITY_PROFILE_IMPORT_ENABLED', true),
        'graph_base_url' => (string) env('MICROSOFT_GRAPH_BASE_URL', 'https://graph.microsoft.com/v1.0'),
        'timeout_seconds' => (int) env('SECURITY_PROFILE_SYNC_TIMEOUT_SECONDS', 30),
        'retry_attempts' => (int) env('SECURITY_PROFILE_SYNC_RETRY_ATTEMPTS', 2),
        'retry_sleep_ms' => (int) env('SECURITY_PROFILE_SYNC_RETRY_SLEEP_MS', 500),
        'batch_limit' => (int) env('SECURITY_PROFILE_SYNC_BATCH_LIMIT', 200),
        'import_page_size' => (int) env('SECURITY_PROFILE_IMPORT_PAGE_SIZE', 100),
        'import_limit' => (int) env('SECURITY_PROFILE_IMPORT_LIMIT', 1500),
        'import_include_guests' => (bool) env('SECURITY_PROFILE_IMPORT_INCLUDE_GUESTS', false),
        'import_default_role_slug' => (string) env('SECURITY_PROFILE_IMPORT_DEFAULT_ROLE', 'user'),
    ],

    'password_policy' => [
        'min_length' => (int) env('SECURITY_PASSWORD_MIN_LENGTH', 10),
        'require_letters' => (bool) env('SECURITY_PASSWORD_REQUIRE_LETTERS', true),
        'require_mixed_case' => (bool) env('SECURITY_PASSWORD_REQUIRE_MIXED_CASE', true),
        'require_numbers' => (bool) env('SECURITY_PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => (bool) env('SECURITY_PASSWORD_REQUIRE_SYMBOLS', true),
        'expiration_days' => (int) env('SECURITY_PASSWORD_EXPIRATION_DAYS', 90),
        'history_count' => (int) env('SECURITY_PASSWORD_HISTORY_COUNT', 5),
    ],
];
