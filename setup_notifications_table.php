<?php
// setup_notifications_table.php
require 'vendor/autoload.php';
$config = require 'config/database.php';

try {
    // Verbind met de MySQL server
    $dsn = "mysql:host={$config['host']};port=" . ($config['port'] ?? 3306) . ";dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "Verbonden met MySQL database.\n";
    
    // Maak de notificaties tabel aan
    $sql = "
    CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'warning', 'danger', 'success') NOT NULL DEFAULT 'info',
        is_read BOOLEAN DEFAULT FALSE,
        related_entity VARCHAR(50) DEFAULT NULL,
        entity_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );";
    
    // Voer de query uit
    $pdo->exec($sql);
    
    echo "Notificatietabel succesvol aangemaakt!\n";
    
} catch (PDOException $e) {
    die("Database fout: " . $e->getMessage() . "\n");
}