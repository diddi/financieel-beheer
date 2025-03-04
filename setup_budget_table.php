<?php
require 'vendor/autoload.php';
$config = require 'config/database.php';

try {
    // Verbind met de MySQL server
    $dsn = "mysql:host={$config['host']};port=" . ($config['port'] ?? 3306) . ";dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "Verbonden met MySQL database.\n";
    
    // Lees het budget schema bestand
    $sql = file_get_contents('database/budget_schema.sql');
    
    // Voer de query uit
    $pdo->exec($sql);
    
    echo "Budget tabel succesvol aangemaakt!\n";
    
} catch (PDOException $e) {
    die("Database fout: " . $e->getMessage() . "\n");
}
