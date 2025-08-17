<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Transaction Logging Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the transaction logging
    | system. You can enable/disable logging, set retention policies,
    | and configure what data should be logged.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enable Transaction Logging
    |--------------------------------------------------------------------------
    |
    | This option controls whether transaction logging is enabled for your
    | application. You may disable this during development or in certain
    | environments where logging is not required.
    |
    */
    'enabled' => env('TRANSACTION_LOG_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Log Retention Days
    |--------------------------------------------------------------------------
    |
    | Number of days to keep transaction logs. Older logs will be
    | automatically purged. Set to 0 to disable automatic cleanup.
    |
    */
    'retention_days' => env('TRANSACTION_LOG_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Exclude Routes
    |--------------------------------------------------------------------------
    |
    | Array of route patterns that should be excluded from logging.
    | Supports wildcards (*) for pattern matching.
    |
    */
    'exclude_routes' => [
        'api/health',
        'api/status',
        'api/ping',
        'up',
        '_debugbar/*',
        'horizon/*',
        'telescope/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exclude Methods
    |--------------------------------------------------------------------------
    |
    | HTTP methods that should be excluded from logging.
    |
    */
    'exclude_methods' => [
        'OPTIONS',
        'HEAD',
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Request Payload
    |--------------------------------------------------------------------------
    |
    | Whether to log the request payload. This includes form data,
    | JSON data, and query parameters.
    |
    */
    'log_request_payload' => env('TRANSACTION_LOG_REQUEST_PAYLOAD', true),

    /*
    |--------------------------------------------------------------------------
    | Log Response Payload
    |--------------------------------------------------------------------------
    |
    | Whether to log the response payload. Note that this can significantly
    | increase storage requirements for APIs that return large datasets.
    |
    */
    'log_response_payload' => env('TRANSACTION_LOG_RESPONSE_PAYLOAD', true),

    /*
    |--------------------------------------------------------------------------
    | Max Payload Size
    |--------------------------------------------------------------------------
    |
    | Maximum size (in bytes) for request/response payloads to be logged.
    | Larger payloads will be truncated. Set to 0 for no limit.
    |
    */
    'max_payload_size' => env('TRANSACTION_LOG_MAX_PAYLOAD_SIZE', 10240), // 10KB

    /*
    |--------------------------------------------------------------------------
    | Log Headers
    |--------------------------------------------------------------------------
    |
    | Whether to log request and response headers. Sensitive headers
    | will be automatically sanitized.
    |
    */
    'log_headers' => env('TRANSACTION_LOG_HEADERS', true),

    /*
    |--------------------------------------------------------------------------
    | Sensitive Fields
    |--------------------------------------------------------------------------
    |
    | Fields that should be hidden/masked in logs for security purposes.
    | These apply to both request and response data.
    |
    */
    'sensitive_fields' => [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'api_key',
        'secret',
        'private_key',
        'access_token',
        'refresh_token',
        'authorization',
        'x-api-key',
        'cookie',
        'x-csrf-token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Async Logging
    |--------------------------------------------------------------------------
    |
    | Whether to log transactions asynchronously using queues.
    | This improves response times but requires a queue worker.
    |
    */
    'async' => env('TRANSACTION_LOG_ASYNC', true),

    /*
    |--------------------------------------------------------------------------
    | Log Failed Requests Only
    |--------------------------------------------------------------------------
    |
    | If true, only log requests that result in 4xx or 5xx status codes.
    | This can significantly reduce log volume in production.
    |
    */
    'log_failed_only' => env('TRANSACTION_LOG_FAILED_ONLY', false),

    /*
    |--------------------------------------------------------------------------
    | Performance Metrics
    |--------------------------------------------------------------------------
    |
    | Whether to capture performance metrics like execution time
    | and memory usage for each request.
    |
    */
    'performance_metrics' => env('TRANSACTION_LOG_PERFORMANCE', true),

    /*
    |--------------------------------------------------------------------------
    | Device Detection
    |--------------------------------------------------------------------------
    |
    | Whether to capture device, browser, and platform information
    | from the User-Agent header.
    |
    */
    'device_detection' => env('TRANSACTION_LOG_DEVICE_DETECTION', true),

    /*
    |--------------------------------------------------------------------------
    | IP Geolocation
    |--------------------------------------------------------------------------
    |
    | Whether to perform IP geolocation lookup for request IPs.
    | This requires an external service and may impact performance.
    |
    */
    'ip_geolocation' => env('TRANSACTION_LOG_IP_GEOLOCATION', false),

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | Database connection to use for storing transaction logs.
    | Leave null to use the default connection.
    |
    */
    'database_connection' => env('TRANSACTION_LOG_DB_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Queue Connection
    |--------------------------------------------------------------------------
    |
    | Queue connection to use for async logging when enabled.
    | Leave null to use the default queue connection.
    |
    */
    'queue_connection' => env('TRANSACTION_LOG_QUEUE_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Queue Name
    |--------------------------------------------------------------------------
    |
    | Queue name to use for async logging jobs.
    |
    */
    'queue_name' => env('TRANSACTION_LOG_QUEUE_NAME', 'transaction-logs'),
];
