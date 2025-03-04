<?php
namespace App\Models;

use App\Core\Database;

class Account {
    protected static $table = 'accounts';
    
    public static function getById($id, $userId) {
        $db = Database::getInstance();
        return $db->fetch(
            "SELECT a.*, at.name as type_name 
             FROM " . self::$table . " a 
             JOIN account_types at ON a.account_type_id = at.id 
             WHERE a.id = ? AND a.user_id = ?", 
            [$id, $userId]
        );
    }
    
    public static function getAllByUser($userId) {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT a.*, at.name as type_name 
             FROM " . self::$table . " a 
             JOIN account_types at ON a.account_type_id = at.id 
             WHERE a.user_id = ? 
             ORDER BY a.name", 
            [$userId]
        );
    }
    
    public static function create($data) {
        $db = Database::getInstance();
        return $db->insert(self::$table, $data);
    }
    
    public static function update($id, $data, $userId) {
        $db = Database::getInstance();
        $db->update(self::$table, $data, "id = ? AND user_id = ?", [$id, $userId]);
    }
    
    public static function delete($id, $userId) {
        $db = Database::getInstance();
        
        // Controleer of er transacties zijn die deze rekening gebruiken
        $transactions = $db->fetch(
            "SELECT COUNT(*) as count FROM transactions WHERE account_id = ? AND user_id = ?", 
            [$id, $userId]
        );
        
        if ($transactions['count'] > 0) {
            throw new \Exception("Deze rekening kan niet worden verwijderd omdat er transacties aan gekoppeld zijn.");
        }
        
        $db->delete(self::$table, "id = ? AND user_id = ?", [$id, $userId]);
    }
    
    public static function createDefaultAccounts($userId) {
        $db = Database::getInstance();
        
        // Standaard rekeningen aanmaken
        $accountTypes = $db->fetchAll("SELECT * FROM account_types");
        $accountTypeMap = [];
        
        foreach ($accountTypes as $type) {
            $accountTypeMap[$type['name']] = $type['id'];
        }
        
        // Bankrekening
        self::create([
            'user_id' => $userId,
            'account_type_id' => $accountTypeMap['Bankrekening'],
            'name' => 'Hoofdrekening',
            'balance' => 0,
            'currency' => 'EUR'
        ]);
        
        // Spaarrekening
        self::create([
            'user_id' => $userId,
            'account_type_id' => $accountTypeMap['Spaarrekening'],
            'name' => 'Spaarrekening',
            'balance' => 0,
            'currency' => 'EUR'
        ]);
        
        // Contant
        self::create([
            'user_id' => $userId,
            'account_type_id' => $accountTypeMap['Contant'],
            'name' => 'Contant',
            'balance' => 0,
            'currency' => 'EUR'
        ]);
    }
}
