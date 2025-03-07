<?php
// setup_recurring_table.php
require 'vendor/autoload.php';
$config = require 'config/database.php';

try {
    // Verbind met de MySQL server
    $dsn = "mysql:host={$config['host']};port=" . ($config['port'] ?? 3306) . ";dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "Verbonden met MySQL database.\n";
    
    // Maak de tabel aan voor terugkerende transacties
    $sql = "
    CREATE TABLE IF NOT EXISTS recurring_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        account_id INT NOT NULL,
        category_id INT,
        amount DECIMAL(10, 2) NOT NULL,
        type ENUM('expense', 'income') NOT NULL,
        description TEXT,
        frequency ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE DEFAULT NULL,
        next_due_date DATE NOT NULL,
        last_generated_date DATE DEFAULT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    );";
    
    // Voer de query uit
    $pdo->exec($sql);
    
    echo "Tabel voor terugkerende transacties succesvol aangemaakt!\n";
    
} catch (PDOException $e) {
    die("Database fout: " . $e->getMessage() . "\n");
}