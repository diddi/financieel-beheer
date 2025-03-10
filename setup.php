<?php
/**
 * Geconsolideerd setup script voor Financieel Beheer applicatie
 * 
 * Dit script voert de volgende taken uit:
 * - Aanmaken van de database en basis tabellen
 * - Aanmaken van budget tabel
 * - Aanmaken van savings tabel
 * - Aanmaken van recurring transactions tabel
 * - Aanmaken van notificaties tabel
 * - Toevoegen van reset wachtwoord kolommen
 */

// Laad de geconsolideerde autoloader
require_once __DIR__ . '/autoload.php';

// Database configuratie laden
$config = require __DIR__ . '/config/database.php';

echo "=== FINANCIEEL BEHEER SETUP ===\n\n";

try {
    // Verbind met de MySQL server zonder database te specificeren
    $dsn = "mysql:host={$config['host']};port=" . ($config['port'] ?? 3306) . ";charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "✓ Verbonden met MySQL server.\n";
    
    // Maak de database aan als deze nog niet bestaat
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET {$config['charset']} COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '{$config['database']}' aangemaakt of bestaat al.\n";
    
    // Gebruik de database
    $pdo->exec("USE `{$config['database']}`");
    
    // Installeer basis schema
    installSchema($pdo, 'database/schema.sql', 'basis tabellen');
    
    // Installeer budget tabel
    installSchema($pdo, 'database/budget_schema.sql', 'budget tabel');
    
    // Installeer savings tabel
    installSchema($pdo, 'database/savings_schema.sql', 'savings tabellen');
    
    // Installeer recurring tabel
    installSchema($pdo, 'database/recurring_schema.sql', 'recurring transactions tabel');
    
    // Installeer notifications tabel
    installSchema($pdo, 'database/notifications_schema.sql', 'notificaties tabel');
    
    // Controleer of de wachtwoord reset kolommen bestaan
    addPasswordResetColumns($pdo);
    
    echo "\n✓ Database setup volledig voltooid!\n";
    
} catch (PDOException $e) {
    die("❌ Database fout: " . $e->getMessage() . "\n");
}

/**
 * Installeer schema uit een SQL bestand
 */
function installSchema(PDO $pdo, string $filename, string $description) {
    echo "\n== Installeer $description ==\n";
    
    if (!file_exists($filename)) {
        echo "❌ Bestand $filename niet gevonden!\n";
        return;
    }
    
    try {
        // Lees het SQL bestand
        $sql = file_get_contents($filename);
        
        // Voer queries afzonderlijk uit
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        $count = 0;
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    $count++;
                } catch (PDOException $e) {
                    echo "❌ Fout bij query: " . substr($statement, 0, 100) . "\n";
                    echo "   Foutmelding: " . $e->getMessage() . "\n";
                    
                    // Als de tabel al bestaat, is dat geen probleem
                    if (strpos($e->getMessage(), 'already exists') !== false) {
                        echo "   (Dit is geen probleem, tabel bestaat al)\n";
                    } else {
                        // Andere fouten tonen maar doorgaan
                        throw $e;
                    }
                }
            }
        }
        
        echo "✓ $count queries uitgevoerd voor $description.\n";
        
    } catch (PDOException $e) {
        echo "❌ Fout bij installeren van $description: " . $e->getMessage() . "\n";
    }
}

/**
 * Voeg wachtwoord reset kolommen toe aan de users tabel
 */
function addPasswordResetColumns(PDO $pdo) {
    echo "\n== Controle van wachtwoord reset kolommen ==\n";
    
    try {
        // Controleer of de kolommen al bestaan
        $columns = $pdo->query("SHOW COLUMNS FROM users WHERE Field IN ('reset_token', 'reset_expires')");
        $existingColumns = $columns->fetchAll(PDO::FETCH_COLUMN);
        
        // Voeg reset_token kolom toe als deze nog niet bestaat
        if (!in_array('reset_token', $existingColumns)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(100) DEFAULT NULL");
            echo "✓ Kolom reset_token toegevoegd aan users tabel.\n";
        } else {
            echo "✓ Kolom reset_token bestaat al.\n";
        }
        
        // Voeg reset_expires kolom toe als deze nog niet bestaat
        if (!in_array('reset_expires', $existingColumns)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN reset_expires DATETIME DEFAULT NULL");
            echo "✓ Kolom reset_expires toegevoegd aan users tabel.\n";
        } else {
            echo "✓ Kolom reset_expires bestaat al.\n";
        }
    } catch (PDOException $e) {
        echo "❌ Fout bij toevoegen wachtwoord reset kolommen: " . $e->getMessage() . "\n";
    }
} 