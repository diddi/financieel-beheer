<?php
namespace App\Models;

use App\Core\Database;

class Category {
    protected static $table = 'categories';
    
    public static function getById($id, $userId = null) {
        $db = Database::getInstance();
        
        $sql = "SELECT * FROM " . self::$table . " WHERE id = ?";
        $params = [$id];
        
        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        return $db->fetch($sql, $params);
    }
    
    public static function getAllByUser($userId) {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT * FROM " . self::$table . " WHERE user_id = ? ORDER BY name", 
            [$userId]
        );
    }
    
    public static function getAllByUserAndType($userId, $type) {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT * FROM " . self::$table . " WHERE user_id = ? AND type = ? ORDER BY name", 
            [$userId, $type]
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
        $db->delete(self::$table, "id = ? AND user_id = ?", [$id, $userId]);
    }
    
    public static function createDefaultCategories($userId) {
        $db = Database::getInstance();
        
        // Uitgaven categorieën
        $expenseCategories = [
            ['name' => 'Boodschappen', 'color' => '#4CAF50'],
            ['name' => 'Restaurants', 'color' => '#FF9800'],
            ['name' => 'Transport', 'color' => '#2196F3'],
            ['name' => 'Huisvesting', 'color' => '#9C27B0'],
            ['name' => 'Nutsvoorzieningen', 'color' => '#F44336'],
            ['name' => 'Entertainment', 'color' => '#E91E63'],
            ['name' => 'Gezondheidszorg', 'color' => '#00BCD4'],
            ['name' => 'Kleding', 'color' => '#FFEB3B'],
            ['name' => 'Persoonlijke verzorging', 'color' => '#FF5722'],
            ['name' => 'Cadeaus', 'color' => '#8BC34A'],
            ['name' => 'Educatie', 'color' => '#3F51B5'],
            ['name' => 'Overige uitgaven', 'color' => '#9E9E9E']
        ];
        
        foreach ($expenseCategories as $category) {
            self::create([
                'user_id' => $userId,
                'name' => $category['name'],
                'type' => 'expense',
                'color' => $category['color']
            ]);
        }
        
        // Inkomsten categorieën
        $incomeCategories = [
            ['name' => 'Salaris', 'color' => '#4CAF50'],
            ['name' => 'Freelance', 'color' => '#2196F3'],
            ['name' => 'Cadeaus', 'color' => '#FF9800'],
            ['name' => 'Rente', 'color' => '#9C27B0'],
            ['name' => 'Overige inkomsten', 'color' => '#9E9E9E']
        ];
        
        foreach ($incomeCategories as $category) {
            self::create([
                'user_id' => $userId,
                'name' => $category['name'],
                'type' => 'income',
                'color' => $category['color']
            ]);
        }
    }
}
