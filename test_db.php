<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Laad autoloader met correct pad
require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config/database.php';

echo "Proberen te verbinden met database...<br>";
echo "Host: " . $config['host'] . "<br>";
echo "Database: " . $config['database'] . "<br>";
echo "Gebruiker: " . $config['username'] . "<br>";

try {
    // Verbind met de MySQL server
    $dsn = "mysql:host={$config['host']};port=" . ($config['port'] ?? 3306);
    $pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "<p style='color:green'>Verbonden met MySQL server!</p>";
    
    // Controleer of database bestaat
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$config['database']}'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>Database '{$config['database']}' bestaat.</p>";
    } else {
        echo "<p style='color:orange'>Database '{$config['database']}' bestaat niet. Aanmaken...</p>";
        $pdo->exec("CREATE DATABASE `{$config['database']}` CHARACTER SET {$config['charset']} COLLATE utf8mb4_unicode_ci");
        echo "<p style='color:green'>Database '{$config['database']}' aangemaakt.</p>";
    }
    
    // Gebruik de database
    $pdo->exec("USE `{$config['database']}`");
    
    // Controleer of tabellen bestaan
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Bestaande tabellen: " . implode(", ", $tables) . "</p>";
    
    if (!in_array('users', $tables)) {
        echo "<p style='color:orange'>Gebruikerstabel ontbreekt. Schema wordt uitgevoerd...</p>";
        
        // Voer schema uit
        $sql = file_get_contents(__DIR__ . '/database/schema.sql');
        $pdo->exec($sql);
        
        echo "<p style='color:green'>Schema uitgevoerd.</p>";
    }
    
    echo "<p style='color:green'>Database setup is compleet!</p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Database fout: " . $e->getMessage() . "</p>";
}
