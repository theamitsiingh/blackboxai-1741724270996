<?php
return [
    'app' => [
        'name' => $_ENV['APP_NAME'] ?? 'Smart Contract Audit System',
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => $_ENV['APP_DEBUG'] ?? false,
        'url' => $_ENV['APP_URL'] ?? 'http://localhost',
        'base_path' => $_ENV['BASE_PATH'] ?? '',
    ],
    
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'name' => $_ENV['DB_NAME'] ?? 'smart_contract_audit',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'pass' => $_ENV['DB_PASS'] ?? '',
    ],
    
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? '',
        'expiration' => $_ENV['JWT_EXPIRATION'] ?? 3600,
    ],
    
    'mail' => [
        'host' => $_ENV['MAIL_HOST'] ?? '',
        'port' => $_ENV['MAIL_PORT'] ?? 587,
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'from' => [
            'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com',
            'name' => $_ENV['MAIL_FROM_NAME'] ?? 'Smart Contract Audit System',
        ],
    ],
    
    'logging' => [
        'channel' => $_ENV['LOG_CHANNEL'] ?? 'file',
        'level' => $_ENV['LOG_LEVEL'] ?? 'debug',
        'path' => $_ENV['LOG_PATH'] ?? 'logs/app.log',
    ],
    
    'security' => [
        'cors' => [
            'allowed_origins' => explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? ''),
        ],
    ],
];
