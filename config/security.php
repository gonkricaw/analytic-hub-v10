<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all security-related configuration options for the
    | Analytics Hub application. These settings control various security
    | features including headers, CSP, rate limiting, and more.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | HTTPS Enforcement
    |--------------------------------------------------------------------------
    |
    | These settings control HTTPS enforcement behavior across the application.
    | In production, HTTPS should always be enforced for security.
    |
    */
    'https' => [
        'enforce' => env('HTTPS_ENFORCE', true),
        'redirect_status_code' => env('HTTPS_REDIRECT_STATUS', 301),
        'trust_proxies' => env('HTTPS_TRUST_PROXIES', false),
        'hsts_max_age' => env('HSTS_MAX_AGE', 31536000), // 1 year
        'hsts_include_subdomains' => env('HSTS_INCLUDE_SUBDOMAINS', true),
        'hsts_preload' => env('HSTS_PRELOAD', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | Configuration for various security headers that help protect against
    | common web vulnerabilities and attacks.
    |
    */
    'headers' => [
        'x_frame_options' => env('X_FRAME_OPTIONS', 'DENY'),
        'x_content_type_options' => env('X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'x_xss_protection' => env('X_XSS_PROTECTION', '1; mode=block'),
        'referrer_policy' => env('REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'permissions_policy' => env('PERMISSIONS_POLICY', 'geolocation=(), microphone=(), camera=()'),
        'cross_origin_embedder_policy' => env('COEP', 'require-corp'),
        'cross_origin_opener_policy' => env('COOP', 'same-origin'),
        'cross_origin_resource_policy' => env('CORP', 'same-origin'),
        'remove_server_header' => env('REMOVE_SERVER_HEADER', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy (CSP)
    |--------------------------------------------------------------------------
    |
    | CSP configuration to prevent XSS attacks and control resource loading.
    | You can override default directives here or disable CSP entirely.
    |
    */
    'csp' => [
        'enabled' => env('CSP_ENABLED', true),
        'report_only' => env('CSP_REPORT_ONLY', false),
        'report_uri' => env('CSP_REPORT_URI', null),
        'upgrade_insecure_requests' => env('CSP_UPGRADE_INSECURE', true),
        
        // Override default CSP directives
        'overrides' => [
            // Example: Add additional script sources
            // 'script-src' => [
            //     'https://additional-domain.com',
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SQL Injection Prevention
    |--------------------------------------------------------------------------
    |
    | Configuration for SQL injection detection and prevention middleware.
    | This works alongside Laravel's built-in protections.
    |
    */
    'sql_injection' => [
        'enabled' => env('SQL_INJECTION_PROTECTION', true),
        'strict_mode' => env('SQL_INJECTION_STRICT', false),
        'log_attempts' => env('SQL_INJECTION_LOG', true),
        'block_suspicious' => env('SQL_INJECTION_BLOCK', true),
        'max_violations_per_hour' => env('SQL_INJECTION_MAX_VIOLATIONS', 5),
        
        // Additional patterns to detect (regex)
        'custom_patterns' => [
            // Add custom SQL injection patterns here
        ],
        
        // Fields to exclude from checking
        'excluded_fields' => [
            'content',
            'description',
            'notes',
            'message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Global rate limiting configuration. Individual routes can override
    | these settings using the rate limiting middleware.
    |
    */
    'rate_limiting' => [
        'enabled' => env('RATE_LIMITING_ENABLED', true),
        'default_max_attempts' => env('RATE_LIMIT_MAX_ATTEMPTS', 60),
        'default_decay_minutes' => env('RATE_LIMIT_DECAY_MINUTES', 1),
        'skip_successful_requests' => env('RATE_LIMIT_SKIP_SUCCESS', false),
        
        // Route-specific rate limits
        'routes' => [
            'login' => [
                'max_attempts' => env('LOGIN_RATE_LIMIT', 5),
                'decay_minutes' => env('LOGIN_RATE_DECAY', 15),
            ],
            'password.reset' => [
                'max_attempts' => env('PASSWORD_RESET_RATE_LIMIT', 3),
                'decay_minutes' => env('PASSWORD_RESET_RATE_DECAY', 60),
            ],
            'api.*' => [
                'max_attempts' => env('API_RATE_LIMIT', 1000),
                'decay_minutes' => env('API_RATE_DECAY', 60),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Security
    |--------------------------------------------------------------------------
    |
    | Configuration for IP-based security features including blacklisting,
    | whitelisting, and geographic restrictions.
    |
    */
    'ip_security' => [
        'blacklist_enabled' => env('IP_BLACKLIST_ENABLED', true),
        'whitelist_enabled' => env('IP_WHITELIST_ENABLED', false),
        'auto_blacklist_enabled' => env('AUTO_BLACKLIST_ENABLED', true),
        'max_failed_attempts' => env('MAX_FAILED_LOGIN_ATTEMPTS', 5),
        'blacklist_duration_hours' => env('BLACKLIST_DURATION_HOURS', 24),
        
        // Geographic restrictions
        'geo_blocking' => [
            'enabled' => env('GEO_BLOCKING_ENABLED', false),
            'allowed_countries' => env('ALLOWED_COUNTRIES', ''),
            'blocked_countries' => env('BLOCKED_COUNTRIES', ''),
        ],
        
        // Trusted proxy networks
        'trusted_proxies' => [
            '127.0.0.1',
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Enhanced session security settings beyond Laravel's default configuration.
    | These settings work with the session configuration in session.php.
    |
    */
    'session' => [
        'regenerate_on_login' => env('SESSION_REGENERATE_LOGIN', true),
        'regenerate_on_privilege_change' => env('SESSION_REGENERATE_PRIVILEGE', true),
        'timeout_warning_minutes' => env('SESSION_TIMEOUT_WARNING', 5),
        'concurrent_sessions_limit' => env('CONCURRENT_SESSIONS_LIMIT', 3),
        'track_user_agent' => env('SESSION_TRACK_USER_AGENT', true),
        'track_ip_address' => env('SESSION_TRACK_IP', true),
        
        // Session fingerprinting
        'fingerprinting' => [
            'enabled' => env('SESSION_FINGERPRINTING', true),
            'include_user_agent' => true,
            'include_accept_language' => true,
            'include_accept_encoding' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for comprehensive audit logging of user activities
    | and system events for security monitoring and compliance.
    |
    */
    'audit' => [
        'enabled' => env('AUDIT_LOGGING_ENABLED', true),
        'log_all_requests' => env('AUDIT_LOG_ALL_REQUESTS', false),
        'log_failed_requests' => env('AUDIT_LOG_FAILED_REQUESTS', true),
        'log_sensitive_operations' => env('AUDIT_LOG_SENSITIVE_OPS', true),
        'retention_days' => env('AUDIT_RETENTION_DAYS', 365),
        
        // Database storage
        'database' => [
            'enabled' => env('AUDIT_DATABASE_ENABLED', true),
            'table' => env('AUDIT_TABLE', 'activity_logs'),
            'connection' => env('AUDIT_DB_CONNECTION', null),
        ],
        
        // File logging
        'file' => [
            'enabled' => env('AUDIT_FILE_ENABLED', true),
            'channel' => env('AUDIT_LOG_CHANNEL', 'audit'),
            'level' => env('AUDIT_LOG_LEVEL', 'info'),
        ],
        
        // External logging (e.g., SIEM)
        'external' => [
            'enabled' => env('AUDIT_EXTERNAL_ENABLED', false),
            'endpoint' => env('AUDIT_EXTERNAL_ENDPOINT', null),
            'api_key' => env('AUDIT_EXTERNAL_API_KEY', null),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security
    |--------------------------------------------------------------------------
    |
    | Configuration for content security features including URL encryption
    | and secure iframe rendering as described in Logic.md.
    |
    */
    'content' => [
        'url_encryption' => [
            'enabled' => env('CONTENT_URL_ENCRYPTION', true),
            'algorithm' => env('CONTENT_ENCRYPTION_ALGO', 'AES-256-CBC'),
            'key' => env('CONTENT_ENCRYPTION_KEY', null),
        ],
        
        'one_time_tokens' => [
            'enabled' => env('CONTENT_ONE_TIME_TOKENS', true),
            'expiry_minutes' => env('CONTENT_TOKEN_EXPIRY', 60),
            'max_uses' => env('CONTENT_TOKEN_MAX_USES', 1),
        ],
        
        'iframe_security' => [
            'enabled' => env('IFRAME_SECURITY_ENABLED', true),
            'allowed_domains' => explode(',', env('IFRAME_ALLOWED_DOMAINS', '')),
            'sandbox_attributes' => env('IFRAME_SANDBOX', 'allow-scripts allow-same-origin'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Security
    |--------------------------------------------------------------------------
    |
    | Enhanced password security settings including complexity requirements,
    | history tracking, and expiration policies.
    |
    */
    'password' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', true),
        'history_limit' => env('PASSWORD_HISTORY_LIMIT', 5),
        'expiry_days' => env('PASSWORD_EXPIRY_DAYS', 90),
        'warning_days' => env('PASSWORD_WARNING_DAYS', 7),
        
        // Common password checking
        'check_common_passwords' => env('PASSWORD_CHECK_COMMON', true),
        'check_personal_info' => env('PASSWORD_CHECK_PERSONAL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    |
    | Configuration for two-factor authentication features.
    |
    */
    'two_factor' => [
        'enabled' => env('TWO_FACTOR_ENABLED', false),
        'required_for_admin' => env('TWO_FACTOR_REQUIRED_ADMIN', true),
        'backup_codes_count' => env('TWO_FACTOR_BACKUP_CODES', 8),
        'totp_window' => env('TWO_FACTOR_TOTP_WINDOW', 1),
        'remember_device_days' => env('TWO_FACTOR_REMEMBER_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Security
    |--------------------------------------------------------------------------
    |
    | Security settings for file uploads including type restrictions,
    | size limits, and virus scanning.
    |
    */
    'file_upload' => [
        'enabled' => env('FILE_UPLOAD_ENABLED', true),
        'max_size_mb' => env('FILE_UPLOAD_MAX_SIZE', 10),
        'allowed_extensions' => explode(',', env('FILE_UPLOAD_ALLOWED_EXT', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx')),
        'allowed_mime_types' => explode(',', env('FILE_UPLOAD_ALLOWED_MIME', 'image/jpeg,image/png,image/gif,application/pdf')),
        'scan_for_viruses' => env('FILE_UPLOAD_VIRUS_SCAN', false),
        'quarantine_suspicious' => env('FILE_UPLOAD_QUARANTINE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Security
    |--------------------------------------------------------------------------
    |
    | Security settings specific to API endpoints.
    |
    */
    'api' => [
        'rate_limit_per_minute' => env('API_RATE_LIMIT_PER_MINUTE', 60),
        'require_api_key' => env('API_REQUIRE_KEY', true),
        'api_key_header' => env('API_KEY_HEADER', 'X-API-Key'),
        'cors_enabled' => env('API_CORS_ENABLED', true),
        'allowed_origins' => explode(',', env('API_ALLOWED_ORIGINS', '*')),
    ],

];