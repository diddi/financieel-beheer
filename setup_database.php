<?php
require 'vendor/autoload.php';
$config = require 'config/database.php';

try {
    // Verbind met de MySQL server zonder database te specificeren
    $dsn = "mysql:host={$config['host']};port=" . ($config['port'] ?? 3306) . ";charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "Verbonden met MySQL server.\n";
    
    // Maak de database aan als deze nog niet bestaat
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET {$config['charset']} COLLATE utf8mb4_unicode_ci");
    echo "Database '{$config['database']}' aangemaakt of bestaat al.\n";
    
    // Gebruik de database
    $pdo->exec("USE `{$config['database']}`");
    
    // Lees het SQL bestand regel voor regel uit voor betere foutopsporing
    $sql = file_get_contents('database/schema.sql');
    
    // Voer queries afzonderlijk uit
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "Query uitgevoerd: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                echo "Fout bij query: " . substr($statement, 0, 100) . "\n";
                echo "Foutmelding: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "Database setup voltooid!\n";
    
} catch (PDOException $e) {
    die("Database fout: " . $e->getMessage() . "\n");
}
