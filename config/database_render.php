<?php
// Render.com database configuration
$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl) {
    // Parse DATABASE_URL for PostgreSQL
    $parsed = parse_url($databaseUrl);
    
    return [
        'host' => $parsed['host'],
        'port' => $parsed['port'] ?? 5432,
        'database' => ltrim($parsed['path'], '/'),
        'username' => $parsed['user'],
        'password' => $parsed['pass'],
        'driver' => 'pgsql',
        'charset' => 'utf8'
    ];
} else {
    // Fallback to local development
    return [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'expense_tracker',
        'username' => 'root',
        'password' => 'root',
        'driver' => 'mysql',
        'charset' => 'utf8mb4'
    ];
}