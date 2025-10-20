<?php

return [
    // Application settings
    'displayErrorDetails' => (bool)($_ENV['APP_DEBUG'] ?? true),

    // Database settings
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => (int)($_ENV['DB_PORT'] ?? 5432),
        'name' => $_ENV['DB_NAME'] ?? 'charlymatloc',
        'user' => $_ENV['DB_USER'] ?? 'charlymatloc',
        'pass' => $_ENV['DB_PASS'] ?? 'charlymatloc',
    ],

    // JWT settings
    'auth.jwt.secret' => $_ENV['AUTH_JWT_SECRET'] ?? 'your-super-secret-jwt-key',
    'auth.jwt.expiration' => (int)($_ENV['AUTH_JWT_EXPIRATION'] ?? 3600), // 1 hour
];
