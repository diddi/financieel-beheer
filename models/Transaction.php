<?php
namespace App\Models;

use App\Core\Database;

class Transaction {
    protected static $table = 'transactions';
    
    public static function getById($id, $userId) {
        $db = Database::getInstance();
        return $db->fetch(
            "SELECT t.*, c.name as category_name, a.name as account_name 
             FROM " . self::$table . " t 
             LEFT JOIN categories c ON t.category_id = c.id 
             LEFT JOIN accounts a ON t.account_id = a.id 
             WHERE t.id = ? AND t.user_id = ?", 
            [$id, $userId]
        );
    }
    
    public static function getAllByUser($userId, $filters = []) {
        $db = Database::getInstance();
        
        $sql = "SELECT t.*, c.name as category_name, c.color, a.name as account_name 
                FROM " . self::$table . " t 
                LEFT JOIN categories c ON t.category_id = c.id 
                LEFT JOIN accounts a ON t.account_id = a.id 
                WHERE t.user_id = ?";
        $params = [$userId];
        
        // Voeg filters toe
        if (!empty($filters['account_id'])) {
            $sql .= " AND t.account_id = ?";
            $params[] = $filters['account_id'];
        }
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND t.category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        if (!empty($filters['type'])) {
            $sql .= " AND t.type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND t.date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND t.date <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY t.date DESC, t.created_at DESC";
        
        // Paginatie
        if (isset($filters['limit'])) {
            $offset = isset($filters['offset']) ? $filters['offset'] : 0;
            $sql .= " LIMIT ?, ?";
            $params[] = (int)$offset;
            $params[] = (int)$filters['limit'];
        }
        
        return $db->fetchAll($sql, $params);
    }
    
    public static function create($data) {
        $db = Database::getInstance();
        $transactionId = $db->insert(self::$table, $data);
        
        // Update account balance
        self::updateAccountBalance($data['account_id'], $data['amount'], $data['type']);
        
        return $transactionId;
    }
    
    public static function update($id, $data, $userId) {
        $db = Database::getInstance();
        
        // Haal de huidige transactie op
        $current = self::getById($id, $userId);
        if (!$current) {
            throw new \Exception("Transactie niet gevonden");
        }
        
        // Update account balance (reverse old transaction)
        $oldType = $current['type'];
        $oldAmount = $current['amount'];
        $oldAccountId = $current['account_id'];
        
        if ($oldType === 'expense') {
            self::updateAccountBalance($oldAccountId, $oldAmount, 'income');
        } else if ($oldType === 'income') {
            self::updateAccountBalance($oldAccountId, $oldAmount, 'expense');
        }
        
        // Apply new transaction
        self::updateAccountBalance($data['account_id'], $data['amount'], $data['type']);
        
        // Update transaction record
        $db->update(self::$table, $data, "id = ? AND user_id = ?", [$id, $userId]);
    }
    
    public static function delete($id, $userId) {
        $db = Database::getInstance();
        
        // Haal de huidige transactie op
        $current = self::getById($id, $userId);
        if (!$current) {
            throw new \Exception("Transactie niet gevonden");
        }
        
        // Reverse the effect on account balance
        $type = $current['type'] === 'expense' ? 'income' : 'expense';
        self::updateAccountBalance($current['account_id'], $current['amount'], $type);
        
        // Delete the transaction
        $db->delete(self::$table, "id = ? AND user_id = ?", [$id, $userId]);
    }
    
    private static function updateAccountBalance($accountId, $amount, $type) {
        $db = Database::getInstance();
        
        $sql = "UPDATE accounts SET balance = balance ";
        
        if ($type === 'expense') {
            $sql .= "- ?";
        } else if ($type === 'income') {
            $sql .= "+ ?";
        } else {
            // Voor overschrijvingen tussen rekeningen, wordt dit apart afgehandeld
            return;
        }
        
        $sql .= " WHERE id = ?";
        $db->query($sql, [$amount, $accountId]);
    }
}
